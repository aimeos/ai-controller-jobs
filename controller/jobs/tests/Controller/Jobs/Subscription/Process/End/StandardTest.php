<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\End;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp()
	{
		$aimeos = \TestHelperJobs::getAimeos();
		$this->context = \TestHelperJobs::getContext();

		$this->object = new \Aimeos\Controller\Jobs\Subscription\Process\End\Standard( $this->context, $aimeos );

		\Aimeos\MShop\Factory::setCache( true );
	}


	protected function tearDown()
	{
		\Aimeos\MShop\Factory::setCache( false );
		\Aimeos\MShop\Factory::clear();

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
		$this->context->getConfig()->set( 'controller/common/subscription/process/processors', ['cgroup'] );
		$item = $this->getSubscription();

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['searchItems', 'saveItem'] )
			->getMock();

		\Aimeos\MShop\Factory::injectManager( $this->context, 'subscription', $managerStub );

		$managerStub->expects( $this->once() )->method( 'searchItems' )
			->will( $this->returnValue( [$item] ) );

		$managerStub->expects( $this->once() )->method( 'saveItem' );

		$this->object->run();
	}


	public function testRunException()
	{
		$this->context->getConfig()->set( 'controller/common/subscription/process/processors', ['cgroup'] );
		$this->context->getConfig()->set( 'controller/common/subscription/process/processor/cgroup/groupids', ['1'] );

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['searchItems', 'saveItem'] )
			->getMock();

		\Aimeos\MShop\Factory::injectManager( $this->context, 'subscription', $managerStub );

		$managerStub->expects( $this->once() )->method( 'searchItems' )
			->will( $this->returnValue( [$managerStub->createItem()] ) );

		$managerStub->expects( $this->never() )->method( 'saveItem' );

		$this->object->run();
	}


	protected function getSubscription()
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->context, 'subscription' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'subscription.dateend', '2010-01-01' ) );

		$items = $manager->searchItems( $search );

		if( ( $item = reset( $items ) ) !== false ) {
			return $item;
		}

		throw new \Exception( 'No subscription item found' );
	}
}
