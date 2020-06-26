<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2020
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Address;


/**
 * Address processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Iface
{
	/** controller/common/supplier/import/csv/processor/address/name
	 * Name of the address processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Address\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2020.07
	 * @category Developer
	 */

	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Iface $object Decorated processor
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context, array $mapping,
								 \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Iface $object = null )
	{
		parent::__construct( $context, $mapping, $object );
	}

	/**
	 * Saves the supplier related data to the storage
	 *
	 * @param \Aimeos\MShop\Supplier\Item\Iface $supplier Supplier item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Supplier\Item\Iface $supplier, array $data ): array
	{

		$manager = \Aimeos\MShop::create( $this->getContext(), 'supplier/address' );
		$manager->begin();

		try {
			$map = $this->getMappedChunk( $data, $this->getMapping() );
			$items = $this->getAddressItems( $supplier->getId() );

			foreach( $map as $pos => $list ) {
				if( $this->checkEntry( $list ) === false ) {
					continue;
				}

				if( ($item = $items->pop()) === null ) {
					$item = $manager->createItem();
					$item->fromArray( $list );
					$supplier->addAddressItem( $item );
				} else {
					$item->fromArray( $list );
					$manager->saveItem( $item );
				}
			}

			$data = $this->getObject()->process( $supplier, $data );

			$manager->commit();
		} catch ( \Exception $e ) {
			$manager->rollback();
			throw $e;
		}


		return $data;
	}

	/**
	 * Returns the address items for the given supplier code
	 *
	 * @param string $id Supplier's id
	 * @return \Aimeos\Map List of stock items implementing \Aimeos\MShop\Stock\Item\Iface
	 */
	protected function getAddressItems( $id ): \Aimeos\Map
	{
		$manager = \Aimeos\MShop::create( $this->getContext(), 'supplier/address' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'supplier.address.parentid', $id ) );

		return $manager->searchItems( $search );
	}

	/**
	 * Checks if an entry can be used for updating a media item
	 *
	 * @param array $list Associative list of key/value pairs from the mapping
	 * @return bool True if valid, false if not
	 */
	protected function checkEntry( array $list ): bool
	{
		if( $this->getValue( $list, 'supplier.address.languageid' ) === null ) {
			return false;
		}
		if( $this->getValue( $list, 'supplier.address.countryid' ) === null ) {
			return false;
		}
		if( $this->getValue( $list, 'supplier.address.city' ) === null ) {
			return false;
		}
		return true;
	}
}
