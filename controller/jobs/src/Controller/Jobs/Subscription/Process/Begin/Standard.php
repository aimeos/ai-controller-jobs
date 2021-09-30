<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\Begin;

use \Aimeos\MW\Logger\Base as Log;


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
	public function getName() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Subscription process start' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Process subscriptions initially' );
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

		$domains = ['order/base', 'order/base/address', 'order/base/coupon', 'order/base/product', 'order/base/service'];

		$processors = $this->getProcessors( $names );
		$orderManager = \Aimeos\MShop::create( $context, 'order' );
		$manager = \Aimeos\MShop::create( $context, 'subscription' );

		$search = $manager->filter( true );
		$expr = [
			$search->compare( '==', 'subscription.datenext', null ),
			$search->getConditions(),
		];
		$search->setConditions( $search->and( $expr ) );
		$search->setSortations( [$search->sort( '+', 'subscription.id' )] );

		$date = date( 'Y-m-d H:i:s', time() - 86400 * $days );
		$start = 0;

		do
		{
			$orderItems = [];

			$search->slice( $start, 100 );
			$items = $manager->search( $search );
			$ordBaseIds = $items->getOrderBaseId()->toArray();

			$orderSearch = $orderManager->filter()->slice( 0, $search->getLimit() );
			$orderSearch->setConditions( $orderSearch->compare( '==', 'order.baseid', $ordBaseIds ) );
			$orderSearch->setSortations( [$orderSearch->sort( '+', 'order.id' )] );

			$orderItems = $orderManager->search( $orderSearch, $domains )->col( null, 'order.baseid' );

			foreach( $items as $item )
			{
				try
				{
					$orderItem = $orderItems->get( $item->getOrderBaseId() );

					if( $orderItem && $orderItem->getStatusPayment() >= $status )
					{
						foreach( $processors as $processor ) {
							$processor->begin( $item, $orderItem );
						}

						$interval = new \DateInterval( $item->getInterval() );
						$dateNext = date_create( $item->getTimeCreated() )->add( $interval )->format( 'Y-m-d' );
						$item = $item->setDateNext( $dateNext )->setPeriod( 1 );
					}
					elseif( $item->getTimeCreated() < $date )
					{
						$item->setStatus( 0 );
					}

					$manager->save( $item );
				}
				catch( \Exception $e )
				{
					$str = 'Unable to begin subscription with ID "%1$s": %2$s';
					$msg = sprintf( $str, $item->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->getLogger()->log( $msg, Log::ERR, 'subscription/process/begin' );
				}
			}

			$count = count( $items );
			$start += $count;
		}
		while( $count === $search->getLimit() );
	}
}
