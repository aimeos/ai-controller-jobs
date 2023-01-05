<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2022
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Admin\Log;


/**
 * Admin log controller.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/admin/log/name
	 * Class name of the used admin log scheduler controller implementation
	 *
	 * Each default log controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Admin\Log\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Admin\Log\Mylog
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/admin/log/name = Mylog
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyLog"!
	 *
	 * @param string Last part of the class name
	 * @since 2014.09
	 */

	/** controller/jobs/admin/log/decorators/excludes
	 * Excludes decorators added by the "common" option from the admin log controllers
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
	 *  controller/jobs/admin/log/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/admin/log/decorators/global
	 * @see controller/jobs/admin/log/decorators/local
	 */

	/** controller/jobs/admin/log/decorators/global
	 * Adds a list of globally available decorators only to the admin log controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/admin/log/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/admin/log/decorators/excludes
	 * @see controller/jobs/admin/log/decorators/local
	 */

	/** controller/jobs/admin/log/decorators/local
	 * Adds a list of local decorators only to the admin log controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Admin\Log\Decorator\*") around this job controller.
	 *
	 *  controller/jobs/admin/log/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Admin\Log\Decorator\Decorator2" only to this job
	 * controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/admin/log/decorators/excludes
	 * @see controller/jobs/admin/log/decorators/global
	 */


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Log cleanup' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Removes the old log entries from the database and archives them (optional)' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$fh = $this->tempfile();
		$context = $this->context();
		$fs = $context->fs( 'fs-admin' );

		$manager = \Aimeos\MAdmin::create( $context, 'log' );
		$filter = $manager->filter()->add( 'log.timestamp', '<=', $this->timestamp() )->order( 'log.timestamp' );
		$cursor = $manager->cursor( $filter->slice( 0, 1000 ) );

		while( $items = $manager->iterate( $cursor ) )
		{
			foreach( $items as $id => $item )
			{
				if( fputcsv( $fh, $item->toArray() ) === false ) {
					throw new \Aimeos\Controller\Jobs\Exception( 'Unable to write log data to temporary file' );
				}
			}

			$manager->delete( $items );
		}

		rewind( $fh );
		$fs->writes( $this->path(), $fh );
		fclose( $fh );
	}


	/**
	 * Returns the path where the export file should be stored
	 */
	protected function path() : string
	{
		/** controller/jobs/admin/log/path
		 * Path to a writable directory where the log archive files should be stored
		 *
		 * During normal operation, a lot of data can be logged, not only for
		 * errors that have occured. By default, these data is written into the
		 * log database and its size will grow if old log entries are not
		 * removed. There's a job controller available that can delete old log
		 * entries and save the old log entries to the given relative path.
		 *
		 * @param string Relative file system path in the fs-admin filesystem
		 * @since 2014.09
		 * @see controller/jobs/admin/log/limit-days
		 */
		$path = $this->context()->config()->get( 'controller/jobs/admin/log/path', 'logs' );

		return $path . '/aimeos_' . date( 'Y-m-d' ) . '.log';
	}


	/**
	 * Returns a file handle for a temporary file
	 *
	 * @return resource Temporary file handle
	 */
	protected function tempfile()
	{
		if( ( $fh = tmpfile() ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( 'Unable to create temporary file' );
		}

		return $fh;
	}


	/**
	 * Returns the timestamp until the logs entries should be moved
	 *
	 * @return string Timestamp in "YYYY-MM-DD HH:mm:ss" format
	 */
	protected function timestamp() : string
	{
		/** controller/jobs/admin/log/limit-days
		 * Only remove log entries that were created berore the configured number of days
		 *
		 * This option specifies the number of days log entries will be kept in
		 * the database. Afterwards, they will be removed and archived.
		 *
		 * @param integer Number of days
		 * @since 2014.09
		 * @see controller/jobs/admin/log/path
		 */
		$limit = $this->context()->config()->get( 'controller/jobs/admin/log/limit-days', 30 );
		return date( 'Y-m-d H:i:s', time() - $limit * 86400 );
	}
}
