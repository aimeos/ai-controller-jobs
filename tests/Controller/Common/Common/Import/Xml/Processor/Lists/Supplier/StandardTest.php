<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021-2022
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Supplier;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelper::context();
		$this->object = new \Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Supplier\Standard( $this->context );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context );
	}


	public function testProcess()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$supManager = \Aimeos\MShop::create( $this->context, 'supplier' );

		$refId1 = $supManager->find( 'unitSupplier001' )->getId();
		$refId2 = $supManager->find( 'unitSupplier002' )->getId();

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<supplier>
	<supplieritem ref="unitSupplier001" lists.type="default" />
	<supplieritem ref="unitSupplier002" lists.type="test" />
</supplier>' );

		$product = $this->object->process( $manager->create(), $dom->firstChild );

		$this->assertEquals( 2, count( $product->getListItems() ) );
		$this->assertNotNull( $product->getListItem( 'supplier', 'default', $refId1 ) );
		$this->assertNotNull( $product->getListItem( 'supplier', 'test', $refId2 ) );
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$supManager = \Aimeos\MShop::create( $this->context, 'supplier' );

		$product = $manager->create();
		$refId1 = $supManager->find( 'unitSupplier001' )->getId();
		$refId2 = $supManager->find( 'unitSupplier002' )->getId();

		$product->addListItem( 'supplier',
			$manager->createListItem()->setType( 'default' )->setId( 1 )->setRefId( $refId1 )
		);
		$product->addListItem( 'supplier',
			$manager->createListItem()->setType( 'test' )->setId( 2 )->setRefId( $refId2 )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<supplier>
	<supplieritem ref="unitSupplier001" lists.type="test" />
	<supplieritem ref="unitSupplier002" lists.type="default" />
</supplier>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$this->assertEquals( 2, count( $product->getListItems() ) );
		$this->assertNotNull( $product->getListItem( 'supplier', 'test', $refId1 ) );
		$this->assertNotNull( $product->getListItem( 'supplier', 'default', $refId2 ) );
	}


	public function testProcessDelete()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$supManager = \Aimeos\MShop::create( $this->context, 'supplier' );

		$product = $manager->create();
		$refId1 = $supManager->find( 'unitSupplier001' )->getId();
		$refId2 = $supManager->find( 'unitSupplier002' )->getId();

		$product->addListItem( 'supplier',
			$manager->createListItem()->setType( 'default' )->setId( 1 )->setRefId( $refId1 )
		);
		$product->addListItem( 'supplier',
			$manager->createListItem()->setType( 'test' )->setId( 2 )->setRefId( $refId2 )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<supplier>
	<supplieritem ref="unitSupplier002" lists.type="default" />
</supplier>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$this->assertEquals( 1, count( $product->getListItems() ) );
		$this->assertNotNull( $product->getListItem( 'supplier', 'default', $refId2 ) );
	}
}
