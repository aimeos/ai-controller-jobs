<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2025
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Customer\Import\Xml;


/**
 * Job controller for XML customer imports
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/customer/import/xml/name
	 * Class name of the used customer suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Customer\Import\Xml\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Customer\Import\Xml\Myxml
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/customer/import/xml/name = Myxml
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyXml"!
	 *
	 * @param string Last part of the class name
	 * @since 2019.04
	 */

	/** controller/jobs/customer/import/xml/decorators/excludes
	 * Excludes decorators added by the "common" option from the customer import CSV job controller
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
	 *  controller/jobs/customer/import/xml/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/customer/import/xml/decorators/global
	 * @see controller/jobs/customer/import/xml/decorators/local
	 */

	/** controller/jobs/customer/import/xml/decorators/global
	 * Adds a list of globally available decorators only to the customer import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/customer/import/xml/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/customer/import/xml/decorators/excludes
	 * @see controller/jobs/customer/import/xml/decorators/local
	 */

	/** controller/jobs/customer/import/xml/decorators/local
	 * Adds a list of local decorators only to the customer import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Customer\Import\Xml\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/customer/import/xml/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Customer\Import\Xml\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/customer/import/xml/decorators/excludes
	 * @see controller/jobs/customer/import/xml/decorators/global
	 */


	use \Aimeos\Controller\Jobs\Common\Types;
	use \Aimeos\Controller\Jobs\Common\Import\Xml\Traits;


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Customer import XML' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Imports new and updates existing customers from XML files' );
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
		$process = $context->process();

		try
		{
			$fs = $context->fs( 'fs-import' );
			$site = $context->locale()->getSiteItem()->getCode();
			$location = $this->location() . '/' . $site;

			if( $fs->isDir( $location ) === false ) {
				return;
			}

			$logger->info( sprintf( 'Started customer import from "%1$s"', $location ), 'import/xml/customer' );

			$fcn = function( \Aimeos\MShop\ContextIface $context, string $path ) {
				$this->import( $context, $path );
			};

			foreach( map( $fs->scan( $location ) )->sort() as $filename )
			{
				$path = $location . '/' . $filename;

				if( $filename[0] === '.' || $fs instanceof \Aimeos\Base\Filesystem\DirIface && $fs->isDir( $path ) ) {
					continue;
				}

				$process->start( $fcn, [$context, $path] );
			}

			$process->wait();

			$logger->info( sprintf( 'Finished customer import from "%1$s"', $location ), 'import/xml/customer' );
		}
		catch( \Exception $e )
		{
			$logger->error( 'Customer import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'import/xml/customer' );
			$this->mail( 'Customer XML import error', $e->getMessage() . "\n" . $e->getTraceAsString() );
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
		/** controller/jobs/customer/import/xml/backup
		 * Name of the backup for sucessfully imported files
		 *
		 * After a XML file was imported successfully, you can move it to another
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
		 * @see controller/jobs/customer/import/xml/domains
		 * @see controller/jobs/customer/import/xml/location
		 * @see controller/jobs/customer/import/xml/max-query
		 */
		$backup = $this->context()->config()->get( 'controller/jobs/customer/import/xml/backup' );
		return \Aimeos\Base\Str::strtime( (string) $backup );
	}


	/**
	 * Returns the list of domain names that should be retrieved along with the attribute items
	 *
	 * @return array List of domain names
	 */
	protected function domains() : array
	{
		/** controller/jobs/customer/import/xml/domains
		 * List of item domain names that should be retrieved along with the attribute items
		 *
		 * For efficient processing, the items associated to the customers can be
		 * fetched to, minimizing the number of database queries required. To be
		 * most effective, the list of item domain names should be used in the
		 * mapping configuration too, so the retrieved items will be used during
		 * the import.
		 *
		 * @param array Associative list of MShop item domain names
		 * @since 2019.04
		 * @see controller/jobs/customer/import/xml/backup
		 * @see controller/jobs/customer/import/xml/location
		 * @see controller/jobs/customer/import/xml/max-query
		 */
		$domains = ['customer/address', 'group', 'customer/property', 'media', 'product', 'text'];
		return $this->context()->config()->get( 'controller/jobs/customer/import/xml/domains', $domains );
	}


	/**
	 * Imports the XML file given by its path
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param string $path Relative path to the XML file in the file system
	 */
	protected function import( \Aimeos\MShop\ContextIface $context, string $path )
	{
		$slice = 0;
		$nodes = [];

		$xml = new \XMLReader();
		$maxquery = $this->max();

		$logger = $context->logger();
		$fs = $context->fs( 'fs-import' );
		$tmpfile = $fs->readf( $path );

		if( $xml->open( $tmpfile, null, LIBXML_COMPACT | LIBXML_PARSEHUGE ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'No XML file "%1$s" found', $tmpfile ) );
		}

		$logger->info( sprintf( 'Started customer import from file "%1$s"', $path ), 'import/xml/customer' );

		while( $xml->read() === true )
		{
			if( $xml->depth === 1 && $xml->nodeType === \XMLReader::ELEMENT && $xml->name === 'customeritem' )
			{
				if( ( $dom = $xml->expand() ) === false )
				{
					$msg = sprintf( 'Expanding "%1$s" node failed', 'customeritem' );
					throw new \Aimeos\Controller\Jobs\Exception( $msg );
				}

				$nodes[] = $dom;

				if( $slice++ >= $maxquery )
				{
					$this->importNodes( $nodes );
					unset( $nodes );
					$nodes = [];
					$slice = 0;
				}
			}
		}

		$this->importNodes( $nodes );
		unset( $nodes );

		$this->saveTypes();

		foreach( $this->getProcessors() as $proc ) {
			$proc->finish();
		}

		unlink( $tmpfile );

		if( !empty( $backup = $this->backup() ) ) {
			$fs->move( $path, $backup );
		} else {
			$fs->rm( $path );
		}

		$logger->info( sprintf( 'Finished customer import from file "%1$s"', $path ), 'import/xml/customer' );
	}


	/**
	 * Imports the given DOM nodes
	 *
	 * @param string[] $ref List of domain names whose referenced items will be updated in the customer items
	 */
	protected function importNodes( array $nodes )
	{
		$codes = [];

		foreach( $nodes as $node )
		{
			if( ( $attr = $node->attributes->getNamedItem( 'ref' ) ) !== null ) {
				$codes[$attr->nodeValue] = null;
			}
		}

		$manager = \Aimeos\MShop::create( $this->context(), 'customer' );
		$search = $manager->filter()->slice( 0, count( $codes ) )->add( ['customer.code'=> array_keys( $codes )] );
		$map = $manager->search( $search, $this->domains() )->col( null, 'customer.code' );

		foreach( $nodes as $node )
		{
			if( ( $attr = $node->attributes->getNamedItem( 'ref' ) ) !== null && isset( $map[$attr->nodeValue] ) ) {
				$item = $this->process( $map[$attr->nodeValue], $node );
			} else {
				$item = $this->process( $manager->create(), $node );
			}

			$manager->save( $item );
		}
	}


	/**
	 * Returns the path to the directory with the XML file
	 *
	 * @return string Path to the directory with the XML file
	 */
	protected function location() : string
	{
		/** controller/jobs/customer/import/xml/location
		 * Directory where the CSV files are stored which should be imported
		 *
		 * It's the relative path inside the "fs-import" virtual file system
		 * configuration. The default location of the "fs-import" file system is:
		 *
		 * * Laravel: ./storage/import/
		 * * TYPO3: /uploads/tx_aimeos/.secure/import/
		 *
		 * @param string Relative path to the XML files
		 * @since 2019.04
		 * @see controller/jobs/customer/import/xml/backup
		 * @see controller/jobs/customer/import/xml/domains
		 * @see controller/jobs/customer/import/xml/max-query
		 */
		return (string) $this->context()->config()->get( 'controller/jobs/customer/import/xml/location', 'customer' );
	}


	/**
	 * Returns the maximum number of XML nodes processed at once
	 *
	 * @return int Maximum number of XML nodes
	 */
	protected function max() : int
	{
		/** controller/jobs/customer/import/xml/max-query
		 * Maximum number of XML nodes processed at once
		 *
		 * Processing and fetching several attribute items at once speeds up importing
		 * the XML files. The more items can be processed at once, the faster the
		 * import. More items also increases the memory usage of the importer and
		 * thus, this parameter should be low enough to avoid reaching the memory
		 * limit of the PHP process.
		 *
		 * @param integer Number of XML nodes
		 * @since 2019.04
		 * @see controller/jobs/customer/import/xml/domains
		 * @see controller/jobs/customer/import/xml/location
		 * @see controller/jobs/customer/import/xml/backup
		 */
		return $this->context()->config()->get( 'controller/jobs/customer/import/xml/max-query', 100 );
	}


	/**
	 * Updates the customer item and its referenced items using the given DOM node
	 *
	 * @param \Aimeos\MShop\Customer\Item\Iface $item Customer item object to update
	 * @param \DomElement $node DOM node used for updateding the customer item
	 * @return \Aimeos\MShop\Customer\Item\Iface $item Updated customer item object
	 */
	protected function process( \Aimeos\MShop\Customer\Item\Iface $item, \DomElement $node ) : \Aimeos\MShop\Customer\Item\Iface
	{
		try
		{
			$list = [];

			foreach( $node->attributes as $attr ) {
				$list[$attr->nodeName] = $attr->nodeValue;
			}

			foreach( $node->childNodes as $tag )
			{
				if( in_array( $tag->nodeName, ['address', 'lists', 'property', 'group'] ) ) {
					$item = $this->getProcessor( $tag->nodeName )->process( $item, $tag );
				} elseif( $tag->nodeName[0] !== '#' ) {
					$list[$tag->nodeName] = $tag->nodeValue;
				}
			}

			$item->fromArray( $list, true );
		}
		catch( \Exception $e )
		{
			$msg = 'Customer import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
			$this->context()->logger()->error( $msg, 'import/xml/customer' );
		}

		return $item;
	}
}
