<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
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
	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Subscription process renew' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Renews subscriptions at next date' );
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
		$logger = $context->getLogger();

		$names = (array) $config->get( 'controller/common/subscription/process/processors', [] );

		$date = date( 'Y-m-d' );
		$processors = $this->getProcessors( $names );
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'subscription' );

		$search = $manager->createSearch( true );
		$expr = [
			$search->compare( '<=', 'subscription.datenext', $date ),
			$search->combine( '||', [
				$search->compare( '==', 'subscription.dateend', null ),
				$search->compare( '>', 'subscription.dateend', $date ),
			] ),
			$search->getConditions(),
		];
		$search->setConditions( $search->combine( '&&', $expr ) );
		$search->setSortations( [$search->sort( '+', 'subscription.id' )] );

		$start = 0;

		do
		{
			$search->setSlice( $start, 100 );
			$items = $manager->searchItems( $search );

			foreach( $items as $item )
			{
				try
				{
					$context = $this->createContext( $item->getOrderBaseId() );
					$newOrder = $this->createOrderBase( $context, $item );
					$newInvoice = $this->createOrderInvoice( $context, $newOrder );

					$this->createPayment( $context, $newOrder, $newInvoice );

					foreach( $processors as $processor ) {
						$processor->renew( $item, $newInvoice );
					}

					$interval = new \DateInterval( $item->getInterval() );
					$item->setDateNext( date_create( $item->getTimeCreated() )->add( $interval )->format( 'Y-m-d' ) );

					$manager->saveItem( $item );
				}
				catch( \Exception $e )
				{
					$msg = 'Unable to process subscription with ID "%1$S": %2$s';
					$logger->log( sprintf( $msg, $item->getId(), $e->getMessage() ) );
					$logger->log( $e->getTraceAsString() );
				}
			}

			$count = count( $items );
			$start += $count;
		}
		while( $count === $search->getSliceSize() );
	}


	/**
	 * Creates a new context based on the order and the customer the subscription belongs to
	 *
	 * @param string $baseId Unique order base ID
	 * @return \Aimeos\MShop\Context\Item\Iface New context object
	 */
	protected function createContext( $baseId )
	{
		$context = clone $this->getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'order/base' );
		$baseItem = $manager->getItem( $baseId );

		$locale = $baseItem->getLocale();
		$level = \Aimeos\MShop\Locale\Manager\Base::SITE_ALL;

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'locale' );
		$locale = $manager->bootstrap( $baseItem->getSiteCode(), $locale->getLanguageId(), $locale->getCurrencyId(), false, $level );

		$context->setLocale( $locale );

		try
		{
			$manager = \Aimeos\MShop\Factory::createManager( $context, 'customer' );
			$customerItem = $manager->getItem( $baseItem->getCustomerId(), ['customer/group'] );

			$context->setUserId( $baseItem->getCustomerId() );
			$context->setGroupIds( $customerItem->getGroups() );
		}
		catch( \Exception $e ) {} // Subscription without account

		return $context;
	}


	/**
	 * Creates and stores a new order for the subscribed product
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface Context object
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item with order base ID and order product ID
	 * @return \Aimeos\MShop\Order\Item\Base\Iface Complete order with product, addresses and services saved to the storage
	 */
	protected function createOrderBase( \Aimeos\MShop\Context\Item\Iface $context, \Aimeos\MShop\Subscription\Item\Iface $subscription )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'order/base' );

		$basket = $manager->load( $subscription->getOrderBaseId() );

		$newBasket = $manager->createItem();
		$newBasket->setCustomerId( $basket->getCustomerId() );

		foreach( $basket->getProducts() as $orderProduct )
		{
			if( $orderProduct->getId() === $subscription->getOrderProductId() )
			{
				$orderProduct->setId( null );
				$newBasket->addProduct( $orderProduct );
			}
		}

		foreach( $basket->getAddresses() as $type => $orderAddress ) {
			$newBasket->setAddress( $orderAddress, $type );
		}

		foreach( $basket->getServices() as $type => $orderServices )
		{
			foreach( $orderServices as $orderService ) {
				$newBasket->addService( $orderService, $type );
			}
		}

		return $manager->store( $newBasket );
	}


	/**
	 * Creates and stores a new invoice for the given order basket
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Complete order with product, addresses and services saved to the storage
	 * @return \Aimeos\MShop\Order\Item\Iface New invoice item associated to the order saved to the storage
	 */
	protected function createOrderInvoice( \Aimeos\MShop\Context\Item\Iface $context, \Aimeos\MShop\Order\Item\Base\Iface $basket )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'order' );

		$item = $manager->createItem();
		$item->setBaseId( $basket->getId() );
		$item->setType( 'subscription' );

		return $manager->saveItem( $item );
	}


	/**
	 * Creates a new payment for the given order and invoice
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface Context object
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Complete order with product, addresses and services
	 * @param \Aimeos\MShop\Order\Item\Iface New invoice item associated to the order
	 */
	protected function createPayment( \Aimeos\MShop\Context\Item\Iface $context, \Aimeos\MShop\Order\Item\Base\Iface $basket,
		\Aimeos\MShop\Order\Item\Iface $invoice )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'service' );

		foreach( $basket->getService( \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_PAYMENT ) as $service )
		{
			$item = $manager->getItem( $service->getServiceId() );
			$provider = $manager->getProvider( $item, 'payment' );

			$provider->repay( $invoice );
		}
	}
}
