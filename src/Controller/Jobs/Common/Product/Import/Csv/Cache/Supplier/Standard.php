<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2024
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Product\Import\Csv\Cache\Supplier;


/**
 * Category cache for CSV imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Cache\Base
	implements \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Cache\Iface
{
	/** controller/jobs/product/import/csv/cache/supplier/name
	 * Name of the supplier cache implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Product\Import\Csv\Cache\Supplier\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the cache class name
	 * @since 2015.10
	 */

	private array $suppliers = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context )
	{
		parent::__construct( $context );

		$manager = \Aimeos\MShop::create( $context, 'supplier' );
		$this->suppliers = $manager->search( $manager->filter(), ['address'] )->col( null, 'supplier.code' )->toArray();
	}


	/**
	 * Returns the supplier ID for the given code and type
	 *
	 * @param string $code Supplier code
	 * @param string|null $type Not used
	 * @return \Aimeos\MShop\Supplier\Item\Iface|null Supplier item or null if not found
	 */
	public function get( string $code, ?string $type = null )
	{
		if( isset( $this->suppliers[$code] ) ) {
			return $this->suppliers[$code];
		}

		$manager = \Aimeos\MShop::create( $this->context(), 'supplier' );
		$search = $manager->filter()->add( 'supplier.code', '==', $code );

		if( $item = $manager->search( $search,['address'] )->first() ) {
			$this->suppliers[$code] = $item;
		}

		return $item;
	}


	/**
	 * Adds the supplier item to the cache
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Supplier object
	 */
	public function set( \Aimeos\MShop\Common\Item\Iface $item )
	{
		$this->suppliers[$item->getCode()] = $item;
	}
}
