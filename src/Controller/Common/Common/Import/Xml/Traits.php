<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2023
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Common\Import\Xml;


/**
 * Shared class for XML importers
 *
 * @package Controller
 * @subpackage Common
 */
trait Traits
{
	private array $processors = [];


	/**
	 * Returns the context item
	 *
	 * @return \Aimeos\MShop\ContextIface Context object
	 */
	abstract protected function context() : \Aimeos\MShop\ContextIface;


	/**
	 * Returns the processor object for adding the product related information
	 *
	 * @param string $type Type of the processor
	 * @return \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface Processor object
	 */
	protected function getProcessor( string $type ) : \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface
	{
		if( !isset( $this->processors[$type] ) ) {
			$this->processors[$type] = $this->createProcessor( $type );
		}

		return $this->processors[$type];
	}


	/**
	 * Returns the processor objects which have been used up to now
	 *
	 * @return \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface[] List of processor objects
	 */
	protected function getProcessors() : array
	{
		return $this->processors;
	}


	/**
	 * Creates a new processor object of the given type
	 *
	 * @param string $type Type of the processor
	 * @return \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface Processor object
	 * @throws \Aimeos\Controller\Common\Exception If type is invalid or processor isn't found
	 * @throws \LogicException If class can't be instantiated
	 */
	protected function createProcessor( string $type ) : \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface
	{
		$context = $this->context();
		$config = $context->config();
		$parts = explode( '/', $type );

		foreach( $parts as $part )
		{
			if( ctype_alnum( $part ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in processor type "%1$s"', $type ), 400 );
			}
		}

		$name = $config->get( 'controller/common/common/import/xml/processor/' . $type . '/name', 'Standard' );

		if( ctype_alnum( $name ) === false ) {
			throw new \LogicException( sprintf( 'Invalid characters in processor name "%1$s"', $name ), 400 );
		}

		$segment = str_replace( '/', '\\', ucwords( $type, '/' ) ) . '\\' . $name;
		$classname = '\\Aimeos\\Controller\\Common\\Common\\Import\\Xml\\Processor\\' . $segment;
		$interface = \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface::class;

		return \Aimeos\Utils::create( $classname, [$context], $interface );
	}
}
