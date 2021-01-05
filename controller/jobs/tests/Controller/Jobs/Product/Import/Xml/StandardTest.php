<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Jobs\Product\Import\Xml;


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

		$config = $this->context->getConfig();
		$config->set( 'controller/jobs/product/import/xml/location', __DIR__ . '/_testfiles' );

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

		$domains = ['attribute', 'media', 'media/property', 'price', 'product', 'product/property', 'text'];
		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$item = $manager->find( 'unittest-xml', $domains );
		$manager->delete( $item->getId() );

		$this->assertEquals( 'default', $item->getType() );
		$this->assertEquals( 'Test product', $item->getLabel() );
		$this->assertEquals( ['css' => 'new'], $item->getConfig() );
		$this->assertEquals( 1, count( $item->getRefItems( 'attribute' ) ) );
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
