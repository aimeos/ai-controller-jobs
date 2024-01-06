<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org)2023-2024
 */


namespace Aimeos\Controller\Jobs\Basket\Cleanup;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;


	protected function setUp() : void
	{
		$aimeos = \TestHelper::getAimeos();
		$this->context = \TestHelper::context();

		$this->object = new \Aimeos\Controller\Jobs\Basket\Cleanup\Standard( $this->context, $aimeos );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Cleanup baskets', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$this->assertEquals( 'Removes the old baskets from the database', $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->object->run();
	}
}
