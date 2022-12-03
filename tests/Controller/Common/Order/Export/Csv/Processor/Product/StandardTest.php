<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2022
 */

namespace Aimeos\Controller\Common\Order\Export\Csv\Processor\Product;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	public function testProcess()
	{
		$context = \TestHelper::context();
		$mapping = array(
			0 => 'order.product.type',
			1 => 'order.product.stocktype',
			2 => 'order.product.vendor',
			3 => 'order.product.prodcode',
			4 => 'order.product.productid',
			5 => 'order.product.quantity',
			6 => 'order.product.name',
			7 => 'order.product.mediaurl',
			8 => 'order.product.price',
			9 => 'order.product.costs',
			10 => 'order.product.rebate',
			11 => 'order.product.taxrate',
			12 => 'order.product.statusdelivery',
			13 => 'order.product.position',
			14 => 'order.product.attribute.type',
			15 => 'order.product.attribute.code',
			16 => 'order.product.attribute.name',
			17 => 'order.product.attribute.value',
		);


		$object = new \Aimeos\Controller\Common\Order\Export\Csv\Processor\Product\Standard( $context, $mapping );
		$data = $object->process( $this->getInvoice( $context ) );


		$this->assertEquals( 4, count( $data ) );

		$this->assertEquals( 18, count( $data[0] ) );
		$this->assertEquals( 'default', $data[0][0] );
		$this->assertEquals( 'default', $data[0][1] );
		$this->assertEquals( 'Test vendor', $data[0][2] );
		$this->assertEquals( 'CNE', $data[0][3] );
		$this->assertGreaterThan( 0, $data[0][4] );
		$this->assertEquals( '9', $data[0][5] );
		$this->assertEquals( 'Cafe Noire Expresso', $data[0][6] );
		$this->assertEquals( 'somewhere/thump1.jpg', $data[0][7] );
		$this->assertEquals( '4.50', $data[0][8] );
		$this->assertEquals( '0.00', $data[0][9] );
		$this->assertEquals( '0.00', $data[0][10] );
		$this->assertEquals( '0.00', $data[0][11] );
		$this->assertEquals( '1', $data[0][12] );
		$this->assertEquals( '1', $data[0][13] );
		$this->assertEquals( "default\ndefault\nconfig", $data[0][14] );
		$this->assertEquals( "width\nlength\ninterval", $data[0][15] );
		$this->assertEquals( "33\n36\nP0Y1M0W0D", $data[0][16] );
		$this->assertEquals( "33\n36\nP0Y1M0W0D", $data[0][17] );
	}


	protected function getInvoice( $context )
	{
		$manager = \Aimeos\MShop::create( $context, 'order' );
		$search = $manager->filter()->add( 'order.datepayment', '==', '2008-02-15 12:34:56' );

		return $manager->search( $search, ['order', 'order/product'] )->first( new \Exception( 'No order item found' ) );
	}
}
