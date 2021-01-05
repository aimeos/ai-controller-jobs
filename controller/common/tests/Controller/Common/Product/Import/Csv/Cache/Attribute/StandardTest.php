<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Cache\Attribute;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$context = \TestHelperCntl::getContext();
		$this->object = new \Aimeos\Controller\Common\Product\Import\Csv\Cache\Attribute\Standard( $context );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
	}


	public function testGet()
	{
		$item = $this->object->get( 'black', 'color' );

		$this->assertInstanceOf( '\\Aimeos\\MShop\\Attribute\\Item\\Iface', $item );
		$this->assertEquals( 'black', $item->getCode() );
		$this->assertEquals( 'color', $item->getType() );
	}


	public function testGetUnknown()
	{
		$this->assertEquals( null, $this->object->get( 'cache-test', 'color' ) );
	}


	public function testSet()
	{
		$item = $this->object->get( 'black', 'color' );

		$this->assertInstanceOf( '\\Aimeos\\MShop\\Attribute\\Item\\Iface', $item );

		$item->setCode( 'cache-test' );

		$this->object->set( $item );
		$item = $this->object->get( 'cache-test', 'color' );

		$this->assertInstanceOf( '\\Aimeos\\MShop\\Attribute\\Item\\Iface', $item );
		$this->assertEquals( 'cache-test', $item->getCode() );
		$this->assertEquals( 'color', $item->getType() );
	}
}
