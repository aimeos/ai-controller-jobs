<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2022
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\Renew;


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
	/** controller/jobs/subscription/process/renew/name
	 * Class name of the used subscription suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Subscription\Process\Renew\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Subscription\Process\Renew\Myrenew
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/subscription/process/renew/name = Myrenew
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyRenew"!
	 *
	 * @param string Last part of the class name
	 * @since 2018.04
	 */

	/** controller/jobs/subscription/process/renew/decorators/excludes
	 * Excludes decorators added by the "common" option from the subscription process CSV job controller
	 *
	 * Decorators extrenew the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to remove a decorator added via
	 * "controller/jobs/common/decorators/default" before they are wrapped
	 * around the job controller.
	 *
	 *  controller/jobs/subscription/process/renew/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2018.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/subscription/process/renew/decorators/global
	 * @see controller/jobs/subscription/process/renew/decorators/local
	 */

	/** controller/jobs/subscription/process/renew/decorators/global
	 * Adds a list of globally available decorators only to the subscription process CSV job controller
	 *
	 * Decorators extrenew the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/subscription/process/renew/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2018.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/subscription/process/renew/decorators/excludes
	 * @see controller/jobs/subscription/process/renew/decorators/local
	 */

	/** controller/jobs/subscription/process/renew/decorators/local
	 * Adds a list of local decorators only to the subscription process CSV job controller
	 *
	 * Decorators extrenew the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Subscription\Process\Renew\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/subscription/process/renew/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Subscription\Process\Renew\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2018.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/subscription/process/renew/decorators/excludes
	 * @see controller/jobs/subscription/process/renew/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Subscription process renew' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Renews subscriptions at next date' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$date = date( 'Y-m-d' );
		$context = $this->context();

		$processors = $this->getProcessors( $this->names() );
		$manager = \Aimeos\MShop::create( $context, 'subscription' );

		$search = $manager->filter( true )->add( 'subscription.datenext', '<=', $date )->slice( 0, $this->max() );
		$search->add( $search->or( [
			$search->compare( '==', 'subscription.dateend', null ),
			$search->compare( '>', 'subscription.dateend', $date ),
		] ) );
		$cursor = $manager->cursor( $search );

		while( $items = $manager->iterate( $cursor ) )
		{
			foreach( $items as $item )
			{
				try
				{
					$manager->save( $this->process( $item, $processors ) );
				}
				catch( \Exception $e )
				{
					$str = 'Unable to renew subscription with ID "%1$s": %2$s';
					$msg = sprintf( $str, $item->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->logger()->error( $msg, 'subscription/process/renew' );
				}
			}
		}
	}


	/**
	 * Adds the given addresses to the basket
	 *
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Basket object to add the addresses to
	 * @param \Aimeos\Map $addresses List of type as key and address object implementing \Aimeos\MShop\Order\Item\Base\Address\Iface as value
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Order with addresses added
	 */
	protected function addBasketAddresses( \Aimeos\MShop\ContextIface $context,
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
			$context->logger()->info( $msg, 'subscription/process/renew' );
		}

		return $newBasket;
	}


	/**
	 * Adds the given coupon codes to basket if enabled
	 *
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Order including product and addresses
	 * @param \Aimeos\Map $codes List of coupon codes that should be added to the given basket
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Basket, maybe with coupons added
	 */
	protected function addBasketCoupons( \Aimeos\MShop\ContextIface $context,
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
		if( $context->config()->get( 'controller/jobs/subscription/process/renew/use-coupons', false ) )
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
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Basket object to add the products to
	 * @param \Aimeos\Map $orderProducts List of product items Implementing \Aimeos\MShop\Order\Item\Base\Product\Iface
	 * @param string $orderProductId Unique ID of the ordered subscription product
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Order with products added
	 */
	protected function addBasketProducts( \Aimeos\MShop\ContextIface $context,
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
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Basket object to add the services to
	 * @param \Aimeos\Map $services Associative list of type as key and list of service objects implementing \Aimeos\MShop\Order\Item\Base\Service\Iface as values
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Order with delivery and payment service added
	 */
	protected function addBasketServices( \Aimeos\MShop\ContextIface $context,
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
	 * @return \Aimeos\MShop\ContextIface New context object
	 * @todo 2021.01 Pass site and locale as parameters instead of $baseId
	 */
	protected function createContext( string $baseId ) : \Aimeos\MShop\ContextIface
	{
		$context = clone $this->context();

		$manager = \Aimeos\MShop::create( $context, 'order/base' );
		$baseItem = $manager->get( $baseId );
		$sitecode = $baseItem->getSiteCode();

		$locale = $baseItem->locale();
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
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Complete order with product, addresses and services saved to the storage
	 * @return \Aimeos\MShop\Order\Item\Iface New invoice item including the order base item
	 */
	protected function createOrder( \Aimeos\MShop\ContextIface $context,
		\Aimeos\MShop\Subscription\Item\Iface $subscription ) : \Aimeos\MShop\Order\Item\Iface
	{
		$manager = \Aimeos\MShop::create( $context, 'order' );
		$basket = $this->createOrderBase( $context, $subscription );

		return $manager->create()->setBaseItem( $basket )->setChannel( 'subscription' );
	}


	/**
	 * Creates and stores a new order for the subscribed product
	 *
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item with order base ID and order product ID
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Complete order with product, addresses and services saved to the storage
	 */
	protected function createOrderBase( \Aimeos\MShop\ContextIface $context, \Aimeos\MShop\Subscription\Item\Iface $subscription ) : \Aimeos\MShop\Order\Item\Base\Iface
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
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Complete order with product, addresses and services saved to the storage
	 * @return \Aimeos\MShop\Order\Item\Iface New invoice item associated to the order saved to the storage
	 * @deprecated Use createOrder() instead
	 */
	protected function createOrderInvoice( \Aimeos\MShop\ContextIface $context, \Aimeos\MShop\Order\Item\Base\Iface $basket ) : \Aimeos\MShop\Order\Item\Iface
	{
		$manager = \Aimeos\MShop::create( $context, 'order' );
		$item = $manager->create()->setBaseItem( $basket )->setBaseId( $basket->getId() )->setChannel( 'subscription' );

		return $manager->save( $item );
	}


	/**
	 * Creates a new payment for the given order and invoice
	 *
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Complete order with product, addresses and services
	 * @param \Aimeos\MShop\Order\Item\Iface New invoice item associated to the order
	 * @deprecated 2021.01 $basket will be removed, use $invoice->getBaseItem() instead
	 */
	protected function createPayment( \Aimeos\MShop\ContextIface $context, \Aimeos\MShop\Order\Item\Base\Iface $basket,
		\Aimeos\MShop\Order\Item\Iface $invoice )
	{
		$manager = \Aimeos\MShop::create( $context, 'service' );

		foreach( $basket->getService( \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_PAYMENT ) as $service ) {
			$manager->getProvider( $manager->get( $service->getServiceId() ), 'payment' )->repay( $invoice );
		}
	}


	/**
	 * Returns if subscriptions should end if payment couldn't be captured
	 *
	 * @return bool TRUE if subscription should end, FALSE if not
	 */
	protected function ends() : bool
	{
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
		 * @see controller/common/subscription/process/processors
		 * @see controller/common/subscription/process/payment-days
		 * @see controller/common/subscription/process/payment-status
		 */
		return (bool) $this->context()->config()->get( 'controller/common/subscription/process/payment-ends', true );
	}


	/**
	 * Returns the maximum number of orders processed at once
	 *
	 * @return int Maximum number of items
	 */
	protected function max() : int
	{
		/** controller/jobs/subscription/process/batch-max
		 * Maximum number of subscriptions processed at once by the subscription process job
		 *
		 * This setting configures the maximum number of subscriptions including
		 * orders that will be processed at once. Bigger batches an improve the
		 * performance but requires more memory.
		 *
		 * @param integer Number of subscriptions
		 * @since 2023.04
		 * @see controller/jobs/subscription/process/domains
		 * @see controller/jobs/subscription/process/names
		 * @see controller/jobs/subscription/process/payment-days
		 * @see controller/jobs/subscription/process/payment-status
		 */
		return $this->context()->config()->get( 'controller/jobs/subscription/process/batch-max', 100 );
	}


	/**
	 * Returns the names of the subscription processors
	 *
	 * @return array List of processor names
	 */
	protected function names() : array
	{
		/** controller/jobs/subscription/process/processors
		 * List of processor names that should be executed for subscriptions
		 *
		 * For each subscription a number of processors for different tasks can be executed.
		 * They can for example add a group to the customers' account during the customer
		 * has an active subscribtion.
		 *
		 * @param array List of processor names
		 * @since 2018.04
		 * @see controller/jobs/subscription/process/domains
		 * @see controller/jobs/subscription/process/max
		 * @see controller/jobs/subscription/process/payment-days
		 * @see controller/jobs/subscription/process/payment-status
		 */
		return (array) $this->context()->config()->get( 'controller/jobs/subscription/process/processors', [] );
	}


	/**
	 * Runs the subscription processors for the passed item
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $item Subscription item
	 * @param iterable $processors List of processor objects to run on the item
	 * @return \Aimeos\MShop\Subscription\Item\Iface Updated subscription item
	 */
	protected function process( \Aimeos\MShop\Subscription\Item\Iface $item, iterable $processors
		) : \Aimeos\MShop\Subscription\Item\Iface
	{
		$context = $this->context();
		$orderManager = \Aimeos\MShop::create( $context, 'order' );
		$baseManager = \Aimeos\MShop::create( $context, 'order/base' );

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
			$date = date_create( (string) $item->getDateNext() )->add( $interval )->format( 'Y-m-d' );

			$item->setDateNext( $date )->setPeriod( $item->getPeriod() + 1 )->setReason( null );
		}
		catch( \Exception $e )
		{
			if( $e->getCode() < 1 ) // not a soft error
			{
				$item->setReason( \Aimeos\MShop\Subscription\Item\Iface::REASON_PAYMENT );

				if( $this->ends() ) {
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

		return $item;
	}
}
