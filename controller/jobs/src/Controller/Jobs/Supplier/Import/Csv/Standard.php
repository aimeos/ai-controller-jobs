<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Supplier\Import\Csv;

use \Aimeos\MW\Logger\Base as Log;


/**
 * Job controller for CSV supplier imports.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Common\Supplier\Import\Csv\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Supplier import CSV' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Imports new and updates existing suppliers from CSV files' );
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
		$domains = array( 'media', 'text', 'supplier/address' );
		$mappings = $this->getDefaultMapping();


		if( file_exists( $config->get( 'controller/jobs/supplier/import/csv/location' ) ) === false )
		{
			return;
		}


		/** controller/common/supplier/import/csv/domains
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
		 * @category Developer
		 * @see controller/common/supplier/import/csv/mapping
		 * @see controller/common/supplier/import/csv/converter
		 * @see controller/common/supplier/import/csv/max-size
		 */
		$domains = $config->get( 'controller/common/supplier/import/csv/domains', $domains );

		/** controller/jobs/supplier/import/csv/domains
		 * List of item domain names that should be retrieved along with the supplier items
		 *
		 * This configuration setting overwrites the shared option
		 * "controller/common/supplier/import/csv/domains" if you need a
		 * specific setting for the job controller. Otherwise, you should
		 * use the shared option for consistency.
		 *
		 * @param array Associative list of MShop item domain names
		 * @since 2020.07
		 * @category Developer
		 * @see controller/jobs/supplier/import/csv/mapping
		 * @see controller/jobs/supplier/import/csv/skip-lines
		 * @see controller/jobs/supplier/import/csv/converter
		 * @see controller/jobs/supplier/import/csv/strict
		 * @see controller/jobs/supplier/import/csv/backup
		 * @see controller/common/supplier/import/csv/max-size
		 */
		$domains = $config->get( 'controller/jobs/supplier/import/csv/domains', $domains );


		/** controller/common/supplier/import/csv/mapping
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
		 * @category Developer
		 * @see controller/common/supplier/import/csv/domains
		 * @see controller/common/supplier/import/csv/converter
		 * @see controller/common/supplier/import/csv/max-size
		 */
		$mappings = $config->get( 'controller/common/supplier/import/csv/mapping', $mappings );

		/** controller/jobs/supplier/import/csv/mapping
		 * List of mappings between the position in the CSV file and item keys
		 *
		 * This configuration setting overwrites the shared option
		 * "controller/common/supplier/import/csv/mapping" if you need a
		 * specific setting for the job controller. Otherwise, you should
		 * use the shared option for consistency.
		 *
		 * @param array Associative list of processor names and lists of key/position pairs
		 * @since 2020.07
		 * @category Developer
		 * @see controller/jobs/supplier/import/csv/domains
		 * @see controller/jobs/supplier/import/csv/skip-lines
		 * @see controller/jobs/supplier/import/csv/converter
		 * @see controller/jobs/supplier/import/csv/strict
		 * @see controller/jobs/supplier/import/csv/backup
		 * @see controller/common/supplier/import/csv/max-size
		 */
		$mappings = $config->get( 'controller/jobs/supplier/import/csv/mapping', $mappings );


		/** controller/common/supplier/import/csv/converter
		 * List of converter names for the values at the position in the CSV file
		 *
		 * Not all data in the CSV file is already in the required format. Maybe
		 * the text encoding isn't UTF-8, the date is not in ISO format or something
		 * similar. In order to convert the data before it's imported, you can
		 * specify a list of converter objects that should be applied to the data
		 * from the CSV file.
		 *
		 * To each field in the CSV file, you can apply one or more converters,
		 * e.g. to encode a Latin text to UTF8 for the second CSV field:
		 *
		 *  array( 1 => 'Text/LatinUTF8' )
		 *
		 * Similarly, you can also apply several converters at once to the same
		 * field:
		 *
		 *  array( 1 => array( 'Text/LatinUTF8', 'DateTime/EnglishISO' ) )
		 *
		 * It would convert the data of the second CSV field first to UTF-8 and
		 * afterwards try to translate it to an ISO date format.
		 *
		 * The available converter objects are named "\Aimeos\MW\Convert\<type>_<conversion>"
		 * where <type> is the data type and <conversion> the way of the conversion.
		 * In the configuration, the type and conversion must be separated by a
		 * slash (<type>/<conversion>).
		 *
		 * **Note:** Keep in mind that the position of the CSV fields start at
		 * zero (0). If you only need to convert a few fields, you don't have to
		 * configure all fields. Only specify the positions in the array you
		 * really need!
		 *
		 * @param array Associative list of position/converter name (or list of names) pairs
		 * @since 2020.07
		 * @category Developer
		 * @see controller/common/supplier/import/csv/domains
		 * @see controller/common/supplier/import/csv/mapping
		 * @see controller/common/supplier/import/csv/max-size
		 */
		$converters = $config->get( 'controller/common/supplier/import/csv/converter', [] );

		/** controller/jobs/supplier/import/csv/converter
		 * List of converter names for the values at the position in the CSV file
		 *
		 * This configuration setting overwrites the shared option
		 * "controller/common/supplier/import/csv/converter" if you need a
		 * specific setting for the job controller. Otherwise, you should
		 * use the shared option for consistency.
		 *
		 * @param array Associative list of position/converter name (or list of names) pairs
		 * @since 2020.07
		 * @category Developer
		 * @see controller/jobs/supplier/import/csv/domains
		 * @see controller/jobs/supplier/import/csv/mapping
		 * @see controller/jobs/supplier/import/csv/skip-lines
		 * @see controller/jobs/supplier/import/csv/strict
		 * @see controller/jobs/supplier/import/csv/backup
		 * @see controller/common/supplier/import/csv/max-size
		 */
		$converters = $config->get( 'controller/jobs/supplier/import/csv/converter', $converters );


		/** controller/common/supplier/import/csv/max-size
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
		 * @category Developer
		 * @see controller/common/supplier/import/csv/domains
		 * @see controller/common/supplier/import/csv/mapping
		 * @see controller/common/supplier/import/csv/converter
		 */
		$maxcnt = (int) $config->get( 'controller/common/supplier/import/csv/max-size', 1000 );


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
		 * @category Developer
		 * @see controller/jobs/supplier/import/csv/domains
		 * @see controller/jobs/supplier/import/csv/mapping
		 * @see controller/jobs/supplier/import/csv/converter
		 * @see controller/jobs/supplier/import/csv/strict
		 * @see controller/jobs/supplier/import/csv/backup
		 * @see controller/common/supplier/import/csv/max-size
		 */
		$skiplines = (int) $config->get( 'controller/jobs/supplier/import/csv/skip-lines', 0 );


		/** controller/jobs/supplier/import/csv/strict
		 * Log all columns from the file that are not mapped and therefore not imported
		 *
		 * Depending on the mapping, there can be more columns in the CSV file
		 * than those which will be imported. This can be by purpose if you want
		 * to import only selected columns or if you've missed to configure one
		 * or more columns. This configuration option will log all columns that
		 * have not been imported if set to true. Otherwise, the left over fields
		 * in the imported line will be silently ignored.
		 *
		 * @param boolen True if not imported columns should be logged, false if not
		 * @since 2020.07
		 * @category User
		 * @category Developer
		 * @see controller/jobs/supplier/import/csv/domains
		 * @see controller/jobs/supplier/import/csv/mapping
		 * @see controller/jobs/supplier/import/csv/skip-lines
		 * @see controller/jobs/supplier/import/csv/converter
		 * @see controller/jobs/supplier/import/csv/backup
		 * @see controller/common/supplier/import/csv/max-size
		 */
		$strict = (bool) $config->get( 'controller/jobs/supplier/import/csv/strict', true );


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
		 * by the PHP strftime() function to create dynamic paths, e.g. "backup/%Y-%m-%d"
		 * which would create "backup/2000-01-01". For more information about the
		 * strftime() placeholders, please have a look into the PHP documentation of
		 * the {@link http://php.net/manual/en/function.strftime.php strftime() function}.
		 *
		 * **Note:** If no backup name is configured, the file or directory
		 * won't be moved away. Please make also sure that the parent directory
		 * and the new directory are writable so the file or directory could be
		 * moved.
		 *
		 * @param integer Name of the backup file, optionally with date/time placeholders
		 * @since 2020.07
		 * @category Developer
		 * @see controller/jobs/supplier/import/csv/domains
		 * @see controller/jobs/supplier/import/csv/mapping
		 * @see controller/jobs/supplier/import/csv/skip-lines
		 * @see controller/jobs/supplier/import/csv/converter
		 * @see controller/jobs/supplier/import/csv/strict
		 * @see controller/common/supplier/import/csv/max-size
		 */
		$backup = $config->get( 'controller/jobs/supplier/import/csv/backup' );


		if( !isset( $mappings['item'] ) || !is_array( $mappings['item'] ) )
		{
			$msg = sprintf( 'Required mapping key "%1$s" is missing or contains no array', 'item' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		try
		{
			$procMappings = $mappings;
			unset( $procMappings['item'] );

			$codePos = $this->getCodePosition( $mappings['item'] );
			$convlist = $this->getConverterList( $converters );
			$processor = $this->getProcessors( $procMappings );
			$supplierMap = $this->getSupplierMap( $domains );
			$container = $this->getContainer();
			$path = $container->getName();


			$msg = sprintf( 'Started supplier import from "%1$s" (%2$s)', $path, __CLASS__ );
			$logger->log( $msg, Log::NOTICE, 'import/csv/supplier' );

			foreach( $container as $content )
			{
				$name = $content->getName();

				for( $i = 0; $i < $skiplines; $i++ )
				{
					$content->next();
				}

				while( ( $data = $this->getData( $content, $maxcnt, $codePos ) ) !== [] )
				{
					$data = $this->convertData( $convlist, $data );
					$errcnt = $this->import( $supplierMap, $data, $mappings['item'], $processor, $strict );
					$chunkcnt = count( $data );

					$str = 'Imported supplier lines from "%1$s": %2$d/%3$d (%4$s)';
					$msg = sprintf( $str, $name, $chunkcnt - $errcnt, $chunkcnt, __CLASS__ );
					$logger->log( $msg, Log::NOTICE, 'import/csv/supplier' );

					$errors += $errcnt;
					$total += $chunkcnt;
					unset( $data );
				}
			}

			$container->close();
		}
		catch( \Exception $e )
		{
			$logger->log( 'Supplier import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), Log::ERR, 'import/csv/supplier' );
			$this->mail( 'Supplier CSV import error', $e->getMessage() . "\n" . $e->getTraceAsString() );
			throw new \Aimeos\Controller\Jobs\Exception( $e->getMessage() );
		}

		$str = 'Finished supplier import from "%1$s": %2$d successful, %3$s errors, %4$s total (%5$s)';
		$msg = sprintf( $str, $path, $total - $errors, $errors, $total, __CLASS__ );
		$logger->log( $msg, Log::NOTICE, 'import/csv/supplier' );

		if( $errors > 0 )
		{
			$msg = sprintf( 'Invalid supplier lines in "%1$s": %2$d/%3$d', $path, $errors, $total );
			$this->mail( 'Supplier CSV import error', $msg );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		if( !empty( $backup ) && @rename( $path, strftime( $backup ) ) === false )
		{
			$msg = sprintf( 'Unable to move imported file "%1$s" to "%2$s"', $path, strftime( $backup ) );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}
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
			if( $key === 'supplier.code' )
			{
				return $pos;
			}
		}

		throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'No "supplier.code" column in CSV mapping found' ) );
	}


	/**
	 * Opens and returns the container which includes the supplier data
	 *
	 * @return \Aimeos\MW\Container\Iface Container object
	 */
	protected function getContainer() : \Aimeos\MW\Container\Iface
	{
		$config = $this->getContext()->getConfig();

		/** controller/jobs/supplier/import/csv/location
		 * File or directory where the content is stored which should be imported
		 *
		 * You need to configure the file or directory that acts as container
		 * for the CSV files that should be imported. It should be an absolute
		 * path to be sure but can be relative path if you absolutely know from
		 * where the job will be executed from.
		 *
		 * The path can point to any supported container format as long as the
		 * content is in CSV format, e.g.
		 *
		 * * Directory container / CSV file
		 * * Zip container / compressed CSV file
		 *
		 * @param string Absolute file or directory path
		 * @since 2020.07
		 * @category Developer
		 * @category User
		 * @see controller/jobs/supplier/import/csv/container/type
		 * @see controller/jobs/supplier/import/csv/container/content
		 * @see controller/jobs/supplier/import/csv/container/options
		 */
		$location = $config->get( 'controller/jobs/supplier/import/csv/location' );

		/** controller/jobs/supplier/import/csv/container/type
		 * Nave of the container type to read the data from
		 *
		 * The container type tells the importer how it should retrieve the data.
		 * There are currently three container types that support the necessary
		 * CSV content:
		 *
		 * * Directory
		 * * Zip
		 *
		 * @param string Container type name
		 * @since 2020.07
		 * @category Developer
		 * @category User
		 * @see controller/jobs/supplier/import/csv/location
		 * @see controller/jobs/supplier/import/csv/container/content
		 * @see controller/jobs/supplier/import/csv/container/options
		 */
		$container = $config->get( 'controller/jobs/supplier/import/csv/container/type', 'Directory' );

		/** controller/jobs/supplier/import/csv/container/content
		 * Name of the content type inside the container to read the data from
		 *
		 * The content type must always be a CSV-like format and there are
		 * currently two format types that are supported:
		 *
		 * * CSV
		 *
		 * @param array Content type name
		 * @since 2020.07
		 * @category Developer
		 * @category User
		 * @see controller/jobs/supplier/import/csv/location
		 * @see controller/jobs/supplier/import/csv/container/type
		 * @see controller/jobs/supplier/import/csv/container/options
		 */
		$content = $config->get( 'controller/jobs/supplier/import/csv/container/content', 'CSV' );

		/** controller/jobs/supplier/import/csv/container/options
		 * List of file container options for the supplier import files
		 *
		 * Some container/content type allow you to hand over additional settings
		 * for configuration. Please have a look at the article about
		 * {@link http://aimeos.org/docs/Developers/Utility/Create_and_read_files container/content files}
		 * for more information.
		 *
		 * @param array Associative list of option name/value pairs
		 * @since 2020.07
		 * @category Developer
		 * @category User
		 * @see controller/jobs/supplier/import/csv/location
		 * @see controller/jobs/supplier/import/csv/container/content
		 * @see controller/jobs/supplier/import/csv/container/type
		 */
		$options = $config->get( 'controller/jobs/supplier/import/csv/container/options', [] );

		if( $location === null )
		{
			$msg = sprintf( 'Required configuration for "%1$s" is missing', 'controller/jobs/supplier/import/csv/location' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return \Aimeos\MW\Container\Factory::getContainer( $location, $container, $content, $options );
	}


	/**
	 * Returns the supplier items building the tree as list
	 *
	 * @param array $domains List of domain names whose items should be fetched too
	 * @return array Associative list of supplier codes as keys and items implementing \Aimeos\MShop\Supplier\Item\Iface as values
	 */
	protected function getSupplierMap( array $domains ) : array
	{
		$map = [];
		$manager = \Aimeos\MShop::create( $this->getContext(), 'supplier' );
		$search = $manager->filter()->slice( 0, 0x7fffffff );

		foreach( $manager->search( $search, $domains ) as $item )
		{
			$map[$item->getCode()] = $item;
		}

		return $map;
	}


	/**
	 * Imports the CSV data and creates new suppliers or updates existing ones
	 *
	 * @param array &$supplierMap Associative list of supplier items with codes as keys and items implementing \Aimeos\MShop\Supplier\Item\Iface as values
	 * @param array $data Associative list of import data as index/value pairs
	 * @param array $mapping Associative list of positions and domain item keys
	 * @param \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Iface $processor Processor object
	 * @param bool $strict Log columns not mapped or silently ignore them
	 * @return int Number of suppliers that couldn't be imported
	 * @throws \Aimeos\Controller\Jobs\Exception
	 */
	protected function import( array &$supplierMap, array $data, array $mapping,
		\Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Iface $processor, bool $strict ) : int
	{
		$errors = 0;
		$context = $this->getContext();
		$manager = \Aimeos\MShop::create( $context, 'supplier' );

		foreach( $data as $code => $list )
		{
			$manager->begin();

			try
			{
				$code = trim( $code );

				if( isset( $supplierMap[$code] ) )
				{
					$item = $supplierMap[$code];
				} else
				{
					$item = $manager->create();
				}

				$map = $this->getMappedChunk( $list, $mapping );

				if( isset( $map[0] ) )
				{
					$map = $map[0]; // there can only be one chunk for the base supplier data
					$item->fromArray( $map, true );

					$list = $processor->process( $item, $list );
					$supplierMap[$code] = $item;

					$manager->save( $item );
				}

				$manager->commit();
			}
			catch( \Exception $e )
			{
				$manager->rollback();

				$msg = sprintf( 'Unable to import supplier with code "%1$s": %2$s', $code, $e->getMessage() );
				$context->getLogger()->log( $msg, Log::ERR, 'import/csv/supplier' );

				$errors++;
			}

			if( $strict && !empty( $list ) )
			{
				$context->getLogger()->log( 'Not imported: ' . print_r( $list, true ), Log::ERR, 'import/csv/supplier' );
			}
		}

		return $errors;
	}
}
