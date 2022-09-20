<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2022
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Catalog\Export\Sitemap;


/**
 * Job controller for catalog sitemap.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/catalog/export/sitemap/name
	 * Class name of the used catalog sitemap export scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Catalog\Export\Sitemap\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Catalog\Export\Sitemap\Mysitemap
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/catalog/export/sitemap/name = Mysitemap
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MySitemap"!
	 *
	 * @param string Last part of the class name
	 * @since 2019.02
	 * @category Developer
	 */

	/** controller/jobs/catalog/export/sitemap/decorators/excludes
	 * Excludes decorators added by the "common" option from the catalog export sitemap job controller
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
	 *  controller/jobs/catalog/export/sitemap/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.02
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/catalog/export/sitemap/decorators/global
	 * @see controller/jobs/catalog/export/sitemap/decorators/local
	 */

	/** controller/jobs/catalog/export/sitemap/decorators/global
	 * Adds a list of globally available decorators only to the catalog export sitemap job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/catalog/export/sitemap/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.02
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/catalog/export/sitemap/decorators/excludes
	 * @see controller/jobs/catalog/export/sitemap/decorators/local
	 */

	/** controller/jobs/catalog/export/sitemap/decorators/local
	 * Adds a list of local decorators only to the catalog export sitemap job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Catalog\Export\Sitemap\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/catalog/export/sitemap/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Catalog\Export\Sitemap\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2019.02
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/catalog/export/sitemap/export/sitemap/decorators/excludes
	 * @see controller/jobs/catalog/export/sitemap/export/sitemap/decorators/global
	 */


	private $locales;


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Catalog site map' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Creates a catalog site map for search engines' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		/** controller/jobs/catalog/export/sitemap/hidden
		 * Export hidden categories in site map
		 *
		 * The catalog site map contains no hidden categories by default. If they
		 * should be part of the export, set this configuration option to TRUE.
		 *
		 * @param bool TRUE to export hidden categories, FALSE if not
		 * @since 2022.01
		 * @see controller/jobs/catalog/export/sitemap/container/options
		 * @see controller/jobs/catalog/export/sitemap/location
		 * @see controller/jobs/catalog/export/sitemap/max-items
		 * @see controller/jobs/catalog/export/sitemap/max-query
		 * @see controller/jobs/catalog/export/sitemap/changefreq
		 */
		$hidden = $this->context()->config()->get( 'controller/jobs/catalog/export/sitemap/hidden', false );

		$container = $this->createContainer();

		$files = $this->export( $container, $hidden ? null : true );
		$this->createSitemapIndex( $container, $files );

		$container->close();
	}


	/**
	 * Adds the given catalogs to the content object for the site map file
	 *
	 * @param \Aimeos\MW\Container\Content\Iface $content File content object
	 * @param \Aimeos\Map $items List of catalog items implementing \Aimeos\MShop\Catalog\Item\Iface
	 */
	protected function addItems( \Aimeos\MW\Container\Content\Iface $content, \Aimeos\Map $items )
	{
		$config = $this->context()->config();

		/** controller/jobs/catalog/export/sitemap/changefreq
		 * Change frequency of the catalog
		 *
		 * Depending on how often the catalog content changes
		 * and the site map files are generated you can give search engines a
		 * hint how often they should reindex your site. The site map schema
		 * allows a few pre-defined strings for the change frequency:
		 *
		 * * always
		 * * hourly
		 * * daily
		 * * weekly
		 * * monthly
		 * * yearly
		 * * never
		 *
		 * More information can be found at
		 * {@link http://www.sitemaps.org/protocol.html#xmlTagDefinitions sitemap.org}
		 *
		 * @param string One of the pre-defined strings (see description)
		 * @since 2019.02
		 * @category User
		 * @category Developer
		 * @see controller/jobs/catalog/export/sitemap/container/options
		 * @see controller/jobs/catalog/export/sitemap/location
		 * @see controller/jobs/catalog/export/sitemap/max-items
		 * @see controller/jobs/catalog/export/sitemap/max-query
		 */
		$changefreq = $config->get( 'controller/jobs/catalog/export/sitemap/changefreq', 'daily' );

		/** controller/jobs/catalog/export/sitemap/template-items
		 * Relative path to the XML items template of the catalog site map job controller.
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
		 * @since 2019.02
		 * @category Developer
		 * @see controller/jobs/catalog/export/sitemap/template-header
		 * @see controller/jobs/catalog/export/sitemap/template-footer
		 * @see controller/jobs/catalog/export/sitemap/template-index
		 */
		$tplconf = 'controller/jobs/catalog/export/sitemap/template-items';
		$default = 'catalog/export/sitemap-items-body-standard';

		$context = $this->context();
		$view = $context->view();

		$view->siteItems = $items;
		$view->siteFreq = $changefreq;
		$view->siteLocales = $this->locales();

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

		/** controller/jobs/catalog/export/sitemap/location
		 * Directory where the generated site maps should be placed into
		 *
		 * The site maps must be publically available for download by the search
		 * engines. Therefore, you have to configure a directory for the site
		 * maps in your web space that is writeable by the process generating
		 * the files, e.g.
		 *
		 * /var/www/yourshop/your/sitemap/path
		 *
		 * The location of the site map index file should then be
		 * added to the robots.txt in the document root of your domain:
		 *
		 * Sitemap: https://www.yourshop.com/your/sitemap/path/aimeos-sitemap-index.xml
		 *
		 * The "sitemapindex-aimeos.xml" file is the site map index file that
		 * references the real site map files which contains the links to the
		 * catalogs. Please make sure that the protocol and domain
		 * (https://www.yourshop.com/) is the same as the ones used in the
		 * catalog links!
		 *
		 * More details about site maps can be found at
		 * {@link http://www.sitemaps.org/protocol.html sitemaps.org}
		 *
		 * @param string Absolute directory to store the site maps into
		 * @since 2019.02
		 * @category Developer
		 * @category User
		 * @see controller/jobs/catalog/export/sitemap/container/options
		 * @see controller/jobs/catalog/export/sitemap/max-items
		 * @see controller/jobs/catalog/export/sitemap/max-query
		 * @see controller/jobs/catalog/export/sitemap/changefreq
		 */
		$location = $config->get( 'resource/fs/basedir' );
		$location = $config->get( 'controller/jobs/catalog/export/sitemap/location', $location );

		/** controller/jobs/catalog/export/sitemap/container/options
		 * List of file container options for the site map files
		 *
		 * The directory and the generated site map files are stored using
		 * container/content objects from the core, namely the "Directory"
		 * container and the "Binary" content classes. Both implementations
		 * support some options:
		 *
		 * * dir-perm (default: 0755): Permissions if the directory must be created
		 * * gzip-level (default: 5): GZip compression level from 0 to 9 (0 = fast, 9 = best)
		 * * gzip-mode (default: "wb"): Overwrite existing files in binary mode
		 *
		 * @param array Associative list of option name/value pairs
		 * @since 2019.02
		 * @category Developer
		 * @see controller/jobs/catalog/export/sitemap/location
		 * @see controller/jobs/catalog/export/sitemap/max-items
		 * @see controller/jobs/catalog/export/sitemap/max-query
		 * @see controller/jobs/catalog/export/sitemap/changefreq
		 */
		$default = array( 'gzip-mode' => 'wb' );
		$options = $config->get( 'controller/jobs/catalog/export/sitemap/container/options', $default );

		if( empty( $location ) )
		{
			$msg = sprintf( 'Required configuration for "%1$s" is missing', 'controller/jobs/catalog/export/sitemap/location' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		return \Aimeos\MW\Container\Factory::getContainer( $location, 'Directory', 'Gzip', $options );
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
		/** controller/jobs/catalog/export/sitemap/template-header
		 * Relative path to the XML site map header template of the catalog site map job controller.
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
		 * @since 2019.02
		 * @category Developer
		 * @see controller/jobs/catalog/export/sitemap/template-items
		 * @see controller/jobs/catalog/export/sitemap/template-footer
		 * @see controller/jobs/catalog/export/sitemap/template-index
		 */
		$tplconf = 'controller/jobs/catalog/export/sitemap/template-header';
		$default = 'catalog/export/sitemap-items-header-standard';

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
		/** controller/jobs/catalog/export/sitemap/template-footer
		 * Relative path to the XML site map footer template of the catalog site map job controller.
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
		 * @since 2019.02
		 * @category Developer
		 * @see controller/jobs/catalog/export/sitemap/template-header
		 * @see controller/jobs/catalog/export/sitemap/template-items
		 * @see controller/jobs/catalog/export/sitemap/template-index
		 */
		$tplconf = 'controller/jobs/catalog/export/sitemap/template-footer';
		$default = 'catalog/export/sitemap-items-footer-standard';

		$context = $this->context();
		$view = $context->view();

		$content->add( $view->render( $context->config()->get( $tplconf, $default ) ) );
	}


	/**
	 * Adds the content for the site map index file
	 *
	 * @param \Aimeos\MW\Container\Iface $container File container object
	 * @param array $files List of generated site map file names
	 */
	protected function createSitemapIndex( \Aimeos\MW\Container\Iface $container, array $files )
	{
		$context = $this->context();
		$config = $context->config();
		$view = $context->view();

		/** controller/jobs/catalog/export/sitemap/template-index
		 * Relative path to the XML site map index template of the catalog site map job controller.
		 *
		 * The template file contains the XML code and processing instructions
		 * to generate the site map index files. The configuration string is the path
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
		 * @param string Relative path to the template creating XML code for the site map index
		 * @since 2019.02
		 * @category Developer
		 * @see controller/jobs/catalog/export/sitemap/template-header
		 * @see controller/jobs/catalog/export/sitemap/template-items
		 * @see controller/jobs/catalog/export/sitemap/template-footer
		 */
		$tplconf = 'controller/jobs/catalog/export/sitemap/template-index';
		$default = 'catalog/export/sitemap-index-standard';

		/** controller/jobs/catalog/export/sitemap/baseurl
		 * URL to the folder where the site maps can be accessed, without the filenames.
		 *
		 * The site maps must be publically available for download by the search
		 * engines. Individual site map files need a fully qualified URL in the index file.
		 *
		 * https://www.yourshop.com/your/sitemap/path/
		 *
		 * The location of the site map index file should then be
		 * added to the robots.txt in the document root of your domain:
		 *
		 * Sitemap: https://www.yourshop.com/your/sitemap/path/aimeos-catalog-sitemap-index.xml
		 *
		 * More details about site maps can be found at
		 * {@link http://www.sitemaps.org/protocol.html sitemaps.org}
		 *
		 * @param string Absolute URL
		 * @since 2019.06
		 * @category Developer
		 * @category User
		 * @see controller/jobs/catalog/export/sitemap/container/options
		 * @see controller/jobs/catalog/export/sitemap/max-items
		 * @see controller/jobs/catalog/export/sitemap/max-query
		 * @see controller/jobs/catalog/export/sitemap/changefreq
		 * @see controller/jobs/catalog/export/sitemap/location
		 */
		$baseUrl = $config->get( 'resource/fs/baseurl' );
		$baseUrl = $config->get( 'controller/jobs/catalog/export/sitemap/baseurl', $baseUrl );

		if( empty( $baseUrl ) )
		{
			$msg = sprintf( 'Required configuration for "%1$s" is missing', 'controller/jobs/catalog/export/sitemap/baseurl' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		$view->baseUrl = rtrim( $baseUrl, '/' ) . '/';
		$view->siteFiles = $files;

		$content = $container->create( 'aimeos-catalog-sitemap-index.xml' );
		$content->add( $view->render( $config->get( $tplconf, $default ) ) );
		$container->add( $content );
	}


	/**
	 * Exports the catalogs into the given container
	 *
	 * @param \Aimeos\MW\Container\Iface $container Container object
	 * @param bool|null $default TRUE to use default criteria, NULL for relaxed criteria
	 * @return array List of content (file) names
	 */
	protected function export( \Aimeos\MW\Container\Iface $container, ?bool $default = true ) : array
	{
		$config = $this->context()->config();
		/** controller/jobs/catalog/export/sitemap/domains
		 * List of associated items from other domains that should be fetched for the sitemap
		 *
		 * Catalogs consist not only of the base data but also of texts, media and
		 * other details. Those information is associated to the catalog via their lists.
		 * Using the "domains" option you can make more or less associated items available
		 * in the template.
		 *
		 * @param array List of domain names
		 * @since 2019.02
		 * @category Developer
		 * @category User
		 * @see controller/jobs/catalog/export/sitemap/container/options
		 * @see controller/jobs/catalog/export/sitemap/location
		 * @see controller/jobs/catalog/export/sitemap/max-items
		 * @see controller/jobs/catalog/export/sitemap/max-query
		 * @see controller/jobs/catalog/export/sitemap/changefreq
		 */
		$domains = $config->get( 'controller/jobs/catalog/export/sitemap/domains', ['text'] );

		/** controller/jobs/catalog/export/sitemap/max-items
		 * Maximum number of categories per site map
		 *
		 * Each site map file must not contain more than 50,000 links and it's
		 * size must be less than 10MB. If your catalog URLs are rather long
		 * and one of your site map files is bigger than 10MB, you should set
		 * the number of categories per file to a smaller value until each file
		 * is less than 10MB.
		 *
		 * More details about site maps can be found at
		 * {@link http://www.sitemaps.org/protocol.html sitemaps.org}
		 *
		 * @param integer Number of categories per file
		 * @since 2019.02
		 * @category Developer
		 * @category User
		 * @see controller/jobs/catalog/export/sitemap/container/options
		 * @see controller/jobs/catalog/export/sitemap/location
		 * @see controller/jobs/catalog/export/sitemap/max-query
		 * @see controller/jobs/catalog/export/sitemap/changefreq
		 * @see controller/jobs/catalog/export/sitemap/domains
		 */
		$maxItems = $config->get( 'controller/jobs/catalog/export/sitemap/max-items', 50000 );

		/** controller/jobs/catalog/export/sitemap/max-query
		 * Maximum number of categories per query
		 *
		 * The catalogs are fetched from the database in bunches for efficient
		 * retrieval. The higher the value, the lower the total time the database
		 * is busy finding the records. Higher values also means that record
		 * updates in the tables need to wait longer and the memory consumption
		 * of the PHP process is higher.
		 *
		 * Note: The value of max-query must be smaller than or equal to
		 * {@see controller/jobs/catalog/export/sitemap/max-items max-items}
		 *
		 * @param integer Number of categories per query
		 * @since 2019.02
		 * @category Developer
		 * @see controller/jobs/catalog/export/sitemap/container/options
		 * @see controller/jobs/catalog/export/sitemap/location
		 * @see controller/jobs/catalog/export/sitemap/max-items
		 * @see controller/jobs/catalog/export/sitemap/changefreq
		 * @see controller/jobs/catalog/export/sitemap/domains
		 */
		$maxQuery = $config->get( 'controller/jobs/catalog/export/sitemap/max-query', 1000 );

		$start = 0;
		$filenum = 1;
		$names = [];

		$manager = \Aimeos\MShop::create( $this->context(), 'catalog' );

		$search = $manager->filter( $default )->slice( 0, $maxQuery );
		$cursor = $manager->cursor( $search );

		$content = $this->createContent( $container, $filenum );
		$names[] = $content->getResource();

		while( $items = $manager->iterate( $cursor, $domains ) )
		{
			$free = $maxItems * $filenum - $start;
			$count = count( $items );

			if( $free < $count )
			{
				$this->addItems( $content, $items->slice( 0, $free ) );
				$items = $items->slice( $free );

				$this->closeContent( $content );
				$content = $this->createContent( $container, ++$filenum );
				$names[] = $content->getResource();
			}

			$this->addItems( $content, $items );

			$start += $count;
		}

		$this->closeContent( $content );

		return $names;
	}


	/**
	 * Returns the file name for the new content file
	 *
	 * @param int $number Current file number
	 * @return string New file name
	 */
	protected function getFilename( int $number ) : string
	{
		return sprintf( 'aimeos-catalog-sitemap-%d.xml', $number );
	}


	/**
	 * Returns the available locale items for the current site
	 *
	 * @return \Aimeos\Map List of locale items
	 */
	protected function locales() : \Aimeos\Map
	{
		if( !isset( $this->locales ) )
		{
			$manager = \Aimeos\MShop::create( $this->context(), 'locale' );
			$filter = $manager->filter()->add( ['locale.siteid' => $this->context()->locale()->getSiteId()] );

			$this->locales = $manager->search( $filter->order( 'locale.position' )->slice( 0, 10000 ) );
		}

		return $this->locales;
	}
}
