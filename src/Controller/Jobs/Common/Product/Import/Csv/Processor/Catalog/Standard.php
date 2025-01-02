<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Catalog;


/**
 * Catalog processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface
{
	/** controller/jobs/product/import/csv/processor/catalog/name
	 * Name of the catalog processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Catalog\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2015.10
	 */

	private \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Cache\Catalog\Standard $cache;
	private ?array $listTypes = null;


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

		/** controller/jobs/product/import/csv/catalog/listtypes
		 * Names of the catalog list types that are updated or removed
		 *
		 * Aimeos offers associated items like "bought together" catalogs that
		 * are automatically generated by other job controllers. These relations
		 * shouldn't normally be overwritten or deleted by default during the
		 * import and this confiuration option enables you to specify the list
		 * types that should be updated or removed if not available in the import
		 * file.
		 *
		 * Contrary, if you don't generate any relations automatically in the
		 * shop and want to import those relations too, you can set the option
		 * to null to update all associated items.
		 *
		 * @param array|null List of catalog list type names or null for all
		 * @since 2015.05
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/separator
		 * @see controller/jobs/product/import/csv/attribute/listtypes
		 * @see controller/jobs/product/import/csv/media/listtypes
		 * @see controller/jobs/product/import/csv/price/listtypes
		 * @see controller/jobs/product/import/csv/product/listtypes
		 * @see controller/jobs/product/import/csv/supplier/listtypes
		 * @see controller/jobs/product/import/csv/text/listtypes
		 */
		$default = $config->get( 'controller/jobs/product/import/csv/processor/catalog/listtypes', ['default', 'promotion'] );
		$this->listTypes = $config->get( 'controller/jobs/product/import/csv/catalog/listtypes', $default );

		if( $this->listTypes === null )
		{
			$this->listTypes = [];
			$manager = \Aimeos\MShop::create( $context, 'product/lists/type' );

			$search = $manager->filter()->slice( 0, 0x7fffffff );
			$search->setConditions( $search->compare( '==', 'product.lists.type.domain', 'catalog' ) );

			foreach( $manager->search( $search ) as $item ) {
				$this->listTypes[$item->getCode()] = $item->getCode();
			}
		}
		else
		{
			$this->listTypes = array_combine( $this->listTypes, $this->listTypes );
		}

		$this->cache = $this->getCache( 'catalog' );
	}


	/**
	 * Saves the catalog related data to the storage
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface $product Product item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which has not been imported
	 */
	public function process( \Aimeos\MShop\Product\Item\Iface $product, array $data ) : array
	{
		$context = $this->context();
		$logger = $context->logger();
		$manager = \Aimeos\MShop::create( $context, 'product' );
		$separator = $context->config()->get( 'controller/jobs/product/import/csv/separator', "\n" );

		$listItems = $product->getListItems( 'catalog', null, null, false );
		$pos = 0;

		foreach( $this->getMappedChunk( $data, $this->getMapping() ) as $list )
		{
			if( $this->checkEntry( $list ) === false ) {
				continue;
			}

			$listConfig = $this->getListConfig( trim( $this->val( $list, 'product.lists.config', '' ) ) );
			$listtype = trim( $this->val( $list, 'product.lists.type', 'default' ) );
			$this->addType( 'product/lists/type', 'catalog', $listtype );

			foreach( explode( $separator, trim( $this->val( $list, 'catalog.code', '' ) ) ) as $code )
			{
				$code = trim( $code );

				if( ( $catid = $this->cache->get( $code ) ) === null )
				{
					$msg = 'No catalog for code "%1$s" available when importing product with code "%2$s"';
					$logger->warning( sprintf( $msg, $code, $product->getCode() ), 'import/csv/product' );
					continue;
				}

				if( ( $listItem = $product->getListItem( 'catalog', $listtype, $catid ) ) === null ) {
					$listItem = $manager->createListItem()->setType( $listtype );
				} else {
					unset( $listItems[$listItem->getId()] );
				}

				$listItem = $listItem->fromArray( $list )->setRefId( $catid )->setConfig( $listConfig )->setPosition( $pos++ );
				$product->addListItem( 'catalog', $listItem );
			}
		}

		$product->deleteListItems( $listItems->toArray() );

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
		if( $this->val( $list, 'catalog.code' ) === null ) {
			return false;
		}

		if( ( $type = trim( $this->val( $list, 'product.lists.type', 'default' ) ) ) && !isset( $this->listTypes[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'product list' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return true;
	}
}
