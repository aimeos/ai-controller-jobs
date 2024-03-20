<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2024
 */


namespace Aimeos\Controller\Jobs\Order\Email\Payment;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Order\Email\Payment\Standard( $this->context, $aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Order payment related e-mails', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Sends order confirmation or payment status update e-mails';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['search'] )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Standard', $orderManagerStub );

		$orderItem = $orderManagerStub->create();

		$orderManagerStub->expects( $this->exactly( 4 ) )->method( 'search' )
			->willReturn( map( [$orderItem] ), map(), map(), map() );

		$object = $this->getMockBuilder( \Aimeos\Controller\Jobs\Order\Email\Payment\Standard::class )
			->setConstructorArgs( [$this->context, \TestHelper::getAimeos()] )
			->onlyMethods( ['notify'] )
			->getMock();

		$object->expects( $this->exactly( 4 ) )->method( 'notify' );

		$object->run();
	}


	public function testAddress()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$addrManager = \Aimeos\MShop::create( $this->context, 'order/address' );

		$item = $manager->create();
		$item->addAddress( $addrManager->create()->setEmail( 'a@b.com' ), 'payment' );
		$item->addAddress( $addrManager->create()->setEmail( 'a@b.com' ), 'delivery' );

		$result = $this->access( 'address' )->invokeArgs( $this->object, [$item] );

		$this->assertInstanceof( \Aimeos\MShop\Order\Item\Address\Iface::class, $result );
	}


	public function testAddressNone()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );

		$this->expectException( \Aimeos\Controller\Jobs\Exception::class );
		$this->access( 'address' )->invokeArgs( $this->object, [$manager->create()] );
	}


	public function testNotify()
	{
		$object = $this->getMockBuilder( \Aimeos\Controller\Jobs\Order\Email\Payment\Standard::class )
			->setConstructorArgs( [$this->context, \TestHelper::getAimeos()] )
			->onlyMethods( ['update', 'send'] )
			->getMock();

		$object->expects( $this->once() )->method( 'update' );
		$object->expects( $this->once() )->method( 'send' );


		$orderItem = \Aimeos\MShop::create( $this->context, 'order' )->create();

		$this->access( 'notify' )->invokeArgs( $object, [map( [$orderItem] ), -1] );
	}


	public function testNotifyException()
	{
		$object = $this->getMockBuilder( \Aimeos\Controller\Jobs\Order\Email\Payment\Standard::class )
			->setConstructorArgs( [$this->context, \TestHelper::getAimeos()] )
			->onlyMethods( ['send'] )
			->getMock();

		$object->expects( $this->once() )->method( 'send' )->will( $this->throwException( new \RuntimeException() ) );

		$orderItem = \Aimeos\MShop::create( $this->context, 'order' )->create();

		$this->access( 'notify' )->invokeArgs( $object, [map( [$orderItem] ), -1] );
	}


	public function testSend()
	{
		$mailStub = $this->getMockBuilder( '\\Aimeos\\Base\\Mail\\None' )
			->disableOriginalConstructor()
			->getMock();

		$mailMsgStub = $this->getMockBuilder( '\\Aimeos\\Base\\Mail\\Message\\None' )
			->disableOriginalConstructor()
			->disableOriginalClone()
			->onlyMethods( ['send'] )
			->getMock();

		$mailStub->expects( $this->once() )->method( 'create' )->willReturn( $mailMsgStub );
		$mailMsgStub->expects( $this->once() )->method( 'send' );

		$this->context->setMail( $mailStub );


		$object = $this->getMockBuilder( \Aimeos\Controller\Jobs\Order\Email\Payment\Standard::class )
			->setConstructorArgs( [$this->context, \TestHelper::getAimeos()] )
			->onlyMethods( ['update'] )
			->getMock();

		$addrItem = \Aimeos\MShop::create( $this->context, 'order/address' )->create()->setEmail( 'a@b.com' );
		$orderItem = \Aimeos\MShop::create( $this->context, 'order' )->create( ['order.ctime' => '2000-01-01 00:00:00'] );

		$orderItem->addAddress( $addrItem, 'payment' );

		$this->access( 'send' )->invokeArgs( $object, [$orderItem] );
	}


	public function testView()
	{
		$orderItem = \Aimeos\MShop::create( $this->context, 'order' )->create();
		$addrItem = \Aimeos\MShop::create( $this->context, 'order/address' )->create()->setEmail( 'a@b.com' );

		$result = $this->access( 'view' )->invokeArgs( $this->object, [$orderItem->addAddress( $addrItem, 'payment' )] );

		$this->assertInstanceof( \Aimeos\Base\View\Iface::class, $result );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( \Aimeos\Controller\Jobs\Order\Email\Payment\Standard::class );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}
}
