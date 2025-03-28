<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Price;


/**
 * Price processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface
{
	/** controller/jobs/product/import/csv/processor/price/name
	 * Name of the price processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Price\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2015.10
	 */

	private ?array $listTypes = null;
	private array $types = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface $object Decorated processor
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context, array $mapping,
			?\Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface $object = null )
	{
		parent::__construct( $context, $mapping, $object );

		$config = $context->config();

		/** controller/jobs/product/import/csv/price/listtypes
		 * Names of the product list types for prices that are updated or removed
		 *
		 * If you want to associate price items manually via the administration
		 * interface to products and don't want these to be touched during the
		 * import, you can specify the product list types for these prices
		 * that shouldn't be updated or removed.
		 *
		 * @param array|null List of product list type names or null for all
		 * @since 2015.05
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/separator
		 * @see controller/jobs/product/import/csv/attribute/listtypes
		 * @see controller/jobs/product/import/csv/catalog/listtypes
		 * @see controller/jobs/product/import/csv/media/listtypes
		 * @see controller/jobs/product/import/csv/product/listtypes
		 * @see controller/jobs/product/import/csv/supplier/listtypes
		 * @see controller/jobs/product/import/csv/text/listtypes
		 */
		$default = $config->get( 'controller/jobs/product/import/csv/processor/price/listtypes' );
		$this->listTypes = $config->get( 'controller/jobs/product/import/csv/price/listtypes', $default );

		if( $this->listTypes === null )
		{
			$this->listTypes = [];
			$manager = \Aimeos\MShop::create( $context, 'product/lists/type' );
			$search = $manager->filter()->slice( 0, 0x7fffffff );

			foreach( $manager->search( $search ) as $item ) {
				$this->listTypes[$item->getCode()] = $item->getCode();
			}
		}
		else
		{
			$this->listTypes = array_combine( $this->listTypes, $this->listTypes );
		}


		$manager = \Aimeos\MShop::create( $context, 'price/type' );
		$search = $manager->filter()->slice( 0, 0x7fffffff );

		foreach( $manager->search( $search ) as $item ) {
			$this->types[$item->getCode()] = $item->getCode();
		}
	}


	/**
	 * Saves the product related data to the storage
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface $product Product item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Product\Item\Iface $product, array $data ) : array
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'product' );
		$refManager = \Aimeos\MShop::create( $this->context(), 'price' );

		$listMap = [];
		$map = $this->getMappedChunk( $data, $this->getMapping() );
		$listItems = $product->getListItems( 'price', $this->listTypes, null, false );

		foreach( $listItems as $listItem )
		{
			if( ( $refItem = $listItem->getRefItem() ) !== null ) {
				$listMap[$refItem->getType()][$listItem->getType()][] = $listItem;
			}
		}

		foreach( $map as $pos => $list )
		{
			if( $this->checkEntry( $list ) === false ) {
				continue;
			}

			$type = trim( $this->val( $list, 'price.type', 'default' ) );
			$listtype = trim( $this->val( $list, 'product.lists.type', 'default' ) );
			$listConfig = $this->getListConfig( trim( $this->val( $list, 'product.lists.config', '' ) ) );

			$this->addType( 'product/lists/type', 'price', $listtype );
			$this->addType( 'price/type', 'product', $type );

			if( isset( $listMap[$type][$listtype] ) && !empty( $listMap[$type][$listtype] ) ) {
				$listItem = array_shift( $listMap[$type][$listtype] );
			} else {
				$listItem = $manager->createListItem();
			}

			if( ( $refItem = $listItem->getRefItem() ) === null ) {
				$refItem = $refManager->create();
			}

			$listItem = $listItem->setType( $listtype )->setPosition( $pos )->fromArray( $list )->setConfig( $listConfig );

			$label = $this->val( $list, 'price.currencyid', '' ) . ' ' . $this->val( $list, 'price.value', '' );
			$refItem = $refItem->setType( $type )->setLabel( $label )->fromArray( $list );

			$product->addListItem( 'price', $listItem, $refItem );

			unset( $listItems[$listItem->getId()] );
		}

		$product->deleteListItems( $listItems->toArray(), true );

		return $this->object()->process( $product, $data );
	}


	/**
	 * Checks if an entry can be used for updating a price item
	 *
	 * @param array $list Associative list of key/value pairs from the mapping
	 * @return bool True if valid, false if not
	 */
	protected function checkEntry( array $list ) : bool
	{
		if( $this->val( $list, 'price.value' ) === null && $this->val( $list, 'price.currencyid' ) === null ) {
			return false;
		}

		if( ( $type = trim( $this->val( $list, 'product.lists.type', 'default' ) ) ) && !isset( $this->listTypes[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'product list' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		if( ( $type = trim( $this->val( $list, 'price.type', 'default' ) ) ) && !isset( $this->types[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'price' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return true;
	}
}
