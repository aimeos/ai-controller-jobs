<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Coupon\Import\Csv\Code;


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
	public function getName()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Coupon code import CSV' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Imports new and updates existing coupon code from CSV files' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$total = $errors = 0;
		$context = $this->getContext();
		$config = $context->getConfig();
		$logger = $context->getLogger();
		$mappings = $this->getDefaultMapping();


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
		$mappings = $config->get( 'controller/jobs/coupon/import/csv/code/mapping', $mappings );


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


		try
		{
			$processor = $this->getProcessors( $mappings );
			$codePos = $this->getCodePosition( $mappings['code'] );
			$fs = $context->getFileSystemManager()->get( 'fs-import' );
			$dir = 'couponcode/' . $context->getLocale()->getSite()->getCode();

			if( $fs->isDir( $dir ) === false ) {
				return;
			}

			foreach( $fs->scan( $dir ) as $filename )
			{
				if( $filename == '.' || $filename == '..' ) {
					continue;
				}

				list( $couponId, ) = explode( '.', $filename );
				$container = $this->getContainer( $fs->readf( $dir . '/' . $filename ) );

				$msg = sprintf( 'Started coupon import from "%1$s" (%2$s)', $filename, __CLASS__ );
				$logger->log( $msg, \Aimeos\MW\Logger\Base::NOTICE );

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

						$msg = 'Imported coupon lines from "%1$s": %2$d/%3$d (%4$s)';
						$logger->log( sprintf( $msg, $filename, $chunkcnt - $errcnt, $chunkcnt, __CLASS__ ), \Aimeos\MW\Logger\Base::NOTICE );

						$errors += $errcnt;
						$total += $chunkcnt;
						unset( $items, $data );
					}
				}

				$container->close();
				$fs->rm( $dir . '/' . $filename );

				$msg = 'Finished coupon import: %1$d successful, %2$s errors, %3$s total (%4$s)';
				$logger->log( sprintf( $msg, $total - $errors, $errors, $total, __CLASS__ ), \Aimeos\MW\Logger\Base::NOTICE );
			}
		}
		catch( \Exception $e )
		{
			$logger->log( 'Coupon import error: ' . $e->getMessage() );
			$logger->log( $e->getTraceAsString() );

			throw $e;
		}
	}


	/**
	 * Returns the position of the "coupon.code" column from the coupon item mapping
	 *
	 * @param array $mapping Mapping of the "item" columns with position as key and code as value
	 * @return integer Position of the "coupon.code" column
	 * @throws \Aimeos\Controller\Jobs\Exception If no mapping for "coupon.code.code" is found
	 */
	protected function getCodePosition( array $mapping )
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
	protected function getContainer( $filepath )
	{
		$config = $this->getContext()->getConfig();

		/** controller/jobs/coupon/import/csv/code/container/type
		 * Name of the container type to read the data from
		 *
		 * The container type tells the importer how it should retrieve the data.
		 * There are currently three container types that support the necessary
		 * CSV content:
		 * * File (plain)
		 * * Zip
		 * * PHPExcel
		 *
		 * '''Note:''' For the PHPExcel container, you need to install the
		 * "ai-container" extension.
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
		 * * CSV
		 * * PHPExcel
		 *
		 * '''Note:''' for the PHPExcel content type, you need to install the
		 * "ai-container" extension.
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
	 * @return integer Number of coupons that couldn't be imported
	 * @throws \Aimeos\Controller\Jobs\Exception
	 */
	protected function import( array $items, array $data, $couponId,
		\Aimeos\Controller\Common\Coupon\Import\Csv\Processor\Iface $processor )
	{
		$errors = 0;
		$context = $this->getContext();
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'coupon/code' );

		foreach( $data as $code => $list )
		{
			$manager->begin();

			try
			{
				if( isset( $items[$code] ) ) {
					$item = $items[$code];
				} else {
					$item = $manager->createItem();
				}

				$item->setParentId( $couponId );
				$list = $processor->process( $item, $list );

				$manager->commit();
			}
			catch( \Exception $e )
			{
				$manager->rollback();

				$msg = sprintf( 'Unable to import coupon with code "%1$s": %2$s', $code, $e->getMessage() );
				$context->getLogger()->log( $msg );

				$errors++;
			}
		}

		return $errors;
	}
}
