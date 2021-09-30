<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Coupon\Import\Csv\Code;

use \Aimeos\MW\Logger\Base as Log;


/**
 * Job controller for CSV coupon imports.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Common\Coupon\Import\Csv\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Coupon code import CSV' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Imports new and updates existing coupon code from CSV files' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$fcn = function( \Aimeos\MShop\Context\Item\Iface $context, \Aimeos\MW\Container\Iface $container, $couponId, $path ) {
			$this->process( $context, $container, $couponId, $path );
		};

		try
		{
			$context = $this->getContext();
			$process = $context->getProcess();
			$fs = $context->getFileSystemManager()->get( 'fs-import' );
			$dir = 'couponcode/' . $context->getLocale()->getSiteItem()->getCode();

			if( $fs->isDir( $dir ) === false ) {
				return;
			}

			foreach( $fs->scan( $dir ) as $filename )
			{
				if( $filename == '.' || $filename == '..' ) {
					continue;
				}

				$path = $dir . '/' . $filename;
				list( $couponId,) = explode( '.', $filename );
				$container = $this->getContainer( $fs->readf( $path ) );

				$process->start( $fcn, [$context, $container, $couponId, $path] );
			}

			$process->wait();
		}
		catch( \Exception $e )
		{
			$context->getLogger()->log( 'Coupon import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), Log::ERR, 'import/csv/coupon/code' );
			$this->mail( 'Coupon CSV import error', $e->getMessage() . "\n" . $e->getTraceAsString() );
			throw $e;
		}
	}


	/**
	 * Returns the position of the "coupon.code" column from the coupon item mapping
	 *
	 * @param array $mapping Mapping of the "item" columns with position as key and code as value
	 * @return int Position of the "coupon.code" column
	 * @throws \Aimeos\Controller\Jobs\Exception If no mapping for "coupon.code.code" is found
	 */
	protected function getCodePosition( array $mapping ) : int
	{
		foreach( $mapping as $pos => $key )
		{
			if( $key === 'coupon.code.code' ) {
				return $pos;
			}
		}

		throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'No "coupon.code.code" column in CSV mapping found' ) );
	}


	/**
	 * Opens and returns the container which includes the coupon data
	 *
	 * @param string $filepath Path to the container file
	 * @return \Aimeos\MW\Container\Iface Container object
	 */
	protected function getContainer( string $filepath ) : \Aimeos\MW\Container\Iface
	{
		$config = $this->getContext()->getConfig();

		/** controller/jobs/coupon/import/csv/code/container/type
		 * Name of the container type to read the data from
		 *
		 * The container type tells the importer how it should retrieve the data.
		 * There are currently three container types that support the necessary
		 * CSV content:
		 *
		 * * File (plain)
		 * * Zip
		 *
		 * @param string Container type name
		 * @since 2017.10
		 * @category Developer
		 * @category User
		 * @see controller/jobs/coupon/import/csv/code/container/content
		 * @see controller/jobs/coupon/import/csv/code/container/options
		 */
		$container = $config->get( 'controller/jobs/coupon/import/csv/code/container/type', 'File' );

		/** controller/jobs/coupon/import/csv/code/container/content
		 * Name of the content type inside the container to read the data from
		 *
		 * The content type must always be a CSV-like format and there are
		 * currently two format types that are supported:
		 *
		 * * CSV
		 *
		 * @param array Content type name
		 * @since 2017.10
		 * @category Developer
		 * @category User
		 * @see controller/jobs/coupon/import/csv/code/container/type
		 * @see controller/jobs/coupon/import/csv/code/container/options
		 */
		$content = $config->get( 'controller/jobs/coupon/import/csv/code/container/content', 'CSV' );

		/** controller/jobs/coupon/import/csv/code/container/options
		 * List of file container options for the coupon import files
		 *
		 * Some container/content type allow you to hand over additional settings
		 * for configuration. Please have a look at the article about
		 * {@link http://aimeos.org/docs/Developers/Utility/Create_and_read_files container/content files}
		 * for more information.
		 *
		 * @param array Associative list of option name/value pairs
		 * @since 2017.10
		 * @category Developer
		 * @category User
		 * @see controller/jobs/coupon/import/csv/code/container/content
		 * @see controller/jobs/coupon/import/csv/code/container/type
		 */
		$options = $config->get( 'controller/jobs/coupon/import/csv/code/container/options', [] );

		return \Aimeos\MW\Container\Factory::getContainer( $filepath, $container, $content, $options );
	}


	/**
	 * Imports the CSV data and creates new coupons or updates existing ones
	 *
	 * @param \Aimeos\MShop\Coupon\Item\Code\Iface[] $items List of coupons code items
	 * @param array $data Associative list of import data as index/value pairs
	 * @param string $couponId ID of the coupon item the coupon code should be added to
	 * @param \Aimeos\Controller\Common\Coupon\Import\Csv\Processor\Iface $processor Processor object
	 * @return int Number of coupons that couldn't be imported
	 * @throws \Aimeos\Controller\Jobs\Exception
	 */
	protected function import( array $items, array $data, string $couponId,
		\Aimeos\Controller\Common\Coupon\Import\Csv\Processor\Iface $processor ) : int
	{
		$errors = 0;
		$context = $this->getContext();
		$manager = \Aimeos\MShop::create( $context, 'coupon/code' );

		foreach( $data as $code => $list )
		{
			$manager->begin();

			try
			{
				if( isset( $items[$code] ) ) {
					$item = $items[$code];
				} else {
					$item = $manager->create();
				}

				$item->setParentId( $couponId );
				$list = $processor->process( $item, $list );

				$manager->commit();
			}
			catch( \Exception $e )
			{
				$manager->rollback();

				$str = 'Unable to import coupon with code "%1$s": %2$s';
				$msg = sprintf( $str, $code, $e->getMessage() . "\n" . $e->getTraceAsString() );
				$context->getLogger()->log( $msg, Log::ERR, 'import/csv/coupon/code' );

				$errors++;
			}
		}

		return $errors;
	}


	/**
	 * Imports content from the given container
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object
	 * @param \Aimeos\MW\Container\Iface $container File container object
	 * @param string $couponId Unique coupon ID the codes should be imported for
	 * @param string $path Path to the container file
	 */
	protected function process( \Aimeos\MShop\Context\Item\Iface $context, \Aimeos\MW\Container\Iface $container, string $couponId, string $path )
	{
		$total = $errors = 0;
		$config = $context->getConfig();
		$logger = $context->getLogger();

		/** controller/jobs/coupon/import/csv/code/mapping
		 * List of mappings between the position in the CSV file and item keys
		 *
		 * This configuration setting overwrites the shared option
		 * "controller/common/coupon/import/csv/mapping" if you need a
		 * specific setting for the job controller. Otherwise, you should
		 * use the shared option for consistency.
		 *
		 * @param array Associative list of processor names and lists of key/position pairs
		 * @since 2017.10
		 * @category Developer
		 * @see controller/jobs/coupon/import/csv/code/skip-lines
		 * @see controller/jobs/coupon/import/csv/code/max-size
		 */
		$mappings = $config->get( 'controller/jobs/coupon/import/csv/code/mapping', $this->getDefaultMapping() );

		/** controller/jobs/coupon/import/csv/code/max-size
		 * Maximum number of CSV rows to import at once
		 *
		 * It's more efficient to read and import more than one row at a time
		 * to speed up the import. Usually, the bigger the chunk that is imported
		 * at once, the less time the importer will need. The downside is that
		 * the amount of memory required by the import process will increase as
		 * well. Therefore, it's a trade-off between memory consumption and
		 * import speed.
		 *
		 * @param integer Number of rows
		 * @since 2017.10
		 * @category Developer
		 * @see controller/jobs/coupon/import/csv/code/skip-lines
		 * @see controller/jobs/coupon/import/csv/code/mapping
		 */
		$maxcnt = (int) $config->get( 'controller/jobs/coupon/import/csv/code/max-size', 1000 );

		/** controller/jobs/coupon/import/csv/code/skip-lines
		 * Number of rows skipped in front of each CSV files
		 *
		 * Some CSV files contain header information describing the content of
		 * the column values. These data is for informational purpose only and
		 * can't be imported into the database. Using this option, you can
		 * define the number of lines that should be left out before the import
		 * begins.
		 *
		 * @param integer Number of rows
		 * @since 2015.08
		 * @category Developer
		 * @see controller/jobs/coupon/import/csv/code/mapping
		 * @see controller/jobs/coupon/import/csv/code/max-size
		 */
		$skiplines = (int) $config->get( 'controller/jobs/coupon/import/csv/code/skip-lines', 0 );


		$msg = sprintf( 'Started coupon import from "%1$s" (%2$s)', $path, __CLASS__ );
		$logger->log( $msg, Log::NOTICE, 'import/csv/coupon/code' );

		$processor = $this->getProcessors( $mappings );
		$codePos = $this->getCodePosition( $mappings['code'] );

		foreach( $container as $content )
		{
			for( $i = 0; $i < $skiplines; $i++ ) {
				$content->next();
			}

			while( ( $data = $this->getData( $content, $maxcnt, $codePos ) ) !== [] )
			{
				$items = $this->getCouponCodeItems( array_keys( $data ) );
				$errcnt = $this->import( $items, $data, $couponId, $processor );
				$chunkcnt = count( $data );

				$str = 'Imported coupon lines from "%1$s": %2$d/%3$d (%4$s)';
				$msg = sprintf( $str, $path, $chunkcnt - $errcnt, $chunkcnt, __CLASS__ );
				$logger->log( $msg, Log::NOTICE, 'import/csv/coupon/code' );

				$errors += $errcnt;
				$total += $chunkcnt;
				unset( $items, $data );
			}
		}

		$str = 'Finished coupon import: %1$d successful, %2$s errors, %3$s total (%4$s)';
		$msg = sprintf( $str, $total - $errors, $errors, $total, __CLASS__ );
		$logger->log( $msg, Log::INFO, 'import/csv/coupon/code' );

		$container->close();
		$context->getFileSystemManager()->get( 'fs-import' )->rm( $path );
	}
}
