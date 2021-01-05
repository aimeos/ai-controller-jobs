<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Jobs\Admin\Log;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;


	protected function setUp() : void
	{
		$this->context = \TestHelperJobs::getContext();
		$aimeos = \TestHelperJobs::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Admin\Log\Standard( $this->context, $aimeos );
	}


	protected function tearDown() : void
	{
		$this->object = null;
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
		$config = $this->context->getConfig();

		$mock = $this->getMockBuilder( '\\Aimeos\\MAdmin\\Log\\Manager\\Standard' )
			->setMethods( array( 'delete' ) )
			->setConstructorArgs( array( $this->context ) )
			->getMock();

		$mock->expects( $this->atLeastOnce() )->method( 'delete' );

		$tmppath = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . DIRECTORY_SEPARATOR . 'tmp';
		$name = 'ControllerJobsAdminLogDefaultRun';
		$config->set( 'madmin/log/manager/name', $name );
		$config->set( 'controller/jobs/admin/log/limit-days', 0 );
		$config->set( 'controller/jobs/admin/log/path', $tmppath );

		\Aimeos\MAdmin\Log\Manager\Factory::injectManager( '\\Aimeos\\MAdmin\\Log\\Manager\\' . $name, $mock );

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
