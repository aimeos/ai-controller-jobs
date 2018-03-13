<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
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
	 * @category Developer
	 */

	private $groupIds;


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context )
	{
		parent::__construct( $context );

		$config = $context->getConfig();

		/** controller/common/subscription/process/processor/cgroup/groupids
		 * List of group IDs that should be added to the customer account
		 *
		 * After customers bought a subscription, the list of group IDs will be
		 * added to their accounts. When the subscription period ends, they will
		 * be removed from the customer accounts again.
		 *
		 * @param array List of customer group IDs
		 * @since 2018.04
		 * @category Developer
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
		if( empty( $this->groupIds ) ) {
			return;
		}

		$context = $this->getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'customer' );
		$baseManager = \Aimeos\MShop\Factory::createManager( $context, 'order/base' );

		$baseItem = $baseManager->getItem( $subscription->getOrderBaseId() );
		$item = $manager->getItem( $baseItem->getCustomerId(), ['customer/group'] );

		$item->setGroups( array_unique( array_merge( $item->getGroups(), $this->groupIds ) ) );
		$manager->saveItem( $item );
	}


	/**
	 * Processes the end of the subscription
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item
	 */
	public function end( \Aimeos\MShop\Subscription\Item\Iface $subscription )
	{
		if( empty( $this->groupIds ) ) {
			return;
		}

		$context = $this->getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'customer' );
		$baseManager = \Aimeos\MShop\Factory::createManager( $context, 'order/base' );

		$baseItem = $baseManager->getItem( $subscription->getOrderBaseId() );
		$item = $manager->getItem( $baseItem->getCustomerId(), ['customer/group'] );

		$item->setGroups( array_diff( $item->getGroups(), $this->groupIds ) );
		$manager->saveItem( $item );
	}
}
