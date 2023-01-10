<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2023
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller;


/**
 * Factory which can create all job controllers
 *
 * @package Controller
 * @subpackage Jobs
 */
class Jobs
{
	private static $objects = [];


	/**
	 * Creates the required controller specified by the given path of controller names.
	 *
	 * Controllers are created by providing only the domain name, e.g.
	 * "stock" for the \Aimeos\Controller\Jobs\Stock\Standard.
	 * Please note, that only the default controllers can be created. If you need
	 * a specific implementation, you need to use the factory class of the
	 * controller to hand over specifc implementation names.
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object required by controllers
	 * @param \Aimeos\Bootstrap $aimeos \Aimeos\Bootstrap object
	 * @param string $path Name of the domain
	 * @param string|null $name Name of the controller implementation ("Standard" if null)
	 * @return \Aimeos\Controller\Jobs\Iface Controller class instance
	 * @throws \Aimeos\Controller\Jobs\Exception If the given path is invalid or the controllers wasn't found
	 */
	public static function create( \Aimeos\MShop\ContextIface $context, \Aimeos\Bootstrap $aimeos,
		string $path, string $name = null ) : \Aimeos\Controller\Jobs\Iface
	{
		if( empty( $path ) ) {
			throw new \Aimeos\Controller\Jobs\Exception( 'Controller path is empty', 400 );
		}

		if( empty( $name ) ) {
			$name = $context->config()->get( 'controller/jobs/' . $path . '/name', 'Standard' );
		}

		$iface = '\\Aimeos\\Controller\\Jobs\\Iface';
		$classname = '\\Aimeos\\Controller\\Jobs\\' . str_replace( '/', '\\', ucwords( $path, '/' ) ) . '\\' . $name;

		return self::createController( $context, $aimeos, $classname, $iface, $path );
	}


	/**
	 * Returns all available controller instances.
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object required by controllers
	 * @param \Aimeos\Bootstrap $aimeos \Aimeos\Bootstrap object
	 * @param array $cntlPaths Associative list of the base path as key and all relative job controller paths (core and extensions)
	 * @return \Aimeos\Controller\Jobs\Iface[] Associative list of controller names as values and class instance as values
	 */
	public static function get( \Aimeos\MShop\ContextIface $context, \Aimeos\Bootstrap $aimeos, array $cntlPaths ) : array
	{
		$cntlList = [];
		$ds = DIRECTORY_SEPARATOR;

		foreach( $cntlPaths as $path => $list )
		{
			foreach( $list as $relpath )
			{
				$path .= $ds . str_replace( '/', $ds, $relpath . '/Controller/Jobs' );

				if( is_dir( $path ) )
				{
					$it = new \DirectoryIterator( $path );
					$list = self::createControllers( $it, $context, $aimeos );

					$cntlList = array_merge( $cntlList, $list );
				}
			}
		}

		ksort( $cntlList );

		return $cntlList;
	}


	/**
	 * Injects a controller object.
	 *
	 * @param string $classname Full name of the class for which the object should be returned
	 * @param \Aimeos\Controller\Jobs\Iface|null $controller Frontend controller object
	 */
	public static function inject( string $classname, \Aimeos\Controller\Jobs\Iface $controller = null )
	{
		self::$objects['\\' . ltrim( $classname, '\\' )] = $controller;
	}


	/**
	 * Adds the decorators to the controller object.
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context instance with necessary objects
	 * @param \Aimeos\Bootstrap $aimeos \Aimeos\Bootstrap object
	 * @param \Aimeos\Controller\Jobs\Iface $controller Controller object
	 * @param string $domain Domain name in lower case, e.g. "product"
	 * @return \Aimeos\Controller\Jobs\Iface Controller object
	 */
	protected static function addControllerDecorators( \Aimeos\MShop\ContextIface $context, \Aimeos\Bootstrap $aimeos,
		\Aimeos\Controller\Jobs\Iface $controller, string $domain ) : \Aimeos\Controller\Jobs\Iface
	{
		$localClass = str_replace( '/', '\\', ucwords( $domain, '/' ) );
		$config = $context->config();

		/** controller/jobs/common/decorators/default
		 * Configures the list of decorators applied to all job controllers
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to configure a list of decorator names that should
		 * be wrapped around the original instance of all created controllers:
		 *
		 *  controller/jobs/common/decorators/default = array( 'decorator1', 'decorator2' )
		 *
		 * This would wrap the decorators named "decorator1" and "decorator2" around
		 * all controller instances in that order. The decorator classes would be
		 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" and
		 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator2".
		 *
		 * @param array List of decorator names
		 * @since 2014.03
		 */
		$decorators = $config->get( 'controller/jobs/common/decorators/default', [] );
		$excludes = $config->get( 'controller/jobs/' . $domain . '/decorators/excludes', [] );

		foreach( $decorators as $key => $name )
		{
			if( in_array( $name, $excludes ) ) {
				unset( $decorators[$key] );
			}
		}

		$classprefix = '\Aimeos\Controller\Jobs\Common\Decorator\\';
		$controller = self::addDecorators( $context, $aimeos, $controller, $decorators, $classprefix );

		$classprefix = '\Aimeos\Controller\Jobs\Common\Decorator\\';
		$decorators = $config->get( 'controller/jobs/' . $domain . '/decorators/global', [] );
		$controller = self::addDecorators( $context, $aimeos, $controller, $decorators, $classprefix );

		$classprefix = '\Aimeos\Controller\Jobs\\' . ucfirst( $localClass ) . '\Decorator\\';
		$decorators = $config->get( 'controller/jobs/' . $domain . '/decorators/local', [] );
		$controller = self::addDecorators( $context, $aimeos, $controller, $decorators, $classprefix );

		return $controller;
	}


	/**
	 * Adds the decorators to the controller object.
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context instance with necessary objects
	 * @param \Aimeos\Bootstrap $aimeos Aimeos Bootstrap object
	 * @param \Aimeos\Controller\Jobs\Iface $controller Controller object
	 * @param array $decorators List of decorator names that should be wrapped around the controller object
	 * @param string $classprefix Decorator class prefix, e.g. "\Aimeos\Controller\Jobs\Attribute\Decorator\"
	 * @return \Aimeos\Controller\Jobs\Iface Controller object
	 */
	protected static function addDecorators( \Aimeos\MShop\ContextIface $context, \Aimeos\Bootstrap $aimeos,
		\Aimeos\Controller\Jobs\Iface $controller, array $decorators, string $classprefix ) : \Aimeos\Controller\Jobs\Iface
	{
		foreach( $decorators as $name )
		{
			if( ctype_alnum( $name ) === false )
			{
				$classname = is_string( $name ) ? $classprefix . $name : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid class name "%1$s"', $classname ), 400 );
			}

			$classname = $classprefix . $name;

			if( class_exists( $classname ) === false ) {
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Class "%1$s" not found', $classname ), 404 );
			}

			$interface = \Aimeos\Controller\Jobs\Common\Decorator\Iface::class;
			$controller = new $classname( $controller, $context, $aimeos );

			if( !( $controller instanceof $interface ) )
			{
				$msg = sprintf( 'Class "%1$s" does not implement "%2$s"', $classname, $interface );
				throw new \Aimeos\Controller\Jobs\Exception( $msg, 400 );
			}
		}

		return $controller;
	}


	/**
	 * Creates a controller object.
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context instance with necessary objects
	 * @param \Aimeos\Bootstrap $aimeos \Aimeos\Bootstrap object
	 * @param string $classname Name of the controller class
	 * @param string $interface Name of the controller interface
	 * @param string $path Name of the domain
	 * @return \Aimeos\Controller\Jobs\Iface Controller object
	 */
	protected static function createController( \Aimeos\MShop\ContextIface $context, \Aimeos\Bootstrap $aimeos,
		string $classname, string $interface, string $path ) : \Aimeos\Controller\Jobs\Iface
	{
		if( isset( self::$objects[$classname] ) ) {
			return self::$objects[$classname];
		}

		if( class_exists( $classname ) === false ) {
			throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Class "%1$s" not found', $classname ), 404 );
		}

		$cntl = new $classname( $context, $aimeos );

		if( !( $cntl instanceof $interface ) )
		{
			$msg = sprintf( 'Class "%1$s" does not implement "%2$s"', $classname, $interface );
			throw new \Aimeos\Controller\Jobs\Exception( $msg, 400 );
		}

		return self::addControllerDecorators( $context, $aimeos, $cntl, $path );
	}


	/**
	 * Instantiates all found factories and stores the controller instances in the class variable.
	 *
	 * @param \DirectoryIterator $dir Iterator over the (sub-)directory which might contain a factory
	 * @param \Aimeos\MShop\ContextIface $context Context object required by controllers
	 * @param \Aimeos\Bootstrap $aimeos \Aimeos\Bootstrap object
	 * @param string $prefix Part of the class name between "\Aimeos\Controller\Jobs" and "Factory"
	 * @return \Aimeos\Controller\Jobs\Iface[] Associative list if prefixes as values and job controller instances as values
	 * @throws \Aimeos\Controller\Jobs\Exception If factory name is invalid or if the controller couldn't be instantiated
	 */
	protected static function createControllers( \DirectoryIterator $dir, \Aimeos\MShop\ContextIface $context,
		\Aimeos\Bootstrap $aimeos, string $prefix = '' ) : array
	{
		$list = [];

		foreach( $dir as $entry )
		{
			if( $entry->getType() === 'dir' && $entry->isDot() === false
				&& !in_array( $entry->getBaseName(), ['Common', 'Decorator'] )
			) {
				$name = strtolower( $entry->getBaseName() );
				$it = new \DirectoryIterator( $entry->getPathName() );
				$pref = ( $prefix !== '' ? $prefix . '/' : '' ) . $name;
				$subList = self::createControllers( $it, $context, $aimeos, $pref );

				$list = array_merge( $list, $subList );
			}
			else if( $prefix !== '' && $entry->getType() === 'file'
				&& !in_array( $entry->getBaseName( '.php' ), ['Base'] ) )
			{
				$list[$prefix] = self::create( $context, $aimeos, $prefix );
			}
		}

		return $list;
	}
}
