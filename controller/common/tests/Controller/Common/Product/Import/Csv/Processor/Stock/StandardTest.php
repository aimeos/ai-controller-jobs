<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2018
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Stock;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $endpoint;


	protected function setUp()
	{
		\Aimeos\MShop\Factory::setCache( true );

		$this->context = \TestHelperCntl::getContext();
		$this->endpoint = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Done( $this->context, [] );
	}


	protected function tearDown()
	{
		\Aimeos\MShop\Factory::setCache( false );
		\Aimeos\MShop\Factory::clear();
	}


	public function testProcess()
	{
		$mapping = array(
			0 => 'stock.stocklevel',
			1 => 'stock.dateback',
		);

		$data = array(
			0 => '100',
			1 => '2000-01-01 00:00:00',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Stock\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );

		$items = $this->getStockItems( $product->getCode() );
		$this->delete( $product );


		$this->assertEquals( 1, count( $items ) );

		foreach( $items as $item )
		{
			$this->assertEquals( 100, $item->getStocklevel() );
			$this->assertEquals( '2000-01-01 00:00:00', $item->getDateBack() );
		}
	}


	public function testProcessMultiple()
	{
		$mapping = array(
			0 => 'stock.type',
			1 => 'stock.stocklevel',
			2 => 'stock.type',
			3 => 'stock.stocklevel',
		);

		$data = array(
			0 => 'unit_type1',
			1 => '200',
			2 => 'unit_type2',
			3 => '200',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Stock\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );

		$items = $this->getStockItems( $product->getCode() );
		$this->delete( $product );


		$this->assertEquals( 2, count( $items ) );

		foreach( $items as $item ) {
			$this->assertEquals( 200, $item->getStocklevel() );
		}
	}


	public function testProcessUpdate()
	{
		$mapping = array(
			0 => 'stock.stocklevel',
		);

		$data = array(
			0 => '10',
		);

		$dataUpdate = array(
			0 => '20',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Stock\Standard( $this->context, $mapping, $this->endpoint );

		$object->process( $product, $data );
		$object->process( $product, $dataUpdate );

		$items = $this->getStockItems( $product->getCode() );
		$this->delete( $product );


		$item = reset( $items );

		$this->assertEquals( 1, count( $items ) );
		$this->assertEquals( 20, $item->getStocklevel() );
	}


	public function testProcessDelete()
	{
		$mapping = array(
			0 => 'stock.stocklevel',
		);

		$data = array(
			0 => 50,
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Stock\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Stock\Standard( $this->context, [], $this->endpoint );
		$object->process( $product, [] );

		$items = $this->getStockItems( $product->getCode() );
		$this->delete( $product );


		$this->assertEquals( 0, count( $items ) );
	}


	public function testProcessEmpty()
	{
		$mapping = array(
			0 => 'stock.type',
			1 => 'stock.stocklevel',
			2 => 'stock.dateback',
		);

		$data = array(
			0 => 'unit_type1',
			1 => '',
			2 => '',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Stock\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );

		$items = $this->getStockItems( $product->getCode() );
		$this->delete( $product );

		$this->assertEquals( 1, count( $items ) );

		foreach( $items as $item )
		{
			$this->assertEquals( null, $item->getStocklevel() );
			$this->assertEquals( null, $item->getDateBack() );
		}
	}


	/**
	 * @param string $code
	 */
	protected function create( $code )
	{
		$manager = \Aimeos\MShop\Product\Manager\Factory::createManager( $this->context );
		$typeManager = $manager->getSubManager( 'type' );

		$typeSearch = $typeManager->createSearch();
		$typeSearch->setConditions( $typeSearch->compare( '==', 'product.type.code', 'default' ) );
		$typeResult = $typeManager->searchItems( $typeSearch );

		if( ( $typeItem = reset( $typeResult ) ) === false ) {
			throw new \RuntimeException( 'No product type "default" found' );
		}

		$item = $manager->createItem();
		$item->setTypeid( $typeItem->getId() );
		$item->setCode( $code );

		return $manager->saveItem( $item );
	}


	protected function delete( \Aimeos\MShop\Product\Item\Iface $product )
	{
		$manager = \Aimeos\MShop\Product\Manager\Factory::createManager( $this->context );
		$manager->deleteItem( $product->getId() );
	}


	protected function getStockItems( $code )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'stock' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'stock.productcode', $code ) );

		return $manager->searchItems( $search );
	}
}