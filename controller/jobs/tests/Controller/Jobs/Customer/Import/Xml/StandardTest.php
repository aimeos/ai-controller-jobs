<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Jobs\Customer\Import\Xml;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelperJobs::context();
		$this->aimeos = \TestHelperJobs::getAimeos();

		$fs = $this->context->fs( 'fs-media' );
		$fs->has( 'path/to' ) ?: $fs->mkdir( 'path/to' );
		$fs->write( 'path/to/file2.jpg', 'test' );
		$fs->write( 'path/to/file.jpg', 'test' );

		$config = $this->context->config();
		$config->set( 'controller/jobs/customer/import/xml/location', __DIR__ . '/_testfiles' );

		$this->object = new \Aimeos\Controller\Jobs\Customer\Import\Xml\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
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
		error_log( __METHOD__ . ': start' );
		$this->object->run();
		error_log( __METHOD__ . ': run finished' );

		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$item = $manager->find( 'me@example.com', ['customer/address', 'customer/property', 'media', 'text'] );
		$manager->delete( $item->getId() );

		error_log( __METHOD__ . ': assert' );
		$this->assertEquals( 'Test user', $item->getLabel() );
		$this->assertEquals( 1, count( $item->getPropertyItems() ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'text' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'media' ) ) );
		$this->assertEquals( 'ms', $item->getPaymentAddress()->getSalutation() );
		$this->assertEquals( 'Example street', $item->getPaymentAddress()->getAddress1() );
		$this->assertEquals( 'ms', $item->getAddressItems()->first()->getSalutation() );
	}
}
