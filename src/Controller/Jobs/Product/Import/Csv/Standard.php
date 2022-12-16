<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2022
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Product\Import\Csv;


/**
 * Job controller for CSV product imports.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Common\Product\Import\Csv\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/product/import/csv/name
	 * Class name of the used product suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Product\Import\Csv\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Product\Import\Csv\Mycsv
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/product/import/csv/name = Mycsv
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
	 * @since 2015.01
	 */

	/** controller/jobs/product/import/csv/decorators/excludes
	 * Excludes decorators added by the "common" option from the product import CSV job controller
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
	 *  controller/jobs/product/import/csv/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.01
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/product/import/csv/decorators/global
	 * @see controller/jobs/product/import/csv/decorators/local
	 */

	/** controller/jobs/product/import/csv/decorators/global
	 * Adds a list of globally available decorators only to the product import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/product/import/csv/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.01
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/product/import/csv/decorators/excludes
	 * @see controller/jobs/product/import/csv/decorators/local
	 */

	/** controller/jobs/product/import/csv/decorators/local
	 * Adds a list of local decorators only to the product import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Product\Import\Csv\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/product/import/csv/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Product\Import\Csv\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.01
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/product/import/csv/decorators/excludes
	 * @see controller/jobs/product/import/csv/decorators/global
	 */


	private $types;


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Product import CSV' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Imports new and updates existing products from CSV files' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		if( file_exists( $this->location() ) === false ) {
			return;
		}

		$mappings = $this->mapping();

		if( !isset( $mappings['item'] ) || !is_array( $mappings['item'] ) )
		{
			$msg = sprintf( 'Required mapping key "%1$s" is missing or contains no array', 'item' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		try
		{
			$procMappings = $mappings;
			unset( $procMappings['item'] );

			$total = $errors = 0;
			$logger = $this->context()->logger();

			$codePos = $this->getCodePosition( $mappings['item'] );
			$convlist = $this->getConverterList( $this->converters() );
			$processor = $this->getProcessors( $procMappings );
			$container = $this->getContainer();
			$path = $container->getName();

			$maxcnt = $this->max();
			$strict = $this->strict();
			$skiplines = $this->skip();
			$domains = $this->domains();

			$msg = sprintf( 'Started product import from "%1$s" (%2$s)', $path, __CLASS__ );
			$logger->notice( $msg, 'import/csv/product' );

			foreach( $container as $content )
			{
				$name = $content->getName();

				for( $i = 0; $i < $skiplines; $i++ ) {
					$content->next();
				}

				while( ( $data = $this->getData( $content, $maxcnt, $codePos ) ) !== [] )
				{
					$chunkcnt = count( $data );
					$data = $this->convertData( $convlist, $data );
					$products = $this->getProducts( array_keys( $data ), $domains );
					$errcnt = $this->import( $products, $data, $mappings['item'], [], $processor );

					$str = 'Imported product lines from "%1$s": %2$d/%3$d (%4$s)';
					$msg = sprintf( $str, $name, $chunkcnt - $errcnt, $chunkcnt, __CLASS__ );
					$logger->info( $msg, 'import/csv/product' );

					$errors += $errcnt;
					$total += $chunkcnt;
					unset( $products, $data );
				}
			}

			$container->close();
		}
		catch( \Exception $e )
		{
			$logger->error( 'Product import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'import/csv/product' );
			$this->mail( 'Product CSV import error', $e->getMessage() . "\n" . $e->getTraceAsString() );
			throw new \Aimeos\Controller\Jobs\Exception( $e->getMessage() );
		}

		$processor->finish();

		$msg = 'Finished product import from "%1$s": %2$d successful, %3$s errors, %4$s total (%5$s)';
		$logger->notice( sprintf( $msg, $path, $total - $errors, $errors, $total, __CLASS__ ), 'import/csv/product' );

		if( $errors > 0 )
		{
			$msg = sprintf( 'Invalid product lines in "%1$s": %2$d/%3$d', $path, $errors, $total );
			$this->mail( 'Product CSV import', $msg );
		}

		if( !empty( $backup = $this->backup() ) && @rename( $path, $backup = \Aimeos\Base\Str::strtime( $backup ) ) === false )
		{
			$msg = sprintf( 'Unable to move imported file "%1$s" to "%2$s"', $path, $backup );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}
	}


	/**
	 * Returns the directory for storing imported files
	 *
	 * @return string Directory for storing imported files
	 */
	protected function backup() : string
	{
		/** controller/jobs/product/import/csv/backup
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
		 * **Note:** If no backup name is configured, the file or directory
		 * won't be moved away. Please make also sure that the parent directory
		 * and the new directory are writable so the file or directory could be
		 * moved.
		 *
		 * @param integer Name of the backup file, optionally with date/time placeholders
		 * @since 2018.04
		 * @see controller/jobs/product/import/csv/converter
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/mapping
		 * @see controller/jobs/product/import/csv/max-size
		 * @see controller/jobs/product/import/csv/skip-lines
		 * @see controller/jobs/product/import/csv/strict
		 */
		return (string) $this->context()->config()->get( 'controller/jobs/product/import/csv/backup' );
	}


	/**
	 * Checks the given product type for validity
	 *
	 * @param string|null $type Product type or null for no type
	 * @return string New product type
	 */
	protected function checkType( string $type = null ) : string
	{
		if( !isset( $this->types ) )
		{
			$this->types = [];

			$manager = \Aimeos\MShop::create( $this->context(), 'product/type' );
			$search = $manager->filter()->slice( 0, 10000 );

			foreach( $manager->search( $search ) as $item ) {
				$this->types[$item->getCode()] = $item->getCode();
			}
		}

		return ( isset( $this->types[$type] ) ? $this->types[$type] : 'default' );
	}


	/**
	 * Returns the list of converter names for the values at the position in the CSV file
	 *
	 * @return array List of converter names for the values at the position in the CSV file
	 */
	protected function converters() : array
	{
		/** controller/jobs/product/import/csv/converter
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
		 *  [1 => 'Text/LatinUTF8']
		 *
		 * Similarly, you can also apply several converters at once to the same
		 * field:
		 *
		 *  [1 => ['Text/LatinUTF8', 'DateTime/EnglishISO']]
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
		 * @since 2018.04
		 * @see controller/jobs/product/import/csv/backup
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/mapping
		 * @see controller/jobs/product/import/csv/max-size
		 * @see controller/jobs/product/import/csv/skip-lines
		 * @see controller/jobs/product/import/csv/strict
		 */
		return (array) $this->context()->config()->get( 'controller/jobs/product/import/csv/converter', [] );
	}


	/**
	 * Returns the list of domain names that should be retrieved along with the attribute items
	 *
	 * @return array List of domain names
	 */
	protected function domains() : array
	{
		/** controller/jobs/product/import/csv/domains
		 * List of item domain names that should be retrieved along with the product items
		 *
		 * For efficient processing, the items associated to the products can be
		 * fetched to, minimizing the number of database queries required. To be
		 * most effective, the list of item domain names should be used in the
		 * mapping configuration too, so the retrieved items will be used during
		 * the import.
		 *
		 * @param array Associative list of MShop item domain names
		 * @since 2018.04
		 * @see controller/jobs/product/import/csv/backup
		 * @see controller/jobs/product/import/csv/converter
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/mapping
		 * @see controller/jobs/product/import/csv/max-size
		 * @see controller/jobs/product/import/csv/skip-lines
		 * @see controller/jobs/product/import/csv/strict
		 */
		return $this->context()->config()->get( 'controller/jobs/product/import/csv/domains', ['media', 'text'] );
	}


	/**
	 * Returns the position of the "product.code" column from the product item mapping
	 *
	 * @param array $mapping Mapping of the "item" columns with position as key and code as value
	 * @return int Position of the "product.code" column
	 * @throws \Aimeos\Controller\Jobs\Exception If no mapping for "product.code" is found
	 */
	protected function getCodePosition( array $mapping ) : int
	{
		foreach( $mapping as $pos => $key )
		{
			if( $key === 'product.code' ) {
				return $pos;
			}
		}

		throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'No "product.code" column in CSV mapping found' ) );
	}


	/**
	 * Opens and returns the container which includes the product data
	 *
	 * @return \Aimeos\MW\Container\Iface Container object
	 */
	protected function getContainer() : \Aimeos\MW\Container\Iface
	{
		$config = $this->context()->config();

		/** controller/jobs/product/import/csv/location
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
		 * @since 2015.05
		 * @category User
		 * @see controller/jobs/product/import/csv/container/type
		 * @see controller/jobs/product/import/csv/container/content
		 * @see controller/jobs/product/import/csv/container/options
		 */
		$location = $config->get( 'controller/jobs/product/import/csv/location' );

		/** controller/jobs/product/import/csv/container/type
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
		 * @since 2015.05
		 * @category User
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/container/content
		 * @see controller/jobs/product/import/csv/container/options
		 */
		$container = $config->get( 'controller/jobs/product/import/csv/container/type', 'Directory' );

		/** controller/jobs/product/import/csv/container/content
		 * Name of the content type inside the container to read the data from
		 *
		 * The content type must always be a CSV-like format and there are
		 * currently two format types that are supported:
		 *
		 * * CSV
		 *
		 * @param array Content type name
		 * @since 2015.05
		 * @category User
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/container/type
		 * @see controller/jobs/product/import/csv/container/options
		 */
		$content = $config->get( 'controller/jobs/product/import/csv/container/content', 'CSV' );

		/** controller/jobs/product/import/csv/container/options
		 * List of file container options for the product import files
		 *
		 * Some container/content type allow you to hand over additional settings
		 * for configuration. Please have a look at the article about
		 * {@link http://aimeos.org/docs/Developers/Utility/Create_and_read_files container/content files}
		 * for more information.
		 *
		 * @param array Associative list of option name/value pairs
		 * @since 2015.05
		 * @category User
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/container/content
		 * @see controller/jobs/product/import/csv/container/type
		 */
		$options = $config->get( 'controller/jobs/product/import/csv/container/options', [] );

		if( $location === null )
		{
			$msg = sprintf( 'Required configuration for "%1$s" is missing', 'controller/jobs/product/import/csv/location' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return \Aimeos\MW\Container\Factory::getContainer( $location, $container, $content, $options );
	}


	/**
	 * Imports the CSV data and creates new products or updates existing ones
	 *
	 * @param \Aimeos\Map $products List of products items implementing \Aimeos\MShop\Product\Item\Iface
	 * @param array $data Associative list of import data as index/value pairs
	 * @param array $mapping Associative list of positions and domain item keys
	 * @param array $types List of allowed product type codes
	 * @param \Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface $processor Processor object
	 * @return int Number of products that couldn't be imported
	 * @throws \Aimeos\Controller\Jobs\Exception
	 */
	protected function import( \Aimeos\Map $products, array $data, array $mapping, array $types,
		\Aimeos\Controller\Common\Product\Import\Csv\Processor\Iface $processor ) : int
	{
		$items = [];
		$errors = 0;
		$context = $this->context();
		$manager = \Aimeos\MShop::create( $context, 'index' );

		foreach( $data as $code => $list )
		{
			$manager->begin();

			try
			{
				$code = trim( $code );
				$product = $products[$code] ?? $manager->create();
				$map = current( $this->getMappedChunk( $list, $mapping ) ); // there can only be one chunk for the base product data

				if( $map )
				{
					$type = $this->checkType( $this->val( $map, 'product.type', $product->getType() ) );

					if( $config = $this->val( $map, 'product.config' ) ) {
						$map['product.config'] = json_decode( $config ) ?: [];
					}

					$product = $product->fromArray( $map, true );
					$product = $manager->save( $product->setType( $type ) );

					$processor->process( $product, $list );

					$product = $manager->save( $product );
					$items[$product->getId()] = $product;
				}

				$manager->commit();
			}
			catch( \Exception $e )
			{
				$manager->rollback();

				$msg = sprintf( 'Unable to import product with code "%1$s": %2$s', $code, $e->getMessage() );
				$context->logger()->error( $msg, 'import/csv/product' );

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
		/** controller/jobs/product/import/csv/location
		 * File or directory where the content is stored which should be imported
		 *
		 * You need to configure the CSV file or directory with the CSV files that
		 * should be imported. It should be an absolute path to be sure but can be
		 * relative path if you absolutely know from where the job will be executed
		 * from.
		 *
		 * @param string Relative path to the CSV files
		 * @since 2015.08
		 * @see controller/jobs/product/import/csv/backup
		 * @see controller/jobs/product/import/csv/converter
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/mapping
		 * @see controller/jobs/product/import/csv/max-size
		 * @see controller/jobs/product/import/csv/skip-lines
		 */
		return (string) $this->context()->config()->get( 'controller/jobs/product/import/csv/location', 'product' );
	}


	/**
	 * Returns the CSV column mapping
	 *
	 * @return array CSV column mapping
	 */
	protected function mapping() : array
	{
		/** controller/jobs/product/import/csv/mapping
		 * List of mappings between the position in the CSV file and item keys
		 *
		 * The importer have to know which data is at which position in the CSV
		 * file. Therefore, you need to specify a mapping between each position
		 * and the MShop domain item key (e.g. "product.code") it represents.
		 *
		 * You can use all domain item keys which are used in the fromArray()
		 * methods of the item classes.
		 *
		 * These mappings are grouped together by their processor names, which
		 * are responsible for importing the data, e.g. all mappings in "item"
		 * will be processed by the base product importer while the mappings in
		 * "text" will be imported by the text processor.
		 *
		 * @param array Associative list of processor names and lists of key/position pairs
		 * @since 2018.04
		 * @see controller/jobs/product/import/csv/backup
		 * @see controller/jobs/product/import/csv/converter
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/max-size
		 * @see controller/jobs/product/import/csv/skip-lines
		 * @see controller/jobs/product/import/csv/strict
		 */
		return (array) $this->context()->config()->get( 'controller/jobs/product/import/csv/mapping', $this->getDefaultMapping() );
	}


	/**
	 * Returns the maximum number of CSV rows to import at once
	 *
	 * @return int Maximum number of CSV rows to import at once
	 */
	protected function max() : int
	{
		/** controller/jobs/product/import/csv/max-size
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
		 * @see controller/jobs/product/import/csv/backup
		 * @see controller/jobs/product/import/csv/converter
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/mapping
		 * @see controller/jobs/product/import/csv/skip-lines
		 * @see controller/jobs/product/import/csv/strict
		 */
		return (int) $this->context()->config()->get( 'controller/jobs/product/import/csv/max-size', 1000 );
	}


	/**
	 * Returns the number of rows skipped in front of each CSV files
	 *
	 * @return int Number of rows skipped in front of each CSV files
	 */
	protected function skip() : int
	{
		/** controller/jobs/product/import/csv/skip-lines
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
		 * @see controller/jobs/product/import/csv/backup
		 * @see controller/jobs/product/import/csv/converter
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/mapping
		 * @see controller/jobs/product/import/csv/max-size
		 * @see controller/jobs/product/import/csv/strict
		 */
		return (int) $this->context()->config()->get( 'controller/jobs/product/import/csv/skip-lines', 0 );
	}


	/**
	 * Returns if all columns from the file should be logged that are not mapped and therefore not imported
	 */
	protected function strict() : bool
	{
		/** controller/jobs/product/import/csv/strict
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
		 * @see controller/jobs/product/import/csv/backup
		 * @see controller/jobs/product/import/csv/converter
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/mapping
		 * @see controller/jobs/product/import/csv/max-size
		 * @see controller/jobs/product/import/csv/skip-lines
		 */
		return (bool) $this->context()->config()->get( 'controller/jobs/product/import/csv/strict', true );
	}
}
