<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\Renew;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $aimeos;
	private $context;
	private $object;


	protected function setUp()
	{
		$this->aimeos = \TestHelperJobs::getAimeos();
		$this->context = \TestHelperJobs::getContext();

		$this->object = new \Aimeos\Controller\Jobs\Subscription\Process\Renew\Standard( $this->context, $this->aimeos );

		\Aimeos\MShop\Factory::setCache( true );
	}


	protected function tearDown()
	{
		\Aimeos\MShop\Factory::setCache( false );
		\Aimeos\MShop\Factory::clear();

		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Subscription process renew', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$this->assertEquals( 'Renews subscriptions at next date', $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->context->getConfig()->set( 'controller/common/subscription/process/processors', ['cgroup'] );
		$item = $this->getSubscription();

		$object = $this->getMockBuilder( '\\Aimeos\\Controller\\Jobs\\Subscription\\Process\\Renew\\Standard' )
			->setConstructorArgs( [$this->context, $this->aimeos] )
			->setMethods( ['createOrderBase', 'createOrderInvoice', 'createPayment'] )
			->getMock();

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['searchItems', 'saveItem'] )
			->getMock();

		\Aimeos\MShop\Factory::injectManager( $this->context, 'subscription', $managerStub );

		$object->expects( $this->once() )->method( 'createOrderBase' )
			->will( $this->returnValue( $this->getOrderBaseItem( $item->getOrderBaseId() ) ) );

		$object->expects( $this->once() )->method( 'createOrderInvoice' )
			->will( $this->returnValue( $this->getOrderItem() ) );

		$object->expects( $this->once() )->method( 'createPayment' );

		$managerStub->expects( $this->once() )->method( 'searchItems' )
			->will( $this->returnValue( [$item] ) );

		$managerStub->expects( $this->once() )->method( 'saveItem' );

		$object->run();
	}


	public function testRunException()
	{
		$this->context->getConfig()->set( 'controller/common/subscription/process/processors', ['cgroup'] );

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


	public function testAddCoupons()
	{
		$this->context->getConfig()->set( 'controller/jobs/subcription/process/renew/standard/use-coupons', true );

		$basket = \Aimeos\MShop\Factory::createManager( $this->context, 'order/base' )->createItem();
		$product = \Aimeos\MShop\Factory::createManager( $this->context, 'product' )->findItem( 'CNC', ['price'] );
		$orderProduct = \Aimeos\MShop\Factory::createManager( $this->context, 'order/base/product' )->createItem();

		$price = $product->getRefItems( 'price', 'default', 'default' );
		$basket->addProduct( $orderProduct->copyFrom( $product )->setPrice( reset( $price ) ) );

		$this->assertEquals( '600.00', $basket->getPrice()->getValue() );
		$this->assertEquals( '30.00', $basket->getPrice()->getCosts() );
		$this->assertEquals( '0.00', $basket->getPrice()->getRebate() );

		$basket = $this->access( 'addBasketCoupons' )->invokeArgs( $this->object, [$this->context, $basket, ['90AB']] );

		$this->assertEquals( 1, count( $basket->getCoupons() ) );
		$this->assertEquals( 2, count( $basket->getProducts() ) );
		$this->assertEquals( '537.00', $basket->getPrice()->getValue() );
		$this->assertEquals( '30.00', $basket->getPrice()->getCosts() );
		$this->assertEquals( '63.00', $basket->getPrice()->getRebate() );
	}


	public function testCreateOrderBase()
	{
		$item = $this->getSubscription();

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Base\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['store'] )
			->getMock();

		\Aimeos\MShop\Factory::injectManager( $this->context, 'order/base', $managerStub );

		$managerStub->expects( $this->once() )->method( 'store' );

		$this->access( 'createOrderBase' )->invokeArgs( $this->object, [$this->context, $item] );
	}


	public function testCreateOrderInvoice()
	{
		$item = $this->getSubscription();
		$baseItem = $this->getOrderBaseItem( $item->getOrderBaseId() );

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['saveItem'] )
			->getMock();

		\Aimeos\MShop\Factory::injectManager( $this->context, 'order', $managerStub );

		$managerStub->expects( $this->once() )->method( 'saveItem' );

		$this->access( 'createOrderInvoice' )->invokeArgs( $this->object, [$this->context, $baseItem] );
	}


	public function testCreatePayment()
	{
		$item = $this->getSubscription();
		$invoice = $this->getOrderItem();
		$baseItem = $this->getOrderBaseItem( $item->getOrderBaseId() );

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['saveItem'] )
			->getMock();

		\Aimeos\MShop\Factory::injectManager( $this->context, 'order', $managerStub );

		$managerStub->expects( $this->once() )->method( 'saveItem' );

		$this->access( 'createPayment' )->invokeArgs( $this->object, [$this->context, $baseItem, $invoice] );
	}


	protected function getOrderItem()
	{
		return \Aimeos\MShop\Factory::createManager( $this->context, 'order' )->createItem();
	}


	protected function getOrderBaseItem( $baseId )
	{
		return \Aimeos\MShop\Factory::createManager( $this->context, 'order/base' )->getItem( $baseId, ['order/base/service'] );
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


	protected function access( $name )
	{
		$class = new \ReflectionClass( '\Aimeos\Controller\Jobs\Subscription\Process\Renew\Standard' );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}
}
