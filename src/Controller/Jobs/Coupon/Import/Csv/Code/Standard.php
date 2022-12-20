<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2022
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
	/** controller/jobs/coupon/import/csv/code/name
	 * Class name of the used coupon code import job controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Coupon\Import\Csv\Code\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Coupon\Import\Csv\Code\Mycsv
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/coupon/import/csv/code/name = Mycsv
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyCsv"!
	 *
	 * @param string Last part of the class name
	 * @since 2017.10
	 */

	/** controller/jobs/coupon/import/csv/code/decorators/excludes
	 * Excludes decorators added by the "common" option from the coupon code import CSV job controller
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
	 *  controller/jobs/coupon/import/csv/code/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2017.10
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/coupon/import/csv/code/decorators/global
	 * @see controller/jobs/coupon/import/csv/code/decorators/local
	 */

	/** controller/jobs/coupon/import/csv/code/decorators/global
	 * Adds a list of globally available decorators only to the coupon code import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/coupon/import/csv/code/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2017.10
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/coupon/import/csv/code/decorators/excludes
	 * @see controller/jobs/coupon/import/csv/code/decorators/local
	 */

	/** controller/jobs/coupon/import/csv/code/decorators/local
	 * Adds a list of local decorators only to the coupon code import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Coupon\Import\Csv\Code\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/coupon/import/csv/code/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Coupon\Import\Csv\Code\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2017.10
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/coupon/import/csv/code/decorators/excludes
	 * @see controller/jobs/coupon/import/csv/code/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Coupon code import CSV' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Imports new and updates existing coupon code from CSV files' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		try
		{
			$context = $this->context();
			$process = $context->process();

			$fs = $context->fs( 'fs-import' );
			$dir = 'couponcode/' . $context->locale()->getSiteItem()->getCode();

			if( $fs->isDir( $dir ) === false ) {
				return;
			}

			$fcn = function( \Aimeos\MShop\ContextIface $context, $couponId, $fhandle, $path ) {
				$this->process( $context, $couponId, $fhandle, $path );
			};

			foreach( $fs->scan( $dir ) as $filename )
			{
				$path = $dir . '/' . $filename;

				if( $fs instanceof \Aimeos\Base\Filesystem\DirIface && $fs->isDir( $path ) ) {
					continue;
				}

				list( $couponId,) = explode( '.', $filename );

				$process->start( $fcn, [$context, $couponId, $fs->reads( $path ), $path] );
			}

			$process->wait();
		}
		catch( \Exception $e )
		{
			$context->logger()->error( 'Coupon import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'import/csv/couponcode' );
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
		$context = $this->context();
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
				$context->logger()->error( $msg, 'import/csv/coupon/code' );

				$errors++;
			}
		}

		return $errors;
	}


	/**
	 * Imports content from the given container
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param string $couponId Unique coupon ID the codes should be imported for
	 * @param resource $fhandle File handle of file to import
	 * @param string $path Path to the container file
	 */
	protected function process( \Aimeos\MShop\ContextIface $context, string $couponId, $fhandle, string $path )
	{
		$total = $errors = 0;
		$logger = $context->logger();

		$maxcnt = $this->size();
		$skiplines = $this->skip();
		$mappings = $this->mapping();


		$logger->info( sprintf( 'Started coupon code import from "%1$s"', $path ), 'import/csv/couponcode' );

		$processor = $this->getProcessors( $mappings );
		$codePos = $this->getCodePosition( $mappings['code'] );

		for( $i = 0; $i < $skiplines; $i++ ) {
			fgetcsv( $fhandle );
		}

		while( ( $data = $this->getData( $fhandle, $maxcnt, $codePos ) ) !== [] )
		{
			$items = $this->getCouponCodeItems( array_keys( $data ) );
			$errors += $this->import( $items, $data, $couponId, $processor );

			$total += count( $data );
			unset( $items, $data );
		}

		fclose( $fhandle );
		$context->fs( 'fs-import' )->rm( $path );

		$str = 'Finished coupon import: %1$d successful, %2$s errors, %3$s total';
		$logger->info( sprintf( $str, $total - $errors, $errors, $total ), 'import/csv/couponcode' );
	}


	/**
	 * Returns the column mapping
	 *
	 * @return array Mapping of the columns
	 */
	protected function mapping() : array
	{
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
		 * @see controller/jobs/coupon/import/csv/code/skip-lines
		 * @see controller/jobs/coupon/import/csv/code/max-size
		 */
		return $this->context()->config()->get( 'controller/jobs/coupon/import/csv/code/mapping', $this->getDefaultMapping() );
	}


	/**
	 * Returns the maximum number of items processed at once
	 *
	 * @return int Maximum number of items
	 */
	protected function size() : int
	{
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
		 * @see controller/jobs/coupon/import/csv/code/skip-lines
		 * @see controller/jobs/coupon/import/csv/code/mapping
		 */
		return (int) $this->context()->config()->get( 'controller/jobs/coupon/import/csv/code/max-size', 1000 );
	}


	/**
	 * Returns the number of lines to skip at the beginning of the file
	 *
	 * @return int Number of linees to skip
	 */
	protected function skip() : int
	{
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
		 * @see controller/jobs/coupon/import/csv/code/mapping
		 * @see controller/jobs/coupon/import/csv/code/max-size
		 */
		return (int) $this->context()->config()->get( 'controller/jobs/coupon/import/csv/code/skip-lines', 0 );
	}
}
