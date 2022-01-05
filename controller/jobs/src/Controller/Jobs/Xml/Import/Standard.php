<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020-2022
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Xml\Import;


/**
 * Job controller for XML imports
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
		return $this->context()->translate( 'controller/jobs', 'All XML import' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Executes all XML importers and rebuild the index' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$aimeos = $this->getAimeos();
		$context = $this->context();
		$logger = $context->logger();

		$logger->info( 'Started XML import', 'import/xml' );

		\Aimeos\Controller\Jobs\Customer\Group\Import\Xml\Factory::create( $context, $aimeos )->run();
		\Aimeos\Controller\Jobs\Customer\Import\Xml\Factory::create( $context, $aimeos )->run();
		\Aimeos\Controller\Jobs\Attribute\Import\Xml\Factory::create( $context, $aimeos )->run();
		\Aimeos\Controller\Jobs\Product\Import\Xml\Factory::create( $context, $aimeos )->run();
		\Aimeos\Controller\Jobs\Supplier\Import\Xml\Factory::create( $context, $aimeos )->run();
		\Aimeos\Controller\Jobs\Catalog\Import\Xml\Factory::create( $context, $aimeos )->run();

		\Aimeos\Controller\Jobs\Index\Rebuild\Factory::create( $context, $aimeos )->run();

		$context->cache()->deleteByTags( ['product'] );

		$logger->info( 'Finished XML import', 'import/xml' );
	}
}
