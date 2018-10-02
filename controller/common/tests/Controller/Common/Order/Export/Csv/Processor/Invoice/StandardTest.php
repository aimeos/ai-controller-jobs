<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */

namespace Aimeos\Controller\Common\Order\Export\Csv\Processor\Invoice;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	public function testProcess()
	{
		$context = \TestHelperCntl::getContext();
		$mapping = array(
			0 => 'order.type',
			1 => 'order.datepayment',
			2 => 'order.statuspayment',
			3 => 'order.datedelivery',
			4 => 'order.statusdelivery',
			5 => 'order.relatedid',
			6 => 'order.base.customerid',
			7 => 'order.base.sitecode',
			8 => 'order.base.languageid',
			9 => 'order.base.currencyid',
			10 => 'order.base.price',
			11 => 'order.base.costs',
			12 => 'order.base.rebate',
			13 => 'order.base.taxvalue',
			14 => 'order.base.taxflag',
			15 => 'order.base.status',
			16 => 'order.base.comment',
		);


		$object = new \Aimeos\Controller\Common\Order\Export\Csv\Processor\Invoice\Standard( $context, $mapping );

		$invoice = $this->getInvoice( $context );
		$order = \Aimeos\MShop\Factory::createManager( $context, 'order/base' )->load( $invoice->getBaseId() );

		$data = $object->process( $invoice, $order );


		$this->assertEquals( 1, count( $data ) );

		$this->assertEquals( 17, count( $data[0] ) );
		$this->assertEquals( 'web', $data[0][0] );
		$this->assertEquals( '2008-02-15 12:34:56', $data[0][1] );
		$this->assertEquals( '6', $data[0][2] );
		$this->assertEquals( '', $data[0][3] );
		$this->assertEquals( '4', $data[0][4] );
		$this->assertEquals( '', $data[0][5] );
		$this->assertGreaterThan( 0, $data[0][6] );
		$this->assertEquals( 'unittest', $data[0][7] );
		$this->assertEquals( 'de', $data[0][8] );
		$this->assertEquals( 'EUR', $data[0][9] );
		$this->assertEquals( '53.50', $data[0][10] );
		$this->assertEquals( '1.50', $data[0][11] );
		$this->assertEquals( '14.50', $data[0][12] );
		$this->assertEquals( '0.0000', $data[0][13] );
		$this->assertEquals( '1', $data[0][14] );
		$this->assertEquals( '0', $data[0][15] );
		$this->assertEquals( 'This is a comment if an order. It can be added by the user.', $data[0][16] );
	}


	protected function getInvoice( $context )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'order' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'order.datepayment', '2008-02-15 12:34:56' ) );

		$items = $manager->searchItems( $search );

		if( ( $item = reset( $items ) ) !== false ) {
			return $item;
		}

		throw new \Exception( 'No order item found' );
	}
}
