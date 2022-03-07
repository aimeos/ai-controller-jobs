<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2022
 */


namespace Aimeos\Controller\Jobs\Product\Import\Xml;


class FactoryTest extends \PHPUnit\Framework\TestCase
{
	public function testCreateController()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$obj = \Aimeos\Controller\Jobs\Product\Import\Xml\Factory::create( $context, $aimeos );
		$this->assertInstanceOf( '\\Aimeos\\Controller\\Jobs\\Iface', $obj );
	}


	public function testFactoryExceptionWrongName()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		\Aimeos\Controller\Jobs\Product\Import\Xml\Factory::create( $context, $aimeos, 'Wrong$$$Name' );
	}


	public function testFactoryExceptionWrongClass()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		\Aimeos\Controller\Jobs\Product\Import\Xml\Factory::create( $context, $aimeos, 'WrongClass' );
	}


	public function testFactoryExceptionWrongInterface()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		\Aimeos\Controller\Jobs\Product\Import\Xml\Factory::create( $context, $aimeos, 'Factory' );
	}
}
