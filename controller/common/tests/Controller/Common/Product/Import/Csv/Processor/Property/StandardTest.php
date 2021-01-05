<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Property;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $endpoint;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelperCntl::getContext();
		$this->endpoint = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Done( $this->context, [] );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
	}


	public function testProcess()
	{
		$mapping = array(
			0 => 'product.property.type',
			1 => 'product.property.value',
			2 => 'product.property.languageid',
			3 => 'product.property.type',
			4 => 'product.property.value',
		);

		$data = array(
			0 => 'package-weight',
			1 => '3.00',
			2 => 'de',
			3 => 'package-width',
			4 => '50',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Property\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$pos = 0;
		$expected = array(
			array( 'package-weight', '3.00', 'de' ),
			array( 'package-width', '50', null ),
		);

		$items = $product->getPropertyItems();
		$this->assertEquals( 2, count( $items ) );

		foreach( $items as $item )
		{
			$this->assertEquals( $expected[$pos][0], $item->getType() );
			$this->assertEquals( $expected[$pos][1], $item->getValue() );
			$this->assertEquals( $expected[$pos][2], $item->getLanguageId() );
			$pos++;
		}
	}


	public function testProcessUpdate()
	{
		$mapping = array(
			0 => 'product.property.type',
			1 => 'product.property.value',
		);

		$data = array(
			0 => 'package-weight',
			1 => '3.00',
		);

		$dataUpdate = array(
			0 => 'package-size',
			1 => 'S',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Property\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );
		$object->process( $product, $dataUpdate );


		$object->finish(); // test if new type is created
		$manager = \Aimeos\MShop::create( $this->context, 'product/property/type' );
		$manager->delete( $manager->find( 'package-size' )->getId() );

		$items = $product->getPropertyItems();
		$item = $items->first();

		$this->assertEquals( 1, count( $items ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Property\\Iface', $item );

		$this->assertEquals( 'package-size', $item->getType() );
		$this->assertEquals( 'S', $item->getValue() );
	}


	public function testProcessDelete()
	{
		$mapping = array(
			0 => 'product.property.type',
			1 => 'product.property.value',
		);

		$data = array(
			0 => 'package-weight',
			1 => '3.00',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Property\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Property\Standard( $this->context, [], $this->endpoint );
		$object->process( $product, [] );


		$items = $product->getPropertyItems();

		$this->assertEquals( 0, count( $items ) );
	}


	public function testProcessEmpty()
	{
		$mapping = array(
			0 => 'product.property.type',
			1 => 'product.property.value',
			2 => 'product.property.type',
			3 => 'product.property.value',
		);

		$data = array(
			0 => '',
			1 => '',
			2 => 'package-weight',
			3 => '3.00',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Property\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );

		$items = $product->getPropertyItems();

		$this->assertEquals( 1, count( $items ) );
	}


	/**
	 * @param string $code
	 */
	protected function create( $code )
	{
		$manager = \Aimeos\MShop\Product\Manager\Factory::create( $this->context );
		return $manager->create()->setCode( $code );
	}
}
