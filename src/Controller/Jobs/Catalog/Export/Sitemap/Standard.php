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
		$context = $this->context();

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
		$hidden = $context->config()->get( 'controller/jobs/catalog/export/sitemap/hidden', false );

		$names = [];
		$fs = $context->fs();

		foreach( $this->createSitemaps( $hidden ? null : true ) as $idx => $file )
		{
			$name = $this->call( 'sitemapFilename', $idx + 1 );
			$fs->writes( $name, $file );
			$names[] = $name;
			fclose( $file );
		}

		$this->createSitemapIndex( $names );
	}


	/**
	 * Creates a temporary sitemap file with the given categories
	 *
	 * @param \Aimeos\Map $items List of catalog items implementing \Aimeos\MShop\Catalog\Item\Iface
	 * @return resource File handle
	 */
	protected function create( \Aimeos\Map $items )
	{
		/** controller/jobs/catalog/export/sitemap/template
		 * Relative path to the XML template of the catalog site map job controller.
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
		 * @param string Relative path to the template creating XML code for the site map
		 * @since 2022.10
		 */
		$tplconf = 'controller/jobs/catalog/export/sitemap/template';

		$context = $this->context();
		$view = $context->view();

		$view->siteItems = $items;
		$view->siteLocales = $this->locales();

		$content = $view->render( $context->config()->get( $tplconf, 'catalog/export/sitemap-items' ) );

		if( ( $file = tmpfile() ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( 'Unable to create temporary sitemap file' );
		}

		if( fwrite( $file, $content ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( 'Unable to write to temporary sitemap file' );
		}

		if( rewind( $file ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( 'Unable to rewind temporary sitemap file' );
		}

		return $file;
	}


	/**
	 * Adds the content for the site map index file
	 *
	 * @param array $files List of generated site map file names
	 */
	protected function createSitemapIndex( array $files )
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
		 * templates/controller/jobs).
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

		if( empty( $baseUrl ) )
		{
			$msg = sprintf( 'Required configuration for "%1$s" is missing', 'resource/fs/baseurl' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		$view->siteFiles = $files;
		$view->baseUrl = rtrim( $baseUrl, '/' ) . '/';

		$content = $view->render( $config->get( $tplconf, 'catalog/export/sitemap-index' ) );
		$context->fs()->write( $this->call( 'sitemapIndexFilename' ), $content );
	}


	/**
	 * Creates the sitemap files
	 *
	 * @param bool|null $default TRUE to use default criteria, NULL for relaxed criteria
	 * @return array List of temporary files
	 */
	protected function createSitemaps( ?bool $default = true ) : array
	{
		$files = [];
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
		 * @see controller/jobs/catalog/export/sitemap/max-items
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
		 * @see controller/jobs/catalog/export/sitemap/domains
		 */
		$maxItems = $config->get( 'controller/jobs/catalog/export/sitemap/max-items', 10000 );

		$manager = \Aimeos\MShop::create( $this->context(), 'catalog' );

		$search = $manager->filter( $default )->slice( 0, $maxItems );
		$cursor = $manager->cursor( $search );

		while( $items = $manager->iterate( $cursor, $domains ) ) {
			$files[] = $this->create( $items );
		}

		return $files;
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
			$filter = $manager->filter( true )->add( ['locale.siteid' => $this->context()->locale()->getSiteId()] );

			$this->locales = $manager->search( $filter->order( 'locale.position' )->slice( 0, 10000 ) );
		}

		return $this->locales;
	}


	/**
	 * Returns the sitemap file name
	 *
	 * @param int $number Current file number
	 * @return string File name
	 */
	protected function sitemapFilename( int $number ) : string
	{
		return sprintf( 'aimeos-catalog-sitemap-%d.xml', $number );
	}


	/**
	 * Returns the file name of the sitemap index file
	 *
	 * @return string File name
	 */
	protected function sitemapIndexFilename() : string
	{
		return 'aimeos-catalog-sitemap-index.xml';
	}
}
