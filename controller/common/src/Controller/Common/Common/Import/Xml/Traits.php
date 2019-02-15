<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
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
	private $processors = [];


	/**
	 * Returns the processor object for adding the product related information
	 *
	 * @param string $type Type of the processor
	 * @return \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface Processor object
	 */
	protected function getProcessor( $type )
	{
		if( !isset( $this->processors[$type] ) ) {
			$this->processors[$type] = $this->createProcessor( $type );
		}

		return $this->processors[$type];
	}


	/**
	 * Creates a new processor object of the given type
	 *
	 * @param string $type Type of the processor
	 * @return \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface Processor object
	 * @throws \Aimeos\Controller\Common\Exception If type is invalid or processor isn't found
	 */
	protected function createProcessor( $type )
	{
		$context = $this->getContext();
		$config = $context->getConfig();

		if( ctype_alnum( $type ) === false )
		{
			$classname = is_string( $type ) ? '\\Aimeos\\Controller\\Common\\Common\\Import\\Xml\\Processor\\' . $type : '<not a string>';
			throw new \Aimeos\Controller\Common\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
		}

		$name = $config->get( 'controller/common/product/import/xml/processor/' . $type . '/name', 'Standard' );

		if( ctype_alnum( $name ) === false )
		{
			$classname = is_string( $name ) ? '\\Aimeos\\Controller\\Common\\Common\\Import\\Xml\\Processor\\' . $type . '\\' . $name : '<not a string>';
			throw new \Aimeos\Controller\Common\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
		}

		$classname = '\\Aimeos\\Controller\\Common\\Common\\Import\\Xml\\Processor\\' . ucfirst( $type ) . '\\' . $name;

		if( class_exists( $classname ) === false ) {
			throw new \Aimeos\Controller\Common\Exception( sprintf( 'Class "%1$s" not found', $classname ) );
		}

		$iface = \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface::class;
		return \Aimeos\MW\Common\Base::checkClass( $iface, new $classname( $context ) );
	}
}
