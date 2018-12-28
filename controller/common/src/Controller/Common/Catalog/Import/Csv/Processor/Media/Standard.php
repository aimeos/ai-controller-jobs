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
	private $types = [];


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
		$key = 'controller/common/catalog/import/csv/processor/media/listtypes';
		$this->listTypes = $context->getConfig()->get( $key );

		if( $this->listTypes === null )
		{
			$this->listTypes = [];
			$manager = \Aimeos\MShop::create( $context, 'catalog/lists/type' );

			$search = $manager->createSearch()->setSlice( 0, 0x7fffffff );
			$search->setConditions( $search->compare( '==', 'catalog.lists.type.domain', 'media' ) );

			foreach( $manager->searchItems( $search ) as $item ) {
				$this->listTypes[$item->getCode()] = $item->getCode();
			}
		}
		else
		{
			$this->listTypes = array_flip( $this->listTypes );
		}


		$manager = \Aimeos\MShop::create( $context, 'media/type' );

		$search = $manager->createSearch()->setSlice( 0, 0x7fffffff );
		$search->setConditions( $search->compare( '==', 'media.type.domain', 'catalog' ) );

		foreach( $manager->searchItems( $search ) as $item ) {
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
	public function process( \Aimeos\MShop\Catalog\Item\Iface $catalog, array $data )
	{
		$context = $this->getContext();
		$manager = \Aimeos\MShop::create( $context, 'media' );
		$listManager = \Aimeos\MShop::create( $context, 'catalog/lists' );
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

			$type = $this->getValue( $list, 'media.type', 'default' );
			$listtype = $this->getValue( $list, 'catalog.lists.type', 'default' );
			$urls = explode( $separator, $this->getValue( $list, 'media.url', '' ) );

			foreach( $urls as $url )
			{
				if( isset( $listMap[$url][$type][$listtype] ) )
				{
					$listItem = $listMap[$url][$type][$listtype];
					$refItem = $listItem->getRefItem();
					unset( $listItems[ $listItem->getId() ] );
				}
				else
				{
					$listItem = $listManager->createItem()->setType( $listtype );
					$refItem = $manager->createItem()->setType( $type );
				}

				$listItem = $listItem->setPosition( $pos++ )->fromArray( $list );
				$refItem = $refItem->setLabel( $url )->setPreview( $url )->fromArray( $list )->setUrl( $url );

				$catalog->addListItem( 'media', $listItem, $refItem );
			}
		}

		$catalog->deleteListItems( $listItems, true );

		return $this->getObject()->process( $catalog, $data );
	}


	/**
	 * Checks if an entry can be used for updating a media item
	 *
	 * @param array $list Associative list of key/value pairs from the mapping
	 * @return boolean True if valid, false if not
	 */
	protected function checkEntry( array $list )
	{
		if( $this->getValue( $list, 'media.url' ) === null ) {
			return false;
		}

		if( ( $type = $this->getValue( $list, 'catalog.lists.type' ) ) && !isset( $this->listTypes[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'catalog list' );
			throw new \Aimeos\Controller\Common\Exception( $msg );
		}

		if( ( $type = $this->getValue( $list, 'media.type' ) ) && !isset( $this->types[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'media' );
			throw new \Aimeos\Controller\Common\Exception( $msg );
		}

		return true;
	}
}
