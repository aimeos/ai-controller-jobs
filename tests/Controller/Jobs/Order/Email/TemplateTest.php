<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2026
 */

namespace Aimeos\Controller\Jobs\Order\Email;

use PHPUnit\Framework\Attributes\DataProvider;


class TemplateTest extends \PHPUnit\Framework\TestCase
{
	public static function templates() : array
	{
		return [
			['summary-html.mjml'],
			['summary-pdf.php'],
			['delivery/html.php'],
			['payment/html.php'],
		];
	}


	public static function invoiceTemplates() : array
	{
		return [
			['delivery/html.mjml'],
			['delivery/html.php'],
			['payment/html.mjml'],
			['payment/html.php'],
		];
	}


	#[DataProvider('templates')]
	public function testProductCodeIsHtmlEncoded( string $file )
	{
		$path = dirname( __DIR__, 5 ) . '/templates/controller/jobs/order/email/' . $file;
		$template = file_get_contents( $path );

		$this->assertStringContainsString( '<?= $enc->html( $product->getProductCode() ) ?>', $template );
		$this->assertStringNotContainsString( '<?= $product->getProductCode() ?>', $template );
	}


	#[DataProvider('invoiceTemplates')]
	public function testInvoiceNumberIsHtmlEncoded( string $file )
	{
		$path = dirname( __DIR__, 5 ) . '/templates/controller/jobs/order/email/' . $file;
		$template = file_get_contents( $path );

		$this->assertStringContainsString( '<?= $enc->html( sprintf(', $template );
		$this->assertStringNotContainsString( '<?= sprintf(', $template );
	}
}
