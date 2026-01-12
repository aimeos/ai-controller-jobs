<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2026
 */

namespace Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Cgroup;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $custStub;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();

		$this->custStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Customer\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['get', 'save'] )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Customer\\Manager\\Standard', $this->custStub );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->context );
	}


	public function testBegin()
	{
		$ordProdStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Product\\Standard' )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['get', 'type'] )
			->getMock();

		$ordProdStub->method( 'type' )->willReturn( ['order', 'product'] );

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Product\\Standard', $ordProdStub );

		$subscription = $this->getSubscription();
		$ordProdAttrManager = $ordProdStub->getSubManager( 'attribute' );
		$ordProdAttrItem = $ordProdAttrManager->create()->setType( 'hidden' )->setCode( 'group' );

		$ordProdItem = $ordProdStub->create()->setAttributeItems( [
			( clone $ordProdAttrItem )->setAttributeId( 10 )->setValue( '3' ),
			( clone $ordProdAttrItem )->setAttributeId( 11 )->setValue( '4' ),
		] );

		$ordProdStub->expects( $this->once() )->method( 'get' )
			->willReturn( $ordProdItem );

		$this->custStub->expects( $this->once() )->method( 'get' )
			->willReturn( $this->custStub->create() );

		$this->custStub->expects( $this->once() )->method( 'save' )
			->with( $this->callback( function( $subject ) {
				return $subject->getGroups() === ['3', '4'];
			} ) );


		$object = new \Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Cgroup\Standard( $this->context );
		$object->begin( $subscription, $subscription->getOrderItem() );
	}


	public function testBeginCustomGroups()
	{
		$this->custStub->expects( $this->once() )->method( 'get' )
			->willReturn( $this->custStub->create()->setGroups( ['1', '2'] ) );

		$this->custStub->expects( $this->once() )->method( 'save' )
			->with( $this->callback( function( $subject ) {
				return $subject->getGroups() === ['1', '2'];
			} ) );

		$subscription = $this->getSubscription();

		$object = new \Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Cgroup\Standard( $this->context );
		$object->begin( $subscription, $subscription->getOrderItem() );
	}


	public function testEnd()
	{
		$ordProdStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Product\\Standard' )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['get', 'type'] )
			->getMock();

		$ordProdStub->method( 'type' )->willReturn( ['order', 'product'] );

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Product\\Standard', $ordProdStub );

		$subscription = $this->getSubscription();
		$ordProdAttrManager = $ordProdStub->getSubManager( 'attribute' );
		$ordProdAttrItem = $ordProdAttrManager->create()->setType( 'hidden' )->setCode( 'group' );

		$ordProdItem = $ordProdStub->create()->setAttributeItems( [
			( clone $ordProdAttrItem )->setAttributeId( 10 )->setValue( '3' ),
			( clone $ordProdAttrItem )->setAttributeId( 11 )->setValue( '4' ),
		] );

		$ordProdStub->expects( $this->once() )->method( 'get' )
			->willReturn( $ordProdItem );

		$this->custStub->expects( $this->once() )->method( 'get' )
			->willReturn( $this->custStub->create() );

		$this->custStub->expects( $this->once() )->method( 'save' )
			->with( $this->callback( function( $subject ) {
				return $subject->getGroups() === [];
			} ) );

		$object = new \Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Cgroup\Standard( $this->context );
		$object->end( $subscription, $subscription->getOrderItem() );
	}


	public function testEndCustomGroups()
	{
		$this->custStub->expects( $this->once() )->method( 'get' )
			->willReturn( $this->custStub->create() );

		$this->custStub->expects( $this->once() )->method( 'save' )
			->with( $this->callback( function( $subject ) {
				return $subject->getGroups() === [];
			} ) );

		$subscription = $this->getSubscription();

		$object = new \Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Cgroup\Standard( $this->context );
		$object->end( $subscription, $subscription->getOrderItem() );
	}


	protected function getSubscription()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'subscription' );
		$search = $manager->filter()->add( ['subscription.dateend' => '2010-01-01'] );
		$domains = ['order', 'order/address', 'order/coupon', 'order/product', 'order/service'];

		return $manager->search( $search, $domains )->first( new \Exception( 'No subscription item found' ) );
	}
}
