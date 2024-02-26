<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2024
 */


namespace Aimeos\Controller\Jobs\Customer\Import\Xml;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	public static function setUpBeforeClass() : void
	{
		$context = \TestHelper::context();

		$fs = $context->fs( 'fs-import' );
		$fs->has( 'customer/unittest' ) ?: $fs->mkdir( 'customer/unittest' );
		$fs->writef( 'customer/unittest/customer_1.xml', __DIR__ . '/_testfiles/customer_1.xml' );
		$fs->writef( 'customer/unittest/customer_2.xml', __DIR__ . '/_testfiles/customer_2.xml' );

		$fs = $context->fs( 'fs-media' );
		$fs->has( 'path/to' ) ?: $fs->mkdir( 'path/to' );
		$fs->write( 'path/to/file2.jpg', 'test' );
		$fs->write( 'path/to/file.jpg', 'test' );

		$fs = $context->fs( 'fs-mimeicon' );
		$fs->write( 'unknown.png', 'icon' );
	}


	protected function setUp() : void
	{
		$this->context = \TestHelper::context();
		$this->aimeos = \TestHelper::getAimeos();

		$this->object = new \Aimeos\Controller\Jobs\Customer\Import\Xml\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Customer import XML', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Imports new and updates existing customers from XML files';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->object->run();

		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$item = $manager->find( 'me@example.com', ['customer/address', 'customer/property', 'media', 'text'] );
		$manager->delete( $item->getId() );

		$this->assertEquals( 'Test user', $item->getLabel() );
		$this->assertEquals( 1, count( $item->getPropertyItems() ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'text' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'media' ) ) );
		$this->assertEquals( 'ms', $item->getPaymentAddress()->getSalutation() );
		$this->assertEquals( 'Example street', $item->getPaymentAddress()->getAddress1() );
		$this->assertEquals( 'ms', $item->getAddressItems()->first()->getSalutation() );
	}
}
