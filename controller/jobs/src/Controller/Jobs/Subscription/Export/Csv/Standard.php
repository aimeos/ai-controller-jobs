<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Subscription\Export\Csv;

use \Aimeos\MW\Logger\Base as Log;


/**
 * Job controller for CSV subscription exports.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Subscription\Export\Csv\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Subscription export CSV' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->getContext()->translate( 'controller/jobs', 'Exports subscriptions to CSV file' );
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
		$mappings = $this->getDefaultMapping();


		/** controller/common/subscription/export/csv/mapping
		 * List of mappings between the position in the CSV file and item keys
		 *
		 * The exporter has to know which data is at which position in the CSV
		 * file. Therefore, you need to specify a mapping between each position
		 * and the MShop domain item key (e.g. "subscription.type") it represents.
		 *
		 * These mappings are grouped together by their processor names, which
		 * are responsible for exporting the data, e.g. all mappings in "invoice"
		 * will be managed by the invoice processor while the mappings in
		 * "product" will be exported by the product processor.
		 *
		 * @param array Associative list of processor names and lists of key/position pairs
		 * @since 2018.04
		 * @category Developer
		 * @see controller/common/subscription/export/csv/max-size
		 */
		$mappings = $config->get( 'controller/common/subscription/export/csv/mapping', $mappings );

		/** controller/jobs/subscription/export/csv/mapping
		 * List of mappings between the position in the CSV file and item keys
		 *
		 * This configuration setting overwrites the shared option
		 * "controller/common/subscription/export/csv/mapping" if you need a
		 * specific setting for the job controller. Otherwise, you should
		 * use the shared option for consistency.
		 *
		 * @param array Associative list of processor names and lists of key/position pairs
		 * @since 2018.04
		 * @category Developer
		 * @see controller/common/subscription/export/csv/max-size
		 */
		$mappings = $config->get( 'controller/jobs/subscription/export/csv/mapping', $mappings );


		/** controller/common/subscription/export/csv/max-size
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
		 * @since 2018.04
		 * @category Developer
		 * @see controller/common/subscription/export/csv/mapping
		 */
		$maxcnt = (int) $config->get( 'controller/common/subscription/export/csv/max-size', 1000 );


		$processors = $this->getProcessors( $mappings );
		$mq = $context->getMessageQueueManager()->get( 'mq-admin' )->getQueue( 'subscription-export' );

		while( ( $msg = $mq->get() ) !== null )
		{
			try
			{
				if( ( $data = json_decode( $msg->getBody(), true ) ) === null ) {
					throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid message: %1$s', $msg->getBody() ) );
				}

				$this->export( $processors, $data, $maxcnt );
			}
			catch( \Exception $e )
			{
				$msg = 'Subscription export error: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
				$logger->log( $msg, Log::ERR, 'export/csv/subscription' );
			}

			$mq->del( $msg );
		}
	}


	/**
	 * Creates a new job entry for the exported file
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context item
	 * @param string $path Absolute path to the exported file
	 */
	protected function addJob( \Aimeos\MShop\Context\Item\Iface $context, string $path )
	{
		$manager = \Aimeos\MAdmin::create( $context, 'job' );
		$item = $manager->create()->setPath( $path )->setLabel( $path );
		$manager->save( $item, false );
	}


	/**
	 * Opens and returns the container which includes the subscription data
	 *
	 * @return \Aimeos\MW\Container\Iface Container object
	 */
	protected function getContainer() : \Aimeos\MW\Container\Iface
	{
		$config = $this->getContext()->getConfig();

		/** controller/jobs/subscription/export/csv/location
		 * Temporary file or directory where the content is stored which should be exported
		 *
		 * The path can point to any supported container format as long as the
		 * content is in CSV format, e.g.
		 *
		 * * Directory container / CSV file
		 * * Zip container / compressed CSV file
		 *
		 * @param string Absolute file or directory path
		 * @since 2018.04
		 * @category Developer
		 * @category User
		 * @see controller/jobs/subscription/export/csv/container/type
		 * @see controller/jobs/subscription/export/csv/container/content
		 * @see controller/jobs/subscription/export/csv/container/options
		 */
		$location = $config->get( 'controller/jobs/subscription/export/csv/location', sys_get_temp_dir() );

		/** controller/jobs/subscription/export/csv/container/type
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
		 * @see controller/jobs/subscription/export/csv/location
		 * @see controller/jobs/subscription/export/csv/container/content
		 * @see controller/jobs/subscription/export/csv/container/options
		 */
		$container = $config->get( 'controller/jobs/subscription/export/csv/container/type', 'Directory' );

		/** controller/jobs/subscription/export/csv/container/content
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
		 * @see controller/jobs/subscription/export/csv/location
		 * @see controller/jobs/subscription/export/csv/container/type
		 * @see controller/jobs/subscription/export/csv/container/options
		 */
		$content = $config->get( 'controller/jobs/subscription/export/csv/container/content', 'CSV' );

		/** controller/jobs/subscription/export/csv/container/options
		 * List of file container options for the subscription export files
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
		 * @see controller/jobs/subscription/export/csv/location
		 * @see controller/jobs/subscription/export/csv/container/content
		 * @see controller/jobs/subscription/export/csv/container/type
		 */
		$options = $config->get( 'controller/jobs/subscription/export/csv/container/options', [] );

		return \Aimeos\MW\Container\Factory::getContainer( $location, $container, $content, $options );
	}


	/**
	 * Exports the subscriptions and returns the exported file name
	 *
	 * @param Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Iface[] List of processor objects
	 * @param array $msg Message data passed from the frontend
	 * @param int $maxcnt Maximum number of retrieved subscriptions at once
	 */
	protected function export( array $processors, array $msg, int $maxcnt )
	{
		$lcontext = $this->getLocaleContext( $msg );
		$baseRef = ['order/base/address', 'order/base/product'];

		/** controller/jobs/subscription/export/csv/collapse
		 * Collapse all lines in the subscription export on only one line
		 *
		 * By default, the subscription export will contain several lines for each
		 * subscription, e.g. for the product, the addresses, the delivery and payment
		 * services as well as the invoice data. You can merge them into one line using
		 * this configuration setting.
		 *
		 * @param bool True to collapse all lines of one subscription, false for separate lines
		 * @since 2020.07
		 */
		$collapse = $lcontext->getConfig()->get( 'controller/jobs/subscription/export/csv/collapse', false );

		$manager = \Aimeos\MShop::create( $lcontext, 'subscription' );
		$baseManager = \Aimeos\MShop::create( $lcontext, 'order/base' );

		$container = $this->getContainer();
		$content = $container->create( 'subscription-export_' . date( 'Y-m-d_H-i-s' ) );
		$search = $this->initCriteria( $manager->filter( false, true )->slice( 0, 0x7fffffff ), $msg );
		$start = 0;

		do
		{
			$baseIds = [];
			$search->slice( $start, $maxcnt );
			$items = $manager->search( $search );

			foreach( $items as $item ) {
				$baseIds[] = $item->getOrderBaseId();
			}

			$baseSearch = $baseManager->filter( false, true );
			$baseSearch->setConditions( $baseSearch->compare( '==', 'order.base.id', $baseIds ) );
			$baseSearch->slice( 0, count( $baseIds ) );

			$baseItems = $baseManager->search( $baseSearch, $baseRef );

			foreach( $items as $id => $item )
			{
				$lines = [];

				foreach( $processors as $type => $processor )
				{
					foreach( $processor->process( $item, $baseItems[$item->getOrderBaseId()] ) as $line )
					{
						if( !$collapse ) {
							$lines[] = [0 => $type, 1 => $id] + $line;
						} else {
							$lines[0] = array_replace( $lines[0] ?? [], $line );
						}
					}
				}

				foreach( $lines as $line ) {
					$content->add( $line );
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
	 * @return \Aimeos\MShop\Context\Item\Iface New context item with updated locale
	 */
	protected function getLocaleContext( array $msg ) : \Aimeos\MShop\Context\Item\Iface
	{
		$lcontext = clone $this->getContext();
		$manager = \Aimeos\MShop::create( $lcontext, 'locale' );

		$sitecode = ( isset( $msg['sitecode'] ) ? $msg['sitecode'] : 'default' );
		$localeItem = $manager->bootstrap( $sitecode, '', '', false, \Aimeos\MShop\Locale\Manager\Base::SITE_ONE );

		return $lcontext->setLocale( $localeItem );
	}


	/**
	 * Initializes the search criteria
	 *
	 * @param \Aimeos\MW\Criteria\Iface $criteria New criteria object
	 * @param array $msg Message data
	 * @return \Aimeos\MW\Criteria\Iface Initialized criteria object
	 */
	protected function initCriteria( \Aimeos\MW\Criteria\Iface $criteria, array $msg ) : \Aimeos\MW\Criteria\Iface
	{
		return $criteria->add( $criteria->parse( $msg['filter'] ?? [] ) )->order( $msg['sort'] ?? [] );
	}


	/**
	 * Moves the exported file to the final storage
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context item
	 * @param string $path Absolute path to the exported file
	 * @return string Relative path of the file in the storage
	 */
	protected function moveFile( \Aimeos\MShop\Context\Item\Iface $context, string $path ) : string
	{
		$filename = basename( $path );
		$context->getFileSystemManager()->get( 'fs-admin' )->writef( $filename, $path );

		unlink( $path );
		return $filename;
	}
}
