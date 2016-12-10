<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2016
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Stock;


/**
 * Product stock processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Product\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface
{
	/** controller/common/product/import/csv/processor/stock/name
	 * Name of the stock processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Product\Import\Csv\Processor\Stock\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2015.10
	 * @category Developer
	 */

	private $cache;


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

		$this->cache = $this->getCache( 'stocktype' );
	}


	/**
	 * Saves the product stock related data to the storage
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface $product Product item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Product\Item\Iface $product, array $data )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'stock' );
		$manager->begin();

		try
		{
			$map = $this->getMappedChunk( $data );
			$items = $this->getStockItems( $product->getCode() );

			foreach( $map as $pos => $list )
			{
				if( !array_key_exists( 'stock.stocklevel', $list ) ) {
					continue;
				}

				$stockType = ( isset( $list['stock.type'] ) ? $list['stock.type'] : 'default' );

				if( !isset( $list['stock.typeid'] ) ) {
					$list['stock.typeid'] = $this->cache->get( $stockType );
				}

				if( isset( $list['stock.dateback'] ) && $list['stock.dateback'] === '' ) {
					$list['stock.dateback'] = null;
				}

				if( $list['stock.stocklevel'] === '' ) {
					$list['stock.stocklevel'] = null;
				}

				$list['stock.productcode'] = $product->getCode();

				if( ( $item = array_pop( $items ) ) === null ) {
					$item = $manager->createItem();
				}

				$item->fromArray( $list );
				$manager->saveItem( $item );
			}

			$manager->deleteItems( array_keys( $items ) );

			$remaining = $this->getObject()->process( $product, $data );

			$manager->commit();
		}
		catch( \Exception $e )
		{
			$manager->rollback();
			throw $e;
		}

		return $remaining;
	}


	/**
	 * Returns the stock items for the given product code
	 *
	 * @param string $code Unique product code
	 * @return \Aimeos\MShop\Stock\Item\Iface[] Associative list of stock items
	 */
	protected function getStockItems( $code )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'stock' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'stock.productcoce', $code ) );

		return $manager->searchItems( $search );
	}
}
