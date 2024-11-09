<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2023
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Text;


/**
 * Text processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Iface
{
	/** controller/common/supplier/import/csv/processor/text/name
	 * Name of the text processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Text\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2020.07
	 */

	private ?array $listTypes = null;
	private array $types = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Iface $object Decorated processor
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context, array $mapping,
		?\Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Iface $object = null )
	{
		parent::__construct( $context, $mapping, $object );

		/** controller/common/supplier/import/csv/processor/text/listtypes
		 * Names of the supplier list types for texts that are updated or removed
		 *
		 * If you want to associate text items manually via the administration
		 * interface to suppliers and don't want these to be touched during the
		 * import, you can specify the supplier list types for these texts
		 * that shouldn't be updated or removed.
		 *
		 * @param array|null List of supplier list type names or null for all
		 * @since 2020.07
		 * @see controller/common/supplier/import/csv/domains
		 * @see controller/common/supplier/import/csv/processor/attribute/listtypes
		 * @see controller/common/supplier/import/csv/processor/supplier/listtypes
		 * @see controller/common/supplier/import/csv/processor/media/listtypes
		 * @see controller/common/supplier/import/csv/processor/price/listtypes
		 * @see controller/common/supplier/import/csv/processor/supplier/listtypes
		 */
		$key = 'controller/common/supplier/import/csv/processor/text/listtypes';
		$this->listTypes = $context->config()->get( $key );

		if( $this->listTypes === null )
		{
			$this->listTypes = [];
			$manager = \Aimeos\MShop::create( $context, 'supplier/lists/type' );

			$search = $manager->filter()->slice( 0, 0x7fffffff );
			$search->setConditions( $search->compare( '==', 'supplier.lists.type.domain', 'text' ) );

			foreach( $manager->search( $search ) as $item )
			{
				$this->listTypes[$item->getCode()] = $item->getCode();
			}
		} else
		{
			$this->listTypes = array_combine( $this->listTypes, $this->listTypes );
		}


		$manager = \Aimeos\MShop::create( $context, 'text/type' );

		$search = $manager->filter()->slice( 0, 0x7fffffff );
		$search->setConditions( $search->compare( '==', 'text.type.domain', 'supplier' ) );

		foreach( $manager->search( $search ) as $item )
		{
			$this->types[$item->getCode()] = $item->getCode();
		}
	}


	/**
	 * Saves the supplier related data to the storage
	 *
	 * @param \Aimeos\MShop\Supplier\Item\Iface $supplier Supplier item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Supplier\Item\Iface $supplier, array $data ) : array
	{
		$context = $this->context();
		$manager = \Aimeos\MShop::create( $context, 'supplier' );
		$refManager = \Aimeos\MShop::create( $context, 'text' );

		$listMap = [];
		$map = $this->getMappedChunk( $data, $this->getMapping() );
		$listItems = $supplier->getListItems( 'text', $this->listTypes );

		foreach( $listItems as $listItem )
		{
			if( ( $refItem = $listItem->getRefItem() ) !== null ) {
				$listMap[$refItem->getContent()][$refItem->getType()][$listItem->getType()] = $listItem;
			}
		}

		foreach( $map as $pos => $list )
		{
			if( $this->checkEntry( $list ) === false ) {
				continue;
			}

			$type = trim( $this->val( $list, 'text.type', 'name' ) );
			$listtype = trim( $this->val( $list, 'supplier.lists.type', 'default' ) );
			$content = trim( $this->val( $list, 'text.content', '' ) );

			if( isset( $listMap[$content][$type][$listtype] ) )
			{
				$listItem = $listMap[$content][$type][$listtype];
				$refItem = $listItem->getRefItem();
				unset( $listItems[$listItem->getId()] );
			}
			else
			{
				$listItem = $manager->createListItem()->setType( $listtype );
				$refItem = $refManager->create()->setType( $type );
			}

			$listItem = $listItem->setPosition( $pos )->fromArray( $list );

			$label = mb_strcut( strip_tags( $this->val( $list, 'text.content', '' ) ), 0, 255 );
			$refItem = $refItem->setLabel( $label )->fromArray( $list );

			$supplier->addListItem( 'text', $listItem, $refItem );
		}

		$supplier->deleteListItems( $listItems->toArray(), true );

		return $this->object()->process( $supplier, $data );
	}


	/**
	 * Checks if an entry can be used for updating a media item
	 *
	 * @param array $list Associative list of key/value pairs from the mapping
	 * @return bool True if valid, false if not
	 */
	protected function checkEntry( array $list ) : bool
	{
		if( $this->val( $list, 'text.content' ) === null ) {
			return false;
		}

		if( ( $type = trim( $this->val( $list, 'supplier.lists.type', '' ) ) ) && !isset( $this->listTypes[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'supplier list' );
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
