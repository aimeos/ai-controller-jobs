<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2026
 */


namespace Aimeos\Controller\Jobs\Order\Export\Csv;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Order\Export\Csv\Standard( $this->context, $aimeos );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Order export CSV', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$this->assertEquals( 'Exports orders to CSV file', $this->object->getDescription() );
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
		$jobSearch->setConditions( $jobSearch->compare( '=~', 'job.label', 'order-export_' ) );
		$jobItems = $jobManager->search( $jobSearch );
		$jobManager->delete( $jobItems->toArray() );

		$this->assertEquals( 1, count( $jobItems ) );


		$filename = dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/tmp/' . $jobItems->first()->getLabel();
		$fp = fopen( $filename, 'r' );

		$invoice = fgetcsv( $fp, null, ',', '"', '' );
		$address1 = fgetcsv( $fp, null, ',', '"', '' );
		$address2 = fgetcsv( $fp, null, ',', '"', '' );
		$service1 = fgetcsv( $fp, null, ',', '"', '' );
		$service2 = fgetcsv( $fp, null, ',', '"', '' );
		$coupon1 = fgetcsv( $fp, null, ',', '"', '' );
		$coupon2 = fgetcsv( $fp, null, ',', '"', '' );
		$product1 = fgetcsv( $fp, null, ',', '"', '' );
		$product2 = fgetcsv( $fp, null, ',', '"', '' );
		$product3 = fgetcsv( $fp, null, ',', '"', '' );
		$product4 = fgetcsv( $fp, null, ',', '"', '' );

		fclose( $fp );
		unlink( $filename );

		$this->assertEquals( 'invoice', $invoice[0] );
		$this->assertEquals( 'address', $address1[0] );
		$this->assertEquals( 'address', $address2[0] );
		$this->assertEquals( 'service', $service1[0] );
		$this->assertEquals( 'service', $service2[0] );
		$this->assertEquals( 'coupon', $coupon1[0] );
		$this->assertEquals( 'coupon', $coupon2[0] );
		$this->assertEquals( 'product', $product1[0] );
		$this->assertEquals( 'product', $product2[0] );
		$this->assertEquals( 'product', $product3[0] );
		$this->assertEquals( 'product', $product4[0] );
	}
}
