<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2018
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Cache\Stocktype;


/**
 * Stock type cache for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Product\Import\Csv\Cache\Base
	implements \Aimeos\Controller\Common\Product\Import\Csv\Cache\Iface
{
	/** controller/common/product/import/csv/cache/stocktype/name
	 * Name of the stock type cache implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Product\Import\Csv\Cache\Stocktype\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the cache class name
	 * @since 2017.01
	 * @category Developer
	 */

	private $types = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context )
	{
		parent::__construct( $context );

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'stock/type' );
		$search = $manager->createSearch();
		$search->setSlice( 0, 0x7fffffff );

		foreach( $manager->searchItems( $search ) as $id => $item ) {
			$this->types[ $item->getCode() ] = $id;
		}
	}


	/**
	 * Returns the type ID for the given code
	 *
	 * @param string $code Stock type
	 * @param string|null $type Attribute type
	 * @return string|null Stock type ID or null if not found
	 */
	public function get( $code, $type = null )
	{
		if( isset( $this->types[$code] ) ) {
			return $this->types[$code];
		}
	}


	/**
	 * Adds the type ID to the cache
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Stock type object
	 */
	public function set( \Aimeos\MShop\Common\Item\Iface $item )
	{
		$this->types[ $item->getCode() ] = $item->getId();
	}
}