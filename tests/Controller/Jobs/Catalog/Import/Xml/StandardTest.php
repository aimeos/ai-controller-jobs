<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2023
 */


namespace Aimeos\Controller\Jobs\Catalog\Import\Xml;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	public static function setUpBeforeClass() : void
	{
		$context = \TestHelper::context();

		$fs = $context->fs( 'fs-import' );
		$fs->has( 'catalog' ) ?: $fs->mkdir( 'catalog' );
		$fs->writef( 'catalog/catalog_1.xml', __DIR__ . '/_testfiles/catalog_1.xml' );
		$fs->writef( 'catalog/catalog_2.xml', __DIR__ . '/_testfiles/catalog_2.xml' );

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

		$this->object = new \Aimeos\Controller\Jobs\Catalog\Import\Xml\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Catalog import XML', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Imports new and updates existing categories from XML files';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->object->run();

		$manager = \Aimeos\MShop::create( $this->context, 'catalog' );
		$tree = $manager->getTree( $manager->find( 'unittest-xml' )->getId(), ['media', 'product', 'text'] );
		$manager->delete( $tree->getId() );

		$this->assertEquals( 'Test catalog', $tree->getLabel() );
		$this->assertEquals( ['css' => 'new'], $tree->getConfig() );
		$this->assertEquals( 2, count( $tree->getChildren() ) );
		$this->assertEquals( 1, count( $tree->getRefItems( 'text', null, null, false ) ) );
		$this->assertEquals( 'Test sub-category 3', $tree->getChild( 0 )->getLabel() );
		$this->assertEquals( 2, count( $tree->getChild( 0 )->getRefItems( 'product' ) ) );
		$this->assertEquals( 'Test sub-category 3-1', $tree->getChild( 0 )->getChild( 0 )->getLabel() );
	}
}
