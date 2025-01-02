<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
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
		$manager = \Aimeos\MShop::create( $this->context(), 'product' );
		$filter = $manager->filter()->order( 'product.id' )->slice( 0, $this->max() );
		$cursor = $manager->cursor( $filter );

		$domains = $this->domains();
		$fs = $this->fs();
		$filenum = 1;

		while( $items = $manager->iterate( $cursor, $domains ) ) {
			$fs->write( $this->call( 'filename', $filenum++ ), $this->render( $items ) );
		}
	}


	/**
	 * Returns the domain names whose items should be exported too
	 *
	 * @return array List of domain names
	 */
	protected function domains() : array
	{
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
		 * @see controller/jobs/product/export/filename
		 * @see controller/jobs/product/export/max-items
		 */
		$default = ['attribute', 'media', 'price', 'product', 'text'];

		return $this->context()->config()->get( 'controller/jobs/product/export/domains', $default );
	}


	/**
	 * Returns the file name for the new content file
	 *
	 * @param int $number Current file number
	 * @return string New file name
	 */
	protected function filename( int $number ) : string
	{
		/** controller/jobs/product/export/filename
		 * Template for the generated file names
		 *
		 * The generated export files will be named according to the given
		 * string which can contain two place holders: The number of the
		 * exported product and the ISO date/time when the file was created.
		 *
		 * @param string File name template
		 * @since 2018.04
		 * @see controller/jobs/product/export/max-items
		 * @see controller/jobs/product/export/domains
		 */
		$name = $this->context()->config()->get( 'controller/jobs/product/export/filename', 'aimeos-products-%1$d_%2$s.xml' );

		return sprintf( $name, $number, date( 'Y-m-d_H:i:s' ) );
	}


	/**
	 * Returns the file system for storing the exported files
	 *
	 * @return \Aimeos\Base\Filesystem\Iface File system to store files to
	 */
	protected function fs() : \Aimeos\Base\Filesystem\Iface
	{
		return $this->context()->fs( 'fs-export' );
	}


	/**
	 * Returns the maximum number of exported products per file
	 *
	 * @return int Maximum number of exported products per file
	 */
	protected function max() : int
	{
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
		 * @see controller/jobs/product/export/filename
		 * @see controller/jobs/product/export/domains
		 */
		return $this->context()->config()->get( 'controller/jobs/product/export/max-items', 10000 );
	}


	/**
	 * Renders the output for the given items
	 *
	 * @param \Aimeos\Map $items List of product items implementing \Aimeos\MShop\Product\Item\Iface
	 * @return string Rendered content
	 */
	protected function render( \Aimeos\Map $items ) : string
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
		 * @see controller/jobs/product/export/domains
		 * @see controller/jobs/product/export/filename
		 * @see controller/jobs/product/export/max-items
		 */
		$tplconf = 'controller/jobs/product/export/template-items';
		$default = 'product/export/items-body-standard';

		$context = $this->context();
		$view = $context->view();

		$view->exportItems = $items;

		return $view->render( $context->config()->get( $tplconf, $default ) );
	}
}
