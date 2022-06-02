<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2022
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Catalog\Import\Xml;


/**
 * Job controller for XML catalog imports
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/catalog/import/xml/name
	 * Class name of the used catalog suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Catalog\Import\Xml\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Catalog\Import\Xml\Myxml
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/catalog/import/xml/name = Myxml
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
	 * @category Developer
	 */

	/** controller/jobs/catalog/import/xml/decorators/excludes
	 * Excludes decorators added by the "common" option from the catalog import CSV job controller
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
	 *  controller/jobs/catalog/import/xml/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.04
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/catalog/import/xml/decorators/global
	 * @see controller/jobs/catalog/import/xml/decorators/local
	 */

	/** controller/jobs/catalog/import/xml/decorators/global
	 * Adds a list of globally available decorators only to the catalog import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/catalog/import/xml/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.04
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/catalog/import/xml/decorators/excludes
	 * @see controller/jobs/catalog/import/xml/decorators/local
	 */

	/** controller/jobs/catalog/import/xml/decorators/local
	 * Adds a list of local decorators only to the catalog import CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Catalog\Import\Xml\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/catalog/import/xml/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Catalog\Import\Xml\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.04
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/catalog/import/xml/decorators/excludes
	 * @see controller/jobs/catalog/import/xml/decorators/global
	 */


	use \Aimeos\Controller\Common\Common\Import\Xml\Traits;


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Catalog import XML' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Imports new and updates existing categories from XML files' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->context();
		$config = $context->config();
		$logger = $context->logger();

		/** controller/jobs/catalog/import/xml/location
		 * File or directory where the content is stored which should be imported
		 *
		 * You need to configure the XML file or directory with the XML files that
		 * should be imported. It should be an absolute path to be sure but can be
		 * relative path if you absolutely know from where the job will be executed
		 * from.
		 *
		 * @param string Absolute file or directory path
		 * @since 2019.04
		 * @category Developer
		 * @category User
		 * @see controller/jobs/catalog/import/xml/container/type
		 * @see controller/jobs/catalog/import/xml/container/content
		 * @see controller/jobs/catalog/import/xml/container/options
		 */
		$location = $config->get( 'controller/jobs/catalog/import/xml/location' );

		try
		{
			$logger->info( sprintf( 'Started catalog import from "%1$s"', $location ), 'import/xml/catalog' );

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
					if( strncmp( $entry->getFilename(), 'catalog', 7 ) === 0 && $entry->getExtension() === 'xml' ) {
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
				$context->process()->start( $fcn, [$filepath] );
			}

			$context->process()->wait();

			$logger->info( sprintf( 'Finished catalog import from "%1$s"', $location ), 'import/xml/catalog' );
		}
		catch( \Exception $e )
		{
			$logger->error( 'Catalog import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'import/xml/catalog' );
			$this->mail( 'Catalog XML import error', $e->getMessage() . "\n" . $e->getTraceAsString() );
			throw $e;
		}
	}


	/**
	 * Imports the XML file given by its path
	 *
	 * @param string $filename Absolute or relative path to the XML file
	 */
	protected function import( string $filename )
	{
		$context = $this->context();
		$config = $context->config();
		$logger = $context->logger();


		/** controller/jobs/catalog/import/xml/domains
		 * List of item domain names that should be retrieved along with the catalog items
		 *
		 * This configuration setting overwrites the shared option
		 * "controller/common/catalog/import/xml/domains" if you need a
		 * specific setting for the job controller. Otherwise, you should
		 * use the shared option for consistency.
		 *
		 * @param array Associative list of MShop item domain names
		 * @since 2019.04
		 * @category Developer
		 * @see controller/jobs/catalog/import/xml/backup
		 * @see controller/jobs/catalog/import/xml/max-query
		 */
		$domains = $config->get( 'controller/jobs/catalog/import/xml/domains', [] );

		/** controller/jobs/catalog/import/xml/backup
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
		 * **Note:** If no backup name is configured, the file or directory
		 * won't be moved away. Please make also sure that the parent directory
		 * and the new directory are writable so the file or directory could be
		 * moved.
		 *
		 * @param integer Name of the backup file, optionally with date/time placeholders
		 * @since 2019.04
		 * @category Developer
		 * @see controller/jobs/catalog/import/xml/domains
		 * @see controller/jobs/catalog/import/xml/max-query
		 */
		$backup = $config->get( 'controller/jobs/catalog/import/xml/backup' );


		$xml = new \XMLReader();

		if( $xml->open( $filename, LIBXML_COMPACT | LIBXML_PARSEHUGE ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'No XML file "%1$s" found', $filename ) );
		}

		$logger->info( sprintf( 'Started catalog import from file "%1$s"', $filename ), 'import/xml/catalog' );

		$this->importTree( $xml, $domains );

		foreach( $this->getProcessors() as $proc ) {
			$proc->finish();
		}

		$logger->info( sprintf( 'Finished catalog import from file "%1$s"', $filename ), 'import/xml/catalog' );

		if( !empty( $backup ) && @rename( $filename, $backup = \Aimeos\Base\Str::strtime( $backup ) ) === false )
		{
			$msg = sprintf( 'Unable to move imported file "%1$s" to "%2$s"', $filename, $backup );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}
	}


	/**
	 * Imports a single category node
	 *
	 * @param \DomElement $node DOM node of "catalogitem" element
	 * @param string[] $domains List of domain names whose referenced items will be updated in the catalog items
	 * @param string|null $parentid ID of the parent catalog node
	 * @param array &$map Will contain the associative list of code/ID pairs of the child categories
	 * @return string Catalog ID of the imported category
	 */
	protected function importNode( \DomElement $node, array $domains, string $parentid = null, array &$map ) : string
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'catalog' );

		if( ( $attr = $node->attributes->getNamedItem( 'ref' ) ) !== null )
		{
			try
			{
				$item = $manager->find( $attr->nodeValue, $domains );
				$manager->move( $item->getId(), $item->getParentId(), $parentid );

				$item = $this->process( $item, $node );
				$currentid = $manager->save( $item )->getId();
				unset( $item );

				$tree = $manager->getTree( $currentid, [], \Aimeos\MW\Tree\Manager\Base::LEVEL_LIST );

				foreach( $tree->getChildren() as $child ) {
					$map[$child->getCode()] = $child->getId();
				}

				return $currentid;
			}
			catch( \Aimeos\MShop\Exception $e ) {} // not found, create new
		}

		$item = $this->process( $manager->create(), $node );
		return $manager->insert( $item, $parentid )->getId();
	}


	/**
	 * Imports the catalog document
	 *
	 * @param \XMLReader $xml Catalog document to import
	 * @param string[] $domains List of domain names whose referenced items will be updated in the catalog items
	 * @param string|null $parentid ID of the parent catalog node
	 * @param array $map Associative list of catalog code as keys and category ID as values
	 */
	protected function importTree( \XMLReader $xml, array $domains, string $parentid = null, array $map = [] )
	{
		$total = 0;
		$childMap = [];
		$currentid = $parentid;

		while( $xml->read() === true )
		{
			if( $xml->nodeType === \XMLReader::ELEMENT && $xml->name === 'catalogitem' )
			{
				if( ( $node = $xml->expand() ) === false )
				{
					$msg = sprintf( 'Expanding "%1$s" node failed', 'catalogitem' );
					throw new \Aimeos\Controller\Jobs\Exception( $msg );
				}

				if( ( $attr = $node->attributes->getNamedItem( 'ref' ) ) !== null ) {
					unset( $map[$attr->nodeValue] );
				}

				$currentid = $this->importNode( $node, $domains, $parentid, $childMap );
				$total++;
			}
			elseif( $xml->nodeType === \XMLReader::ELEMENT && $xml->name === 'catalog' )
			{
				$this->importTree( $xml, $domains, $currentid, $childMap );
				$childMap = [];
			}
			elseif( $xml->nodeType === \XMLReader::END_ELEMENT && $xml->name === 'catalog' )
			{
				\Aimeos\MShop::create( $this->context(), 'catalog' )->delete( $map );
				break;
			}
		}
	}


	/**
	 * Updates the catalog item and its referenced items using the given DOM node
	 *
	 * @param \Aimeos\MShop\Catalog\Item\Iface $item Catalog item object to update
	 * @param \DomElement $node DOM node used for updateding the catalog item
	 * @return \Aimeos\MShop\Catalog\Item\Iface $item Updated catalog item object
	 */
	protected function process( \Aimeos\MShop\Catalog\Item\Iface $item, \DomElement $node ) : \Aimeos\MShop\Catalog\Item\Iface
	{
		try
		{
			$list = [];

			foreach( $node->attributes as $attr ) {
				$list[$attr->nodeName] = $attr->nodeValue;
			}

			foreach( $node->childNodes as $tag )
			{
				if( $tag->nodeName === 'lists' ) {
					$item = $this->getProcessor( $tag->nodeName )->process( $item, $tag );
				} elseif( $tag->nodeName[0] !== '#' ) {
					$list[$tag->nodeName] = $tag->nodeValue;
				}
			}

			$list['catalog.config'] = isset( $list['catalog.config'] ) ? json_decode( $list['catalog.config'], true ) : [];
			$item->fromArray( $list, true );
		}
		catch( \Exception $e )
		{
			$msg = 'Catalog import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
			$this->context()->logger()->error( $msg, 'import/xml/catalog' );
		}

		return $item;
	}
}
