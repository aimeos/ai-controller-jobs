<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Media;


/**
 * Media processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Iface
{
	/** controller/common/catalog/import/csv/processor/media/name
	 * Name of the media processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Media\Myname".
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

		/** controller/common/catalog/import/csv/processor/media/listtypes
		 * Names of the catalog list types for media that are updated or removed
		 *
		 * If you want to associate media items manually via the administration
		 * interface to catalogs and don't want these to be touched during the
		 * import, you can specify the catalog list types for these media
		 * that shouldn't be updated or removed.
		 *
		 * @param array|null List of catalog list type names or null for all
		 * @since 2018.04
		 * @category Developer
		 * @category User
		 * @see controller/common/catalog/import/csv/domains
		 * @see controller/common/catalog/import/csv/processor/attribute/listtypes
		 * @see controller/common/catalog/import/csv/processor/catalog/listtypes
		 * @see controller/common/catalog/import/csv/processor/catalog/listtypes
		 * @see controller/common/catalog/import/csv/processor/price/listtypes
		 * @see controller/common/catalog/import/csv/processor/text/listtypes
		 */
		$this->listTypes = $context->getConfig()->get( 'controller/common/catalog/import/csv/processor/media/listtypes' );
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
		$context = $this->getContext();
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'media' );
		$listManager = \Aimeos\MShop\Factory::createManager( $context, 'catalog/lists' );
		$separator = $context->getConfig()->get( 'controller/common/catalog/import/csv/separator', "\n" );

		$listMap = [];
		$map = $this->getMappedChunk( $data, $this->getMapping() );
		$listItems = $catalog->getListItems( 'media', $this->listTypes );

		foreach( $listItems as $listItem )
		{
			if( ( $refItem = $listItem->getRefItem() ) !== null ) {
				$listMap[ $refItem->getUrl() ][ $refItem->getType() ][ $listItem->getType() ] = $listItem;
			}
		}

		foreach( $map as $pos => $list )
		{
			if( $this->checkEntry( $list ) === false ) {
				continue;
			}

			$urls = explode( $separator, trim( $list['media.url'] ) );
			$type = trim( $this->getValue( $list, 'media.type', 'default' ) );
			$typecode = trim( $this->getValue( $list, 'catalog.lists.type', 'default' ) );

			foreach( $urls as $url )
			{
				if( isset( $listMap[$url][$type][$typecode] ) )
				{
					$listItem = $listMap[$url][$type][$typecode];
					$refItem = $listItem->getRefItem();
					unset( $listItems[ $listItem->getId() ] );
				}
				else
				{
					$listItem = $listManager->createItem( $typecode, 'media' );
					$refItem = $manager->createItem( $type, 'catalog' );
				}

				$list['media.url'] = $url;

				$list = $refItem->fromArray( $this->addItemDefaults( $list ) );
				$list = $listItem->fromArray( $this->addListItemDefaults( $list, $pos++ ) );

				$catalog->addListItem( 'media', $listItem, $refItem );
			}
		}

		$catalog->deleteListItems( $listItems, true );

		return $this->getObject()->process( $catalog, $data );
	}


	/**
	 * Adds the text item default values and returns the resulting array
	 *
	 * @param array $list Associative list of domain item keys and their values, e.g. "media.status" => 1
	 * @return array Given associative list enriched by default values if they were not already set
	 */
	protected function addItemDefaults( array $list )
	{
		if( !isset( $list['media.label'] ) ) {
			$list['media.label'] = $list['media.url'];
		}

		if( !isset( $list['media.preview'] ) ) {
			$list['media.preview'] = $list['media.url'];
		}

		if( !isset( $list['media.status'] ) ) {
			$list['media.status'] = 1;
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
		if( !isset( $list['media.url'] ) || trim( $list['media.url'] ) === '' || isset( $list['catalog.lists.type'] )
				&& $this->listTypes !== null && !in_array( trim( $list['catalog.lists.type'] ), (array) $this->listTypes )
		) {
			return false;
		}

		return true;
	}
}
