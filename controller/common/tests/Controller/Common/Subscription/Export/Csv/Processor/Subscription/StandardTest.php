<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2021
 */

namespace Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Subscription;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	public function testProcess()
	{
		$context = \TestHelperCntl::getContext();
		$mapping = array(
			0 => 'subscription.interval',
			1 => 'subscription.datenext',
			2 => 'subscription.dateend',
		);


		$object = new \Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Subscription\Standard( $context, $mapping );

		$subscription = $this->getSubscription( $context );
		$order = \Aimeos\MShop::create( $context, 'order/base' )->load( $subscription->getOrderBaseId() );

		$data = $object->process( $subscription, $order );


		$this->assertEquals( 1, count( $data ) );

		$this->assertEquals( 3, count( $data[0] ) );
		$this->assertEquals( 'P0Y1M0W0D', $data[0][0] );
		$this->assertEquals( '2000-01-01', $data[0][1] );
		$this->assertEquals( '2010-01-01', $data[0][2] );
	}


	protected function getSubscription( $context )
	{
		$manager = \Aimeos\MShop::create( $context, 'subscription' );

		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'subscription.dateend', '2010-01-01' ) );

		if( ( $item = $manager->search( $search )->first() ) !== null ) {
			return $item;
		}

		throw new \Exception( 'No subscription item found' );
	}
}
