<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2022
 */

namespace Aimeos\Controller\Common\Order\Export\Csv\Processor\Invoice;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	public function testProcess()
	{
		$context = \TestHelper::context();
		$mapping = array(
			0 => 'order.channel',
			1 => 'order.datepayment',
			2 => 'order.statuspayment',
			3 => 'order.datedelivery',
			4 => 'order.statusdelivery',
			5 => 'order.relatedid',
			6 => 'order.customerid',
			7 => 'order.sitecode',
			8 => 'order.languageid',
			9 => 'order.currencyid',
			10 => 'order.price',
			11 => 'order.costs',
			12 => 'order.rebate',
			13 => 'order.taxvalue',
			14 => 'order.taxflag',
			15 => 'order.comment',
		);


		$object = new \Aimeos\Controller\Common\Order\Export\Csv\Processor\Invoice\Standard( $context, $mapping );
		$data = $object->process( $this->getInvoice( $context ) );


		$this->assertEquals( 1, count( $data ) );

		$this->assertEquals( 16, count( $data[0] ) );
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
		$this->assertEquals( 'This is a comment if an order. It can be added by the user.', $data[0][15] );
	}


	protected function getInvoice( $context )
	{
		$manager = \Aimeos\MShop::create( $context, 'order' );
		$search = $manager->filter()->add( 'order.datepayment', '==', '2008-02-15 12:34:56' );

		return $manager->search( $search, ['order'] )->first( new \Exception( 'No order item found' ) );
	}
}
