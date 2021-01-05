<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Product;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $endpoint;
	private $products;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelperCntl::getContext();
		$this->endpoint = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Done( $this->context, [] );

		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'product.code', ['CNC', 'CNE'] ) );

		$this->products = [];
		foreach( $manager->search( $search ) as $id => $item ) {
			$this->products[$item->getCode()] = $id;
		}
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
	}


	public function testProcess()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'product.code',
			2 => 'product.lists.type',
			3 => 'product.code',
		);

		$data = array(
			0 => 'default',
			1 => 'CNC',
			2 => 'suggestion',
			3 => 'CNE',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Product\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$listItems1 = $product->getListItems( 'product', 'default' );
		$listItems2 = $product->getListItems( 'product', 'suggestion' );

		$this->assertEquals( 1, count( $listItems1 ) );
		$this->assertEquals( 1, count( $listItems2 ) );

		$this->assertEquals( 1, $listItems1->first()->getStatus() );
		$this->assertEquals( 1, $listItems2->first()->getStatus() );

		$this->assertEquals( $this->products['CNC'], $listItems1->first()->getRefId() );
		$this->assertEquals( $this->products['CNE'], $listItems2->first()->getRefId() );
	}


	public function testProcessMultiple()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'product.code',
		);

		$data = array(
			0 => 'default',
			1 => "CNC\nCNE",
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Product\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$pos = 0;
		$listItems = $product->getListItems();
		$prodIds = array( $this->products['CNC'], $this->products['CNE'] );

		$this->assertEquals( 2, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( 1, $listItem->getStatus() );
			$this->assertEquals( 'product', $listItem->getDomain() );
			$this->assertEquals( 'default', $listItem->getType() );
			$this->assertEquals( $prodIds[$pos], $listItem->getRefId() );
			$pos++;
		}
	}


	public function testProcessUpdate()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'product.code',
		);

		$data = array(
			0 => 'default',
			1 => 'CNC',
		);

		$dataUpdate = array(
			0 => 'default',
			1 => 'CNE',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Product\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );
		$object->process( $product, $dataUpdate );


		$listItems = $product->getListItems();
		$listItem = $listItems->first();

		$this->assertEquals( 1, count( $listItems ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );

		$this->assertEquals( $this->products['CNE'], $listItem->getRefId() );
	}


	public function testProcessDelete()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'product.code',
		);

		$data = array(
			0 => 'default',
			1 => 'CNC',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Product\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Product\Standard( $this->context, [], $this->endpoint );
		$object->process( $product, [] );


		$this->assertEquals( 0, count( $product->getListItems() ) );
	}


	public function testProcessEmpty()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'product.code',
			2 => 'product.lists.type',
			3 => 'product.code',
		);

		$data = array(
			0 => '',
			1 => '',
			2 => 'default',
			3 => 'CNE',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Product\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$listItems = $product->getListItems();

		$this->assertEquals( 1, count( $listItems ) );
	}


	public function testProcessListtypes()
	{
		$mapping = array(
			0 => 'product.lists.type',
			1 => 'product.code',
			2 => 'product.lists.type',
			3 => 'product.code',
		);

		$data = array(
			0 => 'bought-together',
			1 => 'CNC',
			2 => 'default',
			3 => 'CNE',
		);

		$this->context->getConfig()->set( 'controller/common/product/import/csv/processor/product/listtypes', array( 'default' ) );

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Product\Standard( $this->context, $mapping, $this->endpoint );

		$this->expectException( '\Aimeos\Controller\Common\Exception' );
		$object->process( $product, $data );
	}


	/**
	 * @param string $code
	 */
	protected function create( $code )
	{
		$manager = \Aimeos\MShop\Product\Manager\Factory::create( $this->context );
		return $manager->create()->setCode( $code );
	}
}
