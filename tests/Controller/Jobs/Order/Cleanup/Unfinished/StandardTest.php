<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2025
 */


namespace Aimeos\Controller\Jobs\Order\Cleanup\Unfinished;


class StandardTest
	extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Order\Cleanup\Unfinished\Standard( $context, $aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Removes unfinished orders', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Deletes unfinished orders an makes their products and coupon codes available again';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$context = \TestHelper::context();
		$aimeos = \TestHelper::getAimeos();


		$orderManagerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->onlyMethods( ['iterate', 'delete', 'unblock'] )
			->setConstructorArgs( array( $context ) )
			->getMock();


		\Aimeos\MShop::inject( '\\Aimeos\\MShop\\Order\\Manager\\Standard', $orderManagerStub );


		$orderItem = $orderManagerStub->create()->setId( 2 );

		$orderManagerStub->expects( $this->exactly( 2 ) )->method( 'iterate' )
			->willReturn( map( [$orderItem->getId() => $orderItem] ), null );

		$orderManagerStub->expects( $this->once() )->method( 'delete' );

		$orderManagerStub->expects( $this->once() )->method( 'unblock' );


		$object = new \Aimeos\Controller\Jobs\Order\Cleanup\Unfinished\Standard( $context, $aimeos );
		$object->run();
	}
}
