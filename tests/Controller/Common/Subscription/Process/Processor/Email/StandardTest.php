<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2023
 */

namespace Aimeos\Controller\Common\Subscription\Process\Processor\Email;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
	}


	public function testRenewAfter()
	{
		$context = \TestHelper::context();

		$mailStub = $this->getMockBuilder( '\\Aimeos\\Base\\Mail\\None' )
			->disableOriginalConstructor()
			->getMock();

		$mailMsgStub = $this->getMockBuilder( '\\Aimeos\\Base\\Mail\\Message\\None' )
			->disableOriginalConstructor()
			->disableOriginalClone()
			->getMock();

		$mailStub->expects( $this->once() )->method( 'create' )->will( $this->returnValue( $mailMsgStub ) );

		$context->setMail( $mailStub );
		$subscription = $this->getSubscription()->setReason( \Aimeos\MShop\Subscription\Item\Iface::REASON_PAYMENT );

		$object = new \Aimeos\Controller\Common\Subscription\Process\Processor\Email\Standard( $context );
		$object->renewAfter( $subscription, $subscription->getOrderItem() );
	}


	public function testEnd()
	{
		$context = \TestHelper::context();

		$mailStub = $this->getMockBuilder( '\\Aimeos\\Base\\Mail\\None' )
			->disableOriginalConstructor()
			->getMock();

		$mailMsgStub = $this->getMockBuilder( '\\Aimeos\\Base\\Mail\\Message\\None' )
			->disableOriginalConstructor()
			->disableOriginalClone()
			->getMock();

		$mailStub->expects( $this->once() )->method( 'create' )->will( $this->returnValue( $mailMsgStub ) );

		$subscription = $this->getSubscription();
		$context->setMail( $mailStub );

		$object = new \Aimeos\Controller\Common\Subscription\Process\Processor\Email\Standard( $context );
		$object->end( $subscription, $subscription->getOrderItem() );
	}


	protected function getSubscription()
	{
		$manager = \Aimeos\MShop::create( \TestHelper::context(), 'subscription' );
		$search = $manager->filter()->add( ['subscription.dateend' => '2010-01-01'] );
		$domains = ['order', 'order/address', 'order/coupon', 'order/product', 'order/service'];

		return $manager->search( $search, $domains )->first( new \Exception( 'No subscription item found' ) );
	}
}
