<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2026
 */


namespace Aimeos\Controller\Jobs\Subscription\Export\Csv;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $aimeos;
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->aimeos = \TestHelper::getAimeos();
		$this->context = \TestHelper::context();

		$this->object = new \Aimeos\Controller\Jobs\Subscription\Export\Csv\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Subscription export CSV', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$this->assertEquals( 'Exports subscriptions to CSV file', $this->object->getDescription() );
	}


	public function testRun()
	{
		$mqmStub = $this->getMockBuilder( '\\Aimeos\\Base\\MQueue\\Manager\\Standard' )
			->setConstructorArgs( [[]] )
			->onlyMethods( ['get'] )
			->getMock();

		$mqStub = $this->getMockBuilder( '\\Aimeos\\Base\\MQueue\\Standard' )
			->disableOriginalConstructor()
			->onlyMethods( ['getQueue'] )
			->getMock();

		$queueStub = $this->getMockBuilder( '\\Aimeos\\Base\\MQueue\\Queue\\Standard' )
			->disableOriginalConstructor()
			->onlyMethods( ['del', 'get'] )
			->getMock();

		$msgStub = $this->getMockBuilder( '\\Aimeos\\Base\\MQueue\\Message\\Standard' )
			->disableOriginalConstructor()
			->onlyMethods( ['getBody'] )
			->getMock();


		$this->context->setMessageQueueManager( $mqmStub );


		$mqmStub->expects( $this->once() )->method( 'get' )
			->willReturn( $mqStub );

		$mqStub->expects( $this->once() )->method( 'getQueue' )
			->willReturn( $queueStub );

		$queueStub->expects( $this->exactly( 2 ) )->method( 'get' )
			->willReturn( $msgStub, null );

		$queueStub->expects( $this->once() )->method( 'del' );

		$msgStub->expects( $this->once() )->method( 'getBody' )
			->willReturn( '{"sitecode":"unittest"}' );


		$this->object->run();


		$jobManager = \Aimeos\MAdmin::create( $this->context, 'job' );
		$jobSearch = $jobManager->filter();
		$jobSearch->setConditions( $jobSearch->compare( '=~', 'job.label', 'subscription-export_' ) );
		$jobItems = $jobManager->search( $jobSearch );
		$jobManager->delete( $jobItems->toArray() );

		$this->assertEquals( 1, count( $jobItems ) );


		$filename = dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/tmp/' . $jobItems->first()->getLabel();
		$fp = fopen( $filename, 'r' );

		$subscription = fgetcsv( $fp, null, ',', '"', '' );
		$address1 = fgetcsv( $fp, null, ',', '"', '' );
		$address2 = fgetcsv( $fp, null, ',', '"', '' );
		$product1 = fgetcsv( $fp, null, ',', '"', '' );

		fclose( $fp );
		unlink( $filename );

		$this->assertEquals( 'subscription', $subscription[0] );
		$this->assertEquals( 'address', $address1[0] );
		$this->assertEquals( 'address', $address2[0] );
		$this->assertEquals( 'product', $product1[0] );
	}

	public function testRenderEscaping()
	{
		$address = \Aimeos\MShop::create( $this->context, 'order/address' )->create()
			->setCompany( '=1+1' )->setAddress1( "a\"b\nc" );

		$order = \Aimeos\MShop::create( $this->context, 'order' )->create()->addAddress( $address, 'payment' );
		$item = \Aimeos\MShop::create( $this->context, 'subscription' )->create()->set( '.orderitem', $order );

		$method = new \ReflectionMethod( $this->object, 'render' );
		$fp = fopen( 'php://memory', 'w+' );
		fwrite( $fp, $method->invoke( $this->object, [$item] ) );
		rewind( $fp );

		$subscription = fgetcsv( $fp, null, ',', '"', '' );
		$address = fgetcsv( $fp, null, ',', '"', '' );
		$end = fgetcsv( $fp, null, ',', '"', '' );
		fclose( $fp );

		$this->assertEquals( 'subscription', $subscription[0] );
		$this->assertEquals( 23, count( $address ) );
		$this->assertEquals( "'=1+1", $address[4] );
		$this->assertEquals( "a\"b\nc", $address[9] );
		$this->assertFalse( $end );
	}
}
