<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2017
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
		$this->listTypes = $context->getConfig()->get( 'controller/common/catalog/import/csv/processor/text/listtypes' );
	}


	/**
	 * Saves the catalog related data to the storage
	 *
	 * @param \Aimeos\MShop\Catalog\Item\Iface $catalog Catalog item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Catalog\Item\Iface $catalog, array $data )
	{
		$listManager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'catalog/lists' );
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'text' );
		$manager->begin();

		try
		{
			$delete = $listMap = [];
			$map = $this->getMappedChunk( $data, $this->getMapping() );
			$listItems = $catalog->getListItems( 'text', $this->listTypes );

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

				$type = ( isset( $list['text.type'] ) ? $list['text.type'] : 'name' );
				$typecode = ( isset( $list['catalog.lists.type'] ) ? $list['catalog.lists.type'] : 'default' );

				if( isset( $listMap[ $list['text.content'] ][$type][$typecode] ) )
				{
					$listItem = $listMap[ $list['text.content'] ][$type][$typecode];
					$refItem = $listItem->getRefItem();
					unset( $listItems[ $listItem->getId() ] );
				}
				else
				{
					$listItem = $listManager->createItem();
					$refItem = $manager->createItem();
				}

				$list['text.typeid'] = $this->getTypeId( 'text/type', 'catalog', $type );
				$list['text.domain'] = 'catalog';

				$refItem->fromArray( $this->addItemDefaults( $list ) );
				$refItem = $manager->saveItem( $refItem );

				$list['catalog.lists.typeid'] = $this->getTypeId( 'catalog/lists/type', 'text', $typecode );
				$list['catalog.lists.parentid'] = $catalog->getId();
				$list['catalog.lists.refid'] = $refItem->getId();
				$list['catalog.lists.domain'] = 'text';

				$listItem->fromArray( $this->addListItemDefaults( $list, $pos ) );
				$listManager->saveItem( $listItem, false );
			}

			foreach( $listItems as $listItem ) {
				$delete[] = $listItem->getRefId();
			}

			$manager->deleteItems( $delete );
			$listManager->deleteItems( array_keys( $listItems ) );

			$data = $this->getObject()->process( $catalog, $data );

			$manager->commit();
		}
		catch( \Exception $e )
		{
			$manager->rollback();
			throw $e;
		}

		return $data;
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
			$list['text.label'] = mb_strcut( $list['text.content'], 0, 255 );
		}

		if( !isset( $list['text.languageid'] ) ) {
			$list['text.languageid'] = $this->getContext()->getLocale()->getLanguageId();
		}

		if( !isset( $list['text.status'] ) ) {
			$list['text.status'] = 1;
		}

		return $list;
	}


	/**
	 * Checks if an entry can be used for updating a media item
	 *
	 * @param array $list Associative list of key/value pairs from the mapping
	 * @return boolean True if valid, false if not
	 */
	protected function checkEntry( array $list )
	{
		if( !isset( $list['text.content'] ) || $list['text.content'] === '' || isset( $list['catalog.lists.type'] )
			&& $this->listTypes !== null && !in_array( $list['catalog.lists.type'], (array) $this->listTypes )
		) {
			return false;
		}

		return true;
	}
}
