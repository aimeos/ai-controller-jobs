<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Order\Cleanup\Unfinished;


/**
 * Order cleanup job controller for removing unfinished orders.
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
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Removes unfinished orders' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Deletes unfinished orders an makes their products and coupon codes available again' );
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

		/** controller/jobs/order/cleanup/unfinished/keep-hours
		 * Release the ordered products after the configured time if no payment was confirmed
		 *
		 * After a customer creates an order and before he is redirected to the
		 * payment provider (if necessary), the ordered products, coupon codes,
		 * etc. are blocked for that customer. Normally, they should be released
		 * a certain amount of time if no payment confirmation arrives so
		 * customers can order the products and use the coupon codes again.
		 *
		 * The configured number of hours should be high enough to avoid releasing
		 * products and coupon codes in case of temporary technical problems!
		 *
		 * The unfinished orders are deleted afterwards to keep the database clean.
		 *
		 * @param integer Number of hours
		 * @since 2014.07
		 * @category User
		 */
		$hours = $context->getConfig()->get( 'controller/jobs/order/cleanup/unfinished/keep-hours', 24 );
		$limit = date( 'Y-m-d H:i:s', time() - 3600 * $hours );

		$search = $manager->filter();
		$expr = array(
			$search->compare( '<', 'order.mtime', $limit ),
			$search->compare( '==', 'order.statuspayment', \Aimeos\MShop\Order\Item\Base::PAY_UNFINISHED ),
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
