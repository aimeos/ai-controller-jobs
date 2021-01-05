<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Text;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelperCntl::getContext();
		$this->object = new \Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Text\Standard( $this->context );
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
<text>
	<textitem lists.type="default">
		<text.type><![CDATA[name]]></text.type>
		<text.languageid><![CDATA[de]]></text.languageid>
		<text.content><![CDATA[Test]]></text.content>
	</textitem>
	<textitem lists.type="test">
		<text.type><![CDATA[short]]></text.type>
		<text.languageid><![CDATA[de]]></text.languageid>
		<text.content><![CDATA[Kurztest]]></text.content>
	</textitem>
</text>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$pos = 0;
		$expected = [['default', 'name', 'de', 'Test'], ['test', 'short', 'de', 'Kurztest']];

		$listItems = $product->getListItems( 'text' );
		$this->assertEquals( 2, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $expected[$pos][0], $listItem->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getType() );
			$this->assertEquals( $expected[$pos][2], $listItem->getRefItem()->getLanguageId() );
			$this->assertEquals( $expected[$pos][3], $listItem->getRefItem()->getContent() );
			$pos++;
		}
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'text' );
		$listManager = \Aimeos\MShop::create( $this->context, 'product/lists' );
		$product = \Aimeos\MShop::create( $this->context, 'product' )->create();

		$product->addListItem( 'text',
			$listManager->create()->setType( 'default' )->setId( 1 ),
			$manager->create()->setType( 'name' )->setLanguageId( 'de' )->setContent( 'Test' )
		);
		$product->addListItem( 'text',
			$listManager->create()->setType( 'test' )->setId( 2 ),
			$manager->create()->setType( 'short' )->setLanguageId( 'de' )->setContent( 'Kurztest' )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<text>
	<textitem lists.type="test">
		<text.type><![CDATA[short]]></text.type>
		<text.languageid><![CDATA[de]]></text.languageid>
		<text.content><![CDATA[Kurztest]]></text.content>
	</textitem>
	<textitem lists.type="default">
		<text.type><![CDATA[name]]></text.type>
		<text.languageid><![CDATA[de]]></text.languageid>
		<text.content><![CDATA[Test]]></text.content>
	</textitem>
</text>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$pos = 0;
		$expected = [['test', 'short', 'de', 'Kurztest'], ['default', 'name', 'de', 'Test']];

		$listItems = $product->getListItems();
		$this->assertEquals( 2, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $expected[$pos][0], $listItem->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getType() );
			$this->assertEquals( $expected[$pos][2], $listItem->getRefItem()->getLanguageId() );
			$this->assertEquals( $expected[$pos][3], $listItem->getRefItem()->getContent() );
			$pos++;
		}
	}


	public function testProcessDelete()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'text' );
		$listManager = \Aimeos\MShop::create( $this->context, 'product/lists' );
		$product = \Aimeos\MShop::create( $this->context, 'product' )->create();

		$product->addListItem( 'text',
			$listManager->create()->setType( 'default' )->setId( 1 ),
			$manager->create()->setType( 'name' )->setLanguageId( 'de' )->setContent( 'Test' )
		);
		$product->addListItem( 'text',
			$listManager->create()->setType( 'test' )->setId( 2 ),
			$manager->create()->setType( 'short' )->setLanguageId( 'de' )->setContent( 'Kurztest' )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<text>
	<textitem lists.type="default">
		<text.type><![CDATA[short]]></text.type>
		<text.languageid><![CDATA[de]]></text.languageid>
		<text.content><![CDATA[Kurztest]]></text.content>
	</textitem>
</text>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$pos = 0;
		$expected = [['default', 'short', 'de', 'Kurztest']];

		$listItems = $product->getListItems();
		$this->assertEquals( 1, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $expected[$pos][0], $listItem->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getType() );
			$this->assertEquals( $expected[$pos][2], $listItem->getRefItem()->getLanguageId() );
			$this->assertEquals( $expected[$pos][3], $listItem->getRefItem()->getContent() );
			$pos++;
		}
	}
}
