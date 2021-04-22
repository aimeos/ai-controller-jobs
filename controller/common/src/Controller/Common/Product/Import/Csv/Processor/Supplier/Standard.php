<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Supplier;


/**
 * Supplier processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Product\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface
{
	/** controller/common/product/import/csv/processor/supplier/name
	 * Name of the supplier processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Product\Import\Csv\Processor\Supplier\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2020.07
	 * @category Developer
	 */

	private $cache;
	private $listTypes;


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

		/** controller/common/product/import/csv/processor/supplier/listtypes
		 * Names of the supplier list types that are updated or removed
		 *
		 * If you want to associate product items to suppliers manually via the
		 * administration interface and don't want these to be touched during the
		 * import, you can specify the supplier list types for these products
		 * that shouldn't be updated or removed.
		 *
		 * @param array|null List of supplier list type names or null for all
		 * @since 2020.07
		 * @category Developer
		 * @category User
		 * @see controller/common/product/import/csv/domains
		 * @see controller/common/product/import/csv/processor/attribute/listtypes
		 * @see controller/common/product/import/csv/processor/media/listtypes
		 * @see controller/common/product/import/csv/processor/price/listtypes
		 * @see controller/common/product/import/csv/processor/product/listtypes
		 * @see controller/common/product/import/csv/processor/text/listtypes
		 */
		$key = 'controller/common/product/import/csv/processor/supplier/listtypes';
		$this->listTypes = $context->getConfig()->get( $key );

		if( $this->listTypes === null )
		{
			$this->listTypes = [];
			$manager = \Aimeos\MShop::create( $context, 'supplier/lists/type' );

			$search = $manager->filter()->slice( 0, 0x7fffffff );
			$search->setConditions( $search->compare( '==', 'supplier.lists.type.domain', 'product' ) );

			foreach( $manager->search( $search ) as $item )
			{
				$this->listTypes[$item->getCode()] = $item->getCode();
			}
		} else
		{
			$this->listTypes = array_flip( $this->listTypes );
		}

		$this->cache = $this->getCache( 'supplier' );
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
		$context = $this->getContext();
		$manager = \Aimeos\MShop::create( $context, 'supplier' );
		$listManager = \Aimeos\MShop::create( $context, 'supplier/lists' );

		/** controller/common/product/import/csv/separator
		 * Single separator character for multiple entries in one field of the import file
		 *
		 * The product importer is able split the content of a field from the import
		 * file into several entries based on the given separator character. Thus,
		 * you can create more compact import files and handle a variable range
		 * of entries better. The default separator character is a new line.
		 *
		 * '''Caution:''' The separator character must not be part of any entry
		 * in the field. Otherwise, you will get invalid entries and the importer
		 * may fail!
		 *
		 * @param string Single separator character
		 * @since 2020.07
		 * @category User
		 * @category Developer
		 * @see controller/common/product/import/csv/domains
		 */
		$separator = $context->getConfig()->get( 'controller/common/product/import/csv/separator', "\n" );

		$manager->begin();

		try
		{
			$listMap = [];
			$prodid = $product->getId();
			$map = $this->getMappedChunk( $data, $this->getMapping() );
			$listItems = $this->getListItems( $prodid, $this->listTypes );

			foreach( $listItems as $listItem )
			{
				$listMap[$listItem->getParentId()][$listItem->getType()] = $listItem;
			}

			foreach( $map as $pos => $list )
			{
				if( $this->checkEntry( $list ) === false )
				{
					continue;
				}

				$codes = explode( $separator, $this->getValue( $list, 'supplier.code', '' ) );
				$listtype = $this->getValue( $list, 'supplier.lists.type', 'default' );
				$this->addType( 'supplier/lists/type', 'product', $listtype );

				foreach( $codes as $code )
				{
					$code = trim( $code );

					if( ( $supid = $this->cache->get( $code ) ) === null )
					{
						$msg = 'No supplier for code "%1$s" available when importing product with code "%2$s"';
						throw new \Aimeos\Controller\Jobs\Exception( sprintf( $msg, $code, $product->getCode() ) );
					}

					$list['supplier.lists.parentid'] = $supid;
					$list['supplier.lists.refid'] = $prodid;
					$list['supplier.lists.domain'] = 'product';

					if( isset( $listMap[$supid][$listtype] ) )
					{
						$listItem = $listMap[$supid][$listtype];
						unset( $listItems[$listItem->getId()] );
					} else
					{
						$listItem = $listManager->create()->setType( $listtype );
					}

					$listItem = $listItem->setPosition( $pos++ )->fromArray( $list, true );
					$listManager->save( $listItem, false );
				}
			}

			$listManager->delete( $listItems->toArray() );
			$data = $this->getObject()->process( $product, $data );

			$manager->commit();
		} catch( \Exception $e )
		{
			$manager->rollback();
			throw $e;
		}

		return $data;
	}


	/**
	 * Adds the list item default values and returns the resulting array
	 *
	 * @param array $list Associative list of domain item keys and their values, e.g. "supplier.lists.status" => 1
	 * @param int $pos Computed position of the list item in the associated list of items
	 * @return array Given associative list enriched by default values if they were not already set
	 */
	protected function addListItemDefaults( array $list, int $pos ) : array
	{
		if( !isset( $list['supplier.lists.position'] ) )
		{
			$list['supplier.lists.position'] = $pos;
		}

		if( !isset( $list['supplier.lists.status'] ) )
		{
			$list['supplier.lists.status'] = 1;
		}

		return $list;
	}


	/**
	 * Checks if the entry from the mapped data is valid
	 *
	 * @param array $list Associative list of key/value pairs from the mapped data
	 * @return bool True if the entry is valid, false if not
	 */
	protected function checkEntry( array $list ) : bool
	{
		if( $this->getValue( $list, 'supplier.code' ) === null )
		{
			return false;
		}

		if( ( $type = $this->getValue( $list, 'supplier.lists.type' ) ) && !isset( $this->listTypes[$type] ) )
		{
			$msg = sprintf( 'Invalid type "%1$s" (%2$s)', $type, 'supplier list' );
			throw new \Aimeos\Controller\Common\Exception( $msg );
		}

		return true;
	}


	/**
	 * Returns the supplier list items for the given supplier and product ID
	 *
	 * @param string $prodid Unique product ID
	 * @param array $types List of supplier list types
	 * @return \Aimeos\Map List of supplier list items
	 */
	protected function getListItems( $prodid, array $types ) : \Aimeos\Map
	{
		if( empty( $types ) ) {
			return map();
		}

		$manager = \Aimeos\MShop::create( $this->getContext(), 'supplier/lists' );
		$search = $manager->filter()->slice( 0, 0x7FFFFFFF );

		$expr = [
			$search->compare( '==', 'supplier.lists.domain', 'product' ),
			$search->compare( '==', 'supplier.lists.type', $types ),
			$search->compare( '==', 'supplier.lists.refid', $prodid ),
		];

		return $manager->search( $search->setConditions( $search->and( $expr ) ) );
	}
}
