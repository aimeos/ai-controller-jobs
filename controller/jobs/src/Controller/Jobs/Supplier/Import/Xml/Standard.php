<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Supplier\Import\Xml;

use \Aimeos\MW\Logger\Base as Log;


/**
 * Job controller for XML supplier imports
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
	public function getName() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Supplier import XML' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Imports new and updates existing suppliers from XML files' );
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

		/** controller/jobs/supplier/import/xml/location
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
		 * @see controller/jobs/supplier/import/xml/container/type
		 * @see controller/jobs/supplier/import/xml/container/content
		 * @see controller/jobs/supplier/import/xml/container/options
		 */
		$location = $config->get( 'controller/jobs/supplier/import/xml/location' );

		try
		{
			$msg = sprintf( 'Started supplier import from "%1$s" (%2$s)', $location, __CLASS__ );
			$logger->log( $msg, Log::INFO, 'import/xml/supplier' );

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
					if( strncmp( $entry->getFilename(), 'supplier', 8 ) === 0 && $entry->getExtension() === 'xml' ) {
						$files[] = $entry->getPathname();
					}
				}
			}
			else
			{
				$files[] = $location;
			}

			sort( $files );
			$total = 0;

			foreach( $files as $filepath ) {
				$total += $this->import( $filepath );
			}

			$msg = 'Finished supplier import from "%1$s": %2$s total (%3$s MB)';
			$mem = number_format( memory_get_peak_usage() / 1024 / 1024, 2 );

			$logger->log( sprintf( $msg, $location, $total, $mem ), Log::INFO, 'import/xml/supplier' );
		}
		catch( \Exception $e )
		{
			$logger->log( 'Supplier import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), Log::ERR, 'import/xml/supplier' );
			$this->mail( 'Supplier XML import error', $e->getMessage() . "\n" . $e->getTraceAsString() );
			throw $e;
		}
	}


	/**
	 * Imports the XML file given by its path
	 *
	 * @param string $filename Absolute or relative path to the XML file
	 * @return int Total number of imported suppliers
	 */
	protected function import( string $filename ) : int
	{
		$context = $this->getContext();
		$config = $context->getConfig();

		/** controller/jobs/supplier/import/xml/domains
		 * List of item domain names that should be retrieved along with the supplier items
		 *
		 * This configuration setting overwrites the shared option
		 * "controller/common/supplier/import/xml/domains" if you need a
		 * specific setting for the job controller. Otherwise, you should
		 * use the shared option for consistency.
		 *
		 * @param array Associative list of MShop item domain names
		 * @since 2019.04
		 * @category Developer
		 * @see controller/jobs/supplier/import/xml/backup
		 * @see controller/jobs/supplier/import/xml/max-query
		 */
		$domains = $config->get( 'controller/jobs/supplier/import/xml/domains', [] );

		/** controller/jobs/supplier/import/xml/backup
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
		 * **Note:** If no backup name is configured, the file or directory
		 * won't be moved away. Please make also sure that the parent directory
		 * and the new directory are writable so the file or directory could be
		 * moved.
		 *
		 * @param integer Name of the backup file, optionally with date/time placeholders
		 * @since 2019.04
		 * @category Developer
		 * @see controller/jobs/supplier/import/xml/domains
		 * @see controller/jobs/supplier/import/xml/max-query
		 */
		$backup = $config->get( 'controller/jobs/supplier/import/xml/backup' );

		/** controller/jobs/supplier/import/xml/max-query
		 * Maximum number of XML nodes processed at once
		 *
		 * Processing and fetching several supplier items at once speeds up importing
		 * the XML files. The more items can be processed at once, the faster the
		 * import. More items also increases the memory usage of the importer and
		 * thus, this parameter should be low enough to avoid reaching the memory
		 * limit of the PHP process.
		 *
		 * @param integer Number of XML nodes
		 * @since 2019.04
		 * @category Developer
		 * @category User
		 * @see controller/jobs/supplier/import/xml/domains
		 * @see controller/jobs/supplier/import/xml/backup
		 */
		$maxquery = $config->get( 'controller/jobs/supplier/import/xml/max-query', 1000 );

		$nodes = [];
		$total = $slice = 0;
		$xml = new \XMLReader();

		if( $xml->open( $filename, LIBXML_COMPACT | LIBXML_PARSEHUGE ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'No XML file "%1$s" found', $filename ) );
		}

		while( $xml->read() === true )
		{
			if( $xml->depth === 1 && $xml->nodeType === \XMLReader::ELEMENT && $xml->name === 'supplieritem' )
			{
				if( ( $dom = $xml->expand() ) === false )
				{
					$msg = sprintf( 'Expanding "%1$s" node failed', 'supplieritem' );
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

				$total++;
			}
		}

		$this->importNodes( $nodes, $domains );
		unset( $nodes );

		foreach( $this->getProcessors() as $proc ) {
			$proc->finish();
		}

		if( !empty( $backup ) && @rename( $filename, strftime( $backup ) ) === false )
		{
			$msg = sprintf( 'Unable to move imported file "%1$s" to "%2$s"', $filename, strftime( $backup ) );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return $total;
	}


	/**
	 * Imports the given DOM nodes
	 *
	 * @param \DomElement[] $nodes List of nodes to import
	 * @param string[] $ref List of domain names whose referenced items will be updated in the supplier items
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

		$manager = \Aimeos\MShop::create( $this->getContext(), 'supplier' );
		$search = $manager->filter()->slice( 0, count( $codes ) );
		$search->setConditions( $search->compare( '==', 'supplier.code', array_keys( $codes ) ) );

		foreach( $manager->search( $search, $ref ) as $item ) {
			$map[$item->getCode()] = $item;
		}

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
	 * Updates the supplier item and its referenced items using the given DOM node
	 *
	 * @param \Aimeos\MShop\Supplier\Item\Iface $item Supplier item object to update
	 * @param \DomElement $node DOM node used for updateding the supplier item
	 * @return \Aimeos\MShop\Supplier\Item\Iface $item Updated supplier item object
	 */
	protected function process( \Aimeos\MShop\Supplier\Item\Iface $item, \DomElement $node ) : \Aimeos\MShop\Supplier\Item\Iface
	{
		try
		{
			$list = [];

			foreach( $node->attributes as $attr ) {
				$list[$attr->nodeName] = $attr->nodeValue;
			}

			foreach( $node->childNodes as $tag )
			{
				if( in_array( $tag->nodeName, ['address', 'lists', 'property'] ) ) {
					$item = $this->getProcessor( $tag->nodeName )->process( $item, $tag );
				} else {
					$list[$tag->nodeName] = $tag->nodeValue;
				}
			}

			$item->fromArray( $list, true );
		}
		catch( \Exception $e )
		{
			$msg = 'Supplier import error: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
			$this->getContext()->getLogger()->log( $msg, Log::ERR, 'import/xml/supplier' );
		}

		return $item;
	}
}
