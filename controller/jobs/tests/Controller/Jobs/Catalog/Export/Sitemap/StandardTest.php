<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Jobs\Catalog\Export\Sitemap;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelperJobs::getContext();
		$this->aimeos = \TestHelperJobs::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Catalog\Export\Sitemap\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		$this->object = null;
	}


	public function testGetName()
	{
		$this->assertEquals( 'Catalog site map', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Creates a catalog site map for search engines';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->context->getConfig()->set( 'controller/jobs/catalog/export/sitemap/max-items', 5 );
		$this->context->getConfig()->set( 'controller/jobs/catalog/export/sitemap/baseurl', 'https://www.yourshop.com/sitemaps/' );

		$this->object->run();

		$ds = DIRECTORY_SEPARATOR;
		$this->assertFileExists( 'tmp' . $ds . 'aimeos-catalog-sitemap-1.xml.gz' );
		$this->assertFileExists( 'tmp' . $ds . 'aimeos-catalog-sitemap-2.xml.gz' );
		$this->assertFileExists( 'tmp' . $ds . 'aimeos-catalog-sitemap-index.xml.gz' );

		$file1 = gzread( gzopen( 'tmp' . $ds . 'aimeos-catalog-sitemap-1.xml.gz', 'rb' ), 0x1000 );
		$file2 = gzread( gzopen( 'tmp' . $ds . 'aimeos-catalog-sitemap-2.xml.gz', 'rb' ), 0x1000 );
		$index = gzread( gzopen( 'tmp' . $ds . 'aimeos-catalog-sitemap-index.xml.gz', 'rb' ), 0x1000 );

		unlink( 'tmp' . $ds . 'aimeos-catalog-sitemap-1.xml.gz' );
		unlink( 'tmp' . $ds . 'aimeos-catalog-sitemap-2.xml.gz' );
		unlink( 'tmp' . $ds . 'aimeos-catalog-sitemap-index.xml.gz' );

		$this->assertStringContainsString( 'Kaffee', $file1 );
		$this->assertStringContainsString( 'Misc', $file1 );
		$this->assertStringContainsString( 'Groups', $file2 );

		$this->assertStringContainsString( 'https://www.yourshop.com/sitemaps/aimeos-catalog-sitemap-1.xml.gz', $index );
		$this->assertStringContainsString( 'https://www.yourshop.com/sitemaps/aimeos-catalog-sitemap-2.xml.gz', $index );
	}

	public function testRunEmptyLocation()
	{
		$this->context->getConfig()->set( 'controller/jobs/catalog/export/sitemap/location', '' );

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );

		$this->object->run();
	}

	public function testRunNoLocation()
	{
		$this->context->getConfig()->set( 'controller/jobs/catalog/export/sitemap/location', null );

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );

		$this->object->run();
	}
}
