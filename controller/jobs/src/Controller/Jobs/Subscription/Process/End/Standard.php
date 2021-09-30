<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\End;

use \Aimeos\MW\Logger\Base as Log;


/**
 * Job controller for subscription processs end.
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
		return $this->getContext()->translate( 'controller/jobs', 'Subscription process end' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Terminates expired subscriptions' );
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
		 */
		$names = (array) $config->get( 'controller/common/subscription/process/processors', [] );

		$domains = ['order/base', 'order/base/address', 'order/base/coupon', 'order/base/product', 'order/base/service'];

		$processors = $this->getProcessors( $names );
		$manager = \Aimeos\MShop::create( $context, 'subscription' );
		$orderManager = \Aimeos\MShop::create( $context, 'order' );

		$search = $manager->filter( true );
		$expr = [
			$search->compare( '<', 'subscription.dateend', date( 'Y-m-d' ) ),
			$search->getConditions(),
		];
		$search->setConditions( $search->and( $expr ) );
		$search->setSortations( [$search->sort( '+', 'subscription.id' )] );

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
					if( $orderItem = $orderItems->get( $item->getOrderBaseId() ) )
					{
						foreach( $processors as $processor ) {
							$processor->end( $item, $orderItem );
						}
					}

					if( ( $reason = $item->getReason() ) === null ) {
						$reason = \Aimeos\MShop\Subscription\Item\Iface::REASON_END;
					}

					$manager->save( $item->setReason( $reason )->setStatus( 0 ) );
				}
				catch( \Exception $e )
				{
					$str = 'Unable to end subscription with ID "%1$s": %2$s';
					$msg = sprintf( $str, $item->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->getLogger()->log( $msg, Log::ERR, 'subscription/process/end' );
				}
			}

			$count = count( $items );
			$start += $count;
		}
		while( $count === $search->getLimit() );
	}
}
