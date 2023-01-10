<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2023
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Catalog\Import\Csv;


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
	 * @return \Aimeos\Controller\Common\Catalog\Import\Csv\Cache\Iface Cache object
	 */
	protected function getCache( string $type, $name = null ) : \Aimeos\Controller\Common\Catalog\Import\Csv\Cache\Iface
	{
		$context = $this->context();
		$config = $context->config();

		if( ctype_alnum( $type ) === false )
		{
			$classname = is_string( $name ) ? '\\Aimeos\\Controller\\Common\\Catalog\\Import\\Csv\\Cache\\' . $type : '<not a string>';
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
		}

		if( $name === null ) {
			$name = $config->get( 'controller/common/catalog/import/csv/cache/' . $type . '/name', 'Standard' );
		}

		if( ctype_alnum( $name ) === false )
		{
			$classname = is_string( $name ) ? '\\Aimeos\\Controller\\Common\\Catalog\\Import\\Csv\\Cache\\' . $type . '\\' . $name : '<not a string>';
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
		}

		$classname = '\\Aimeos\\Controller\\Common\\Catalog\\Import\\Csv\\Cache\\' . ucfirst( $type ) . '\\' . $name;

		if( class_exists( $classname ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Class "%1$s" not found', $classname ) );
		}

		$object = new $classname( $context );

		\Aimeos\MW\Common\Base::checkClass( '\\Aimeos\\Controller\\Common\\Catalog\\Import\\Csv\\Cache\\Iface', $object );

		return $object;
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
	 * @return \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Iface Processor object
	 */
	protected function getProcessors( array $mappings ) : \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Iface
	{
		unset( $mappings['item'] );

		$context = $this->context();
		$config = $context->config();
		$object = new \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Done( $context, [] );

		foreach( $mappings as $type => $mapping )
		{
			if( ctype_alnum( $type ) === false )
			{
				$classname = is_string( $type ) ? '\\Aimeos\\Controller\\Common\\Catalog\\Import\\Csv\\Processor\\' . $type : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$name = $config->get( 'controller/common/catalog/import/csv/processor/' . $type . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false )
			{
				$classname = is_string( $name ) ? '\\Aimeos\\Controller\\Common\\Catalog\\Import\\Csv\\Processor\\' . $type . '\\' . $name : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$classname = '\\Aimeos\\Controller\\Common\\Catalog\\Import\\Csv\\Processor\\' . ucfirst( $type ) . '\\' . $name;

			if( class_exists( $classname ) === false ) {
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Class "%1$s" not found', $classname ) );
			}

			$object = new $classname( $context, $mapping, $object );

			\Aimeos\MW\Common\Base::checkClass( '\\Aimeos\\Controller\\Common\\Catalog\\Import\\Csv\\Processor\\Iface', $object );
		}

		return $object;
	}
}
