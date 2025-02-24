<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 */


namespace Aimeos\Controller\Jobs\Common\Product\Import\Csv\Cache\Supplier;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$context = \TestHelper::context();
		$this->object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Cache\Supplier\Standard( $context );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
	}


	public function testGet()
	{
		$this->assertInstanceOf( \Aimeos\MShop\Supplier\Item\Iface::class, $this->object->get( 'unitSupplier001' ) );
	}


	public function testGetUnknown()
	{
		$this->assertEquals( null, $this->object->get( 'cache-test' ) );
	}


	public function testSet()
	{
		$item = \Aimeos\MShop::create( \TestHelper::context(), 'supplier' )->create();
		$item->setCode( 'cache-test2' );
		$item->setId( 1 );

		$this->object->set( $item );
		$result = $this->object->get( 'cache-test2' );

		$this->assertSame( $item, $result );
	}
}
