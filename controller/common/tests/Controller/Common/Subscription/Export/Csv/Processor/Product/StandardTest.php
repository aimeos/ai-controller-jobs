<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
 */

namespace Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Product;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	public function testProcess()
	{
		$context = \TestHelperCntl::getContext();
		$mapping = array(
			0 => 'order.base.product.type',
			1 => 'order.base.product.stocktype',
			2 => 'order.base.product.suppliercode',
			3 => 'order.base.product.prodcode',
			4 => 'order.base.product.productid',
			5 => 'order.base.product.quantity',
			6 => 'order.base.product.name',
			7 => 'order.base.product.mediaurl',
			8 => 'order.base.product.price',
			9 => 'order.base.product.costs',
			10 => 'order.base.product.rebate',
			11 => 'order.base.product.taxrate',
			12 => 'order.base.product.status',
			13 => 'order.base.product.position',
			14 => 'order.base.product.attribute.type',
			15 => 'order.base.product.attribute.code',
			16 => 'order.base.product.attribute.name',
			17 => 'order.base.product.attribute.value',
		);


		$object = new \Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Product\Standard( $context, $mapping );

		$subscription = $this->getSubscription( $context );
		$order = \Aimeos\MShop\Factory::createManager( $context, 'order/base' )->load( $subscription->getOrderBaseId() );

		$data = $object->process( $subscription, $order );


		$this->assertEquals( 1, count( $data ) );

		$this->assertEquals( 18, count( $data[0] ) );
		$this->assertEquals( 'default', $data[0][0] );
		$this->assertEquals( 'unit_type1', $data[0][1] );
		$this->assertEquals( 'unitsupplier', $data[0][2] );
		$this->assertEquals( 'CNE', $data[0][3] );
		$this->assertGreaterThan( 0, $data[0][4] );
		$this->assertEquals( '9', $data[0][5] );
		$this->assertEquals( 'Cafe Noire Expresso', $data[0][6] );
		$this->assertEquals( 'somewhere/thump1.jpg', $data[0][7] );
		$this->assertEquals( '4.50', $data[0][8] );
		$this->assertEquals( '0.00', $data[0][9] );
		$this->assertEquals( '0.00', $data[0][10] );
		$this->assertEquals( '0.0000', $data[0][11] );
		$this->assertEquals( '1', $data[0][12] );
		$this->assertEquals( '1', $data[0][13] );
		$this->assertEquals( "default\ndefault\nconfig", $data[0][14] );
		$this->assertEquals( "width\nlength\ninterval", $data[0][15] );
		$this->assertEquals( "33\n36\nP0Y1M0W0D", $data[0][16] );
		$this->assertEquals( "33\n36\nP0Y1M0W0D", $data[0][17] );
	}


	protected function getSubscription( $context )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $context, 'subscription' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'subscription.dateend', '2010-01-01' ) );

		$items = $manager->searchItems( $search );

		if( ( $item = reset( $items ) ) !== false ) {
			return $item;
		}

		throw new \Exception( 'No subscription item found' );
	}
}
