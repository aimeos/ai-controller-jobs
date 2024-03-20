<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2024
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\Begin;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $aimeos;
	private $context;
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->aimeos = \TestHelper::getAimeos();
		$this->context = \TestHelper::context();

		$this->object = new \Aimeos\Controller\Jobs\Subscription\Process\Begin\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Subscription process start', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$this->assertEquals( 'Process subscriptions initially', $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->context->config()->set( 'controller/jobs/subscription/process/processors', ['cgroup'] );

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['iterate', 'save'] )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard', $managerStub );

		$managerStub->expects( $this->exactly( 2 ) )->method( 'iterate' )
			->willReturn( map( [$this->getSubscription()] ), null );

		$managerStub->expects( $this->once() )->method( 'save' );

		$this->object->run();
	}


	public function testRunException()
	{
		$this->context->config()->set( 'controller/jobs/subscription/process/processors', ['cgroup'] );

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['iterate', 'save'] )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard', $managerStub );

		$object = $this->getMockBuilder( '\\Aimeos\\Controller\\Jobs\\Subscription\\Process\\Begin\\Standard' )
			->setConstructorArgs( [$this->context, $this->aimeos] )
			->onlyMethods( ['process'] )
			->getMock();

		$managerStub->expects( $this->exactly( 2 ) )->method( 'iterate' )
			->willReturn( map( [$managerStub->create()] ), null );

		$managerStub->expects( $this->never() )->method( 'save' );

		$object->expects( $this->once() )->method( 'process' )->will( $this->throwException( new \RuntimeException() ) );

		$object->run();
	}


	protected function getSubscription()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'subscription' );
		$search = $manager->filter()->add( ['subscription.dateend' => '2010-01-01'] );
		$domains = ['order', 'order/address', 'order/coupon', 'order/product', 'order/service'];

		return $manager->search( $search, $domains )->first( new \Exception( 'No subscription item found' ) );
	}
}
