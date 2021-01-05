<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Price;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelperCntl::getContext();
		$this->object = new \Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Price\Standard( $this->context );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context );
	}


	public function testProcess()
	{
		$dom = new \DOMDocument();
		$product = \Aimeos\MShop::create( $this->context, 'product' )->create();

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<price>
	<priceitem lists.type="default">
		<price.type><![CDATA[test]]></price.type>
		<price.currencyid><![CDATA[EUR]]></price.currencyid>
		<price.taxrate><![CDATA[20.00]]></price.taxrate>
		<price.quantity><![CDATA[1]]></price.quantity>
		<price.value><![CDATA[10.00]]></price.value>
	</priceitem>
	<priceitem lists.type="test">
		<price.type><![CDATA[default]]></price.type>
		<price.currencyid><![CDATA[USD]]></price.currencyid>
		<price.taxrate><![CDATA[10.00]]></price.taxrate>
		<price.quantity><![CDATA[2]]></price.quantity>
		<price.value><![CDATA[20.00]]></price.value>
	</priceitem>
</price>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$pos = 0;
		$expected = [
			['default', 'test', '1', 'EUR', '20.00', '10.00'],
			['test', 'default', '2', 'USD', '10.00', '20.00'],
		];

		$listItems = $product->getListItems( 'price' );
		$this->assertEquals( 2, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $expected[$pos][0], $listItem->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getType() );
			$this->assertEquals( $expected[$pos][2], $listItem->getRefItem()->getQuantity() );
			$this->assertEquals( $expected[$pos][3], $listItem->getRefItem()->getCurrencyId() );
			$this->assertEquals( $expected[$pos][4], $listItem->getRefItem()->getTaxrate() );
			$this->assertEquals( $expected[$pos][5], $listItem->getRefItem()->getValue() );
			$pos++;
		}
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'price' );
		$listManager = \Aimeos\MShop::create( $this->context, 'product/lists' );
		$product = \Aimeos\MShop::create( $this->context, 'product' )->create();

		$product->addListItem( 'price',
			$listManager->create()->setType( 'default' )->setId( 1 ),
			$manager->create()->setType( 'test' )->setCurrencyId( 'EUR' )
				->setTaxrate( '20.00' )->setQuantity( 1 )->setValue( '10.00' )
		);
		$product->addListItem( 'price',
			$listManager->create()->setType( 'test' )->setId( 2 ),
			$manager->create()->setType( 'default' )->setCurrencyId( 'USD' )
				->setTaxrate( '10.00' )->setQuantity( 2 )->setValue( '20.00' )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<price>
	<priceitem lists.type="test">
		<price.type><![CDATA[default]]></price.type>
		<price.currencyid><![CDATA[USD]]></price.currencyid>
		<price.taxrate><![CDATA[10.00]]></price.taxrate>
		<price.quantity><![CDATA[2]]></price.quantity>
		<price.value><![CDATA[20.00]]></price.value>
	</priceitem>
	<priceitem lists.type="default">
		<price.type><![CDATA[test]]></price.type>
		<price.currencyid><![CDATA[EUR]]></price.currencyid>
		<price.taxrate><![CDATA[20.00]]></price.taxrate>
		<price.quantity><![CDATA[1]]></price.quantity>
		<price.value><![CDATA[10.00]]></price.value>
	</priceitem>
</price>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$pos = 0;
		$expected = [
			['test', 'default', '2', 'USD', '10.00', '20.00'],
			['default', 'test', '1', 'EUR', '20.00', '10.00'],
		];

		$listItems = $product->getListItems();
		$this->assertEquals( 2, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $expected[$pos][0], $listItem->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getType() );
			$this->assertEquals( $expected[$pos][2], $listItem->getRefItem()->getQuantity() );
			$this->assertEquals( $expected[$pos][3], $listItem->getRefItem()->getCurrencyId() );
			$this->assertEquals( $expected[$pos][4], $listItem->getRefItem()->getTaxrate() );
			$this->assertEquals( $expected[$pos][5], $listItem->getRefItem()->getValue() );
			$pos++;
		}
	}


	public function testProcessDelete()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'price' );
		$listManager = \Aimeos\MShop::create( $this->context, 'product/lists' );
		$product = \Aimeos\MShop::create( $this->context, 'product' )->create();

		$product->addListItem( 'price',
			$listManager->create()->setType( 'default' )->setId( 1 ),
			$manager->create()->setType( 'test' )->setCurrencyId( 'EUR' )
				->setTaxrate( '20.00' )->setQuantity( 1 )->setValue( '10.00' )
		);
		$product->addListItem( 'price',
			$listManager->create()->setType( 'test' )->setId( 2 ),
			$manager->create()->setType( 'default' )->setCurrencyId( 'USD' )
				->setTaxrate( '10.00' )->setQuantity( 2 )->setValue( '20.00' )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<price>
	<priceitem lists.type="test">
		<price.type><![CDATA[default]]></price.type>
		<price.currencyid><![CDATA[USD]]></price.currencyid>
		<price.taxrate><![CDATA[10.00]]></price.taxrate>
		<price.quantity><![CDATA[2]]></price.quantity>
		<price.value><![CDATA[20.00]]></price.value>
	</priceitem>
</price>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$pos = 0;
		$expected = [['test', 'default', '2', 'USD', '10.00', '20.00']];

		$listItems = $product->getListItems();
		$this->assertEquals( 1, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $expected[$pos][0], $listItem->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getType() );
			$this->assertEquals( $expected[$pos][2], $listItem->getRefItem()->getQuantity() );
			$this->assertEquals( $expected[$pos][3], $listItem->getRefItem()->getCurrencyId() );
			$this->assertEquals( $expected[$pos][4], $listItem->getRefItem()->getTaxrate() );
			$this->assertEquals( $expected[$pos][5], $listItem->getRefItem()->getValue() );
			$pos++;
		}
	}
}
