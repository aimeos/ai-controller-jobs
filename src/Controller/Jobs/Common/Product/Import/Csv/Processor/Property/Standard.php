<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Property;


/**
 * Product property processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface
{
	/** controller/jobs/product/import/csv/processor/property/name
	 * Name of the property processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Property\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2015.10
	 */


	/**
	 * Saves the product property related data to the storage
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface $product Product item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Product\Item\Iface $product, array $data ) : array
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'product' );

		$propMap = [];
		$items = $product->getPropertyItems( null, false );
		$map = $this->getMappedChunk( $data, $this->getMapping() );

		foreach( $items as $item ) {
			$propMap[$item->getValue()][$item->getType()] = $item;
		}

		foreach( $map as $list )
		{
			if( ( $value = $this->val( $list, 'product.property.value' ) ) === null ) {
				continue;
			}

			$type = $this->val( $list, 'product.property.type' );
			$this->addType( 'product/property/type', 'product', $type );

			if( isset( $propMap[$value][$type] ) )
			{
				$item = $propMap[$value][$type];
				$items->remove( $item->getId() );
			}
			else
			{
				$item = $manager->createPropertyItem()->setType( $type );
			}

			$product->addPropertyItem( $item->fromArray( $list ) );
		}

		$product->deletePropertyItems( $items->toArray() );

		return $this->object()->process( $product, $data );
	}
}
