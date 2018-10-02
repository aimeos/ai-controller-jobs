<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2018
 */


namespace Aimeos\Controller\Jobs\Product\Bought;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	protected function setUp()
	{
		\Aimeos\MShop\Factory::setCache( true );

		$this->context = \TestHelperJobs::getContext();
		$this->aimeos = \TestHelperJobs::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Product\Bought\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown()
	{
		\Aimeos\MShop\Factory::setCache( false );
		\Aimeos\MShop\Factory::clear();

		$this->object = null;
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
		$stub = $this->getMockBuilder( '\\Aimeos\\MShop\\Product\\Manager\\Lists\\Standard' )
			->setConstructorArgs( array( $this->context ) )
			->setMethods( array( 'deleteItems', 'saveItem' ) )
			->getMock();

		\Aimeos\MShop\Factory::injectManager( $this->context, 'product/lists', $stub );

		$stub->expects( $this->atLeastOnce() )->method( 'deleteItems' );
		$stub->expects( $this->atLeastOnce() )->method( 'saveItem' );

		$this->object->run();
	}
}