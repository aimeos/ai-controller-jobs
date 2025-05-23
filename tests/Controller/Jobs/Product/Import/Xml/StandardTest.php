<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 */


namespace Aimeos\Controller\Jobs\Product\Import\Xml;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	public static function setUpBeforeClass() : void
	{
		$context = \TestHelper::context();

		$fs = $context->fs( 'fs-import' );
		$fs->has( 'product/unittest' ) ?: $fs->mkdir( 'product/unittest' );
		$fs->writef( 'product/unittest/product_1.xml', __DIR__ . '/_testfiles/product_1.xml' );
		$fs->writef( 'product/unittest/product_2.xml', __DIR__ . '/_testfiles/product_2.xml' );

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

		$this->object = new \Aimeos\Controller\Jobs\Product\Import\Xml\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Product import XML', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Imports new and updates existing products from XML files';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->object->run();

		$domains = ['attribute', 'catalog', 'media', 'media/property', 'price', 'product', 'product/property', 'supplier', 'text'];
		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$item = $manager->find( 'unittest-xml', $domains );
		$manager->delete( $item->getId() );

		$this->assertEquals( 'default', $item->getType() );
		$this->assertEquals( 'Test product', $item->getLabel() );
		$this->assertEquals( ['css' => 'new'], $item->getConfig() );
		$this->assertEquals( 1, count( $item->getRefItems( 'attribute' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'catalog' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'product' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'media' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'price' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'text' ) ) );
		$this->assertEquals( 1, count( $item->getPropertyItems() ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'media' )->first()->getPropertyItems() ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'media' )->first()->getListItems( 'attribute' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'price' )->first()->getListItems( 'attribute' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'text' )->first()->getListItems( 'attribute' ) ) );
	}
}
