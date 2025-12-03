<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Address;


/**
 * Address processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Iface
{
	/** controller/jobs/customer/import/csv/processor/address/name
	 * Name of the address processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Address\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2025.10
	 */


	/**
	 * Saves the customer related data to the storage
	 *
	 * @param \Aimeos\MShop\Customer\Item\Iface $customer Customer item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Customer\Item\Iface $customer, array $data ) : array
	{
		$context = $this->context();
		$manager = \Aimeos\MShop::create( $context, 'customer' );

		$pos = 0;
		$map = $this->getMappedChunk( $data, $this->getMapping() );
		$addresses = $customer->getAddressItems();

		foreach( $map as $entry )
		{
			$key = $addresses->firstKey();
			$address = $addresses->pull( $key ) ?? $manager->createAddressItem();
			$address->setPosition( $pos++ )->fromArray( $entry );

			$customer->addAddressItem( $address, $key );
		}

		$customer->deleteAddressItems( $addresses );

		return $this->object()->process( $customer, $data );
	}
}
