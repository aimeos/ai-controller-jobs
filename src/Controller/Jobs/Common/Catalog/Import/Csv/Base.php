<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2023
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Catalog\Import\Csv;


/**
 * Common class for CSV catalog import job controllers and processors.
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
	 * @return \Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Cache\Iface Cache object
	 * @throws \LogicException If class can't be instantiated
	 */
	protected function getCache( string $type, $name = null ) : \Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Cache\Iface
	{
		$context = $this->context();
		$config = $context->config();

		if( ctype_alnum( $type ) === false ) {
			throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $type ), 400 );
		}

		if( $name === null ) {
			$name = $config->get( 'controller/jobs/catalog/import/csv/cache/' . $type . '/name', 'Standard' );
		}

		if( ctype_alnum( $name ) === false ) {
			throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $name ), 400 );
		}

		$classname = '\\Aimeos\\Controller\\Jobs\\Common\\Catalog\\Import\\Csv\\Cache\\' . ucfirst( $type ) . '\\' . $name;
		$interface = \Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Cache\Iface::class;

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
	 *  	0 => 'catalog.code', // e.g. unique EAN code
	 *		1 => 'catalog.parent', // Code of parent catalog node
	 *  	2 => 'catalog.label', // UTF-8 encoded text, also used as catalog name
	 *		3 => 'catalog.status', // If category should be shown in the frontend
	 *  ),
	 *  'text' => array(
	 *  	3 => 'text.type', // e.g. "short" for short description
	 *  	4 => 'text.content', // UTF-8 encoded text
	 *  ),
	 *  'media' => array(
	 *  	5 => 'media.url', // relative URL of the catalog image on the server
	 *  ),
	 *
	 * @return array Associative list of domains as keys ("item" is special for the catalog itself) and a list of
	 * 	positions and the domain item keys as values.
	 */
	protected function getDefaultMapping() : array
	{
		return array(
			'item' => array(
				0 => 'catalog.code',
				1 => 'catalog.parent',
				2 => 'catalog.label',
				3 => 'catalog.status',
			),
			'text' => array(
				4 => 'text.type',
				5 => 'text.content',
			),
			'media' => array(
				6 => 'media.url',
			),
		);
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
			list( $key, $val ) = explode( ':', $line );
			$config[$key] = $val;
		}

		return $config;
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

			if( isset( $data[$pos] ) )
			{
				$map[$idx][$key] = $data[$pos];
				unset( $data[$pos] );
			}
		}

		return $map;
	}


	/**
	 * Returns the processor object for saving the catalog related information
	 *
	 * @param array $mappings Associative list of processor types as keys and index/data mappings as values
	 * @return \Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Iface Processor object
	 * @throws \LogicException If class can't be instantiated
	 */
	protected function getProcessors( array $mappings ) : \Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Iface
	{
		unset( $mappings['item'] );

		$context = $this->context();
		$config = $context->config();

		$interface = \Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Iface::class;
		$object = new \Aimeos\Controller\Jobs\Common\Catalog\Import\Csv\Processor\Done( $context, [] );

		foreach( $mappings as $type => $mapping )
		{
			if( ctype_alnum( $type ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $type ), 400 );
			}

			$name = $config->get( 'controller/jobs/catalog/import/csv/processor/' . $type . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $name ), 400 );
			}

			$classname = '\\Aimeos\\Controller\\Jobs\\Common\\Catalog\\Import\\Csv\\Processor\\' . ucfirst( $type ) . '\\' . $name;

			$object = \Aimeos\Utils::create( $classname, [$context, $mapping, $object], $interface );
		}

		return $object;
	}
}
