<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2022
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Order\Export\Csv;


/**
 * Job controller for CSV order exports.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Order\Export\Csv\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/order/export/csv/name
	 * Class name of the used order suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Order\Export\Csv\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Order\Export\Csv\Mycsv
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/order/export/csv/name = Mycsv
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyCsv"!
	 *
	 * @param string Last part of the class name
	 * @since 2015.01
	 * @category Developer
	 */

	/** controller/jobs/order/export/csv/decorators/excludes
	 * Excludes decorators added by the "common" option from the order export CSV job controller
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
	 *  controller/jobs/order/export/csv/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.01
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/export/csv/decorators/global
	 * @see controller/jobs/order/export/csv/decorators/local
	 */

	/** controller/jobs/order/export/csv/decorators/global
	 * Adds a list of globally available decorators only to the order export CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/order/export/csv/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.01
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/export/csv/decorators/excludes
	 * @see controller/jobs/order/export/csv/decorators/local
	 */

	/** controller/jobs/order/export/csv/decorators/local
	 * Adds a list of local decorators only to the order export CSV job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Order\Export\Csv\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/order/export/csv/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Order\Export\Csv\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.01
	 * @category Developer
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/export/csv/decorators/excludes
	 * @see controller/jobs/order/export/csv/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Order export CSV' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Exports orders to CSV file' );
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
		$mappings = $this->getDefaultMapping();


		/** controller/common/order/export/csv/mapping
		 * List of mappings between the position in the CSV file and item keys
		 *
		 * The exporter has to know which data is at which position in the CSV
		 * file. Therefore, you need to specify a mapping between each position
		 * and the MShop domain item key (e.g. "order.type") it represents.
		 *
		 * These mappings are grouped together by their processor names, which
		 * are responsible for exporting the data, e.g. all mappings in "invoice"
		 * will be managed by the invoice processor while the mappings in
		 * "product" will be exported by the product processor.
		 *
		 * @param array Associative list of processor names and lists of key/position pairs
		 * @since 2017.08
		 * @category Developer
		 * @see controller/common/order/export/csv/max-size
		 */
		$mappings = $config->get( 'controller/common/order/export/csv/mapping', $mappings );

		/** controller/jobs/order/export/csv/mapping
		 * List of mappings between the position in the CSV file and item keys
		 *
		 * This configuration setting overwrites the shared option
		 * "controller/common/order/export/csv/mapping" if you need a
		 * specific setting for the job controller. Otherwise, you should
		 * use the shared option for consistency.
		 *
		 * @param array Associative list of processor names and lists of key/position pairs
		 * @since 2017.08
		 * @category Developer
		 * @see controller/common/order/export/csv/max-size
		 */
		$mappings = $config->get( 'controller/jobs/order/export/csv/mapping', $mappings );


		/** controller/common/order/export/csv/max-size
		 * Maximum number of CSV rows to export at once
		 *
		 * It's more efficient to read and export more than one row at a time
		 * to speed up the export. Usually, the bigger the chunk that is exported
		 * at once, the less time the exporter will need. The downside is that
		 * the amount of memory required by the export process will increase as
		 * well. Therefore, it's a trade-off between memory consumption and
		 * export speed.
		 *
		 * @param integer Number of rows
		 * @since 2017.08
		 * @category Developer
		 * @see controller/common/order/export/csv/mapping
		 */
		$maxcnt = (int) $config->get( 'controller/common/order/export/csv/max-size', 1000 );


		$processed = [];
		$processors = $this->getProcessors( $mappings );
		$mq = $context->queue( 'mq-admin', 'order-export' );

		while( $msg = $mq->get() )
		{
			try
			{
				$body = $msg->getBody();
				$hash = md5( $body );

				if( !isset( $processed[$hash] ) )
				{
					$processed[$hash] = true;

					if( ( $data = json_decode( $body, true ) ) === null ) {
						throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid message: %1$s', $body ) );
					}

					$this->export( $processors, $data, $maxcnt );
				}
			}
			catch( \Exception $e )
			{
				$msg = 'Order export error: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
				$logger->error( $msg, 'order/export/csv' );
			}

			$mq->del( $msg );
		}
	}


	/**
	 * Creates a new job entry for the exported file
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context item
	 * @param string $path Absolute path to the exported file
	 */
	protected function addJob( \Aimeos\MShop\ContextIface $context, string $path )
	{
		$manager = \Aimeos\MAdmin::create( $context, 'job' );
		$item = $manager->create()->setPath( $path )->setLabel( $path );
		$manager->save( $item, false );
	}


	/**
	 * Opens and returns the container which includes the order data
	 *
	 * @return \Aimeos\MW\Container\Iface Container object
	 */
	protected function getContainer() : \Aimeos\MW\Container\Iface
	{
		$config = $this->context()->config();

		/** controller/jobs/order/export/csv/location
		 * Temporary file or directory where the content is stored which should be exported
		 *
		 * The path can point to any supported container format as long as the
		 * content is in CSV format, e.g.
		 *
		 * * Directory container / CSV file
		 * * Zip container / compressed CSV file
		 *
		 * @param string Absolute file or directory path
		 * @since 2017.08
		 * @category Developer
		 * @category User
		 * @see controller/jobs/order/export/csv/container/type
		 * @see controller/jobs/order/export/csv/container/content
		 * @see controller/jobs/order/export/csv/container/options
		 */
		$location = $config->get( 'controller/jobs/order/export/csv/location', sys_get_temp_dir() );

		/** controller/jobs/order/export/csv/container/type
		 * Nave of the container type to read the data from
		 *
		 * The container type tells the exporter how it should retrieve the data.
		 * There are currently three container types that support the necessary
		 * CSV content:
		 *
		 * * Directory
		 * * Zip
		 *
		 * @param string Container type name
		 * @since 2015.05
		 * @category Developer
		 * @category User
		 * @see controller/jobs/order/export/csv/location
		 * @see controller/jobs/order/export/csv/container/content
		 * @see controller/jobs/order/export/csv/container/options
		 */
		$container = $config->get( 'controller/jobs/order/export/csv/container/type', 'Directory' );

		/** controller/jobs/order/export/csv/container/content
		 * Name of the content type inside the container to read the data from
		 *
		 * The content type must always be a CSV-like format and there are
		 * currently two format types that are supported:
		 *
		 * * CSV
		 *
		 * @param array Content type name
		 * @since 2015.05
		 * @category Developer
		 * @category User
		 * @see controller/jobs/order/export/csv/location
		 * @see controller/jobs/order/export/csv/container/type
		 * @see controller/jobs/order/export/csv/container/options
		 */
		$content = $config->get( 'controller/jobs/order/export/csv/container/content', 'CSV' );

		/** controller/jobs/order/export/csv/container/options
		 * List of file container options for the order export files
		 *
		 * Some container/content type allow you to hand over additional settings
		 * for configuration. Please have a look at the article about
		 * {@link http://aimeos.org/docs/Developers/Utility/Create_and_read_files container/content files}
		 * for more information.
		 *
		 * @param array Associative list of option name/value pairs
		 * @since 2015.05
		 * @category Developer
		 * @category User
		 * @see controller/jobs/order/export/csv/location
		 * @see controller/jobs/order/export/csv/container/content
		 * @see controller/jobs/order/export/csv/container/type
		 */
		$options = $config->get( 'controller/jobs/order/export/csv/container/options', [] );

		return \Aimeos\MW\Container\Factory::getContainer( $location, $container, $content, $options );
	}


	/**
	 * Exports the orders and returns the exported file name
	 *
	 * @param Aimeos\Controller\Common\Order\Export\Csv\Processor\Iface[] List of processor objects
	 * @param array $msg Message data passed from the frontend
	 * @param int $maxcnt Maximum number of retrieved orders at once
	 */
	protected function export( array $processors, array $msg, int $maxcnt )
	{
		$lcontext = $this->getLocaleContext( $msg );
		$siteId = $lcontext->locale()->getSiteId();
		$manager = \Aimeos\MShop::create( $lcontext, 'order' );
		$ref = ['order/base', 'order/base/address', 'order/base/coupon', 'order/base/product', 'order/base/service'];

		$container = $this->getContainer();
		$content = $container->create( 'order-export_' . date( 'Y-m-d_H-i-s' ) );

		$search = $this->initCriteria( $manager->filter( false, true ), $msg );
		$search->setSortations( array_merge( $search->getSortations(), [$search->sort( '+', 'order.id' )] ) );

		$start = 0;

		do
		{
			$search->slice( $start, $maxcnt );
			$items = $manager->search( $search, $ref );

			foreach( $items as $id => $item )
			{
				foreach( $processors as $type => $processor )
				{
					foreach( $processor->process( $item, $item->getBaseItem(), $siteId ) as $line ) {
						$content->add( [0 => $type, 1 => $id] + $line );
					}
				}
			}

			$count = count( $items );
			$start += $count;
		}
		while( $count === $search->getLimit() );

		$path = $content->getResource();
		$container->add( $content );
		$container->close();

		$path = $this->moveFile( $lcontext, $path );
		$this->addJob( $lcontext, $path );
	}


	/**
	 * Returns a new context including the locale from the message data
	 *
	 * @param array $msg Message data including a "sitecode" value
	 * @return \Aimeos\MShop\ContextIface New context item with updated locale
	 */
	protected function getLocaleContext( array $msg ) : \Aimeos\MShop\ContextIface
	{
		$lcontext = clone $this->context();
		$manager = \Aimeos\MShop::create( $lcontext, 'locale' );

		$sitecode = ( isset( $msg['sitecode'] ) ? $msg['sitecode'] : 'default' );
		$localeItem = $manager->bootstrap( $sitecode, '', '', false, \Aimeos\MShop\Locale\Manager\Base::SITE_PATH );

		return $lcontext->setLocale( $localeItem );
	}


	/**
	 * Initializes the search criteria
	 *
	 * @param \Aimeos\Base\Criteria\Iface $criteria New criteria object
	 * @param array $msg Message data
	 * @return \Aimeos\Base\Criteria\Iface Initialized criteria object
	 */
	protected function initCriteria( \Aimeos\Base\Criteria\Iface $criteria, array $msg ) : \Aimeos\Base\Criteria\Iface
	{
		return $criteria->add( $criteria->parse( $msg['filter'] ?? [] ) )->order( $msg['sort'] ?? [] );
	}


	/**
	 * Moves the exported file to the final storage
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context item
	 * @param string $path Absolute path to the exported file
	 * @return string Relative path of the file in the storage
	 */
	protected function moveFile( \Aimeos\MShop\ContextIface $context, string $path ) : string
	{
		$filename = basename( $path );
		$context->fs( 'fs-admin' )->writef( $filename, $path );

		unlink( $path );
		return $filename;
	}
}
