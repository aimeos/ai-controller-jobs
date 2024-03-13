<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2024
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Media;


/**
 * Media processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Iface
{
	/** controller/jobs/catalog/import/csv/processor/media/name
	 * Name of the media processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Media\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2018.04
	 */

	private ?array $listTypes = null;
	private array $types = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param \Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Iface $object Decorated processor
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context, array $mapping,
			\Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Iface $object = null )
	{
		parent::__construct( $context, $mapping, $object );

		/** controller/jobs/catalog/import/csv/processor/media/listtypes
		 * Names of the catalog list types for media that are updated or removed
		 *
		 * If you want to associate media items manually via the administration
		 * interface to catalogs and don't want these to be touched during the
		 * import, you can specify the catalog list types for these media
		 * that shouldn't be updated or removed.
		 *
		 * @param array|null List of catalog list type names or null for all
		 * @since 2018.04
		 * @see controller/jobs/catalog/import/csv/domains
		 * @see controller/jobs/catalog/import/csv/processor/attribute/listtypes
		 * @see controller/jobs/catalog/import/csv/processor/catalog/listtypes
		 * @see controller/jobs/catalog/import/csv/processor/catalog/listtypes
		 * @see controller/jobs/catalog/import/csv/processor/price/listtypes
		 * @see controller/jobs/catalog/import/csv/processor/text/listtypes
		 */
		$key = 'controller/jobs/catalog/import/csv/processor/media/listtypes';
		$this->listTypes = $context->config()->get( $key );

		if( $this->listTypes === null )
		{
			$this->listTypes = [];
			$manager = \Aimeos\MShop::create( $context, 'catalog/lists/type' );

			$search = $manager->filter()->slice( 0, 0x7fffffff );
			$search->setConditions( $search->compare( '==', 'catalog.lists.type.domain', 'media' ) );

			foreach( $manager->search( $search ) as $item ) {
				$this->listTypes[$item->getCode()] = $item->getCode();
			}
		}
		else
		{
			$this->listTypes = array_combine( $this->listTypes, $this->listTypes );
		}


		$manager = \Aimeos\MShop::create( $context, 'media/type' );

		$search = $manager->filter()->slice( 0, 0x7fffffff );
		$search->setConditions( $search->compare( '==', 'media.type.domain', 'catalog' ) );

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
		$context = $this->context();
		$manager = \Aimeos\MShop::create( $context, 'catalog' );
		$refManager = \Aimeos\MShop::create( $context, 'media' );

		/** controller/jobs/catalog/import/csv/separator
		 * Single separator character for multiple entries in one field of the import file
		 *
		 * The catalog importer is able split the content of a field from the import
		 * file into several entries based on the given separator character. Thus,
		 * you can create more compact import files and handle a variable range
		 * of entries better. The default separator character is a new line.
		 *
		 * '''Caution:''' The separator character must not be part of any entry
		 * in the field. Otherwise, you will get invalid entries and the importer
		 * may fail!
		 *
		 * @param string Single separator character
		 * @since 2018.04
		 * @see controller/jobs/catalog/import/csv/domains
		 * @see controller/jobs/supplier/import/csv/domains
		 */
		$separator = $context->config()->get( 'controller/jobs/catalog/import/csv/separator', "\n" );

		$listMap = [];
		$map = $this->getMappedChunk( $data, $this->getMapping() );
		$listItems = $catalog->getListItems( 'media', $this->listTypes );

		foreach( $listItems as $listItem )
		{
			if( ( $refItem = $listItem->getRefItem() ) !== null ) {
				$listMap[$refItem->getUrl()][$refItem->getType()][$listItem->getType()] = $listItem;
			}
		}

		foreach( $map as $pos => $list )
		{
			if( $this->checkEntry( $list ) === false ) {
				continue;
			}

			$type = trim( $this->val( $list, 'media.type', 'default' ) );
			$listtype = trim( $this->val( $list, 'catalog.lists.type', 'default' ) );
			$listConfig = $this->getListConfig( trim( $this->val( $list, 'catalog.lists.config', '' ) ) );
			$urls = explode( $separator, $this->val( $list, 'media.url', '' ) );

			foreach( $urls as $url )
			{
				if( isset( $listMap[$url][$type][$listtype] ) )
				{
					$listItem = $listMap[$url][$type][$listtype];
					$refItem = $listItem->getRefItem();
					unset( $listItems[$listItem->getId()] );
				}
				else
				{
					$listItem = $manager->createListItem()->setType( $listtype );
					$refItem = $refManager->create()->setType( $type );
				}

				$listItem = $listItem->setPosition( $pos++ )->fromArray( $list )->setConfig( $listConfig );
				$refItem = $refItem->setLabel( $url )->setPreview( $url )->fromArray( $list )->setUrl( $url );

				$catalog->addListItem( 'media', $listItem, $refItem );
			}
		}

		$catalog->deleteListItems( $listItems->toArray(), true );

		return $this->object()->process( $catalog, $data );
	}


	/**
	 * Checks if an entry can be used for updating a media item
	 *
	 * @param array $list Associative list of key/value pairs from the mapping
	 * @return bool True if valid, false if not
	 */
	protected function checkEntry( array $list ) : bool
	{
		if( $this->val( $list, 'media.url' ) === null ) {
			return false;
		}

		if( ( $type = trim( $this->val( $list, 'catalog.lists.type', 'default' ) ) ) && !isset( $this->listTypes[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'catalog list' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		if( ( $type = trim( $this->val( $list, 'media.type', 'default' ) ) ) && !isset( $this->types[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'media' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return true;
	}
}
