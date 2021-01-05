<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelperCntl::getContext();
		$this->object = new \Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Standard( $this->context );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context );
	}


	public function testProcess()
	{
		$dom = new \DOMDocument();
		$product = \Aimeos\MShop::create( $this->context, 'product' )->create();

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<lists>
	<text><textitem></textitem></text>
	<media><mediaitem></mediaitem></media>
	<price><priceitem></priceitem></price>
</lists>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$this->assertEquals( 1, count( $product->getListItems( 'text' ) ) );
		$this->assertEquals( 1, count( $product->getListItems( 'media' ) ) );
		$this->assertEquals( 1, count( $product->getListItems( 'price' ) ) );
	}
}
