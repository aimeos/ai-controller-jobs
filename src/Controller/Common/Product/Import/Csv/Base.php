<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2023
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv;


/**
 * Common class for CSV product import job controllers and processors.
 *
 * @package Controller
 * @subpackage Common
 */
class Base
	extends \Aimeos\Controller\Jobs\Base
{
	/**
	 * Returns the cache object for the given type
	 *
	 * @param string $type Type of the cached data
	 * @param string|null $name Name of the cache implementation
	 * @return \Aimeos\Controller\Common\Product\Import\Csv\Cache\Iface Cache object
	 * @throws \LogicException If class can't be instantiated
	 */
	protected function getCache( string $type, string $name = null ) : \Aimeos\Controller\Common\Product\Import\Csv\Cache\Iface
	{
		$context = $this->context();
		$config = $context->config();

		if( ctype_alnum( $type ) === false ) {
			throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $type ), 400 );
		}

		$name = $name ?: $config->get( 'controller/common/product/import/csv/cache/' . $type . '/name', 'Standard' );

		if( ctype_alnum( $name ) === false ) {
			throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $name ), 400 );
		}

		$classname = '\\Aimeos\\Controller\\Common\\Product\\Import\\Csv\\Cache\\' . ucfirst( $type ) . '\\' . $name;
		$interface = \Aimeos\Controller\Common\Product\Import\Csv\Cache\Iface::class;

		return \Aimeos\Utils::create( $classname, [$context], $interface );
	}


	/**
	 * Returns the rows from the CSV file up to the maximum count
	 *
	 * @param resource $fh File handle to CSV file
	 * @param int $maxcnt Maximum number of rows that should be retrieved at once
	 * @param int $codePos Column position which contains the unique product code (starting from 0)
	 * @return array List of arrays with product codes as keys and list of values from the CSV file
	 */
	protected function getData( $fh, int $maxcnt, int $codePos ) : array
	{
		$data = [];
		$count = 0;

		while( ( $row = fgetcsv( $fh ) ) !== false && $count++ < $maxcnt ) {
			$data[$row[$codePos]] = $row;
		}

		return $data;
	}


	/**
	 * Returns the default mapping for the CSV fields to the domain item keys
	 *
	 * Example:
	 *  'item' => array(
	 *  	0 => 'product.code', // e.g. unique EAN code
	 *  	1 => 'product.label', // UTF-8 encoded text, also used as product name
	 *  ),
	 *  'text' => array(
	 *  	3 => 'text.type', // e.g. "short" for short description
	 *  	4 => 'text.content', // UTF-8 encoded text
	 *  ),
	 *  'media' => array(
	 *  	5 => 'media.url', // relative URL of the product image on the server
	 *  ),
	 *  'price' => array(
	 *		6 => 'price.currencyid',
	 *  	7 => 'price.value', // price with decimals separated by a dot, no thousand separator
	 *  	8 => 'price.taxrate', // tax rate with decimals separated by a dot
	 *  ),
	 *  'attribute' => array(
	 *  	9 => 'attribute.type', // e.g. "size", "length", "width", "color", etc.
	 *  	10 => 'attribute.code', // code of an existing attribute, new ones will be created automatically
	 *  ),
	 *  'product' => array(
	 *  	11 => 'product.code', // e.g. EAN code of another product
	 *  	12 => 'product.lists.type', // e.g. "suggestion" for suggested product
	 *  ),
	 *  'property' => array(
	 *  	13 => 'product.property.type', // e.g. "package-weight"
	 *  	14 => 'product.property.value', // arbitrary value for the corresponding type
	 *  ),
	 *  'catalog' => array(
	 *  	15 => 'catalog.code', // e.g. Unique category code
	 *  	16 => 'catalog.lists.type', // e.g. "promotion" for top seller products
	 *  ),
	 *
	 * @return array Associative list of domains as keys ("item" is special for the product itself) and a list of
	 * 	positions and the domain item keys as values.
	 */
	protected function getDefaultMapping() : array
	{
		return array(
			'item' => array(
				0 => 'product.code',
				1 => 'product.label',
				2 => 'product.type',
				3 => 'product.status',
			),
			'text' => array(
				4 => 'text.type',
				5 => 'text.content',
				6 => 'text.type',
				7 => 'text.content',
			),
			'media' => array(
				8 => 'media.url',
			),
			'price' => array(
				9 => 'price.currencyid',
				10 => 'price.quantity',
				11 => 'price.value',
				12 => 'price.taxrate',
			),
			'attribute' => array(
				13 => 'attribute.code',
				14 => 'attribute.type',
			),
			'product' => array(
				15 => 'product.code',
				16 => 'product.lists.type',
			),
			'property' => array(
				17 => 'product.property.value',
				18 => 'product.property.type',
			),
			'catalog' => array(
				19 => 'catalog.code',
				20 => 'catalog.lists.type',
			),
		);
	}


	/**
	 * Returns the mapped data from the CSV line
	 *
	 * @param array $data List of CSV fields with position as key and domain item key as value (mapped data is removed)
	 * @param array $mapping List of domain item keys with the CSV field position as key
	 * @return array List of associative arrays containing the chunked properties
	 */
	protected function getMappedChunk( array &$data, array $mapping ) : array
	{
		$idx = 0;
		$map = [];

		foreach( $mapping as $pos => $key )
		{
			if( isset( $map[$idx][$key] ) ) {
				$idx++;
			}

			if( isset( $data[$pos] ) ) {
				$map[$idx][$key] = $data[$pos];
			}
		}

		return $map;
	}


	/**
	 * Returns the processor object for saving the product related information
	 *
	 * @param array $mappings Associative list of processor types as keys and index/data mappings as values
	 * @return \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface Processor object
	 * @throws \LogicException If class can't be instantiated
	 */
	protected function getProcessors( array $mappings ) : \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface
	{
		unset( $mappings['item'] );

		$context = $this->context();
		$config = $context->config();

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Done( $context, [] );
		$interface = \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface::class;

		foreach( $mappings as $type => $mapping )
		{
			if( ctype_alnum( $type ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $type ), 400 );
			}

			$name = $config->get( 'controller/common/product/import/csv/processor/' . $type . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $name ), 400 );
			}

			$classname = '\\Aimeos\\Controller\\Common\\Product\\Import\\Csv\\Processor\\' . ucfirst( $type ) . '\\' . $name;

			$object = \Aimeos\Utils::create( $classname, [$context, $mapping, $object], $interface );
		}

		return $object;
	}
}
