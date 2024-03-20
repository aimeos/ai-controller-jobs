<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2024
 */


namespace Aimeos\Controller\Jobs\Customer\Email\Account;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	protected function setUp() : void
	{
		$this->context = \TestHelper::context();
		$this->aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Customer\Email\Account\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		$this->object = null;
	}


	public function testGetName()
	{
		$this->assertEquals( 'Customer account e-mails', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Sends e-mails for new customer accounts';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
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


		$queueStub = $this->getMockBuilder( '\\Aimeos\\Base\\MQueue\\Queue\\Standard' )
			->disableOriginalConstructor()
			->getMock();

		$queueStub->expects( $this->exactly( 2 ) )->method( 'get' )
			->willReturn( new \Aimeos\Base\MQueue\Message\Standard( array( 'message' => '{"customer.languageid": "de"}' ) ), null );

		$queueStub->expects( $this->once() )->method( 'del' );


		$mqueueStub = $this->getMockBuilder( '\\Aimeos\\Base\\MQueue\\Standard' )
			->disableOriginalConstructor()
			->getMock();

		$mqueueStub->expects( $this->once() )->method( 'getQueue' )
			->willReturn( $queueStub );


		$managerStub = $this->getMockBuilder( '\\Aimeos\\Base\\MQueue\\Manager\\Standard' )
			->disableOriginalConstructor()
			->getMock();

		$managerStub->expects( $this->once() )->method( 'get' )
			->willReturn( $mqueueStub );

		$this->context->setMessageQueueManager( $managerStub );


		$object = new \Aimeos\Controller\Jobs\Customer\Email\Account\Standard( $this->context, $this->aimeos );
		$object->run();
	}


	public function testRunException()
	{
		$queueStub = $this->getMockBuilder( '\\Aimeos\\Base\\MQueue\\Queue\\Standard' )
			->disableOriginalConstructor()
			->getMock();

		$queueStub->expects( $this->exactly( 2 ) )->method( 'get' )
			->willReturn( new \Aimeos\Base\MQueue\Message\Standard( array( 'message' => 'error' ) ), null );

		$queueStub->expects( $this->once() )->method( 'del' );


		$mqueueStub = $this->getMockBuilder( '\\Aimeos\\Base\\MQueue\\Standard' )
			->disableOriginalConstructor()
			->getMock();

		$mqueueStub->expects( $this->once() )->method( 'getQueue' )
			->willReturn( $queueStub );


		$managerStub = $this->getMockBuilder( '\\Aimeos\\Base\\MQueue\\Manager\\Standard' )
			->disableOriginalConstructor()
			->getMock();

		$managerStub->expects( $this->once() )->method( 'get' )
			->willReturn( $mqueueStub );

		$this->context->setMessageQueueManager( $managerStub );


		$this->object->run();
	}
}
