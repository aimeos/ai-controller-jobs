<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2018
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Property;


/**
 * Product property processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Product\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface
{
	/** controller/common/product/import/csv/processor/property/name
	 * Name of the property processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Product\Import\Csv\Processor\Property\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2015.10
	 * @category Developer
	 */

	private $types = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface $object Decorated processor
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context, array $mapping,
		\Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface $object = null )
	{
		parent::__construct( $context, $mapping, $object );

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'product/property/type' );
		$search = $manager->createSearch()->setSlice( 0, 0x7fffffff );

		foreach( $manager->searchItems( $search ) as $item ) {
			$this->types[$item->getCode()] = $item->getCode();
		}
	}


	/**
	 * Saves the product property related data to the storage
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface $product Product item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Product\Item\Iface $product, array $data )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'product/property' );

		$propMap = [];
		$items = $product->getPropertyItems( null, false );
		$map = $this->getMappedChunk( $data, $this->getMapping() );

		foreach( $items as $item ) {
			$propMap[ $item->getValue() ][ $item->getType() ] = $item;
		}

		foreach( $map as $list )
		{
			if( ( $value = $this->getValue( $list, 'product.property.value' ) ) === null ) {
				continue;
			}

			if( ( $type = $this->getValue( $list, 'product.property.type' ) ) && !isset( $this->types[$type] ) )
			{
				$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'product property' );
				throw new \Aimeos\Controller\Common\Exception( $msg );
			}

			if( isset( $propMap[$value][$type] ) )
			{
				$item = $propMap[$value][$type];
				unset( $items[ $item->getId() ] );
			}
			else
			{
				$item = $manager->createItem()->setType( $type );
			}

			$product->addPropertyItem( $item->fromArray( $list ) );
		}

		$product->deletePropertyItems( $items );

		return $this->getObject()->process( $product, $data );
	}
}
