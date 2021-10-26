<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Order\Service\Transfer;

use \Aimeos\MW\Logger\Base as Log;


/**
 * Transfers the money to the vendors
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
		return $this->getContext()->translate( 'controller/jobs', 'Transfers money to vendors' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Transfers the price of ordered products to the vendors incl. commission handling' );
	}


	/**
	 * Executes the job.
	 */
	public function run()
	{
		$context = $this->getContext();
		$serviceManager = \Aimeos\MShop::create( $context, 'service' );
		$serviceSearch = $serviceManager->filter()->add( ['service.type' => 'payment'] );

		$orderManager = \Aimeos\MShop::create( $context, 'order' );
		$orderSearch = $orderManager->filter();
		$start = 0;

		do
		{
			$serviceItems = $serviceManager->search( $serviceSearch );

			foreach( $serviceItems as $serviceItem )
			{
				try
				{
					$serviceProvider = $serviceManager->getProvider( $serviceItem, $serviceItem->getType() );

					if( !$serviceProvider->isImplemented( \Aimeos\MShop\Service\Provider\Payment\Base::FEAT_TRANSFER ) ) {
						continue;
					}

					$orderSearch->setConditions( $orderSearch->and( [
						$orderSearch->compare( '==', 'order.statuspayment', \Aimeos\MShop\Order\Item\Base::PAY_RECEIVED ),
						$orderSearch->compare( '==', 'order.base.service.code', $serviceItem->getCode() ),
						$orderSearch->compare( '==', 'order.base.service.type', 'payment' )
					] ) );

					$orderStart = 0;

					do
					{
						$orderItems = $orderManager->search( $orderSearch );

						foreach( $orderItems as $orderItem )
						{
							try
							{
								$orderManager->save( $serviceProvider->transfer( $orderItem ) );
							}
							catch( \Exception $e )
							{
								$str = 'Error while transferring payment for order with ID "%1$s": %2$s';
								$msg = sprintf( $str, $serviceItem->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
								$context->logger()->log( $msg, Log::ERR, 'order/service/transfer' );
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
					$str = 'Error while transferring payment for service with ID "%1$s": %2$s';
					$msg = sprintf( $str, $serviceItem->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->getLogger()->log( $msg, Log::ERR, 'order/service/transfer' );
				}
			}

			$count = count( $serviceItems );
			$start += $count;
			$serviceSearch->slice( $start );
		}
		while( $count >= $serviceSearch->getLimit() );
	}
}
