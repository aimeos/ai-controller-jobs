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
			$typecode = trim( $list['product.property.type'] );
			$value = trim( $list['product.property.value'] );

			if( $typecode == '' || $value == '' ) {
				continue;
			}

			if( isset( $propMap[$value][$typecode] ) )
			{
				$item = $propMap[$value][$typecode];
				unset( $items[ $item->getId() ] );
			}
			else
			{
				$item = $manager->createItem( $typecode, 'product' );
			}

			$item->fromArray( $list );

			$product->addPropertyItem( $item );
		}

		$product->deletePropertyItems( $items );

		return $this->getObject()->process( $product, $data );
	}
}
