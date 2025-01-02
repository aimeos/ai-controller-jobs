<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 */


namespace Aimeos\Controller\Jobs\Supplier\Import\Csv;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	public static function setUpBeforeClass() : void
	{
		$context = \TestHelper::context();

		$fs = $context->fs( 'fs-import' );
		$fs->has( 'supplier/unittest' ) ?: $fs->mkdir( 'supplier/unittest' );
		$fs->writef( 'supplier/unittest/empty.csv', __DIR__ . '/_testfiles/empty.csv' );

		$fs->has( 'supplier/valid' ) ?: $fs->mkdir( 'supplier/valid' );
		$fs->writef( 'supplier/valid/unittest/suppliers.csv', __DIR__ . '/_testfiles/valid/suppliers.csv' );

		$fs->has( 'supplier/position' ) ?: $fs->mkdir( 'supplier/position' );
		$fs->writef( 'supplier/position/unittest/suppliers.csv', __DIR__ . '/_testfiles/position/suppliers.csv' );

		$fs = $context->fs( 'fs-media' );
		$fs->has( 'path/to' ) ?: $fs->mkdir( 'path/to' );
		$fs->write( 'path/to/file2.jpg', 'test' );
		$fs->write( 'path/to/file.jpg', 'test' );

		$fs = $context->fs( 'fs-mimeicon' );
		$fs->write( 'unknown.png', 'icon' );
	}


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->aimeos = \TestHelper::getAimeos();
		$this->context = \TestHelper::context();

		$config = $this->context->config();
		$config->set( 'controller/jobs/supplier/import/csv/skip-lines', 1 );
		$config->set( 'controller/jobs/supplier/import/csv/location', 'supplier/valid' );

		$this->object = new \Aimeos\Controller\Jobs\Supplier\Import\Csv\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Supplier import CSV', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Imports new and updates existing suppliers from CSV files';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$codes = ['job_csv_test', 'job_csv_test2'];

		$this->object->run();

		$result = $this->get( $codes, ['address', 'media', 'text'] );
		$addresses = $this->getAddresses( array_keys( $result ) );

		$this->delete( $codes, ['media', 'text'] );

		$this->assertEquals( 2, count( $result ) );
		$this->assertEquals( 2, count( $addresses ) );

		foreach( $result as $supplier ) {
			$this->assertEquals( 2, count( $supplier->getListItems() ) );
		}
	}


	public function testRunUpdate()
	{
		$fs = $this->context->fs( 'fs-import' );
		$fs->writef( 'supplier/valid/unittest/suppliers.csv', __DIR__ . '/_testfiles/valid/suppliers.csv' );

		$this->object->run();

		$fs = $this->context->fs( 'fs-import' );
		$fs->writef( 'supplier/valid/unittest/suppliers.csv', __DIR__ . '/_testfiles/valid/suppliers.csv' );

		$this->object->run();

		$codes = ['job_csv_test', 'job_csv_test2'];
		$result = $this->get( $codes, ['address', 'media', 'text'] );
		$addresses = $this->getAddresses( array_keys( $result ) );

		$this->delete( $codes, ['media', 'text'] );

		$this->assertEquals( 2, count( $result ) );
		$this->assertEquals( 2, count( $addresses ) );

		foreach( $result as $supplier ) {
			$this->assertEquals( 2, count( $supplier->getListItems() ) );
		}
	}


	public function testRunPosition()
	{
		$codes = ['job_csv_test', 'job_csv_test2'];

		$config = $this->context->config();
		$mapping = $config->get( 'controller/jobs/supplier/import/csv/mapping', [] );
		$mapping['item'] = array( 0 => 'supplier.label', 1 => 'supplier.code' );

		$config->set( 'controller/jobs/supplier/import/csv/mapping', $mapping );
		$config->set( 'controller/jobs/supplier/import/csv/location', 'supplier/position' );

		$this->object->run();

		$result = $this->get( $codes, ['address', 'media', 'text'] );
		$this->delete( $codes, ['media', 'text'] );

		$this->assertEquals( 2, count( $result ) );
	}


	public function testRunProcessorInvalidMapping()
	{
		$config = $this->context->config();
		$config->set( 'controller/jobs/supplier/import/csv/location', 'supplier' );

		$mapping = array(
			'media' => array(
				8 => 'media.url',
			),
		);

		$this->context->config()->set( 'controller/jobs/supplier/import/csv/mapping', $mapping );

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->run();
	}


	public function testRunBackup()
	{
		$config = $this->context->config();
		$config->set( 'controller/jobs/supplier/import/csv/backup', 'backup-%Y-%m-%d.csv' );
		$config->set( 'controller/jobs/supplier/import/csv/location', 'supplier' );

		$this->object->run();

		$filename = \Aimeos\Base\Str::strtime( 'backup-%Y-%m-%d.csv' );
		$this->assertTrue( $this->context->fs( 'fs-import' )->has( $filename ) );

		$this->context->fs( 'fs-import' )->rm( $filename );
	}


	protected function delete( array $codes, array $delete )
	{
		$supplierManager = \Aimeos\MShop::create( $this->context, 'supplier' );

		foreach( $this->get( $codes, $delete ) as $id => $supplier )
		{
			foreach( $delete as $domain )
			{
				$manager = \Aimeos\MShop::create( $this->context, $domain );

				foreach( $supplier->getListItems( $domain ) as $listItem ) {
					$manager->delete( $listItem->getRefItem()->getId() );
				}
			}

			$supplierManager->delete( $id );
		}


		$attrManager = \Aimeos\MShop::create( $this->context, 'attribute' );
		$search = $attrManager->filter()->add( ['attribute.code' => 'import-test'] );

		$attrManager->delete( $attrManager->search( $search ) );
	}


	protected function get( array $codes, array $domains ) : array
	{
		$supplierManager = \Aimeos\MShop::create( $this->context, 'supplier' );
		$search = $supplierManager->filter()->add( ['supplier.code' => $codes] );

		return $supplierManager->search( $search, $domains )->all();
	}


	protected function getAddresses( array $prodids ) : array
	{
		$manager = \Aimeos\MShop::create( $this->context, 'supplier/address' );
		$search = $manager->filter()->add( ['supplier.address.parentid' => $prodids] );

		return $manager->search( $search )->all();
	}
}
