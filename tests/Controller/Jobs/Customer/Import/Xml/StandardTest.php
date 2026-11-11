<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
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
		$item = $manager->find( 'me@example.com', ['customer/address', 'customer/property', 'group', 'media', 'text'] );
		$manager->delete( $item->getId() );

		$this->assertEquals( 'Test user', $item->getLabel() );
		$this->assertEquals( ['unitgroup'], $item->getRefItems( 'group' )->getCode()->values()->all() );
		$this->assertEquals( 1, count( $item->getPropertyItems() ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'text' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'media' ) ) );
		$this->assertEquals( 'ms', $item->getPaymentAddress()->getSalutation() );
		$this->assertEquals( 'Example street', $item->getPaymentAddress()->getAddress1() );
		$this->assertEquals( 'ms', $item->getAddressItems()->first()->getSalutation() );
	}


	public function testRunDenied()
	{
		$this->context->config()->set( 'controller/jobs/customer/import/xml/processor/group/denied', ['unitgroup'] );
		$this->context->fs( 'fs-import' )->writef( 'customer/unittest/customer_1.xml', __DIR__ . '/_testfiles/customer_1.xml' );

		$this->object->run();

		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$item = $manager->find( 'me@example.com', ['group'] );
		$manager->delete( $item->getId() );

		$this->assertEquals( 'Test user', $item->getLabel() );
		$this->assertEquals( [], $item->getGroups() );
	}


	public function testRunPrivileged()
	{
		$this->context->config()->set( 'controller/jobs/customer/import/xml/processor/group/denied', ['unitgroup2'] );
		$this->context->fs( 'fs-import' )->writef( 'customer/unittest/customer_1.xml', __DIR__ . '/_testfiles/customer_1.xml' );

		$grpId = \Aimeos\MShop::create( $this->context, 'group' )->find( 'unitgroup2' )->getId();
		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$item = $manager->create()->setCode( 'me@example.com' )->setLabel( 'privileged' )->setGroups( [$grpId] );
		$id = $manager->save( $item )->getId();

		try
		{
			$this->object->run();
			$item = $manager->find( 'me@example.com', ['group'] );
		}
		finally
		{
			$manager->delete( $id );
		}

		$this->assertEquals( 'privileged', $item->getLabel() );
		$this->assertEquals( [$grpId], array_values( $item->getGroups() ) );
	}


	public function testPrivilegedRoles()
	{
		$item = new class( new \Aimeos\MShop\Common\Item\Address\Standard( 'customer.' ), 'customer.' ) extends \Aimeos\MShop\Customer\Item\Standard
		{
			public array $roles = ['ROLE_USER'];
			public function getRoles() : array { return $this->roles; }
		};
		$method = new \ReflectionMethod( \Aimeos\Controller\Jobs\Customer\Import\Xml\Standard::class, 'privileged' );

		$this->assertFalse( $method->invoke( $this->object, $item, [] ) );

		$item->roles = ['ROLE_USER', 'ROLE_ADMIN'];
		$this->assertTrue( $method->invoke( $this->object, $item, [] ) );
	}


	public function testPrivilegedSuper()
	{
		$item = \Aimeos\MShop::create( $this->context, 'customer' )->create()->set( '.super', true );
		$method = new \ReflectionMethod( \Aimeos\Controller\Jobs\Customer\Import\Xml\Standard::class, 'privileged' );

		$this->assertTrue( $method->invoke( $this->object, $item, [] ) );
		$this->assertFalse( $method->invoke( $this->object, $item->set( '.super', false ), [] ) );
	}
}
