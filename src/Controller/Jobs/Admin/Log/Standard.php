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
	 * @category Developer
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
	 * @category Developer
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
	 * @category Developer
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
	 * @category Developer
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
		$context = $this->context();
		$config = $context->config();
		$container = null;

		/** controller/jobs/admin/log/limit-days
		 * Only remove log entries that were created berore the configured number of days
		 *
		 * This option specifies the number of days log entries will be kept in
		 * the database. Afterwards, they will be removed and archived if a
		 * path for storing the archive files is configured.
		 *
		 * @param integer Number of days
		 * @since 2014.09
		 * @category User
		 * @category Developer
		 * @see controller/jobs/admin/log/path
		 * @see controller/jobs/admin/log/container/type
		 * @see controller/jobs/admin/log/container/format
		 * @see controller/jobs/admin/log/container/options
		 */
		$limit = $config->get( 'controller/jobs/admin/log/limit-days', 30 );
		$limitDate = date( 'Y-m-d H:i:s', time() - $limit * 86400 );

		/** controller/jobs/admin/log/path
		 * Path to a writable directory where the log archive files should be stored
		 *
		 * During normal operation, a lot of data can be logged, not only for
		 * errors that have occured. By default, these data is written into the
		 * log database and its size will grow if old log entries are not
		 * removed. There's a job controller available that can delete old log
		 * entries.
		 *
		 * If an absolute path to a writeable directory in the file system is
		 * set via this configuration option, the job controller will save the
		 * old log entries to a file in this path. They can be analysed later
		 * if required.
		 *
		 * The type and format of these files as well as the time frame after
		 * the log entries are removed from the log database can be configured
		 * too.
		 *
		 * @param string Absolute file system path to a writable directory
		 * @since 2014.09
		 * @category Developer
		 * @category User
		 * @see controller/jobs/admin/log/container/type
		 * @see controller/jobs/admin/log/container/format
		 * @see controller/jobs/admin/log/container/options
		 * @see controller/jobs/admin/log/limit-days
		 */
		$path = $config->get( 'controller/jobs/admin/log/path', sys_get_temp_dir() );

		/** controller/jobs/admin/log/container/type
		 * Container file type storing all coupon code files to import
		 *
		 * All coupon code files or content objects must be put into one
		 * container file so editors don't have to upload one file for each
		 * coupon code file.
		 *
		 * The container file types that are supported by default are:
		 *
		 * * Zip
		 *
		 * Extensions implement other container types like spread sheets, XMLs or
		 * more advanced ways of handling the exported data.
		 *
		 * @param string Container file type
		 * @since 2014.09
		 * @category Developer
		 * @category User
		 * @see controller/jobs/admin/log/path
		 * @see controller/jobs/admin/log/container/format
		 * @see controller/jobs/admin/log/container/options
		 * @see controller/jobs/admin/log/limit-days
		 */

		/** controller/jobs/admin/log/container/format
		 * Format of the coupon code files to import
		 *
		 * The coupon codes are stored in one or more files or content
		 * objects. The format of that file or content object can be configured
		 * with this option but most formats are bound to a specific container
		 * type.
		 *
		 * The formats that are supported by default are:
		 *
		 * * CSV (requires container type "Zip")
		 *
		 * Extensions implement other container types like spread sheets, XMLs or
		 * more advanced ways of handling the exported data.
		 *
		 * @param string Content file type
		 * @since 2014.09
		 * @category Developer
		 * @category User
		 * @see controller/jobs/admin/log/path
		 * @see controller/jobs/admin/log/container/type
		 * @see controller/jobs/admin/log/container/options
		 * @see controller/jobs/admin/log/limit-days
		 */

		/** controller/jobs/admin/log/container/options
		 * Options changing the expected format of the coupon codes to import
		 *
		 * Each content format may support some configuration options to change
		 * the output for that content type.
		 *
		 * The options for the CSV content format are:
		 *
		 * * csv-separator, default ','
		 * * csv-enclosure, default '"'
		 * * csv-escape, default '"'
		 * * csv-lineend, default '\n'
		 *
		 * For format options provided by other container types implemented by
		 * extensions, please have a look into the extension documentation.
		 *
		 * @param array Associative list of options with the name as key and its value
		 * @since 2014.09
		 * @category Developer
		 * @category User
		 * @see controller/jobs/admin/log/path
		 * @see controller/jobs/admin/log/container/type
		 * @see controller/jobs/admin/log/container/format
		 * @see controller/jobs/admin/log/limit-days
		 */

		$type = $config->get( 'controller/jobs/admin/log/container/type', 'Zip' );
		$format = $config->get( 'controller/jobs/admin/log/container/format', 'CSV' );
		$options = $config->get( 'controller/jobs/admin/log/container/options', [] );

		$path .= DIRECTORY_SEPARATOR . str_replace( ' ', '_', $limitDate );
		$container = \Aimeos\MW\Container\Factory::getContainer( $path, $type, $format, $options );

		$manager = \Aimeos\MAdmin::create( $context, 'log' );

		$search = $manager->filter();
		$search->setConditions( $search->compare( '<=', 'log.timestamp', $limitDate ) );
		$search->setSortations( array( $search->sort( '+', 'log.timestamp' ) ) );

		$start = 0;
		$contents = [];

		do
		{
			$ids = [];
			$items = $manager->search( $search );

			foreach( $items as $id => $item )
			{
				if( $container !== null )
				{
					$facility = $item->getFacility();

					if( !isset( $contents[$facility] ) ) {
						$contents[$facility] = $container->create( $facility );
					}

					$contents[$facility]->add( $item->toArray() );
				}

				$ids[] = $id;
			}

			$manager->delete( $ids );

			$count = count( $items );
			$start += $count;
			$search->slice( $start );
		}
		while( $count >= $search->getLimit() );


		if( $container !== null && !empty( $contents ) )
		{
			foreach( $contents as $content ) {
				$container->add( $content );
			}

			$container->close();
		}
	}
}
