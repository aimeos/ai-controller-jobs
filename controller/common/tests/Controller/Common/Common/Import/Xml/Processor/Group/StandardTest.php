<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Group;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelperCntl::getContext();
		$this->object = new \Aimeos\Controller\Common\Common\Import\Xml\Processor\Group\Standard( $this->context );
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
<group>
	<groupitem ref="unitgroup"/>
	<groupitem ref="unitgroup2"/>
</group>' );

		$customer = $this->object->process( $customer, $dom->firstChild );

		$items = $customer->getGroups();
		$this->assertEquals( 2, count( $items ) );
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->create()->setGroups( [123] );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<group>
	<groupitem ref="unitgroup"/>
	<groupitem ref="unitgroup2"/>
</group>' );

		$customer = $this->object->process( $customer, $dom->firstChild );

		$items = $customer->getGroups();
		$this->assertEquals( 2, count( $items ) );
	}


	public function testProcessDelete()
	{
		$dom = new \DOMDocument();
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->create()->setGroups( [123, 456] );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<group>
	<groupitem ref="unitgroup"/>
</group>' );

		$customer = $this->object->process( $customer, $dom->firstChild );

		$items = $customer->getGroups();
		$this->assertEquals( 1, count( $items ) );
	}
}
