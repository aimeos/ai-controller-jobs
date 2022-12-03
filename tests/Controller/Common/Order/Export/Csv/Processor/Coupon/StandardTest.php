<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2022
 */

namespace Aimeos\Controller\Common\Order\Export\Csv\Processor\Coupon;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	public function testProcess()
	{
		$context = \TestHelper::context();
		$mapping = array(
			0 => 'order.coupon.code',
		);


		$object = new \Aimeos\Controller\Common\Order\Export\Csv\Processor\Coupon\Standard( $context, $mapping );
		$data = $object->process( $this->getInvoice( $context ) );


		$this->assertEquals( 2, count( $data ) );

		$this->assertEquals( 1, count( $data[0] ) );
		$this->assertEquals( '1234', $data[0][0] );

		$this->assertEquals( 1, count( $data[1] ) );
		$this->assertEquals( 'OPQR', $data[1][0] );
	}


	protected function getInvoice( $context )
	{
		$manager = \Aimeos\MShop::create( $context, 'order' );
		$search = $manager->filter()->add( 'order.datepayment', '==', '2008-02-15 12:34:56' );

		return $manager->search( $search, ['order', 'order/coupon'] )->first( new \Exception( 'No order item found' ) );
	}
}
