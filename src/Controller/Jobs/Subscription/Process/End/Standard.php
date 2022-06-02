<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2022
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
	 * @category Developer
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
	 * @category Developer
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
	 * @category Developer
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
	 * @category Developer
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
		$config = $context->config();

		/** controller/common/subscription/process/processors
		 * List of processor names that should be executed for subscriptions
		 *
		 * For each subscription a number of processors for different tasks can be executed.
		 * They can for example add a group to the customers' account during the customer
		 * has an active subscribtion.
		 *
		 * @param array List of processor names
		 * @since 2018.04
		 * @category Developer
		 */
		$names = (array) $config->get( 'controller/common/subscription/process/processors', [] );

		$domains = ['order/base', 'order/base/address', 'order/base/coupon', 'order/base/product', 'order/base/service'];

		$processors = $this->getProcessors( $names );
		$manager = \Aimeos\MShop::create( $context, 'subscription' );
		$orderManager = \Aimeos\MShop::create( $context, 'order' );

		$search = $manager->filter( true );
		$expr = [
			$search->compare( '<', 'subscription.dateend', date( 'Y-m-d' ) ),
			$search->getConditions(),
		];
		$search->setConditions( $search->and( $expr ) );
		$search->setSortations( [$search->sort( '+', 'subscription.id' )] );

		$start = 0;

		do
		{
			$orderItems = [];

			$search->slice( $start, 100 );
			$items = $manager->search( $search );
			$ordBaseIds = $items->getOrderBaseId()->toArray();

			$orderSearch = $orderManager->filter()->slice( 0, $search->getLimit() );
			$orderSearch->setConditions( $orderSearch->compare( '==', 'order.baseid', $ordBaseIds ) );
			$orderSearch->setSortations( [$orderSearch->sort( '+', 'order.id' )] );

			$orderItems = $orderManager->search( $orderSearch, $domains )->col( null, 'order.baseid' );

			foreach( $items as $item )
			{
				try
				{
					if( $orderItem = $orderItems->get( $item->getOrderBaseId() ) )
					{
						foreach( $processors as $processor ) {
							$processor->end( $item, $orderItem );
						}
					}

					if( ( $reason = $item->getReason() ) === null ) {
						$reason = \Aimeos\MShop\Subscription\Item\Iface::REASON_END;
					}

					$manager->save( $item->setReason( $reason )->setStatus( 0 ) );
				}
				catch( \Exception $e )
				{
					$str = 'Unable to end subscription with ID "%1$s": %2$s';
					$msg = sprintf( $str, $item->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->logger()->error( $msg, 'subscription/process/end' );
				}
			}

			$count = count( $items );
			$start += $count;
		}
		while( $count === $search->getLimit() );
	}
}
