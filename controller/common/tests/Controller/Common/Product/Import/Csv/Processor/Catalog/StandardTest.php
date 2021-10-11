<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Catalog;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private static $product;
	private $context;
	private $endpoint;


	public static function setUpBeforeClass() : void
	{
		$manager = \Aimeos\MShop\Product\Manager\Factory::create( \TestHelperCntl::getContext() );

		$item = $manager->create();
		$item->setCode( 'job_csv_prod' );
		$item->setType( 'default' );
		$item->setStatus( 1 );

		self::$product = $manager->save( $item );
	}


	public static function tearDownAfterClass() : void
	{
		$manager = \Aimeos\MShop\Product\Manager\Factory::create( \TestHelperCntl::getContext() );
		$manager->delete( self::$product->getId() );
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
			0 => 'catalog.lists.type',
			1 => 'catalog.code',
			2 => 'catalog.lists.type',
			3 => 'catalog.code',
		);

		$data = array(
			0 => 'default',
			1 => 'job_csv_test',
			2 => 'promotion',
			3 => 'job_csv_test',
		);

		$catItem = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( self::$product, $data );

		$category = $this->get( 'job_csv_test' );
		$this->delete( $catItem );


		$pos = 0;
		$listItems = $category->getListItems();
		$expected = array(
			array( 'default', 'job_csv_prod' ),
			array( 'promotion', 'job_csv_prod' ),
		);

		$this->assertEquals( 2, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( 1, $listItem->getStatus() );
			$this->assertEquals( 'product', $listItem->getDomain() );
			$this->assertEquals( $expected[$pos][0], $listItem->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getCode() );
			$pos++;
		}
	}


	public function testProcessMultiple()
	{
		$mapping = array(
			0 => 'catalog.lists.type',
			1 => 'catalog.code',
			2 => 'catalog.lists.type',
			3 => 'catalog.code',
		);

		$data = array(
			0 => 'default',
			1 => "job_csv_test\njob_csv_test2",
			2 => 'promotion',
			3 => "job_csv_test\njob_csv_test2",
		);

		$catItem = $this->create( 'job_csv_test' );
		$catItem2 = $this->create( 'job_csv_test2' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( self::$product, $data );

		$category = $this->get( 'job_csv_test' );
		$category2 = $this->get( 'job_csv_test2' );

		$this->delete( $catItem );
		$this->delete( $catItem2 );


		$pos = 0;
		$types = array( 'default', 'promotion', 'default', 'promotion' );

		foreach( array( $category->getListItems(), $category2->getListItems() ) as $listItems )
		{
			$this->assertEquals( 2, count( $listItems ) );

			foreach( $listItems as $listItem )
			{
				$this->assertEquals( 1, $listItem->getStatus() );
				$this->assertEquals( 'product', $listItem->getDomain() );
				$this->assertEquals( $types[$pos], $listItem->getType() );
				$this->assertEquals( 'job_csv_prod', $listItem->getRefItem()->getCode() );
				$pos++;
			}
		}
	}


	public function testProcessUpdate()
	{
		$mapping = array(
			0 => 'catalog.lists.type',
			1 => 'catalog.code',
		);

		$data = array(
			0 => 'default',
			1 => 'job_csv_test',
		);

		$dataUpdate = array(
			0 => 'promotion',
			1 => 'job_csv_test',
		);

		$catItem = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( self::$product, $data );
		$object->process( self::$product, $dataUpdate );

		$category = $this->get( 'job_csv_test' );
		$this->delete( $catItem );


		$listItems = $category->getListItems();
		$listItem = $listItems->first();

		$this->assertEquals( 1, count( $listItems ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );

		$this->assertEquals( 'job_csv_prod', $listItem->getRefItem()->getCode() );
	}


	public function testProcessDelete()
	{
		$mapping = array(
			0 => 'catalog.lists.type',
			1 => 'catalog.code',
		);

		$data = array(
			0 => 'default',
			1 => 'job_csv_test',
		);

		$catItem = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( self::$product, $data );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, [], $this->endpoint );
		$object->process( self::$product, [] );

		$category = $this->get( 'job_csv_test' );
		$this->delete( $catItem );


		$listItems = $category->getListItems();

		$this->assertEquals( 0, count( $listItems ) );
	}


	public function testProcessEmpty()
	{
		$mapping = array(
			0 => 'catalog.lists.type',
			1 => 'catalog.code',
			2 => 'catalog.lists.type',
			3 => 'catalog.code',
		);

		$data = array(
			0 => '',
			1 => '',
			2 => 'default',
			3 => 'job_csv_test',
		);

		$catItem = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( self::$product, $data );

		$category = $this->get( 'job_csv_test' );
		$this->delete( $catItem );


		$listItems = $category->getListItems();

		$this->assertEquals( 1, count( $listItems ) );
	}


	public function testProcessListtypes()
	{
		$mapping = array(
			0 => 'catalog.lists.type',
			1 => 'catalog.code',
			2 => 'catalog.lists.type',
			3 => 'catalog.code',
		);

		$data = array(
			0 => 'promotion',
			1 => 'job_csv_test',
			2 => 'default',
			3 => 'job_csv_test',
		);

		$this->context->getConfig()->set( 'controller/common/product/import/csv/processor/catalog/listtypes', array( 'default' ) );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Catalog\Standard( $this->context, $mapping, $this->endpoint );

		$this->expectException( '\Aimeos\Controller\Common\Exception' );
		$object->process( self::$product, $data );
	}


	/**
	 * @param string $code
	 */
	protected function create( $code )
	{
		$manager = \Aimeos\MShop\Catalog\Manager\Factory::create( $this->context );
		return $manager->insert( $manager->create()->setCode( $code ) );
	}


	protected function delete( \Aimeos\MShop\Catalog\Item\Iface $catItem )
	{
		\Aimeos\MShop\Catalog\Manager\Factory::create( $this->context )->delete( $catItem->getId() );
	}


	/**
	 * @param string $code
	 */
	protected function get( $code )
	{
		return \Aimeos\MShop\Catalog\Manager\Factory::create( $this->context )->find( $code, ['product'] );
	}
}
