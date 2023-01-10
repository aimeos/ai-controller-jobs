<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2013
 * @copyright Aimeos (aimeos.org), 2015-2023
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
	/** controller/jobs/index/rebuild/name
	 * Class name of the used catalog index rebuild scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Index\Rebuild\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Index\Rebuild\Myrebuild
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/index/rebuild/name = Myrebuild
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyRebuild"!
	 *
	 * @param string Last part of the class name
	 * @since 2014.03
	 */

	/** controller/jobs/index/rebuild/decorators/excludes
	 * Excludes decorators added by the "common" option from the catalog index rebuild controllers
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
	 *  controller/jobs/index/rebuild/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/index/rebuild/decorators/global
	 * @see controller/jobs/index/rebuild/decorators/local
	 */

	/** controller/jobs/index/rebuild/decorators/global
	 * Adds a list of globally available decorators only to the catalog index rebuild controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/index/rebuild/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/index/rebuild/decorators/excludes
	 * @see controller/jobs/index/rebuild/decorators/local
	 */

	/** controller/jobs/index/rebuild/decorators/local
	 * Adds a list of local decorators only to the catalog index rebuild controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Index\Rebuild\Decorator\*") around this job controller.
	 *
	 *  controller/jobs/index/rebuild/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Index\Rebuild\Decorator\Decorator2" only to this job
	 * controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/index/rebuild/decorators/excludes
	 * @see controller/jobs/index/rebuild/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Index rebuild' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Rebuilds the index for searching products' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->context();
		$context->locale()->setLanguageId( null )->setCurrencyId( null );
		$date = date( 'Y-m-d H:i:s' );

		\Aimeos\MShop::create( $context, 'index' )->rebuild()->cleanup( $date );
	}
}
