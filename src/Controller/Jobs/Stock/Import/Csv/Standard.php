<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2023
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Stock\Import\Csv;


/**
 * Job controller for CSV stock imports
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/stock/import/csv/name
	 * Class name of the used stock suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Stock\Import\Csv\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Stock\Import\Csv\Mycsv
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/stock/import/csv/name = Mycsv
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
	 * @since 2019.04
	 */

	/** controller/jobs/stock/import/csv/decorators/excludes
	 * Excludes decorators added by the "common" option from the stock import CSV job controller
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
	 *  controller/jobs/stock/import/csv/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/stock/import/csv/decorators/global
	 * @see controller/jobs/stock/import/csv/decorators/local
	 */

	/** controller/jobs/stock/import/csv/decorators/global
	 * Adds a list of globally available decorators only to the stock import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/stock/import/csv/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/stock/import/csv/decorators/excludes
	 * @see controller/jobs/stock/import/csv/decorators/local
	 */

	/** controller/jobs/stock/import/csv/decorators/local
	 * Adds a list of local decorators only to the stock import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Stock\Import\Csv\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/stock/import/csv/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Stock\Import\Csv\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/stock/import/csv/decorators/excludes
	 * @see controller/jobs/stock/import/csv/decorators/global
	 */


	use \Aimeos\Controller\Common\Common\Import\Traits;


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Stock import CSV' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Imports new and updates existing stocks from CSV files' );
	}


	/**
	 * Executes the job
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->context();
		$logger = $context->logger();
		$process = $context->process();

		$location = $this->location();
		$fs = $context->fs( 'fs-import' );

		if( $fs->isDir( $location ) === false ) {
			return;
		}

		try
		{
			$logger->info( sprintf( 'Started stock import from "%1$s"', $location ), 'import/csv/stock' );

			$fcn = function( \Aimeos\MShop\ContextIface $context, string $path ) {
				$this->import( $context, $path );
			};

			foreach( map( $fs->scan( $location ) )->sort() as $filename )
			{
				$path = $location . '/' . $filename;

				if( $fs instanceof \Aimeos\Base\Filesystem\DirIface && $fs->isDir( $path ) ) {
					continue;
				}

				$process->start( $fcn, [$context, $path] );
			}

			$process->wait();

			$logger->info( sprintf( 'Finished stock import from "%1$s"', $location ), 'import/csv/stock' );
		}
		catch( \Exception $e )
		{
			$logger->error( 'Stock import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'import/csv/stock' );
			$this->mail( 'Stock CSV import error', $e->getMessage() . "\n" . $e->getTraceAsString() );
			throw $e;
		}
	}


	/**
	 * Returns the directory for storing imported files
	 *
	 * @return string Directory for storing imported files
	 */
	protected function backup() : string
	{
		/** controller/jobs/stock/import/csv/backup
		 * Name of the backup for sucessfully imported files
		 *
		 * After a CSV file was imported successfully, you can move it to another
		 * location, so it won't be imported again and isn't overwritten by the
		 * next file that is stored at the same location in the file system.
		 *
		 * You should use an absolute path to be sure but can be relative path
		 * if you absolutely know from where the job will be executed from. The
		 * name of the new backup location can contain placeholders understood
		 * by the PHP DateTime::format() method (with percent signs prefix) to
		 * create dynamic paths, e.g. "backup/%Y-%m-%d" which would create
		 * "backup/2000-01-01". For more information about the date() placeholders,
		 * please have a look  into the PHP documentation of the
		 * {@link https://www.php.net/manual/en/datetime.format.php format() method}.
		 *
		 * **Note:** If no backup name is configured, the file will be removed!
		 *
		 * @param integer Name of the backup file, optionally with date/time placeholders
		 * @since 2019.04
		 * @see controller/jobs/stock/import/csv/location
		 * @see controller/jobs/stock/import/csv/max-size
		 * @see controller/jobs/stock/import/csv/skip-lines
		 */
		$backup = $this->context()->config()->get( 'controller/jobs/stock/import/csv/backup' );
		return \Aimeos\Base\Str::strtime( (string) $backup );
	}


	/**
	 * Imports the CSV file given by its path
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param string $path Relative path to the CSV file in the file system
	 */
	protected function import( \Aimeos\MShop\ContextIface $context, string $path )
	{
		$context = $this->context();
		$logger = $context->logger();

		$skiplines = $this->skip();
		$fs = $context->fs( 'fs-import' );

		$logger->info( sprintf( 'Started stock import from file "%1$s"', $path ), 'import/csv/stock' );

		$fh = $fs->reads( $path );

		for( $i = 0; $i < $skiplines; $i++ ) {
			fgetcsv( $fh );
		}

		$this->importStocks( $fh );

		fclose( $fh );
		$this->saveTypes();

		if( !empty( $backup = $this->backup() ) ) {
			$fs->move( $path, $backup );
		} else {
			$fs->rm( $path );
		}

		$logger->info( sprintf( 'Finished stock import from file "%1$s"', $path ), 'import/csv/stock' );
	}


	/**
	 * Returns the stock items for the given product IDs and stock types
	 *
	 * @param array $ids List of product IDs
	 * @param array $types List of stock types
	 * @return array Multi-dimensional array of code/type/item map
	 */
	protected function getStockItems( array $ids, array $types ) : array
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'stock' );
		$search = $manager->filter()->add( ['stock.productid' => $ids, 'stock.type' => $types] )->slice( 0, 10000 );

		$map = [];
		foreach( $manager->search( $search ) as $item ) {
			$map[$item->getProductId()][$item->getType()] = $item;
		}

		return $map;
	}


	/**
	 * Imports the CSV data and creates new stocks or updates existing ones
	 *
	 * @param resource $fhandle File handle for the CSV file to import
	 * @return int Number of imported stocks
	 */
	protected function importStocks( $fhandle ) : int
	{
		$total = 0;

		do
		{
			$count = 0;
			$max = $this->max();
			$codes = $data = $types = [];

			while( ( $row = fgetcsv( $fhandle ) ) !== false && $count < $max )
			{
				if( $row[0] === '' ) {
					continue;
				}

				$type = $this->val( $row, 2, 'default' );
				$types[$type] = null;
				$codes[] = $row[0];
				$row[2] = $type;
				$data[] = $row;

				$count++;
			}

			if( !empty( $data ) ) {
				$this->update( $data, $codes, array_keys( $types ) );
			}

			$total += $count;
		}
		while( $count > 0 );

		return $total;
	}


	/**
	 * Returns the path to the directory with the CSV file
	 *
	 * @return string Path to the directory with the CSV file
	 */
	protected function location() : string
	{
		/** controller/jobs/stock/import/csv/location
		 * File or directory where the content is stored which should be imported
		 *
		 * You need to configure the CSV file or directory with the CSV files that
		 * should be imported. It should be an absolute path to be sure but can be
		 * relative path if you absolutely know from where the job will be executed
		 * from.
		 *
		 * @param string Relative path to the CSV files
		 * @since 2019.04
		 * @see controller/jobs/stock/import/csv/backup
		 * @see controller/jobs/stock/import/csv/max-size
		 * @see controller/jobs/stock/import/csv/skip-lines
		 */
		return (string) $this->context()->config()->get( 'controller/jobs/stock/import/csv/location', 'stock' );
	}


	/**
	 * Returns the maximum number of CSV rows to import at once
	 *
	 * @return int Maximum number of CSV rows to import at once
	 */
	protected function max() : int
	{
		/** controller/jobs/stock/import/csv/max-size
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
		 * @since 2019.04
		 * @see controller/jobs/stock/import/csv/backup
		 * @see controller/jobs/stock/import/csv/location
		 * @see controller/jobs/stock/import/csv/skip-lines
		 */
		return (int) $this->context()->config()->get( 'controller/jobs/stock/import/csv/max-size', 1000 );
	}


	/**
	 * Returns the number of rows skipped in front of each CSV files
	 *
	 * @return int Number of rows skipped in front of each CSV files
	 */
	protected function skip() : int
	{
		/** controller/jobs/stock/import/csv/skip-lines
		 * Number of rows skipped in front of each CSV files
		 *
		 * Some CSV files contain header information describing the content of
		 * the column values. These data is for informational purpose only and
		 * can't be imported into the database. Using this option, you can
		 * define the number of lines that should be left out before the import
		 * begins.
		 *
		 * @param integer Number of rows
		 * @since 2019.04
		 * @see controller/jobs/stock/import/csv/backup
		 * @see controller/jobs/stock/import/csv/location
		 * @see controller/jobs/stock/import/csv/max-size
		 */
		return (int) $this->context()->config()->get( 'controller/jobs/stock/import/csv/skip-lines', 0 );
	}


	/**
	 * Updates the stock items
	 *
	 * @param array $data List of stock entries
	 * @param array $codes List of product codes the stock items are associated to
	 * @param array $types List of stock types which should be updated
	 */
	protected function update( array $data, array $codes, array $types )
	{
		$context = $this->context();
		$manager = \Aimeos\MShop::create( $context, 'stock' );
		$prodManager = \Aimeos\MShop::create( $context, 'product' );

		$filter = $prodManager->filter()->add( ['product.code' => $codes] )->slice( 0, count( $codes ) );
		$products = $prodManager->search( $filter );
		$prodMap = $products->col( null, 'product.code' );

		$map = $this->getStockItems( $products->keys()->all(), $types );
		$items = [];

		foreach( $data as $entry )
		{
			$code = $entry[0];
			$type = $entry[2];

			if( ( $product = $prodMap->get( $code ) ) === null ) {
				continue;
			}

			$item = $map[$product->getId()][$type] ?? $manager->create();

			$items[] = $item->setProductId( $product->getId() )->setType( $type )
				->setStocklevel( $this->val( $entry, 1 ) )
				->setDateBack( $this->val( $entry, 3 ) );

			if( $item->getStockLevel() === null || $item->getStockLevel() > 0 ) {
				$product->setInStock( 1 );
			}

			$this->addType( 'stock/type', 'product', $type );
			unset( $map[$code][$type] );
		}

		$prodManager->save( $products );
		$manager->save( $items );
		unset( $items );
	}
}
