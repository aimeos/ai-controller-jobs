<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2024
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Common;


/**
 * Trait with methods to add new types
 *
 * @package Controller
 * @subpackage Jobs
 */

trait Types
{
	private array $typeMap = [];


	/**
	 * Returns the context item
	 *
	 * @return \Aimeos\MShop\ContextIface Context object
	 */
	abstract protected function context() : \Aimeos\MShop\ContextIface;


	/**
	 * Registers a used type which is going to be saved if it doesn't exist yet
	 *
	 * @param string $path Manager path, e.g. "product/lists/type"
	 * @param string $domain Domain name the type belongs to, e.g. "attribute"
	 * @param string $code Type code
	 * @return self Same object for method chaining
	 */
	protected function addType( string $path, string $domain, string $code ) : self
	{
		$this->typeMap[$path][$domain][$code] = $code;
		return $this;
	}


	/**
	 * Stores all types for which no type items exist yet
	 *
	 * @return self Same object for method chaining
	 */
	protected function saveTypes() : self
	{
		foreach( $this->typeMap as $path => $list )
		{
			$manager = \Aimeos\MShop::create( $this->context(), $path );
			$prefix = str_replace( '/', '.', $path );

			foreach( $list as $domain => $codes )
			{
				$manager->begin();

				try
				{
					$types = $items = [];
					$search = $manager->filter()
						->add( [$prefix . '.domain' => $domain, $prefix . '.code' => $codes] )
						->slice( 0, 10000 );

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
					$this->context()->logger()->error( $msg, 'import' );
				}
			}
		}

		return $this;
	}
}
