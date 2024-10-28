<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2022-2024
 */

/** Available data
 * - orderProductItem : Order product item
 * - orderAddressItem : Order address item
 * - voucher : Voucher code
 */


$enc = $this->encoder();

$this->pdf->setMargins( 15, 30, 15 );
$this->pdf->setAutoPageBreak( true, 30 );
$this->pdf->setTitle( sprintf( $this->translate( 'controller/jobs', 'Voucher %1$s' ), $this->get( 'voucher' ) ) );
$this->pdf->setFont( 'dejavusans', '', 10 );

$vmargin = [
	'h1' => [ // HTML tag
		0 => ['h' => 1.5, 'n' => 0], // space before = h * n
		1 => ['h' => 1.5, 'n' => 3] // space after = h * n
	],
	'h2' => [
		0 => ['h' => 1.5, 'n' => 10],
		1 => ['h' => 1.5, 'n' => 5]
	],
	'ul' => [
		0 => ['h' => 0, 'n' => 0],
		1 => ['h' => 0, 'n' => 0]
	],
];

$this->pdf->setHtmlVSpace( $vmargin );
$this->pdf->setListIndentWidth( 4 );

$this->pdf->setHeaderFunction( function( $pdf ) {
	/* Add background image
	$margin = $pdf->getBreakMargin();
	$pdf->setAutoPageBreak( false, 0 );
	$pdf->image( __DIR__ . '/pdf-background.png', 0, 0, $pdf->getPageWidth(), $pdf->getPageHeight(), 'PNG' );
	$pdf->setAutoPageBreak( true, $margin );
	$pdf->setPageMark();
	*/

	$pdf->writeHtmlCell( 210, 20, 0, 0, '
		<div style="background-color: #103050; color: #ffffff; text-align: center; font-weight: bold">
			<div style="font-size: 0px"> </div>
			<!-- img src="https://aimeos.org/fileadmin/logos/logo-aimeos-white.png" height="30" -->
			Example company
			<div style="font-size: 0px"> </div>
		</div>
	' );
} );

$this->pdf->setFooterFunction( function( $pdf ) {
	$pdf->writeHtmlCell( 180, 22.5, 15, -22.5, '
		<table cellpadding="0.5" style="font-size: 8px">
			<tr>
				<td style="font-weight: bold">Example company</td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td>Example address, 12345 Example city</td>
				<td>District court: </td>
				<td>Bank: </td>
			</tr>
			<tr>
				<td>Telephone: </td>
				<td>Managing director: </td>
				<td>IBAN: </td>
			</tr>
			<tr>
				<td>E-Mail: </td>
				<td>VAT ID: </td>
				<td>BIC: </td>
			</tr>
		</table>
	' );
	$pdf->writeHtmlCell( 210, 5, 0, -5, '
		<div style="background-color: #103050; color: #ffffff; text-align: center; font-weight: bold; font-size: 10px">
			example.com
		</div>
	' );
} );


$pricetype = 'price:default';
$pricefmt = $this->translate( 'controller/jobs', $pricetype );
/// Price format with price value (%1$s) and currency (%2$s)
$priceFormat = $pricefmt !== 'price:default' ? $pricefmt : $this->translate( 'controller/jobs', '%1$s %2$s' );


?>
<h1><?= $enc->html( $this->get( 'intro' ) ) ?></h1>

<h2><?= nl2br( $enc->html( $this->translate( 'controller/jobs', 'Your voucher' ) . ': ' . $this->voucher, $enc::TRUST ) ) ?></h2>

<p>
	<?php $priceCurrency = $this->translate( 'currency', $this->orderProductItem->getPrice()->getCurrencyId() ) ?>
	<?php $value = sprintf( $priceFormat, $this->number( $this->orderProductItem->getPrice()->getValue() + $this->orderProductItem->getPrice()->getRebate(), $this->orderProductItem->getPrice()->getPrecision() ), $priceCurrency ) ?>
	<?= nl2br( $enc->html( sprintf( $this->translate( 'controller/jobs', 'The value of your voucher is %1$s', 'The value of your vouchers are %1$s', count( (array) $this->voucher ) ), $value ), $enc::TRUST ) ) ?>
</p>

<p><?= nl2br( $enc->html( $this->translate( 'controller/jobs', 'You can use your vouchers at any time in our online shop' ), $enc::TRUST ) ) ?></p>
