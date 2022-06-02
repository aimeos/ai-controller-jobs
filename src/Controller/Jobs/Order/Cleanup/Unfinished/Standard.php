<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2022
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
	/** controller/jobs/order/cleanup/unfinished/name
	 * Class name of the used order cleanup unfinished scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Order\Cleanup\Unfinished\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Order\Cleanup\Unfinished\Myunfinished
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/order/cleanup/unfinished/name = Myunfinished
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyUnfinished"!
	 *
	 * @param string Last part of the class name
	 * @since 2014.03
	 * @category Developer
	 */

	/** controller/jobs/order/cleanup/unfinished/decorators/excludes
	 * Excludes decorators added by the "common" option from the order cleanup unfinished controllers
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
	 *  controller/jobs/order/cleanup/unfinished/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/cleanup/unfinished/decorators/global
	 * @see controller/jobs/order/cleanup/unfinished/decorators/local
	 */

	/** controller/jobs/order/cleanup/unfinished/decorators/global
	 * Adds a list of globally available decorators only to the order cleanup unfinished controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/order/cleanup/unfinished/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/cleanup/unfinished/decorators/excludes
	 * @see controller/jobs/order/cleanup/unfinished/decorators/local
	 */

	/** controller/jobs/order/cleanup/unfinished/decorators/local
	 * Adds a list of local decorators only to the order cleanup unfinished controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Order\Cleanup\Unfinished\Decorator\*") around this job controller.
	 *
	 *  controller/jobs/order/cleanup/unfinished/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Order\Cleanup\Unfinished\Decorator\Decorator2" only to this job
	 * controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/cleanup/unfinished/decorators/excludes
	 * @see controller/jobs/order/cleanup/unfinished/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Removes unfinished orders' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Deletes unfinished orders an makes their products and coupon codes available again' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->context();
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
		$hours = $context->config()->get( 'controller/jobs/order/cleanup/unfinished/keep-hours', 24 );
		$limit = date( 'Y-m-d H:i:s', time() - 3600 * $hours );

		$search = $manager->filter();
		$expr = array(
			$search->compare( '<', 'order.mtime', $limit ),
			$search->compare( '==', 'order.statuspayment', null ),
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
