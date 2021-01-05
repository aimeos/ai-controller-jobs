<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */


namespace Aimeos\Controller\Common\Customer\Import\Xml\Processor\Group;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelperCntl::getContext();
		$this->object = new \Aimeos\Controller\Common\Customer\Import\Xml\Processor\Group\Standard( $this->context );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context );
	}


	public function testProcess()
	{
		$dom = new \DOMDocument();
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->create();
		$grpId = \Aimeos\MShop::create( $this->context, 'customer/group' )->find( 'unitgroup' )->getId();

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<group>
	<groupitem ref="unitgroup" />
</group>' );

		$customer = $this->object->process( $customer, $dom->firstChild );

		$this->assertEquals( 1, count( $customer->getGroups() ) );
		$this->assertEquals( $grpId, current( $customer->getGroups() ) );
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->create();
		$grpId = \Aimeos\MShop::create( $this->context, 'customer/group' )->find( 'unitgroup2' )->getId();

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<group>
	<groupitem ref="unitgroup" />
</group>' );

		$customer = $this->object->process( $customer, $dom->firstChild );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<group>
	<groupitem ref="unitgroup2" />
</group>' );

		$customer = $this->object->process( $customer, $dom->firstChild );

		$this->assertEquals( 1, count( $customer->getGroups() ) );
		$this->assertEquals( $grpId, current( $customer->getGroups() ) );
	}


	public function testProcessDelete()
	{
		$dom = new \DOMDocument();
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->create();

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<group>
	<groupitem ref="unitgroup" />
</group>' );

		$customer = $this->object->process( $customer, $dom->firstChild );

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<group>
</group>' );

		$customer = $this->object->process( $customer, $dom->firstChild );

		$this->assertEquals( 0, count( $customer->getGroups() ) );
	}
}
