<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2023
 */


namespace Aimeos\Controller\Jobs\Customer\Group\Import\Xml;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	public static function setUpBeforeClass() : void
	{
		$context = \TestHelper::context();

		$fs = $context->fs( 'fs-import' );
		$fs->has( 'customergroup' ) ?: $fs->mkdir( 'customergroup' );
		$fs->writef( 'customergroup/customergroup_1.xml', __DIR__ . '/_testfiles/customergroup_1.xml' );
		$fs->writef( 'customergroup/customergroup_2.xml', __DIR__ . '/_testfiles/customergroup_2.xml' );

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

		$this->object = new \Aimeos\Controller\Jobs\Customer\Group\Import\Xml\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Customer group import XML', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Imports new and updates existing customer groups from XML files';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->object->run();

		$manager = \Aimeos\MShop::create( $this->context, 'customer/group' );
		$item = $manager->find( 'test' );
		$manager->delete( $item );

		$this->assertEquals( 'Test group', $item->getLabel() );
		$this->assertEquals( 'test', $item->getCode() );
	}
}
