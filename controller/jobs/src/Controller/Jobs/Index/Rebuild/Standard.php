<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2013
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Index\Rebuild;


/**
 * Rebuild catalog index job controller.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Index rebuild' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Rebuilds the index for searching products' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$timestamp = date( 'Y-m-d H:i:s' );
		$context = clone $this->getContext();

		$context->getLocale()->setLanguageId( null );
		$context->getLocale()->setCurrencyId( null );

		$manager = \Aimeos\MShop\Index\Manager\Factory::create( $context );

		$manager->rebuild();
		$manager->cleanup( $timestamp );
	}
}
