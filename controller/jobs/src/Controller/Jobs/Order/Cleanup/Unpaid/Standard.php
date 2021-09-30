<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Order\Cleanup\Unpaid;


/**
 * Order cleanup job controller for removing unpaid orders.
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
		return $this->getContext()->translate( 'controller/jobs', 'Removes unpaid orders' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Deletes unpaid orders to keep the database clean' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->getContext();
		$controller = \Aimeos\Controller\Common\Order\Factory::create( $context );
		$baseManager = \Aimeos\MShop::create( $context, 'order/base' );
		$manager = \Aimeos\MShop::create( $context, 'order' );

		/** controller/jobs/order/cleanup/unpaid/keep-days
		 * Removes all orders from the database that are unpaid
		 *
		 * Orders with a payment status of deleted, canceled or refused are only
		 * necessary for the records for a certain amount of time. Afterwards,
		 * they can be deleted from the database most of the time.
		 *
		 * The number of days should be high enough to ensure that you keep the
		 * orders as long as your customers will be asking what happend to their
		 * orders.
		 *
		 * @param integer Number of days
		 * @since 2014.07
		 * @category User
		 */
		$days = $context->getConfig()->get( 'controller/jobs/order/cleanup/unpaid/keep-days', 3 );
		$limit = date( 'Y-m-d H:i:s', time() - 86400 * $days );

		$search = $manager->filter();
		$expr = array(
			$search->compare( '<', 'order.mtime', $limit ),
			$search->compare( '<', 'order.statuspayment', \Aimeos\MShop\Order\Item\Base::PAY_REFUND ),
		);
		$search->setConditions( $search->and( $expr ) );

		$start = 0;

		do
		{
			$baseIds = [];
			$items = $manager->search( $search );

			foreach( $items as $item )
			{
				$controller->unblock( $item );
				$baseIds[] = $item->getBaseId();
			}

			$baseManager->delete( $baseIds );

			$count = count( $items );
			$start += $count;
			$search->slice( $start );
		}
		while( $count >= $search->getLimit() );
	}
}
