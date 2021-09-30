<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor;

use \Aimeos\MW\Logger\Base as Log;


/**
 * Abstract class with common methods for all CSV import processors
 *
 * @package Controller
 * @subpackage Common
 */
abstract class Base
	extends \Aimeos\Controller\Common\Product\Import\Csv\Base
{
	private $context;
	private $mapping;
	private $object;
	private $types = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface $object Decorated processor
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context, array $mapping,
		\Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface $object = null )
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

		foreach( $this->types as $path => $list )
		{
			$manager = \Aimeos\MShop::create( $this->context, $path );
			$prefix = str_replace( '/', '.', $path );

			foreach( $list as $domain => $codes )
			{
				$manager->begin();

				try
				{
					$search = $manager->filter()->slice( 0, 10000 );
					$expr = [
						$search->compare( '==', $prefix . '.domain', $domain ),
						$search->compare( '==', $prefix . '.code', $codes )
					];
					$search->setConditions( $search->and( $expr ) );

					$types = $items = [];

					foreach( $manager->search( $search ) as $item ) {
						$types[] = $item->getCode();
					}

					foreach( array_diff( $codes, $types ) as $code ) {
						$items[] = $manager->create()->setDomain( $domain )->setCode( $code )->setLabel( $code );
					}

					$manager->save( $items, false );
					$manager->commit();
				}
				catch( \Exception $e )
				{
					$manager->rollback();

					$msg = 'Error saving types: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString();
					$this->context->getLogger()->log( $msg, Log::ERR, 'import/csv/product' );
				}
			}
		}
	}


	/**
	 * Registers a used type which is going to be saved if it doesn't exist yet
	 *
	 * @param string $path Manager path, e.g. "product/lists/type"
	 * @param string $domain Domain name the type belongs to, e.g. "attribute"
	 * @param string $code Type code
	 * @return \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface Same object for fluent interface
	 */
	protected function addType( string $path, string $domain, string $code ) : Iface
	{
		$this->types[$path][$domain][$code] = $code;
		return $this;
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
	 * @return \Aimeos\MShop\Context\Item\Iface Context object
	 */
	protected function getContext() : \Aimeos\MShop\Context\Item\Iface
	{
		return $this->context;
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
	 * @return \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface Processor object
	 * @throws \Aimeos\Controller\Jobs\Exception If no processor object is available
	 */
	protected function getObject() : \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface
	{
		if( $this->object === null ) {
			throw new \Aimeos\Controller\Jobs\Exception( 'No processor object available' );
		}

		return $this->object;
	}
}
