<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Product\Import\Xml;


/**
 * Job controller for XML product imports
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	use \Aimeos\Controller\Common\Common\Import\Xml\Traits;


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Product import XML' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription()
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Imports new and updates existing products from XML files' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->getContext();
		$config = $context->getConfig();
		$logger = $context->getLogger();


		/** controller/jobs/product/import/xml/location
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
		 * @see controller/jobs/product/import/xml/container/type
		 * @see controller/jobs/product/import/xml/container/content
		 * @see controller/jobs/product/import/xml/container/options
		 */
		$location = $config->get( 'controller/jobs/product/import/xml/location' );

		try
		{
			$logger->log( sprintf( 'Started product import from "%1$s"', $location ), \Aimeos\MW\Logger\Base::INFO );

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
					if( strncmp( $entry->getFilename(), 'product', 7 ) === 0 && $entry->getExtension() === 'xml' ) {
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

			$logger->log( sprintf( 'Finished product import from "%1$s"', $location ), \Aimeos\MW\Logger\Base::INFO );
		}
		catch( \Exception $e )
		{
			$logger->log( 'Product import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() );
			throw $e;
		}
	}


	/**
	 * Imports the XML file given by its path
	 *
	 * @param string $filename Absolute or relative path to the XML file
	 */
	protected function import( $filename )
	{
		$context = $this->getContext();
		$config = $context->getConfig();
		$logger = $context->getLogger();


		$domains = ['attribute', 'media', 'price', 'product', 'product/property', 'text'];

		/** controller/jobs/product/import/xml/domains
		 * List of item domain names that should be retrieved along with the product items
		 *
		 * This configuration setting overwrites the shared option
		 * "controller/common/product/import/xml/domains" if you need a
		 * specific setting for the job controller. Otherwise, you should
		 * use the shared option for consistency.
		 *
		 * @param array Associative list of MShop item domain names
		 * @since 2019.04
		 * @category Developer
		 * @see controller/jobs/product/import/xml/backup
		 * @see controller/jobs/product/import/xml/max-query
		 */
		$domains = $config->get( 'controller/jobs/product/import/xml/domains', $domains );

		/** controller/jobs/product/import/xml/backup
		 * Name of the backup for sucessfully imported files
		 *
		 * After a XML file was imported successfully, you can move it to another
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
		 * @see controller/jobs/product/import/xml/domains
		 * @see controller/jobs/product/import/xml/max-query
		 */
		$backup = $config->get( 'controller/jobs/product/import/xml/backup' );

		/** controller/jobs/product/import/xml/max-query
		 * Maximum number of XML nodes processed at once
		 *
		 * Processing and fetching several product items at once speeds up importing
		 * the XML files. The more items can be processed at once, the faster the
		 * import. More items also increases the memory usage of the importer and
		 * thus, this parameter should be low enough to avoid reaching the memory
		 * limit of the PHP process.
		 *
		 * @param integer Number of XML nodes
		 * @since 2019.04
		 * @category Developer
		 * @category User
		 * @see controller/jobs/product/import/xml/domains
		 * @see controller/jobs/product/import/xml/backup
		 */
		$maxquery = $config->get( 'controller/jobs/product/import/xml/max-query', 1000 );


		$slice = 0;
		$nodes = [];
		$xml = new \XMLReader();

		if( $xml->open( $filename, LIBXML_COMPACT | LIBXML_PARSEHUGE ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'No XML file "%1$s" found', $filename ) );
		}

		$logger->log( sprintf( 'Started product import from file "%1$s"', $filename ), \Aimeos\MW\Logger\Base::INFO );

		while( $xml->read() === true )
		{
			if( $xml->depth === 1 && $xml->nodeType === \XMLReader::ELEMENT && $xml->name === 'productitem' )
			{
				if( ( $dom = $xml->expand() ) === false )
				{
					$msg = sprintf( 'Expanding "%1$s" node failed', 'productitem' );
					throw new \Aimeos\Controller\Jobs\Exception( $msg );
				}

				$nodes[] = $dom;

				if( $slice++ >= $maxquery )
				{
					$this->importNodes( $nodes, $domains );
					unset( $nodes );
					$nodes = [];
					$slice = 0;
				}
			}
		}

		$this->importNodes( $nodes, $domains );
		unset( $nodes );

		$logger->log( sprintf( 'Finished product import from file "%1$s"', $filename ), \Aimeos\MW\Logger\Base::INFO );

		if( !empty( $backup ) && @rename( $filename, strftime( $backup ) ) === false )
		{
			$msg = sprintf( 'Unable to move imported file "%1$s" to "%2$s"', $filename, strftime( $backup ) );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}
	}


	/**
	 * Imports the given DOM nodes
	 *
	 * @param \DomElement[] $nodes List of nodes to import
	 * @param string[] $ref List of domain names whose referenced items will be updated in the product items
	 */
	protected function importNodes( array $nodes, array $ref )
	{
		$codes = $map = [];

		foreach( $nodes as $node )
		{
			if( ( $attr = $node->attributes->getNamedItem( 'ref' ) ) !== null ) {
				$codes[$attr->nodeValue] = null;
			}
		}

		$manager = \Aimeos\MShop::create( $this->getContext(), 'product' );
		$search = $manager->createSearch()->setSlice( 0, count( $codes ) );
		$search->setConditions( $search->compare( '==', 'product.code', array_keys( $codes ) ) );

		foreach( $manager->searchItems( $search, $ref ) as $item ) {
			$map[$item->getCode()] = $item;
		}

		foreach( $nodes as $node )
		{
			if( ( $attr = $node->attributes->getNamedItem( 'ref' ) ) !== null && isset( $map[$attr->nodeValue] ) ) {
				$item = $this->process( $map[$attr->nodeValue], $node );
			} else {
				$item = $this->process( $manager->createItem(), $node );
			}
		}
	}


	/**
	 * Updates the product item and its referenced items using the given DOM node
	 *
	 * @param \Aimeos\MShop\Product\Item\Iface $item Product item object to update
	 * @param \DomElement $node DOM node used for updateding the product item
	 * @return \Aimeos\MShop\Product\Item\Iface $item Updated product item object
	 */
	protected function process( \Aimeos\MShop\Product\Item\Iface $item, \DomElement $node )
	{
		$list = $subnodes = [];

		foreach( $node->attributes as $attr ) {
			$list[$attr->nodeName] = $attr->nodeValue;
		}

		foreach( $node->childNodes as $tag )
		{
			if( in_array( $tag->nodeName, ['lists', 'property'] ) ) {
				$item = $this->getProcessor( $tag->nodeName )->process( $item, $tag );
			} elseif( in_array( $tag->nodeName, ['catalog'] ) ) {
				$subnodes[$tag->nodeName] = $tag;
			} else {
				$list[$tag->nodeName] = $tag->nodeValue;
			}
		}

		$item = \Aimeos\MShop::create( $this->getContext(), 'product' )->saveItem( $item->fromArray( $list, true ) );

		foreach( $subnodes as $name => $subnode ) {
			$item = $this->getProcessor( $name )->process( $item, $subnode );
		}

		return $item;
	}
}
