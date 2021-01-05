<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2013
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Jobs\Index\Rebuild;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		$context = \TestHelperJobs::getContext();
		$aimeos = \TestHelperJobs::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Index\Rebuild\Standard( $context, $aimeos );
	}


	protected function tearDown() : void
	{
		$this->object = null;
	}


	public function testGetName()
	{
		$this->assertEquals( 'Index rebuild', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Rebuilds the index for searching products';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$context = \TestHelperJobs::getContext();
		$aimeos = \TestHelperJobs::getAimeos();


		$name = 'ControllerJobsCatalogIndexRebuildDefaultRun';
		$context->getConfig()->set( 'mshop/index/manager/name', $name );


		$indexManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Index\\Manager\\Standard' )
			->setMethods( array( 'rebuild', 'cleanup' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		\Aimeos\MShop\Catalog\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Index\\Manager\\' . $name, $indexManagerStub );


		$indexManagerStub->expects( $this->once() )->method( 'rebuild' );
		$indexManagerStub->expects( $this->once() )->method( 'cleanup' );


		$object = new \Aimeos\Controller\Jobs\Index\Rebuild\Standard( $context, $aimeos );
		$object->run();
	}
}
