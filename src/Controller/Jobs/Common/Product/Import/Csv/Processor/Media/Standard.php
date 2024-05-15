<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2024
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Media;


/**
 * Media processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface
{
	/** controller/jobs/product/import/csv/processor/media/name
	 * Name of the media processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Media\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2015.10
	 */

	private ?array $listTypes = null;
	private array $types = [];
	private array $mimes = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface $object Decorated processor
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context, array $mapping,
			\Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface $object = null )
	{
		parent::__construct( $context, $mapping, $object );

		$config = $context->config();
		$this->mimes = array_flip( $config->get( 'mshop/media/manager/extensions', [] ) );

		/** controller/jobs/product/import/csv/media/listtypes
		 * Names of the product list types for media that are updated or removed
		 *
		 * If you want to associate media items manually via the administration
		 * interface to products and don't want these to be touched during the
		 * import, you can specify the product list types for these media
		 * that shouldn't be updated or removed.
		 *
		 * @param array|null List of product list type names or null for all
		 * @since 2015.05
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/separator
		 * @see controller/jobs/product/import/csv/attribute/listtypes
		 * @see controller/jobs/product/import/csv/catalog/listtypes
		 * @see controller/jobs/product/import/csv/product/listtypes
		 * @see controller/jobs/product/import/csv/price/listtypes
		 * @see controller/jobs/product/import/csv/supplier/listtypes
		 * @see controller/jobs/product/import/csv/text/listtypes
		 */
		$default = $config->get( 'controller/jobs/product/import/csv/processor/media/listtypes' );
		$this->listTypes = $config->get( 'controller/jobs/product/import/csv/media/listtypes', $default );

		if( $this->listTypes === null )
		{
			$this->listTypes = [];
			$manager = \Aimeos\MShop::create( $context, 'product/lists/type' );

			$search = $manager->filter()->slice( 0, 0x7fffffff );
			$search->setConditions( $search->compare( '==', 'product.lists.type.domain', 'media' ) );

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
		$search->setConditions( $search->compare( '==', 'media.type.domain', 'product' ) );

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
		$refManager = \Aimeos\MShop::create( $context, 'media' );
		$separator = $context->config()->get( 'controller/jobs/product/import/csv/separator', "\n" );

		$listMap = [];
		$map = $this->getMappedChunk( $data, $this->getMapping() );
		$listItems = $product->getListItems( 'media', $this->listTypes, null, false );

		foreach( $listItems as $listItem )
		{
			if( ( $refItem = $listItem->getRefItem() ) !== null ) {
				$listMap[$refItem->getUrl()][$refItem->getType()][$refItem->getLanguageId()][$listItem->getType()] = $listItem;
			}
		}

		foreach( $map as $pos => $list )
		{
			if( $this->checkEntry( $list ) === false ) {
				continue;
			}

			$type = trim( $this->val( $list, 'media.type', 'default' ) );
			$langId = trim( $this->val( $list, 'media.languageid', '' ) );
			$listtype = trim( $this->val( $list, 'product.lists.type', 'default' ) );
			$listConfig = $this->getListConfig( trim( $this->val( $list, 'product.lists.config', '' ) ) );

			$urls = explode( $separator, trim( $this->val( $list, 'media.url', '' ) ) );
			unset( $list['media.url'] );

			$this->addType( 'product/lists/type', 'media', $listtype );
			$this->addType( 'media/type', 'product', $type );

			foreach( $urls as $idx => $url )
			{
				$url = trim( $url );

				if( isset( $listMap[$url][$type][$langId][$listtype] ) )
				{
					$listItem = $listMap[$url][$type][$langId][$listtype];
					$refItem = $listItem->getRefItem();
					unset( $listItems[$listItem->getId()] );
				}
				else
				{
					$listItem = $manager->createListItem()->setType( $listtype );
					$refItem = $refManager->create()->setType( $type );
				}

				$ext = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );
				if( isset( $this->mimes[$ext] ) ) {
					$refItem->setMimeType( $this->mimes[$ext] );
				}

				$refItem->setDomain( 'product' );
				$refItem = $this->update( $refItem, $list, $url );
				$listItem = $listItem->setPosition( $pos++ )->fromArray( $list )->setConfig( $listConfig );

				$product->addListItem( 'media', $listItem, $refItem );
			}
		}

		$product->deleteListItems( $listItems->toArray(), true );

		return $this->object()->process( $product, $data );
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

		if( ( $type = trim( $this->val( $list, 'product.lists.type', 'default' ) ) ) && !isset( $this->listTypes[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'product list' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		if( ( $type = trim( $this->val( $list, 'media.type', 'default' ) ) ) && !isset( $this->types[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'media' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return true;
	}


	/**
	 * Updates the media item with the given key/value pairs
	 *
	 * @param \Aimeos\MShop\Media\Item\Iface $refItem Media item to update
	 * @param array &$list Associative list of key/value pairs, matching pairs are removed
	 * @return \Aimeos\MShop\Media\Item\Iface Updated media item
	 */
	protected function update( \Aimeos\MShop\Media\Item\Iface $refItem, array &$list, string $url ) : \Aimeos\MShop\Media\Item\Iface
	{
		try
		{
			if( isset( $list['media.previews'] ) && ( $map = json_decode( $list['media.previews'], true ) ) !== null ) {
				$refItem->setPreviews( $map )->setUrl( $url );
			} elseif( isset( $list['media.preview'] ) ) {
				$refItem->setPreview( $list['media.preview'] )->setUrl( $url );
			} elseif( $refItem->getUrl() !== $url ) {
				$refItem = \Aimeos\MShop::create( $this->context(), 'media' )->scale( $refItem->setUrl( $url ), true );
			} else {
				$refItem = \Aimeos\MShop::create( $this->context(), 'media' )->scale( $refItem->setUrl( $url ) );
			}

			unset( $list['media.previews'], $list['media.preview'] );
		}
		catch( \Exception $e )
		{
			$msg = sprintf( 'Scaling image "%1$s" failed: %2$s', $url, $e->getMessage() );
			$this->context()->logger()->error( $msg, 'import/csv/product' );
		}

		return $refItem->fromArray( $list );
	}
}
