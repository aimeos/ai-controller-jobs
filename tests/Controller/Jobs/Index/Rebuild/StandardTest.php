<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2013
 * @copyright Aimeos (aimeos.org), 2015-2024
 */


namespace Aimeos\Controller\Jobs\Index\Rebuild;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Index\Rebuild\Standard( $context, $aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object );
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
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$indexManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Index\\Manager\\Standard' )
			->onlyMethods( array( 'rebuild', 'cleanup' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Index\\Manager\\Standard', $indexManagerStub );

		$indexManagerStub->expects( $this->once() )->method( 'rebuild' )->will( $this->returnSelf() );
		$indexManagerStub->expects( $this->once() )->method( 'cleanup' )->will( $this->returnSelf() );

		$object = new \Aimeos\Controller\Jobs\Index\Rebuild\Standard( $context, $aimeos );
		$object->run();
	}
}
