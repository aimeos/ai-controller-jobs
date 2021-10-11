<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Supplier;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private static $product;
	private $context;
	private $endpoint;


	public static function setUpBeforeClass() : void
	{
		$manager = \Aimeos\MShop\Product\Manager\Factory::create( \TestHelperCntl::getContext() );
		$item = $manager->create()->setCode( 'job_csv_prod' )->setType( 'default' );

		self::$product = $manager->save( $item );
	}


	public static function tearDownAfterClass() : void
	{
		\Aimeos\MShop\Product\Manager\Factory::create( \TestHelperCntl::getContext() )->delete( self::$product );
	}


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
			0 => 'supplier.lists.type',
			1 => 'supplier.code',
			2 => 'supplier.lists.type',
			3 => 'supplier.code',
		);

		$data = array(
			0 => 'default',
			1 => 'job_csv_test',
			2 => 'default',
			3 => 'job_csv_test2',
		);

		$suppliersCodes = ['job_csv_test', 'job_csv_test2'];

		foreach( $suppliersCodes as $code ) {
			$this->create( $code );
		}

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( self::$product, $data );

		foreach( $suppliersCodes as $code )
		{
			$supplier = $this->get( $code );
			$this->delete( $supplier );

			$actualProductItem = $supplier->getRefItems()->first()[self::$product->getId()];

			$this->assertEquals( self::$product->getId(), $actualProductItem->getId() );
		}
	}


	public function testProcessMultiple()
	{
		$mapping = array(
			0 => 'supplier.lists.type',
			1 => 'supplier.code',
			2 => 'supplier.lists.type',
			3 => 'supplier.code',
		);

		$data = array(
			0 => 'default',
			1 => "job_csv_test\njob_csv_test2",
		);

		$suppliersCodes = ['job_csv_test', 'job_csv_test2'];

		foreach( $suppliersCodes as $code ) {
			$this->create( $code );
		}

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( self::$product, $data );

		foreach( $suppliersCodes as $code )
		{
			$supplier = $this->get( $code );
			$this->delete( $supplier );

			$actualProductItem = $supplier->getRefItems()->first()[self::$product->getId()];

			$this->assertEquals( self::$product->getId(), $actualProductItem->getId() );
		}
	}


	public function testProcessUpdate()
	{
		$mapping = array(
			0 => 'supplier.lists.type',
			1 => 'supplier.code',
		);

		$data = array(
			0 => 'default',
			1 => 'job_csv_test',
		);

		$dataUpdate = array(
			0 => 'promotion',
			1 => 'job_csv_test',
		);

		$listType = $this->createListType( 'promotion' );
		$this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( self::$product, $data );
		$object->process( self::$product, $dataUpdate );

		$supplier = $this->get( 'job_csv_test' );
		$this->delete( $supplier );
		$this->deleteListType( $listType );


		$listItems = $supplier->getListItems();
		$listItem = $listItems->first();

		$this->assertEquals( 1, count( $listItems ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );

		$this->assertEquals( 'job_csv_prod', $listItem->getRefItem()->getCode() );
	}


	public function testProcessDelete()
	{
		$mapping = array(
			0 => 'supplier.lists.type',
			1 => 'supplier.code',
		);

		$data = array(
			0 => 'default',
			1 => 'job_csv_test',
		);

		$this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( self::$product, $data );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, [], $this->endpoint );
		$object->process( self::$product, [] );

		$supplier = $this->get( 'job_csv_test' );
		$this->delete( $supplier );


		$listItems = $supplier->getListItems();

		$this->assertEquals( 0, count( $listItems ) );
	}


	public function testProcessEmpty()
	{
		$mapping = array(
			0 => 'supplier.lists.type',
			1 => 'supplier.code',
			2 => 'supplier.lists.type',
			3 => 'supplier.code',
		);

		$data = array(
			0 => '',
			1 => '',
			2 => 'default',
			3 => 'job_csv_test',
		);

		$this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( self::$product, $data );

		$supplier = $this->get( 'job_csv_test' );
		$this->delete( $supplier );


		$listItems = $supplier->getListItems();

		$this->assertEquals( 1, count( $listItems ) );
	}


	public function testProcessListtypes()
	{
		$mapping = array(
			0 => 'supplier.lists.type',
			1 => 'supplier.code',
			2 => 'supplier.lists.type',
			3 => 'supplier.code',
		);

		$data = array(
			0 => 'promotion',
			1 => 'job_csv_test',
			2 => 'default',
			3 => 'job_csv_test',
		);

		$this->context->getConfig()->set( 'controller/common/product/import/csv/processor/supplier/listtypes', array( 'default' ) );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Supplier\Standard( $this->context, $mapping, $this->endpoint );

		$this->expectException( '\Aimeos\Controller\Common\Exception' );
		$object->process( self::$product, $data );
	}


	/**
	 * @param string $code
	 */
	protected function create( $code )
	{
		$manager = \Aimeos\MShop\Supplier\Manager\Factory::create( $this->context );
		return $manager->save( $manager->create()->setCode( $code ) );
	}


	/**
	 * @param string $code
	 */
	protected function createListType( $code )
	{
		$manager = \Aimeos\MShop\Supplier\Manager\Factory::create( $this->context );
		$supplierListManager = $manager->getSubManager( 'lists' );
		$supplierListTypeManager = $supplierListManager->getSubmanager( 'type' );

		$item = $supplierListTypeManager->create();
		$item->setCode( $code );
		$item->setDomain( 'product' );
		$item->setLabel( $code );
		$item->setStatus( 1 );

		return $supplierListTypeManager->save( $item );
	}


	protected function delete( \Aimeos\MShop\Supplier\Item\Iface $item )
	{
		\Aimeos\MShop\Supplier\Manager\Factory::create( $this->context )->delete( $item->getId() );
	}


	protected function deleteListType( \Aimeos\MShop\Common\Item\Iface $item )
	{
		$manager = \Aimeos\MShop\Supplier\Manager\Factory::create( $this->context );
		$listManager = $manager->getSubManager( 'lists' );
		$listTypeManager = $listManager->getSubmanager( 'type' );

		$listTypeManager->delete( $item->getId() );
	}


	/**
	 * @param string $code
	 */
	protected function get( $code )
	{
		return \Aimeos\MShop\Supplier\Manager\Factory::create( $this->context )->find( $code, ['product'] );
	}
}
