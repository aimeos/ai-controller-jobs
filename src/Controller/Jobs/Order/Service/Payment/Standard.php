<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2022
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Order\Service\Payment;


/**
 * Captures the money for authorized orders
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/order/service/payment/name
	 * Class name of the used order service payment scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Order\Service\Payment\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Order\Service\Payment\Mypayment
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/order/service/payment/name = Mypayment
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyPayment"!
	 *
	 * @param string Last part of the class name
	 * @since 2014.07
	 * @category Developer
	 */

	/** controller/jobs/order/service/payment/decorators/excludes
	 * Excludes decorators added by the "common" option from the order service payment controllers
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
	 *  controller/jobs/order/service/payment/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/service/payment/decorators/global
	 * @see controller/jobs/order/service/payment/decorators/local
	 */

	/** controller/jobs/order/service/payment/decorators/global
	 * Adds a list of globally available decorators only to the order service payment controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/order/service/payment/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/service/payment/decorators/excludes
	 * @see controller/jobs/order/service/payment/decorators/local
	 */

	/** controller/jobs/order/service/payment/decorators/local
	 * Adds a list of local decorators only to the order service payment controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Order\Service\Payment\Decorator\*") around this job controller.
	 *
	 *  controller/jobs/order/service/payment/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Order\Service\Payment\Decorator\Decorator2" only to this job
	 * controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/service/payment/decorators/excludes
	 * @see controller/jobs/order/service/payment/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Capture authorized payments' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Authorized payments of orders will be captured after dispatching or after a configurable amount of time' );
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


		/** controller/jobs/order/service/payment/limit-days
		 * Only start capturing payments of orders that were created in the past within the configured number of days
		 *
		 * Capturing payments is normally done immediately after the delivery
		 * status changed to "dispatched" or "delivered". This option prevents
		 * payments from being captured in case anything went wrong and payments
		 * of old orders would be captured now.
		 *
		 * @param integer Number of days
		 * @since 2014.07
		 * @category User
		 * @category Developer
		 */
		$days = $config->get( 'controller/jobs/order/service/payment/limit-days', 90 );
		$date = date( 'Y-m-d 00:00:00', time() - 86400 * $days );

		/** controller/jobs/order/service/payment/domains
		 * Associated items that should be available too in the order
		 *
		 * Orders consist of address, coupons, products and services. They can be
		 * fetched together with the order items and passed to the payment service
		 * providers. Available domains for those items are:
		 *
		 * - order/base
		 * - order/base/address
		 * - order/base/coupon
		 * - order/base/product
		 * - order/base/service
		 *
		 * @param array Referenced domain names
		 * @since 2022.04
		 * @see controller/jobs/order/email/payment/limit-days
		 * @see controller/jobs/order/service/payment/capture-days
		 */
		$domains = ['order/base', 'order/base/address', 'order/base/coupon', 'order/base/product', 'order/base/service'];
		$domains = $context->config()->get( 'controller/jobs/order/service/delivery/domains', $domains );

		/** controller/jobs/order/service/payment/capture-days
		 * Automatically capture payments after the configured amount of days
		 *
		 * You can capture authorized payments after a configured amount of days
		 * even if the parcel for the order wasn't dispatched yet. This is useful
		 * for payment methods like credit cards where autorizations are revoked
		 * by the aquirers after some time (usually seven days).
		 *
		 * @param integer Number of days
		 * @since 2014.07
		 * @category User
		 * @category Developer
		 */
		$capDays = $config->get( 'controller/jobs/order/service/payment/capture-days', null );


		$serviceManager = \Aimeos\MShop::create( $context, 'service' );
		$serviceSearch = $serviceManager->filter();
		$serviceSearch->setConditions( $serviceSearch->compare( '==', 'service.type', 'payment' ) );

		$orderManager = \Aimeos\MShop::create( $context, 'order' );
		$orderSearch = $orderManager->filter();

		$status = array( \Aimeos\MShop\Order\Item\Base::STAT_DISPATCHED, \Aimeos\MShop\Order\Item\Base::STAT_DELIVERED );
		$start = 0;

		do
		{
			$serviceItems = $serviceManager->search( $serviceSearch );

			foreach( $serviceItems as $serviceItem )
			{
				try
				{
					$serviceProvider = $serviceManager->getProvider( $serviceItem, $serviceItem->getType() );

					if( !$serviceProvider->isImplemented( \Aimeos\MShop\Service\Provider\Payment\Base::FEAT_CAPTURE ) ) {
						continue;
					}


					$expr = [];
					$expr[] = $orderSearch->compare( '>', 'order.datepayment', $date );

					if( $capDays !== null )
					{
						$capdate = date( 'Y-m-d 00:00:00', time() - 86400 * $capDays );
						$expr[] = $orderSearch->compare( '<=', 'order.datepayment', $capdate );
					}
					else
					{
						$expr[] = $orderSearch->compare( '==', 'order.statusdelivery', $status );
					}

					$expr[] = $orderSearch->compare( '==', 'order.statuspayment', \Aimeos\MShop\Order\Item\Base::PAY_AUTHORIZED );
					$expr[] = $orderSearch->compare( '==', 'order.base.service.code', $serviceItem->getCode() );
					$expr[] = $orderSearch->compare( '==', 'order.base.service.type', 'payment' );

					$orderSearch->setConditions( $orderSearch->and( $expr ) );


					$orderStart = 0;

					do
					{
						$orderItems = $orderManager->search( $orderSearch, $domains );

						foreach( $orderItems as $orderItem )
						{
							try
							{
								$serviceProvider->capture( $orderItem );
							}
							catch( \Exception $e )
							{
								$str = 'Error while capturing payment for order with ID "%1$s": %2$s';
								$msg = sprintf( $str, $serviceItem->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
								$context->logger()->error( $msg, 'order/service/payment' );
							}
						}

						$orderCount = count( $orderItems );
						$orderStart += $orderCount;
						$orderSearch->slice( $orderStart );
					}
					while( $orderCount >= $orderSearch->getLimit() );
				}
				catch( \Exception $e )
				{
					$str = 'Error while capturing payments for service with ID "%1$s": %2$s';
					$msg = sprintf( $str, $serviceItem->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->logger()->error( $msg, 'order/service/payment' );
				}
			}

			$count = count( $serviceItems );
			$start += $count;
			$serviceSearch->slice( $start );
		}
		while( $count >= $serviceSearch->getLimit() );
	}
}
