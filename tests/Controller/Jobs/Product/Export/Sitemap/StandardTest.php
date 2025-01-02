<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 */


namespace Aimeos\Controller\Jobs\Product\Export\Sitemap;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();
		$this->aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Product\Export\Sitemap\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		$this->object = null;
	}


	public function testGetName()
	{
		$this->assertEquals( 'Product site map', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Creates a product site map for search engines';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->context->config()->set( 'controller/jobs/product/export/sitemap/max-items', 5 );
		$this->context->config()->set( 'resource/fs/baseurl', 'https://www.yourshop.com/' );

		$this->object->run();

		$ds = DIRECTORY_SEPARATOR;
		$this->assertFileExists( 'tmp' . $ds . 'unittest-sitemap-1.xml' );
		$this->assertFileExists( 'tmp' . $ds . 'unittest-sitemap-2.xml' );
		$this->assertFileExists( 'tmp' . $ds . 'unittest-sitemap-index.xml' );

		$file1 = file_get_contents( 'tmp' . $ds . 'unittest-sitemap-1.xml' );
		$file2 = file_get_contents( 'tmp' . $ds . 'unittest-sitemap-2.xml' );
		$index = file_get_contents( 'tmp' . $ds . 'unittest-sitemap-index.xml' );

		unlink( 'tmp' . $ds . 'unittest-sitemap-1.xml' );
		unlink( 'tmp' . $ds . 'unittest-sitemap-2.xml' );
		unlink( 'tmp' . $ds . 'unittest-sitemap-index.xml' );

		$this->assertStringContainsString( 'cafe_noire_expresso', $file2 );
		$this->assertStringContainsString( 'unittest-bundle', $file2 );

		$this->assertStringContainsString( 'https://www.yourshop.com/unittest-sitemap-1.xml', $index );
		$this->assertStringContainsString( 'https://www.yourshop.com/unittest-sitemap-2.xml', $index );
	}


	public function testRunEmptyBaseurl()
	{
		$this->context->config()->set( 'resource/fs/baseurl', '' );

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->run();
	}
}
