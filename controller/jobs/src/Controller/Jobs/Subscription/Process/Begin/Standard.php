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
		 * @see controller/common/subscription/process/payment-status
		 * @see controller/common/subscription/process/payment-days
		 */
		$names = (array) $config->get( 'controller/common/subscription/process/processors', [] );

		/** controller/common/subscription/process/payment-status
		 * Minimum payment status that will activate the subscription
		 *
		 * Subscriptions will be activated if the payment status of the order is
		 * at least the configured payment constant. The default payment status
		 * is "authorized" so orders with a payment status of "authorized" (5) and
		 * "received" (6) will cause the subscription to be activated. Lower
		 * payment status values, e.g. "pending" (4) won't activate the subscription.
		 *
		 * @param integer Payment status constant
		 * @since 2018.07
		 * @category Developer
		 * @category User
		 * @see controller/common/subscription/process/processors
		 * @see controller/common/subscription/process/payment-days
		 */
		$status = \Aimeos\MShop\Order\Item\Base::PAY_AUTHORIZED;
		$status = $config->get( 'controller/common/subscription/process/payment-status', $status );

		/** controller/common/subscription/process/payment-days
		 * Number of days to wait for the payment until subscription is removed
		 *
		 * Subscriptions wait for the confiugrable number of days until the payment
		 * status changes to a valid payment (by default: "authorized" and "received").
		 * If the payment arrives within this time frame, the subscription is activated.
		 * Otherwise, the subscription is removed from the list of subscriptions that
		 * will be checked for activation.
		 *
		 * @param float Number of days
		 * @since 2018.07
		 * @category Developer
		 * @category User
		 * @see controller/common/subscription/process/processors
		 * @see controller/common/subscription/process/payment-status
		 */
		$days = (float) $config->get( 'controller/common/subscription/process/payment-days', 3 );


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

		$date = date( 'Y-m-d H:i:s', time() - 86400 * $days );
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
			$orderSearch->setSortations( [$orderSearch->sort( '+', 'order.id' )] );

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
					elseif( $item->getTimeCreated() < $date )
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
