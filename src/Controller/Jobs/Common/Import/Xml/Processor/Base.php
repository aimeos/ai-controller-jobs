<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2024
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Import\Xml\Processor;


/**
 * Abstract class with common methods for all XML import processors
 *
 * @package Controller
 * @subpackage Common
 */
abstract class Base
{
	use \Aimeos\Controller\Jobs\Common\Import\Traits;


	private \Aimeos\MShop\ContextIface $context;


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context )
	{
		$this->context = $context;
	}


	/**
	 * Clean up and store data.
	 */
	public function finish()
	{
		$this->saveTypes();
	}


	/**
	 * Returns the context item
	 *
	 * @return \Aimeos\MShop\ContextIface Context object
	 */
	protected function context() : \Aimeos\MShop\ContextIface
	{
		return $this->context;
	}
}
