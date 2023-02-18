<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2023
 */


namespace Aimeos\Controller\Jobs\Order\Email\Voucher;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$aimeos = \TestHelper::getAimeos();
		$this->context = \TestHelper::context();

		$codeManager = $this->getMockBuilder( '\\Aimeos\\MShop\\Coupon\\Manager\\Code\\Standard' )
			->setConstructorArgs( array( $this->context ) )
			->onlyMethods( array( 'save' ) )
			->getMock();

		$codeManager->expects( $this->any() )->method( 'save' );
		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Coupon\\Manager\\Code\\Standard', $codeManager );

		$this->object = new \Aimeos\Controller\Jobs\Order\Email\Voucher\Standard( $this->context, $aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Voucher related e-mails', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Sends the e-mail with the voucher to the customer';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$orderManagerStub = $this->getMockBuilder( \Aimeos\MShop\Order\Manager\Standard::class )
			->setConstructorArgs( array( $this->context ) )
			->onlyMethods( ['search'] )
			->getMock();

		\Aimeos\MShop::inject( \Aimeos\MShop\Order\Manager\Standard::class, $orderManagerStub );

		$orderItem = $orderManagerStub->create();

		$orderManagerStub->expects( $this->once() )->method( 'search' )
			->will( $this->returnValue( map( [$orderItem] ) ) );

		$object = $this->getMockBuilder( \Aimeos\Controller\Jobs\Order\Email\Voucher\Standard::class )
			->setConstructorArgs( array( $this->context, \TestHelper::getAimeos() ) )
			->onlyMethods( ['notify'] )
			->getMock();

		$object->expects( $this->once() )->method( 'notify' );

		$object->run();
	}


	public function testAddress()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$addrManager = \Aimeos\MShop::create( $this->context, 'order/address' );

		$item = $manager->create();
		$item->addAddress( $addrManager->create()->setEmail( 'a@b.c' ), \Aimeos\MShop\Order\Item\Address\Base::TYPE_PAYMENT );
		$item->addAddress( $addrManager->create(), \Aimeos\MShop\Order\Item\Address\Base::TYPE_DELIVERY );

		$result = $this->access( 'address' )->invokeArgs( $this->object, [$item] );

		$this->assertInstanceof( \Aimeos\MShop\Order\Item\Address\Iface::class, $result );
	}


	public function testAddressNone()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );

		$this->expectException( \Aimeos\Controller\Jobs\Exception::class );
		$this->access( 'address' )->invokeArgs( $this->object, [$manager->create()] );
	}


	public function testCouponId()
	{
		$this->assertGreaterThan( 0, $this->access( 'couponId' )->invokeArgs( $this->object, [] ) );
	}


	public function testCreateCoupons()
	{
		$orderProductItem = \Aimeos\MShop::create( $this->context, 'order/product' )->create();

		$object = $this->getMockBuilder( \Aimeos\Controller\Jobs\Order\Email\Voucher\Standard::class )
			->setConstructorArgs( [$this->context, \TestHelper::getAimeos()] )
			->onlyMethods( ['saveCoupons'] )
			->getMock();

		$object->expects( $this->once() )->method( 'saveCoupons' );

		$result = $this->access( 'createCoupons' )->invokeArgs( $object, [map( $orderProductItem )] );

		$this->assertEquals( 1, $result->count() );
		$this->assertEquals( 1, count( $result->first()->getAttribute( 'coupon-code', 'coupon' ) ) );
	}


	public function testNotify()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$domains = ['order', 'order/address', 'order/product'];

		$order = $manager->search( $manager->filter()->slice( 0, 1 ), $domains )
			->first( new \RuntimeException( 'No orders available' ) );

		$object = $this->getMockBuilder( \Aimeos\Controller\Jobs\Order\Email\Voucher\Standard::class )
			->setConstructorArgs( [$this->context, \TestHelper::getAimeos()] )
			->onlyMethods( ['createCoupons', 'send', 'status'] )
			->getMock();

		$object->expects( $this->once() )->method( 'createCoupons' )->will( $this->returnValue( map() ) );
		$object->expects( $this->once() )->method( 'status' );
		$object->expects( $this->once() )->method( 'send' );

		$this->access( 'notify' )->invokeArgs( $object, [map( [$order] )] );
	}


	public function testNotifyException()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $manager->search( $manager->filter()->slice( 0, 1 ), ['order', 'order/product'] )->first();

		$object = $this->getMockBuilder( \Aimeos\Controller\Jobs\Order\Email\Voucher\Standard::class )
			->setConstructorArgs( [$this->context, \TestHelper::getAimeos()] )
			->onlyMethods( ['products'] )
			->getMock();

		$object->expects( $this->once() )->method( 'products' )->will( $this->throwException( new \RuntimeException() ) );

		$this->access( 'notify' )->invokeArgs( $object, [map( [$order] )] );
	}


	public function testSaveCoupons()
	{
		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Coupon\\Manager\\Code\\Standard' )
			->setConstructorArgs( array( $this->context ) )
			->onlyMethods( ['save'] )
			->getMock();

		$managerStub->expects( $this->once() )->method( 'save' );

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Coupon\\Manager\\Code\\Standard', $managerStub );

		$this->access( 'saveCoupons' )->invokeArgs( $this->object, [['test' => 1]] );
	}


	public function testSend()
	{
		$address = \Aimeos\MShop::create( $this->context, 'order/address' )->create()->setEmail( 'a@b.com' );
		$product = \Aimeos\MShop::create( $this->context, 'order/product' )->create()->setProductCode( 'voucher-test' );


		$mailStub = $this->getMockBuilder( '\\Aimeos\\Base\\Mail\\None' )
			->disableOriginalConstructor()
			->getMock();

		$mailMsgStub = $this->getMockBuilder( '\\Aimeos\\Base\\Mail\\Message\\None' )
			->disableOriginalConstructor()
			->disableOriginalClone()
			->onlyMethods( ['send'] )
			->getMock();

		$mailStub->expects( $this->once() )->method( 'create' )->will( $this->returnValue( $mailMsgStub ) );
		$mailMsgStub->expects( $this->once() )->method( 'send' );

		$this->context->setMail( $mailStub );


		$object = $this->getMockBuilder( \Aimeos\Controller\Jobs\Order\Email\Voucher\Standard::class )
			->setConstructorArgs( [$this->context, \TestHelper::getAimeos()] )
			->onlyMethods( [] )
			->getMock();

		$this->access( 'createCoupons' )->invokeArgs( $object, [map( $product )] );
		$this->access( 'send' )->invokeArgs( $object, [$this->context->view(), map( $product ), $address] );
	}


	public function testStatus()
	{
		$statusManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Status\\Standard' )
			->setConstructorArgs( array( $this->context ) )
			->onlyMethods( ['save'] )
			->getMock();

		$statusManagerStub->expects( $this->once() )->method( 'save' );

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Status\\Standard', $statusManagerStub );

		$this->access( 'status' )->invokeArgs( $this->object, array( -1, +1 ) );
	}


	public function testView()
	{
		$base = \Aimeos\MShop::create( $this->context, 'order' )->create();
		$address = \Aimeos\MShop::create( $this->context, 'order/address' )->create();
		$base->addAddress( $address->setLanguageId( 'de' )->setEmail( 'a@b.com' ), 'delivery' );

		$result = $this->access( 'view' )->invokeArgs( $this->object, [$base] );
		$this->assertInstanceof( \Aimeos\Base\View\Iface::class, $result );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( \Aimeos\Controller\Jobs\Order\Email\Voucher\Standard::class );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}
}
