<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2024
 */


namespace Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Lists\Catalog;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelper::context();
		$this->object = new \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Lists\Catalog\Standard( $this->context );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context );
	}


	public function testProcess()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$catManager = \Aimeos\MShop::create( $this->context, 'catalog' );

		$refId1 = $catManager->find( 'cafe' )->getId();
		$refId2 = $catManager->find( 'tea' )->getId();

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<catalog>
	<catalogitem ref="cafe" lists.type="default" />
	<catalogitem ref="tea" lists.type="test" />
</catalog>' );

		$product = $this->object->process( $manager->create(), $dom->firstChild );

		$this->assertEquals( 2, count( $product->getListItems() ) );
		$this->assertNotNull( $product->getListItem( 'catalog', 'default', $refId1 ) );
		$this->assertNotNull( $product->getListItem( 'catalog', 'test', $refId2 ) );
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$catManager = \Aimeos\MShop::create( $this->context, 'catalog' );

		$product = $manager->create();
		$refId1 = $catManager->find( 'cafe' )->getId();
		$refId2 = $catManager->find( 'tea' )->getId();

		$product->addListItem( 'catalog',
			$manager->createListItem()->setType( 'default' )->setId( 1 )->setRefId( $refId1 )
		);
		$product->addListItem( 'catalog',
			$manager->createListItem()->setType( 'test' )->setId( 2 )->setRefId( $refId2 )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<catalog>
	<catalogitem ref="tea" lists.type="test" />
	<catalogitem ref="cafe" lists.type="default" />
</catalog>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$this->assertEquals( 2, count( $product->getListItems() ) );
		$this->assertNotNull( $product->getListItem( 'catalog', 'test', $refId2 ) );
		$this->assertNotNull( $product->getListItem( 'catalog', 'default', $refId1 ) );
	}


	public function testProcessDelete()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$catManager = \Aimeos\MShop::create( $this->context, 'catalog' );

		$product = $manager->create();
		$refId1 = $catManager->find( 'cafe' )->getId();
		$refId2 = $catManager->find( 'tea' )->getId();

		$product->addListItem( 'catalog',
			$manager->createListItem()->setType( 'default' )->setId( 1 )->setRefId( $refId1 )
		);
		$product->addListItem( 'catalog',
			$manager->createListItem()->setType( 'test' )->setId( 2 )->setRefId( $refId2 )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<catalog>
	<catalogitem ref="tea" lists.type="default" />
</catalog>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$this->assertEquals( 1, count( $product->getListItems() ) );
		$this->assertNotNull( $product->getListItem( 'catalog', 'default', $refId2 ) );
	}
}
