<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */

namespace Aimeos\Controller\Common\Order\Export\Csv\Processor\Service;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	public function testProcess()
	{
		$context = \TestHelperCntl::getContext();
		$mapping = array(
			0 => 'order.base.service.type',
			1 => 'order.base.service.code',
			2 => 'order.base.service.name',
			3 => 'order.base.service.mediaurl',
			4 => 'order.base.service.price',
			5 => 'order.base.service.costs',
			6 => 'order.base.service.rebate',
			7 => 'order.base.service.taxrate',
			8 => 'order.base.service.attribute.type',
			9 => 'order.base.service.attribute.code',
			10 => 'order.base.service.attribute.name',
			11 => 'order.base.service.attribute.value',
		);


		$object = new \Aimeos\Controller\Common\Order\Export\Csv\Processor\Service\Standard( $context, $mapping );

		$invoice = $this->getInvoice( $context );
		$order = \Aimeos\MShop\Factory::createManager( $context, 'order/base' )->load( $invoice->getBaseId() );

		$data = $object->process( $invoice, $order );


		$this->assertEquals( 2, count( $data ) );

		$this->assertEquals( 12, count( $data[0] ) );
		$this->assertEquals( 'payment', $data[0][0] );
		$this->assertEquals( 'OGONE', $data[0][1] );
		$this->assertEquals( 'ogone', $data[0][2] );
		$this->assertEquals( 'somewhere/thump1.jpg', $data[0][3] );
		$this->assertEquals( '0.00', $data[0][4] );
		$this->assertEquals( '0.00', $data[0][5] );
		$this->assertEquals( '0.00', $data[0][6] );
		$this->assertEquals( '0.0000', $data[0][7] );
		$this->assertEquals( "payment\npayment\npayment\npayment\npayment\npayment\npayment\npayment\npayment", $data[0][8] );
		$this->assertEquals( "ACOWNER\nACSTRING\nNAME\nOgone-alias-name\nOgone-alias-value\nREFID\nTXDATE\nX-ACCOUNT\nX-STATUS", $data[0][9] );
		$this->assertEquals( "account owner\naccount number\npayment method\nogone alias name\nogone alias value\nreference id\ntransaction date\ntransaction account\ntransaction status", $data[0][10] );
		$this->assertEquals( "test user\n9876543\nCreditCard\naliasName\naliasValue\n12345678\n2009-08-18\nKraft02\n9", $data[0][11] );

		$this->assertEquals( 12, count( $data[1] ) );
		$this->assertEquals( 'delivery', $data[1][0] );
		$this->assertEquals( '73', $data[1][1] );
		$this->assertEquals( 'solucia', $data[1][2] );
		$this->assertEquals( 'somewhere/thump1.jpg', $data[1][3] );
		$this->assertEquals( '0.00', $data[1][4] );
		$this->assertEquals( '5.00', $data[1][5] );
		$this->assertEquals( '0.00', $data[1][6] );
		$this->assertEquals( '0.0000', $data[1][7] );
		$this->assertEquals( '', $data[1][8] );
		$this->assertEquals( '', $data[1][9] );
		$this->assertEquals( '', $data[1][10] );
		$this->assertEquals( '', $data[1][11] );
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
