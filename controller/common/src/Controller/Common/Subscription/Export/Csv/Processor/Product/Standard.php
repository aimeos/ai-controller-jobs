<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Product;


/**
 * Product processor for subscription CSV exports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Base
	implements \Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Iface
{
	/** controller/common/subscription/export/csv/processor/product/name
	 * Name of the product processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Product\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2018.04
	 * @category Developer
	 */


	/**
	 * Returns the subscription/order related data
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $order Full order with associated items
	 * @return array Two dimensional associative list of order data representing the lines in CSV
	 */
	public function process( \Aimeos\MShop\Subscription\Item\Iface $subscription, \Aimeos\MShop\Order\Item\Base\Iface $order ) : array
	{
		$result = [];

		foreach( $order->getProducts() as $item )
		{
			if( $subscription->getOrderProductId() != $item->getId() ) {
				continue;
			}

			$data = [];
			$list = $item->toArray( true );

			foreach( $item->getAttributeItems() as $attrItem )
			{
				foreach( $attrItem->toArray( true ) as $key => $value )
				{
					if( isset( $list[$key] ) ) {
						$list[$key] .= "\n" . $value;
					} else {
						$list[$key] = $value;
					}
				}
			}

			foreach( $this->getMapping() as $pos => $key )
			{
				if( array_key_exists( $key, $list ) ) {
					$data[$pos] = $list[$key];
				} else {
					$data[$pos] = '';
				}
			}

			ksort( $data );
			$result[] = $data;
		}

		return $result;
	}
}
