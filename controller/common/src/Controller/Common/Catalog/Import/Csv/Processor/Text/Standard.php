<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Text;


/**
 * Text processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Iface
{
	/** controller/common/catalog/import/csv/processor/text/name
	 * Name of the text processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Text\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2018.04
	 * @category Developer
	 */

	private $listTypes;
	private $types;


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Iface $object Decorated processor
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context, array $mapping,
			\Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Iface $object = null )
	{
		parent::__construct( $context, $mapping, $object );

		/** controller/common/catalog/import/csv/processor/text/listtypes
		 * Names of the catalog list types for texts that are updated or removed
		 *
		 * If you want to associate text items manually via the administration
		 * interface to catalogs and don't want these to be touched during the
		 * import, you can specify the catalog list types for these texts
		 * that shouldn't be updated or removed.
		 *
		 * @param array|null List of catalog list type names or null for all
		 * @since 2018.04
		 * @category Developer
		 * @category User
		 * @see controller/common/catalog/import/csv/domains
		 * @see controller/common/catalog/import/csv/processor/attribute/listtypes
		 * @see controller/common/catalog/import/csv/processor/catalog/listtypes
		 * @see controller/common/catalog/import/csv/processor/media/listtypes
		 * @see controller/common/catalog/import/csv/processor/price/listtypes
		 * @see controller/common/catalog/import/csv/processor/catalog/listtypes
		 */
		$key = 'controller/common/catalog/import/csv/processor/text/listtypes';
		$this->listTypes = $context->getConfig()->get( $key );

		if( $this->listTypes === null )
		{
			$this->listTypes = [];
			$manager = \Aimeos\MShop::create( $context, 'catalog/lists/type' );

			$search = $manager->filter()->slice( 0, 0x7fffffff );
			$search->setConditions( $search->compare( '==', 'catalog.lists.type.domain', 'text' ) );

			foreach( $manager->search( $search ) as $item ) {
				$this->listTypes[$item->getCode()] = $item->getCode();
			}
		}
		else
		{
			$this->listTypes = array_flip( $this->listTypes );
		}


		$manager = \Aimeos\MShop::create( $context, 'text/type' );

		$search = $manager->filter()->slice( 0, 0x7fffffff );
		$search->setConditions( $search->compare( '==', 'text.type.domain', 'catalog' ) );

		foreach( $manager->search( $search ) as $item ) {
			$this->types[$item->getCode()] = $item->getCode();
		}
	}


	/**
	 * Saves the catalog related data to the storage
	 *
	 * @param \Aimeos\MShop\Catalog\Item\Iface $catalog Catalog item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Catalog\Item\Iface $catalog, array $data ) : array
	{
		$listManager = \Aimeos\MShop::create( $this->getContext(), 'catalog/lists' );
		$manager = \Aimeos\MShop::create( $this->getContext(), 'text' );

		$listMap = [];
		$map = $this->getMappedChunk( $data, $this->getMapping() );
		$listItems = $catalog->getListItems( 'text', $this->listTypes );

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

			$type = $this->getValue( $list, 'text.type', 'name' );
			$listtype = $this->getValue( $list, 'catalog.lists.type', 'default' );
			$content = $this->getValue( $list, 'text.content', '' );

			if( isset( $listMap[$content][$type][$listtype] ) )
			{
				$listItem = $listMap[$content][$type][$listtype];
				$refItem = $listItem->getRefItem();
				unset( $listItems[$listItem->getId()] );
			}
			else
			{
				$listItem = $listManager->create()->setType( $listtype );
				$refItem = $manager->create()->setType( $type );
			}

			$listItem = $listItem->setPosition( $pos )->fromArray( $list );

			$label = mb_strcut( $this->getValue( $list, 'text.content', '' ), 0, 255 );
			$refItem = $refItem->setLabel( $label )->fromArray( $list );

			$catalog->addListItem( 'text', $listItem, $refItem );
		}

		$catalog->deleteListItems( $listItems->toArray(), true );

		return $this->getObject()->process( $catalog, $data );
	}


	/**
	 * Checks if an entry can be used for updating a media item
	 *
	 * @param array $list Associative list of key/value pairs from the mapping
	 * @return bool True if valid, false if not
	 */
	protected function checkEntry( array $list ) : bool
	{
		if( $this->getValue( $list, 'text.content' ) === null ) {
			return false;
		}

		if( ( $type = $this->getValue( $list, 'catalog.lists.type' ) ) && !isset( $this->listTypes[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'catalog list' );
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
