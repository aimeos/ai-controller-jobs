<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2022
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

		$search = $manager->filter();
		$expr = array(
			$search->compare( '==', 'media.siteid', $context->locale()->getSiteId() ),
			$search->compare( '==', 'media.domain', ['attribute', 'catalog', 'product', 'service', 'supplier'] ),
			$search->compare( '=~', 'media.mimetype', 'image/' ),
		);
		$search->setConditions( $search->and( $expr ) );
		$search->setSortations( array( $search->sort( '+', 'media.id' ) ) );

		$start = 0;

		$fcn = function( \Aimeos\MShop\ContextIface $context, \Aimeos\Map $items ) {
			$this->rescale( $context, $items );
		};

		do
		{
			$search->slice( $start );
			$items = $manager->search( $search );

			if( !$items->isEmpty() ) {
				$process->start( $fcn, [$context, $items] );
			}

			$count = count( $items );
			$start += $count;
		}
		while( $count === $search->getLimit() );

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
		$cntl = \Aimeos\Controller\Common\Media\Factory::create( $context );

		/** controller/jobs/media/scale/force
		 * Enforce rescaling all images
		 *
		 * By default, all images are rescaled when executing the job controller.
		 * You can limit scaling to new images only (if mtime of the file is newer
		 * than the mtime of the media record) by setting this configuration option
		 * to false or 0
		 *
		 * @param bool True to rescale all images, false for new ones only
		 * @category Developer
		 * @category User
		 * @since 2019.10
		 */
		$force = $context->config()->get( 'controller/jobs/media/scale/force', true );

		foreach( $items as $item )
		{
			try {
				$manager->save( $cntl->scale( $item, $force ) );
			} catch( \Exception $e ) {
				$msg = sprintf( 'Scaling media item "%1$s" failed: %2$s', $item->getId(), $e->getMessage() );
				$logger->error( $msg, 'media/scale' );
			}
		}
	}
}
