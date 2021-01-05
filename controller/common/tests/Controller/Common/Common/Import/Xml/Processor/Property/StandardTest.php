<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Property;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelperCntl::getContext();
		$this->object = new \Aimeos\Controller\Common\Common\Import\Xml\Processor\Property\Standard( $this->context );
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
<property>
	<propertyitem>
		<product.property.type><![CDATA[package-weight]]></product.property.type>
		<product.property.languageid><![CDATA[de]]></product.property.languageid>
		<product.property.value><![CDATA[3.00 kg]]></product.property.value>
	</propertyitem>
	<propertyitem>
		<product.property.type><![CDATA[package-width]]></product.property.type>
		<product.property.languageid><![CDATA[]]></product.property.languageid>
		<product.property.value><![CDATA[50]]></product.property.value>
	</propertyitem>
</property>' );

		$product = $this->object->process( $product, $dom->firstChild );


		$pos = 0;
		$expected = [['package-weight', '3.00 kg', 'de'], ['package-width', '50', null]];

		$items = $product->getPropertyItems();
		$this->assertEquals( 2, count( $items ) );

		foreach( $items as $item )
		{
			$this->assertEquals( $expected[$pos][0], $item->getType() );
			$this->assertEquals( $expected[$pos][1], $item->getValue() );
			$this->assertEquals( $expected[$pos][2], $item->getLanguageId() );
			$pos++;
		}
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'product/property' );
		$product = \Aimeos\MShop::create( $this->context, 'product' )->create();

		$product->addPropertyItem( $manager->create()->setType( 'package-weight' )
			->setLanguageId( 'de' )->setValue( '3.00 kg' )->setId( 1 ) );
		$product->addPropertyItem( $manager->create()->setType( 'package-width' )
			->setLanguageId( '' )->setValue( '50' )->setId( 2 ) );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<property>
	<propertyitem>
		<product.property.type><![CDATA[package-width]]></product.property.type>
		<product.property.languageid><![CDATA[]]></product.property.languageid>
		<product.property.value><![CDATA[50]]></product.property.value>
	</propertyitem>
	<propertyitem>
		<product.property.type><![CDATA[package-size]]></product.property.type>
		<product.property.languageid><![CDATA[de]]></product.property.languageid>
		<product.property.value><![CDATA[S]]></product.property.value>
	</propertyitem>
</property>' );

		$product = $this->object->process( $product, $dom->firstChild );


		$pos = 0;
		$expected = [['package-width', '50', null], ['package-size', 'S', 'de']];

		$this->object->finish(); // test if new type is created
		$manager = \Aimeos\MShop::create( $this->context, 'product/property/type' );
		$manager->delete( $manager->find( 'package-size' )->getId() );

		$items = $product->getPropertyItems();
		$this->assertEquals( 2, count( $items ) );

		foreach( $items as $item )
		{
			$this->assertEquals( $expected[$pos][0], $item->getType() );
			$this->assertEquals( $expected[$pos][1], $item->getValue() );
			$this->assertEquals( $expected[$pos][2], $item->getLanguageId() );
			$pos++;
		}
	}


	public function testProcessDelete()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'product/property' );
		$product = \Aimeos\MShop::create( $this->context, 'product' )->create();

		$product->addPropertyItem( $manager->create()->setType( 'package-weight' )
			->setLanguageId( 'de' )->setValue( '3.00 kg' )->setId( 1 ) );
		$product->addPropertyItem( $manager->create()->setType( 'package-width' )
			->setLanguageId( '' )->setValue( '50' )->setId( 2 ) );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<property>
	<propertyitem>
		<product.property.type><![CDATA[package-width]]></product.property.type>
		<product.property.languageid><![CDATA[]]></product.property.languageid>
		<product.property.value><![CDATA[50]]></product.property.value>
	</propertyitem>
</property>' );

		$product = $this->object->process( $product, $dom->firstChild );


		$pos = 0;
		$expected = [['package-width', '50', null]];

		$items = $product->getPropertyItems();
		$this->assertEquals( 1, count( $items ) );

		foreach( $items as $item )
		{
			$this->assertEquals( $expected[$pos][0], $item->getType() );
			$this->assertEquals( $expected[$pos][1], $item->getValue() );
			$this->assertEquals( $expected[$pos][2], $item->getLanguageId() );
			$pos++;
		}
	}
}
