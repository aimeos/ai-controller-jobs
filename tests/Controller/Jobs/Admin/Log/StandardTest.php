<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2026
 */


namespace Aimeos\Controller\Jobs\Admin\Log;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;


	protected function setUp() : void
	{
		\Aimeos\MAdmin::cache( true );

		$this->context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Admin\Log\Standard( $this->context, $aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MAdmin::cache( false );
		unset( $this->object, $this->context );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Log cleanup', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Removes the old log entries from the database and archives them (optional)';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$config = $this->context->config();
		$config->set( 'controller/jobs/admin/log/limit-days', 0 );

		$mock = $this->getMockBuilder( '\\Aimeos\\MAdmin\\Log\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['delete'] )
			->getMock();

		$mock->expects( $this->atLeastOnce() )->method( 'delete' );

		\Aimeos\MAdmin::inject( '\\Aimeos\\MAdmin\\Log\\Manager\\Standard', $mock );

		$this->object->run();

		$expected = dirname( __DIR__, 4) . '/tmp/logs/aimeos_' . date( 'Y-m-d' ) . '.log';
		$this->assertFileExists( $expected );

		unlink( $expected );
	}
}
