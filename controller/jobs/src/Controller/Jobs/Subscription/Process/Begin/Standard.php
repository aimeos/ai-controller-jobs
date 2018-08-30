<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\Begin;


/**
 * Job controller for subscription processs start.
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
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Subscription process start' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Process subscriptions initially' );
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

		/** controller/common/subscription/process/processors
		 * List of processor names that should be executed for subscriptions
		 *
		 * For each subscription a number of processors for different tasks can be executed.
		 * They can for example add a group to the customers' account during the customer
		 * has an active subscribtion.
		 *
		 * @param array List of processor names
		 * @since 2018.04
		 * @category Developer
		 */
		$names = (array) $config->get( 'controller/common/subscription/process/processors', [] );

		$processors = $this->getProcessors( $names );
		$orderManager = \Aimeos\MShop\Factory::createManager( $context, 'order' );
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'subscription' );

		$search = $manager->createSearch( true );
		$expr = [
			$search->compare( '==', 'subscription.datenext', null ),
			$search->getConditions(),
		];
		$search->setConditions( $search->combine( '&&', $expr ) );
		$search->setSortations( [$search->sort( '+', 'subscription.id' )] );

		$status = \Aimeos\MShop\Order\Item\Base::PAY_AUTHORIZED;
		$start = 0;

		do
		{
			$ordBaseIds = $payStatus = [];

			$search->setSlice( $start, 100 );
			$items = $manager->searchItems( $search );

			foreach( $items as $item ) {
				$ordBaseIds[] = $item->getOrderBaseId();
			}

			$orderSearch = $orderManager->createSearch()->setSlice( 0, $search->getSliceSize() );
			$orderSearch->setConditions( $orderSearch->compare( '==', 'order.base.id', $ordBaseIds ) );

			foreach( $orderManager->searchItems( $orderSearch ) as $orderItem ) {
				$payStatus[$orderItem->getBaseId()] = $orderItem->getPaymentStatus();
			}

			foreach( $items as $item )
			{
				try
				{
					if( isset( $payStatus[$item->getOrderBaseId()] ) && $payStatus[$item->getOrderBaseId()] >= $status )
					{
						foreach( $processors as $processor ) {
							$processor->begin( $item );
						}

						$interval = new \DateInterval( $item->getInterval() );
						$item->setDateNext( date_create( $item->getTimeCreated() )->add( $interval )->format( 'Y-m-d' ) );
					}
					else
					{
						$item->setStatus( 0 );
					}

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
}
