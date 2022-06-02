<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2022
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\End;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$aimeos = \TestHelper::getAimeos();
		$this->context = \TestHelper::context();

		$this->object = new \Aimeos\Controller\Jobs\Subscription\Process\End\Standard( $this->context, $aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Subscription process end', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$this->assertEquals( 'Terminates expired subscriptions', $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->context->config()->set( 'controller/common/subscription/process/processors', ['cgroup'] );
		$item = $this->getSubscription();

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['search', 'save'] )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard', $managerStub );

		$managerStub->expects( $this->once() )->method( 'search' )
			->will( $this->returnValue( map( [$item] ) ) );

		$managerStub->expects( $this->once() )->method( 'save' );

		$this->object->run();
	}


	public function testRunException()
	{
		$item = $this->getSubscription();

		$this->context->config()->set( 'controller/common/subscription/process/processors', ['cgroup'] );
		$this->context->config()->set( 'controller/common/subscription/process/processor/cgroup/groupids', ['1'] );

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['search', 'save'] )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard', $managerStub );

		$managerStub->expects( $this->once() )->method( 'search' )
			->will( $this->returnValue( map( [$item] ) ) );

		$managerStub->expects( $this->once() )->method( 'save' )
			->will( $this->throwException( new \Exception() ) );

		$this->object->run();
	}


	protected function getSubscription()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'subscription' );

		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'subscription.dateend', '2010-01-01' ) );

		if( ( $item = $manager->search( $search )->first() ) !== null ) {
			return $item;
		}

		throw new \Exception( 'No subscription item found' );
	}
}
