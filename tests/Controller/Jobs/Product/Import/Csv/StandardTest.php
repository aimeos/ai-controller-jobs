<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2024
 */


namespace Aimeos\Controller\Jobs\Product\Import\Csv;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	public static function setUpBeforeClass() : void
	{
		$context = \TestHelper::context();

		$fs = $context->fs( 'fs-import' );
		$fs->has( 'product' ) ?: $fs->mkdir( 'product' );
		$fs->writef( 'product/unittest/empty.csv', __DIR__ . '/_testfiles/empty.csv' );

		$fs->has( 'product/valid' ) ?: $fs->mkdir( 'product/valid' );
		$fs->writef( 'product/valid/unittest/products.csv', __DIR__ . '/_testfiles/valid/products.csv' );

		$fs->has( 'product/position' ) ?: $fs->mkdir( 'product/position' );
		$fs->writef( 'product/position/unittest/products.csv', __DIR__ . '/_testfiles/position/products.csv' );

		$fs = $context->fs( 'fs-media' );
		$fs->has( 'path/to' ) ?: $fs->mkdir( 'path/to' );
		$fs->write( 'path/to/image2.jpg', 'test' );
		$fs->write( 'path/to/image.jpg', 'test' );

		$fs = $context->fs( 'fs-mimeicon' );
		$fs->write( 'unknown.png', 'icon' );
	}


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();
		$this->aimeos = \TestHelper::getAimeos();

		$config = $this->context->config();
		$config->set( 'controller/jobs/product/import/csv/skip-lines', 1 );
		$config->set( 'controller/jobs/product/import/csv/location', 'product/valid' );

		$this->object = new \Aimeos\Controller\Jobs\Product\Import\Csv\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Product import CSV', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Imports new and updates existing products from CSV files';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$prodcodes = array( 'job_csv_test', 'job_csv_test2' );
		$nondelete = array( 'attribute', 'product', 'catalog' );
		$delete = array( 'media', 'price', 'text' );

		$this->object->run();

		$result = $this->get( $prodcodes, array_merge( $delete, $nondelete ) );
		$properties = $this->getProperties( array_keys( $result ) );
		$this->delete( $prodcodes, $delete );

		$this->assertEquals( 2, count( $result ) );
		$this->assertEquals( 2, count( $properties ) );

		foreach( $result as $product ) {
			$this->assertEquals( 6, count( $product->getListItems() ) );
		}
	}


	public function testRunUpdate()
	{
		$prodcodes = array( 'job_csv_test', 'job_csv_test2' );
		$nondelete = array( 'attribute', 'product', 'catalog' );
		$delete = array( 'media', 'price', 'text' );

		$mapping = array(
			'item' => array(
				0 => 'product.code',
				1 => 'product.label',
				2 => 'product.type',
				3 => 'product.status',
			),
			'text' => array(
				4 => 'text.type',
				5 => 'text.content',
				6 => 'text.type',
				7 => 'text.content',
			),
			'media' => array(
				8 => 'media.url',
			),
			'price' => array(
				9 => 'price.currencyid',
				10 => 'price.quantity',
				11 => 'price.value',
				12 => 'price.taxrate',
			),
			'attribute' => array(
				13 => 'product.lists.type',
				14 => 'attribute.code',
				15 => 'attribute.type',
			),
			'product' => array(
				16 => 'product.code',
				17 => 'product.lists.type',
			),
			'property' => array(
				18 => 'product.property.value',
				19 => 'product.property.type',
			),
			'catalog' => array(
				20 => 'catalog.code',
				21 => 'catalog.lists.type',
				22 => 'product.lists.config',
			),
		);
		$this->context->config()->set( 'controller/jobs/product/import/csv/mapping', $mapping );

		$fs = $this->context->fs( 'fs-import' );
		$fs->writef( 'product/valid/unittest/products.csv', __DIR__ . '/_testfiles/valid/products.csv' );

		$this->object->run();

		$fs = $this->context->fs( 'fs-import' );
		$fs->writef( 'product/valid/unittest/products.csv', __DIR__ . '/_testfiles/valid/products.csv' );

		$this->object->run();

		$result = $this->get( $prodcodes, array_merge( $delete, $nondelete ) );
		$properties = $this->getProperties( array_keys( $result ) );
		$this->delete( $prodcodes, $delete );

		$this->assertEquals( 2, count( $result ) );
		$this->assertEquals( 2, count( $properties ) );

		foreach( $result as $product ) {
			$this->assertEquals( 6, count( $product->getListItems() ) );
		}

		$config = map( $result )->getListItems( 'catalog' )->flat( 1 )->getConfig()->first();
		$this->assertEquals( ['num' => 2, 'key' => 'value'], $config );
	}


	public function testRunPosition()
	{
		$prodcodes = array( 'job_csv_test', 'job_csv_test2' );
		$nondelete = array( 'attribute', 'product' );
		$delete = array( 'media', 'price', 'text' );

		$config = $this->context->config();
		$mapping = $config->get( 'controller/jobs/product/import/csv/mapping', [] );
		$mapping['item'] = array( 0 => 'product.label', 1 => 'product.code' );

		$config->set( 'controller/jobs/product/import/csv/mapping', $mapping );
		$config->set( 'controller/jobs/product/import/csv/location', 'product/position' );

		$this->object->run();

		$result = $this->get( $prodcodes, array_merge( $delete, $nondelete ) );
		$this->delete( $prodcodes, $delete );

		$this->assertEquals( 2, count( $result ) );
	}


	public function testRunProcessorInvalidMapping()
	{
		$config = $this->context->config();
		$config->set( 'controller/jobs/product/import/csv/location', 'product' );

		$mapping = array(
			'media' => array(
					8 => 'media.url',
			),
		);

		$this->context->config()->set( 'controller/jobs/product/import/csv/mapping', $mapping );

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->run();
	}


	public function testRunBackup()
	{
		$config = $this->context->config();
		$config->set( 'controller/jobs/product/import/csv/backup', 'backup-%Y-%m-%d.csv' );
		$config->set( 'controller/jobs/product/import/csv/location', 'product' );

		$this->object->run();

		$filename = \Aimeos\Base\Str::strtime( 'backup-%Y-%m-%d.csv' );
		$this->assertTrue( $this->context->fs( 'fs-import' )->has( $filename ) );

		$this->context->fs( 'fs-import' )->rm( $filename );
	}


	public function testRunCleanup()
	{
		$stub = $this->getMockBuilder( '\\Aimeos\\MShop\\Index\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['save', 'type'] )
			->getMock();

		$stub->expects( $this->exactly( 1 ) )->method( 'save' );
		$stub->method( 'type' )->willReturn( ['product'] );
		$stub = new \Aimeos\MShop\Common\Manager\Decorator\Lists( $stub, $this->context );

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Index\\Manager\\Standard', $stub );

		$result = $this->access( 'cleanup' )->invokeArgs( $this->object, [date( 'Y-m-d H:i:s' )] );

		$this->assertEquals( 15, $result );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( \Aimeos\Controller\Jobs\Product\Import\Csv\Standard::class );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}


	protected function delete( array $prodcodes, array $delete )
	{
		$productManager = \Aimeos\MShop::create( $this->context, 'product' );

		foreach( $this->get( $prodcodes, $delete ) as $id => $product )
		{
			foreach( $delete as $domain )
			{
				$ids = $product->getListItems( $domain )->getRefId()->all();
				\Aimeos\MShop::create( $this->context, $domain )->delete( $ids );
			}

			$productManager->delete( $product->getId() );
		}


		$attrManager = \Aimeos\MShop::create( $this->context, 'attribute' );

		$search = $attrManager->filter();
		$search->setConditions( $search->compare( '==', 'attribute.code', 'import-test' ) );

		$attrManager->delete( $attrManager->search( $search ) );
	}


	protected function get( array $prodcodes, array $domains ) : array
	{
		$productManager = \Aimeos\MShop::create( $this->context, 'product' );

		$search = $productManager->filter();
		$search->setConditions( $search->compare( '==', 'product.code', $prodcodes ) );

		return $productManager->search( $search, $domains )->all();
	}


	protected function getProperties( array $prodids ) : array
	{
		$manager = \Aimeos\MShop::create( $this->context, 'product/property' );

		$search = $manager->filter()->order( 'product.property.type' )
			->add( ['product.property.parentid' => $prodids] );

		return $manager->search( $search )->all();
	}
}
