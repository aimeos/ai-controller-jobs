<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor;


/**
 * End point for the CSV import processors
 *
 * @package Controller
 * @subpackage Common
 */
class Done
	implements \Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Iface
{
	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param \Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Iface $processor Decorated processor
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context, array $mapping,
		?\Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Iface $processor = null )
	{
	}


	/**
	 * Stores all types for which no type items exist yet
	 */
	public function finish()
	{
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
		return $data;
	}
}
