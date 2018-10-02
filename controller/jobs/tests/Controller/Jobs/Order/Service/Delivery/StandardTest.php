<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2018
 */


namespace Aimeos\Controller\Jobs\Order\Service\Delivery;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp()
	{
		$context = \TestHelperJobs::getContext();
		$aimeos = \TestHelperJobs::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Order\Service\Delivery\Standard( $context, $aimeos );
	}


	protected function tearDown()
	{
		$this->object = null;
	}


	public function testGetName()
	{
		$this->assertEquals( 'Process order delivery services', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Sends paid orders to the ERP system or logistic partner';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$context = \TestHelperJobs::getContext();
		$aimeos = \TestHelperJobs::getAimeos();


		$name = 'ControllerJobsServiceDeliveryProcessDefaultRun';
		$context->getConfig()->set( 'mshop/service/manager/name', $name );
		$context->getConfig()->set( 'mshop/order/manager/name', $name );


		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->setMethods( array( 'getProvider', 'searchItems' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setMethods( array( 'saveItems', 'searchItems' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		\Aimeos\MShop\Service\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Service\\Manager\\' . $name, $serviceManagerStub );
		\Aimeos\MShop\Order\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Order\\Manager\\' . $name, $orderManagerStub );


		$serviceItem = $serviceManagerStub->createItem();
		$orderItem = $orderManagerStub->createItem();

		$serviceProviderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Provider\\Delivery\\Manual' )
			->setConstructorArgs( array( $context, $serviceItem ) )
			->getMock();


		$serviceManagerStub->expects( $this->once() )->method( 'searchItems' )
			->will( $this->onConsecutiveCalls( array( $serviceItem ), [] ) );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->will( $this->returnValue( $serviceProviderStub ) );

		$orderManagerStub->expects( $this->once() )->method( 'searchItems' )
			->will( $this->onConsecutiveCalls( array( $orderItem ), [] ) );

		$serviceProviderStub->expects( $this->once() )->method( 'processBatch' );

		$orderManagerStub->expects( $this->once() )->method( 'saveItems' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Delivery\Standard( $context, $aimeos );
		$object->run();
	}


	public function testRunExceptionProcess()
	{
		$context = \TestHelperJobs::getContext();
		$aimeos = \TestHelperJobs::getAimeos();


		$name = 'ControllerJobsServiceDeliveryProcessDefaultRun';
		$context->getConfig()->set( 'mshop/service/manager/name', $name );
		$context->getConfig()->set( 'mshop/order/manager/name', $name );


		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setMethods( array( 'saveItems', 'searchItems' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->setMethods( array( 'getProvider', 'searchItems' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		\Aimeos\MShop\Service\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Service\\Manager\\' . $name, $serviceManagerStub );
		\Aimeos\MShop\Order\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Order\\Manager\\' . $name, $orderManagerStub );


		$serviceItem = $serviceManagerStub->createItem();
		$orderItem = $orderManagerStub->createItem();

		$serviceProviderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Provider\\Delivery\\Manual' )
			->setConstructorArgs( array( $context, $serviceItem ) )
			->getMock();


		$serviceManagerStub->expects( $this->once() )->method( 'searchItems' )
			->will( $this->onConsecutiveCalls( array( $serviceItem ), [] ) );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->will( $this->returnValue( $serviceProviderStub ) );

		$orderManagerStub->expects( $this->once() )->method( 'searchItems' )
			->will( $this->onConsecutiveCalls( array( $orderItem ), [] ) );

		$serviceProviderStub->expects( $this->once() )->method( 'processBatch' )
			->will( $this->throwException( new \Aimeos\MShop\Service\Exception( 'test order service delivery: process' ) ) );

		$orderManagerStub->expects( $this->never() )->method( 'saveItems' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Delivery\Standard( $context, $aimeos );
		$object->run();
	}


	public function testRunExceptionProvider()
	{
		$context = \TestHelperJobs::getContext();
		$aimeos = \TestHelperJobs::getAimeos();


		$name = 'ControllerJobsServiceDeliveryProcessDefaultRun';
		$context->getConfig()->set( 'mshop/service/manager/name', $name );
		$context->getConfig()->set( 'mshop/order/manager/name', $name );


		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setMethods( array( 'saveItem', 'searchItems' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->setMethods( array( 'getProvider', 'searchItems' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		\Aimeos\MShop\Service\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Service\\Manager\\' . $name, $serviceManagerStub );
		\Aimeos\MShop\Order\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Order\\Manager\\' . $name, $orderManagerStub );


		$serviceItem = $serviceManagerStub->createItem();

		$serviceManagerStub->expects( $this->once() )->method( 'searchItems' )
			->will( $this->onConsecutiveCalls( array( $serviceItem ), [] ) );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->will( $this->throwException( new \Aimeos\MShop\Service\Exception( 'test sorder service delivery: getProvider' ) ) );

		$orderManagerStub->expects( $this->never() )->method( 'searchItems' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Delivery\Standard( $context, $aimeos );
		$object->run();
	}
}
