<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2023
 */


namespace Aimeos\Controller\Common\Product\Import\Csv;


class BaseTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->object = new TestAbstract( $context, $aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
	}


	public function testGetCache()
	{
		$cache = $this->object->getCachePublic( 'attribute' );

		$this->assertInstanceOf( '\\Aimeos\\Controller\\Common\\Product\\Import\\Csv\\Cache\\Iface', $cache );
	}


	public function testGetCacheInvalidType()
	{
		$this->expectException( \LogicException::class );
		$this->object->getCachePublic( '$' );
	}


	public function testGetCacheInvalidClass()
	{
		$this->expectException( \LogicException::class );
		$this->object->getCachePublic( 'unknown' );
	}


	public function testGetCacheInvalidInterface()
	{
		$this->expectException( \LogicException::class );
		$this->object->getCachePublic( 'attribute', 'unknown' );
	}


	public function testGetProcessors()
	{
		$processor = $this->object->getProcessorsPublic( array( 'attribute' => [] ) );

		$this->assertInstanceOf( '\\Aimeos\\Controller\\Common\\Product\\Import\\Csv\\Processor\\Iface', $processor );
	}


	public function testGetProcessorsInvalidType()
	{
		$this->expectException( \LogicException::class );
		$this->object->getProcessorsPublic( array( '$' => [] ) );
	}


	public function testGetProcessorsInvalidClass()
	{
		$this->expectException( \LogicException::class );
		$this->object->getProcessorsPublic( array( 'unknown' => [] ) );
	}


	public function testGetProcessorsInvalidInterface()
	{
		$this->expectException( \LogicException::class );
		$this->object->getProcessorsPublic( array( 'unknown' => [] ) );
	}
}


class TestAbstract
	extends \Aimeos\Controller\Common\Product\Import\Csv\Base
{
	public function context() : \Aimeos\MShop\ContextIface
	{
		return \TestHelper::context();
	}

	public function getCachePublic( $type, $name = null )
	{
		return $this->getCache( $type, $name );
	}


	public function getProcessorsPublic( array $mappings )
	{
		return $this->getProcessors( $mappings );
	}
}


class TestInvalid
{
}
