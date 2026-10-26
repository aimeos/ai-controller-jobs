<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2026
 */


namespace Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Catalog;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $endpoint;
	private $product;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();
		$this->product = \Aimeos\MShop::create( $this->context, 'product' )->create();
		$this->endpoint = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Done( $this->context, [] );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
	}


	public function testProcess()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'catalog.code',
			2 => 'product.lists.type',
			3 => 'catalog.code',
		);

		$data = array(
			0 => 'default',
			1 => 'cafe',
			2 => 'promotion',
			3 => 'cafe',
		);

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $this->product, $data );

		$pos = 0;
		$types = ['default', 'promotion'];
		$listItems = $this->product->getListItems();

		$this->assertEquals( 2, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( 1, $listItem->getStatus() );
			$this->assertEquals( 'catalog', $listItem->getDomain() );
			$this->assertEquals( $types[$pos], $listItem->getType() );
			$this->assertGreaterThan( 0, $listItem->getRefId() );
			$pos++;
		}
	}


	public function testProcessMultiple()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'catalog.code',
			2 => 'product.lists.type',
			3 => 'catalog.code',
		);

		$data = array(
			0 => 'default',
			1 => "cafe\ntea",
			2 => 'promotion',
			3 => "cafe\ntea",
		);

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $this->product, $data );


		$pos = 0;
		$listItems = $this->product->getListItems();
		$types = ['default', 'default', 'promotion', 'promotion'];

		$this->assertEquals( 4, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( 1, $listItem->getStatus() );
			$this->assertEquals( 'catalog', $listItem->getDomain() );
			$this->assertEquals( $types[$pos], $listItem->getType() );
			$this->assertGreaterThan( 0, $listItem->getRefId() );
			$pos++;
		}
	}


	public function testProcessPosition()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'catalog.code',
			2 => 'product.lists.position',
			3 => 'product.lists.type',
			4 => 'catalog.code',
		);

		$data = array(
			0 => 'default',
			1 => 'cafe',
			2 => '1500',
			3 => 'promotion',
			4 => 'cafe',
		);

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $this->product, $data );

		$listItems = $this->product->getListItems( 'catalog' );

		$this->assertEquals( 2, count( $listItems ) );
		$this->assertEquals( 1500, $listItems->find( fn( $item ) => $item->getType() === 'default' )->getPosition() );
		$this->assertEquals( 1, $listItems->find( fn( $item ) => $item->getType() === 'promotion' )->getPosition() );
	}


	public function testProcessUpdate()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'catalog.code',
		);

		$data = array(
			0 => 'default',
			1 => 'cafe',
		);

		$dataUpdate = array(
			0 => 'promotion',
			1 => 'cafe',
		);

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $this->product, $data );
		$object->process( $this->product, $dataUpdate );

		$listItems = $this->product->getListItems();
		$listItem = $listItems->first();

		$this->assertEquals( 1, count( $listItems ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );
		$this->assertGreaterThan( 0, $listItem->getRefId() );
	}


	public function testProcessDelete()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'catalog.code',
		);

		$data = array(
			0 => 'default',
			1 => 'cafe',
		);

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $this->product, $data );

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, [], $this->endpoint );
		$object->process( $this->product, [] );

		$listItems = $this->product->getListItems();

		$this->assertEquals( 0, count( $listItems ) );
	}


	public function testProcessEmpty()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'catalog.code',
			2 => 'product.lists.type',
			3 => 'catalog.code',
		);

		$data = array(
			0 => '',
			1 => '',
			2 => 'default',
			3 => 'cafe',
		);

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $this->product, $data );

		$listItems = $this->product->getListItems();

		$this->assertEquals( 1, count( $listItems ) );
	}


	public function testProcessListtypes()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'catalog.code',
			2 => 'product.lists.type',
			3 => 'catalog.code',
		);

		$data = array(
			0 => 'promotion',
			1 => 'cafe',
			2 => 'default',
			3 => 'cafe',
		);

		$this->context->config()->set( 'controller/jobs/product/import/csv/catalog/listtypes', array( 'default' ) );

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );

		$this->expectException( '\Aimeos\Controller\Jobs\Exception' );
		$object->process( $this->product, $data );
	}
}
