<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021-2023
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Order\Service\Transfer;


/**
 * Transfers the money to the vendors
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
		/** controller/jobs/order/service/transfer/name
		 * Class name of the used order service transfer scheduler controller implementation
		 *
		 * Each default job controller can be replace by an alternative imlementation.
		 * To use this implementation, you have to set the last part of the class
		 * name as configuration value so the controller factory knows which class it
		 * has to instantiate.
		 *
		 * For example, if the name of the default class is
		 *
		 *  \Aimeos\Controller\Jobs\Order\Service\Transfer\Standard
		 *
		 * and you want to replace it with your own version named
		 *
		 *  \Aimeos\Controller\Jobs\Order\Service\Transfer\Mytransfer
		 *
		 * then you have to set the this configuration option:
		 *
		 *  controller/jobs/order/service/transfer/name = Mytransfer
		 *
		 * The value is the last part of your own class name and it's case sensitive,
		 * so take care that the configuration value is exactly named like the last
		 * part of the class name.
		 *
		 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
		 * characters are possible! You should always start the last part of the class
		 * name with an upper case character and continue only with lower case characters
		 * or numbers. Avoid chamel case names like "MyTransfer"!
		 *
		 * @param string Last part of the class name
		 * @since 2021.10
		 */

		/** controller/jobs/order/service/transfer/decorators/excludes
		 * Excludes decorators added by the "common" option from the order service transfer controllers
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
		 *  controller/jobs/order/service/transfer/decorators/excludes = array( 'decorator1' )
		 *
		 * This would remove the decorator named "decorator1" from the list of
		 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
		 * "controller/jobs/common/decorators/default" to this job controller.
		 *
		 * @param array List of decorator names
		 * @since 2021.10
		 * @see controller/jobs/common/decorators/default
		 * @see controller/jobs/order/service/transfer/decorators/global
		 * @see controller/jobs/order/service/transfer/decorators/local
		 */

		/** controller/jobs/order/service/transfer/decorators/global
		 * Adds a list of globally available decorators only to the order service transfer controllers
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap global decorators
		 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
		 *
		 *  controller/jobs/order/service/transfer/decorators/global = array( 'decorator1' )
		 *
		 * This would add the decorator named "decorator1" defined by
		 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to this job controller.
		 *
		 * @param array List of decorator names
		 * @since 2021.10
		 * @see controller/jobs/common/decorators/default
		 * @see controller/jobs/order/service/transfer/decorators/excludes
		 * @see controller/jobs/order/service/transfer/decorators/local
		 */

		/** controller/jobs/order/service/transfer/decorators/local
		 * Adds a list of local decorators only to the order service transfer controllers
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap local decorators
		 * ("\Aimeos\Controller\Jobs\Order\Service\Transfer\Decorator\*") around this job controller.
		 *
		 *  controller/jobs/order/service/transfer/decorators/local = array( 'decorator2' )
		 *
		 * This would add the decorator named "decorator2" defined by
		 * "\Aimeos\Controller\Jobs\Order\Service\Transfer\Decorator\Decorator2" only to this job
		 * controller.
		 *
		 * @param array List of decorator names
		 * @since 2021.10
		 * @see controller/jobs/common/decorators/default
		 * @see controller/jobs/order/service/transfer/decorators/excludes
		 * @see controller/jobs/order/service/transfer/decorators/global
		 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Transfers money to vendors' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Transfers the price of ordered products to the vendors incl. commission handling' );
	}


	/**
	 * Executes the job.
	 */
	public function run()
	{
		$context = $this->context();
		$manager = \Aimeos\MShop::create( $context, 'service' );

		$filter = $manager->filter()->add( ['service.type' => 'payment'] );
		$cursor = $manager->cursor( $filter );

		while( $items = $manager->iterate( $cursor ) )
		{
			foreach( $items as $item )
			{
				try
				{
					$provider = $manager->getProvider( $item, $item->getType() );

					if( $provider->isImplemented( \Aimeos\MShop\Service\Provider\Payment\Base::FEAT_TRANSFER ) ) {
						$this->orders( $provider );
					}
				}
				catch( \Exception $e )
				{
					$str = 'Error while transferring payment for service with ID "%1$s": %2$s';
					$msg = sprintf( $str, $item->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->logger()->error( $msg, 'order/service/transfer' );
				}
			}
		}
	}


	/**
	 * Returns the number of days to postpone transfers
	 *
	 * @return string Date/time in "YYYY-MM-DD HH:mm:ss" format
	 */
	protected function limit() : string
	{
		/** controller/jobs/order/service/transfer/transfer-days
		 * Automatically transfers payments after the configured amount of days
		 *
		 * You can start transferring payments after the configured amount of days.
		 * Before, the money is hold back and not available to vendors.
		 *
		 * @param integer Number of days
		 * @since 2010.10
		 */
		$days = $this->context()->config()->get( 'controller/jobs/order/service/transfer/transfer-days', 0 );
		return date( 'Y-m-d H:i:s', time() - 86400 * $days );
	}


	/**
	 * Returns the domains used when fetching orders
	 *
	 * @return array List of data domain names
	 */
	protected function domains() : array
	{
		$config = $this->context()->config();

		/** controller/jobs/order/service/transfer/domains
		 * Associated items that should be available too in the order
		 *
		 * Orders consist of address, coupons, products and services. They can be
		 * fetched together with the order items and passed to the payment service
		 * providers. Available domains for those items are:
		 *
		 * - order/address
		 * - order/coupon
		 * - order/product
		 * - order/service
		 *
		 * @param array Referenced domain names
		 * @since 2022.04
		 */
		$ref = $config->get( 'mshop/order/manager/subdomains', [] );
		return $config->get( 'controller/jobs/order/service/transfer/domains', $ref );
	}


	/**
	 * Returns the maximum number of orders processed at once
	 *
	 * @return int Maximum number of items
	 */
	protected function max() : int
	{
		/** controller/jobs/order/service/transfer/batch-max
		 * Maximum number of orders processed at once by the payment service provider
		 *
		 * Orders are sent in batches if the payment service provider supports it.
		 * This setting configures the maximum orders that will be handed over to
		 * the payment service provider at once. Bigger batches an improve the
		 * performance but requires more memory.
		 *
		 * @param integer Number of orders
		 * @since 2023.04
		 * @see controller/jobs/order/service/transfer/domains
		 * @see controller/jobs/order/service/transfer/limit-days
		 */
		return $this->context()->config()->get( 'controller/jobs/order/service/transfer/batch-max', 100 );
	}


	/**
	 * Fetches and processes the order items
	 *
	 * @param \Aimeos\MShop\Service\Provider\Iface $provider Service provider for processing the orders
	 */
	protected function orders( \Aimeos\MShop\Service\Provider\Iface $provider )
	{
		$context = $this->context();
		$domains = $this->domains();

		$serviceItem = $provider->getServiceItem();
		$manager = \Aimeos\MShop::create( $context, 'order' );

		$filter = $manager->filter()->slice( 0, $this->max() );
		$filter->setConditions( $filter->and( [
			$filter->compare( '<=', 'order.ctime', $this->limit() ),
			$filter->compare( '==', 'order.statuspayment', \Aimeos\MShop\Order\Item\Base::PAY_RECEIVED ),
			$filter->compare( '==', 'order.service.code', $serviceItem->getCode() ),
			$filter->compare( '==', 'order.service.type', 'payment' )
		] ) );
		$cursor = $manager->cursor( $filter );

		while( $items = $manager->iterate( $cursor, $domains ) )
		{
			foreach( $items as $item )
			{
				try
				{
					$manager->save( $provider->transfer( $item ) );
				}
				catch( \Exception $e )
				{
					$str = 'Error while transferring payment for order with ID "%1$s": %2$s';
					$msg = sprintf( $str, $item->getId(), $e->getMessage() . "\n" . $e->getTraceAsString() );
					$context->logger()->error( $msg, 'order/service/transfer' );
				}
			}
		}
	}
}
