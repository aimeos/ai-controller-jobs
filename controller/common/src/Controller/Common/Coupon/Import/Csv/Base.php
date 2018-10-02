<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Coupon\Import\Csv;


/**
 * Common class for CSV coupon import job controllers and processors.
 *
 * @package Controller
 * @subpackage Common
 */
class Base
	extends \Aimeos\Controller\Jobs\Base
{
	/**
	 * Returns the coupon code items for the given codes
	 *
	 * @param array $codes List of coupon codes
	 * @return array Associative list of coupon codes as key and coupon code items as value
	 */
	protected function getCouponCodeItems( array $codes )
	{
		$result = [];
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'coupon/code' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'coupon.code.code', $codes ) );
		$search->setSlice( 0, count( $codes ) );

		foreach( $manager->searchItems( $search ) as $item ) {
			$result[ $item->getCode() ] = $item;
		}

		return $result;
	}


	/**
	 * Returns the rows from the CSV file up to the maximum count
	 *
	 * @param \Aimeos\MW\Container\Content\Iface $content CSV content object
	 * @param integer $maxcnt Maximum number of rows that should be retrieved at once
	 * @param integer $codePos Column position which contains the unique coupon code (starting from 0)
	 * @return array List of arrays with coupon codes as keys and list of values from the CSV file
	 */
	protected function getData( \Aimeos\MW\Container\Content\Iface $content, $maxcnt, $codePos )
	{
		$count = 0;
		$data = [];

		while( $content->valid() && $count++ < $maxcnt )
		{
			$row = $content->current();
			$data[ $row[$codePos] ] = $row;
			$content->next();
		}

		return $data;
	}


	/**
	 * Returns the default mapping for the CSV fields to the domain item keys
	 *
	 * Example:
	 *  'item' => array(
	 *  	0 => 'coupon.code.code', // e.g. letters and digits
	 *  	1 => 'coupon.code.count', // number of time the code is available
	 *  ),
	 *
	 * @return array Associative list of domains as keys and a list of positions and the domain item keys as values
	 */
	protected function getDefaultMapping()
	{
		return array(
			'code' => array(
				0 => 'coupon.code.code',
				1 => 'coupon.code.count',
				2 => 'coupon.code.datestart',
				3 => 'coupon.code.dateend',
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
	protected function getMappedChunk( array &$data, array $mapping )
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
	 * Returns the processor object for saving the coupon related information
	 *
	 * @param array $mappings Associative list of processor types as keys and index/data mappings as values
	 * @return \Aimeos\Controller\Common\Coupon\Import\Csv\Processor\Iface Processor object
	 */
	protected function getProcessors( array $mappings )
	{
		$context = $this->getContext();
		$config = $context->getConfig();
		$object = new \Aimeos\Controller\Common\Coupon\Import\Csv\Processor\Done( $context, [] );

		foreach( $mappings as $type => $mapping )
		{
			if( ctype_alnum( $type ) === false )
			{
				$classname = is_string($type) ? '\\Aimeos\\Controller\\Common\\Coupon\\Import\\Csv\\Processor\\' . $type : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$name = $config->get( 'controller/common/coupon/import/csv/processor/' . $type . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false )
			{
				$classname = is_string($name) ? '\\Aimeos\\Controller\\Common\\Coupon\\Import\\Csv\\Processor\\' . $type . '\\' . $name : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$classname = '\\Aimeos\\Controller\\Common\\Coupon\\Import\\Csv\\Processor\\' . ucfirst( $type ) . '\\' . $name;

			if( class_exists( $classname ) === false ) {
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Class "%1$s" not found', $classname ) );
			}

			$object = new $classname( $context, $mapping, $object );

			\Aimeos\MW\Common\Base::checkClass( '\\Aimeos\\Controller\\Common\\Coupon\\Import\\Csv\\Processor\\Iface', $object );
		}

		return $object;
	}
}
