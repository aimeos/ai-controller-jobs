<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Cache\Catalog;


/**
 * Category cache for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Product\Import\Csv\Cache\Base
	implements \Aimeos\Controller\Common\Product\Import\Csv\Cache\Iface
{
	/** controller/common/product/import/csv/cache/catalog/name
	 * Name of the catalog cache implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Product\Import\Csv\Cache\Catalog\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the cache class name
	 * @since 2015.10
	 * @category Developer
	 */

	private $categories = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context )
	{
		parent::__construct( $context );

		$manager = \Aimeos\MShop::create( $context, 'catalog' );
		$result = $manager->search( $manager->filter() );

		foreach( $result as $id => $item ) {
			$this->categories[$item->getCode()] = $id;
		}
	}


	/**
	 * Returns the catalog ID for the given code and type
	 *
	 * @param string $code Category code
	 * @param string|null $type Not used
	 * @return string|null Catalog ID or null if not found
	 */
	public function get( string $code, string $type = null )
	{
		if( isset( $this->categories[$code] ) ) {
			return $this->categories[$code];
		}

		$manager = \Aimeos\MShop::create( $this->getContext(), 'catalog' );

		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'catalog.code', $code ) );

		if( ( $item = $manager->search( $search )->first() ) !== null )
		{
			$this->categories[$code] = $item->getId();
			return $item->getId();
		}
	}


	/**
	 * Adds the catalog item to the cache
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Catalog object
	 */
	public function set( \Aimeos\MShop\Common\Item\Iface $item )
	{
		$this->categories[$item->getCode()] = $item->getId();
	}
}
