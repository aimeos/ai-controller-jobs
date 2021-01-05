<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Cache\Product;


/**
 * Product cache for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Product\Import\Csv\Cache\Base
	implements \Aimeos\Controller\Common\Product\Import\Csv\Cache\Iface
{
	/** controller/common/product/import/csv/cache/product/name
	 * Name of the product cache implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Product\Import\Csv\Cache\Product\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the cache class name
	 * @since 2015.10
	 * @category Developer
	 */

	private $prodmap = [];


	/**
	 * Returns the product ID for the given code
	 *
	 * @param string $code Product code
	 * @param string|null $type Attribute type
	 * @return string|null Product ID or null if not found
	 */
	public function get( string $code, string $type = null )
	{
		if( isset( $this->prodmap[$code] ) ) {
			return $this->prodmap[$code];
		}

		$manager = \Aimeos\MShop::create( $this->getContext(), 'product' );

		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'product.code', $code ) );

		if( ( $item = $manager->search( $search )->first() ) !== null )
		{
			$this->prodmap[$code] = $item->getId();
			return $this->prodmap[$code];
		}
	}


	/**
	 * Adds the product ID to the cache
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Product object
	 */
	public function set( \Aimeos\MShop\Common\Item\Iface $item )
	{
		$this->prodmap[$item->getCode()] = $item->getId();
	}
}
