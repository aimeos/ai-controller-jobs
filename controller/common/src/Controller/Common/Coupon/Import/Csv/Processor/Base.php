<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Coupon\Import\Csv\Processor;


/**
 * Abstract class with common methods for all CSV import processors
 *
 * @package Controller
 * @subpackage Common
 */
class Base
	extends \Aimeos\Controller\Common\Coupon\Import\Csv\Base
{
	private $context;
	private $mapping;
	private $object;


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 * @param array $mapping Associative list of field position in CSV as key and domain item key as value
	 * @param \Aimeos\Controller\Common\Coupon\Import\Csv\Processor\Iface $object Decorated processor
	 */
	public function __construct( \Aimeos\MShop\Context\Item\Iface $context, array $mapping,
		\Aimeos\Controller\Common\Coupon\Import\Csv\Processor\Iface $object = null )
	{
		$this->context = $context;
		$this->mapping = $mapping;
		$this->object = $object;
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


	/**
	 * Returns the mapping list
	 *
	 * @return array Associative list of field positions in CSV as keys and domain item keys as values
	 */
	protected function getMapping()
	{
		return $this->mapping;
	}


	/**
	 * Returns the decorated processor object
	 *
	 * @return \Aimeos\Controller\Common\Coupon\Import\Csv\Processor\Iface Processor object
	 * @throws \Aimeos\Controller\Jobs\Exception If no processor object is available
	 */
	protected function getObject()
	{
		if( $this->object === null ) {
			throw new \Aimeos\Controller\Jobs\Exception( 'No processor object available' );
		}

		return $this->object;
	}
}
