<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2023
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Basket\Cleanup;


/**
 * Basket cleanup controller.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/basket/cleanup/name
	 * Class name of the used basket cleanup scheduler controller implementation
	 *
	 * Each default log controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Basket\Cleanup\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Basket\Cleanup\Mylog
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/basket/cleanup/name = Mylog
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyLog"!
	 *
	 * @param string Last part of the class name
	 * @since 2023.10
	 */

	/** controller/jobs/basket/cleanup/decorators/excludes
	 * Excludes decorators added by the "common" option from the basket cleanup controllers
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
	 *  controller/jobs/basket/cleanup/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2023.10
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/basket/cleanup/decorators/global
	 * @see controller/jobs/basket/cleanup/decorators/local
	 */

	/** controller/jobs/basket/cleanup/decorators/global
	 * Adds a list of globally available decorators only to the basket cleanup controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/basket/cleanup/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2023.10
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/basket/cleanup/decorators/excludes
	 * @see controller/jobs/basket/cleanup/decorators/local
	 */

	/** controller/jobs/basket/cleanup/decorators/local
	 * Adds a list of local decorators only to the basket cleanup controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Basket\Cleanup\Decorator\*") around this job controller.
	 *
	 *  controller/jobs/basket/cleanup/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Basket\Cleanup\Decorator\Decorator2" only to this job
	 * controller.
	 *
	 * @param array List of decorator names
	 * @since 2023.10
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/basket/cleanup/decorators/excludes
	 * @see controller/jobs/basket/cleanup/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Cleanup baskets' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Removes the old baskets from the database' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'order/basket' );
		$filter = $manager->filter()->add( 'order.basket.mtime', '<=', $this->timestamp() );
		$cursor = $manager->cursor( $filter->slice( 0, 1000 ) );

		while( $items = $manager->iterate( $cursor ) ) {
			$manager->delete( $items );
		}
	}


	/**
	 * Returns the timestamp until the logs entries should be moved
	 *
	 * @return string Timestamp in "YYYY-MM-DD HH:mm:ss" format
	 */
	protected function timestamp() : string
	{
		/** controller/jobs/basket/cleanup/limit-days
		 * Only remove log entries that were created berore the configured number of days
		 *
		 * This option specifies the number of days log entries will be kept in
		 * the database. Afterwards, they will be removed and archived.
		 *
		 * @param integer Number of days
		 * @since 2023.10
		 * @see controller/jobs/basket/cleanup/path
		 */
		$limit = $this->context()->config()->get( 'controller/jobs/basket/cleanup/limit-days', 30 );
		return date( 'Y-m-d H:i:s', time() - $limit * 86400 );
	}
}
