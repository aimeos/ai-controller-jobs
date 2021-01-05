<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Product\Export;


/**
 * Job controller for product exports.
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
	public function getName() : string
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Product export' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->getI18n()->dt( 'controller/jobs', 'Exports all available products' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$container = $this->createContainer();
		$this->export( $container, false );
		$container->close();
	}


	/**
	 * Adds the given products to the content object for the site map file
	 *
	 * @param \Aimeos\MW\Container\Content\Iface $content File content object
	 * @param \Aimeos\Map $items List of product items implementing \Aimeos\MShop\Product\Item\Iface
	 */
	protected function addItems( \Aimeos\MW\Container\Content\Iface $content, \Aimeos\Map $items )
	{
		/** controller/jobs/product/export/template-items
		 * Relative path to the XML items template of the product site map job controller.
		 *
		 * The template file contains the XML code and processing instructions
		 * to generate the site map files. The configuration string is the path
		 * to the template file relative to the templates directory (usually in
		 * controller/jobs/templates).
		 *
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates. These alternative templates should be
		 * named like the default one but with the string "standard" replaced by
		 * an unique name. You may use the name of your project for this. If
		 * you've implemented an alternative client class as well, "standard"
		 * should be replaced by the name of the new class.
		 *
		 * @param string Relative path to the template creating XML code for the site map items
		 * @since 2015.01
		 * @category Developer
		 * @see client/html/account/favorite/template-header
		 * @see controller/jobs/product/export/template-footer
		 * @see controller/jobs/product/export/template-index
		 */
		$tplconf = 'controller/jobs/product/export/template-items';
		$default = 'product/export/items-body-standard';

		$context = $this->getContext();
		$view = $context->getView();

		$view->exportItems = $items;

		$content->add( $view->render( $context->getConfig()->get( $tplconf, $default ) ) );
	}


	/**
	 * Creates a new container for the site map file
	 *
	 * @return \Aimeos\MW\Container\Iface Container object
	 */
	protected function createContainer() : \Aimeos\MW\Container\Iface
	{
		$config = $this->getContext()->getConfig();

		/** controller/jobs/product/export/location
		 * Directory where the generated site maps should be placed into
		 *
		 * You have to configure a directory for the generated files on your
		 * server that is writeable by the process generating the files, e.g.
		 *
		 * /var/www/your/export/path
		 *
		 * @param string Absolute directory to store the exported files into
		 * @since 2015.01
		 * @category Developer
		 * @category User
		 * @see controller/jobs/product/export/container/options
		 * @see controller/jobs/product/export/max-items
		 * @see controller/jobs/product/export/max-query
		 */
		$location = $config->get( 'controller/jobs/product/export/location' );

		/** controller/jobs/product/export/container/type
		 * List of file container options for the export files
		 *
		 * The generated files are stored using container/content objects from
		 * the core.
		 *
		 * @param string Container name
		 * @since 2015.01
		 * @category Developer
		 * @see controller/jobs/product/export/container/content
		 * @see controller/jobs/product/export/container/options
		 * @see controller/jobs/product/export/location
		 * @see controller/jobs/product/export/max-items
		 * @see controller/jobs/product/export/max-query
		 */
		$container = $config->get( 'controller/jobs/product/export/container/type', 'Directory' );

		/** controller/jobs/product/export/container/content
		 * List of file container options for the export files
		 *
		 * The generated files are stored using container/content objects from
		 * the core.
		 *
		 * @param array Associative list of option name/value pairs
		 * @since 2015.01
		 * @category Developer
		 * @see controller/jobs/product/export/container/type
		 * @see controller/jobs/product/export/container/options
		 * @see controller/jobs/product/export/location
		 * @see controller/jobs/product/export/max-items
		 * @see controller/jobs/product/export/max-query
		 */
		$content = $config->get( 'controller/jobs/product/export/container/content', 'Binary' );

		/** controller/jobs/product/export/container/options
		 * List of file container options for the export files
		 *
		 * The generated files are stored using container/content objects from
		 * the core.
		 *
		 * @param array Associative list of option name/value pairs
		 * @since 2015.01
		 * @category Developer
		 * @see controller/jobs/product/export/container/type
		 * @see controller/jobs/product/export/container/content
		 * @see controller/jobs/product/export/location
		 * @see controller/jobs/product/export/max-items
		 * @see controller/jobs/product/export/max-query
		 */
		$options = $config->get( 'controller/jobs/product/export/container/options', [] );

		if( $location === null )
		{
			$msg = sprintf( 'Required configuration for "%1$s" is missing', 'controller/jobs/product/export/location' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return \Aimeos\MW\Container\Factory::getContainer( $location, $container, $content, $options );
	}


	/**
	 * Creates a new site map content object
	 *
	 * @param \Aimeos\MW\Container\Iface $container Container object
	 * @param int $filenum New file number
	 * @return \Aimeos\MW\Container\Content\Iface New content object
	 */
	protected function createContent( \Aimeos\MW\Container\Iface $container, int $filenum ) : \Aimeos\MW\Container\Content\Iface
	{
		/** controller/jobs/product/export/template-header
		 * Relative path to the XML site map header template of the product site map job controller.
		 *
		 * The template file contains the XML code and processing instructions
		 * to generate the site map header. The configuration string is the path
		 * to the template file relative to the templates directory (usually in
		 * controller/jobs/templates).
		 *
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates. These alternative templates should be
		 * named like the default one but with the string "standard" replaced by
		 * an unique name. You may use the name of your project for this. If
		 * you've implemented an alternative client class as well, "standard"
		 * should be replaced by the name of the new class.
		 *
		 * @param string Relative path to the template creating XML code for the site map header
		 * @since 2015.01
		 * @category Developer
		 * @see controller/jobs/product/export/template-items
		 * @see controller/jobs/product/export/template-footer
		 * @see controller/jobs/product/export/template-index
		 */
		$tplconf = 'controller/jobs/product/export/template-header';
		$default = 'product/export/items-header-standard';

		$context = $this->getContext();
		$view = $context->getView();

		$content = $container->create( $this->getFilename( $filenum ) );
		$content->add( $view->render( $context->getConfig()->get( $tplconf, $default ) ) );
		$container->add( $content );

		return $content;
	}


	/**
	 * Closes the site map content object
	 *
	 * @param \Aimeos\MW\Container\Content\Iface $content
	 */
	protected function closeContent( \Aimeos\MW\Container\Content\Iface $content )
	{
		/** controller/jobs/product/export/template-footer
		 * Relative path to the XML site map footer template of the product site map job controller.
		 *
		 * The template file contains the XML code and processing instructions
		 * to generate the site map footer. The configuration string is the path
		 * to the template file relative to the templates directory (usually in
		 * controller/jobs/templates).
		 *
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates. These alternative templates should be
		 * named like the default one but with the string "standard" replaced by
		 * an unique name. You may use the name of your project for this. If
		 * you've implemented an alternative client class as well, "standard"
		 * should be replaced by the name of the new class.
		 *
		 * @param string Relative path to the template creating XML code for the site map footer
		 * @since 2015.01
		 * @category Developer
		 * @see controller/jobs/product/export/template-header
		 * @see controller/jobs/product/export/template-items
		 * @see controller/jobs/product/export/template-index
		 */
		$tplconf = 'controller/jobs/product/export/template-footer';
		$default = 'product/export/items-footer-standard';

		$context = $this->getContext();
		$view = $context->getView();

		$content->add( $view->render( $context->getConfig()->get( $tplconf, $default ) ) );
	}


	/**
	 * Exports the products into the given container
	 *
	 * @param \Aimeos\MW\Container\Iface $container Container object
	 * @param bool $default True to filter exported products by default criteria
	 * @return array List of content (file) names
	 */
	protected function export( \Aimeos\MW\Container\Iface $container, bool $default = true ) : array
	{
		$domains = array( 'attribute', 'media', 'price', 'product', 'text' );

		$domains = $this->getConfig( 'domains', $domains );
		$maxItems = $this->getConfig( 'max-items', 10000 );
		$maxQuery = $this->getConfig( 'max-query', 1000 );

		$start = 0; $filenum = 1;
		$names = [];

		$manager = \Aimeos\MShop::create( $this->getContext(), 'product' );

		$search = $manager->filter( $default );
		$search->setSortations( array( $search->sort( '+', 'product.id' ) ) );
		$search->slice( 0, $maxQuery );

		$content = $this->createContent( $container, $filenum );
		$names[] = $content->getResource();

		do
		{
			$items = $manager->search( $search->slice( $start, $maxQuery ), $domains );
			$remaining = $maxItems * $filenum - $start;
			$count = count( $items );

			if( $remaining < $count )
			{
				$this->addItems( $content, $items->slice( 0, $remaining ) );
				$items = $items->slice( $remaining );

				$this->closeContent( $content );
				$content = $this->createContent( $container, ++$filenum );
				$names[] = $content->getResource();
			}

			$this->addItems( $content, $items );
			$start += $count;
		}
		while( $count >= $search->getLimit() );

		$this->closeContent( $content );

		return $names;
	}


	/**
	 * Returns the configuration value for the given name
	 *
	 * @param string $name One of "domain", "max-items" or "max-query"
	 * @param mixed $default Default value if name is unknown
	 * @return mixed Configuration value
	 */
	protected function getConfig( string $name, $default = null )
	{
		$config = $this->getContext()->getConfig();

		switch( $name )
		{
			case 'domain':
				/** controller/jobs/product/export/domains
				 * List of associated items from other domains that should be exported too
				 *
				 * Products consist not only of the base data but also of texts, media
				 * files, prices, attrbutes and other details. Those information is
				 * associated to the products via their lists. Using the "domains" option
				 * you can make more or less associated items available in the template.
				 *
				 * @param array List of domain names
				 * @since 2015.01
				 * @category Developer
				 * @category User
				 * @see controller/jobs/product/export/container/type
				 * @see controller/jobs/product/export/container/content
				 * @see controller/jobs/product/export/container/options
				 * @see controller/jobs/product/export/filename
				 * @see controller/jobs/product/export/location
				 * @see controller/jobs/product/export/max-items
				 * @see controller/jobs/product/export/max-query
				 */
				return $config->get( 'controller/jobs/product/export/domains', $default );

			case 'max-items':
				/** controller/jobs/product/export/max-items
				 * Maximum number of exported products per file
				 *
				 * Limits the number of exported products per file as the memory
				 * consumption of processing big files is rather high. Splitting
				 * the data into several files that can also be processed in
				 * parallel is able to speed up importing the files again.
				 *
				 * @param integer Number of products entries per file
				 * @since 2015.01
				 * @category Developer
				 * @category User
				 * @see controller/jobs/product/export/container/type
				 * @see controller/jobs/product/export/container/content
				 * @see controller/jobs/product/export/container/options
				 * @see controller/jobs/product/export/filename
				 * @see controller/jobs/product/export/location
				 * @see controller/jobs/product/export/max-query
				 * @see controller/jobs/product/export/domains
				 */
				return $config->get( 'controller/jobs/product/export/max-items', $default );

			case 'max-query':
				/** controller/jobs/product/export/max-query
				 * Maximum number of products per query
				 *
				 * The products are fetched from the database in bunches for efficient
				 * retrieval. The higher the value, the lower the total time the database
				 * is busy finding the records. Higher values also means that record
				 * updates in the tables need to wait longer and the memory consumption
				 * of the PHP process is higher.
				 *
				 * @param integer Number of products per query
				 * @since 2015.01
				 * @category Developer
				 * @see controller/jobs/product/export/container/type
				 * @see controller/jobs/product/export/container/content
				 * @see controller/jobs/product/export/container/options
				 * @see controller/jobs/product/export/filename
				 * @see controller/jobs/product/export/location
				 * @see controller/jobs/product/export/max-items
				 * @see controller/jobs/product/export/domains
				 */
				return $config->get( 'controller/jobs/product/export/max-query', $default );

			case 'filename':
				/** controller/jobs/product/export/filename
				 * Template for the generated file names
				 *
				 * The generated export files will be named according to the given
				 * string which can contain two place holders: The number of the
				 * exported product and the ISO date/time when the file was created.
				 *
				 * @param string File name template
				 * @since 2018.04
				 * @category Developer
				 * @see controller/jobs/product/export/container/type
				 * @see controller/jobs/product/export/container/content
				 * @see controller/jobs/product/export/container/options
				 * @see controller/jobs/product/export/location
				 * @see controller/jobs/product/export/max-items
				 * @see controller/jobs/product/export/max-query
				 * @see controller/jobs/product/export/domains
				 */
				return $config->get( 'controller/jobs/product/export/filename', $default );
		}

		return $default;
	}


	/**
	 * Returns the file name for the new content file
	 *
	 * @param int $number Current file number
	 * @return string New file name
	 */
	protected function getFilename( int $number ) : string
	{
		return sprintf( $this->getConfig( 'filename', 'aimeos-products-%1$d_%2$s.xml' ), $number, date( 'Y-m-d_H:i:s' ) );
	}
}
