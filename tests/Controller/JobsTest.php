<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2023
 */


namespace Aimeos\Controller;


class JobsTest extends \PHPUnit\Framework\TestCase
{
	public function testCreateEmpty()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->expectException( \LogicException::class );
		\Aimeos\Controller\Jobs::create( $context, $aimeos, "\t\n" );
	}


	public function testCreateInvalidName()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->expectException( \LogicException::class );
		\Aimeos\Controller\Jobs::create( $context, $aimeos, '%^' );
	}


	public function testCreateNotExisting()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->expectException( \LogicException::class );
		\Aimeos\Controller\Jobs::create( $context, $aimeos, 'notexist' );
	}


	public function testGet()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$list = \Aimeos\Controller\Jobs::get( $context, $aimeos, $aimeos->getCustomPaths( 'controller/jobs' ) );

		$this->assertGreaterThanOrEqual( 38, count( $list ) );
	}
}
