<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
 */

namespace Aimeos\Controller\Common\Subscription\Process\Processor\Cgroup;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp()
	{
		\Aimeos\MShop\Factory::setCache( true );
	}


	protected function tearDown()
	{
		\Aimeos\MShop\Factory::setCache( false );
	}


	public function testBegin()
	{
		$context = \TestHelperCntl::getContext();

		$context->getConfig()->set( 'controller/common/subscription/process/processor/cgroup/groupids', ['1', '2'] );

		$fcn = function( $subject ){
			return $subject->getGroups() === ['1', '2'];
		};

		$customerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Customer\\Manager\\Standard' )
			->setConstructorArgs( [$context] )
			->setMethods( ['getItem', 'saveItem'] )
			->getMock();

		\Aimeos\MShop\Factory::injectManager( $context, 'customer', $customerStub );

		$customerItem = $customerStub->createItem();

		$customerStub->expects( $this->once() )->method( 'getItem' )
			->will( $this->returnValue( $customerItem ) );

		$customerStub->expects( $this->once() )->method( 'saveItem' )
			->with( $this->callback( $fcn ) );

		$object = new \Aimeos\Controller\Common\Subscription\Process\Processor\Cgroup\Standard( $context );
		$object->begin( $this->getSubscription( $context ) );
	}


	public function testEnd()
	{
		$context = \TestHelperCntl::getContext();

		$context->getConfig()->set( 'controller/common/subscription/process/processor/cgroup/groupids', ['1', '2'] );

		$fcn = function( $subject ){
			return $subject->getGroups() === [];
		};

		$customerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Customer\\Manager\\Standard' )
			->setConstructorArgs( [$context] )
			->setMethods( ['getItem', 'saveItem'] )
			->getMock();

		\Aimeos\MShop\Factory::injectManager( $context, 'customer', $customerStub );

		$customerItem = $customerStub->createItem()->setGroups( ['1', '2'] );

		$customerStub->expects( $this->once() )->method( 'getItem' )
			->will( $this->returnValue( $customerItem ) );

		$customerStub->expects( $this->once() )->method( 'saveItem' )
			->with( $this->callback( $fcn ) );

		$object = new \Aimeos\Controller\Common\Subscription\Process\Processor\Cgroup\Standard( $context );
		$object->end( $this->getSubscription( $context ) );
	}


	protected function getSubscription( $context )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'subscription' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'subscription.dateend', '2010-01-01' ) );

		$items = $manager->searchItems( $search );

		if( ( $item = reset( $items ) ) !== false ) {
			return $item;
		}

		throw new \Exception( 'No subscription item found' );
	}
}
