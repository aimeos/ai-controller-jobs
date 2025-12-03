<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Property;


/**
 * Customer property processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Iface
{
	/** controller/jobs/customer/import/csv/processor/property/name
	 * Name of the property processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Property\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2025.10
	 */


	/**
	 * Saves the customer property related data to the storage
	 *
	 * @param \Aimeos\MShop\Customer\Item\Iface $customer Customer item with associated items
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Customer\Item\Iface $customer, array $data ) : array
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'customer' );

		$propMap = [];
		$items = $customer->getPropertyItems( null, false );
		$map = $this->getMappedChunk( $data, $this->getMapping() );

		foreach( $items as $item ) {
			$propMap[$item->getValue()][$item->getType()] = $item;
		}

		foreach( $map as $list )
		{
			if( ( $value = $this->val( $list, 'customer.property.value' ) ) === null ) {
				continue;
			}

			$type = $this->val( $list, 'customer.property.type' );
			$this->addType( 'customer/property/type', 'customer', $type );

			if( isset( $propMap[$value][$type] ) )
			{
				$item = $propMap[$value][$type];
				$items->remove( $item->getId() );
			}
			else
			{
				$item = $manager->createPropertyItem()->setType( $type );
			}

			$customer->addPropertyItem( $item->fromArray( $list ) );
		}

		$customer->deletePropertyItems( $items );

		return $this->object()->process( $customer, $data );
	}
}
