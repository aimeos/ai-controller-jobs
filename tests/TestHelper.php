<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2022
 */


class TestHelper
{
	private static $aimeos;
	private static $context;


	public static function bootstrap()
	{
		self::getAimeos();
		\Aimeos\MShop::cache( false );
	}


	public static function context( $site = 'unittest' )
	{
		if( !isset( self::$context[$site] ) ) {
			self::$context[$site] = self::createContext( $site );
		}

		return clone self::$context[$site];
	}


	public static function getAimeos()
	{
		if( !isset( self::$aimeos ) )
		{
			require_once 'Bootstrap.php';
			spl_autoload_register( 'Aimeos\\Bootstrap::autoload' );

			self::$aimeos = new \Aimeos\Bootstrap();
		}

		return self::$aimeos;
	}


	/**
	 * @param string $site
	 */
	private static function createContext( $site )
	{
		$ctx = new \Aimeos\MShop\Context();
		$aimeos = self::getAimeos();


		$paths = $aimeos->getConfigPaths();
		$paths[] = __DIR__ . DIRECTORY_SEPARATOR . 'config';
		$file = __DIR__ . DIRECTORY_SEPARATOR . 'confdoc.ser';

		$conf = new \Aimeos\Base\Config\PHPArray( [], $paths );
		$conf = new \Aimeos\Base\Config\Decorator\Memory( $conf );
		$conf = new \Aimeos\Base\Config\Decorator\Documentor( $conf, $file );
		$ctx->setConfig( $conf );


		$dbm = \Aimeos\Base\DB\Factory::create( $conf, 'PDO' );
		$ctx->setDatabaseManager( $dbm );


		$fs = new \Aimeos\Base\Filesystem\Manager\Standard( $conf->get( 'resource' ) );
		$ctx->setFilesystemManager( $fs );


		$logger = new \Aimeos\Base\Logger\File( 'unittest.log', \Aimeos\Base\Logger\Iface::DEBUG );
		$ctx->setLogger( $logger );


		$cache = new \Aimeos\Base\Cache\None();
		$ctx->setCache( $cache );


		$i18n = new \Aimeos\Base\Translation\None( 'de' );
		$ctx->setI18n( array( 'de' => $i18n ) );


		$mail = new \Aimeos\Base\Mail\None();
		$ctx->setMail( $mail );


		$process = new \Aimeos\Base\Process\None();
		$ctx->setProcess( $process );


		$localeManager = \Aimeos\MShop::create( $ctx, 'locale' );
		$locale = $localeManager->bootstrap( $site, '', '', false );
		$ctx->setLocale( $locale );


		$view = self::createView( $conf );
		$ctx->setView( $view );


		return $ctx->setEditor( 'ai-controller-jobs' );
	}


	protected static function createView( \Aimeos\Base\Config\Iface $config )
	{
		$view = new \Aimeos\Base\View\Standard( self::getAimeos()->getTemplatePaths( 'controller/jobs/templates' ) );

		$trans = new \Aimeos\Base\Translation\None( 'de_DE' );
		$helper = new \Aimeos\Base\View\Helper\Translate\Standard( $view, $trans );
		$view->addHelper( 'translate', $helper );

		$helper = new \Aimeos\Base\View\Helper\Url\Standard( $view, 'http://baseurl' );
		$view->addHelper( 'url', $helper );

		$helper = new \Aimeos\Base\View\Helper\Number\Standard( $view, '.', '' );
		$view->addHelper( 'number', $helper );

		$helper = new \Aimeos\Base\View\Helper\Date\Standard( $view, 'Y-m-d' );
		$view->addHelper( 'date', $helper );

		$paths = ['version', 'controller/jobs', 'client/html', 'resource/fs/baseurl', 'resource/fs-media/baseurl'];
		$config = new \Aimeos\Base\Config\Decorator\Protect( $config, $paths );
		$helper = new \Aimeos\Base\View\Helper\Config\Standard( $view, $config );
		$view->addHelper( 'config', $helper );

		return $view;
	}
}
