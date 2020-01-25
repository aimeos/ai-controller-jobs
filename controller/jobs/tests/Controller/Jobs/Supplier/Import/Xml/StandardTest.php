<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2018
 */


namespace Aimeos\Controller\Jobs\Supplier\Import\Xml;


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
		$config->set( 'controller/jobs/supplier/import/xml/location', __DIR__ . '/_testfiles' );

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
		$item = $manager->findItem( 'unittest-xml', ['media', 'text', 'product', 'supplier/address'] );
		$manager->deleteItem( $item->getId() );

		$this->assertEquals( 'Test supplier', $item->getLabel() );
		$this->assertEquals( 1, count( $item->getRefItems( 'product' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'media' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'text' ) ) );
		$this->assertEquals( 1, count( $item->getAddressItems() ) );
		$this->assertEquals( 'mrs', $item->getAddressItems()->first()->getSalutation() );
	}
}
