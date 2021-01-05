<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2021
 */


namespace Aimeos\Controller\Jobs\Subscription\Export\Csv;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $aimeos;
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->aimeos = \TestHelperJobs::getAimeos();
		$this->context = \TestHelperJobs::getContext();

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
		$mqmStub = $this->getMockBuilder( '\\Aimeos\\MW\\MQueue\\Manager\\Standard' )
			->setConstructorArgs( [$this->context->getConfig()] )
			->setMethods( ['get'] )
			->getMock();

		$mqStub = $this->getMockBuilder( '\\Aimeos\\MW\\MQueue\\Standard' )
			->disableOriginalConstructor()
			->setMethods( ['getQueue'] )
			->getMock();

		$queueStub = $this->getMockBuilder( '\\Aimeos\\MW\\MQueue\\Queue\\Standard' )
			->disableOriginalConstructor()
			->setMethods( ['del', 'get'] )
			->getMock();

		$msgStub = $this->getMockBuilder( '\\Aimeos\\MW\\MQueue\\Message\\Standard' )
			->disableOriginalConstructor()
			->setMethods( ['getBody'] )
			->getMock();


		$this->context->setMessageQueueManager( $mqmStub );


		$mqmStub->expects( $this->once() )->method( 'get' )
			->will( $this->returnValue( $mqStub ) );

		$mqStub->expects( $this->once() )->method( 'getQueue' )
			->will( $this->returnValue( $queueStub ) );

		$queueStub->expects( $this->exactly( 2 ) )->method( 'get' )
			->will( $this->onConsecutiveCalls( $msgStub, null ) );

		$queueStub->expects( $this->once() )->method( 'del' );

		$msgStub->expects( $this->once() )->method( 'getBody' )
			->will( $this->returnValue( '{"sitecode":"unittest"}' ) );


		$this->object->run();


		$jobManager = \Aimeos\MAdmin::create( $this->context, 'job' );
		$jobSearch = $jobManager->filter();
		$jobSearch->setConditions( $jobSearch->compare( '=~', 'job.label', 'subscription-export_' ) );
		$jobItems = $jobManager->search( $jobSearch );
		$jobManager->delete( $jobItems->toArray() );

		$this->assertEquals( 1, count( $jobItems ) );


		$filename = dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/tmp/' . $jobItems->first()->getLabel();
		$fp = fopen( $filename, 'r' );

		$subscription = fgetcsv( $fp );
		$address1 = fgetcsv( $fp );
		$address2 = fgetcsv( $fp );
		$product1 = fgetcsv( $fp );

		fclose( $fp );
		unlink( $filename );

		$this->assertEquals( 'subscription', $subscription[0] );
		$this->assertEquals( 'address', $address1[0] );
		$this->assertEquals( 'address', $address2[0] );
		$this->assertEquals( 'product', $product1[0] );
	}


	public function testRunCollapsedLines()
	{
		$mqmStub = $this->getMockBuilder( '\\Aimeos\\MW\\MQueue\\Manager\\Standard' )
			->setConstructorArgs( [$this->context->getConfig()] )
			->setMethods( ['get'] )
			->getMock();

		$mqStub = $this->getMockBuilder( '\\Aimeos\\MW\\MQueue\\Standard' )
			->disableOriginalConstructor()
			->setMethods( ['getQueue'] )
			->getMock();

		$queueStub = $this->getMockBuilder( '\\Aimeos\\MW\\MQueue\\Queue\\Standard' )
			->disableOriginalConstructor()
			->setMethods( ['del', 'get'] )
			->getMock();

		$msgStub = $this->getMockBuilder( '\\Aimeos\\MW\\MQueue\\Message\\Standard' )
			->disableOriginalConstructor()
			->setMethods( ['getBody'] )
			->getMock();


		$this->context->setMessageQueueManager( $mqmStub );
		$this->context->getConfig()->set( 'controller/jobs/subscription/export/csv/collapse', true );
		$this->context->getConfig()->set( 'controller/jobs/subscription/export/csv/mapping', [
			'subscription' => array(
				0 => 'subscription.interval',
				1 => 'subscription.period',
				2 => 'subscription.ordbaseid',
			),
			'address' => array(
				3 => 'order.base.address.firstname',
				4 => 'order.base.address.lastname',
			),
			'product' => array(
				5 => 'order.base.product.prodcode',
				6 => 'order.base.product.price',
			),
		] );


		$mqmStub->expects( $this->once() )->method( 'get' )
			->will( $this->returnValue( $mqStub ) );

		$mqStub->expects( $this->once() )->method( 'getQueue' )
			->will( $this->returnValue( $queueStub ) );

		$queueStub->expects( $this->exactly( 2 ) )->method( 'get' )
			->will( $this->onConsecutiveCalls( $msgStub, null ) );

		$queueStub->expects( $this->once() )->method( 'del' );

		$msgStub->expects( $this->once() )->method( 'getBody' )
			->will( $this->returnValue( '{"sitecode":"unittest"}' ) );


		$object = new \Aimeos\Controller\Jobs\Subscription\Export\Csv\Standard( $this->context, $this->aimeos );
		$object->run();


		$jobManager = \Aimeos\MAdmin::create( $this->context, 'job' );
		$jobSearch = $jobManager->filter();
		$jobSearch->setConditions( $jobSearch->compare( '=~', 'job.label', 'subscription-export_' ) );
		$jobItems = $jobManager->search( $jobSearch );
		$jobManager->delete( $jobItems->toArray() );

		$this->assertEquals( 1, count( $jobItems ) );


		$filename = dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/tmp/' . $jobItems->first()->getLabel();
		$fp = fopen( $filename, 'r' );

		$line1 = fgetcsv( $fp );
		$line2 = fgetcsv( $fp );
		$line3 = fgetcsv( $fp );

		fclose( $fp );
		unlink( $filename );

		$this->assertEquals( 'P0Y1M0W0D', $line1[0] );
		$this->assertEquals( 'Unittest', $line1[4] );
		$this->assertEquals( 'CNE', $line1[5] );
		$this->assertEquals( 'P1Y0M0W0D', $line2[0] );
		$this->assertFalse( $line3 );
	}
}
