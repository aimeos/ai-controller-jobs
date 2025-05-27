<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Supplier\Import\Csv;


/**
 * Common class for CSV supplier import job controllers and processors.
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
	 * @return \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Cache\Iface Cache object
	 * @throws \LogicException If class can't be instantiated
	 */
	protected function getCache( string $type, $name = null ) : \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Cache\Iface
	{
		$context = $this->context();
		$config = $context->config();

		if( ctype_alnum( $type ) === false ) {
			throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $type ), 400 );
		}

		$name = $name ?: $config->get( 'controller/jobs/supplier/import/csv/cache/' . $type . '/name', 'Standard' );

		if( ctype_alnum( $name ) === false ) {
			throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $name ), 400 );
		}

		$classname = '\\Aimeos\\Controller\\Jobs\\Common\\Supplier\\Import\\Csv\\Cache\\' . ucfirst( $type ) . '\\' . $name;
		$interface = \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Cache\Iface::class;

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
		$count = 0;
		$data = [];

		while( $count++ < $maxcnt && ( $row = fgetcsv( $fh, null, ',', '"', '' ) ) !== false ) {
			$data[$row[$codePos]] = $row;
		}

		return $data;
	}


	/**
	 * Returns the default mapping for the CSV fields to the domain item keys
	 *
	 * Example:
	 *  'item' => array(
	 *    0 => 'supplier.code', // e.g. unique EAN code
	 *    1 => 'supplier.parent', // Code of parent supplier node
	 *    2 => 'supplier.label', // UTF-8 encoded text, also used as supplier name
	 *    3 => 'supplier.status', // If supplier should be shown in the frontend
	 *  ),
	 *  'text' => array(
	 *    3 => 'text.type', // e.g. "short" for short description
	 *    4 => 'text.content', // UTF-8 encoded text
	 *  ),
	 *  'media' => array(
	 *    5 => 'media.url', // relative URL of the supplier image on the server
	 *  ),
	 *  'address' => array(
	 *    6 => supplier.address.countryid', // Country id by ISO 3166-1. e.g. Germany is DE
	 *    6 => supplier.address.languageid', // e.g. en for English
	 *    6 => supplier.address.city', // e.g. Berlin
	 *  ),
	 * @return array Associative list of domains as keys ("item" is special for the supplier itself) and a list of
	 *    positions and the domain item keys as values.
	 */
	protected function getDefaultMapping() : array
	{
		return array(
			'item' => array(
				0 => 'supplier.code',
				1 => 'supplier.label',
				2 => 'supplier.status',
			),
			'text' => array(
				3 => 'text.languageid',
				4 => 'text.type',
				5 => 'text.content',
			),
			'media' => array(
				6 => 'media.type',
				7 => 'media.url',
			),
			'address' => array(
				8 => 'supplier.address.languageid',
				9 => 'supplier.address.countryid',
				10 => 'supplier.address.city',
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
	 * Returns the processor object for saving the supplier related information
	 *
	 * @param array $mappings Associative list of processor types as keys and index/data mappings as values
	 * @return \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Iface Processor object
	 * @throws \LogicException If class can't be instantiated
	 */
	protected function getProcessors( array $mappings ) : \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Iface
	{
		unset( $mappings['item'] );

		$context = $this->context();
		$config = $context->config();
		$object = new \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Done( $context, [] );

		foreach( $mappings as $type => $mapping )
		{
			if( ctype_alnum( $type ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $type ), 400 );
			}

			$name = $config->get( 'controller/jobs/supplier/import/csv/processor/' . $type . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $name ), 400 );
			}

			$classname = '\\Aimeos\\Controller\\Jobs\\Common\\Supplier\\Import\\Csv\\Processor\\' . ucfirst( $type ) . '\\' . $name;
			$interface = \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Iface::class;

			$object = \Aimeos\Utils::create( $classname, [$context, $mapping, $object], $interface );
		}

		return $object;
	}
}
