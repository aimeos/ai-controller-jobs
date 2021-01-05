<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Cache\Supplier;


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
	/** controller/common/product/import/csv/cache/supplier/name
	 * Name of the supplier cache implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Product\Import\Csv\Cache\Supplier\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the cache class name
	 * @since 2015.10
	 * @category Developer
	 */

	private $suppliers = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context )
	{
		parent::__construct( $context );

		$manager = \Aimeos\MShop::create( $context, 'supplier' );
		$result = $manager->search( $manager->filter() );

		foreach( $result as $id => $item )
		{
			$this->suppliers[$item->getCode()] = $id;
		}
	}


	/**
	 * Returns the supplier ID for the given code and type
	 *
	 * @param string $code Category code
	 * @param string|null $type Not used
	 * @return string|null Supplier ID or null if not found
	 */
	public function get( string $code, string $type = null )
	{
		if( isset( $this->suppliers[$code] ) )
		{
			return $this->suppliers[$code];
		}

		$manager = \Aimeos\MShop::create( $this->getContext(), 'supplier' );

		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'supplier.code', $code ) );


		if( ( $item = $manager->search( $search )->first() ) !== null )
		{
			$this->suppliers[$code] = $item->getId();
			return $item->getId();
		}
	}


	/**
	 * Adds the supplier item to the cache
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Supplier object
	 */
	public function set( \Aimeos\MShop\Common\Item\Iface $item )
	{
		$this->suppliers[$item->getCode()] = $item->getId();
	}
}
