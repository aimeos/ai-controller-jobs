<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2022
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

		$mock = $this->getMockBuilder( '\\Aimeos\\MAdmin\\Log\\Manager\\Standard' )
			->setConstructorArgs( array( $this->context ) )
			->setMethods( array( 'delete' ) )
			->getMock();

		$mock->expects( $this->atLeastOnce() )->method( 'delete' );

		$tmppath = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . DIRECTORY_SEPARATOR . 'tmp';
		$config->set( 'controller/jobs/admin/log/path', $tmppath );
		$config->set( 'controller/jobs/admin/log/limit-days', 0 );

		\Aimeos\MAdmin::inject( '\\Aimeos\\MAdmin\\Log\\Manager\\Standard', $mock );

		if( !is_dir( $tmppath ) && mkdir( $tmppath ) === false ) {
			throw new \RuntimeException( sprintf( 'Unable to create temporary path "%1$s"', $tmppath ) );
		}

		$this->object->run();

		foreach( new \DirectoryIterator( $tmppath ) as $file )
		{
			if( $file->isFile() && $file->getExtension() === 'zip' )
			{
				$container = \Aimeos\MW\Container\Factory::getContainer( $file->getPathName(), 'Zip', 'CSV', [] );
				$container->get( 'unittest facility.csv' );
				unlink( $file->getPathName() );
				return;
			}
		}

		$this->fail( 'Log archive file not found' );
	}
}
