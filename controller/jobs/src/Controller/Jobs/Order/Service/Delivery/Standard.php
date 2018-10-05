<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2018
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Order\Service\Delivery;


/**
 * Sends paid orders to the ERP system or logistic partner.
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
	public function getName()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Process order delivery services' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Sends paid orders to the ERP system or logistic partner' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->getContext();

		/** controller/jobs/order/service/delivery/limit-days
		 * Only start the delivery process of orders that were created in the past within the configured number of days
		 *
		 * The delivery process is normally started immediately after the
		 * notification about a successful payment arrived. This option prevents
		 * orders from being shipped in case anything went wrong or an update
		 * failed and old orders would have been shipped now.
		 *
		 * @param integer Number of days
		 * @since 2014.03
		 * @category User
		 * @category Developer
		 * @see controller/jobs/order/email/payment/standard/limit-days
		 * @see controller/jobs/order/email/delivery/standard/limit-days
		 * @see controller/jobs/order/service/delivery/batch-max
		 */
		$days = $context->getConfig()->get( 'controller/jobs/order/service/delivery/limit-days', 90 );
		$date = date( 'Y-m-d 00:00:00', time() - 86400 * $days );

		/** controller/jobs/order/service/delivery/batch-max
		 * Maximum number of orders processed at once by the delivery service provider
		 *
		 * Orders are sent in batches if the delivery service provider supports it.
		 * This setting configures the maximum orders that will be handed over to
		 * the delivery service provider at once. Bigger batches an improve the
		 * performance but requires more memory.
		 *
		 * @param integer Number of orders
		 * @since 2018.07
		 * @category Developer
		 * @see controller/jobs/order/service/delivery/limit-days
		 */
		$maxItems = $context->getConfig()->get( 'controller/jobs/order/service/delivery/batch-max', 100 );


		$serviceManager = \Aimeos\MShop\Service\Manager\Factory::createManager( $context );
		$serviceSearch = $serviceManager->createSearch();
		$serviceSearch->setConditions( $serviceSearch->compare( '==', 'service.type.code', 'delivery' ) );

		$orderManager = \Aimeos\MShop\Order\Manager\Factory::createManager( $context );
		$orderSearch = $orderManager->createSearch();

		$start = 0;

		do
		{
			$serviceItems = $serviceManager->searchItems( $serviceSearch );

			foreach( $serviceItems as $serviceItem )
			{
				try
				{
					$serviceProvider = $serviceManager->getProvider( $serviceItem, $serviceItem->getType() );

					$expr = array(
						$orderSearch->compare( '>', 'order.datepayment', $date ),
						$orderSearch->compare( '>', 'order.statuspayment', \Aimeos\MShop\Order\Item\Base::PAY_PENDING ),
						$orderSearch->compare( '==', 'order.statusdelivery', \Aimeos\MShop\Order\Item\Base::STAT_UNFINISHED ),
						$orderSearch->compare( '==', 'order.base.service.code', $serviceItem->getCode() ),
						$orderSearch->compare( '==', 'order.base.service.type', 'delivery' ),
					);
					$orderSearch->setConditions( $orderSearch->combine( '&&', $expr ) );

					$orderStart = 0;

					do
					{
						$orderSearch->setSlice( $orderStart, $maxItems );
						$orderItems = $orderManager->searchItems( $orderSearch );

						try
						{
							$serviceProvider->processBatch( $orderItems );
							$orderManager->saveItems( $orderItems );
						}
						catch( \Exception $e )
						{
							$str = 'Error while processing orders by delivery service "%1$s": %2$s';
							$context->getLogger()->log( sprintf( $str, $serviceItem->getLabel(), $e->getMessage() ) );
						}

						$orderCount = count( $orderItems );
						$orderStart += $orderCount;
					}
					while( $orderCount >= $orderSearch->getSliceSize() );
				}
				catch( \Exception $e )
				{
					$str = 'Error while processing service with ID "%1$s": %2$s';
					$context->getLogger()->log( sprintf( $str, $serviceItem->getId(), $e->getMessage() ) );
				}
			}

			$count = count( $serviceItems );
			$start += $count;
			$serviceSearch->setSlice( $start );
		}
		while( $count >= $serviceSearch->getSliceSize() );
	}
}
