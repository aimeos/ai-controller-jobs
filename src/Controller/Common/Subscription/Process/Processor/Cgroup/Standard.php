<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2022
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Subscription\Process\Processor\Cgroup;


/**
 * Customer group processor for subscriptions
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Subscription\Process\Processor\Base
	implements \Aimeos\Controller\Common\Subscription\Process\Processor\Iface
{
	/** controller/common/subscription/export/csv/processor/cgroup/name
	 * Name of the customer group processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Subscription\Process\Processor\Cgroup\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2018.04
	 */

	private $groupIds;


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context )
	{
		parent::__construct( $context );

		$config = $context->config();

		/** controller/common/subscription/process/processor/cgroup/groupids
		 * List of group IDs that should be added to the customer account
		 *
		 * After customers bought a subscription, the list of group IDs will be
		 * added to their accounts. When the subscription period ends, they will
		 * be removed from the customer accounts again.
		 *
		 * @param array List of customer group IDs
		 * @since 2018.04
		 */
		$this->groupIds = (array) $config->get( 'controller/common/subscription/process/processor/cgroup/groupids', [] );
	}


	/**
	 * Processes the initial subscription
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item
	 */
	public function begin( \Aimeos\MShop\Subscription\Item\Iface $subscription )
	{
		$context = $this->context();

		$manager = \Aimeos\MShop::create( $context, 'customer' );
		$productManager = \Aimeos\MShop::create( $context, 'order/product' );

		$productItem = $productManager->get( $subscription->getOrderProductId() );
		$item = $manager->get( $subscription->getOrderItem()->getCustomerId(), ['customer/group'] );

		if( ( $groupIds = (array) $productItem->getAttribute( 'customer/group', 'hidden' ) ) === [] ) {
			$groupIds = $this->groupIds;
		}

		$item->setGroups( array_unique( array_merge( $item->getGroups(), $groupIds ) ) );
		$manager->save( $item );
	}


	/**
	 * Processes the end of the subscription
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item
	 */
	public function end( \Aimeos\MShop\Subscription\Item\Iface $subscription )
	{
		$context = $this->context();

		$manager = \Aimeos\MShop::create( $context, 'customer' );
		$productManager = \Aimeos\MShop::create( $context, 'order/product' );

		$productItem = $productManager->get( $subscription->getOrderProductId() );
		$item = $manager->get( $subscription->getOrderItem()->getCustomerId(), ['customer/group'] );

		if( ( $groupIds = (array) $productItem->getAttribute( 'customer/group', 'hidden' ) ) === [] ) {
			$groupIds = $this->groupIds;
		}

		$item->setGroups( array_diff( $item->getGroups(), $groupIds ) );
		$manager->save( $item );
	}
}
