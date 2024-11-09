<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2023
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
	 */

	private ?array $listTypes = null;
	private array $types = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface $object Decorated processor
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context, array $mapping,
			?\Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface $object = null )
	{
		parent::__construct( $context, $mapping, $object );

		$config = $context->config();

		/** controller/jobs/product/import/csv/text/listtypes
		 * Names of the product list types for texts that are updated or removed
		 *
		 * If you want to associate text items manually via the administration
		 * interface to products and don't want these to be touched during the
		 * import, you can specify the product list types for these texts
		 * that shouldn't be updated or removed.
		 *
		 * @param array|null List of product list type names or null for all
		 * @since 2015.05
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/separator
		 * @see controller/jobs/product/import/csv/attribute/listtypes
		 * @see controller/jobs/product/import/csv/catalog/listtypes
		 * @see controller/jobs/product/import/csv/media/listtypes
		 * @see controller/jobs/product/import/csv/price/listtypes
		 * @see controller/jobs/product/import/csv/product/listtypes
		 * @see controller/jobs/product/import/csv/supplier/listtypes
		 */
		$default = $config->get( 'controller/common/product/import/csv/processor/text/listtypes' );
		$this->listTypes = $config->get( 'controller/jobs/product/import/csv/text/listtypes', $default );

		if( $this->listTypes === null )
		{
			$this->listTypes = [];
			$manager = \Aimeos\MShop::create( $context, 'product/lists/type' );

			$search = $manager->filter()->slice( 0, 0x7fffffff );
			$search->setConditions( $search->compare( '==', 'product.lists.type.domain', 'text' ) );

			foreach( $manager->search( $search ) as $item ) {
				$this->listTypes[$item->getCode()] = $item->getCode();
			}
		}
		else
		{
			$this->listTypes = array_combine( $this->listTypes, $this->listTypes );
		}


		$manager = \Aimeos\MShop::create( $context, 'text/type' );

		$search = $manager->filter()->slice( 0, 0x7fffffff );
		$search->setConditions( $search->compare( '==', 'text.type.domain', 'product' ) );

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
		$context = $this->context();
		$manager = \Aimeos\MShop::create( $context, 'product' );
		$refManager = \Aimeos\MShop::create( $context, 'text' );

		$pos = 0;
		$listMap = [];
		$map = $this->getMappedChunk( $data, $this->getMapping() );
		$listItems = $product->getListItems( 'text', $this->listTypes, null, false );

		foreach( $listItems as $listItem )
		{
			if( ( $refItem = $listItem->getRefItem() ) !== null ) {
				$listMap[$refItem->getContent()][$refItem->getLanguageId()][$refItem->getType()][$listItem->getType()] = $listItem;
			}
		}

		foreach( $map as $list )
		{
			if( $this->checkEntry( $list ) === false ) {
				continue;
			}

			$listtype = trim( $this->val( $list, 'product.lists.type', 'default' ) );
			$listConfig = $this->getListConfig( trim( $this->val( $list, 'product.lists.config', '' ) ) );

			$language = trim( $this->val( $list, 'text.languageid', '' ) );
			$content = trim( $this->val( $list, 'text.content', '' ) );
			$type = trim( $this->val( $list, 'text.type', 'name' ) );

			$this->addType( 'product/lists/type', 'text', $listtype );
			$this->addType( 'text/type', 'product', $type );

			if( isset( $listMap[$content][$language][$type][$listtype] ) )
			{
				$listItem = $listMap[$content][$language][$type][$listtype];
				$refItem = $listItem->getRefItem();
				unset( $listItems[$listItem->getId()] );
			}
			else
			{
				$listItem = $manager->createListItem()->setType( $listtype );
				$refItem = $refManager->create()->setType( $type );
			}

			$listItem = $listItem->setPosition( $pos++ )->fromArray( $list )->setConfig( $listConfig );

			$label = mb_strcut( strip_tags( trim( $this->val( $list, 'text.content', '' ) ) ), 0, 255 );
			$refItem = $refItem->setLabel( $label )->fromArray( $list );

			$product->addListItem( 'text', $listItem, $refItem );
		}

		$product->deleteListItems( $listItems->toArray(), true );

		return $this->object()->process( $product, $data );
	}


	/**
	 * Checks if an entry can be used for updating a text item
	 *
	 * @param array $list Associative list of key/value pairs from the mapping
	 * @return bool True if valid, false if not
	 */
	protected function checkEntry( array $list ) : bool
	{
		if( $this->val( $list, 'text.content' ) === null ) {
			return false;
		}

		if( ( $type = trim( $this->val( $list, 'product.lists.type', '' ) ) ) && !isset( $this->listTypes[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'product list' );
			throw new \Aimeos\Controller\Common\Exception( $msg );
		}

		if( ( $type = trim( $this->val( $list, 'text.type', '' ) ) ) && !isset( $this->types[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'text' );
			throw new \Aimeos\Controller\Common\Exception( $msg );
		}

		return true;
	}
}
