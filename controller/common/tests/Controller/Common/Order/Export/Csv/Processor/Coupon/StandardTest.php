<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */

namespace Aimeos\Controller\Common\Order\Export\Csv\Processor\Coupon;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	public function testProcess()
	{
		$context = \TestHelperCntl::getContext();
		$mapping = array(
			0 => 'order.base.coupon.code',
		);


		$object = new \Aimeos\Controller\Common\Order\Export\Csv\Processor\Coupon\Standard( $context, $mapping );

		$invoice = $this->getInvoice( $context );
		$order = \Aimeos\MShop::create( $context, 'order/base' )->load( $invoice->getBaseId() );

		$data = $object->process( $invoice, $order );


		$this->assertEquals( 2, count( $data ) );

		$this->assertEquals( 1, count( $data[0] ) );
		$this->assertEquals( '5678', $data[0][0] );

		$this->assertEquals( 1, count( $data[1] ) );
		$this->assertEquals( 'OPQR', $data[1][0] );
	}


	protected function getInvoice( $context )
	{
		$manager = \Aimeos\MShop::create( $context, 'order' );

		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'order.datepayment', '2008-02-15 12:34:56' ) );

		if( ( $item = $manager->search( $search )->first() ) !== null ) {
			return $item;
		}

		throw new \Exception( 'No order item found' );
	}
}
