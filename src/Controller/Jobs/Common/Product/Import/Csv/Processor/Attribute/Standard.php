<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2024
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Attribute;


/**
 * Attribute processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface
{
	/** controller/jobs/product/import/csv/processor/attribute/name
	 * Name of the attribute processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Attribute\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2015.10
	 */

	private \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Cache\Attribute\Standard $cache;
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

		/** controller/jobs/product/import/csv/attribute/listtypes
		 * Names of the product list types for attributes that are updated or removed
		 *
		 * If you want to associate attribute items manually via the administration
		 * interface to products and don't want these to be touched during the
		 * import, you can specify the product list types for these attributes
		 * that shouldn't be updated or removed.
		 *
		 * @param array|null List of product list type names or null for all
		 * @since 2015.05
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/separator
		 * @see controller/jobs/product/import/csv/catalog/listtypes
		 * @see controller/jobs/product/import/csv/media/listtypes
		 * @see controller/jobs/product/import/csv/price/listtypes
		 * @see controller/jobs/product/import/csv/product/listtypes
		 * @see controller/jobs/product/import/csv/supplier/listtypes
		 * @see controller/jobs/product/import/csv/text/listtypes
		 */
		$default = $config->get( 'controller/jobs/product/import/csv/processor/attribute/listtypes' );
		$this->listTypes = $config->get( 'controller/jobs/product/import/csv/attribute/listtypes', $default );

		if( $this->listTypes === null )
		{
			$this->listTypes = [];
			$manager = \Aimeos\MShop::create( $context, 'product/lists/type' );

			$search = $manager->filter()->slice( 0, 0x7fffffff );
			$search->setConditions( $search->compare( '==', 'product.lists.type.domain', 'attribute' ) );

			foreach( $manager->search( $search ) as $item ) {
				$this->listTypes[$item->getCode()] = $item->getCode();
			}
		}
		else
		{
			$this->listTypes = array_combine( $this->listTypes, $this->listTypes );
		}


		$manager = \Aimeos\MShop::create( $context, 'attribute/type' );

		$search = $manager->filter()->slice( 0, 0x7fffffff );
		$search->setConditions( $search->compare( '==', 'attribute.type.domain', 'product' ) );

		foreach( $manager->search( $search ) as $item ) {
			$this->types[$item->getCode()] = $item->getCode();
		}


		$this->cache = $this->getCache( 'attribute' );
	}


	/**
	 * Saves the attribute related data to the storage
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface $product Product item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Product\Item\Iface $product, array $data ) : array
	{
		$context = $this->context();
		$manager = \Aimeos\MShop::create( $context, 'product' );

		/** controller/jobs/product/import/csv/separator
		 * Separator between multiple values in one CSV field
		 *
		 * In Aimeos, fields of some CSV columns can contain multiple values which
		 * are split and imported as separate values. This setting configures the
		 * character that is used for splitting the values and by default, a new
		 * line character (\n) is used.
		 *
		 * @param string Unique character or characters in field values
		 * @since 2015.05
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/attribute/listtypes
		 * @see controller/jobs/product/import/csv/media/listtypes
		 * @see controller/jobs/product/import/csv/price/listtypes
		 * @see controller/jobs/product/import/csv/product/listtypes
		 * @see controller/jobs/product/import/csv/supplier/listtypes
		 * @see controller/jobs/product/import/csv/text/listtypes
		 */
		$separator = $context->config()->get( 'controller/jobs/product/import/csv/separator', "\n" );

		$pos = 0;
		$listMap = [];
		$map = $this->getMappedChunk( $data, $this->getMapping() );
		$listItems = $product->getListItems( 'attribute', $this->listTypes, null, false );

		foreach( $listItems as $listItem )
		{
			if( $refItem = $listItem->getRefItem() ) {
				$listMap[$refItem->getCode()][$refItem->getType()][$listItem->getType()] = $listItem;
			}
		}

		foreach( $map as $list )
		{
			if( $this->checkEntry( $list ) === false ) {
				continue;
			}

			$attrType = trim( $this->val( $list, 'attribute.type', '' ) );
			$listtype = trim( $this->val( $list, 'product.lists.type', 'default' ) );
			$this->addType( 'product/lists/type', 'attribute', $listtype );

			$listConfig = $this->getListConfig( trim( $this->val( $list, 'product.lists.config', '' ) ) );
			$codes = explode( $separator, trim( $this->val( $list, 'attribute.code', '' ) ) );
			unset( $list['attribute.code'], $list['product.lists.config'] );

			foreach( $codes as $code )
			{
				$code = trim( $code );

				$attrItem = $this->getAttributeItem( $code, $attrType );
				$attrItem = $attrItem->fromArray( $list )->setCode( $code );

				$listItem = $listMap[$code][$attrType][$listtype] ?? $manager->createListItem();
				$listItem = $listItem->setPosition( $pos )->fromArray( $list )->setConfig( $listConfig );

				$product->addListItem( 'attribute', $listItem->setType( $listtype ), $attrItem );
				unset( $listItems[$listItem->getId()] );
			}
		}

		$product->deleteListItems( $listItems );

		return $this->object()->process( $product, $data );
	}


	/**
	 * Checks if the entry from the mapped data is valid
	 *
	 * @param array $list Associative list of key/value pairs from the mapped data
	 * @return bool True if the entry is valid, false if not
	 */
	protected function checkEntry( array $list ) : bool
	{
		if( $this->val( $list, 'attribute.code' ) === null ) {
			return false;
		}

		if( ( $type = trim( $this->val( $list, 'product.lists.type', 'default' ) ) ) && !isset( $this->listTypes[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'product list' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		if( ( $type = trim( $this->val( $list, 'attribute.type', '' ) ) ) && !isset( $this->types[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'attribute' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return true;
	}


	/**
	 * Returns the attribute item for the given code and type
	 *
	 * @param string $code Attribute code
	 * @param string $type Attribute type
	 * @return \Aimeos\MShop\Attribute\Item\Iface Attribute item object
	 */
	protected function getAttributeItem( string $code, string $type ) : \Aimeos\MShop\Attribute\Item\Iface
	{
		if( ( $item = $this->cache->get( $code, $type ) ) === null )
		{
			$manager = \Aimeos\MShop::create( $this->context(), 'attribute' );

			$item = $manager->create();
			$item->setType( $type );
			$item->setDomain( 'product' );
			$item->setLabel( $code );
			$item->setCode( $code );
			$item->setStatus( 1 );

			$item = $manager->save( $item );

			$this->cache->set( $item );
		}

		return $item;
	}
}
