<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */


namespace Aimeos\Controller\Jobs\Coupon\Import\Csv\Code;


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

		$config->set( 'controller/jobs/product/import/csv/skip-lines', 1 );

		$this->object = new \Aimeos\Controller\Jobs\Coupon\Import\Csv\Code\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Coupon code import CSV', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Imports new and updates existing coupon code from CSV files';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'coupon' );
		$coupon = $manager->save( $manager->create()->setProvider( 'Example' ) );

		$dir = 'tmp/import/couponcode/unittest';
		$filepath = $dir . '/' . $coupon->getId() . '.csv';

		if( !is_dir( $dir ) && mkdir( 'tmp/import/couponcode/unittest', 0775, true ) === false ) {
			throw new \Exception( sprintf( 'Unable to create directory "%1$s"', $dir ) );
		}

		$content = 'code,count,start,end
jobccimport1,3,2000-01-01 00:00:00,
jobccimport2,5,,';

		if( file_put_contents( $filepath, $content ) === false ) {
			throw new \Exception( sprintf( 'Unable to create file "%1$s"', $file ) );
		}

		$this->object->run();


		$codeManager = \Aimeos\MShop::create( $this->context, 'coupon/code' );
		$code1 = $codeManager->find( 'jobccimport1' );
		$code2 = $codeManager->find( 'jobccimport2' );

		$manager->delete( $coupon->getId() );

		$this->assertEquals( 3, $code1->getCount() );
		$this->assertEquals( '2000-01-01 00:00:00', $code1->getDateStart() );
		$this->assertEquals( null, $code1->getDateEnd() );

		$this->assertEquals( 5, $code2->getCount() );
		$this->assertEquals( null, $code2->getDateStart() );
		$this->assertEquals( null, $code2->getDateEnd() );
	}


	public function testRunException()
	{
		$dir = 'tmp/import/couponcode/unittest';
		$filepath = $dir . '/0.csv';

		if( !is_dir( $dir ) && mkdir( 'tmp/import/couponcode/unittest', 0775, true ) === false ) {
			throw new \Exception( sprintf( 'Unable to create directory "%1$s"', $dir ) );
		}

		$content = 'code,count,start,end
jobccimport1,a,b,c';

		if( file_put_contents( $filepath, $content ) === false ) {
			throw new \Exception( sprintf( 'Unable to create file "%1$s"', $file ) );
		}

		$this->object->run();

		$manager = \Aimeos\MShop::create( $this->context, 'coupon/code' );

		$this->expectException( \Aimeos\MShop\Exception::class );
		$manager->find( 'jobccimport1' );
	}
}
