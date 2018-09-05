<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Catalog\Import\Csv;


/**
 * Job controller for CSV catalog imports.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Common\Catalog\Import\Csv\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Catalog import CSV' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Imports new and updates existing categories from CSV files' );
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
		$domains = array( 'media', 'text' );
		$mappings = $this->getDefaultMapping();


		/** controller/common/catalog/import/csv/domains
		 * List of item domain names that should be retrieved along with the catalog items
		 *
		 * For efficient processing, the items associated to the catalogs can be
		 * fetched to, minimizing the number of database queries required. To be
		 * most effective, the list of item domain names should be used in the
		 * mapping configuration too, so the retrieved items will be used during
		 * the import.
		 *
		 * @param array Associative list of MShop item domain names
		 * @since 2018.04
		 * @category Developer
		 * @see controller/common/catalog/import/csv/mapping
		 * @see controller/common/catalog/import/csv/converter
		 * @see controller/common/catalog/import/csv/max-size
		 */
		$domains = $config->get( 'controller/common/catalog/import/csv/domains', $domains );

		/** controller/jobs/catalog/import/csv/domains
		 * List of item domain names that should be retrieved along with the catalog items
		 *
		 * This configuration setting overwrites the shared option
		 * "controller/common/catalog/import/csv/domains" if you need a
		 * specific setting for the job controller. Otherwise, you should
		 * use the shared option for consistency.
		 *
		 * @param array Associative list of MShop item domain names
		 * @since 2018.04
		 * @category Developer
		 * @see controller/jobs/catalog/import/csv/mapping
		 * @see controller/jobs/catalog/import/csv/skip-lines
		 * @see controller/jobs/catalog/import/csv/converter
		 * @see controller/jobs/catalog/import/csv/strict
		 * @see controller/jobs/catalog/import/csv/backup
		 * @see controller/common/catalog/import/csv/max-size
		 */
		$domains = $config->get( 'controller/jobs/catalog/import/csv/domains', $domains );


		/** controller/common/catalog/import/csv/mapping
		 * List of mappings between the position in the CSV file and item keys
		 *
		 * The importer have to know which data is at which position in the CSV
		 * file. Therefore, you need to specify a mapping between each position
		 * and the MShop domain item key (e.g. "catalog.code") it represents.
		 *
		 * You can use all domain item keys which are used in the fromArray()
		 * methods of the item classes. The "*.type" item keys will be
		 * automatically converted to their "*.typeid" representation. You only
		 * need to make sure that the corresponding type is available in the
		 * database.
		 *
		 * These mappings are grouped together by their processor names, which
		 * are responsible for importing the data, e.g. all mappings in "item"
		 * will be processed by the base catalog importer while the mappings in
		 * "text" will be imported by the text processor.
		 *
		 * @param array Associative list of processor names and lists of key/position pairs
		 * @since 2018.04
		 * @category Developer
		 * @see controller/common/catalog/import/csv/domains
		 * @see controller/common/catalog/import/csv/converter
		 * @see controller/common/catalog/import/csv/max-size
		 */
		$mappings = $config->get( 'controller/common/catalog/import/csv/mapping', $mappings );

		/** controller/jobs/catalog/import/csv/mapping
		 * List of mappings between the position in the CSV file and item keys
		 *
		 * This configuration setting overwrites the shared option
		 * "controller/common/catalog/import/csv/mapping" if you need a
		 * specific setting for the job controller. Otherwise, you should
		 * use the shared option for consistency.
		 *
		 * @param array Associative list of processor names and lists of key/position pairs
		 * @since 2018.04
		 * @category Developer
		 * @see controller/jobs/catalog/import/csv/domains
		 * @see controller/jobs/catalog/import/csv/skip-lines
		 * @see controller/jobs/catalog/import/csv/converter
		 * @see controller/jobs/catalog/import/csv/strict
		 * @see controller/jobs/catalog/import/csv/backup
		 * @see controller/common/catalog/import/csv/max-size
		 */
		$mappings = $config->get( 'controller/jobs/catalog/import/csv/mapping', $mappings );


		/** controller/common/catalog/import/csv/converter
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
		 * '''Note:''' Keep in mind that the position of the CSV fields start at
		 * zero (0). If you only need to convert a few fields, you don't have to
		 * configure all fields. Only specify the positions in the array you
		 * really need!
		 *
		 * @param array Associative list of position/converter name (or list of names) pairs
		 * @since 2018.04
		 * @category Developer
		 * @see controller/common/catalog/import/csv/domains
		 * @see controller/common/catalog/import/csv/mapping
		 * @see controller/common/catalog/import/csv/max-size
		 */
		$converters = $config->get( 'controller/common/catalog/import/csv/converter', [] );

		/** controller/jobs/catalog/import/csv/converter
		 * List of converter names for the values at the position in the CSV file
		 *
		 * This configuration setting overwrites the shared option
		 * "controller/common/catalog/import/csv/converter" if you need a
		 * specific setting for the job controller. Otherwise, you should
		 * use the shared option for consistency.
		 *
		 * @param array Associative list of position/converter name (or list of names) pairs
		 * @since 2018.04
		 * @category Developer
		 * @see controller/jobs/catalog/import/csv/domains
		 * @see controller/jobs/catalog/import/csv/mapping
		 * @see controller/jobs/catalog/import/csv/skip-lines
		 * @see controller/jobs/catalog/import/csv/strict
		 * @see controller/jobs/catalog/import/csv/backup
		 * @see controller/common/catalog/import/csv/max-size
		 */
		$converters = $config->get( 'controller/jobs/catalog/import/csv/converter', $converters );


		/** controller/common/catalog/import/csv/max-size
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
		 * @since 2018.04
		 * @category Developer
		 * @see controller/common/catalog/import/csv/domains
		 * @see controller/common/catalog/import/csv/mapping
		 * @see controller/common/catalog/import/csv/converter
		 */
		$maxcnt = (int) $config->get( 'controller/common/catalog/import/csv/max-size', 1000 );


		/** controller/jobs/catalog/import/csv/skip-lines
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
		 * @see controller/jobs/catalog/import/csv/domains
		 * @see controller/jobs/catalog/import/csv/mapping
		 * @see controller/jobs/catalog/import/csv/converter
		 * @see controller/jobs/catalog/import/csv/strict
		 * @see controller/jobs/catalog/import/csv/backup
		 * @see controller/common/catalog/import/csv/max-size
		 */
		$skiplines = (int) $config->get( 'controller/jobs/catalog/import/csv/skip-lines', 0 );


		/** controller/jobs/catalog/import/csv/strict
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
		 * @since 2015.08
		 * @category User
		 * @category Developer
		 * @see controller/jobs/catalog/import/csv/domains
		 * @see controller/jobs/catalog/import/csv/mapping
		 * @see controller/jobs/catalog/import/csv/skip-lines
		 * @see controller/jobs/catalog/import/csv/converter
		 * @see controller/jobs/catalog/import/csv/backup
		 * @see controller/common/catalog/import/csv/max-size
		 */
		$strict = (bool) $config->get( 'controller/jobs/catalog/import/csv/strict', true );


		/** controller/jobs/catalog/import/csv/backup
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
		 * @since 2018.04
		 * @category Developer
		 * @see controller/jobs/catalog/import/csv/domains
		 * @see controller/jobs/catalog/import/csv/mapping
		 * @see controller/jobs/catalog/import/csv/skip-lines
		 * @see controller/jobs/catalog/import/csv/converter
		 * @see controller/jobs/catalog/import/csv/strict
		 * @see controller/common/catalog/import/csv/max-size
		 */
		$backup = $config->get( 'controller/jobs/catalog/import/csv/backup' );


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
			$catalogMap = $this->getCatalogMap( $domains );
			$container = $this->getContainer();
			$path = $container->getName();


			$msg = sprintf( 'Started catalog import from "%1$s" (%2$s)', $path, __CLASS__ );
			$logger->log( $msg, \Aimeos\MW\Logger\Base::NOTICE );

			foreach( $container as $content )
			{
				$name = $content->getName();

				for( $i = 0; $i < $skiplines; $i++ ) {
					$content->next();
				}

				while( ( $data = $this->getData( $content, $maxcnt, $codePos ) ) !== [] )
				{
					$data = $this->convertData( $convlist, $data );
					$errcnt = $this->import( $catalogMap, $data, $mappings['item'], $processor, $strict );
					$chunkcnt = count( $data );

					$msg = 'Imported catalog lines from "%1$s": %2$d/%3$d (%4$s)';
					$logger->log( sprintf( $msg, $name, $chunkcnt - $errcnt, $chunkcnt, __CLASS__ ), \Aimeos\MW\Logger\Base::NOTICE );

					$errors += $errcnt;
					$total += $chunkcnt;
					unset( $data );
				}
			}

			$container->close();
		}
		catch( \Exception $e )
		{
			$logger->log( 'Catalog import error: ' . $e->getMessage() );
			$logger->log( $e->getTraceAsString() );

			throw new \Aimeos\Controller\Jobs\Exception( $e->getMessage() );
		}

		$msg = 'Finished catalog import from "%1$s": %2$d successful, %3$s errors, %4$s total (%5$s)';
		$logger->log( sprintf( $msg, $path, $total - $errors, $errors, $total, __CLASS__ ), \Aimeos\MW\Logger\Base::NOTICE );

		if( $errors > 0 )
		{
			$msg = sprintf( 'Invalid catalog lines in "%1$s": %2$d/%3$d', $path, $errors, $total );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		if( !empty( $backup ) && @rename( $path, strftime( $backup ) ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Unable to move imported file' ) );
		}
	}


	/**
	 * Returns the position of the "catalog.code" column from the catalog item mapping
	 *
	 * @param array $mapping Mapping of the "item" columns with position as key and code as value
	 * @return integer Position of the "catalog.code" column
	 * @throws \Aimeos\Controller\Jobs\Exception If no mapping for "catalog.code" is found
	 */
	protected function getCodePosition( array $mapping )
	{
		foreach( $mapping as $pos => $key )
		{
			if( $key === 'catalog.code' ) {
				return $pos;
			}
		}

		throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'No "catalog.code" column in CSV mapping found' ) );
	}


	/**
	 * Opens and returns the container which includes the catalog data
	 *
	 * @return \Aimeos\MW\Container\Iface Container object
	 */
	protected function getContainer()
	{
		$config = $this->getContext()->getConfig();

		/** controller/jobs/catalog/import/csv/location
		 * File or directory where the content is stored which should be imported
		 *
		 * You need to configure the file or directory that acts as container
		 * for the CSV files that should be imported. It should be an absolute
		 * path to be sure but can be relative path if you absolutely know from
		 * where the job will be executed from.
		 *
		 * The path can point to any supported container format as long as the
		 * content is in CSV format, e.g.
		 * * Directory container / CSV file
		 * * Zip container / compressed CSV file
		 * * PHPExcel container / PHPExcel sheet
		 *
		 * @param string Absolute file or directory path
		 * @since 2018.04
		 * @category Developer
		 * @category User
		 * @see controller/jobs/catalog/import/csv/container/type
		 * @see controller/jobs/catalog/import/csv/container/content
		 * @see controller/jobs/catalog/import/csv/container/options
		 */
		$location = $config->get( 'controller/jobs/catalog/import/csv/location' );

		/** controller/jobs/catalog/import/csv/container/type
		 * Nave of the container type to read the data from
		 *
		 * The container type tells the importer how it should retrieve the data.
		 * There are currently three container types that support the necessary
		 * CSV content:
		 * * Directory
		 * * Zip
		 * * PHPExcel
		 *
		 * '''Note:''' For the PHPExcel container, you need to install the
		 * "ai-container" extension.
		 *
		 * @param string Container type name
		 * @since 2018.04
		 * @category Developer
		 * @category User
		 * @see controller/jobs/catalog/import/csv/location
		 * @see controller/jobs/catalog/import/csv/container/content
		 * @see controller/jobs/catalog/import/csv/container/options
		 */
		$container = $config->get( 'controller/jobs/catalog/import/csv/container/type', 'Directory' );

		/** controller/jobs/catalog/import/csv/container/content
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
		 * @since 2018.04
		 * @category Developer
		 * @category User
		 * @see controller/jobs/catalog/import/csv/location
		 * @see controller/jobs/catalog/import/csv/container/type
		 * @see controller/jobs/catalog/import/csv/container/options
		 */
		$content = $config->get( 'controller/jobs/catalog/import/csv/container/content', 'CSV' );

		/** controller/jobs/catalog/import/csv/container/options
		 * List of file container options for the catalog import files
		 *
		 * Some container/content type allow you to hand over additional settings
		 * for configuration. Please have a look at the article about
		 * {@link http://aimeos.org/docs/Developers/Utility/Create_and_read_files container/content files}
		 * for more information.
		 *
		 * @param array Associative list of option name/value pairs
		 * @since 2018.04
		 * @category Developer
		 * @category User
		 * @see controller/jobs/catalog/import/csv/location
		 * @see controller/jobs/catalog/import/csv/container/content
		 * @see controller/jobs/catalog/import/csv/container/type
		 */
		$options = $config->get( 'controller/jobs/catalog/import/csv/container/options', [] );

		if( $location === null )
		{
			$msg = sprintf( 'Required configuration for "%1$s" is missing', 'controller/jobs/catalog/import/csv/location' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return \Aimeos\MW\Container\Factory::getContainer( $location, $container, $content, $options );
	}


	/**
	 * Returns the catalog items building the tree as list
	 *
	 * @param array $domains List of domain names whose items should be fetched too
	 * @return array Associative list of catalog codes as keys and items implementing \Aimeos\MShop\Catalog\Item\Iface as values
	 */
	protected function getCatalogMap( array $domains )
	{
		$map = [];
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'catalog' );
		$search = $manager->createSearch()->setSlice( 0, 0x7fffffff );

		foreach( $manager->searchItems( $search, $domains ) as $item ) {
			$map[$item->getCode()] = $item;
		}

		return $map;
	}


	/**
	 * Returns the parent ID of the catalog node for the given code
	 *
	 * @param array $catalogMap Associative list of catalog items with codes as keys and items implementing \Aimeos\MShop\Catalog\Item\Iface as values
	 * @param array $map Associative list of catalog item key/value pairs
	 * @param string $code Catalog item code of the parent category
	 * @return string|null ID of the parent category or null for top level nodes
	 */
	protected function getParentId( array $catalogMap, array $map, $code )
	{
		if( !isset( $map['catalog.parent'] ) )
		{
			$msg = sprintf( 'Required column "%1$s" not found for code "%2$s"', 'catalog.parent', $code );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		$parent = trim( $map['catalog.parent'] );

		if( $parent != '' && !isset( $catalogMap[$parent] ) )
		{
			$msg = sprintf( 'Parent node for code "%1$s" not found', $parent );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return ( $parent != '' ? $catalogMap[$parent]->getId() : null );
	}


	/**
	 * Imports the CSV data and creates new categories or updates existing ones
	 *
	 * @param array &$catalogMap Associative list of catalog items with codes as keys and items implementing \Aimeos\MShop\Catalog\Item\Iface as values
	 * @param array $data Associative list of import data as index/value pairs
	 * @param array $mapping Associative list of positions and domain item keys
	 * @param \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Iface $processor Processor object
	 * @param boolean $strict Log columns not mapped or silently ignore them
	 * @return integer Number of catalogs that couldn't be imported
	 * @throws \Aimeos\Controller\Jobs\Exception
	 */
	protected function import( array &$catalogMap, array $data, array $mapping,
		\Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Iface $processor, $strict )
	{
		$errors = 0;
		$context = $this->getContext();
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'catalog' );

		foreach( $data as $code => $list )
		{
			$manager->begin();

			try
			{
				$code = trim( $code );

				if( isset( $catalogMap[$code] )  ) {
					$catalogItem = $catalogMap[$code];
				} else {
					$catalogItem = $manager->createItem();
				}

				$map = $this->getMappedChunk( $list, $mapping );

				if( isset( $map[0] ) )
				{
					$map = $map[0]; // there can only be one chunk for the base catalog data
					$parentid = $this->getParentId( $catalogMap, $map, $code );
					$catalogItem->fromArray( $this->addItemDefaults( $map ) );

					if( isset( $catalogMap[$code] ) )
					{
						$manager->moveItem( $catalogItem->getId(), $catalogItem->getParentId(), $parentid );
						$catalogItem = $manager->saveItem( $catalogItem );
					}
					else
					{
						$catalogItem = $manager->insertItem( $catalogItem, $parentid );
					}

					$list = $processor->process( $catalogItem, $list );
					$catalogMap[$code] = $catalogItem;

					$manager->saveItem( $catalogItem );
				}

				$manager->commit();
			}
			catch( \Exception $e )
			{
				$manager->rollback();

				$msg = sprintf( 'Unable to import catalog with code "%1$s": %2$s', $code, $e->getMessage() );
				$context->getLogger()->log( $msg );

				$errors++;
			}

			if( $strict && !empty( $list ) ) {
				$context->getLogger()->log( 'Not imported: ' . print_r( $list, true ) );
			}
		}

		return $errors;
	}


	/**
	 * Adds the catalog item default values and returns the resulting array
	 *
	 * @param array $list Associative list of domain item keys and their values, e.g. "catalog.status" => 1
	 * @return array Given associative list enriched by default values if they were not already set
	 */
	protected function addItemDefaults( array $list )
	{
		if( !isset( $list['catalog.status'] ) ) {
			$list['catalog.status'] = 1;
		}

		return $list;
	}
}
