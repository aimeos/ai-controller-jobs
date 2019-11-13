<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor;


/**
 * Abstract class with common methods for all XML import processors
 *
 * @package Controller
 * @subpackage Common
 */
abstract class Base
{
	private $context;
	private $types = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context )
	{
		$this->context = $context;
	}


	/**
	 * Stores all types for which no type items exist yet
	 */
	public function __destruct()
	{
		foreach( $this->types as $path => $list )
		{
			$manager = \Aimeos\MShop::create( $this->context, $path );
			$prefix = str_replace( '/', '.', $path );

			foreach( $list as $domain => $codes )
			{
				$manager->begin();

				try
				{
					$search = $manager->createSearch()->setSlice( 0, 10000 );
					$expr = [
						$search->compare( '==', $prefix . '.domain', $domain ),
						$search->compare( '==', $prefix . '.code', $codes )
					];
					$search->setConditions( $search->combine( '&&', $expr ) );

					$types = $items = [];

					foreach( $manager->searchItems( $search ) as $item ) {
						$types[] = $item->getCode();
					}

					foreach( array_diff( $codes, $types ) as $code ) {
						$items[] = $manager->createItem()->setDomain( $domain )->setCode( $code )->setLabel( $code );
					}

					$manager->saveItems( $items, false );
					$manager->commit();
				}
				catch( \Exception $e )
				{
					$manager->rollback();
					$this->context->getLogger()->log( 'Error saving types: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() );
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
	 */
	protected function addType( $path, $domain, $code )
	{
		$this->types[$path][$domain][$code] = $code;
	}


	/**
	 * Returns the context item
	 *
	 * @return \Aimeos\MShop\Context\Item\Iface Context object
	 */
	protected function getContext()
	{
		return $this->context;
	}
}
