<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020-2023
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
	/** controller/jobs/xml/import/name
	 * Class name of the used product suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Xml\Import\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Xml\Import\Myxml
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/xml/import/name = Myxml
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyXml"!
	 *
	 * @param string Last part of the class name
	 * @since 2019.04
	 */

	/** controller/jobs/xml/import/decorators/excludes
	 * Excludes decorators added by the "common" option from the product import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to remove a decorator added via
	 * "controller/jobs/common/decorators/default" before they are wrapped
	 * around the job controller.
	 *
	 *  controller/jobs/xml/import/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/xml/import/decorators/global
	 * @see controller/jobs/xml/import/decorators/local
	 */

	/** controller/jobs/xml/import/decorators/global
	 * Adds a list of globally available decorators only to the product import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/xml/import/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/xml/import/decorators/excludes
	 * @see controller/jobs/xml/import/decorators/local
	 */

	/** controller/jobs/xml/import/decorators/local
	 * Adds a list of local decorators only to the product import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Xml\Import\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/xml/import/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Xml\Import\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/xml/import/decorators/excludes
	 * @see controller/jobs/xml/import/decorators/global
	 */


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

		\Aimeos\Controller\Jobs::create( $context, $aimeos, 'customer/group/import/xml' )->run();
		\Aimeos\Controller\Jobs::create( $context, $aimeos, 'customer/import/xml' )->run();
		\Aimeos\Controller\Jobs::create( $context, $aimeos, 'attribute/import/xml' )->run();
		\Aimeos\Controller\Jobs::create( $context, $aimeos, 'product/import/xml' )->run();
		\Aimeos\Controller\Jobs::create( $context, $aimeos, 'supplier/import/xml' )->run();
		\Aimeos\Controller\Jobs::create( $context, $aimeos, 'catalog/import/xml' )->run();

		\Aimeos\Controller\Jobs::create( $context, $aimeos, 'index/rebuild' )->run();

		$context->cache()->deleteByTags( ['product'] );

		$logger->info( 'Finished XML import', 'import/xml' );
	}
}
