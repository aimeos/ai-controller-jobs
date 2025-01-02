<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 */


namespace Aimeos\Controller;


class JobsTest extends \PHPUnit\Framework\TestCase
{
	public function testCreateNotExisting()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->expectException( \LogicException::class );
		\Aimeos\Controller\Jobs::create( $context, $aimeos, 'unknown' );
	}


	public function testGet()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$list = \Aimeos\Controller\Jobs::get( $context, $aimeos, $aimeos->getCustomPaths( 'controller/jobs' ) );

		$this->assertGreaterThanOrEqual( 38, count( $list ) );
	}
}
