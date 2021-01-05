<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Price;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $endpoint;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelperCntl::getContext();
		$this->endpoint = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Done( $this->context, [] );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
	}


	public function testProcess()
	{
		$mapping = array(
			0 => 'price.type',
			1 => 'price.label',
			2 => 'price.currencyid',
			3 => 'price.quantity',
			4 => 'price.value',
			5 => 'price.costs',
			6 => 'price.rebate',
			7 => 'price.taxrate',
			8 => 'price.status',
		);

		$data = array(
			0 => 'default',
			1 => 'EUR 1.00',
			2 => 'EUR',
			3 => 5,
			4 => '1.00',
			5 => '0.20',
			6 => '0.10',
			7 => '20.00',
			8 => 1,
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Price\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$listItems = $product->getListItems();
		$listItem = $listItems->first();

		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );
		$this->assertEquals( 1, count( $listItems ) );

		$this->assertEquals( 1, $listItem->getStatus() );
		$this->assertEquals( 0, $listItem->getPosition() );
		$this->assertEquals( 'default', $listItem->getType() );

		$refItem = $listItem->getRefItem();

		$this->assertEquals( 1, $refItem->getStatus() );
		$this->assertEquals( 'default', $refItem->getType() );
		$this->assertEquals( 'EUR 1.00', $refItem->getLabel() );
		$this->assertEquals( 5, $refItem->getQuantity() );
		$this->assertEquals( '1.00', $refItem->getValue() );
		$this->assertEquals( '0.20', $refItem->getCosts() );
		$this->assertEquals( '0.10', $refItem->getRebate() );
		$this->assertEquals( '20.00', $refItem->getTaxrate() );
		$this->assertEquals( 1, $refItem->getStatus() );
	}


	public function testProcessMultiple()
	{
		$mapping = array(
			0 => 'price.currencyid',
			1 => 'price.value',
			2 => 'price.currencyid',
			3 => 'price.value',
			4 => 'price.currencyid',
			5 => 'price.value',
			6 => 'price.currencyid',
			7 => 'price.value',
		);

		$data = array(
			0 => 'EUR',
			1 => '1.00',
			2 => 'EUR',
			3 => '2.00',
			4 => 'EUR',
			5 => '3.00',
			6 => 'EUR',
			7 => '4.00',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Price\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$pos = 0;
		$listItems = $product->getListItems();

		$this->assertEquals( 4, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $data[$pos++], $listItem->getRefItem()->getCurrencyId() );
			$this->assertEquals( $data[$pos++], $listItem->getRefItem()->getValue() );
		}
	}


	public function testProcessUpdate()
	{
		$mapping = array(
			0 => 'price.currencyid',
			1 => 'price.value',
		);

		$data = array(
			0 => 'EUR',
			1 => '1.00',
		);

		$dataUpdate = array(
			0 => 'EUR',
			1 => '2.00',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Price\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );
		$object->process( $product, $dataUpdate );


		$listItems = $product->getListItems();
		$listItem = $listItems->first();

		$this->assertEquals( 1, count( $listItems ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );

		$this->assertEquals( '2.00', $listItem->getRefItem()->getValue() );
	}


	public function testProcessDelete()
	{
		$mapping = array(
			0 => 'price.currencyid',
			1 => 'price.value',
		);

		$data = array(
			0 => 'EUR',
			1 => '1.00',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Price\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Price\Standard( $this->context, [], $this->endpoint );
		$object->process( $product, [] );


		$listItems = $product->getListItems();

		$this->assertEquals( 0, count( $listItems ) );
	}


	public function testProcessEmpty()
	{
		$mapping = array(
			0 => 'price.currencyid',
			1 => 'price.value',
			2 => 'price.currencyid',
			3 => 'price.value',
		);

		$data = array(
			0 => 'EUR',
			1 => '1.00',
			2 => 'EUR',
			3 => '',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Price\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$listItems = $product->getListItems();

		$this->assertEquals( 1, count( $listItems ) );
	}


	public function testProcessListtypes()
	{
		$mapping = array(
			0 => 'price.currencyid',
			1 => 'price.value',
			2 => 'product.lists.type',
			3 => 'price.currencyid',
			4 => 'price.value',
			5 => 'product.lists.type',
		);

		$data = array(
			0 => 'EUR',
			1 => '1.00',
			2 => 'test',
			3 => 'EUR',
			4 => '2.00',
			5 => 'default',
		);

		$this->context->getConfig()->set( 'controller/common/product/import/csv/processor/price/listtypes', array( 'default' ) );

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Price\Standard( $this->context, $mapping, $this->endpoint );

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
