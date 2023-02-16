<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2023
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\End;


/**
 * Job controller for subscription processs end.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Subscription\Process\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/subscription/process/end/name
	 * Class name of the used subscription suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Subscription\Process\End\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Subscription\Process\End\Myend
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/subscription/process/end/name = Myend
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyEnd"!
	 *
	 * @param string Last part of the class name
	 * @since 2018.04
	 */

	/** controller/jobs/subscription/process/end/decorators/excludes
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
	 *  controller/jobs/subscription/process/end/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2018.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/subscription/process/end/decorators/global
	 * @see controller/jobs/subscription/process/end/decorators/local
	 */

	/** controller/jobs/subscription/process/end/decorators/global
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
	 *  controller/jobs/subscription/process/end/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2018.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/subscription/process/end/decorators/excludes
	 * @see controller/jobs/subscription/process/end/decorators/local
	 */

	/** controller/jobs/subscription/process/end/decorators/local
	 * Adds a list of local decorators only to the subscription process CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Subscription\Process\End\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/subscription/process/end/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Subscription\Process\End\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2018.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/subscription/process/end/decorators/excludes
	 * @see controller/jobs/subscription/process/end/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Subscription process end' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Terminates expired subscriptions' );
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

		$filter = $manager->filter( true )->add( 'subscription.dateend', '<', date( 'Y-m-d' ) )->slice( 0, $this->max() );
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

					$str = 'Unable to end subscription with ID "%1$s": %2$s';
					$msg = sprintf( $str, $item->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->logger()->error( $msg, 'subscription/process/end' );
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
		 * @see controller/jobs/subscription/process/processors
		 * @see controller/common/subscription/process/payment-days
		 * @see controller/common/subscription/process/payment-status
		 */
		$ref = ['order'] + $config->get( 'mshop/order/manager/subdomains', [] );
		return $config->get( 'controller/jobs/subscription/process/domains', $ref );
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
		foreach( $processors as $processor ) {
			$processor->end( $item, $item->getOrderItem() );
		}

		if( ( $reason = $item->getReason() ) === null ) {
			$reason = \Aimeos\MShop\Subscription\Item\Iface::REASON_END;
		}

		return $item->setReason( $reason )->setStatus( 0 );
	}
}
