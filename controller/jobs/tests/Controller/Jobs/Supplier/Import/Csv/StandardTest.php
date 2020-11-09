<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2020
 */


namespace Aimeos\Controller\Jobs\Supplier\Import\Csv;


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

		$config->set( 'controller/jobs/supplier/import/csv/skip-lines', 1 );
		$config->set( 'controller/jobs/supplier/import/csv/location', __DIR__ . '/_testfiles/valid' );

		$this->object = new \Aimeos\Controller\Jobs\Supplier\Import\Csv\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		$this->object = null;

		if( file_exists( 'tmp/import.zip' ) )
		{
			unlink( 'tmp/import.zip' );
		}
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
		$prodcodes = array( 'job_csv_test', 'job_csv_test2' );
		$nondelete = array( 'address' );
		$delete = array( 'media', 'text' );

		$convert = array(
			1 => 'Text/LatinUTF8',
		);

		$this->context->getConfig()->set( 'controller/jobs/supplier/import/csv/converter', $convert );

		$this->object->run();

		$result = $this->get( $prodcodes, array_merge( $delete, $nondelete ) );


		$addresses = $this->getAddresses( $result->keys()->toArray() );
		$this->delete( $prodcodes, $delete, $nondelete );

		$this->assertEquals( 2, count( $result ) );
		$this->assertEquals( 2, count( $addresses ) );

		foreach( $result as $supplier )
		{
			$this->assertEquals( 2, count( $supplier->getListItems() ) );
		}
	}


	public function testRunUpdate()
	{
		$prodcodes = array( 'job_csv_test', 'job_csv_test2' );
		$nondelete = array( 'address' );
		$delete = array( 'media', 'text' );

		$this->object->run();
		$this->object->run();

		$result = $this->get( $prodcodes, array_merge( $delete, $nondelete ) );
		$addresses = $this->getAddresses( $result->keys()->toArray() );
		$this->delete( $prodcodes, $delete, $nondelete );

		$this->assertEquals( 2, count( $result ) );
		$this->assertEquals( 2, count( $addresses ) );

		foreach( $result as $supplier )
		{
			$this->assertEquals( 2, count( $supplier->getListItems() ) );
		}
	}


	public function testRunPosition()
	{
		$prodcodes = array( 'job_csv_test', 'job_csv_test2' );
		$nondelete = array( 'address' );
		$delete = array( 'media', 'text' );

		$config = $this->context->getConfig();
		$mapping = $config->get( 'controller/jobs/supplier/import/csv/mapping', [] );
		$mapping['item'] = array( 0 => 'supplier.label', 1 => 'supplier.code' );

		$config->set( 'controller/jobs/supplier/import/csv/mapping', $mapping );
		$config->set( 'controller/jobs/supplier/import/csv/location', __DIR__ . '/_testfiles/position' );

		$this->object->run();

		$result = $this->get( $prodcodes, array_merge( $delete, $nondelete ) );
		$this->delete( $prodcodes, $delete, $nondelete );

		$this->assertEquals( 2, count( $result ) );
	}


	public function testRunProcessorInvalidMapping()
	{
		$mapping = array(
			'media' => array(
				8 => 'media.url',
			),
		);

		$this->context->getConfig()->set( 'controller/jobs/supplier/import/csv/mapping', $mapping );

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->run();
	}


	public function testRunBackup()
	{
		$config = $this->context->getConfig();
		$config->set( 'controller/jobs/supplier/import/csv/container/type', 'Zip' );
		$config->set( 'controller/jobs/supplier/import/csv/location', 'tmp/import.zip' );
		$config->set( 'controller/jobs/supplier/import/csv/backup', 'tmp/test-%Y-%m-%d.zip' );

		if( copy( __DIR__ . '/_testfiles/import.zip', 'tmp/import.zip' ) === false )
		{
			throw new \RuntimeException( 'Unable to copy test file' );
		}

		$this->object->run();

		$filename = strftime( 'tmp/test-%Y-%m-%d.zip' );
		$this->assertTrue( file_exists( $filename ) );

		unlink( $filename );
	}


	public function testRunBackupInvalid()
	{
		$config = $this->context->getConfig();
		$config->set( 'controller/jobs/supplier/import/csv/container/type', 'Zip' );
		$config->set( 'controller/jobs/supplier/import/csv/location', 'tmp/import.zip' );
		$config->set( 'controller/jobs/supplier/import/csv/backup', 'tmp/notexist/import.zip' );

		if( copy( __DIR__ . '/_testfiles/import.zip', 'tmp/import.zip' ) === false )
		{
			throw new \RuntimeException( 'Unable to copy test file' );
		}

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->run();
	}


	protected function delete( array $prodcodes, array $delete, array $nondelete )
	{
		$catListManager = \Aimeos\MShop\Catalog\Manager\Factory::create( $this->context )->getSubmanager( 'lists' );
		$supplierManager = \Aimeos\MShop\Supplier\Manager\Factory::create( $this->context );
		$listManager = $supplierManager->getSubManager( 'lists' );

		foreach( $this->get( $prodcodes, $delete + $nondelete ) as $id => $supplier )
		{
			foreach( $delete as $domain )
			{
				$manager = \Aimeos\MShop::create( $this->context, $domain );

				foreach( $supplier->getListItems( $domain ) as $listItem )
				{
					$manager->delete( $listItem->getRefItem()->getId() );
					$listManager->delete( $listItem->getId() );
				}
			}

			foreach( $nondelete as $domain )
			{
				$listManager->deleteItems( $supplier->getListItems( $domain )->toArray() );
			}

			$supplierManager->delete( $supplier->getId() );

			$search = $catListManager->filter();
			$search->setConditions( $search->compare( '==', 'catalog.lists.refid', $id ) );
			$result = $catListManager->search( $search );

			$catListManager->deleteItems( $result->toArray() );
		}


		$attrManager = \Aimeos\MShop\Attribute\Manager\Factory::create( $this->context );

		$search = $attrManager->filter();
		$search->setConditions( $search->compare( '==', 'attribute.code', 'import-test' ) );

		$result = $attrManager->search( $search );

		$attrManager->deleteItems( $result->toArray() );
	}


	protected function get( array $prodcodes, array $domains )
	{
		$supplierManager = \Aimeos\MShop\Supplier\Manager\Factory::create( $this->context );

		$search = $supplierManager->filter();
		$search->setConditions( $search->compare( '==', 'supplier.code', $prodcodes ) );

		return $supplierManager->search( $search, $domains );
	}

	protected function getAddresses( array $prodids )
	{
		$manager = \Aimeos\MShop\Supplier\Manager\Factory::create( $this->context )->getSubManager( 'address' );

		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'supplier.address.parentid', $prodids ) );

		return $manager->search( $search );
	}
}
