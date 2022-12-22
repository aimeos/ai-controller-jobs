<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2022
 */


namespace Aimeos\Controller\Jobs\Catalog\Import\Csv;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	public static function setUpBeforeClass() : void
	{
		$context = \TestHelper::context();

		$fs = $context->fs( 'fs-import' );
		$fs->has( 'catalog' ) ?: $fs->mkdir( 'catalog' );
		$fs->writef( 'catalog/empty.csv', __DIR__ . '/_testfiles/empty.csv' );

		$fs->has( 'catalog/valid' ) ?: $fs->mkdir( 'catalog/valid' );
		$fs->writef( 'catalog/valid/catalog.csv', __DIR__ . '/_testfiles/valid/catalog.csv' );

		$fs->has( 'catalog/invalid' ) ?: $fs->mkdir( 'catalog/invalid' );
		$fs->writef( 'catalog/invalid/catalog.csv', __DIR__ . '/_testfiles/invalid/catalog.csv' );

		$fs->has( 'catalog/position' ) ?: $fs->mkdir( 'catalog/position' );
		$fs->writef( 'catalog/position/catalog.csv', __DIR__ . '/_testfiles/position/catalog.csv' );

		$fs = $context->fs( 'fs-media' );
		$fs->has( 'path/to' ) ?: $fs->mkdir( 'path/to' );
		$fs->write( 'path/to/file2.jpg', 'test' );
		$fs->write( 'path/to/file.jpg', 'test' );

		$fs = $context->fs( 'fs-mimeicon' );
		$fs->write( 'unknown.png', 'icon' );
	}


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();
		$this->aimeos = \TestHelper::getAimeos();

		$config = $this->context->config();
		$config->set( 'controller/jobs/catalog/import/csv/skip-lines', 1 );
		$config->set( 'controller/jobs/catalog/import/csv/location', 'catalog/valid' );

		$this->object = new \Aimeos\Controller\Jobs\Catalog\Import\Csv\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Catalog import CSV', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Imports new and updates existing categories from CSV files';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$catcodes = array( 'job_csv_test', 'job_csv_test2', 'job_csv_test3', 'job_csv_test4' );
		$domains = array( 'media', 'text' );

		$this->object->run();

		$tree = $this->get( 'job_csv_test', $domains );
		$this->delete( $tree, $domains );

		$this->assertEquals( 2, count( $tree->getListItems() ) );

		foreach( $tree->getChildren() as $node ) {
			$this->assertEquals( 2, count( $node->getListItems() ) );
		}
	}


	public function testRunUpdate()
	{
		$fs = $this->context->fs( 'fs-import' );
		$fs->writef( 'catalog/valid/catalog.csv', __DIR__ . '/_testfiles/valid/catalog.csv' );

		$this->object->run();

		$fs = $this->context->fs( 'fs-import' );
		$fs->writef( 'catalog/valid/catalog.csv', __DIR__ . '/_testfiles/valid/catalog.csv' );

		$this->object->run();

		$tree = $this->get( 'job_csv_test', ['media', 'text'] );
		$this->delete( $tree, ['media', 'text'] );

		$this->assertEquals( 2, count( $tree->getListItems() ) );

		foreach( $tree->getChildren() as $node ) {
			$this->assertEquals( 2, count( $node->getListItems() ) );
		}
	}


	public function testRunPosition()
	{
		$config = $this->context->config();
		$mapping = $config->get( 'controller/jobs/catalog/import/csv/mapping', [] );
		$mapping['item'] = array( 0 => 'catalog.label', 1 => 'catalog.code', 2 => 'catalog.parent' );

		$config->set( 'controller/jobs/catalog/import/csv/mapping', $mapping );
		$config->set( 'controller/jobs/catalog/import/csv/location', 'catalog/position' );

		$this->object->run();

		$tree = $this->get( 'job_csv_test' );
		$this->delete( $tree );

		$this->assertEquals( 1, count( $tree->getChildren() ) );
	}


	public function testRunProcessorInvalidMapping()
	{
		$config = $this->context->config();
		$config->set( 'controller/jobs/catalog/import/csv/location', 'catalog' );

		$mapping = array(
			'media' => array(
					8 => 'media.url',
			),
		);

		$this->context->config()->set( 'controller/jobs/catalog/import/csv/mapping', $mapping );

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->run();
	}


	public function testRunProcessorInvalidData()
	{
		$mapping = array(
			'item' => array(
				0 => 'catalog.code',
				1 => 'catalog.parent',
				2 => 'catalog.label',
				3 => 'catalog.status',
			),
			'text' => array(
				4 => 'text.type',
				5 => 'text.content',
			),
			'media' => array(
				6 => 'media.url',
				7 => 'catalog.lists.type',
			),
		);

		$this->context->config()->set( 'controller/jobs/catalog/import/csv/mapping', $mapping );

		$config = $this->context->config();
		$config->set( 'controller/jobs/catalog/import/csv/skip-lines', 0 );
		$config->set( 'controller/jobs/catalog/import/csv/location', 'catalog/invalid' );

		$this->object = new \Aimeos\Controller\Jobs\Catalog\Import\Csv\Standard( $this->context, $this->aimeos );

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->run();
	}


	public function testRunBackup()
	{
		$config = $this->context->config();
		$config->set( 'controller/jobs/catalog/import/csv/backup', 'backup-%Y-%m-%d.csv' );
		$config->set( 'controller/jobs/catalog/import/csv/location', 'catalog' );

		$this->object->run();

		$filename = \Aimeos\Base\Str::strtime( 'backup-%Y-%m-%d.csv' );
		$this->assertTrue( $this->context->fs( 'fs-import' )->has( $filename ) );

		$this->context->fs( 'fs-import' )->rm( $filename );
	}


	protected function delete( \Aimeos\MShop\Catalog\Item\Iface $tree, array $domains = [] )
	{
		$catalogManager = \Aimeos\MShop::create( $this->context, 'catalog' );

		foreach( $domains as $domain )
		{
			$manager = \Aimeos\MShop::create( $this->context, $domain );

			foreach( $tree->getListItems( $domain ) as $listItem ) {
				$manager->delete( $listItem->getRefItem()->getId() );
			}
		}

		foreach( $tree->getChildren() as $node ) {
			$this->delete( $node, $domains );
		}

		$catalogManager->delete( $tree->getId() );
	}


	protected function get( $catcode, array $domains = [] )
	{
		$manager = \Aimeos\MShop::create( $this->context, 'catalog' );
		$root = $manager->find( $catcode );

		return $manager->getTree( $root->getId(), $domains, \Aimeos\MW\Tree\Manager\Base::LEVEL_TREE );
	}
}
