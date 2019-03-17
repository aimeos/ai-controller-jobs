<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
 */


namespace Aimeos\Controller\Jobs\Stock\Import\Csv;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	protected function setUp()
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelperJobs::getContext();
		$this->aimeos = \TestHelperJobs::getAimeos();
		$config = $this->context->getConfig();

		$config->set( 'controller/jobs/stock/import/csv/location', __DIR__ . '/_testfiles' );

		$this->object = new \Aimeos\Controller\Jobs\Stock\Import\Csv\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown()
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

		$map = $ids = [];
		$manager = \Aimeos\MShop::create( $this->context, 'stock' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'stock.productcode', ['unittest-csv', 'unittest-csv2'] ) );

		foreach( $manager->searchItems( $search ) as $item )
		{
			$map[$item->getProductCode()] = $item;
			$ids[] = $item->getId();
		}

		$this->assertEquals( 2, count( $map ) );

		$this->assertEquals( 'test', $map['unittest-csv']->getType() );
		$this->assertEquals( 10, $map['unittest-csv']->getStockLevel() );
		$this->assertEquals( '2000-01-01 00:00:00', $map['unittest-csv']->getDateBack() );

		$this->assertEquals( 'default', $map['unittest-csv2']->getType() );
		$this->assertEquals( null, $map['unittest-csv2']->getStockLevel() );
		$this->assertEquals( null, $map['unittest-csv2']->getDateBack() );
	}
}
