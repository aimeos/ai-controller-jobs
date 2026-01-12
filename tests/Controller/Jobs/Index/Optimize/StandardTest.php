<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2013
 * @copyright Aimeos (aimeos.org), 2015-2026
 */


namespace Aimeos\Controller\Jobs\Index\Optimize;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Index\Optimize\Standard( $context, $aimeos );
	}


	protected function tearDown() : void
	{
		$this->object = null;
	}


	public function testGetName()
	{
		$this->assertEquals( 'Index optimization', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Optimizes the index for searching products faster';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$indexManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Index\\Manager\\Standard' )
			->onlyMethods( array( 'optimize' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Index\\Manager\\Standard', $indexManagerStub );

		$indexManagerStub->expects( $this->once() )->method( 'optimize' );

		$object = new \Aimeos\Controller\Jobs\Index\Optimize\Standard( $context, $aimeos );
		$object->run();
	}
}
