<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2026
 */


namespace Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Group;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $endpoint;


	protected function setUp() : void
	{
		$this->context = \TestHelper::context();
		$this->endpoint = new \Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Done( $this->context, [] );
	}


	protected function tearDown() : void
	{
		unset( $this->endpoint, $this->context );
	}


	public function testProcess()
	{
		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->create();
		$manager = \Aimeos\MShop::create( $this->context, 'group' );

		$object = new \Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Group\Standard( $this->context, [0 => 'customer.groups'], $this->endpoint );
		$object->process( $customer, [0 => "unitgroup\nunitgroup2\nunknown"] );

		$expected = [$manager->find( 'unitgroup' )->getId(), $manager->find( 'unitgroup2' )->getId()];
		$this->assertEquals( $expected, $customer->getGroups() );
	}


	public function testProcessDenied()
	{
		$this->context->config()->set( 'controller/jobs/customer/import/csv/processor/group/denied', ['unitgroup2'] );

		$customer = \Aimeos\MShop::create( $this->context, 'customer' )->create();
		$grpId = \Aimeos\MShop::create( $this->context, 'group' )->find( 'unitgroup' )->getId();

		$object = new \Aimeos\Controller\Jobs\Common\Customer\Import\Csv\Processor\Group\Standard( $this->context, [0 => 'customer.groups'], $this->endpoint );
		$object->process( $customer, [0 => "unitgroup\nunitgroup2"] );

		$this->assertEquals( [$grpId], $customer->getGroups() );
	}
}
