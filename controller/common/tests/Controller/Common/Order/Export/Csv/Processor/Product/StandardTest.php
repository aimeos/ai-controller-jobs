<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */

namespace Aimeos\Controller\Common\Order\Export\Csv\Processor\Product;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	public function testProcess()
	{
		$context = \TestHelperCntl::getContext();
		$mapping = array(
			0 => 'order.base.product.type',
			1 => 'order.base.product.stocktype',
			2 => 'order.base.product.suppliername',
			3 => 'order.base.product.prodcode',
			4 => 'order.base.product.productid',
			5 => 'order.base.product.quantity',
			6 => 'order.base.product.name',
			7 => 'order.base.product.mediaurl',
			8 => 'order.base.product.price',
			9 => 'order.base.product.costs',
			10 => 'order.base.product.rebate',
			11 => 'order.base.product.taxrate',
			12 => 'order.base.product.statusdelivery',
			13 => 'order.base.product.position',
			14 => 'order.base.product.attribute.type',
			15 => 'order.base.product.attribute.code',
			16 => 'order.base.product.attribute.name',
			17 => 'order.base.product.attribute.value',
		);


		$object = new \Aimeos\Controller\Common\Order\Export\Csv\Processor\Product\Standard( $context, $mapping );

		$invoice = $this->getInvoice( $context );
		$order = \Aimeos\MShop::create( $context, 'order/base' )->load( $invoice->getBaseId() );

		$data = $object->process( $invoice, $order, $context->getLocale()->getSiteId() );


		$this->assertEquals( 4, count( $data ) );

		$this->assertEquals( 18, count( $data[0] ) );
		$this->assertEquals( 'default', $data[0][0] );
		$this->assertEquals( 'default', $data[0][1] );
		$this->assertEquals( 'Test supplier', $data[0][2] );
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

		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'order.datepayment', '2008-02-15 12:34:56' ) );

		if( ( $item = $manager->search( $search )->first() ) !== null ) {
			return $item;
		}

		throw new \Exception( 'No order item found' );
	}
}
