<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Jobs\Xml\Import;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$aimeos = \TestHelperJobs::getAimeos();
		$this->context = \TestHelperJobs::getContext();

		$this->object = new \Aimeos\Controller\Jobs\Xml\Import\Standard( $this->context, $aimeos );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->context );
		\Aimeos\MShop::cache( false );
	}


	public function testGetName()
	{
		$this->assertEquals( 'All XML import', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Executes all XML importers and rebuild the index';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$config = $this->context->getConfig();
		$config->set( 'controller/jobs/customer/group/import/xml/location', __DIR__ . '/_testfiles' );
		$config->set( 'controller/jobs/customer/import/xml/location', __DIR__ . '/_testfiles' );
		$config->set( 'controller/jobs/attribute/import/xml/location', __DIR__ . '/_testfiles' );
		$config->set( 'controller/jobs/product/import/xml/location', __DIR__ . '/_testfiles' );
		$config->set( 'controller/jobs/supplier/import/xml/location', __DIR__ . '/_testfiles' );
		$config->set( 'controller/jobs/catalog/import/xml/location', __DIR__ . '/_testfiles' );

		$this->object->run();
	}
}
