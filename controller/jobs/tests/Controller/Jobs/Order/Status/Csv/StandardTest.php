<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
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

		$this->context = \TestHelperJobs::getContext();
		$this->aimeos = \TestHelperJobs::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Order\Status\Csv\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context, $this->aimeos );
		\Aimeos\MShop::cache( true );
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
		$this->context->config()->set( 'controller/common/order/status/csv/directory', '' );
		$this->context->config()->set( 'controller/common/order/status/csv/separator', ';' );
		$this->context->config()->set( 'controller/common/order/status/csv/skip', 1 );


		$orderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['save', 'search'] )
			->getMock();

		$oProdStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Base\\Product\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['save', 'search'] )
			->getMock();

		\Aimeos\MShop::inject( 'order', $orderStub );
		\Aimeos\MShop::inject( 'order/base/product', $oProdStub );


		$orderStub->expects( $this->once() )->method( 'search' )
			->will( $this->returnValue( map( [$orderStub->create()] ) ) );

		$oProdStub->expects( $this->once() )->method( 'search' )
			->will( $this->returnValue( map( [$oProdStub->create()] ) ) );

		$orderStub->expects( $this->once() )->method( 'save' );
		$oProdStub->expects( $this->once() )->method( 'save' );


		$object = new \Aimeos\Controller\Jobs\Order\Status\Csv\Standard( $this->context, $this->aimeos );
		$object->run();

		$this->context->fs( 'fs-orderstatus' )->move( '_done/status.csv', 'status.csv' );
	}
}
