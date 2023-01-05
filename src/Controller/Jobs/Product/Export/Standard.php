<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2022
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
	/** controller/jobs/product/export/name
	 * Class name of the used product suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Product\Export\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Product\Export\Myalgorithm
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/product/export/name = Myalgorithm
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyOptimizer"!
	 *
	 * @param string Last part of the class name
	 * @since 2015.01
	 */

	/** controller/jobs/product/export/decorators/excludes
	 * Excludes decorators added by the "common" option from the product export job controller
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
	 *  controller/jobs/product/export/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.01
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/product/export/decorators/global
	 * @see controller/jobs/product/export/decorators/local
	 */

	/** controller/jobs/product/export/decorators/global
	 * Adds a list of globally available decorators only to the product export job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/product/export/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.01
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/product/export/decorators/excludes
	 * @see controller/jobs/product/export/decorators/local
	 */

	/** controller/jobs/product/export/decorators/local
	 * Adds a list of local decorators only to the product export job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Product\Export\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/product/export/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Product\Export\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.01
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/product/export/decorators/excludes
	 * @see controller/jobs/product/export/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Product export' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Exports all available products' );
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
		 * templates/controller/jobs).
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
		 * @see controller/jobs/product/export/template-footer
		 * @see controller/jobs/product/export/template-index
		 */
		$tplconf = 'controller/jobs/product/export/template-items';
		$default = 'product/export/items-body-standard';

		$context = $this->context();
		$view = $context->view();

		$view->exportItems = $items;

		$content->add( $view->render( $context->config()->get( $tplconf, $default ) ) );
	}


	/**
	 * Creates a new container for the site map file
	 *
	 * @return \Aimeos\MW\Container\Iface Container object
	 */
	protected function createContainer() : \Aimeos\MW\Container\Iface
	{
		$config = $this->context()->config();

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
		 * templates/controller/jobs).
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
		 * @see controller/jobs/product/export/template-items
		 * @see controller/jobs/product/export/template-footer
		 * @see controller/jobs/product/export/template-index
		 */
		$tplconf = 'controller/jobs/product/export/template-header';
		$default = 'product/export/items-header-standard';

		$context = $this->context();
		$view = $context->view();

		$content = $container->create( $this->getFilename( $filenum ) );
		$content->add( $view->render( $context->config()->get( $tplconf, $default ) ) );
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
		 * templates/controller/jobs).
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
		 * @see controller/jobs/product/export/template-header
		 * @see controller/jobs/product/export/template-items
		 * @see controller/jobs/product/export/template-index
		 */
		$tplconf = 'controller/jobs/product/export/template-footer';
		$default = 'product/export/items-footer-standard';

		$context = $this->context();
		$view = $context->view();

		$content->add( $view->render( $context->config()->get( $tplconf, $default ) ) );
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

		$manager = \Aimeos\MShop::create( $this->context(), 'product' );

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
		$config = $this->context()->config();

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
