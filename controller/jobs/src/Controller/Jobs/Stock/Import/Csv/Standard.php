<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
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
	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Stock import CSV' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Imports new and updates existing stocks from CSV files' );
	}


	/**
	 * Executes the job
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->getContext();
		$config = $context->getConfig();
		$logger = $context->getLogger();


		/** controller/jobs/stock/import/csv/location
		 * File or directory where the content is stored which should be imported
		 *
		 * You need to configure the CSV file or directory with the CSV files that
		 * should be imported. It should be an absolute path to be sure but can be
		 * relative path if you absolutely know from where the job will be executed
		 * from.
		 *
		 * @param string Absolute file or directory path
		 * @since 2019.04
		 * @category Developer
		 * @category User
		 * @see controller/jobs/stock/import/csv/container/type
		 * @see controller/jobs/stock/import/csv/container/content
		 * @see controller/jobs/stock/import/csv/container/options
		 */
		$location = $config->get( 'controller/jobs/stock/import/csv/location' );

		try
		{
			$logger->log( sprintf( 'Started stock import from "%1$s"', $location ), \Aimeos\MW\Logger\Base::INFO );

			if( !file_exists( $location ) )
			{
				$msg = sprintf( 'File or directory "%1$s" doesn\'t exist', $location );
				throw new \Aimeos\Controller\Jobs\Exception( $msg );
			}

			$files = [];

			if( is_dir( $location ) )
			{
				foreach( new \DirectoryIterator( $location ) as $entry )
				{
					if( strncmp( $entry->getFilename(), 'stock', 5 ) === 0 && $entry->getExtension() === 'csv' ) {
						$files[] = $entry->getPathname();
					}
				}
			}
			else
			{
				$files[] = $location;
			}

			sort( $files );
			$context->__sleep();

			$fcn = function( $filepath ) {
				$this->import( $filepath );
			};

			foreach( $files as $filepath ) {
				$context->getProcess()->start( $fcn, [$filepath] );
			}

			$context->getProcess()->wait();

			$logger->log( sprintf( 'Finished stock import from "%1$s"', $location ), \Aimeos\MW\Logger\Base::INFO );
		}
		catch( \Exception $e )
		{
			$logger->log( 'Stock import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() );
			throw $e;
		}
	}


	/**
	 * Executes the job.
	 *
	 * @param string $filename Absolute path to the file that whould be imported
	 */
	public function import( $filename )
	{
		$context = $this->getContext();
		$config = $context->getConfig();
		$logger = $context->getLogger();


		/** controller/common/stock/import/csv/max-size
		 * Maximum number of CSV rows to import at once
		 *
		 * It's more efficient to read and import more than one row at a time
		 * to speed up the import. Usually, the bigger the chunk that is imported
		 * at once, the less time the importer will need. The downside is that
		 * the amount of memory required by the import process will increase as
		 * well. Therefore, it's a trade-off between memory consumption and
		 * import speed.
		 *
		 * '''Note:''' The maximum size is 10000 records
		 *
		 * @param integer Number of rows
		 * @since 2019.04
		 * @category Developer
		 * @see controller/jobs/stock/import/csv/backup
		 * @see controller/jobs/stock/import/csv/skip-lines
		 */
		$maxcnt = (int) $config->get( 'controller/common/stock/import/csv/max-size', 1000 );

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
		 * @category Developer
		 * @see controller/jobs/stock/import/csv/backup
		 * @see controller/common/stock/import/csv/max-size
		 */
		$skiplines = (int) $config->get( 'controller/jobs/stock/import/csv/skip-lines', 0 );

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
		 * by the PHP strftime() function to create dynamic paths, e.g. "backup/%Y-%m-%d"
		 * which would create "backup/2000-01-01". For more information about the
		 * strftime() placeholders, please have a look into the PHP documentation of
		 * the {@link http://php.net/manual/en/function.strftime.php strftime() function}.
		 *
		 * '''Note:''' If no backup name is configured, the file or directory
		 * won't be moved away. Please make also sure that the parent directory
		 * and the new directory are writable so the file or directory could be
		 * moved.
		 *
		 * @param integer Name of the backup file, optionally with date/time placeholders
		 * @since 2019.04
		 * @category Developer
		 * @see controller/common/stock/import/csv/max-size
		 * @see controller/jobs/stock/import/csv/skip-lines
		 */
		$backup = $config->get( 'controller/jobs/stock/import/csv/backup' );


		$container = $this->getContainer( $filename );

		$logger->log( sprintf( 'Started stock import from file "%1$s"', $filename ), \Aimeos\MW\Logger\Base::INFO );

		foreach( $container as $content )
		{
			for( $i = 0; $i < $skiplines; $i++ ) {
				$content->next();
			}

			$this->importStocks( $content, $maxcnt );
		}

		$logger->log( sprintf( 'Finished stock import from file "%1$s"', $filename ), \Aimeos\MW\Logger\Base::INFO );

		$container->close();

		if( !empty( $backup ) && @rename( $filename, strftime( $backup ) ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Unable to move imported file' ) );
		}
	}


	/**
	 * Opens and returns the container which includes the stock data
	 *
	 * @param string $location Absolute path to the file
	 * @return \Aimeos\MW\Container\Iface Container object
	 */
	protected function getContainer( $location )
	{
		$config = $this->getContext()->getConfig();

		/** controller/jobs/stock/import/csv/container/type
		 * Nave of the container type to read the data from
		 *
		 * The container type tells the importer how it should retrieve the data.
		 * There are currently two container types that support the necessary
		 * CSV content:
		 * * File
		 * * Zip
		 *
		 * '''Note:''' For the PHPExcel container, you need to install the
		 * "ai-container" extension.
		 *
		 * @param string Container type name
		 * @since 2019.04
		 * @category Developer
		 * @category User
		 * @see controller/jobs/stock/import/csv/location
		 * @see controller/jobs/stock/import/csv/container/options
		 */
		$container = $config->get( 'controller/jobs/stock/import/csv/container/type', 'File' );

		/** controller/jobs/stock/import/csv/container/options
		 * List of file container options for the stock import files
		 *
		 * Some container/content type allow you to hand over additional settings
		 * for configuration. Please have a look at the article about
		 * {@link http://aimeos.org/docs/Developers/Utility/Create_and_read_files container/content files}
		 * for more information.
		 *
		 * @param array Associative list of option name/value pairs
		 * @since 2019.04
		 * @category Developer
		 * @category User
		 * @see controller/jobs/stock/import/csv/location
		 * @see controller/jobs/stock/import/csv/container/type
		 */
		$options = $config->get( 'controller/jobs/stock/import/csv/container/options', [] );

		return \Aimeos\MW\Container\Factory::getContainer( $location, $container, 'CSV', $options );
	}


	/**
	 * Returns the stock items for the given codes and types
	 *
	 * @param array $codes List of stock codes
	 * @param array $types List of stock types
	 * @return array Multi-dimensional array of code/type/item map
	 */
	protected function getStockItems( array $codes, array $types )
	{
		$map = [];
		$manager = \Aimeos\MShop::create( $this->getContext(), 'stock' );

		$search = $manager->createSearch()->setSlice( 0, 10000 );
		$search->setConditions( $search->combine( '&&', [
			$search->compare( '==', 'stock.productcode', $codes ),
			$search->compare( '==', 'stock.type', $types )
		] ) );

		foreach( $manager->searchItems( $search ) as $item ) {
			$map[$item->getProductCode()][$item->getType()] = $item;
		}

		return $map;
	}


	/**
	 * Imports the CSV data and creates new stocks or updates existing ones
	 *
	 * @param \Aimeos\MW\Container\Content\Iface $content Content object
	 * @return integer Number of imported stocks
	 */
	protected function importStocks( \Aimeos\MW\Container\Content\Iface $content, $maxcnt )
	{
		$total = 0;
		$manager = \Aimeos\MShop::create( $this->getContext(), 'stock' );

		do
		{
			$count = 0;
			$codes = $data = $items = $types = [];

			while( $content->valid() && $count < $maxcnt )
			{
				$row = $content->current();
				$content->next();

				if( $row[0] == '' ) {
					continue;
				}

				$type = $this->getValue( $row, 2, 'default' );
				$types[$type] = null;
				$codes[] = $row[0];
				$row[2] = $type;
				$data[] = $row;

				$count++;
			}

			if( $count === 0 ) {
				break;
			}

			$items = [];
			$map = $this->getStockItems( $codes, $types );

			foreach( $data as $entry )
			{
				$code = $entry[0];
				$type = $entry[2];

				if( isset( $map[$code][$type] ) ) {
					$item = $map[$code][$type];
				} else {
					$item = $manager->createItem();
				}

				$items[] = $item->setProductCode( $code )->setType( $type )
					->setStocklevel( $this->getValue( $entry, 1 ) )
					->setDateBack( $this->getValue( $entry, 3 ) );

				unset( $map[$code][$type] );
			}

			$manager->saveItems( $items );
			unset( $items );

			$total += $count;
		}
		while( $count > 0 );

		return $total;
	}
}
