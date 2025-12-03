<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2025
 */


namespace Aimeos\Controller\Jobs\Customer\Import\Csv;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	public static function setUpBeforeClass() : void
	{
		$context = \TestHelper::context();

		$fs = $context->fs( 'fs-import' );
		$fs->has( 'customer' ) ?: $fs->mkdir( 'customer' );
		$fs->writef( 'customer/unittest/empty.csv', __DIR__ . '/_testfiles/empty.csv' );

		$fs->has( 'customer/valid' ) ?: $fs->mkdir( 'customer/valid' );
		$fs->writef( 'customer/valid/unittest/customers.csv', __DIR__ . '/_testfiles/valid/customers.csv' );

		$fs->has( 'customer/position' ) ?: $fs->mkdir( 'customer/position' );
		$fs->writef( 'customer/position/unittest/customers.csv', __DIR__ . '/_testfiles/position/customers.csv' );
	}


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();
		$this->aimeos = \TestHelper::getAimeos();

		$config = $this->context->config();
		$config->set( 'controller/jobs/customer/import/csv/skip-lines', 1 );
		$config->set( 'controller/jobs/customer/import/csv/location', 'customer/valid' );

		$this->object = new \Aimeos\Controller\Jobs\Customer\Import\Csv\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Customer import CSV', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Imports new and updates existing customers from CSV files';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$codes = array( 'job@csv.test', 'job2@csv.test' );

		$this->object->run();

		$result = $this->get( $codes, ['customer/address', 'customer/property'] );
		$this->delete( $codes );

		$this->assertEquals( 1, count( $result ) );
		$this->assertEquals( 1, count( current( $result )->getPropertyItems() ) );

		foreach( $result as $customer ) {
			$this->assertEquals( 1, count( $customer->getAddressItems() ) );
		}
	}


	public function testRunUpdate()
	{
		$codes = array( 'job@csv.test', 'job2@csv.test' );

		$fs = $this->context->fs( 'fs-import' );
		$fs->writef( 'customer/valid/unittest/customers.csv', __DIR__ . '/_testfiles/valid/customers.csv' );

		$this->object->run();

		$fs = $this->context->fs( 'fs-import' );
		$fs->writef( 'customer/valid/unittest/customers.csv', __DIR__ . '/_testfiles/valid/customers.csv' );

		$this->object->run();

		$result = $this->get( $codes, ['customer/address', 'customer/property'] );
		$this->delete( $codes );

		$this->assertEquals( 1, count( $result ) );
		$this->assertEquals( 1, count( current( $result )->getPropertyItems() ) );

		foreach( $result as $customer ) {
			$this->assertEquals( 1, count( $customer->getAddressItems() ) );
		}
	}


	public function testRunPosition()
	{
		$codes = array( 'job@csv.test', 'job2@csv.test' );

		$config = $this->context->config();
		$config->set( 'controller/jobs/customer/import/csv/location', 'customer/position' );

		$this->object->run();

		$result = $this->get( $codes );
		$this->delete( $codes );

		$this->assertEquals( 2, count( $result ) );
	}


	public function testRunProcessorInvalidMapping()
	{
		$config = $this->context->config();
		$config->set( 'controller/jobs/customer/import/csv/location', 'customer' );

		$mapping = array(
			'media' => array(
					8 => 'media.url',
			),
		);

		$this->context->config()->set( 'controller/jobs/customer/import/csv/mapping', $mapping );

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->run();
	}


	public function testRunBackup()
	{
		$config = $this->context->config();
		$config->set( 'controller/jobs/customer/import/csv/backup', 'backup-%Y-%m-%d.csv' );
		$config->set( 'controller/jobs/customer/import/csv/location', 'customer' );

		$this->object->run();

		$filename = \Aimeos\Base\Str::strtime( 'backup-%Y-%m-%d.csv' );
		$this->assertTrue( $this->context->fs( 'fs-import' )->has( $filename ) );

		$this->context->fs( 'fs-import' )->rm( $filename );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( \Aimeos\Controller\Jobs\Customer\Import\Csv\Standard::class );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}


	protected function delete( array $codes )
	{
		$customerManager = \Aimeos\MShop::create( $this->context, 'customer' );

		foreach( $this->get( $codes ) as $id => $customer ) {
			$customerManager->delete( $customer->getId() );
		}


		$attrManager = \Aimeos\MShop::create( $this->context, 'attribute' );

		$search = $attrManager->filter();
		$search->setConditions( $search->compare( '==', 'attribute.code', 'import-test' ) );

		$attrManager->delete( $attrManager->search( $search ) );
	}


	protected function get( array $codes, array $domains = [] ) : array
	{
		$customerManager = \Aimeos\MShop::create( $this->context, 'customer' );

		$search = $customerManager->filter();
		$search->setConditions( $search->compare( '==', 'customer.code', $codes ) );

		return $customerManager->search( $search, $domains )->all();
	}


	protected function getProperties( array $parentIds ) : array
	{
		$manager = \Aimeos\MShop::create( $this->context, 'customer/property' );

		$search = $manager->filter()->order( 'customer.property.type' )
			->add( ['customer.property.parentid' => $parentIds] );

		return $manager->search( $search )->all();
	}
}
