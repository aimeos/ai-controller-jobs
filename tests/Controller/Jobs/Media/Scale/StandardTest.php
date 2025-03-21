<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2025
 */


namespace Aimeos\Controller\Jobs\Media\Scale;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Media\Scale\Standard( $context, $aimeos );
	}


	protected function tearDown() : void
	{
		unset( $this->object );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Rescale product images', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Rescales product images to the new sizes';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Media\\Manager\\Standard' )
			->onlyMethods( array( 'save', 'scale', 'type' ) )
			->setConstructorArgs( array( $context ) )
			->getMock();

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Media\\Manager\\Standard', $managerStub );

		$managerStub->method( 'type' )->willReturn([ 'media'] );
		$managerStub->expects( $this->atLeast( 1 ) )->method( 'save' );
		$managerStub->expects( $this->atLeast( 1 ) )->method( 'scale' )->willReturnArgument( 0 );


		$object = new \Aimeos\Controller\Jobs\Media\Scale\Standard( $context, $aimeos );
		$object->run();
	}
}
