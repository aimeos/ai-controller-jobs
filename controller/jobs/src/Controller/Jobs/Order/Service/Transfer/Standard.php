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
		$config = $context->getConfig();

		/** controller/jobs/order/service/transfer/domains
		 * Associated items that should be available too in the order
		 *
		 * Orders consist of address, coupons, products and services. They can be
		 * fetched together with the order items and passed to the payment service
		 * providers. Available domains for those items are:
		 *
		 * - order/base
		 * - order/base/address
		 * - order/base/coupon
		 * - order/base/product
		 * - order/base/service
		 *
		 * @param array Referenced domain names
		 * @since 2021.10
		 */
		$domains = ['order/base', 'order/base/address', 'order/base/coupon', 'order/base/product', 'order/base/service'];
		$domains = $config->get( 'controller/jobs/order/service/transfer/domains', $domains );

		/** controller/jobs/order/service/transfer/transfer-days
		 * Automatically transfers payments after the configured amount of days
		 *
		 * You can start transferring payments after the configured amount of days.
		 * Before, the money is hold back and not available to vendors.
		 *
		 * @param integer Number of days
		 * @since 2010.10
		 * @category User
		 * @category Developer
		 */
		$days = $config->get( 'controller/jobs/order/service/transfer/transfer-days', 0 );

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
						$orderSearch->compare( '<=', 'order.ctime', date( 'Y-m-d 00:00:00', time() - 86400 * $days ) ),
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
