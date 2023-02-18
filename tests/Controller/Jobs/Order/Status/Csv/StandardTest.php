<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021-2023
 */


namespace Aimeos\Controller\Jobs\Order\Status\Csv;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $aimeos;
	private $context;
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();
		$this->aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Order\Status\Csv\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( true );
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Order status import CSV', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$this->assertEquals( 'Status import for orders from CSV file', $this->object->getDescription() );
	}


	public function testRun()
	{
		$dir = dirname( __DIR__, 5 ) . '/tmp/import/orderstatus';
		file_exists( $dir ) ?: mkdir( $dir );
		copy( __DIR__ . '/_test/status.csv', $dir . '/status.csv' );


		$this->context->config()->set( 'controller/jobs/order/status/csv/separator', ';' );
		$this->context->config()->set( 'controller/jobs/order/status/csv/skip', 1 );


		$orderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['save', 'search'] )
			->getMock();

		$oProdStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Product\\Standard' )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['save', 'search'] )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Standard', $orderStub );
		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Product\\Standard', $oProdStub );


		$orderStub->expects( $this->once() )->method( 'search' )
			->will( $this->returnValue( map( [$orderStub->create()->setId( 1 )] ) ) );

		$oProdStub->expects( $this->once() )->method( 'search' )
			->will( $this->returnValue( map( [$oProdStub->create()->setId( 2 )] ) ) );

		$orderStub->expects( $this->once() )->method( 'save' );
		$oProdStub->expects( $this->once() )->method( 'save' );


		$object = new \Aimeos\Controller\Jobs\Order\Status\Csv\Standard( $this->context, $this->aimeos );
		$object->run();
	}
}
