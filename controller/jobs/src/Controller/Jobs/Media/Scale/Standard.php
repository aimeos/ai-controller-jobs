<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
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
	public function getName()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Rescale product images' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Rescales product images to the new sizes' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->getContext();
		$process = $context->getProcess();
		$manager = \Aimeos\MShop::create( $context, 'media' );

		$search = $manager->createSearch();
		$expr = array(
			$search->compare( '==', 'media.domain', 'product' ),
			$search->compare( '=~', 'media.mimetype', 'image/' ),
		);
		$search->setConditions( $search->combine( '&&', $expr ) );
		$search->setSortations( array( $search->sort( '+', 'media.id' ) ) );

		$start = 0;

		$fcn = function( array $items ) {
			$this->rescale( $items );
		};

		do
		{
			$search->setSlice( $start );
			$items = $manager->searchItems( $search );

			$context->__sleep();
			$process->start( $fcn, [$items] );

			$count = count( $items );
			$start += $count;
		}
		while( $count === $search->getSliceSize() );

		$process->wait();
	}


	/**
	 * Recreates the preview images for the given media items
	 *
	 * @param \Aimeos\MShop\Media\Item\Iface[] $items List of media items
	 */
	protected function rescale( array $items )
	{
		$context = $this->getContext();
		$manager = \Aimeos\MShop::create( $context, 'media' );
		$cntl = \Aimeos\Controller\Common\Media\Factory::create( $context );

		$fs = $context->getFileSystemManager()->get( 'fs-media' );
		$is = ( $fs instanceof \Aimeos\MW\Filesystem\MetaIface ? true : false );
		$force = $context->getConfig()->get( 'controller/jobs/media/scale/standard/force', true );

		foreach( $items as $item )
		{
			try
			{
				if( $is && date( 'Y-m-d H:i:s', $fs->time( $item->getUrl() ) ) > $item->getTimeModified() || $force ) {
					$manager->saveItem( $cntl->scale( $item ) );
				}
			}
			catch( \Exception $e )
			{
				$msg = sprintf( 'Scaling media item "%1$s" failed: %2$s', $item->getId(), $e->getMessage() );
				$context->getLogger()->log( $msg );
			}
		}
	}
}
