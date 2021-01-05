<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Attribute;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelperCntl::getContext();
		$this->object = new \Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Attribute\Standard( $this->context );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context );
	}


	public function testProcess()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'attribute' );

		$refId1 = $manager->find( 'black', [], 'product', 'color' )->getId();
		$refId2 = $manager->find( 'white', [], 'product', 'color' )->getId();

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<attribute>
	<attributeitem ref="product|color|black" lists.type="default" />
	<attributeitem ref="product|color|white" lists.type="test" />
</attribute>' );

		$attribute = $this->object->process( $manager->create(), $dom->firstChild );

		$this->assertEquals( 2, count( $attribute->getListItems() ) );
		$this->assertNotNull( $attribute->getListItem( 'attribute', 'test', $refId2 ) );
		$this->assertNotNull( $attribute->getListItem( 'attribute', 'default', $refId1 ) );
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'attribute' );
		$listManager = \Aimeos\MShop::create( $this->context, 'attribute/lists' );

		$attribute = $manager->create();
		$refId1 = $manager->find( 'black', [], 'product', 'color' )->getId();
		$refId2 = $manager->find( 'white', [], 'product', 'color' )->getId();

		$attribute->addListItem( 'attribute',
			$listManager->create()->setType( 'default' )->setId( 1 )->setRefId( $refId1 )
		);
		$attribute->addListItem( 'attribute',
			$listManager->create()->setType( 'test' )->setId( 2 )->setRefId( $refId2 )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<attribute>
	<attributeitem ref="product|color|white" lists.type="test" />
	<attributeitem ref="product|color|black" lists.type="default" />
</attribute>' );

		$attribute = $this->object->process( $attribute, $dom->firstChild );

		$this->assertEquals( 2, count( $attribute->getListItems() ) );
		$this->assertNotNull( $attribute->getListItem( 'attribute', 'test', $refId2 ) );
		$this->assertNotNull( $attribute->getListItem( 'attribute', 'default', $refId1 ) );
	}


	public function testProcessDelete()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'attribute' );
		$listManager = \Aimeos\MShop::create( $this->context, 'attribute/lists' );

		$attribute = $manager->create();
		$refId1 = $manager->find( 'black', [], 'product', 'color' )->getId();
		$refId2 = $manager->find( 'white', [], 'product', 'color' )->getId();

		$attribute->addListItem( 'attribute',
			$listManager->create()->setType( 'default' )->setId( 1 )->setRefId( $refId1 )
		);
		$attribute->addListItem( 'attribute',
			$listManager->create()->setType( 'test' )->setId( 2 )->setRefId( $refId2 )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<attribute>
	<attributeitem ref="product|color|white" lists.type="default" />
</attribute>' );

		$attribute = $this->object->process( $attribute, $dom->firstChild );

		$this->assertEquals( 1, count( $attribute->getListItems() ) );
		$this->assertNotNull( $attribute->getListItem( 'attribute', 'default', $refId2 ) );
	}
}
