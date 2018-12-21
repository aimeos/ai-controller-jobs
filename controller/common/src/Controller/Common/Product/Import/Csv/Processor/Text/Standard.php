<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2018
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Text;


/**
 * Text processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Product\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface
{
	/** controller/common/product/import/csv/processor/text/name
	 * Name of the text processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Product\Import\Csv\Processor\Text\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2015.10
	 * @category Developer
	 */

	private $listTypes;
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

		/** controller/common/product/import/csv/processor/text/listtypes
		 * Names of the product list types for texts that are updated or removed
		 *
		 * If you want to associate text items manually via the administration
		 * interface to products and don't want these to be touched during the
		 * import, you can specify the product list types for these texts
		 * that shouldn't be updated or removed.
		 *
		 * @param array|null List of product list type names or null for all
		 * @since 2015.05
		 * @category Developer
		 * @category User
		 * @see controller/common/product/import/csv/domains
		 * @see controller/common/product/import/csv/processor/attribute/listtypes
		 * @see controller/common/product/import/csv/processor/catalog/listtypes
		 * @see controller/common/product/import/csv/processor/media/listtypes
		 * @see controller/common/product/import/csv/processor/price/listtypes
		 * @see controller/common/product/import/csv/processor/product/listtypes
		 */
		$key = 'controller/common/product/import/csv/processor/text/listtypes';
		$this->listTypes = $context->getConfig()->get( $key );

		if( $this->listTypes === null )
		{
			$this->listTypes = [];
			$manager = \Aimeos\MShop\Factory::createManager( $context, 'product/lists/type' );

			$search = $manager->createSearch()->setSlice( 0, 0x7fffffff );
			$search->setConditions( $search->compare( '==', 'product.lists.type.domain', 'text' ) );

			foreach( $manager->searchItems( $search ) as $item ) {
				$this->listTypes[$item->getCode()] = $item->getCode();
			}
		}
		else
		{
			$this->listTypes = array_flip( $this->listTypes );
		}


		$manager = \Aimeos\MShop\Factory::createManager( $context, 'text/type' );

		$search = $manager->createSearch()->setSlice( 0, 0x7fffffff );
		$search->setConditions( $search->compare( '==', 'text.type.domain', 'product' ) );

		foreach( $manager->searchItems( $search ) as $item ) {
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
	public function process( \Aimeos\MShop\Product\Item\Iface $product, array $data )
	{
		$listManager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'product/lists' );
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'text' );

		$listMap = [];
		$map = $this->getMappedChunk( $data, $this->getMapping() );
		$listItems = $product->getListItems( 'text', $this->listTypes );

		foreach( $listItems as $listItem )
		{
			if( ( $refItem = $listItem->getRefItem() ) !== null ) {
				$listMap[ $refItem->getContent() ][ $refItem->getType() ][ $listItem->getType() ] = $listItem;
			}
		}

		foreach( $map as $pos => $list )
		{
			if( $this->checkEntry( $list ) === false ) {
				continue;
			}

			$type = $this->getValue( $list, 'text.type', 'name' );
			$listtype = $this->getValue( $list, 'product.lists.type', 'default' );
			$content = $this->getValue( $list, 'text.content', '' );

			if( isset( $listMap[$content][$type][$listtype] ) )
			{
				$listItem = $listMap[$content][$type][$listtype];
				$refItem = $listItem->getRefItem();
				unset( $listItems[ $listItem->getId() ] );
			}
			else
			{
				$listItem = $listManager->createItem()->setType( $listtype );
				$refItem = $manager->createItem()->setType( $type );
			}

			$refItem->fromArray( $this->addItemDefaults( $list ) );
			$listItem->fromArray( $this->addListItemDefaults( $list, $pos ) );

			$product->addListItem( 'text', $listItem, $refItem );
		}

		$product->deleteListItems( $listItems, true );

		return $this->getObject()->process( $product, $data );
	}


	/**
	 * Adds the text item default values and returns the resulting array
	 *
	 * @param array $list Associative list of domain item keys and their values, e.g. "text.status" => 1
	 * @return array Given associative list enriched by default values if they were not already set
	 */
	protected function addItemDefaults( array $list )
	{
		if( !isset( $list['text.label'] ) ) {
			$list['text.label'] = mb_strcut( trim( $list['text.content'] ), 0, 255 );
		}

		if( !isset( $list['text.status'] ) ) {
			$list['text.status'] = 1;
		}

		return $list;
	}


	/**
	 * Checks if an entry can be used for updating a text item
	 *
	 * @param array $list Associative list of key/value pairs from the mapping
	 * @return boolean True if valid, false if not
	 */
	protected function checkEntry( array $list )
	{
		if( $this->getValue( $list, 'text.content' ) === null ) {
			return false;
		}

		if( ( $type = $this->getValue( $list, 'product.lists.type' ) ) && !isset( $this->listTypes[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'product list' );
			throw new \Aimeos\Controller\Common\Exception( $msg );
		}

		if( ( $type = $this->getValue( $list, 'text.type' ) ) && !isset( $this->types[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'text' );
			throw new \Aimeos\Controller\Common\Exception( $msg );
		}

		return true;
	}
}
