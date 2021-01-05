<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Media;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->context = \TestHelperCntl::getContext();

		$fs = $this->context->getFileSystemManager()->get( 'fs-media' );
		$fs->has( 'path/to' ) ?: $fs->mkdir( 'path/to' );
		$fs->write( 'path/to/file2.jpg', 'test2' );
		$fs->write( 'path/to/file.jpg', 'test' );

		$this->object = new \Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Media\Standard( $this->context );
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
<media>
	<mediaitem lists.type="default">
		<media.type><![CDATA[test]]></media.type>
		<media.languageid><![CDATA[de]]></media.languageid>
		<media.url><![CDATA[path/to/file.jpg]]></media.url>
		<media.preview><![CDATA[path/to/preview.jpg]]></media.preview>
	</mediaitem>
	<mediaitem lists.type="test">
		<media.type><![CDATA[default]]></media.type>
		<media.languageid><![CDATA[]]></media.languageid>
		<media.url><![CDATA[path/to/file2.jpg]]></media.url>
		<media.previews><![CDATA[{"1":"path/to/preview.jpg"}]]></media.previews>
	</mediaitem>
</media>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$pos = 0;
		$expected = [
			['default', 'test', 'de', 'path/to/file.jpg', [1 => 'path/to/preview.jpg']],
			['test', 'default', '', 'path/to/file2.jpg', [1 => 'path/to/preview.jpg']],
		];

		$listItems = $product->getListItems( 'media' );
		$this->assertEquals( 2, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $expected[$pos][0], $listItem->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getType() );
			$this->assertEquals( $expected[$pos][2], $listItem->getRefItem()->getLanguageId() );
			$this->assertEquals( $expected[$pos][3], $listItem->getRefItem()->getUrl() );
			$this->assertEquals( $expected[$pos][4], $listItem->getRefItem()->getPreviews() );
			$pos++;
		}
	}


	public function testProcessUpdate()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'media' );
		$listManager = \Aimeos\MShop::create( $this->context, 'product/lists' );
		$product = \Aimeos\MShop::create( $this->context, 'product' )->create();

		$product->addListItem( 'media',
			$listManager->create()->setType( 'default' )->setId( 1 ),
			$manager->create()->setType( 'test' )->setLanguageId( 'de' )->setUrl( 'path/to/file.jpg' )
		);
		$product->addListItem( 'media',
			$listManager->create()->setType( 'test' )->setId( 2 ),
			$manager->create()->setType( 'default' )->setLanguageId( '' )->setUrl( 'path/to/file2.jpg' )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<media>
	<mediaitem lists.type="test">
		<media.type><![CDATA[default]]></media.type>
		<media.languageid><![CDATA[]]></media.languageid>
		<media.url><![CDATA[path/to/file2.jpg]]></media.url>
	</mediaitem>
	<mediaitem lists.type="default">
		<media.type><![CDATA[test]]></media.type>
		<media.languageid><![CDATA[de]]></media.languageid>
		<media.url><![CDATA[path/to/file.jpg]]></media.url>
	</mediaitem>
</media>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$pos = 0;
		$expected = [
			['test', 'default', '', 'path/to/file2.jpg'],
			['default', 'test', 'de', 'path/to/file.jpg'],
		];

		$listItems = $product->getListItems();
		$this->assertEquals( 2, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $expected[$pos][0], $listItem->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getType() );
			$this->assertEquals( $expected[$pos][2], $listItem->getRefItem()->getLanguageId() );
			$this->assertEquals( $expected[$pos][3], $listItem->getRefItem()->getUrl() );
			$pos++;
		}
	}


	public function testProcessDelete()
	{
		$dom = new \DOMDocument();
		$manager = \Aimeos\MShop::create( $this->context, 'media' );
		$listManager = \Aimeos\MShop::create( $this->context, 'product/lists' );
		$product = \Aimeos\MShop::create( $this->context, 'product' )->create();

		$product->addListItem( 'media',
			$listManager->create()->setType( 'default' )->setId( 1 ),
			$manager->create()->setType( 'test' )->setLanguageId( 'de' )->setUrl( 'path/to/file.jpg' )
		);
		$product->addListItem( 'media',
			$listManager->create()->setType( 'test' )->setId( 2 ),
			$manager->create()->setType( 'default' )->setLanguageId( '' )->setUrl( 'path/to/file2.jpg' )
		);

		$dom->loadXML( '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<media>
	<mediaitem lists.type="test">
		<media.type><![CDATA[default]]></media.type>
		<media.languageid><![CDATA[]]></media.languageid>
		<media.url><![CDATA[path/to/file2.jpg]]></media.url>
	</mediaitem>
</media>' );

		$product = $this->object->process( $product, $dom->firstChild );

		$pos = 0;
		$expected = [['test', 'default', '', 'path/to/file2.jpg']];

		$listItems = $product->getListItems();
		$this->assertEquals( 1, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $expected[$pos][0], $listItem->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getType() );
			$this->assertEquals( $expected[$pos][2], $listItem->getRefItem()->getLanguageId() );
			$this->assertEquals( $expected[$pos][3], $listItem->getRefItem()->getUrl() );
			$pos++;
		}
	}
}
