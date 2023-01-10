<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2023
 */


namespace Aimeos\Controller\Jobs\Supplier\Import\Xml;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	public static function setUpBeforeClass() : void
	{
		$context = \TestHelper::context();

		$fs = $context->fs( 'fs-import' );
		$fs->has( 'supplier' ) ?: $fs->mkdir( 'supplier' );
		$fs->writef( 'supplier/supplier_1.xml', __DIR__ . '/_testfiles/supplier_1.xml' );
		$fs->writef( 'supplier/supplier_2.xml', __DIR__ . '/_testfiles/supplier_2.xml' );

		$fs = $context->fs( 'fs-media' );
		$fs->has( 'path/to' ) ?: $fs->mkdir( 'path/to' );
		$fs->write( 'path/to/file2.jpg', 'test' );
		$fs->write( 'path/to/file.jpg', 'test' );

		$fs = $context->fs( 'fs-mimeicon' );
		$fs->write( 'unknown.png', 'icon' );
	}


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();
		$this->aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Supplier\Import\Xml\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Supplier import XML', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Imports new and updates existing suppliers from XML files';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->object->run();

		$manager = \Aimeos\MShop::create( $this->context, 'supplier' );
		$item = $manager->find( 'unittest-xml', ['media', 'text', 'product', 'supplier/address'] );
		$manager->delete( $item->getId() );

		$this->assertEquals( 'Test supplier', $item->getLabel() );
		$this->assertEquals( 1, count( $item->getRefItems( 'product' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'media' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'text' ) ) );
		$this->assertEquals( 1, count( $item->getAddressItems() ) );
		$this->assertEquals( 'ms', $item->getAddressItems()->first()->getSalutation() );
	}
}
