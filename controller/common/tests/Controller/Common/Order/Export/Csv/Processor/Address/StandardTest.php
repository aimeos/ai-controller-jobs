<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */

namespace Aimeos\Controller\Common\Order\Export\Csv\Processor\Address;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	public function testProcess()
	{
		$context = \TestHelperCntl::getContext();
		$mapping = array(
			0 => 'order.base.address.type',
			1 => 'order.base.address.salutation',
			2 => 'order.base.address.company',
			3 => 'order.base.address.vatid',
			4 => 'order.base.address.title',
			5 => 'order.base.address.firstname',
			6 => 'order.base.address.lastname',
			7 => 'order.base.address.address1',
			8 => 'order.base.address.address2',
			9 => 'order.base.address.address3',
			10 => 'order.base.address.postal',
			11 => 'order.base.address.city',
			12 => 'order.base.address.state',
			13 => 'order.base.address.countryid',
			14 => 'order.base.address.languageid',
			15 => 'order.base.address.telephone',
			16 => 'order.base.address.telefax',
			17 => 'order.base.address.email',
			18 => 'order.base.address.website',
			19 => 'order.base.address.longitude',
			20 => 'order.base.address.latitude',
		);


		$object = new \Aimeos\Controller\Common\Order\Export\Csv\Processor\Address\Standard( $context, $mapping );

		$invoice = $this->getInvoice( $context );
		$order = \Aimeos\MShop\Factory::createManager( $context, 'order/base' )->load( $invoice->getBaseId() );

		$data = $object->process( $invoice, $order );


		$this->assertEquals( 2, count( $data ) );

		$this->assertEquals( 21, count( $data[0] ) );
		$this->assertEquals( 'payment', $data[0][0] );
		$this->assertEquals( 'mr', $data[0][1] );
		$this->assertEquals( '', $data[0][2] );
		$this->assertEquals( '', $data[0][3] );
		$this->assertEquals( '', $data[0][4] );
		$this->assertEquals( 'Our', $data[0][5] );
		$this->assertEquals( 'Unittest', $data[0][6] );
		$this->assertEquals( 'Durchschnitt', $data[0][7] );
		$this->assertEquals( '1', $data[0][8] );
		$this->assertEquals( '', $data[0][9] );
		$this->assertEquals( '20146', $data[0][10] );
		$this->assertEquals( 'Hamburg', $data[0][11] );
		$this->assertEquals( 'Hamburg', $data[0][12] );
		$this->assertEquals( 'DE', $data[0][13] );
		$this->assertEquals( 'de', $data[0][14] );
		$this->assertEquals( '055544332211', $data[0][15] );
		$this->assertEquals( '055544332213', $data[0][16] );
		$this->assertEquals( 'test@example.com', $data[0][17] );
		$this->assertEquals( 'www.metaways.net', $data[0][18] );
		$this->assertEquals( '11.000000', $data[0][19] );
		$this->assertEquals( '52.000000', $data[0][20] );

		$this->assertEquals( 21, count( $data[1] ) );
		$this->assertEquals( 'delivery', $data[1][0] );
		$this->assertEquals( 'mr', $data[1][1] );
		$this->assertEquals( 'Example company', $data[1][2] );
		$this->assertEquals( 'DE999999999', $data[1][3] );
		$this->assertEquals( 'Dr.', $data[1][4] );
		$this->assertEquals( 'Our', $data[1][5] );
		$this->assertEquals( 'Unittest', $data[1][6] );
		$this->assertEquals( 'Pickhuben', $data[1][7] );
		$this->assertEquals( '2-4', $data[1][8] );
		$this->assertEquals( '', $data[1][9] );
		$this->assertEquals( '20457', $data[1][10] );
		$this->assertEquals( 'Hamburg', $data[1][11] );
		$this->assertEquals( 'Hamburg', $data[1][12] );
		$this->assertEquals( 'DE', $data[1][13] );
		$this->assertEquals( 'de', $data[1][14] );
		$this->assertEquals( '055544332211', $data[1][15] );
		$this->assertEquals( '055544332212', $data[1][16] );
		$this->assertEquals( 'test@example.com', $data[1][17] );
		$this->assertEquals( 'www.example.com', $data[1][18] );
		$this->assertEquals( '10.000000', $data[1][19] );
		$this->assertEquals( '50.000000', $data[1][20] );
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
