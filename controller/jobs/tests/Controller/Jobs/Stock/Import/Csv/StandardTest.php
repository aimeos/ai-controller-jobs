<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */


namespace Aimeos\Controller\Jobs\Stock\Import\Csv;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelperJobs::getContext();
		$this->aimeos = \TestHelperJobs::getAimeos();
		$config = $this->context->getConfig();

		$config->set( 'controller/jobs/stock/import/csv/location', __DIR__ . '/_testfiles' );

		$this->object = new \Aimeos\Controller\Jobs\Stock\Import\Csv\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Stock import CSV', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Imports new and updates existing stocks from CSV files';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->object->run();

		$map = [];
		$manager = \Aimeos\MShop::create( $this->context, 'stock' );
		$prodManager = \Aimeos\MShop::create( $this->context, 'product' );

		$filter = $prodManager->filter()->add( ['product.code' => ['U:WH', 'U:CF']] );
		$prodIds = $prodManager->search( $filter )->col( 'product.id', 'product.code' )->toArray();

		$items = $manager->search( $manager->filter()->add( ['stock.productid' => $prodIds] ) );
		$manager->delete( $items->toArray() );

		foreach( $items as $item ) {
			$map[$item->getProductId()] = $item;
		}

		$this->assertEquals( 2, count( $map ) );

		$this->assertEquals( 'test', $map[$prodIds['U:WH']]->getType() );
		$this->assertEquals( 20, $map[$prodIds['U:WH']]->getStockLevel() );
		$this->assertEquals( '2000-01-01 00:00:00', $map[$prodIds['U:WH']]->getDateBack() );

		$this->assertEquals( 'default', $map[$prodIds['U:CF']]->getType() );
		$this->assertEquals( 5, $map[$prodIds['U:CF']]->getStockLevel() );
		$this->assertEquals( null, $map[$prodIds['U:CF']]->getDateBack() );
	}
}
