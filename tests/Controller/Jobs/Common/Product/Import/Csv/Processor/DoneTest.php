<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 */


namespace Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor;


class DoneTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();
		$this->object = new \Aimeos\Controller\Jobs\Common\Product\Import\Csv\Processor\Done( $this->context, [] );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		$this->object = null;
	}


	public function testProcess()
	{
		$product = \Aimeos\MShop::create( $this->context, 'product' )->create();

		$result = $this->object->process( $product, array( 'test' ) );

		$this->assertEquals( array( 'test' ), $result );
	}
}
