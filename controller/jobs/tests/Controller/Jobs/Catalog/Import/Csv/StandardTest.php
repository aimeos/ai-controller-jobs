<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2021
 */


namespace Aimeos\Controller\Jobs\Catalog\Import\Csv;


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

		$config->set( 'controller/jobs/catalog/import/csv/skip-lines', 1 );
		$config->set( 'controller/jobs/catalog/import/csv/location', __DIR__ . '/_testfiles/valid' );

		$this->object = new \Aimeos\Controller\Jobs\Catalog\Import\Csv\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		$this->object = null;

		if( file_exists( 'tmp/import.zip' ) ) {
			unlink( 'tmp/import.zip' );
		}
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

		$convert = array(
			1 => 'Text/LatinUTF8',
		);

		$this->context->getConfig()->set( 'controller/jobs/catalog/import/csv/converter', $convert );

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
		$this->object->run();
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
		$config = $this->context->getConfig();
		$mapping = $config->get( 'controller/jobs/catalog/import/csv/mapping', [] );
		$mapping['item'] = array( 0 => 'catalog.label', 1 => 'catalog.code', 2 => 'catalog.parent' );

		$config->set( 'controller/jobs/catalog/import/csv/mapping', $mapping );
		$config->set( 'controller/jobs/catalog/import/csv/location', __DIR__ . '/_testfiles/position' );

		$this->object->run();

		$tree = $this->get( 'job_csv_test' );
		$this->delete( $tree );

		$this->assertEquals( 1, count( $tree->getChildren() ) );
	}


	public function testRunProcessorInvalidMapping()
	{
		$mapping = array(
			'media' => array(
					8 => 'media.url',
			),
		);

		$this->context->getConfig()->set( 'controller/jobs/catalog/import/csv/mapping', $mapping );

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

		$this->context->getConfig()->set( 'controller/jobs/catalog/import/csv/mapping', $mapping );

		$config = $this->context->getConfig();
		$config->set( 'controller/jobs/catalog/import/csv/skip-lines', 0 );
		$config->set( 'controller/jobs/catalog/import/csv/location', __DIR__ . '/_testfiles/invalid' );

		$this->object = new \Aimeos\Controller\Jobs\Catalog\Import\Csv\Standard( $this->context, $this->aimeos );

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->run();
	}


	public function testRunBackup()
	{
		$config = $this->context->getConfig();
		$config->set( 'controller/jobs/catalog/import/csv/container/type', 'Zip' );
		$config->set( 'controller/jobs/catalog/import/csv/location', 'tmp/import.zip' );
		$config->set( 'controller/jobs/catalog/import/csv/backup', 'tmp/test-%Y-%m-%d.zip' );

		if( copy( __DIR__ . '/_testfiles/import.zip', 'tmp/import.zip' ) === false ) {
			throw new \RuntimeException( 'Unable to copy test file' );
		}

		$this->object->run();

		$filename = strftime( 'tmp/test-%Y-%m-%d.zip' );
		$this->assertTrue( file_exists( $filename ) );

		unlink( $filename );
	}


	public function testRunBackupInvalid()
	{
		$config = $this->context->getConfig();
		$config->set( 'controller/jobs/catalog/import/csv/container/type', 'Zip' );
		$config->set( 'controller/jobs/catalog/import/csv/location', 'tmp/import.zip' );
		$config->set( 'controller/jobs/catalog/import/csv/backup', 'tmp/notexist/import.zip' );

		if( copy( __DIR__ . '/_testfiles/import.zip', 'tmp/import.zip' ) === false ) {
			throw new \RuntimeException( 'Unable to copy test file' );
		}

		$this->expectException( '\\Aimeos\\Controller\\Jobs\\Exception' );
		$this->object->run();
	}


	protected function delete( \Aimeos\MShop\Catalog\Item\Iface $tree, array $domains = [] )
	{
		$catalogManager = \Aimeos\MShop\Catalog\Manager\Factory::create( $this->context );

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
		$manager = \Aimeos\MShop\Catalog\Manager\Factory::create( $this->context );
		$root = $manager->find( $catcode );

		return $manager->getTree( $root->getId(), $domains, \Aimeos\MW\Tree\Manager\Base::LEVEL_TREE );
	}
}
