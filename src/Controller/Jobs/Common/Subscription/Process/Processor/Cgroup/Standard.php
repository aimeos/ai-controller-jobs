<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2026
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

	private array $allowed;
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
		 * The configured groups are used if the ordered subscription product
		 * has no hidden "group" attribute.
		 *
		 * @param array List of customer group IDs
		 * @since 2018.04
		 * @see controller/jobs/subscription/process/processor/cgroup/allowed
		 */
		$this->groupIds = (array) $config->get( 'controller/jobs/subscription/process/processor/cgroup/groupids', [] );

		/** controller/jobs/subscription/process/processor/cgroup/allowed
		 * List of group IDs that subscription products are allowed to grant
		 *
		 * Subscription products can contain hidden attributes of type "group"
		 * whose codes are the IDs of the customer groups that should be added
		 * to the customer account. Only the group IDs listed in this setting
		 * are granted or removed, all others are ignored and logged. Editors
		 * can change products and attributes, so privileged groups like "admin"
		 * must never be part of this list.
		 *
		 * @param array List of customer group IDs
		 * @since 2026.10
		 * @see controller/jobs/subscription/process/processor/cgroup/groupids
		 */
		$this->allowed = (array) $config->get( 'controller/jobs/subscription/process/processor/cgroup/allowed', [] );
	}


	/**
	 * Processes the initial subscription
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item
	 * @param \Aimeos\MShop\Order\Item\Iface $order Order item
	 */
	public function begin( \Aimeos\MShop\Subscription\Item\Iface $subscription, \Aimeos\MShop\Order\Item\Iface $order ) : void
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'customer' );
		$item = $manager->get( $subscription->getOrderItem()->getCustomerId(), ['group'] );
		$groupIds = $this->grants( $subscription );

		// @phpstan-ignore argument.type
		$item->setGroups( array_unique( array_merge( $item->getGroups(), $groupIds ) ) );
		$manager->save( $item );
	}


	/**
	 * Processes the end of the subscription
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item
	 * @param \Aimeos\MShop\Order\Item\Iface $order Order item
	 */
	public function end( \Aimeos\MShop\Subscription\Item\Iface $subscription, \Aimeos\MShop\Order\Item\Iface $order ) : void
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'customer' );
		$item = $manager->get( $subscription->getOrderItem()->getCustomerId(), ['group'] );
		$groupIds = $this->grants( $subscription );

		// @phpstan-ignore argument.type
		$item->setGroups( array_diff( $item->getGroups(), $groupIds ) );
		$manager->save( $item );
	}


	/**
	 * Returns the customer group IDs granted by the ordered subscription product
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item
	 * @return array List of customer group IDs
	 */
	protected function grants( \Aimeos\MShop\Subscription\Item\Iface $subscription ) : array
	{
		$context = $this->context();
		$productManager = \Aimeos\MShop::create( $context, 'order/product' );

		// @phpstan-ignore argument.type
		$productItem = $productManager->get( $subscription->getOrderProductId() );

		if( ( $groupIds = (array) $productItem->getAttribute( 'group', 'hidden' ) ) === [] ) {
			return $this->groupIds;
		}

		if( $denied = array_diff( $groupIds, $this->allowed ) )
		{
			$msg = sprintf( 'Group IDs "%1$s" of subscription ID "%2$s" are not allowed', join( '", "', $denied ), $subscription->getId() );
			$context->logger()->warning( $msg, 'subscription' );
		}

		return array_intersect( $groupIds, $this->allowed );
	}
}
