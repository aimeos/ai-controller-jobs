<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Product\Export\Sitemap;


/**
 * Job controller for product sitemap.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/product/export/sitemap/name
	 * Class name of the used product suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Product\Export\Sitemap\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Product\Export\Sitemap\Mysitemap
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/product/export/sitemap/name = Mysitemap
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
	 * @since 2015.01
	 */

	/** controller/jobs/product/export/sitemap/decorators/excludes
	 * Excludes decorators added by the "common" option from the product export sitemap job controller
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
	 *  controller/jobs/product/export/sitemap/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.01
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/product/export/sitemap/decorators/global
	 * @see controller/jobs/product/export/sitemap/decorators/local
	 */

	/** controller/jobs/product/export/sitemap/decorators/global
	 * Adds a list of globally available decorators only to the product export sitemap job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/product/export/sitemap/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.01
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/product/export/sitemap/decorators/excludes
	 * @see controller/jobs/product/export/sitemap/decorators/local
	 */

	/** controller/jobs/product/export/sitemap/decorators/local
	 * Adds a list of local decorators only to the product export sitemap job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Product\Export\Sitemap\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/product/export/sitemap/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Product\Export\Sitemap\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.01
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/product/export/sitemap/export/sitemap/decorators/excludes
	 * @see controller/jobs/product/export/sitemap/export/sitemap/decorators/global
	 */


	private ?\Aimeos\Map $locales = null;


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Product site map' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Creates a product site map for search engines' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		/** controller/jobs/product/export/sitemap/hidden
		 * Export hidden products in site map
		 *
		 * The product site map contains no hidden products by default. If they
		 * should be part of the export, set this configuration option to TRUE.
		 *
		 * @param bool TRUE to export hidden products, FALSE if not
		 * @since 2022.01
		 * @see controller/jobs/product/export/sitemap/container/options
		 * @see controller/jobs/product/export/sitemap/location
		 * @see controller/jobs/product/export/sitemap/max-items
		 * @see controller/jobs/product/export/sitemap/max-query
		 * @see controller/jobs/product/export/sitemap/changefreq
		 */
		$hidden = $this->context()->config()->get( 'controller/jobs/product/export/sitemap/hidden', false );

		$this->createIndex( $this->export( $hidden ? null : true ) );
	}


	/**
	 * Adds the content for the site map index file
	 *
	 * @param array $files List of generated site map file names
	 */
	protected function createIndex( array $files )
	{
		$context = $this->context();
		$config = $context->config();
		$view = $context->view();

		/** controller/jobs/product/export/sitemap/template-index
		 * Relative path to the XML site map index template of the product site map job controller.
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
		 * @since 2015.01
		 * @see controller/jobs/product/export/sitemap/template-items
		 */
		$tplconf = 'controller/jobs/product/export/sitemap/template-index';

		if( empty( $baseUrl = rtrim( $config->get( 'resource/fs/baseurl', '' ), '/' ) ) )
		{
			$msg = sprintf( 'Required configuration for "%1$s" is missing', 'resource/fs/baseurl' );
			throw new \Aimeos\Controller\Jobs\Exception( $msg );
		}

		$view->siteFiles = $files;
		$view->baseUrl = $baseUrl . '/';

		$content = $view->render( $config->get( $tplconf, 'product/export/sitemap-index' ) );
		$context->fs()->write( $this->call( 'indexFilename' ), $content );
	}


	/**
	 * Exports the sitemap files
	 *
	 * @param bool|null $default TRUE to use default criteria, NULL for relaxed criteria
	 * @return array List of temporary files
	 */
	protected function export( ?bool $default = true ) : array
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'index' );

		$search = $manager->filter( $default )->order( 'product.id' );
		$search->add( $search->make( 'product:has', ['catalog'] ), '!=', null );
		$cursor = $manager->cursor( $search->slice( 0, $this->max() ) );

		$domains = $this->domains();
		$fs = $this->fs();

		$filenum = 1;
		$files = [];

		while( $items = $manager->iterate( $cursor, $domains ) )
		{
			$filename = $this->call( 'filename', $filenum++ );
			$fs->write( $filename, $this->render( $items ) );
			$files[] = $filename;
		}

		return $files;
	}


	/**
	 * Returns the domain names whose items should be exported too
	 *
	 * @return array List of domain names
	 */
	protected function domains() : array
	{
		/** controller/jobs/product/export/sitemap/domains
		 * List of associated items from other domains that should be fetched for the sitemap
		 *
		 * Catalogs consist not only of the base data but also of texts, media and
		 * other details. Those information is associated to the product via their lists.
		 * Using the "domains" option you can make more or less associated items available
		 * in the template.
		 *
		 * @param array List of domain names
		 * @since 2019.02
		 * @see controller/jobs/product/export/sitemap/max-items
		 */
		return $this->context()->config()->get( 'controller/jobs/product/export/sitemap/domains', ['text'] );
	}


	/**
	 * Returns the sitemap file name
	 *
	 * @param int $number Current file number
	 * @return string File name
	 */
	protected function filename( int $number ) : string
	{
		return sprintf( '%s-sitemap-%d.xml', $this->context()->locale()->getSiteItem()->getCode(), $number );
	}


	/**
	 * Returns the file system for storing the exported files
	 *
	 * @return \Aimeos\Base\Filesystem\Iface File system to store files to
	 */
	protected function fs() : \Aimeos\Base\Filesystem\Iface
	{
		return $this->context()->fs( 'fs' );
	}


	/**
	 * Returns the file name of the sitemap index file
	 *
	 * @return string File name
	 */
	protected function indexFilename() : string
	{
		return sprintf( '%s-sitemap-index.xml', $this->context()->locale()->getSiteItem()->getCode() );
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
	 * Returns the maximum number of exported products per file
	 *
	 * @return int Maximum number of exported products per file
	 */
	protected function max() : int
	{
		/** controller/jobs/product/export/sitemap/max-items
		 * Maximum number of categories per site map
		 *
		 * Each site map file must not contain more than 50,000 links and it's
		 * size must be less than 10MB. If your product URLs are rather long
		 * and one of your site map files is bigger than 10MB, you should set
		 * the number of categories per file to a smaller value until each file
		 * is less than 10MB.
		 *
		 * More details about site maps can be found at
		 * {@link http://www.sitemaps.org/protocol.html sitemaps.org}
		 *
		 * @param integer Number of categories per file
		 * @since 2019.02
		 * @see controller/jobs/product/export/sitemap/domains
		 */
		return $this->context()->config()->get( 'controller/jobs/product/export/sitemap/max-items', 10000 );
	}


	/**
	 * Creates sitemap with the given products
	 *
	 * @param \Aimeos\Map $items List of product items implementing \Aimeos\MShop\Product\Item\Iface
	 * @return string Rendered content
	 */
	protected function render( \Aimeos\Map $items ) : string
	{
		/** controller/jobs/product/export/sitemap/template
		 * Relative path to the XML template of the product site map job controller.
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
		$tplconf = 'controller/jobs/product/export/sitemap/template';

		$context = $this->context();
		$view = $context->view();

		$view->siteItems = $items;
		$view->siteLocales = $this->locales();

		return $view->render( $context->config()->get( $tplconf, 'product/export/sitemap-items' ) );
	}
}
