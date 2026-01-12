<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2026
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor;


/**
 * Abstract class with common methods for all CSV import processors
 *
 * @package Controller
 * @subpackage Common
 */
abstract class Base
	extends \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Base
{
	use \Aimeos\Controller\Jobs\Common\Types;


	private \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface $object;
	private \Aimeos\MShop\ContextIface $context;
	private array $mapping;


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
		$this->context = $context;
		$this->mapping = $mapping;
		$this->object = $object;
	}


	/**
	 * Stores all types for which no type items exist yet
	 */
	public function finish()
	{
		if( $this->object ) {
			$this->object->finish();
		}

		$this->saveTypes();
	}


	/**
	 * Adds the list item default values and returns the resulting array
	 *
	 * @param array $list Associative list of domain item keys and their values, e.g. "product.lists.status" => 1
	 * @param int $pos Computed position of the list item in the associated list of items
	 * @return array Given associative list enriched by default values if they were not already set
	 */
	protected function addListItemDefaults( array $list, int $pos ) : array
	{
		if( !isset( $list['product.lists.position'] ) ) {
			$list['product.lists.position'] = $pos;
		}

		if( !isset( $list['product.lists.status'] ) ) {
			$list['product.lists.status'] = 1;
		}

		return $list;
	}


	/**
	 * Returns the context item
	 *
	 * @return \Aimeos\MShop\ContextIface Context object
	 */
	protected function context() : \Aimeos\MShop\ContextIface
	{
		return $this->context;
	}


	/**
	 * Returns the configuration for the given string
	 *
	 * @param string $value Configuration string
	 * @return array Configuration settings
	 */
	protected function getListConfig( string $value ) : array
	{
		$config = [];

		foreach( array_filter( explode( "\n", $value ) ) as $line )
		{
			$parts = explode( ':', $line );

			if( count( $parts ) !== 2 ) {
				throw new \Aimeos\Controller\Jobs\Exception( 'Invalid list configuration: ' . $value );
			}

			list( $key, $val ) = $parts;
			$config[$key] = $val;
		}

		return $config;
	}


	/**
	 * Returns the mapping list
	 *
	 * @return array Associative list of field positions in CSV as keys and domain item keys as values
	 */
	protected function getMapping() : array
	{
		return $this->mapping;
	}


	/**
	 * Returns the decorated processor object
	 *
	 * @return \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface Processor object
	 * @throws \Aimeos\Controller\Jobs\Exception If no processor object is available
	 */
	protected function object() : \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface
	{
		if( $this->object === null ) {
			throw new \Aimeos\Controller\Jobs\Exception( 'No processor object available' );
		}

		return $this->object;
	}
}
