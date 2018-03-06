<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
 */


namespace Aimeos\Controller\Jobs\Subscription\Export\Csv;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp()
	{
		$this->context = \TestHelperJobs::getContext();
		$aimeos = \TestHelperJobs::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Subscription\Export\Csv\Standard( $this->context, $aimeos );
	}


	protected function tearDown()
	{
		unset( $this->object, $this->context );
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


		$jobManager = \Aimeos\MAdmin\Factory::createManager( $this->context, 'job' );
		$jobSearch = $jobManager->createSearch();
		$jobSearch->setConditions( $jobSearch->compare( '=~', 'job.label', 'subscription-export_' ) );
		$jobItems = $jobManager->searchItems( $jobSearch );
		$jobManager->deleteItems( array_keys( $jobItems ) );

		$this->assertEquals( 1, count( $jobItems ) );


		$filename = dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/tmp/' . reset( $jobItems )->getLabel();
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
}
