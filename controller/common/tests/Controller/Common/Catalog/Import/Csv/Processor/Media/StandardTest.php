<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
 */


namespace Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Media;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $endpoint;


	protected function setUp()
	{
		\Aimeos\MShop\Factory::setCache( true );

		$this->context = \TestHelperCntl::getContext();
		$this->endpoint = new \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Done( $this->context, [] );
	}


	protected function tearDown()
	{
		\Aimeos\MShop\Factory::setCache( false );
		\Aimeos\MShop\Factory::clear();
	}


	public function testProcess()
	{
		$mapping = array(
			0 => 'media.languageid',
			1 => 'media.label',
			2 => 'media.mimetype',
			3 => 'media.preview',
			4 => 'media.url',
			5 => 'media.status',
		);

		$data = array(
			0 => 'de',
			1 => 'test image',
			2 => 'image/jpeg',
			3 => 'path/to/preview',
			4 => 'path/to/file',
			5 => 1,
		);

		$catalog = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Media\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $catalog, $data );


		$listItems = $catalog->getListItems();
		$listItem = reset( $listItems );

		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );
		$this->assertEquals( 1, count( $listItems ) );

		$this->assertEquals( 1, $listItem->getStatus() );
		$this->assertEquals( 0, $listItem->getPosition() );
		$this->assertEquals( 'media', $listItem->getDomain() );
		$this->assertEquals( 'default', $listItem->getType() );

		$refItem = $listItem->getRefItem();

		$this->assertEquals( 1, $refItem->getStatus() );
		$this->assertEquals( 'default', $refItem->getType() );
		$this->assertEquals( 'test image', $refItem->getLabel() );
		$this->assertEquals( 'image/jpeg', $refItem->getMimetype() );
		$this->assertEquals( 'path/to/preview', $refItem->getPreview() );
		$this->assertEquals( 'path/to/file', $refItem->getUrl() );
		$this->assertEquals( 'de', $refItem->getLanguageId() );
	}


	public function testProcessMultiple()
	{
		$mapping = array(
			0 => 'media.url',
		);

		$data = array(
			0 => "path/to/0\npath/to/1\npath/to/2\npath/to/3",
		);

		$catalog = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Media\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $catalog, $data );


		$pos = 0;
		$listItems = $catalog->getListItems();
		$expected = array( 'path/to/0', 'path/to/1', 'path/to/2', 'path/to/3' );

		$this->assertEquals( 4, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $expected[$pos], $listItem->getRefItem()->getUrl() );
			$pos++;
		}
	}


	public function testProcessMultipleFields()
	{
		$mapping = array(
			0 => 'media.url',
			1 => 'media.url',
			2 => 'media.url',
			3 => 'media.url',
		);

		$data = array(
			0 => 'path/to/0',
			1 => 'path/to/1',
			2 => 'path/to/2',
			3 => 'path/to/3',
		);

		$catalog = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Media\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $catalog, $data );


		$pos = 0;
		$listItems = $catalog->getListItems();

		$this->assertEquals( 4, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $data[$pos], $listItem->getRefItem()->getUrl() );
			$pos++;
		}
	}


	public function testProcessUpdate()
	{
		$mapping = array(
			0 => 'media.url',
			1 => 'media.languageid',
		);

		$data = array(
			0 => 'path/to/file',
			1 => 'de',
		);

		$dataUpdate = array(
			0 => 'path/to/new',
			1 => '',
		);

		$catalog = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Media\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $catalog, $data );
		$object->process( $catalog, $dataUpdate );


		$listItems = $catalog->getListItems();
		$listItem = reset( $listItems );

		$this->assertEquals( 1, count( $listItems ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );

		$this->assertEquals( 'path/to/new', $listItem->getRefItem()->getUrl() );
		$this->assertEquals( null, $listItem->getRefItem()->getLanguageId() );
	}


	public function testProcessDelete()
	{
		$mapping = array(
			0 => 'media.url',
		);

		$data = array(
			0 => '/path/to/file',
		);

		$catalog = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Media\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $catalog, $data );

		$object = new \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Media\Standard( $this->context, [], $this->endpoint );
		$object->process( $catalog, [] );


		$listItems = $catalog->getListItems();

		$this->assertEquals( 0, count( $listItems ) );
	}


	public function testProcessEmpty()
	{
		$mapping = array(
			0 => 'media.url',
			1 => 'media.url',
		);

		$data = array(
			0 => 'path/to/file',
			1 => '',
		);

		$catalog = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Media\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $catalog, $data );


		$listItems = $catalog->getListItems();

		$this->assertEquals( 1, count( $listItems ) );
	}


	public function testProcessListtypes()
	{
		$mapping = array(
			0 => 'media.url',
			1 => 'catalog.lists.type',
			2 => 'media.url',
			3 => 'catalog.lists.type',
		);

		$data = array(
			0 => 'path/to/file',
			1 => 'download',
			2 => 'path/to/file2',
			3 => 'default',
		);

		$this->context->getConfig()->set( 'controller/common/catalog/import/csv/processor/media/listtypes', array( 'default' ) );

		$catalog = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Catalog\Import\Csv\Processor\Media\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $catalog, $data );


		$listItems = $catalog->getListItems();
		$listItem = reset( $listItems );

		$this->assertEquals( 1, count( $listItems ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Item\\Lists\\Iface', $listItem );

		$this->assertEquals( 'default', $listItem->getType() );
		$this->assertEquals( 'path/to/file2', $listItem->getRefItem()->getUrl() );
	}


	/**
	 * @param string $code
	 */
	protected function create( $code )
	{
		$manager = \Aimeos\MShop\Catalog\Manager\Factory::createManager( $this->context );

		$item = $manager->createItem();
		$item->setCode( $code );

		return $item;
	}
}