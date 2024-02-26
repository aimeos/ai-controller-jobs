<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2024
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Supplier\Import\Csv;


/**
 * Job controller for CSV supplier imports.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/supplier/import/csv/name
	 * Class name of the used supplier suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Supplier\Import\Csv\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Supplier\Import\Csv\Mycsv
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/supplier/import/csv/name = Mycsv
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
	 * @since 2020.07
	 */

	/** controller/jobs/supplier/import/csv/decorators/excludes
	 * Excludes decorators added by the "common" option from the supplier import CSV job controller
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
	 *  controller/jobs/supplier/import/csv/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2020.07
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/supplier/import/csv/decorators/global
	 * @see controller/jobs/supplier/import/csv/decorators/local
	 */

	/** controller/jobs/supplier/import/csv/decorators/global
	 * Adds a list of globally available decorators only to the supplier import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/supplier/import/csv/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2020.07
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/supplier/import/csv/decorators/excludes
	 * @see controller/jobs/supplier/import/csv/decorators/local
	 */

	/** controller/jobs/supplier/import/csv/decorators/local
	 * Adds a list of local decorators only to the supplier import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Supplier\Import\Csv\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/supplier/import/csv/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Supplier\Import\Csv\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2020.07
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/supplier/import/csv/decorators/excludes
	 * @see controller/jobs/supplier/import/csv/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Supplier import CSV' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Imports new and updates existing suppliers from CSV files' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->context();

		try
		{
			$errors = 0;
			$location = $this->location();
			$fs = $context->fs( 'fs-import' );

			if( $fs->isDir( $location ) === false ) {
				return;
			}

			foreach( map( $fs->scan( $location ) )->sort() as $filename )
			{
				if( $filename[0] === '.' ) {
					continue;
				}

				$path = $location . '/' . $filename;

				if( $fs instanceof \Aimeos\Base\Filesystem\DirIface && $fs->isDir( $path ) ) {
					continue;
				}

				$errors += $this->import( $path );
			}

			if( $errors > 0 ) {
				$this->mail( 'Supplier CSV import', sprintf( 'Invalid supplier lines during import: %1$d', $errors ) );
			}
		}
		catch( \Exception $e )
		{
			$context->logger()->error( 'Supplier import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'import/csv/supplier' );
			$this->mail( 'Supplier CSV import error', $e->getMessage() . "\n" . $e->getTraceAsString() );
			throw new \Aimeos\Controller\Jobs\Exception( $e->getMessage() );
		}
	}


	/**
	 * Returns the directory for storing imported files
	 *
	 * @return string Directory for storing imported files
	 */
	protected function backup() : string
	{
		/** controller/jobs/supplier/import/csv/backup
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
		 * @since 2020.07
		 * @see controller/jobs/supplier/import/csv/domains
		 * @see controller/jobs/supplier/import/csv/mapping
		 * @see controller/jobs/supplier/import/csv/skip-lines
		 * @see controller/jobs/supplier/import/csv/converter
		 * @see controller/jobs/supplier/import/csv/strict
		 * @see controller/jobs/supplier/import/csv/max-size
		 */
		$backup = $this->context()->config()->get( 'controller/jobs/supplier/import/csv/backup' );
		return \Aimeos\Base\Str::strtime( (string) $backup );
	}


	/**
	 * Returns the list of domain names that should be retrieved along with the supplier items
	 *
	 * @return array List of domain names
	 */
	protected function domains() : array
	{
		/** controller/jobs/supplier/import/csv/domains
		 * List of item domain names that should be retrieved along with the supplier items
		 *
		 * For efficient processing, the items associated to the suppliers can be
		 * fetched to, minimizing the number of database queries required. To be
		 * most effective, the list of item domain names should be used in the
		 * mapping configuration too, so the retrieved items will be used during
		 * the import.
		 *
		 * @param array Associative list of MShop item domain names
		 * @since 2020.07
		 * @see controller/jobs/supplier/import/csv/mapping
		 * @see controller/jobs/supplier/import/csv/skip-lines
		 * @see controller/jobs/supplier/import/csv/converter
		 * @see controller/jobs/supplier/import/csv/strict
		 * @see controller/jobs/supplier/import/csv/backup
		 * @see controller/jobs/supplier/import/csv/max-size
		 */
		$domains = ['media', 'text', 'supplier/address'];
		return $this->context()->config()->get( 'controller/jobs/supplier/import/xml/domains', $domains );
	}


	/**
	 * Returns the position of the "supplier.code" column from the supplier item mapping
	 *
	 * @param array $mapping Mapping of the "item" columns with position as key and code as value
	 * @return int Position of the "supplier.code" column
	 * @throws \Aimeos\Controller\Jobs\Exception If no mapping for "supplier.code" is found
	 */
	protected function getCodePosition( array $mapping ) : int
	{
		foreach( $mapping as $pos => $key )
		{
			if( $key === 'supplier.code' ) {
				return $pos;
			}
		}

		throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'No "supplier.code" column in CSV mapping found' ) );
	}


	/**
	 * Returns the supplier items for the given codes
	 *
	 * @param array $codes List of supplier codes
	 * @param array $domains List of domains whose items should be fetched too
	 * @return \Aimeos\Map Associative list of supplier codes as key and supplier items as value
	 */
	protected function getSuppliers( array $codes, array $domains ) : \Aimeos\Map
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'supplier' );
		$search = $manager->filter()->add( ['supplier.code' => $codes] )->slice( 0, count( $codes ) );

		return $manager->search( $search, $domains )->col( null, 'supplier.code' );
	}


	/**
	 * Imports the CSV file from the given path
	 *
	 * @param string $path Relative path to the CSV file
	 * @return int Number of lines which couldn't be imported
	 */
	protected function import( string $path ) : int
	{
		$context = $this->context();
		$logger = $context->logger();

		$logger->info( sprintf( 'Started supplier import from "%1$s"', $path ), 'import/csv/supplier' );

		$maxcnt = $this->max();
		$skiplines = $this->skip();
		$domains = $this->domains();

		$mappings = $this->mapping();
		$processor = $this->getProcessors( $mappings );
		$codePos = $this->getCodePosition( $mappings['item'] );

		$fs = $context->fs( 'fs-import' );
		$fh = $fs->reads( $path );
		$total = $errors = 0;

		for( $i = 0; $i < $skiplines; $i++ ) {
			fgetcsv( $fh );
		}

		while( ( $data = $this->getData( $fh, $maxcnt, $codePos ) ) !== [] )
		{
			$suppliers = $this->getSuppliers( array_keys( $data ), $domains );
			$errors += $this->importSuppliers( $suppliers, $data, $mappings['item'], $processor );

			$total += count( $data );
			unset( $suppliers, $data );
		}

		fclose( $fh );

		if( !empty( $backup = $this->backup() ) ) {
			$fs->move( $path, $backup );
		} else {
			$fs->rm( $path );
		}

		$str = sprintf( 'Finished supplier import from "%1$s" (%2$d/%3$d)', $path, $errors, $total );
		$logger->info( $str, 'import/csv/supplier' );

		return $errors;
	}


	/**
	 * Imports the CSV data and creates new suppliers or updates existing ones
	 *
	 * @param \Aimeos\Map $suppliers Associative list of supplier items with codes as keys and items implementing \Aimeos\MShop\Supplier\Item\Iface as values
	 * @param array $data Associative list of import data as index/value pairs
	 * @param array $mapping Associative list of positions and domain item keys
	 * @param \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Iface $processor Processor object
	 * @return int Number of suppliers that couldn't be imported
	 * @throws \Aimeos\Controller\Jobs\Exception
	 */
	protected function importSuppliers( \Aimeos\Map $suppliers, array $data, array $mapping,
		\Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Iface $processor ) : int
	{
		$errors = 0;
		$context = $this->context();
		$manager = \Aimeos\MShop::create( $context, 'supplier' );

		foreach( $data as $code => $list )
		{
			$manager->begin();

			try
			{
				$code = trim( $code );
				$item = $suppliers[$code] ?? $manager->create();
				$map = current( $this->getMappedChunk( $list, $mapping ) ); // there can only be one chunk for the base supplier data

				if( $map )
				{
					$item->fromArray( $map, true );

					$list = $processor->process( $item, $list );
					$suppliers[$code] = $item;

					$manager->save( $item );
				}

				$manager->commit();
			}
			catch( \Exception $e )
			{
				$manager->rollback();

				$msg = sprintf( 'Unable to import supplier with code "%1$s": %2$s', $code, $e->getMessage() );
				$context->logger()->error( $msg, 'import/csv/supplier' );

				$errors++;
			}
		}

		return $errors;
	}


	/**
	 * Returns the path to the directory with the CSV file
	 *
	 * @return string Path to the directory with the CSV file
	 */
	protected function location() : string
	{
		/** controller/jobs/supplier/import/csv/location
		 * Directory where the CSV files are stored which should be imported
		 *
		 * It's the relative path inside the "fs-import" virtual file system
		 * configuration. The default location of the "fs-import" file system is:
		 *
		 * * Laravel: ./storage/import/
		 * * TYPO3: /uploads/tx_aimeos/.secure/import/
		 *
		 * @param string Relative path to the CSV files
		 * @since 2020.07
		 * @see controller/jobs/supplier/import/csv/backup
		 * @see controller/jobs/supplier/import/csv/domains
		 * @see controller/jobs/supplier/import/csv/location
		 * @see controller/jobs/supplier/import/csv/mapping
		 * @see controller/jobs/supplier/import/csv/max-size
		 * @see controller/jobs/supplier/import/csv/skip-lines
		 */
		return (string) $this->context()->config()->get( 'controller/jobs/supplier/import/csv/location', 'supplier' );
	}


	/**
	 * Returns the CSV column mapping
	 *
	 * @return array CSV column mapping
	 */
	protected function mapping() : array
	{
		/** controller/jobs/supplier/import/csv/mapping
		 * List of mappings between the position in the CSV file and item keys
		 *
		 * The importer have to know which data is at which position in the CSV
		 * file. Therefore, you need to specify a mapping between each position
		 * and the MShop domain item key (e.g. "supplier.code") it represents.
		 *
		 * You can use all domain item keys which are used in the fromArray()
		 * methods of the item classes.
		 *
		 * These mappings are grouped together by their processor names, which
		 * are responsible for importing the data, e.g. all mappings in "item"
		 * will be processed by the base supplier importer while the mappings in
		 * "text" will be imported by the text processor.
		 *
		 * @param array Associative list of processor names and lists of key/position pairs
		 * @since 2020.07
		 * @see controller/jobs/supplier/import/csv/domains
		 * @see controller/jobs/supplier/import/csv/skip-lines
		 * @see controller/jobs/supplier/import/csv/converter
		 * @see controller/jobs/supplier/import/csv/strict
		 * @see controller/jobs/supplier/import/csv/backup
		 * @see controller/jobs/supplier/import/csv/max-size
		 */
		$map = (array) $this->context()->config()->get( 'controller/jobs/supplier/import/csv/mapping', $this->getDefaultMapping() );

		if( !isset( $map['item'] ) || !is_array( $map['item'] ) )
		{
			$msg = sprintf( 'Required mapping key "%1$s" is missing or contains no array', 'item' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return $map;
	}


	/**
	 * Returns the maximum number of CSV rows to import at once
	 *
	 * @return int Maximum number of CSV rows to import at once
	 */
	protected function max() : int
	{
		/** controller/jobs/supplier/import/csv/max-size
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
		 * @since 2020.07
		 * @see controller/jobs/supplier/import/csv/backup
		 * @see controller/jobs/supplier/import/csv/domains
		 * @see controller/jobs/supplier/import/csv/location
		 * @see controller/jobs/supplier/import/csv/mapping
		 * @see controller/jobs/supplier/import/csv/skip-lines
		 */
		return (int) $this->context()->config()->get( 'controller/jobs/supplier/import/csv/max-size', 1000 );
	}


	/**
	 * Returns the number of rows skipped in front of each CSV files
	 *
	 * @return int Number of rows skipped in front of each CSV files
	 */
	protected function skip() : int
	{
		/** controller/jobs/supplier/import/csv/skip-lines
		 * Number of rows skipped in front of each CSV files
		 *
		 * Some CSV files contain header information describing the content of
		 * the column values. These data is for informational purpose only and
		 * can't be imported into the database. Using this option, you can
		 * define the number of lines that should be left out before the import
		 * begins.
		 *
		 * @param integer Number of rows
		 * @since 2020.07
		 * @see controller/jobs/supplier/import/csv/domains
		 * @see controller/jobs/supplier/import/csv/mapping
		 * @see controller/jobs/supplier/import/csv/converter
		 * @see controller/jobs/supplier/import/csv/strict
		 * @see controller/jobs/supplier/import/csv/backup
		 * @see controller/jobs/supplier/import/csv/max-size
		 */
		return (int) $this->context()->config()->get( 'controller/jobs/supplier/import/csv/skip-lines', 0 );
	}
}
