<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2022
 */

namespace Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Subscription;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	public function testProcess()
	{
		$context = \TestHelper::context();
		$mapping = array(
			0 => 'subscription.interval',
			1 => 'subscription.datenext',
			2 => 'subscription.dateend',
		);


		$object = new \Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Subscription\Standard( $context, $mapping );
		$data = $object->process( $this->getSubscription( $context ) );


		$this->assertEquals( 1, count( $data ) );

		$this->assertEquals( 3, count( $data[0] ) );
		$this->assertEquals( 'P0Y1M0W0D', $data[0][0] );
		$this->assertEquals( '2000-01-01', $data[0][1] );
		$this->assertEquals( '2010-01-01', $data[0][2] );
	}


	protected function getSubscription( $context )
	{
		$manager = \Aimeos\MShop::create( $context, 'subscription' );
		$search = $manager->filter()->add( 'subscription.dateend', '==', '2010-01-01' );
		$domains = ['order', 'order/address', 'order/coupon', 'order/product', 'order/service'];

		return $manager->search( $search, $domains )->first( new \Exception( 'No subscription item found' ) );
	}
}
