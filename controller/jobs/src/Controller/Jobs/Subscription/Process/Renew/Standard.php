<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\Renew;

use \Aimeos\MW\Logger\Base as Log;


/**
 * Job controller for subscription processs renew.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Subscription\Process\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Subscription process renew' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Renews subscriptions at next date' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->getContext();
		$config = $context->getConfig();

		/** controller/common/subscription/process/payment-ends
		 * Subscriptions ends if payment couldn't be captured
		 *
		 * By default, a subscription ends automatically if the next payment couldn't
		 * be captured. When setting this configuration to FALSE, the subscription job
		 * controller will try to capture the payment at the next run again until the
		 * subscription is deactivated manually.
		 *
		 * @param bool TRUE if payment failures ends the subscriptions, FALSE if not
		 * @since 2019.10
		 * @category Developer
		 * @category User
		 * @see controller/common/subscription/process/processors
		 * @see controller/common/subscription/process/payment-days
		 * @see controller/common/subscription/process/payment-status
		 */
		$end = (bool) $config->get( 'controller/common/subscription/process/payment-ends', true );

		$names = (array) $config->get( 'controller/common/subscription/process/processors', [] );

		$date = date( 'Y-m-d' );
		$processors = $this->getProcessors( $names );
		$manager = \Aimeos\MShop::create( $context, 'subscription' );
		$baseManager = \Aimeos\MShop::create( $context, 'order/base' );
		$orderManager = \Aimeos\MShop::create( $context, 'order' );

		$search = $manager->filter( true );
		$expr = [
			$search->compare( '<=', 'subscription.datenext', $date ),
			$search->or( [
				$search->compare( '==', 'subscription.dateend', null ),
				$search->compare( '>', 'subscription.dateend', $date ),
			] ),
			$search->getConditions(),
		];
		$search->setConditions( $search->and( $expr ) );
		$search->setSortations( [$search->sort( '+', 'subscription.id' )] );

		$start = 0;

		do
		{
			$search->slice( $start, 100 );
			$items = $manager->search( $search );

			foreach( $items as $item )
			{
				try
				{
					$context = $this->createContext( $item->getOrderBaseId() );
					$newOrder = $this->createOrder( $context, $item );

					foreach( $processors as $processor ) {
						$processor->renewBefore( $item, $newOrder );
					}

					$basket = $baseManager->store( $newOrder->getBaseItem()->check() );
					$newOrder = $orderManager->save( $newOrder->setBaseId( $basket->getId() ) );

					try
					{
						$this->createPayment( $context, $basket, $newOrder );

						$interval = new \DateInterval( $item->getInterval() );
						$date = date_create( $item->getDateNext() )->add( $interval )->format( 'Y-m-d' );

						$item = $item->setDateNext( $date )->setPeriod( $item->getPeriod() + 1 )->setReason( null );
					}
					catch( \Exception $e )
					{
						if( $e->getCode() < 1 ) // not a soft error
						{
							$item->setReason( \Aimeos\MShop\Subscription\Item\Iface::REASON_PAYMENT );

							if( $end ) {
								$item->setDateEnd( date_create()->format( 'Y-m-d' ) );
							}
						}

						throw $e;
					}
					finally // will be always executed, even if exception is rethrown in catch()
					{
						foreach( $processors as $processor ) {
							$processor->renewAfter( $item, $newOrder );
						}
					}
				}
				catch( \Exception $e )
				{
					$str = 'Unable to renew subscription with ID "%1$s": %2$s';
					$msg = sprintf( $str, $item->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->getLogger()->log( $msg, Log::ERR, 'subscription/process/renew' );
				}

				$manager->save( $item );
			}

			$count = count( $items );
			$start += $count;
		}
		while( $count === $search->getLimit() );
	}


	/**
	 * Adds the given addresses to the basket
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Basket object to add the addresses to
	 * @param \Aimeos\Map $addresses List of type as key and address object implementing \Aimeos\MShop\Order\Item\Base\Address\Iface as value
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Order with addresses added
	 */
	protected function addBasketAddresses( \Aimeos\MShop\Context\Item\Iface $context,
		\Aimeos\MShop\Order\Item\Base\Iface $newBasket, \Aimeos\Map $addresses ) : \Aimeos\MShop\Order\Item\Base\Iface
	{
		foreach( $addresses as $type => $orderAddresses )
		{
			$idx = 0;

			foreach( $orderAddresses as $orderAddress ) {
				$newBasket->addAddress( $orderAddress->setId( null ), $type, $idx );
			}
		}

		if( !$newBasket->getCustomerId() ) {
			return $newBasket;
		}

		try
		{
			$customer = \Aimeos\MShop::create( $context, 'customer' )->get( $newBasket->getCustomerId() );
			$address = \Aimeos\MShop::create( $context, 'order/base/address' )->create();

			$type = \Aimeos\MShop\Order\Item\Base\Address\Base::TYPE_PAYMENT;
			$newBasket->addAddress( $address->copyFrom( $customer->getPaymentAddress() ), $type, 0 );
		}
		catch( \Exception $e )
		{
			$msg = sprintf( 'Unable to add current address for customer with ID "%1$s"', $newBasket->getCustomerId() );
			$context->getLogger()->log( $msg, Log::INFO, 'subscription/process/renew' );
		}

		return $newBasket;
	}


	/**
	 * Adds the given coupon codes to basket if enabled
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Order including product and addresses
	 * @param \Aimeos\Map $codes List of coupon codes that should be added to the given basket
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Basket, maybe with coupons added
	 */
	protected function addBasketCoupons( \Aimeos\MShop\Context\Item\Iface $context,
		\Aimeos\MShop\Order\Item\Base\Iface $basket, \Aimeos\Map $codes ) : \Aimeos\MShop\Order\Item\Base\Iface
	{
		/** controller/jobs/subscription/process/renew/use-coupons
		 * Applies the coupons of the previous order also to the new one
		 *
		 * Reuse coupon codes added to the basket by the customer the first time
		 * again in new subscription orders. If they have any effect depends on
		 * the codes still being active (status, time frame and count) and the
		 * decorators added to the coupon providers in the admin interface.
		 *
		 * @param boolean True to reuse coupon codes, false to remove coupons
		 * @category Developer
		 * @category User
		 * @since 2018.10
		 */
		if( $context->getConfig()->get( 'controller/jobs/subscription/process/renew/use-coupons', false ) )
		{
			foreach( $codes as $code )
			{
				try {
					$basket->addCoupon( $code );
				} catch( \Aimeos\MShop\Plugin\Provider\Exception | \Aimeos\MShop\Coupon\Exception $e ) {
					$basket->deleteCoupon( $code );
				}
			}
		}

		return $basket;
	}


	/**
	 * Adds the given products to the basket
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Basket object to add the products to
	 * @param \Aimeos\Map $orderProducts List of product items Implementing \Aimeos\MShop\Order\Item\Base\Product\Iface
	 * @param string $orderProductId Unique ID of the ordered subscription product
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Order with products added
	 */
	protected function addBasketProducts( \Aimeos\MShop\Context\Item\Iface $context,
		\Aimeos\MShop\Order\Item\Base\Iface $newBasket, \Aimeos\Map $orderProducts, $orderProductId ) : \Aimeos\MShop\Order\Item\Base\Iface
	{
		foreach( $orderProducts as $orderProduct )
		{
			if( $orderProduct->getId() == $orderProductId )
			{
				foreach( $orderProduct->getAttributeItems() as $attrItem ) {
					$attrItem->setId( null );
				}
				$newBasket->addProduct( $orderProduct->setId( null ) );
			}
		}

		return $newBasket;
	}


	/**
	 * Adds a matching delivery and payment service to the basket
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Basket object to add the services to
	 * @param \Aimeos\Map $services Associative list of type as key and list of service objects implementing \Aimeos\MShop\Order\Item\Base\Service\Iface as values
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Order with delivery and payment service added
	 */
	protected function addBasketServices( \Aimeos\MShop\Context\Item\Iface $context,
		\Aimeos\MShop\Order\Item\Base\Iface $newBasket, \Aimeos\Map $services ) : \Aimeos\MShop\Order\Item\Base\Iface
	{
		$type = \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_PAYMENT;

		if( isset( $services[$type] ) )
		{
			$idx = 0;

			foreach( $services[$type] as $orderService )
			{
				foreach( $orderService->getAttributeItems() as $attrItem ) {
					$attrItem->setId( null );
				}
				$newBasket->addService( $orderService->setId( null ), $type, $idx++ );
			}
		}

		$idx = 0;
		$type = \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_DELIVERY;

		$serviceManager = \Aimeos\MShop::create( $context, 'service' );
		$orderServiceManager = \Aimeos\MShop::create( $context, 'order/base/service' );

		$search = $serviceManager->filter( true );
		$search->setSortations( [$search->sort( '+', 'service.position' )] );
		$search->setConditions( $search->compare( '==', 'service.type', $type ) );

		foreach( $serviceManager->search( $search, ['media', 'price', 'text'] ) as $item )
		{
			$provider = $serviceManager->getProvider( $item, $item->getType() );

			if( $provider->isAvailable( $newBasket ) === true )
			{
				$orderServiceItem = $orderServiceManager->create()->copyFrom( $item );
				return $newBasket->addService( $orderServiceItem, $type, $idx++ );
			}
		}

		return $newBasket;
	}


	/**
	 * Creates a new context based on the order and the customer the subscription belongs to
	 *
	 * @param string $baseId Unique order base ID
	 * @return \Aimeos\MShop\Context\Item\Iface New context object
	 * @todo 2021.01 Pass site and locale as parameters instead of $baseId
	 */
	protected function createContext( string $baseId ) : \Aimeos\MShop\Context\Item\Iface
	{
		$context = clone $this->getContext();

		$manager = \Aimeos\MShop::create( $context, 'order/base' );
		$baseItem = $manager->get( $baseId );
		$sitecode = $baseItem->getSiteCode();

		$locale = $baseItem->getLocale();
		$level = \Aimeos\MShop\Locale\Manager\Base::SITE_ALL;

		$manager = \Aimeos\MShop::create( $context, 'locale' );
		$locale = $manager->bootstrap( $sitecode, $locale->getLanguageId(), $locale->getCurrencyId(), false, $level );

		$context->setLocale( $locale );

		try
		{
			$manager = \Aimeos\MShop::create( $context, 'customer' );
			$customerItem = $manager->get( $baseItem->getCustomerId(), ['customer/group'] );

			$context->setUserId( $baseItem->getCustomerId() );
			$context->setGroupIds( $customerItem->getGroups() );
		}
		catch( \Exception $e ) {} // Subscription without account

		return $context;
	}


	/**
	 * Creates and stores a new invoice for the given order basket
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Complete order with product, addresses and services saved to the storage
	 * @return \Aimeos\MShop\Order\Item\Iface New invoice item including the order base item
	 */
	protected function createOrder( \Aimeos\MShop\Context\Item\Iface $context,
		\Aimeos\MShop\Subscription\Item\Iface $subscription ) : \Aimeos\MShop\Order\Item\Iface
	{
		$manager = \Aimeos\MShop::create( $context, 'order' );
		$basket = $this->createOrderBase( $context, $subscription );

		return $manager->create()->setBaseItem( $basket )->setType( 'subscription' );
	}


	/**
	 * Creates and stores a new order for the subscribed product
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface Context object
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item with order base ID and order product ID
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Complete order with product, addresses and services saved to the storage
	 */
	protected function createOrderBase( \Aimeos\MShop\Context\Item\Iface $context, \Aimeos\MShop\Subscription\Item\Iface $subscription ) : \Aimeos\MShop\Order\Item\Base\Iface
	{
		$manager = \Aimeos\MShop::create( $context, 'order/base' );

		$basket = $manager->load( $subscription->getOrderBaseId() );
		$newBasket = $manager->create()->setCustomerId( $basket->getCustomerId() );

		$newBasket = $this->addBasketAddresses( $context, $newBasket, $basket->getAddresses() );
		$newBasket = $this->addBasketProducts( $context, $newBasket, $basket->getProducts(), $subscription->getOrderProductId() );
		$newBasket = $this->addBasketServices( $context, $newBasket, $basket->getServices() );
		$newBasket = $this->addBasketCoupons( $context, $newBasket, $basket->getCoupons()->keys() );

		return $newBasket->check();
	}


	/**
	 * Creates and stores a new invoice for the given order basket
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Complete order with product, addresses and services saved to the storage
	 * @return \Aimeos\MShop\Order\Item\Iface New invoice item associated to the order saved to the storage
	 * @deprecated Use createOrder() instead
	 */
	protected function createOrderInvoice( \Aimeos\MShop\Context\Item\Iface $context, \Aimeos\MShop\Order\Item\Base\Iface $basket ) : \Aimeos\MShop\Order\Item\Iface
	{
		$manager = \Aimeos\MShop::create( $context, 'order' );
		$item = $manager->create()->setBaseItem( $basket )->setBaseId( $basket->getId() )->setType( 'subscription' );

		return $manager->save( $item );
	}


	/**
	 * Creates a new payment for the given order and invoice
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Complete order with product, addresses and services
	 * @param \Aimeos\MShop\Order\Item\Iface New invoice item associated to the order
	 * @deprecated 2021.01 $basket will be removed, use $invoice->getBaseItem() instead
	 */
	protected function createPayment( \Aimeos\MShop\Context\Item\Iface $context, \Aimeos\MShop\Order\Item\Base\Iface $basket,
		\Aimeos\MShop\Order\Item\Iface $invoice )
	{
		$manager = \Aimeos\MShop::create( $context, 'service' );

		foreach( $basket->getService( \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_PAYMENT ) as $service ) {
			$manager->getProvider( $manager->get( $service->getServiceId() ), 'payment' )->repay( $invoice );
		}
	}
}
