<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2023
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Media\Scale;


/**
 * Image rescale job controller.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/media/scale/name
	 * Class name of the used media scale job controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Media\Scale\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Media\Scale\Myscaler
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/media/scale/name = Myscaler
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "Myscaler"!
	 *
	 * @param string Last part of the class name
	 * @since 2017.01
	 */

	/** controller/jobs/media/scale/decorators/excludes
	 * Excludes decorators added by the "common" option from the media scale controllers
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
	 *  controller/jobs/media/scale/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/media/scale/decorators/global
	 * @see controller/jobs/media/scale/decorators/local
	 */

	/** controller/jobs/media/scale/decorators/global
	 * Adds a list of globally available decorators only to the media scale controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/media/scale/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/media/scale/decorators/excludes
	 * @see controller/jobs/media/scale/decorators/local
	 */

	/** controller/jobs/media/scale/decorators/local
	 * Adds a list of local decorators only to the media scale controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Media\Scale\Decorator\*") around this job controller.
	 *
	 *  controller/jobs/media/scale/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Media\Scale\Decorator\Decorator2" only to this job
	 * controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/media/scale/decorators/excludes
	 * @see controller/jobs/media/scale/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Rescale product images' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Rescales product images to the new sizes' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->context();
		$process = $context->process();
		$manager = \Aimeos\MShop::create( $context, 'media' );

		$search = $manager->filter()->order( 'media.id' );
		$search->add( $search->and( [
			$search->compare( '==', 'media.siteid', $context->locale()->getSiteId() ),
			$search->compare( '==', 'media.domain', ['attribute', 'catalog', 'product', 'service', 'supplier'] ),
			$search->compare( '=~', 'media.mimetype', 'image/' ),
		] ) );

		$fcn = function( \Aimeos\MShop\ContextIface $context, \Aimeos\Map $items ) {
			$this->rescale( $context, $items );
		};

		while( !( $items = $manager->search( ( clone $search )->add( 'media.id', '>', $lastId ?? 0 ) ) )->isEmpty() )
		{
			$process->start( $fcn, [$context, $items] );
			$lastId = $items->last()->getId();
		}

		$process->wait();

		$context->cache()->clear();
	}


	/**
	 * Recreates the preview images for the given media items
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param \Aimeos\Map $items List of media items implementing \Aimeos\MShop\Media\Item\Iface
	 */
	protected function rescale( \Aimeos\MShop\ContextIface $context, \Aimeos\Map $items )
	{
		$logger = $context->logger();
		$manager = \Aimeos\MShop::create( $context, 'media' );

		/** controller/jobs/media/scale/force
		 * Enforce rescaling all images
		 *
		 * By default, all images are rescaled when executing the job controller.
		 * You can limit scaling to new images only (if mtime of the file is newer
		 * than the mtime of the media record) by setting this configuration option
		 * to false or 0
		 *
		 * @param bool True to rescale all images, false for new ones only
		 * @since 2019.10
		 */
		$force = $context->config()->get( 'controller/jobs/media/scale/force', true );

		foreach( $items as $item )
		{
			try {
				$manager->save( $manager->scale( $item, $force ) );
			} catch( \Exception $e ) {
				$msg = sprintf( 'Scaling media item "%1$s" failed: %2$s', $item->getId(), $e->getMessage() );
				$logger->error( $msg, 'media/scale' );
			}
		}
	}
}
