<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */


namespace Aimeos\Controller\Jobs\Order\Service\Transfer;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		$context = \TestHelperJobs::getContext();
		$aimeos = \TestHelperJobs::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Order\Service\Transfer\Standard( $context, $aimeos );
	}


	protected function tearDown() : void
	{
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
		$context = \TestHelperJobs::getContext();
		$aimeos = \TestHelperJobs::getAimeos();


		$name = 'ControllerJobsServiceTransferProcessDefaultRun';
		$context->getConfig()->set( 'mshop/service/manager/name', $name );
		$context->getConfig()->set( 'mshop/order/manager/name', $name );


		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->setMethods( ['getProvider', 'search'] )
			->setConstructorArgs( [$context] )
			->getMock();

		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setMethods( ['save', 'search'] )
			->setConstructorArgs( [$context] )
			->getMock();

		\Aimeos\MShop\Service\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Service\\Manager\\' . $name, $serviceManagerStub );
		\Aimeos\MShop\Order\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Order\\Manager\\' . $name, $orderManagerStub );


		$serviceItem = $serviceManagerStub->create()->setType( '' );
		$orderItem = $orderManagerStub->create();

		$serviceProviderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Provider\\Payment\\PrePay' )
			->setMethods( ['isImplemented', 'transfer'] )
			->setConstructorArgs( [$context, $serviceItem] )
			->getMock();


		$serviceManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->onConsecutiveCalls( map( [$serviceItem] ), map() ) );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->will( $this->returnValue( $serviceProviderStub ) );

		$orderManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->onConsecutiveCalls( map( [$orderItem] ), map() ) );

		$serviceProviderStub->expects( $this->once() )->method( 'isImplemented' )
			->will( $this->returnValue( true ) );

		$serviceProviderStub->expects( $this->once() )->method( 'transfer' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Transfer\Standard( $context, $aimeos );
		$object->run();
	}


	public function testRunExceptionProcess()
	{
		$context = \TestHelperJobs::getContext();
		$aimeos = \TestHelperJobs::getAimeos();


		$name = 'ControllerJobsServiceTransferProcessDefaultRun';
		$context->getConfig()->set( 'mshop/service/manager/name', $name );
		$context->getConfig()->set( 'mshop/order/manager/name', $name );


		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setMethods( ['save', 'search'] )
			->setConstructorArgs( [$context] )
			->getMock();

		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->setMethods( ['getProvider', 'search'] )
			->setConstructorArgs( [$context] )
			->getMock();

		\Aimeos\MShop\Service\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Service\\Manager\\' . $name, $serviceManagerStub );
		\Aimeos\MShop\Order\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Order\\Manager\\' . $name, $orderManagerStub );


		$serviceItem = $serviceManagerStub->create()->setType( '' );
		$orderItem = $orderManagerStub->create();

		$serviceProviderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Provider\\Payment\\PrePay' )
			->setMethods( ['isImplemented', 'transfer'] )
			->setConstructorArgs( [$context, $serviceItem] )
			->getMock();


		$serviceManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->onConsecutiveCalls( map( [$serviceItem] ), map() ) );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->will( $this->returnValue( $serviceProviderStub ) );

		$orderManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->onConsecutiveCalls( map( [$orderItem] ), map() ) );

		$serviceProviderStub->expects( $this->once() )->method( 'isImplemented' )
			->will( $this->returnValue( true ) );

		$serviceProviderStub->expects( $this->once() )->method( 'transfer' )
			->will( $this->throwException( new \Aimeos\MShop\Service\Exception( 'test oder service payment: transfer' ) ) );

		$orderManagerStub->expects( $this->never() )->method( 'save' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Transfer\Standard( $context, $aimeos );
		$object->run();
	}


	public function testRunExceptionProvider()
	{
		$context = \TestHelperJobs::getContext();
		$aimeos = \TestHelperJobs::getAimeos();


		$name = 'ControllerJobsServiceTransferProcessDefaultRun';
		$context->getConfig()->set( 'mshop/service/manager/name', $name );
		$context->getConfig()->set( 'mshop/order/manager/name', $name );


		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setMethods( ['save', 'search'] )
			->setConstructorArgs( [$context] )
			->getMock();

		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->setMethods( ['getProvider', 'search'] )
			->setConstructorArgs( [$context] )
			->getMock();

		\Aimeos\MShop\Service\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Service\\Manager\\' . $name, $serviceManagerStub );
		\Aimeos\MShop\Order\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Order\\Manager\\' . $name, $orderManagerStub );


		$serviceItem = $serviceManagerStub->create()->setType( '' );

		$serviceManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->onConsecutiveCalls( map( [$serviceItem] ), map() ) );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->will( $this->throwException( new \Aimeos\MShop\Service\Exception( 'test service delivery process: getProvider' ) ) );

		$orderManagerStub->expects( $this->never() )->method( 'search' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Transfer\Standard( $context, $aimeos );
		$object->run();
	}
}
