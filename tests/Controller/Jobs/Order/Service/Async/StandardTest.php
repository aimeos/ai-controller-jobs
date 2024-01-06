<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2024
 */


namespace Aimeos\Controller\Jobs\Order\Service\Async;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Order\Service\Async\Standard( $context, $aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Batch update of payment/delivery status', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Executes payment or delivery service providers that uses batch updates';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();


		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->onlyMethods( array( 'getProvider', 'search' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Service\\Manager\\Standard', $serviceManagerStub );


		$serviceItem = $serviceManagerStub->create()->setType( '' );

		$serviceProviderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Provider\\Delivery\\Standard' )
			->setConstructorArgs( array( $context, $serviceItem ) )
			->getMock();


		$serviceManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->onConsecutiveCalls( map( [$serviceItem] ), map() ) );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->will( $this->returnValue( $serviceProviderStub ) );

		$serviceProviderStub->expects( $this->once() )->method( 'updateAsync' );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Async\Standard( $context, $aimeos );
		$object->run();
	}


	public function testRunExceptionProcess()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();


		$serviceManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Service\\Manager\\Standard' )
			->onlyMethods( array( 'getProvider', 'search' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Service\\Manager\\Standard', $serviceManagerStub );


		$serviceItem = $serviceManagerStub->create()->setType( '' );

		$serviceManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->onConsecutiveCalls( map( [$serviceItem] ), map() ) );

		$serviceManagerStub->expects( $this->once() )->method( 'getProvider' )
			->will( $this->throwException( new \Aimeos\MShop\Service\Exception() ) );


		$object = new \Aimeos\Controller\Jobs\Order\Service\Async\Standard( $context, $aimeos );
		$object->run();
	}
}
