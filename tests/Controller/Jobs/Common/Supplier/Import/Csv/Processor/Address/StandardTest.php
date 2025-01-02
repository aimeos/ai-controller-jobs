<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2025
 */


namespace Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Address;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $endpoint;
	private $manager;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelper::context();
		$this->manager = \Aimeos\MShop::create( $this->context, 'supplier' );
		$this->endpoint = new \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Done( $this->context, [] );
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

		$object = new \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Address\Standard( $this->context, $mapping, $this->endpoint );
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


	public function testProcessMultiple()
	{
		$mapping = array(
			0 => 'supplier.address.languageid',
			1 => 'supplier.address.countryid',
			2 => 'supplier.address.city',
			3 => 'supplier.address.languageid',
			4 => 'supplier.address.countryid',
			5 => 'supplier.address.city',
			6 => 'supplier.address.languageid',
			7 => 'supplier.address.countryid',
			8 => 'supplier.address.city',
		);

		$data = array(
			0 => 'de',
			1 => 'DE',
			2 => 'Berlin',
			3 => 'en',
			4 => 'US',
			5 => 'Washington',
			6 => 'fr',
			7 => 'FR',
			8 => 'Paris',
		);

		$supplier = $this->create( 'job_csv_test' );

		$object = new \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Address\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $supplier, $data );


		$pos = 0;
		$addrItems = $supplier->getAddressItems();
		$expected = array(
			0 => array( 'de', 'DE', 'Berlin' ),
			1 => array( 'en', 'US', 'Washington' ),
			2 => array( 'fr', 'FR', 'Paris' ),
		);

		$this->assertEquals( 3, count( $addrItems ) );

		foreach( $addrItems as $addrItem )
		{
			$this->assertEquals( $expected[$pos][0], $addrItem->getLanguageId() );
			$this->assertEquals( $expected[$pos][1], $addrItem->getCountryId() );
			$this->assertEquals( $expected[$pos][2], $addrItem->getCity() );
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

		$object = new \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Address\Standard( $this->context, $mapping, $this->endpoint );
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

		$object = new \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Address\Standard( $this->context, $mapping, $this->endpoint );
		$object->process( $supplier, $data );

		$object = new \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Address\Standard( $this->context, [], $this->endpoint );
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

		$object = new \Aimeos\Controller\Jobs\Common\Supplier\Import\Csv\Processor\Address\Standard( $this->context, $mapping, $this->endpoint );
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
