<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2025
 */


namespace Aimeos\Controller\Jobs\Product\Bought;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();
		$this->aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Product\Bought\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Products bought together', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Creates bought together product suggestions';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$stub = $this->getMockBuilder( '\\Aimeos\\MShop\\Product\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->onlyMethods( ['save', 'type'] )
			->getMock();

		$stub->method( 'type' )->willReturn( ['product'] );
		$stub->expects( $this->atLeastOnce() )->method( 'save' );

		$stub = new \Aimeos\MShop\Common\Manager\Decorator\Lists( $stub, $this->context );

		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Product\\Manager\\Standard', $stub );

		$this->object->run();
	}
}
