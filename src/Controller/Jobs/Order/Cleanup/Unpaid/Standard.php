<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2024
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
	/** controller/jobs/order/cleanup/unpaid/name
	 * Class name of the used order cleanup unpaid scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Order\Cleanup\Unpaid\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Order\Cleanup\Unpaid\Myunpaid
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/order/cleanup/unpaid/name = Myunpaid
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyUnpaid"!
	 *
	 * @param string Last part of the class name
	 * @since 2014.07
	 */

	/** controller/jobs/order/cleanup/unpaid/decorators/excludes
	 * Excludes decorators added by the "common" option from the order cleanup unpaid controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to remove a decorator added via
	 * "controller/jobs/common/decorators/default" before they are wrapped
	 * around the job controller.
	 *
	 *  controller/jobs/order/cleanup/unpaid/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/cleanup/unpaid/decorators/global
	 * @see controller/jobs/order/cleanup/unpaid/decorators/local
	 */

	/** controller/jobs/order/cleanup/unpaid/decorators/global
	 * Adds a list of globally available decorators only to the order cleanup unpaid controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/order/cleanup/unpaid/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/cleanup/unpaid/decorators/excludes
	 * @see controller/jobs/order/cleanup/unpaid/decorators/local
	 */

	/** controller/jobs/order/cleanup/unpaid/decorators/local
	 * Adds a list of local decorators only to the order cleanup unpaid controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Order\Cleanup\Unpaid\Decorator\*") around this job controller.
	 *
	 *  controller/jobs/order/cleanup/unpaid/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Order\Cleanup\Unpaid\Decorator\Decorator2" only to this job
	 * controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/cleanup/unpaid/decorators/excludes
	 * @see controller/jobs/order/cleanup/unpaid/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Removes unpaid orders' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Deletes unpaid orders to keep the database clean' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->context();
		$manager = \Aimeos\MShop::create( $context, 'order' );

		$filter = $manager->filter()
			->add( 'order.mtime', '<', $this->mtime() )
			->add( 'order.statuspayment', '<', \Aimeos\MShop\Order\Item\Base::PAY_REFUND );
		$cursor = $manager->cursor( $filter );

		while( $items = $manager->iterate( $cursor ) )
		{
			foreach( $items as $item ) {
				$manager->unblock( $item );
			}

			$manager->delete( $items );
		}
	}


	/**
	 * Returns the modifiction time when orders can be deleted
	 *
	 * @return string Date/time in "YYYY-mm-dd HH:mm:ss" format
	 */
	protected function mtime() : string
	{
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
		 */
		$days = $this->context()->config()->get( 'controller/jobs/order/cleanup/unpaid/keep-days', 3 );
		return date( 'Y-m-d H:i:s', time() - 86400 * $days );
	}
}
