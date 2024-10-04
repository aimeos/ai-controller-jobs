<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2024
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Cgroup;


/**
 * Customer group processor for subscriptions
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Iface
{
	/** controller/jobs/subscription/process/processor/cgroup/name
	 * Name of the customer group processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Cgroup\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2018.04
	 */

	private array $groupIds;


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context )
	{
		parent::__construct( $context );

		$config = $context->config();

		/** controller/jobs/subscription/process/processor/cgroup/groupids
		 * List of group IDs that should be added to the customer account
		 *
		 * After customers bought a subscription, the list of group IDs will be
		 * added to their accounts. When the subscription period ends, they will
		 * be removed from the customer accounts again.
		 *
		 * @param array List of customer group IDs
		 * @since 2018.04
		 */
		$this->groupIds = (array) $config->get( 'controller/jobs/subscription/process/processor/cgroup/groupids', [] );
	}


	/**
	 * Processes the initial subscription
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item
	 * @param \Aimeos\MShop\Order\Item\Iface $subscription Order item
	 */
	public function begin( \Aimeos\MShop\Subscription\Item\Iface $subscription, \Aimeos\MShop\Order\Item\Iface $order )
	{
		$context = $this->context();

		$manager = \Aimeos\MShop::create( $context, 'customer' );
		$productManager = \Aimeos\MShop::create( $context, 'order/product' );

		$productItem = $productManager->get( $subscription->getOrderProductId() );
		$item = $manager->get( $subscription->getOrderItem()->getCustomerId(), ['group'] );

		if( ( $groupIds = (array) $productItem->getAttribute( 'group', 'hidden' ) ) === [] ) {
			$groupIds = $this->groupIds;
		}

		$item->setGroups( array_replace( $item->getGroups(), array_combine( $groupIds, $groupIds ) ) );
		$manager->save( $item );
	}


	/**
	 * Processes the end of the subscription
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item
	 * @param \Aimeos\MShop\Order\Item\Iface $subscription Order item
	 */
	public function end( \Aimeos\MShop\Subscription\Item\Iface $subscription, \Aimeos\MShop\Order\Item\Iface $order )
	{
		$context = $this->context();

		$manager = \Aimeos\MShop::create( $context, 'customer' );
		$productManager = \Aimeos\MShop::create( $context, 'order/product' );

		$productItem = $productManager->get( $subscription->getOrderProductId() );
		$item = $manager->get( $subscription->getOrderItem()->getCustomerId(), ['group'] );

		if( ( $groupIds = (array) $productItem->getAttribute( 'group', 'hidden' ) ) === [] ) {
			$groupIds = $this->groupIds;
		}

		$item->setGroups( array_diff_key( $item->getGroups(), array_flip( $groupIds ) ) );
		$manager->save( $item );
	}
}
