<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2018
 */


namespace Aimeos\Controller\Jobs\Product\Export\Sitemap;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	protected function setUp()
	{
		\Aimeos\MShop\Factory::setCache( true );

		$this->context = \TestHelperJobs::getContext();
		$this->aimeos = \TestHelperJobs::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Product\Export\Sitemap\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown()
	{
		\Aimeos\MShop\Factory::setCache( false );
		\Aimeos\MShop\Factory::clear();

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
		$this->context->getConfig()->set( 'controller/jobs/product/export/sitemap/max-items', 5 );

		$this->object->run();

		$ds = DIRECTORY_SEPARATOR;
		$this->assertFileExists( 'tmp' . $ds . 'aimeos-sitemap-1.xml.gz' );
		$this->assertFileExists( 'tmp' . $ds . 'aimeos-sitemap-2.xml.gz' );
		$this->assertFileExists( 'tmp' . $ds . 'aimeos-sitemap-index.xml.gz' );

		$file1 = gzread( gzopen( 'tmp' . $ds . 'aimeos-sitemap-1.xml.gz', 'rb' ), 0x1000 );
		$file2 = gzread( gzopen( 'tmp' . $ds . 'aimeos-sitemap-2.xml.gz', 'rb' ), 0x1000 );
		$index = gzread( gzopen( 'tmp' . $ds . 'aimeos-sitemap-index.xml.gz', 'rb' ), 0x1000 );

		unlink( 'tmp' . $ds . 'aimeos-sitemap-1.xml.gz' );
		unlink( 'tmp' . $ds . 'aimeos-sitemap-2.xml.gz' );
		unlink( 'tmp' . $ds . 'aimeos-sitemap-index.xml.gz' );

		$this->assertContains( 'Cafe_Noire_Expresso', $file2 );
		$this->assertContains( 'Unittest%3A_Bundle', $file2 );

		$this->assertContains( 'aimeos-sitemap-1.xml.gz', $index );
		$this->assertContains( 'aimeos-sitemap-2.xml.gz', $index );
	}
}