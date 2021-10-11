<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */


namespace Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Address;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $endpoint;
	private $manager;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelperCntl::getContext();
		$this->manager = \Aimeos\MShop\Supplier\Manager\Factory::create( $this->context );
		$this->endpoint = new \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Done( $this->context, [] );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
	}


	public function testProcess()
	{
		$mapping = array(
			0 => 'supplier.address.languageid',
			1 => 'supplier.address.countryid',
			2 => 'supplier.address.city',
			3 => 'supplier.address.firstname',
			4 => 'supplier.address.lastname',
			5 => 'supplier.address.email',
		);

		$data = array(
			0 => 'de',
			1 => 'de',
			2 => 'Berlin',
			3 => 'John',
			4 => 'Dummy',
			5 => 'john@dummies-domains.de',
		);

		$supplier = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Address\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $supplier, $data );

		$addressItems = $supplier->getAddressItems();
		$address = $addressItems->first();

		$this->assertInstanceOf( '\\Aimeos\\MShop\\Supplier\\Item\\Address\\Iface', $address );
		$this->assertEquals( 1, count( $addressItems ) );

		$this->assertEquals( 'de', $address->getLanguageid() );
		$this->assertEquals( 'DE', $address->getCountryid() );
		$this->assertEquals( 'Berlin', $address->getCity() );
		$this->assertEquals( 'John', $address->getFirstname() );
		$this->assertEquals( 'Dummy', $address->getLastname() );
		$this->assertEquals( 'john@dummies-domains.de', $address->getEmail() );
	}


	/*
	 * There are no ability to add several addresses to one Supplier from CSV Import.
	 * Because of if Supplier has one address, it will be rewrite instead of add one.
	 * Because we cannot identify if it's a new one address or modification of existing one
	 */
	public function testProcessMultiple()
	{
		$this->markTestSkipped( 'There are no ability to add several addresses to one Supplier from CSV Import.' );

		$mapping = array(
			0 => 'address.type',
			1 => 'address.content',
			2 => 'address.type',
			3 => 'address.content',
			4 => 'address.type',
			5 => 'address.content',
			6 => 'address.type',
			7 => 'address.content',
		);

		$data = array(
			0 => 'name',
			1 => 'Job CSV test',
			2 => 'short',
			3 => 'Short: Job CSV test',
			4 => 'long',
			5 => 'Long: Job CSV test',
			6 => 'long',
			7 => 'Long: Job CSV test 2',
		);

		$supplier = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Address\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $supplier, $data );


		$pos = 0;
		$listItems = $supplier->getAddressItems();
		$expected = array(
			0 => array( 'name', 'Job CSV test' ),
			1 => array( 'short', 'Short: Job CSV test' ),
			2 => array( 'long', 'Long: Job CSV test' ),
			3 => array( 'long', 'Long: Job CSV test 2' ),
		);

		$this->assertEquals( 4, count( $listItems ) );

		foreach( $listItems as $listItem )
		{
			$this->assertEquals( $expected[$pos][0], $listItem->getRefItem()->getType() );
			$this->assertEquals( $expected[$pos][1], $listItem->getRefItem()->getContent() );
			$pos++;
		}
	}


	public function testProcessUpdate()
	{
		$mapping = array(
			0 => 'supplier.address.languageid',
			1 => 'supplier.address.countryid',
			2 => 'supplier.address.city',
		);

		$data = array(
			0 => 'de',
			1 => 'de',
			2 => 'Berlin',
		);

		$dataUpdate = array(
			0 => 'ru',
			1 => 'ru',
			2 => 'Moscow',
		);

		$supplier = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Address\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $supplier, $data );
		$object->process( $supplier, $dataUpdate );

		$addressItems = $supplier->getAddressItems();
		$address = $addressItems->first();

		$this->assertEquals( 1, count( $addressItems ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Supplier\\Item\\Address\\Iface', $address );

		$this->assertEquals( 'ru', $address->getLanguageid() );
		$this->assertEquals( 'RU', $address->getCountryid() );
		$this->assertEquals( 'Moscow', $address->getCity() );
	}

	public function testProcessDelete()
	{
		$mapping = array(
			0 => 'address.type',
			1 => 'address.content',
		);

		$data = array(
			0 => 'name',
			1 => 'Job CSV test',
		);

		$supplier = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Address\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $supplier, $data );

		$object = new \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Address\Standard( $this->context, [], $this->endpoint );
		$object->process( $supplier, [] );


		$listItems = $supplier->getListItems();
		$this->assertEquals( 0, count( $listItems ) );
	}


	public function testProcessEmpty()
	{
		$mapping = array(
			0 => 'supplier.address.languageid',
			1 => 'supplier.address.countryid',
			2 => 'supplier.address.city',
			3 => 'supplier.address.languageid',
			4 => 'supplier.address.countryid',
			5 => 'supplier.address.city',
		);

		$data = array(
			0 => 'de',
			1 => 'de',
			2 => 'Berlin',
			3 => '',
			4 => '',
			5 => '',
		);

		$supplier = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Common\Supplier\Import\Csv\Processor\Address\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $supplier, $data );

		$listItems = $supplier->getAddressItems();

		$this->assertEquals( 1, count( $listItems ) );
	}


	/**
	 * @param string $code
	 */
	protected function create( $code )
	{
		return $this->manager->create()->setCode( $code );
	}
}
