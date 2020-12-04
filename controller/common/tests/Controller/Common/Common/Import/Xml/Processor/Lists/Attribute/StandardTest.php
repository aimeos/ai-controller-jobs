<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Attribute;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp()
	{
		$this->context = \TestHelperCntl::getContext();

		$fs = $this->context->getFileSystemManager()->get( 'fs-media' );
		$fs->has( 'path/to' ) ?: $fs->mkdir( 'path/to' );
		$fs->write( 'path/to/file2.jpg', 'test2' );
		$fs->write( 'path/to/file.jpg', 'test' );

		$this->object = new \Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Attribute\Standard( $this->context );
	}


	protected function tearDown()
	{
		unset( $this->object, $this->context );
	}


	public function testProcess()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'attribute' );

		$refId1 = $manager->findItem( 'black', [], 'product', 'color' )->getId();
		$refId2 = $manager->findItem( 'white', [], 'product', 'color' )->getId();

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<attribute>
	<attributeitem ref="product|color|black" lists.type="default" />
	<attributeitem ref="product|color|white" lists.type="test" />
</attribute>' );

		$attribute = $this->object->process( $manager->createItem(), $dom->firstChild );

		$this->assertEquals( 2, count( $attribute->getListItems() ) );
		$this->assertNotNull( $attribute->getListItem( 'attribute', 'test', $refId2 ) );
		$this->assertNotNull( $attribute->getListItem( 'attribute', 'default', $refId1 ) );
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'attribute' );
		$listManager = \Aimeos\MShop::create( $this->context, 'attribute/lists' );

		$attribute = $manager->createItem();
		$refId1 = $manager->findItem( 'black', [], 'product', 'color' )->getId();
		$refId2 = $manager->findItem( 'white', [], 'product', 'color' )->getId();

		$attribute->addListItem( 'attribute',
			$listManager->createItem()->setType( 'default' )->setId( 1 )->setRefId( $refId1 )
		);
		$attribute->addListItem( 'attribute',
			$listManager->createItem()->setType( 'test' )->setId( 2 )->setRefId( $refId2 )
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

		$attribute = $manager->createItem();
		$refId1 = $manager->findItem( 'black', [], 'product', 'color' )->getId();
		$refId2 = $manager->findItem( 'white', [], 'product', 'color' )->getId();

		$attribute->addListItem( 'attribute',
			$listManager->createItem()->setType( 'default' )->setId( 1 )->setRefId( $refId1 )
		);
		$attribute->addListItem( 'attribute',
			$listManager->createItem()->setType( 'test' )->setId( 2 )->setRefId( $refId2 )
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
