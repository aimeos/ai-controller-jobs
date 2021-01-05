<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Coupon\Import\Csv\Processor\Code;


/**
 * Coupon code processor for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Coupon\Import\Csv\Processor\Base
	implements \Aimeos\Controller\Common\Coupon\Import\Csv\Processor\Iface
{
	/** controller/common/coupon/import/csv/processor/code/name
	 * Name of the coupon code processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Coupon\Import\Csv\Processor\Code\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2017.10
	 * @category Developer
	 */


	/**
	 * Saves the coupon code related data to the storage
	 *
	 * @param \Aimeos\MShop\Coupon\Item\Code\Iface $item Coupon code object
	 * @param array $data List of CSV fields with position as key and data as value
	 * @return array List of data which hasn't been imported
	 */
	public function process( \Aimeos\MShop\Coupon\Item\Code\Iface $item, array $data ) : array
	{
		$manager = \Aimeos\MShop::create( $this->getContext(), 'coupon/code' );
		$map = $this->getMappedChunk( $data, $this->getMapping() );

		foreach( $map as $list )
		{
			if( trim( $list['coupon.code.code'] ) == '' ) {
				continue;
			}

			$item = $manager->save( $item->fromArray( $list ) );
		}

		return $this->getObject()->process( $item, $data );
	}
}
