<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2022
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Order\Service\Async;


/**
 * Updates the payment or delivery status for services with asynchronous methods.
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
		return $this->context()->translate( 'controller/jobs', 'Batch update of payment/delivery status' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Executes payment or delivery service providers that uses batch updates' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->context();
		$serviceManager = \Aimeos\MShop::create( $context, 'service' );

		$search = $serviceManager->filter();
		$start = 0;

		do
		{
			$serviceItems = $serviceManager->search( $search->slice( $start ) );

			foreach( $serviceItems as $serviceItem )
			{
				try
				{
					$serviceManager->getProvider( $serviceItem, $serviceItem->getType() )->updateAsync();
				}
				catch( \Exception $e )
				{
					$str = 'Executing updateAsyc() of "%1$s" failed: %2$s';
					$msg = sprintf( $str, $serviceItem->getProvider(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->logger()->error( $msg, 'order/service/async' );
				}
			}

			$count = count( $serviceItems );
			$start += $count;
		}
		while( $count >= $search->getLimit() );
	}
}
