<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Jobs\Product\Export;


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

		$this->object = new \Aimeos\Controller\Jobs\Product\Export\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		$this->object = null;
	}


	public function testGetName()
	{
		$this->assertEquals( 'Product export', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Exports all available products';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->context->getConfig()->set( 'controller/jobs/product/export/filename', 'aimeos-products-%1$d.xml' );

		$this->object->run();

		$ds = DIRECTORY_SEPARATOR;
		$this->assertFileExists( 'tmp' . $ds . 'aimeos-products-1.xml' );
		$this->assertFileExists( 'tmp' . $ds . 'aimeos-products-2.xml' );

		$file1 = file_get_contents( 'tmp' . $ds . 'aimeos-products-1.xml' );
		$file2 = file_get_contents( 'tmp' . $ds . 'aimeos-products-2.xml' );

		unlink( 'tmp' . $ds . 'aimeos-products-1.xml' );
		unlink( 'tmp' . $ds . 'aimeos-products-2.xml' );

		$this->assertStringContainsString( 'CNE', $file2 );
		$this->assertStringContainsString( 'U:BUNDLE', $file2 );
	}
}
