<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2018
 */


namespace Aimeos\Controller\Common\Catalog\Import\Csv;


class BaseTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp()
	{
		\Aimeos\MShop\Factory::setCache( true );

		$context = \TestHelperCntl::getContext();
		$aimeos = \TestHelperCntl::getAimeos();

		$this->object = new TestAbstract( $context, $aimeos );
	}


	protected function tearDown()
	{
		\Aimeos\MShop\Factory::setCache( false );
		\Aimeos\MShop\Factory::clear();
	}


	public function testGetCacheInvalidType()
	{
		$this->setExpectedException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->getCachePublic( '$' );
	}


	public function testGetCacheInvalidClass()
	{
		$this->setExpectedException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->getCachePublic( 'unknown' );
	}


	public function testGetProcessors()
	{
		$processor = $this->object->getProcessorsPublic( array( 'media' => [] ) );

		$this->assertInstanceOf( '\\Aimeos\\Controller\\Common\\Catalog\\Import\\Csv\\Processor\\Iface', $processor );
	}


	public function testGetProcessorsInvalidType()
	{
		$this->setExpectedException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->getProcessorsPublic( array( '$' => [] ) );
	}


	public function testGetProcessorsInvalidClass()
	{
		$this->setExpectedException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->getProcessorsPublic( array( 'unknown' => [] ) );
	}


	public function testGetProcessorsInvalidInterface()
	{
		$this->setExpectedException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->getProcessorsPublic( array( 'unknown' => [] ) );
	}


	public function testGetTypeId()
	{
		$typeid = $this->object->getTypeIdPublic( 'text/type', 'catalog', 'name' );

		$this->assertNotEquals( null, $typeid );
	}


	public function testGetTypeIdUnknown()
	{
		$this->setExpectedException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->getTypeIdPublic( 'text/type', 'catalog', 'unknown' );
	}
}


class TestAbstract
	extends \Aimeos\Controller\Common\Catalog\Import\Csv\Base
{
	public function getCachePublic( $type, $name = null )
	{
		return $this->getCache( $type, $name );
	}


	public function getProcessorsPublic( array $mappings )
	{
		return $this->getProcessors( $mappings );
	}


	public function getTypeIdPublic( $path, $domain, $code )
	{
		return $this->getTypeId( $path, $domain, $code );
	}
}


class TestInvalid
{
}
