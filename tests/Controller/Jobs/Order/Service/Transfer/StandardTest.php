<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021-2025
 */


namespace Aimeos\Controller\Jobs\Order\Service\Transfer;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Order\Service\Transfer\Standard( $context, $aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Transfers money to vendors', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Transfers the price of ordered products to the vendors incl. commission handling';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();


		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->onlyMethods( ['getProvider', 'iterate'] )
			->setConstructorArgs( [$context] )
			->getMock();

		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->onlyMethods( ['save', 'iterate'] )
			->setConstructorArgs( [$context] )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Service\\Manager\\Standard', $serviceManagerStub );
		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Standard', $orderManagerStub );


		$serviceItem = $serviceManagerStub->create()->setType( '' );
		$orderItem = $orderManagerStub->create();

		$serviceProviderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Provider\\Payment\\PrePay' )
			->onlyMethods( ['isImplemented', 'transfer'] )
			->setConstructorArgs( [$context, $serviceItem] )
			->getMock();


		$serviceManagerStub->expects( $this->exactly( 2 ) )->method( 'iterate' )
			->willReturn( map( [$serviceItem] ), null );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->willReturn( $serviceProviderStub );

		$orderManagerStub->expects( $this->exactly( 2 ) )->method( 'iterate' )
			->willReturn( map( [$orderItem] ), null );

		$serviceProviderStub->expects( $this->once() )->method( 'isImplemented' )
			->willReturn( true );

		$serviceProviderStub->expects( $this->once() )->method( 'transfer' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Transfer\Standard( $context, $aimeos );
		$object->run();
	}


	public function testRunExceptionProcess()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();


		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->onlyMethods( ['save', 'iterate'] )
			->setConstructorArgs( [$context] )
			->getMock();

		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->onlyMethods( ['getProvider', 'iterate'] )
			->setConstructorArgs( [$context] )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Service\\Manager\\Standard', $serviceManagerStub );
		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Standard', $orderManagerStub );


		$serviceItem = $serviceManagerStub->create()->setType( '' );
		$orderItem = $orderManagerStub->create();

		$serviceProviderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Provider\\Payment\\PrePay' )
			->onlyMethods( ['isImplemented', 'transfer'] )
			->setConstructorArgs( [$context, $serviceItem] )
			->getMock();


		$serviceManagerStub->expects( $this->exactly( 2 ) )->method( 'iterate' )
			->willReturn( map( [$serviceItem] ), null );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->willReturn( $serviceProviderStub );

		$orderManagerStub->expects( $this->exactly( 2 ) )->method( 'iterate' )
			->willReturn( map( [$orderItem] ), null );

		$serviceProviderStub->expects( $this->once() )->method( 'isImplemented' )
			->willReturn( true );

		$serviceProviderStub->expects( $this->once() )->method( 'transfer' )
			->will( $this->throwException( new \Aimeos\MShop\Service\Exception( 'test oder service payment: transfer' ) ) );

		$orderManagerStub->expects( $this->never() )->method( 'save' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Transfer\Standard( $context, $aimeos );
		$object->run();
	}


	public function testRunExceptionProvider()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();


		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->onlyMethods( ['save', 'iterate'] )
			->setConstructorArgs( [$context] )
			->getMock();

		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->onlyMethods( ['getProvider', 'iterate'] )
			->setConstructorArgs( [$context] )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Service\\Manager\\Standard', $serviceManagerStub );
		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Standard', $orderManagerStub );


		$serviceItem = $serviceManagerStub->create()->setType( '' );

		$serviceManagerStub->expects( $this->exactly( 2 ) )->method( 'iterate' )
			->willReturn( map( [$serviceItem] ), null );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->will( $this->throwException( new \Aimeos\MShop\Service\Exception( 'test service delivery process: getProvider' ) ) );

		$orderManagerStub->expects( $this->never() )->method( 'iterate' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Transfer\Standard( $context, $aimeos );
		$object->run();
	}
}
