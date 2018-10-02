<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2018
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv;


/**
 * Common trait for CSV product import job controllers and processors.
 *
 * @package Controller
 * @subpackage Common
 */
trait Traits
{
	private static $types = [];


	/**
	 * Returns the context item
	 *
	 * @return \Aimeos\MShop\Context\Item\Iface Context object
	 */
	abstract protected function getContext();


	/**
	 * Converts the CSV field data using the available converter objects
	 *
	 * @param array $convlist Associative list of CSV field indexes and converter objects
	 * @param array $data Associative list of product codes and lists of CSV field indexes and their data
	 * @return array Associative list of CSV field indexes and their converted data
	 */
	protected function convertData( array $convlist, array $data )
	{
		foreach( $convlist as $idx => $converter )
		{
			foreach( $data as $code => $list )
			{
				if( isset( $list[$idx] ) ) {
					$data[$code][$idx] = $converter->translate( $list[$idx] );
				}
			}
		}

		return $data;
	}


	/**
	 * Returns the cache object for the given type
	 *
	 * @param string $type Type of the cached data
	 * @param string|null Name of the cache implementation
	 * @return \Aimeos\Controller\Common\Product\Import\Csv\Cache\Iface Cache object
	 */
	protected function getCache( $type, $name = null )
	{
		$context = $this->getContext();
		$config = $context->getConfig();

		if( ctype_alnum( $type ) === false )
		{
			$classname = is_string($name) ? '\\Aimeos\\Controller\\Common\\Product\\Import\\Csv\\Cache\\' . $type : '<not a string>';
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
		}

		if( $name === null ) {
			$name = $config->get( 'controller/common/product/import/csv/cache/' . $type . '/name', 'Standard' );
		}

		if( ctype_alnum( $name ) === false )
		{
			$classname = is_string($name) ? '\\Aimeos\\Controller\\Common\\Product\\Import\\Csv\\Cache\\' . $type . '\\' . $name : '<not a string>';
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
		}

		$classname = '\\Aimeos\\Controller\\Common\\Product\\Import\\Csv\\Cache\\' . ucfirst( $type ) . '\\' . $name;

		if( class_exists( $classname ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Class "%1$s" not found', $classname ) );
		}

		$object = new $classname( $context );

		\Aimeos\MW\Common\Base::checkClass( '\\Aimeos\\Controller\\Common\\Product\\Import\\Csv\\Cache\\Iface', $object );

		return $object;
	}


	/**
	 * Returns the list of converter objects based on the given converter map
	 *
	 * @param array $convmap List of converter names for the values at the position in the CSV file
	 * @return array Associative list of positions and converter objects
	 */
	protected function getConverterList( array $convmap )
	{
		$convlist = [];

		foreach( $convmap as $idx => $name ) {
			$convlist[$idx] = \Aimeos\MW\Convert\Factory::createConverter( $name );
		}

		return $convlist;
	}


	/**
	 * Returns the rows from the CSV file up to the maximum count
	 *
	 * @param \Aimeos\MW\Container\Content\Iface $content CSV content object
	 * @param integer $maxcnt Maximum number of rows that should be retrieved at once
	 * @param integer $codePos Column position which contains the unique product code (starting from 0)
	 * @return array List of arrays with product codes as keys and list of values from the CSV file
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
	protected function getDefaultMapping()
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
	 * Returns the processor object for saving the product related information
	 *
	 * @param array $mappings Associative list of processor types as keys and index/data mappings as values
	 * @return \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface Processor object
	 */
	protected function getProcessors( array $mappings )
	{
		$context = $this->getContext();
		$config = $context->getConfig();
		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Done( $context, [] );

		foreach( $mappings as $type => $mapping )
		{
			if( ctype_alnum( $type ) === false )
			{
				$classname = is_string($type) ? '\\Aimeos\\Controller\\Common\\Product\\Import\\Csv\\Processor\\' . $type : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$name = $config->get( 'controller/common/product/import/csv/processor/' . $type . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false )
			{
				$classname = is_string($name) ? '\\Aimeos\\Controller\\Common\\Product\\Import\\Csv\\Processor\\' . $type . '\\' . $name : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$classname = '\\Aimeos\\Controller\\Common\\Product\\Import\\Csv\\Processor\\' . ucfirst( $type ) . '\\' . $name;

			if( class_exists( $classname ) === false ) {
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Class "%1$s" not found', $classname ) );
			}

			$object = new $classname( $context, $mapping, $object );

			\Aimeos\MW\Common\Base::checkClass( '\\Aimeos\\Controller\\Common\\Product\\Import\\Csv\\Processor\\Iface', $object );
		}

		return $object;
	}


	/**
	 * Returns the product items for the given codes
	 *
	 * @param array $codes List of product codes
	 * @param array $domains List of domains whose items should be fetched too
	 * @return array Associative list of product codes as key and product items as value
	 */
	protected function getProducts( array $codes, array $domains )
	{
		$result = [];
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'product' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'product.code', $codes ) );
		$search->setSlice( 0, count( $codes ) );

		foreach( $manager->searchItems( $search, $domains ) as $item ) {
			$result[ $item->getCode() ] = $item;
		}

		return $result;
	}


	/**
	 * Returns the ID of the type item with the given code
	 *
	 * @param string $path Item/manager path separated by slashes, e.g. "product/lists/type"
	 * @param string $domain Domain the type items needs to be from
	 * @param string $code Unique code of the type item
	 * @return string Unique ID of the type item
	 */
	protected function getTypeId( $path, $domain, $code )
	{
		if( !isset( self::$types[$path][$domain] ) )
		{
			$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), $path );
			$key = str_replace( '/', '.', $path );

			$search = $manager->createSearch();
			$search->setConditions( $search->compare( '==', $key . '.domain', $domain ) );

			foreach( $manager->searchItems( $search ) as $id => $item ) {
				self::$types[$path][$domain][ $item->getCode() ] = $id;
			}
		}

		if( !isset( self::$types[$path][$domain][$code] ) ) {
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'No type item for "%1$s/%2$s" in "%3$s" found', $domain, $code, $path ) );
		}

		return self::$types[$path][$domain][$code];
	}
}
