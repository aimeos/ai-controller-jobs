<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Common\Product\Import\Csv\Processor\Text;


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
			0 => 'text.type',
			1 => 'text.content',
			2 => 'text.label',
			3 => 'text.languageid',
			4 => 'text.status',
		);

		$data = array(
			0 => 'name',
			1 => 'Job CSV test',
			2 => 'test text',
			3 => 'de',
			4 => 1,
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Text\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$listItems = $product->getListItems();
		$listItem = $listItems->first();

		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );
		$this->assertEquals( 1, count( $listItems ) );

		$this->assertEquals( 1, $listItem->getStatus() );
		$this->assertEquals( 0, $listItem->getPosition() );
		$this->assertEquals( 'default', $listItem->getType() );

		$refItem = $listItem->getRefItem();

		$this->assertEquals( 1, $refItem->getStatus() );
		$this->assertEquals( 'name', $refItem->getType() );
		$this->assertEquals( 'test text', $refItem->getLabel() );
		$this->assertEquals( 'Job CSV test', $refItem->getContent() );
		$this->assertEquals( 'de', $refItem->getLanguageId() );
		$this->assertEquals( 1, $refItem->getStatus() );
	}


	public function testProcessMultiple()
	{
		$mapping = array(
			0 => 'text.type',
			1 => 'text.content',
			2 => 'text.type',
			3 => 'text.content',
			4 => 'text.type',
			5 => 'text.languageid',
			6 => 'text.content',
			7 => 'text.type',
			8 => 'text.languageid',
			9 => 'text.content',
		);

		$data = array(
			0 => 'name',
			1 => 'Job CSV test',
			2 => 'short',
			3 => 'Short: Job CSV test',
			4 => 'long',
			5 => 'de',
			6 => 'Long: Job CSV test',
			7 => 'long',
			8 => 'en',
			9 => 'Long: Job CSV test',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Text\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$pos = 0;
		$listItems = $product->getListItems();
		$expected = array(
			0 => array( 'name', null, 'Job CSV test' ),
			1 => array( 'short', null, 'Short: Job CSV test' ),
			2 => array( 'long', 'de', 'Long: Job CSV test' ),
			3 => array( 'long', 'en', 'Long: Job CSV test' ),
		);

		$this->assertEquals( 4, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $expected[$pos][0], $listItem->getRefItem()->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getLanguageId() );
			$this->assertEquals( $expected[$pos][2], $listItem->getRefItem()->getContent() );
			$pos++;
		}
	}


	public function testProcessUpdate()
	{
		$mapping = array(
			0 => 'text.type',
			1 => 'text.content',
		);

		$data = array(
			0 => 'name',
			1 => 'Job CSV test',
		);

		$dataUpdate = array(
			0 => 'short',
			1 => 'Short: Job CSV test',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Text\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );
		$object->process( $product, $dataUpdate );


		$listItems = $product->getListItems();
		$listItem = $listItems->first();

		$this->assertEquals( 1, count( $listItems ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );

		$this->assertEquals( 'short', $listItem->getRefItem()->getType() );
		$this->assertEquals( 'Short: Job CSV test', $listItem->getRefItem()->getContent() );
	}


	public function testProcessDelete()
	{
		$mapping = array(
			0 => 'text.type',
			1 => 'text.content',
		);

		$data = array(
			0 => 'name',
			1 => 'Job CSV test',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Text\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Text\Standard( $this->context, [], $this->endpoint );
		$object->process( $product, [] );


		$listItems = $product->getListItems();

		$this->assertEquals( 0, count( $listItems ) );
	}


	public function testProcessEmpty()
	{
		$mapping = array(
			0 => 'text.type',
			1 => 'text.content',
			2 => 'text.type',
			3 => 'text.content',
		);

		$data = array(
			0 => 'name',
			1 => 'Job CSV test',
			2 => '',
			3 => '',
		);

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Text\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $product, $data );


		$listItems = $product->getListItems();

		$this->assertEquals( 1, count( $listItems ) );
	}


	public function testProcessListtypes()
	{
		$mapping = array(
			0 => 'text.type',
			1 => 'text.content',
			2 => 'product.lists.type',
			3 => 'text.type',
			4 => 'text.content',
			5 => 'product.lists.type',
		);

		$data = array(
			0 => 'name',
			1 => 'test name',
			2 => 'test',
			3 => 'short',
			4 => 'test short',
			5 => 'default',
		);

		$this->context->getConfig()->set( 'controller/common/product/import/csv/processor/text/listtypes', array( 'default' ) );

		$product = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Product\Import\Csv\Processor\Text\Standard( $this->context, $mapping, $this->endpoint );

		$this->expectException( '\Aimeos\Controller\Common\Exception' );
		$object->process( $product, $data );
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
