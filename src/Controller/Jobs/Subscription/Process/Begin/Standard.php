<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2022
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\Begin;


/**
 * Job controller for subscription processs start.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Subscription\Process\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/subscription/process/begin/name
	 * Class name of the used subscription suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Subscription\Process\Begin\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Subscription\Process\Begin\Mybegin
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/subscription/process/begin/name = Mybegin
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyBegin"!
	 *
	 * @param string Last part of the class name
	 * @since 2018.04
	 */

	/** controller/jobs/subscription/process/begin/decorators/excludes
	 * Excludes decorators added by the "common" option from the subscription process CSV job controller
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
	 *  controller/jobs/subscription/process/begin/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2018.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/subscription/process/begin/decorators/global
	 * @see controller/jobs/subscription/process/begin/decorators/local
	 */

	/** controller/jobs/subscription/process/begin/decorators/global
	 * Adds a list of globally available decorators only to the subscription process CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/subscription/process/begin/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2018.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/subscription/process/begin/decorators/excludes
	 * @see controller/jobs/subscription/process/begin/decorators/local
	 */

	/** controller/jobs/subscription/process/begin/decorators/local
	 * Adds a list of local decorators only to the subscription process CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Subscription\Process\Begin\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/subscription/process/begin/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Subscription\Process\Begin\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2018.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/subscription/process/begin/decorators/excludes
	 * @see controller/jobs/subscription/process/begin/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Subscription process start' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Process subscriptions initially' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->context();
		$domains = $this->domains();
		$processors = $this->getProcessors( $this->names() );

		$manager = \Aimeos\MShop::create( $context, 'subscription' );

		$filter = $manager->filter( true )->add( 'subscription.datenext', '==', null )->slice( 0, $this->max() );
		$cursor = $manager->cursor( $filter );

		while( $items = $manager->iterate( $cursor, $domains ) )
		{
			foreach( $items as $item )
			{
				$manager->begin();

				try
				{
					$manager->save( $this->process( $item, $processors ) );
					$manager->commit();
				}
				catch( \Exception $e )
				{
					$manager->rollback();

					$str = 'Unable to begin subscription with ID "%1$s": %2$s';
					$msg = sprintf( $str, $item->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->logger()->error( $msg, 'subscription/process/begin' );
				}
			}
		}
	}


	/**
	 * Returns the domains that should be fetched together with the order data
	 *
	 * @return array List of domain names
	 */
	protected function domains() : array
	{
		$config = $this->context()->config();

		/** controller/jobs/subscription/process/domains
		 * Associated items that should be available too in the subscription
		 *
		 * Orders consist of address, coupons, products and services. They can be
		 * fetched together with the subscription items and passed to the processor.
		 * Available domains for those items are:
		 *
		 * - order
		 * - order/address
		 * - order/coupon
		 * - order/product
		 * - order/service
		 *
		 * @param array Referenced domain names
		 * @since 2022.04
		 * @see controller/common/subscription/process/processors
		 * @see controller/common/subscription/process/payment-days
		 * @see controller/common/subscription/process/payment-status
		 */
		$ref = ['order'] + $config->get( 'mshop/order/manager/subdomains', [] );
		return $config->get( 'controller/jobs/subscription/process/domains', $ref );
	}


	/**
	 * Returns the payment date until orders should be processed
	 *
	 * @return string Date/time in "YYYY-MM-DD HH:mm:ss" format
	 */
	protected function limit() : string
	{
		/** controller/jobs/subscription/process/payment-days
		 * Number of days to wait for the payment until subscription is removed
		 *
		 * Subscriptions wait for the confiugrable number of days until the payment
		 * status changes to a valid payment (by default: "authorized" and "received").
		 * If the payment arrives within this time frame, the subscription is activated.
		 * Otherwise, the subscription is removed from the list of subscriptions that
		 * will be checked for activation.
		 *
		 * @param float Number of days
		 * @since 2018.07
		 * @see controller/jobs/subscription/process/processors
		 * @see controller/jobs/subscription/process/payment-status
		 */
		$days = $this->context()->config()->get( 'controller/jobs/subscription/process/payment-days', 90 );
		return date( 'Y-m-d H:i:s', time() - 86400 * $days );
	}


	/**
	 * Returns the maximum number of orders processed at once
	 *
	 * @return int Maximum number of items
	 */
	protected function max() : int
	{
		/** controller/jobs/subscription/process/batch-max
		 * Maximum number of subscriptions processed at once by the subscription process job
		 *
		 * This setting configures the maximum number of subscriptions including
		 * orders that will be processed at once. Bigger batches an improve the
		 * performance but requires more memory.
		 *
		 * @param integer Number of subscriptions
		 * @since 2023.04
		 * @see controller/jobs/subscription/process/domains
		 * @see controller/jobs/subscription/process/names
		 * @see controller/jobs/subscription/process/payment-days
		 * @see controller/jobs/subscription/process/payment-status
		 */
		return $this->context()->config()->get( 'controller/jobs/subscription/process/batch-max', 100 );
	}


	/**
	 * Returns the names of the subscription processors
	 *
	 * @return array List of processor names
	 */
	protected function names() : array
	{
		/** controller/jobs/subscription/process/processors
		 * List of processor names that should be executed for subscriptions
		 *
		 * For each subscription a number of processors for different tasks can be executed.
		 * They can for example add a group to the customers' account during the customer
		 * has an active subscribtion.
		 *
		 * @param array List of processor names
		 * @since 2018.04
		 * @see controller/jobs/subscription/process/domains
		 * @see controller/jobs/subscription/process/max
		 * @see controller/jobs/subscription/process/payment-days
		 * @see controller/jobs/subscription/process/payment-status
		 */
		return (array) $this->context()->config()->get( 'controller/jobs/subscription/process/processors', [] );
	}


	/**
	 * Runs the passed processors over all items and updates the properties
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $item Subscription item
	 * @param iterable $processors List of processor objects
	 * @return \Aimeos\MShop\Subscription\Item\Iface Updated subscription item
	 */
	protected function process( \Aimeos\MShop\Subscription\Item\Iface $item, iterable $processors ) : \Aimeos\MShop\Subscription\Item\Iface
	{
		if( $item->getOrderItem()->getStatusPayment() >= $this->status() )
		{
			foreach( $processors as $processor ) {
				$processor->begin( $item, $item->getOrderItem() );
			}

			$interval = new \DateInterval( $item->getInterval() );
			$dateNext = date_create( $item->getTimeCreated() )->add( $interval )->format( 'Y-m-d' );

			return $item->setDateNext( $dateNext )->setPeriod( 1 );
		}

		if( $item->getTimeCreated() < $this->limit() ) {
			$item->setStatus( 0 );
		}

		return $item;
	}


	/**
	 * Returns the minimum payment status to activate subscriptions
	 *
	 * @return int Minimum payment status
	 */
	protected function status() : int
	{
		/** controller/jobs/subscription/process/payment-status
		 * Minimum payment status that will activate the subscription
		 *
		 * Subscriptions will be activated if the payment status of the order is
		 * at least the configured payment constant. The default payment status
		 * is "authorized" so orders with a payment status of "authorized" (5) and
		 * "received" (6) will cause the subscription to be activated. Lower
		 * payment status values, e.g. "pending" (4) won't activate the subscription.
		 *
		 * @param integer Payment status constant
		 * @since 2018.07
		 * @see controller/jobs/subscription/process/begin/domains
		 * @see controller/jobs/subscription/process/begin/max
		 * @see controller/jobs/subscription/process/begin/names
		 * @see controller/jobs/subscription/process/payment-days
		 */
		$status = \Aimeos\MShop\Order\Item\Base::PAY_AUTHORIZED;
		return $this->context()->config()->get( 'controller/jobs/subscription/process/payment-status', $status );
	}
}
