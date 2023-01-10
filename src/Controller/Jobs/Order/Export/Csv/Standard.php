<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2023
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
	extends \Aimeos\Controller\Jobs\Base
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
		$mq = $context->queue( 'mq-admin', 'order-export' );

		while( $msg = $mq->get() )
		{
			try
			{
				$body = $msg->getBody();

				if( ( $data = json_decode( $body, true ) ) === null ) {
					throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid message: %1$s', $body ) );
				}

				$this->export( $data );
			}
			catch( \Exception $e )
			{
				$str = 'Order export error: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
				$context->logger()->error( $str, 'order/export/csv' );
			}

			$mq->del( $msg );
		}
	}


	/**
	 * Initializes the search criteria
	 *
	 * @param \Aimeos\Base\Criteria\Iface $criteria New criteria object
	 * @param array $msg Message data
	 * @return \Aimeos\Base\Criteria\Iface Initialized criteria object
	 */
	protected function criteria( \Aimeos\Base\Criteria\Iface $criteria, array $msg ) : \Aimeos\Base\Criteria\Iface
	{
		/** controller/jobs/order/export/csv/max-size
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
		 * @since 2023.04
		 */
		$size = (int) $this->context()->config()->get( 'controller/jobs/order/export/csv/max-size', 1000 );

		return $criteria->add( $criteria->parse( $msg['filter'] ?? [] ) )->order( $msg['sort'] ?? [] )->slice( 0, $size );
	}


	/**
	 * Exports the orders
	 *
	 * @param array $msg Message data passed from the frontend
	 */
	protected function export( array $msg )
	{
		if( ( $fh = tmpfile() ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( 'Unable to create temporary file' );
		}

		$path = $this->path();
		$lcontext = $this->getLocaleContext( $msg );
		$manager = \Aimeos\MShop::create( $lcontext, 'order' );

		$cursor = $manager->cursor( $this->criteria( $manager->filter( false, true ), $msg ) );
		$ref = $lcontext->config()->get( 'mshop/order/manager/subdomains', [] );

		while( $items = $manager->iterate( $cursor, $ref ) )
		{
			if( fwrite( $fh, $this->render( $items ) ) === false ) {
				throw new \Aimeos\Controller\Jobs\Exception( 'Unable to add data to temporary file' );
			}
		}

		rewind( $fh );
		$lcontext->fs( 'fs-export' )->writes( $path, $fh );
		fclose( $fh );

		$manager = \Aimeos\MAdmin::create( $lcontext, 'job' );
		$manager->save( $manager->create()->setPath( $path )->setLabel( $path ), false );
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
	 * Returns the relative path the orders should be exported to
	 *
	 * @return string Relativ path to the export file
	 */
	protected function path() : string
	{
		/** controller/jobs/order/export/csv/path
		 * Relativ path to the export file
		 *
		 * It's more efficient to read and export more than one row at a time
		 * to speed up the export. Usually, the bigger the chunk that is exported
		 * at once, the less time the exporter will need. The downside is that
		 * the amount of memory required by the export process will increase as
		 * well. Therefore, it's a trade-off between memory consumption and
		 * export speed.
		 *
		 * @param string Relativ path with placeholders
		 * @since 2023.04
		 */
		$path = $this->context()->config()->get( 'controller/jobs/order/export/csv/path', 'order-export_%Y-%m-%d_%H-%i-%s' );
		return \Aimeos\Base\Str::strtime( $path );
	}


	/**
	 * Creates the CSV file for the given orders
	 *
	 * @param \Aimeos\MShop\Order\Item\Iface[] $items List of order items to export
	 * @return string Generated CSV
	 */
	protected function render( iterable $items ) : string
	{
		$context = $this->context();
		$template = $context->config()->get( 'controller/jobs/order/export/csv/template', 'order/export/csv/body' );

		return $context->view()->assign( ['items' => $items] )->render( $template );
	}
}
