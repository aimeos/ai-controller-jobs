<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Address;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelperCntl::getContext();
		$this->object = new \Aimeos\Controller\Common\Common\Import\Xml\Processor\Address\Standard( $this->context );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context );
	}


	public function testProcess()
	{
		$dom = new \DOMDocument();
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->create();

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<address>
	<addressitem>
		<customer.address.salutation><![CDATA[mr]]></customer.address.salutation>
		<customer.address.firstname><![CDATA[Test]]></customer.address.firstname>
		<customer.address.lastname><![CDATA[User]]></customer.address.lastname>
	</addressitem>
	<addressitem>
		<customer.address.salutation><![CDATA[ms]]></customer.address.salutation>
		<customer.address.firstname><![CDATA[Mytest]]></customer.address.firstname>
		<customer.address.lastname><![CDATA[Lastuser]]></customer.address.lastname>
	</addressitem>
</address>' );

		$customer = $this->object->process( $customer, $dom->firstChild );


		$pos = 0;
		$expected = [['mr', 'Test', 'User'], ['ms', 'Mytest', 'Lastuser']];

		$items = $customer->getAddressItems();
		$this->assertEquals( 2, count( $items ) );

		foreach( $items as $item )
		{
			$this->assertEquals( $expected[$pos][0], $item->getSalutation() );
			$this->assertEquals( $expected[$pos][1], $item->getFirstname() );
			$this->assertEquals( $expected[$pos][2], $item->getLastname() );
			$pos++;
		}
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'customer/address' );
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->create();

		$customer->addAddressItem( $manager->create()->setSalutation( 'mr' )
			->setFirstname( 'Test' )->setLastname( 'User' )->setId( 1 ), 1 );
		$customer->addAddressItem( $manager->create()->setSalutation( 'ms' )
			->setFirstname( 'Mytest' )->setLastname( 'Lastuser' )->setId( 2 ), 2 );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<address>
	<addressitem>
		<customer.address.salutation><![CDATA[ms]]></customer.address.salutation>
		<customer.address.firstname><![CDATA[Mytest]]></customer.address.firstname>
		<customer.address.lastname><![CDATA[Lastuser]]></customer.address.lastname>
	</addressitem>
	<addressitem>
		<customer.address.salutation><![CDATA[mr]]></customer.address.salutation>
		<customer.address.firstname><![CDATA[Test]]></customer.address.firstname>
		<customer.address.lastname><![CDATA[User]]></customer.address.lastname>
	</addressitem>
</address>' );

		$customer = $this->object->process( $customer, $dom->firstChild );


		$pos = 0;
		$expected = [['ms', 'Mytest', 'Lastuser'], ['mr', 'Test', 'User']];

		$items = $customer->getAddressItems();
		$this->assertEquals( 2, count( $items ) );

		foreach( $items as $item )
		{
			$this->assertEquals( $expected[$pos][0], $item->getSalutation() );
			$this->assertEquals( $expected[$pos][1], $item->getFirstname() );
			$this->assertEquals( $expected[$pos][2], $item->getLastname() );
			$pos++;
		}
	}


	public function testProcessDelete()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'customer/address' );
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->create();

		$customer->addAddressItem( $manager->create()->setSalutation( 'mr' )
			->setFirstname( 'Test' )->setLastname( 'User' )->setId( 1 ), 1 );
		$customer->addAddressItem( $manager->create()->setSalutation( 'ms' )
			->setFirstname( 'Mytest' )->setLastname( 'Lastuser' )->setId( 2 ), 2 );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<address>
	<addressitem>
		<customer.address.salutation><![CDATA[mr]]></customer.address.salutation>
		<customer.address.firstname><![CDATA[Test]]></customer.address.firstname>
		<customer.address.lastname><![CDATA[User]]></customer.address.lastname>
	</addressitem>
</address>' );

		$customer = $this->object->process( $customer, $dom->firstChild );


		$pos = 0;
		$expected = [['mr', 'Test', 'User']];

		$items = $customer->getAddressItems();
		$this->assertEquals( 1, count( $items ) );

		foreach( $items as $item )
		{
			$this->assertEquals( $expected[$pos][0], $item->getSalutation() );
			$this->assertEquals( $expected[$pos][1], $item->getFirstname() );
			$this->assertEquals( $expected[$pos][2], $item->getLastname() );
			$pos++;
		}
	}
}
