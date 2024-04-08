<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2024
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
	extends \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Base
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


	private ?array $types = null;


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
		$context = $this->context();
		$logger = $context->logger();
		$date = date( 'Y-m-d H:i:s' );

		try
		{
			$files = $errors = 0;
			$fs = $context->fs( 'fs-import' );
			$site = $context->locale()->getSiteItem()->getCode();
			$location = $this->location() . '/' . $site;

			if( $fs->isDir( $location ) === false ) {
				return;
			}

			$logger->info( sprintf( 'Started product import from "%1$s"', $location ), 'import/xml/product' );

			foreach( map( $fs->scan( $location ) )->sort() as $filename )
			{
				$path = $location . '/' . $filename;

				if( $filename[0] === '.' || $fs instanceof \Aimeos\Base\Filesystem\DirIface && $fs->isDir( $path ) ) {
					continue;
				}

				$errors = $this->import( $path );
				$files++;
			}

			/** controller/jobs/product/import/csv/cleanup
			 * Deletes all products with categories which havn't been updated
			 *
			 * By default, the product importer only adds new and updates existing
			 * products but doesn't delete any products. If you want to remove all
			 * products which haven't been updated during the import, then set this
			 * configuration option to "true". This will remove all products which
			 * are not assigned to any category but keep the ones without categories,
			 * e.g. rebate products.
			 *
			 * @param bool TRUE to delete all untouched products, FALSE to keep them
			 * @since 2023.10
			 * @see controller/jobs/product/import/csv/backup
			 * @see controller/jobs/product/import/csv/domains
			 * @see controller/jobs/product/import/csv/location
			 * @see controller/jobs/product/import/csv/mapping
			 * @see controller/jobs/product/import/csv/max-size
			 * @see controller/jobs/product/import/csv/skip-lines
			 */
			if( $files && $context->config()->get( 'controller/jobs/product/import/csv/cleanup', false ) )
			{
				$count = $this->cleanup( $date );
				$logger->info( sprintf( 'Cleaned %1$s old products', $count ), 'import/csv/product' );
			}

			if( $errors > 0 ) {
				$this->mail( 'Product CSV import', sprintf( 'Invalid product lines during import: %1$d', $errors ) );
			}

			$logger->info( sprintf( 'Finished product import from "%1$s"', $location ), 'import/csv/product' );
		}
		catch( \Exception $e )
		{
			$logger->error( 'Product import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'import/csv/product' );
			$this->mail( 'Product CSV import error', $e->getMessage() . "\n" . $e->getTraceAsString() );
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
		 * **Note:** If no backup name is configured, the file will be removed!
		 *
		 * @param integer Name of the backup file, optionally with date/time placeholders
		 * @since 2018.04
		 * @see controller/jobs/product/import/csv/cleanup
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/mapping
		 * @see controller/jobs/product/import/csv/max-size
		 * @see controller/jobs/product/import/csv/skip-lines
		 */
		$backup = $this->context()->config()->get( 'controller/jobs/product/import/csv/backup' );
		return \Aimeos\Base\Str::strtime( (string) $backup );
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
	 * Cleans up the given list of product items
	 *
	 * @param \Aimeos\Map $products List of product items implementing \Aimeos\MShop\Product\Item\Iface
	 */
	protected function clean( \Aimeos\Map $products )
	{
		$articles = $products->filter( fn( $item ) => $item->getType() === 'select' )
			->getRefItems( 'product', null, 'default' )->flat( 1 );

		$manager = \Aimeos\MShop::create( $this->context(), 'product' );

		$manager->begin();
		$manager->save( $products->merge( $articles )->setStatus( -2 ) );
		$manager->commit();
	}


	/**
	 * Adds conditions to the filter for fetching products that should be removed
	 *
	 * @param \Aimeos\Base\Criteria\Iface $filter Criteria object
	 * @return \Aimeos\Base\Criteria\Iface Modified criteria object
	 */
	protected function cleaner( \Aimeos\Base\Criteria\Iface $filter ) : \Aimeos\Base\Criteria\Iface
	{
		return $filter->add( $filter->make( 'product:has', ['catalog'] ), '!=', null );
	}


	/**
	 * Removes all products which have been updated before the given date/time
	 *
	 * @param string $datetime Date and time in ISO format
	 * @return int Number of removed products
	 */
	protected function cleanup( string $datetime ) : int
	{
		$count = 0;
		$manager = \Aimeos\MShop::create( $this->context(), 'product' );

		$filter = $manager->filter();
		$filter->add( 'product.mtime', '<', $datetime );
		$cursor = $manager->cursor( $this->call( 'cleaner', $filter ) );

		while( $items = $manager->iterate( $cursor, ['product' => ['default']] ) )
		{
			$this->call( 'clean', $items );
			$count += count( $items );
		}

		return $count;
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
		 * @see controller/jobs/product/import/csv/cleanup
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/mapping
		 * @see controller/jobs/product/import/csv/max-size
		 * @see controller/jobs/product/import/csv/skip-lines
		 */
		return $this->context()->config()->get( 'controller/jobs/product/import/csv/domains', [] );
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
	 * Returns the product items for the given codes
	 *
	 * @param array $codes List of product codes
	 * @param array $domains List of domains whose items should be fetched too
	 * @return \Aimeos\Map Associative list of product codes as key and product items as value
	 */
	protected function getProducts( array $codes, array $domains ) : \Aimeos\Map
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'product' );
		$search = $manager->filter()->add( ['product.code' => $codes] )->slice( 0, count( $codes ) );

		return $manager->search( $search, $domains )->col( null, 'product.code' );
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

		$logger->info( sprintf( 'Started product import from "%1$s"', $path ), 'import/csv/product' );

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
			$products = $this->getProducts( array_keys( $data ), $domains );
			$errors += $this->importProducts( $products, $data, $mappings['item'], [], $processor );

			$total += count( $data );
			unset( $products, $data );
		}

		$processor->finish();
		fclose( $fh );

		if( !empty( $backup = $this->backup() ) ) {
			$fs->move( $path, $backup );
		} else {
			$fs->rm( $path );
		}

		$str = sprintf( 'Finished product import from "%1$s" (%2$d/%3$d)', $path, $errors, $total );
		$logger->info( $str, 'import/csv/product' );

		return $errors;
	}


	/**
	 * Imports the CSV data and creates new products or updates existing ones
	 *
	 * @param \Aimeos\Map $products List of products items implementing \Aimeos\MShop\Product\Item\Iface
	 * @param array $data Associative list of import data as index/value pairs
	 * @param array $mapping Associative list of positions and domain item keys
	 * @param array $types List of allowed product type codes
	 * @param \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface $processor Processor object
	 * @return int Number of products that couldn't be imported
	 * @throws \Aimeos\Controller\Jobs\Exception
	 */
	protected function importProducts( \Aimeos\Map $products, array $data, array $mapping, array $types,
		\Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Iface $processor ) : int
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

					$product = $manager->save( $product->setModified() );
					$items[$product->getId()] = $product;
				}

				$manager->commit();
			}
			catch( \Throwable $t )
			{
				$manager->rollback();

				$msg = sprintf( 'Unable to import product with code "%1$s": %2$s', $code, $t->getMessage() );
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
		 * Directory where the CSV files are stored which should be imported
		 *
		 * It's the relative path inside the "fs-import" virtual file system
		 * configuration. The default location of the "fs-import" file system is:
		 *
		 * * Laravel: ./storage/import/
		 * * TYPO3: /uploads/tx_aimeos/.secure/import/
		 *
		 * @param string Relative path to the CSV files
		 * @since 2015.08
		 * @see controller/jobs/product/import/csv/backup
		 * @see controller/jobs/product/import/csv/cleanup
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
		 * @see controller/jobs/product/import/csv/cleanup
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/max-size
		 * @see controller/jobs/product/import/csv/skip-lines
		 */
		$map = (array) $this->context()->config()->get( 'controller/jobs/product/import/csv/mapping', $this->getDefaultMapping() );

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
		 * @see controller/jobs/product/import/csv/cleanup
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/mapping
		 * @see controller/jobs/product/import/csv/skip-lines
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
		 * @see controller/jobs/product/import/csv/cleanup
		 * @see controller/jobs/product/import/csv/domains
		 * @see controller/jobs/product/import/csv/location
		 * @see controller/jobs/product/import/csv/mapping
		 * @see controller/jobs/product/import/csv/max-size
		 */
		return (int) $this->context()->config()->get( 'controller/jobs/product/import/csv/skip-lines', 0 );
	}
}
