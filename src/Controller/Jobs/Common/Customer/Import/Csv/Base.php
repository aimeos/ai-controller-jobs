<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Customer\Import\Csv;


/**
 * Common class for CSV customer import job controllers and processors.
 *
 * @package Controller
 * @subpackage Common
 */
class Base
	extends \Aimeos\Controller\Jobs\Base
{
	private array $checks;
	private bool $html;


	/**
	 * Initializes the object.
	 *
	 * @param \Aimeos\MShop\ContextIface $context MShop context object
	 * @param \Aimeos\Bootstrap $aimeos \Aimeos\Bootstrap main object
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context, \Aimeos\Bootstrap $aimeos )
	{
		parent::__construct( $context, $aimeos );

		$this->checks = (array) $context->config()->get( 'controller/jobs/customer/import/csv/checks', [] );
		$this->html = (bool) $context->config()->get( 'controller/jobs/customer/import/csv/html', false );
	}


	/**
	 * Checks if an entry can be used for updating the item
	 *
	 * @param array $entry Associative list of key/value pairs from the mapping
	 * @throws \Aimeos\Controller\Jobs\Exception If the check fails
	 */
	protected function check( array $entry )
	{
		foreach( $this->checks as $code => $regex )
		{
			$value = $this->val( $entry, $code );

			if( preg_match( $regex, (string) $value ) !== 1 )
			{
				$msg = sprintf( 'Checking "%1$s" value "%2$s" against "%3$s" failed', $code, $value, $regex );
				throw new \Aimeos\Controller\Jobs\Exception( $msg );
			}
		}

		if( !$this->html )
		{
			foreach( $entry as $key => $value )
			{
				if( is_string( $value ) && strpos( $value, '<' ) !== false ) {
					throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'HTML tags are not allowed in field "%1$s"', $key ) );
				}
			}
		}
	}


	/**
	 * Returns the rows from the CSV file up to the maximum count
	 *
	 * @param resource $fh File handle to CSV file
	 * @param int $maxcnt Maximum number of rows that should be retrieved at once
	 * @param int $codePos Column position which contains the unique customer code (starting from 0)
	 * @return array List of arrays with customer codes as keys and list of values from the CSV file
	 */
	protected function getData( $fh, int $maxcnt, int $codePos ) : array
	{
		$data = [];
		$count = 0;

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
	 *      0 => 'customer.code', // e.g. unique EAN code
	 *      1 => 'customer.label', // UTF-8 encoded text, also used as customer name
	 *      2 => 'customer.salutation',
	 *      3 => 'customer.company',
	 *      4 => 'customer.vatid',
	 *      5 => 'customer.title',
	 *      6 => 'customer.firstname',
	 *      7 => 'customer.lastname',
	 *      8 => 'customer.address1',
	 *      9 => 'customer.address2',
	 *      10 => 'customer.address3',
	 *      11 => 'customer.postal',
	 *      12 => 'customer.city',
	 *      13 => 'customer.state',
	 *      14 => 'customer.languageid',
	 *      15 => 'customer.countryid',
	 *      16 => 'customer.telephone',
	 *      17 => 'customer.telefax',
	 *      18 => 'customer.mobile',
	 *      19 => 'customer.email',
	 *      20 => 'customer.website',
	 *      21 => 'customer.longitude',
	 *      22 => 'customer.latitude',
	 *      23 => 'customer.birthday',
	 *      24 => 'customer.status', // Status value (-2, -1, 0, 1)
	 *      25 => 'customer.groups',
	 *  ),
	 *	'address' => array(
	 *      26 => 'customer.address.salutation',
	 *      27 => 'customer.address.company',
	 *      28 => 'customer.address.vatid',
	 *      29 => 'customer.address.title',
	 *      30 => 'customer.address.firstname',
	 *      31 => 'customer.address.lastname',
	 *      32 => 'customer.address.address1',
	 *      33 => 'customer.address.address2',
	 *      34 => 'customer.address.address3',
	 *      35 => 'customer.address.postal',
	 *      36 => 'customer.address.city',
	 *      37 => 'customer.address.state',
	 *      38 => 'customer.address.languageid',
	 *      39 => 'customer.address.countryid',
	 *      40 => 'customer.address.telephone',
	 *      41 => 'customer.address.telefax',
	 *      42 => 'customer.address.mobile',
	 *      43 => 'customer.address.email',
	 *      44 => 'customer.address.website',
	 *      45 => 'customer.address.longitude',
	 *      46 => 'customer.address.latitude',
	 *      47 => 'customer.address.birthday',
	 *	),
	 *  'property' => array(
	 *      48 => 'customer.property.type',
	 *      49 => 'customer.property.languageid',
	 *      50 => 'customer.property.value',
	 *  ),
	 *
	 * @return array Associative list of domains as keys ("item" is special for the customer itself) and a list of
	 * 	positions and the domain item keys as values.
	 */
	protected function getDefaultMapping() : array
	{
		return array(
			'item' => array(
				0 => 'customer.code', // e.g. unique EAN code
				1 => 'customer.label', // UTF-8 encoded text, also used as customer name
				2 => 'customer.salutation',
				3 => 'customer.company',
				4 => 'customer.vatid',
				5 => 'customer.title',
				6 => 'customer.firstname',
				7 => 'customer.lastname',
				8 => 'customer.address1',
				9 => 'customer.address2',
				10 => 'customer.address3',
				11 => 'customer.postal',
				12 => 'customer.city',
				13 => 'customer.state',
				14 => 'customer.languageid',
				15 => 'customer.countryid',
				16 => 'customer.telephone',
				17 => 'customer.telefax',
				18 => 'customer.mobile',
				19 => 'customer.email',
				20 => 'customer.website',
				21 => 'customer.longitude',
				22 => 'customer.latitude',
				23 => 'customer.birthday',
				24 => 'customer.status', // Status value (-2, -1, 0, 1)
			),
			'group' => array(
				25 => 'customer.groups',
			),
			'address' => array(
				26 => 'customer.address.salutation',
				27 => 'customer.address.company',
				28 => 'customer.address.vatid',
				29 => 'customer.address.title',
				30 => 'customer.address.firstname',
				31 => 'customer.address.lastname',
				32 => 'customer.address.address1',
				33 => 'customer.address.address2',
				34 => 'customer.address.address3',
				35 => 'customer.address.postal',
				36 => 'customer.address.city',
				37 => 'customer.address.state',
				38 => 'customer.address.languageid',
				39 => 'customer.address.countryid',
				40 => 'customer.address.telephone',
				41 => 'customer.address.telefax',
				42 => 'customer.address.mobile',
				43 => 'customer.address.email',
				44 => 'customer.address.website',
				45 => 'customer.address.longitude',
				46 => 'customer.address.latitude',
				47 => 'customer.address.birthday',
			),
			'property' => array(
				48 => 'customer.property.type',
				49 => 'customer.property.languageid',
				50 => 'customer.property.value',
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
			$code = is_array( $key ) ? $key['_'] : $key;

			if( isset( $map[$idx][$code] ) ) {
				$idx++;
			}

			if( is_array( $key ) )
			{
				foreach( $key as $name => $val )
				{
					if( $name !== '_' ) {
						$map[$idx][$name] = $val;
					}
				}
			}

			if( isset( $data[$pos] ) ) {
				$map[$idx][$code] = $data[$pos];
			}
		}

		return $map;
	}


	/**
	 * Returns the processor object for saving the customer related information
	 *
	 * @param array $mappings Associative list of processor types as keys and index/data mappings as values
	 * @return \Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Iface Processor object
	 * @throws \LogicException If class can't be instantiated
	 */
	protected function getProcessors( array $mappings ) : \Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Iface
	{
		unset( $mappings['item'] );

		$context = $this->context();
		$config = $context->config();

		$object = new \Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Done( $context, [] );
		$interface = \Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Iface::class;

		foreach( $mappings as $type => $mapping )
		{
			if( ctype_alnum( $type ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $type ), 400 );
			}

			$name = $config->get( 'controller/jobs/customer/import/csv/processor/' . $type . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $name ), 400 );
			}

			$classname = '\\Aimeos\\Controller\\Jobs\\Common\\Customer\\Import\\Csv\\Processor\\' . ucfirst( $type ) . '\\' . $name;

			$object = \Aimeos\Utils::create( $classname, [$context, $mapping, $object], $interface );
		}

		return $object;
	}
}
