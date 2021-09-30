<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Common\Import;

use \Aimeos\MW\Logger\Base as Log;


/**
 * Shared class for XML importers
 *
 * @package Controller
 * @subpackage Common
 */
trait Traits
{
	private $typeMap = [];


	abstract protected function getContext() : \Aimeos\MShop\Context\Item\Iface;


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
			$manager = \Aimeos\MShop::create( $this->getContext(), $path );
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
					$this->getContext()->getLogger()->log( $msg, Log::ERR, 'import' );
				}
			}
		}

		return $this;
	}
}
