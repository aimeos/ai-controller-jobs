<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2026
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
		$mailerMock = $this->getMockBuilder( '\\Aimeos\\Base\\Mail\\Manager\\None' )
			->disableOriginalConstructor()
			->getMock();

		$mailMock = $this->getMockBuilder( '\\Aimeos\\Base\\Mail\\None' )
			->disableOriginalConstructor()
			->getMock();

		$mailMsgMock = $this->getMockBuilder( '\\Aimeos\\Base\\Mail\\Message\\None' )
			->disableOriginalConstructor()
			->disableOriginalClone()
			->onlyMethods( ['send'] )
			->getMock();

		$mailerMock->expects( $this->once() )->method( 'get' )->willReturn( $mailMock );
		$mailMock->expects( $this->once() )->method( 'create' )->willReturn( $mailMsgMock );
		$mailMsgMock->expects( $this->once() )->method( 'send' );

		$this->context->setMail( $mailerMock );
		$this->mockQueue( '{"customer.languageid": "de"}' );

		$object = new \Aimeos\Controller\Jobs\Customer\Email\Account\Standard( $this->context, $this->aimeos );
		$object->run();
	}


	public function testRunException()
	{
		$this->mockQueue( 'error' );
		$this->object->run();
	}


	public function testViewIdn()
	{
		$address = \Aimeos\MShop::create( $this->context, 'customer/address' )->create()->setLanguageId( 'de' );

		$method = new \ReflectionMethod( \Aimeos\Controller\Jobs\Customer\Email\Account\Standard::class, 'view' );
		$view = $method->invokeArgs( $this->object, [$address] );
		$view->account = 'test@xn--mller-kva.de';

		$this->assertStringContainsString( "test@m\u{fc}ller.de", $view->render( 'customer/email/account/text' ) );
		$this->assertStringContainsString( "test@m\u{fc}ller.de", $view->render( 'customer/email/account/html' ) );
	}


	protected function mockQueue( string $body )
	{
		$queueMock = $this->createMock( '\\Aimeos\\Base\\MQueue\\Queue\\Standard' );
		$queueMock->expects( $this->exactly( 2 ) )->method( 'get' )
			->willReturn( new \Aimeos\Base\MQueue\Message\Standard( ['message' => $body] ), null );
		$queueMock->expects( $this->once() )->method( 'del' );

		$mqueueMock = $this->createMock( '\\Aimeos\\Base\\MQueue\\Standard' );
		$mqueueMock->expects( $this->once() )->method( 'getQueue' )
			->with( 'customer/email/account/' . $this->context->locale()->getSiteId() )
			->willReturn( $queueMock );

		$managerStub = $this->createStub( '\\Aimeos\\Base\\MQueue\\Manager\\Standard' );
		$managerStub->method( 'get' )->willReturn( $mqueueMock );

		$this->context->setMessageQueueManager( $managerStub );
	}
}
