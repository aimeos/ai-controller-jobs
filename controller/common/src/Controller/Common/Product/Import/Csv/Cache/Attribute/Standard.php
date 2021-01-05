<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Cache\Attribute;


/**
 * Attribute cache for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Product\Import\Csv\Cache\Base
	implements \Aimeos\Controller\Common\Product\Import\Csv\Cache\Iface
{
	/** controller/common/product/import/csv/cache/attribute/name
	 * Name of the attribute cache implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Product\Import\Csv\Cache\Attribute\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the cache class name
	 * @since 2015.10
	 * @category Developer
	 */

	private $attributes = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context )
	{
		parent::__construct( $context );

		$manager = \Aimeos\MShop::create( $context, 'attribute' );
		$result = $manager->search( $manager->filter() );

		foreach( $result as $id => $item ) {
			$this->attributes[$item->getCode()][$item->getType()] = $item;
		}
	}


	/**
	 * Returns the attribute item for the given code and type
	 *
	 * @param string $code Attribute code
	 * @param string|null $type Attribute type
	 * @return \Aimeos\MShop\Attribute\Item\Iface|null Attribute object or null if not found
	 */
	public function get( string $code, string $type = null )
	{
		if( isset( $this->attributes[$code] ) && isset( $this->attributes[$code][$type] ) ) {
			return $this->attributes[$code][$type];
		}

		$manager = \Aimeos\MShop::create( $this->getContext(), 'attribute' );

		$search = $manager->filter();
		$expr = array(
			$search->compare( '==', 'attribute.code', $code ),
			$search->compare( '==', 'attribute.type', $type ),
		);
		$search->setConditions( $search->and( $expr ) );

		if( ( $item = $manager->search( $search )->first() ) !== null )
		{
			$this->attributes[$code][$type] = $item;
			return $item;
		}
	}


	/**
	 * Adds the attribute item to the cache
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Attribute object
	 */
	public function set( \Aimeos\MShop\Common\Item\Iface $item )
	{
		$code = $item->getCode();

		if( !isset( $this->attributes[$code] ) || !is_array( $this->attributes[$code] ) ) {
			$this->attributes[$code] = [];
		}

		$this->attributes[$code][$item->getType()] = $item;
	}
}
