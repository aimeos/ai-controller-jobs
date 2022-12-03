<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2022
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Subscription\Export\Csv\Processor;


/**
 * Common interface for all CSV export processors
 *
 * @package Controller
 * @subpackage Common
 */
interface Iface
{
	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context, array $mapping );


	/**
	 * Returns the order related data
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item with associated order
	 * @return array Two dimensional associative list of order data representing the lines in CSV
	 */
	public function process( \Aimeos\MShop\Subscription\Item\Iface $subscription ) : array;
}
