<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2022
 */

namespace Aimeos\Controller\Common\Order\Export\Csv\Processor\Service;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	public function testProcess()
	{
		$context = \TestHelper::context();
		$mapping = array(
			0 => 'order.service.type',
			1 => 'order.service.code',
			2 => 'order.service.name',
			3 => 'order.service.mediaurl',
			4 => 'order.service.price',
			5 => 'order.service.costs',
			6 => 'order.service.rebate',
			7 => 'order.service.taxrate',
			8 => 'order.service.attribute.type',
			9 => 'order.service.attribute.code',
			10 => 'order.service.attribute.name',
			11 => 'order.service.attribute.value',
		);


		$object = new \Aimeos\Controller\Common\Order\Export\Csv\Processor\Service\Standard( $context, $mapping );
		$data = $object->process( $this->getInvoice( $context ) );


		$this->assertEquals( 2, count( $data ) );

		$this->assertEquals( 12, count( $data[0] ) );
		$this->assertEquals( 'payment', $data[0][0] );
		$this->assertEquals( 'unitpaymentcode', $data[0][1] );
		$this->assertEquals( 'unitpaymentcode', $data[0][2] );
		$this->assertEquals( 'somewhere/thump1.jpg', $data[0][3] );
		$this->assertEquals( '0.00', $data[0][4] );
		$this->assertEquals( '0.00', $data[0][5] );
		$this->assertEquals( '0.00', $data[0][6] );
		$this->assertEquals( '0.00', $data[0][7] );
		$this->assertEquals( "payment\npayment\npayment\npayment\npayment\npayment\npayment\npayment\npayment", $data[0][8] );
		$this->assertEquals( "ACOWNER\nACSTRING\nNAME\nREFID\nTXDATE\nX-ACCOUNT\nX-STATUS\nunitpaymentcode-alias-name\nunitpaymentcode-alias-value", $data[0][9] );
		$this->assertEquals( "account owner\naccount number\npayment method\nreference id\ntransaction date\ntransaction account\ntransaction status\nunitpaymentcode alias name\nunitpaymentcode alias value", $data[0][10] );
		$this->assertEquals( "test user\n9876543\nCreditCard\n12345678\n2009-08-18\nKraft02\n9\naliasName\naliasValue", $data[0][11] );

		$this->assertEquals( 12, count( $data[1] ) );
		$this->assertEquals( 'delivery', $data[1][0] );
		$this->assertEquals( 'unitdeliverycode', $data[1][1] );
		$this->assertEquals( 'unitdeliverycode', $data[1][2] );
		$this->assertEquals( 'somewhere/thump1.jpg', $data[1][3] );
		$this->assertEquals( '0.00', $data[1][4] );
		$this->assertEquals( '0.00', $data[1][5] );
		$this->assertEquals( '5.00', $data[1][6] );
		$this->assertEquals( '0.00', $data[1][7] );
		$this->assertEquals( '', $data[1][8] );
		$this->assertEquals( '', $data[1][9] );
		$this->assertEquals( '', $data[1][10] );
		$this->assertEquals( '', $data[1][11] );
	}


	protected function getInvoice( $context )
	{
		$manager = \Aimeos\MShop::create( $context, 'order' );
		$search = $manager->filter()->add( 'order.datepayment', '==', '2008-02-15 12:34:56' );

		return $manager->search( $search, ['order', 'order/service'] )->first( new \Exception( 'No order item found' ) );
	}
}
