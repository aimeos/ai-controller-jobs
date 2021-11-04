<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Order\Status\Csv;

use \Aimeos\MW\Logger\Base as Log;


/**
 * Job controller for CSV order status imports.
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
		return $this->getContext()->translate( 'controller/jobs', 'Order status import CSV' );
	}


	/**
	 * Returns the localized description of the job.
	 */
	public function getDescription() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Status import for orders from CSV file' );
	}


	/**
	 * Executes the job.
	 */
	public function run()
	{
		$context = $this->getContext();

		/** controller/jobs/order/status/csv/directory
		 * Path to the CSV files relative to the order status file system
		 *
		 * The CSV files for importing the order status values are expected to
		 * be in a subdirectory of the used file system ("fs-orderstatus" or "fs")
		 * named "orderstatus" by default. This can be changed to any other
		 * sub-directory name (or a path with several sub-directories) or to an
		 * empty string in case the files are located in the root directory of
		 * the virtual file system.
		 *
		 * @param string Relative sub-directory name, path or empty string
		 * @since 2021.10
		 */
		$dirname = $context->config()->get( 'controller/jobs/order/status/csv/directory', 'orderstatus' );

		$fs = $context->fs( 'fs-import' );

		if( ( $dir = ( $fs instanceof \Aimeos\MW\Filesystem\DirIface ) ) && !$fs->has( $dirname . '/_done' ) ) {
			$fs->mkDir( $dirname . '/_done' );
		}

		foreach( $fs->scan( $dirname ) as $name )
		{
			if( $dir && $fs->isDir( $dirname . '/' . $name ) ) {
				continue;
			}

			try
			{
				$handle = $fs->reads( $dirname . '/' . $name );

				$this->import( $handle );

				if( $dir ) {
					$fs->move( $dirname . '/' . $name, $dirname . '/_done/' . $name );
				} else {
					$fs->rm( $dirname . '/' . $name );
				}
			}
			catch( \Exception $e )
			{
				$msg = 'Order status import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
				$context->logger()->log( $msg, Log::ERR, 'order/status/csv' );
			}
			finally
			{
				!is_resource( $handle ?? null ) ?: fclose( $handle );
			}
		}
	}


	/**
	 * Returns the rows from the resource handle
	 *
	 * @param resource $handle File resource handle
	 * @param int $maxcnt Maximum number of rows to read
	 * @param string $sep Single byte character for separating the values
	 * @return array<int,array<string,array<int,int|string>>>|null Array of order and product status rows or NULL for no more rows
	 */
	protected function getData( $handle, int $maxcnt, string $sep ) : ?array
	{
		$cnt = 0;
		$orders = $products = [];

		while( ( $row = fgetcsv( $handle, 0, $sep ) ) && $cnt < $maxcnt )
		{
			if( empty( $row[0] ) ) {
				continue;
			}

			if( !empty( $row[1] ) ) {
				$products[$row[1]] = $row;
			} else {
				$orders[$row[0]] = $row;
			}

			++$cnt;
		}

		if( !empty( $orders ) || !empty( $products ) ) {
			return [$orders, $products];
		}

		return null;
	}


	/**
	 * Imports the order status CSV
	 *
	 * @param resource $handle File handle to read content from
	 */
	protected function import( $handle )
	{
		$context = $this->getContext();
		$config = $context->config();

		/** controller/jobs/order/status/csv/max-size
		 * Maximum number of CSV rows to import at once
		 *
		 * It's more efficient to read and status more than one row at a time
		 * to speed up the status. Usually, the bigger the chunk that is statused
		 * at once, the less time the statuser will need. The downside is that
		 * the amount of memory required by the status process will increase as
		 * well. Therefore, it's a trade-off between memory consumption and
		 * status speed.
		 *
		 * @param int Number of rows
		 * @since 2021.10
		 */
		$maxcnt = (int) $config->get( 'controller/jobs/order/status/csv/max-size', 1000 );

		/** controller/jobs/order/status/csv/separator
		 * Character separating the values in the CSV file
		 *
		 * By default, a comma (",") is used but it can be changed to e.g. a
		 * semicolon (";") if neccesary.
		 *
		 * @param string Single byte separator character
		 * @since 2021.10
		 */
		$sep = $config->get( 'controller/jobs/order/status/csv/separator', ',' );

		/** controller/jobs/order/status/csv/skip
		 * Number of rows that should be skipped
		 *
		 * If the CSV file contains a header that shouldn't be imported, set
		 * this option to "1" or any number of rows that should be ignored.
		 *
		 * @param int Number of header rows to skip
		 * @since 2021.10
		 */
		$skip = (int) $config->get( 'controller/jobs/order/status/csv/skip', 0 );

		for( $i = 0; $i < $skip; $i++ ) {
			fgetcsv( $handle, 0, $sep );
		}

		$pmanager = \Aimeos\MShop::create( $context, 'order/base/product' );
		$manager = \Aimeos\MShop::create( $context, 'order' );

		while( $data = $this->getData( $handle, $maxcnt, $sep ) )
		{
			if( !empty( $orders = $data[0] ) )
			{
				$filter = $manager->filter()->slice( 0, count( $orders ) )
					->add( ['order.id' => array_keys( $orders )] );
				$items = $manager->search( $filter );

				foreach( $items as $item ) {
					$item->setStatusDelivery( $orders[$item->getId()][2] ?? $item->getStatusDelivery() );
				}

				$manager->save( $items );
			}


			if( !empty( $products = $data[1] ) )
			{
				$filter = $pmanager->filter()->slice( 0, count( $products ) )
					->add( ['order.base.product.id' => array_keys( $products )] );
				$items = $pmanager->search( $filter );

				foreach( $items as $item ) {
					$item->setStatusDelivery( $orders[$item->getId()][2] ?? $item->getStatusDelivery() );
				}

				$pmanager->save( $items );
			}
		}
	}
}
