<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 */


namespace Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Supplier;


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
			1 => 'supplier.code',
			2 => 'product.lists.type',
			3 => 'supplier.code',
		);

		$data = array(
			0 => 'default',
			1 => 'unitSupplier001',
			2 => 'promotion',
			3 => 'unitSupplier002',
		);

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $this->product, $data );

		$pos = 0;
		$types = ['default', 'promotion'];
		$listItems = $this->product->getListItems();

		$this->assertEquals( 2, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( 1, $listItem->getStatus() );
			$this->assertEquals( 'supplier', $listItem->getDomain() );
			$this->assertEquals( $types[$pos], $listItem->getType() );
			$this->assertGreaterThan( 0, $listItem->getRefId() );
			$pos++;
		}
	}


	public function testProcessMultiple()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'supplier.code',
			2 => 'product.lists.type',
			3 => 'supplier.code',
		);

		$data = array(
			0 => 'default',
			1 => "unitSupplier001\nunitSupplier002",
			2 => 'promotion',
			3 => "unitSupplier001\nunitSupplier002",
		);

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $this->product, $data );


		$pos = 0;
		$listItems = $this->product->getListItems();
		$types = ['default', 'default', 'promotion', 'promotion'];

		$this->assertEquals( 4, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( 1, $listItem->getStatus() );
			$this->assertEquals( 'supplier', $listItem->getDomain() );
			$this->assertEquals( $types[$pos], $listItem->getType() );
			$this->assertGreaterThan( 0, $listItem->getRefId() );
			$pos++;
		}
	}


	public function testProcessUpdate()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'supplier.code',
		);

		$data = array(
			0 => 'default',
			1 => 'unitSupplier001',
		);

		$dataUpdate = array(
			0 => 'promotion',
			1 => 'unitSupplier001',
		);

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, $mapping, $this->endpoint );
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
			1 => 'supplier.code',
		);

		$data = array(
			0 => 'default',
			1 => 'unitSupplier001',
		);

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $this->product, $data );

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, [], $this->endpoint );
		$object->process( $this->product, [] );

		$listItems = $this->product->getListItems();

		$this->assertEquals( 0, count( $listItems ) );
	}


	public function testProcessEmpty()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'supplier.code',
			2 => 'product.lists.type',
			3 => 'supplier.code',
		);

		$data = array(
			0 => '',
			1 => '',
			2 => 'default',
			3 => 'unitSupplier001',
		);

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $this->product, $data );

		$listItems = $this->product->getListItems();

		$this->assertEquals( 1, count( $listItems ) );
	}


	public function testProcessListtypes()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'supplier.code',
			2 => 'product.lists.type',
			3 => 'supplier.code',
		);

		$data = array(
			0 => 'promotion',
			1 => 'unitSupplier001',
			2 => 'default',
			3 => 'unitSupplier001',
		);

		$this->context->config()->set( 'controller/jobs/product/import/csv/processor/supplier/listtypes', array( 'default' ) );

		$object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, $mapping, $this->endpoint );

		$this->expectException( '\Aimeos\Controller\Jobs\Exception' );
		$object->process( $this->product, $data );
	}


	/**
	 * @param string $code
	 */
	protected function create( $code )
	{
		$manager = \Aimeos\MShop::create( $this->context, 'supplier' );
		return $manager->save( $manager->create()->setCode( $code ) );
	}
}
