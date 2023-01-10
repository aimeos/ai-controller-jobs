<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2023
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Cache\Supplier;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$context = \TestHelper::context();
		$this->object = new \Aimeos\Controller\Common\Product\Import\Csv\Cache\Supplier\Standard( $context );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
	}

	public function testGetUnknown()
	{
		$this->assertEquals( null, $this->object->get( 'cache-test' ) );
	}

	public function testSet()
	{
		$item = \Aimeos\MShop::create( \TestHelper::context(), 'catalog' )->create();
		$item->setCode( 'cache-test' );
		$item->setId( 1 );

		$this->object->set( $item );
		$id = $this->object->get( 'cache-test' );

		$this->assertEquals( $item->getId(), $id );
	}
}
