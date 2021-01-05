<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2021
 */

namespace Aimeos\Controller\Common\Subscription\Process\Processor\Cgroup;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $custStub;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelperCntl::getContext();
		$this->context->getConfig()->set( 'controller/common/subscription/process/processor/cgroup/groupids', ['1', '2'] );

		$this->custStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Customer\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['get', 'save'] )
			->getMock();

		\Aimeos\MShop::inject( 'customer', $this->custStub );

		$this->custStub->expects( $this->once() )->method( 'get' )
			->will( $this->returnValue( $this->custStub->create() ) );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->context );
	}


	public function testBegin()
	{
		$ordProdStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Base\\Product\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['get'] )
			->getMock();

		\Aimeos\MShop::inject( 'order/base/product', $ordProdStub );

		$ordProdAttrManager = $ordProdStub->getSubManager( 'attribute' );
		$ordProdAttrItem = $ordProdAttrManager->create()->setType( 'hidden' )->setCode( 'customer/group' );

		$ordProdItem = $ordProdStub->create()->setAttributeItems( [
			( clone $ordProdAttrItem )->setAttributeId( 10 )->setValue( '3' ),
			( clone $ordProdAttrItem )->setAttributeId( 11 )->setValue( '4' ),
		] );

		$ordProdStub->expects( $this->once() )->method( 'get' )
			->will( $this->returnValue( $ordProdItem ) );

		$this->custStub->expects( $this->once() )->method( 'save' )
			->with( $this->callback( function( $subject ) {
				return $subject->getGroups() === ['3', '4'];
			} ) );


		$object = new \Aimeos\Controller\Common\Subscription\Process\Processor\Cgroup\Standard( $this->context );
		$object->begin( $this->getSubscription() );
	}


	public function testBeginCustomGroups()
	{
		$this->custStub->expects( $this->once() )->method( 'save' )
			->with( $this->callback( function( $subject ) {
				return $subject->getGroups() === ['1', '2'];
			} ) );


		$object = new \Aimeos\Controller\Common\Subscription\Process\Processor\Cgroup\Standard( $this->context );
		$object->begin( $this->getSubscription() );
	}


	public function testEnd()
	{
		$ordProdStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Base\\Product\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['get'] )
			->getMock();

		\Aimeos\MShop::inject( 'order/base/product', $ordProdStub );

		$ordProdAttrManager = $ordProdStub->getSubManager( 'attribute' );
		$ordProdAttrItem = $ordProdAttrManager->create()->setType( 'hidden' )->setCode( 'customer/group' );

		$ordProdItem = $ordProdStub->create()->setAttributeItems( [
			( clone $ordProdAttrItem )->setAttributeId( 10 )->setValue( '3' ),
			( clone $ordProdAttrItem )->setAttributeId( 11 )->setValue( '4' ),
		] );

		$ordProdStub->expects( $this->once() )->method( 'get' )
			->will( $this->returnValue( $ordProdItem ) );

		$this->custStub->expects( $this->once() )->method( 'save' )
			->with( $this->callback( function( $subject ) {
				return $subject->getGroups() === [];
			} ) );

		$object = new \Aimeos\Controller\Common\Subscription\Process\Processor\Cgroup\Standard( $this->context );
		$object->end( $this->getSubscription() );
	}


	public function testEndCustomGroups()
	{
		$this->custStub->expects( $this->once() )->method( 'save' )
			->with( $this->callback( function( $subject ) {
				return $subject->getGroups() === [];
			} ) );

		$object = new \Aimeos\Controller\Common\Subscription\Process\Processor\Cgroup\Standard( $this->context );
		$object->end( $this->getSubscription() );
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
