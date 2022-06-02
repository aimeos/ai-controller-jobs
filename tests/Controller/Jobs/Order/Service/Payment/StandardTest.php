<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2022
 */


namespace Aimeos\Controller\Jobs\Order\Service\Payment;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Order\Service\Payment\Standard( $context, $aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Capture authorized payments', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Authorized payments of orders will be captured after dispatching or after a configurable amount of time';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();


		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->setMethods( array( 'getProvider', 'search' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setMethods( array( 'save', 'search' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Service\\Manager\\Standard', $serviceManagerStub );
		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Standard', $orderManagerStub );


		$serviceItem = $serviceManagerStub->create()->setType( '' );
		$orderItem = $orderManagerStub->create();

		$serviceProviderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Provider\\Payment\\PrePay' )
			->setMethods( array( 'isImplemented', 'capture' ) )
			->setConstructorArgs( array( $context, $serviceItem ) )
			->getMock();


		$serviceManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->onConsecutiveCalls( map( [$serviceItem] ), map() ) );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->will( $this->returnValue( $serviceProviderStub ) );

		$orderManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->onConsecutiveCalls( map( [$orderItem] ), map() ) );

		$serviceProviderStub->expects( $this->once() )->method( 'isImplemented' )
			->will( $this->returnValue( true ) );

		$serviceProviderStub->expects( $this->once() )->method( 'capture' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Payment\Standard( $context, $aimeos );
		$object->run();
	}


	public function testRunExceptionProcess()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();


		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setMethods( array( 'save', 'search' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->setMethods( array( 'getProvider', 'search' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Service\\Manager\\Standard', $serviceManagerStub );
		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Standard', $orderManagerStub );


		$serviceItem = $serviceManagerStub->create()->setType( '' );
		$orderItem = $orderManagerStub->create();

		$serviceProviderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Provider\\Payment\\PrePay' )
			->setMethods( array( 'isImplemented', 'capture' ) )
			->setConstructorArgs( array( $context, $serviceItem ) )
			->getMock();


		$serviceManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->onConsecutiveCalls( map( [$serviceItem] ), map() ) );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->will( $this->returnValue( $serviceProviderStub ) );

		$orderManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->onConsecutiveCalls( map( [$orderItem] ), map() ) );

		$serviceProviderStub->expects( $this->once() )->method( 'isImplemented' )
			->will( $this->returnValue( true ) );

		$serviceProviderStub->expects( $this->once() )->method( 'capture' )
			->will( $this->throwException( new \Aimeos\MShop\Service\Exception( 'test oder service payment: capture' ) ) );

		$orderManagerStub->expects( $this->never() )->method( 'save' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Payment\Standard( $context, $aimeos );
		$object->run();
	}


	public function testRunExceptionProvider()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();


		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setMethods( array( 'save', 'search' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->setMethods( array( 'getProvider', 'search' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Service\\Manager\\Standard', $serviceManagerStub );
		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Standard', $orderManagerStub );


		$serviceItem = $serviceManagerStub->create()->setType( '' );

		$serviceManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->onConsecutiveCalls( map( [$serviceItem] ), map() ) );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->will( $this->throwException( new \Aimeos\MShop\Service\Exception() ) );

		$orderManagerStub->expects( $this->never() )->method( 'search' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Payment\Standard( $context, $aimeos );
		$object->run();
	}
}
