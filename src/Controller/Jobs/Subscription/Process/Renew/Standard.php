<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2024
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
		$date = date( 'Y-m-d H:i:s' );
		$context = $this->context();
		$domains = $this->domains();

		$processors = $this->getProcessors( $this->names() );
		$manager = \Aimeos\MShop::create( $context, 'subscription' );

		$search = $manager->filter( true )->add( 'subscription.datenext', '<=', $date )->slice( 0, $this->max() );
		$search->add( $search->or( [
			$search->compare( '==', 'subscription.dateend', null ),
			$search->compare( '>', 'subscription.dateend', $date ),
		] ) );
		$cursor = $manager->cursor( $search );

		while( $items = $manager->iterate( $cursor, $domains ) )
		{
			foreach( $items as $item )
			{
				$manager->begin();

				try
				{
					$manager->save( $this->process( $item, $processors ) );
					$manager->commit();
				}
				catch( \Exception $e )
				{
					$manager->rollback();

					$str = 'Unable to renew subscription with ID "%1$s": %2$s';
					$msg = sprintf( $str, $item->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->logger()->error( $msg, 'subscription/process/renew' );
				}
			}
		}
	}


	/**
	 * Adds the given addresses to the order
	 *
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Order\Item\Iface $newOrder Order object to add the addresses to
	 * @param \Aimeos\Map $addresses List of type as key and address object implementing \Aimeos\MShop\Order\Item\Address\Iface as value
	 * @return \Aimeos\MShop\Order\Item\Iface Order with addresses added
	 */
	protected function addBasketAddresses( \Aimeos\MShop\ContextIface $context,
		\Aimeos\MShop\Order\Item\Iface $newOrder, \Aimeos\Map $addresses ) : \Aimeos\MShop\Order\Item\Iface
	{
		foreach( $addresses as $type => $orderAddresses )
		{
			$idx = 0;

			foreach( $orderAddresses as $orderAddress ) {
				$newOrder->addAddress( ( clone $orderAddress )->setId( null ), $type, $idx );
			}
		}

		if( !$newOrder->getCustomerId() ) {
			return $newOrder;
		}

		try
		{
			$customer = \Aimeos\MShop::create( $context, 'customer' )->get( $newOrder->getCustomerId() );
			$address = \Aimeos\MShop::create( $context, 'order/address' )->create();

			$type = \Aimeos\MShop\Order\Item\Address\Base::TYPE_PAYMENT;
			$newOrder->addAddress( $address->copyFrom( $customer->getPaymentAddress() ), $type, 0 );
		}
		catch( \Exception $e )
		{
			$msg = sprintf( 'Unable to add current address for customer with ID "%1$s"', $newOrder->getCustomerId() );
			$context->logger()->info( $msg, 'subscription/process/renew' );
		}

		return $newOrder;
	}


	/**
	 * Adds the given coupon codes to the order if enabled
	 *
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Order\Item\Iface $newOrder Order including product and addresses
	 * @param \Aimeos\Map $codes List of coupon codes that should be added to the given order
	 * @return \Aimeos\MShop\Order\Item\Iface Basket, maybe with coupons added
	 */
	protected function addBasketCoupons( \Aimeos\MShop\ContextIface $context,
		\Aimeos\MShop\Order\Item\Iface $newOrder, \Aimeos\Map $codes ) : \Aimeos\MShop\Order\Item\Iface
	{
		/** controller/jobs/subscription/process/renew/use-coupons
		 * Applies the coupons of the previous order also to the new one
		 *
		 * Reuse coupon codes added to the order by the customer the first time
		 * again in new subscription orders. If they have any effect depends on
		 * the codes still being active (status, time frame and count) and the
		 * decorators added to the coupon providers in the admin interface.
		 *
		 * @param boolean True to reuse coupon codes, false to remove coupons
		 * @since 2018.10
		 */
		if( $context->config()->get( 'controller/jobs/subscription/process/renew/use-coupons', false ) )
		{
			foreach( $codes as $code )
			{
				try {
					$newOrder->addCoupon( $code );
				} catch( \Aimeos\MShop\Plugin\Provider\Exception | \Aimeos\MShop\Coupon\Exception $e ) {
					$newOrder->deleteCoupon( $code );
				}
			}
		}

		return $newOrder;
	}


	/**
	 * Adds the given products to the order
	 *
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Order\Item\Iface $order Order to add the products to
	 * @param \Aimeos\Map $orderProducts List of product items Implementing \Aimeos\MShop\Order\Item\Product\Iface
	 * @param string $orderProductId Unique ID of the ordered subscription product
	 * @return \Aimeos\MShop\Order\Item\Iface Order with products added
	 */
	protected function addBasketProducts( \Aimeos\MShop\ContextIface $context,
		\Aimeos\MShop\Order\Item\Iface $newOrder, \Aimeos\Map $orderProducts, $orderProductId ) : \Aimeos\MShop\Order\Item\Iface
	{
		foreach( $orderProducts as $orderProduct )
		{
			if( $orderProduct->getId() == $orderProductId )
			{
				$orderProduct = clone $orderProduct;
				$orderProduct->getAttributeItems()->setId( null );

				$newOrder->addProduct( $orderProduct->setId( null ) );
			}
		}

		return $newOrder;
	}


	/**
	 * Adds a matching delivery and payment service to the order
	 *
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Order\Item\Iface $order Order to add the services to
	 * @param \Aimeos\Map $services Associative list of type as key and list of service objects implementing \Aimeos\MShop\Order\Item\Service\Iface as values
	 * @return \Aimeos\MShop\Order\Item\Iface Order with delivery and payment service added
	 */
	protected function addBasketServices( \Aimeos\MShop\ContextIface $context,
		\Aimeos\MShop\Order\Item\Iface $newOrder, \Aimeos\Map $services ) : \Aimeos\MShop\Order\Item\Iface
	{
		$type = \Aimeos\MShop\Order\Item\Service\Base::TYPE_PAYMENT;

		if( isset( $services[$type] ) )
		{
			$idx = 0;

			foreach( $services[$type] as $orderService )
			{
				$orderService = clone $orderService;
				$orderService->getAttributeItems()->setId( null );

				$newOrder->addService( $orderService->setId( null ), $type, $idx++ );
			}
		}

		$idx = 0;
		$type = \Aimeos\MShop\Order\Item\Service\Base::TYPE_DELIVERY;

		$serviceManager = \Aimeos\MShop::create( $context, 'service' );
		$orderServiceManager = \Aimeos\MShop::create( $context, 'order/service' );

		$search = $serviceManager->filter( true );
		$search->setSortations( [$search->sort( '+', 'service.position' )] );
		$search->setConditions( $search->compare( '==', 'service.type', $type ) );

		foreach( $serviceManager->search( $search, ['media', 'price', 'text'] ) as $item )
		{
			$provider = $serviceManager->getProvider( $item, $item->getType() );

			if( $provider->isAvailable( $newOrder ) === true )
			{
				$orderServiceItem = $orderServiceManager->create()->copyFrom( $item );
				return $newOrder->addService( $orderServiceItem, $type, $idx++ );
			}
		}

		return $newOrder;
	}


	/**
	 * Creates a new context based on the order and the customer the subscription belongs to
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $order Subscription item with associated order
	 * @return \Aimeos\MShop\ContextIface New context object
	 */
	protected function createContext( \Aimeos\MShop\Subscription\Item\Iface $subscription ) : \Aimeos\MShop\ContextIface
	{
		$context = clone $this->context();
		$level = \Aimeos\MShop\Locale\Manager\Base::SITE_ALL;

		$order = $subscription->getOrderItem();
		$sitecode = $order->getSiteCode();
		$locale = $order->locale();

		$manager = \Aimeos\MShop::create( $context, 'locale' );
		$locale = $manager->bootstrap( $sitecode, $locale->getLanguageId(), $locale->getCurrencyId(), false, $level );

		$context->setLocale( $locale );

		try
		{
			$manager = \Aimeos\MShop::create( $context, 'customer' );
			$customerItem = $manager->get( $order->getCustomerId(), ['group'] );
			$context->setUser( $customerItem );

			$manager = \Aimeos\MShop::create( $context, 'group' );
			$filter = $manager->filter( true )->add( ['group.id' => $customerItem->getGroups()] );
			$groupItems = $manager->search( $filter->slice( 0, count( $customerItem->getGroups() ) ) )->all();
			$context->setGroups( $groupItems );
		}
		catch( \Exception $e ) {} // Subscription without account

		return $context;
	}


	/**
	 * Creates and stores a new order from the given subscription
	 *
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item with associated order
	 * @return \Aimeos\MShop\Order\Item\Iface New order item including addresses, coupons, products and services
	 */
	protected function createOrder( \Aimeos\MShop\ContextIface $context,
		\Aimeos\MShop\Subscription\Item\Iface $subscription ) : \Aimeos\MShop\Order\Item\Iface
	{
		$order = $subscription->getOrderItem();

		$manager = \Aimeos\MShop::create( $context, 'order' );
		$newOrder = $manager->create()->setCustomerId( $order->getCustomerId() )->setChannel( 'subscription' );

		$newOrder = $this->addBasketAddresses( $context, $newOrder, $order->getAddresses() );
		$newOrder = $this->addBasketProducts( $context, $newOrder, $order->getProducts(), $subscription->getOrderProductId() );
		$newOrder = $this->addBasketServices( $context, $newOrder, $order->getServices() );
		$newOrder = $this->addBasketCoupons( $context, $newOrder, $order->getCoupons()->keys() );

		return $newOrder->check();
	}


	/**
	 * Creates a new payment for the given order and invoice
	 *
	 * @param \Aimeos\MShop\ContextIface Context object
	 * @param \Aimeos\MShop\Order\Item\Iface $order Complete order with product, addresses and services
	 * @return \Aimeos\MShop\Order\Item\Iface Updated order item
	 */
	protected function createPayment( \Aimeos\MShop\ContextIface $context, \Aimeos\MShop\Order\Item\Iface $order ) : \Aimeos\MShop\Order\Item\Iface
	{
		$manager = \Aimeos\MShop::create( $context, 'service' );

		foreach( $order->getService( \Aimeos\MShop\Order\Item\Service\Base::TYPE_PAYMENT ) as $service ) {
			$manager->getProvider( $manager->get( $service->getServiceId() ), 'payment' )->repay( $order );
		}

		return $order;
	}


	/**
	 * Returns the domains that should be fetched together with the order data
	 *
	 * @return array List of domain names
	 */
	protected function domains() : array
	{
		/** controller/jobs/subscription/process/domains
		 * Associated items that should be available too in the subscription
		 *
		 * Orders consist of address, coupons, products and services. They can be
		 * fetched together with the subscription items and passed to the processor.
		 * Available domains for those items are:
		 *
		 * - order
		 * - order/address
		 * - order/coupon
		 * - order/product
		 * - order/service
		 *
		 * @param array Referenced domain names
		 * @since 2022.04
		 * @see controller/jobs/subscription/process/processors
		 * @see controller/jobs/subscription/process/payment-days
		 * @see controller/jobs/subscription/process/payment-status
		 */
		$domains = ['order', 'order/address', 'order/coupon', 'order/product', 'order/service'];
		return $this->context()->config()->get( 'controller/jobs/subscription/process/domains', $domains );
	}


	/**
	 * Returns if subscriptions should end if payment couldn't be captured
	 *
	 * @return bool TRUE if subscription should end, FALSE if not
	 */
	protected function ends() : bool
	{
		/** controller/jobs/subscription/process/payment-ends
		 * Subscriptions ends if payment couldn't be captured
		 *
		 * By default, a subscription ends automatically if the next payment couldn't
		 * be captured. When setting this configuration to FALSE, the subscription job
		 * controller will try to capture the payment at the next run again until the
		 * subscription is deactivated manually.
		 *
		 * @param bool TRUE if payment failures ends the subscriptions, FALSE if not
		 * @since 2019.10
		 * @see controller/jobs/subscription/process/processors
		 * @see controller/jobs/subscription/process/payment-days
		 * @see controller/jobs/subscription/process/payment-status
		 */
		return (bool) $this->context()->config()->get( 'controller/jobs/subscription/process/payment-ends', true );
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

		$context = $this->createContext( $item );
		$newOrder = $this->createOrder( $context, $item );

		foreach( $processors as $processor ) {
			$processor->renewBefore( $item, $newOrder );
		}

		$newOrder = $orderManager->save( $newOrder->check() );

		try
		{
			$newOrder = $orderManager->save( $this->createPayment( $context, $newOrder ) );

			$interval = new \DateInterval( $item->getInterval() );
			$date = date_create( (string) $item->getDateNext() )->add( $interval )->format( 'Y-m-d H:i:s' );

			$item->setDateNext( $date )->setPeriod( $item->getPeriod() + 1 )->setReason( null );
		}
		catch( \Exception $e )
		{
			if( $e->getCode() < 1 ) // not a soft error
			{
				$item->setReason( \Aimeos\MShop\Subscription\Item\Iface::REASON_PAYMENT );

				if( $this->ends() ) {
					$item->setDateEnd( date_create()->format( 'Y-m-d H:i:s' ) );
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
