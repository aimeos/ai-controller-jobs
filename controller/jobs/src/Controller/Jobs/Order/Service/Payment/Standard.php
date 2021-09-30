<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Order\Service\Payment;

use \Aimeos\MW\Logger\Base as Log;


/**
 * Captures the money for authorized orders
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Capture authorized payments' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Authorized payments of orders will be captured after dispatching or after a configurable amount of time' );
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


		/** controller/jobs/order/service/payment/limit-days
		 * Only start capturing payments of orders that were created in the past within the configured number of days
		 *
		 * Capturing payments is normally done immediately after the delivery
		 * status changed to "dispatched" or "delivered". This option prevents
		 * payments from being captured in case anything went wrong and payments
		 * of old orders would be captured now.
		 *
		 * @param integer Number of days
		 * @since 2014.07
		 * @category User
		 * @category Developer
		 */
		$days = $config->get( 'controller/jobs/order/service/payment/limit-days', 90 );
		$date = date( 'Y-m-d 00:00:00', time() - 86400 * $days );

		/** controller/jobs/order/service/payment/capture-days
		 * Automatically capture payments after the configured amount of days
		 *
		 * You can capture authorized payments after a configured amount of days
		 * even if the parcel for the order wasn't dispatched yet. This is useful
		 * for payment methods like credit cards where autorizations are revoked
		 * by the aquirers after some time (usually seven days).
		 *
		 * @param integer Number of days
		 * @since 2014.07
		 * @category User
		 * @category Developer
		 */
		$capDays = $config->get( 'controller/jobs/order/service/payment/capture-days', null );


		$serviceManager = \Aimeos\MShop::create( $context, 'service' );
		$serviceSearch = $serviceManager->filter();
		$serviceSearch->setConditions( $serviceSearch->compare( '==', 'service.type', 'payment' ) );

		$orderManager = \Aimeos\MShop::create( $context, 'order' );
		$orderSearch = $orderManager->filter();

		$status = array( \Aimeos\MShop\Order\Item\Base::STAT_DISPATCHED, \Aimeos\MShop\Order\Item\Base::STAT_DELIVERED );
		$start = 0;

		do
		{
			$serviceItems = $serviceManager->search( $serviceSearch );

			foreach( $serviceItems as $serviceItem )
			{
				try
				{
					$serviceProvider = $serviceManager->getProvider( $serviceItem, $serviceItem->getType() );

					if( !$serviceProvider->isImplemented( \Aimeos\MShop\Service\Provider\Payment\Base::FEAT_CAPTURE ) ) {
						continue;
					}


					$expr = [];
					$expr[] = $orderSearch->compare( '>', 'order.datepayment', $date );

					if( $capDays !== null )
					{
						$capdate = date( 'Y-m-d 00:00:00', time() - 86400 * $capDays );
						$expr[] = $orderSearch->compare( '<=', 'order.datepayment', $capdate );
					}
					else
					{
						$expr[] = $orderSearch->compare( '==', 'order.statusdelivery', $status );
					}

					$expr[] = $orderSearch->compare( '==', 'order.statuspayment', \Aimeos\MShop\Order\Item\Base::PAY_AUTHORIZED );
					$expr[] = $orderSearch->compare( '==', 'order.base.service.code', $serviceItem->getCode() );
					$expr[] = $orderSearch->compare( '==', 'order.base.service.type', 'payment' );

					$orderSearch->setConditions( $orderSearch->and( $expr ) );


					$orderStart = 0;

					do
					{
						$orderItems = $orderManager->search( $orderSearch );

						foreach( $orderItems as $orderItem )
						{
							try
							{
								$serviceProvider->capture( $orderItem );
							}
							catch( \Exception $e )
							{
								$str = 'Error while capturing payment for order with ID "%1$s": %2$s';
								$msg = sprintf( $str, $serviceItem->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
								$context->getLogger()->log( $msg, Log::ERR, 'order/service/payment' );
							}
						}

						$orderCount = count( $orderItems );
						$orderStart += $orderCount;
						$orderSearch->slice( $orderStart );
					}
					while( $orderCount >= $orderSearch->getLimit() );
				}
				catch( \Exception $e )
				{
					$str = 'Error while capturing payments for service with ID "%1$s": %2$s';
					$msg = sprintf( $str, $serviceItem->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->getLogger()->log( $msg, Log::ERR, 'order/service/payment' );
				}
			}

			$count = count( $serviceItems );
			$start += $count;
			$serviceSearch->slice( $start );
		}
		while( $count >= $serviceSearch->getLimit() );
	}
}
