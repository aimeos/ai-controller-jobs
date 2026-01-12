<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2026
 */

/** Available data
 * - orderProductItem : Order product item
 * - orderAddressItem : Order address item
 * - voucher : Voucher code
 */


$enc = $this->encoder();


$pricetype = 'price:default';
$pricefmt = $this->translate( 'controller/jobs', $pricetype );
/// Price format with price value (%1$s) and currency (%2$s)
$priceFormat = $pricefmt !== 'price:default' ? $pricefmt : $this->translate( 'controller/jobs', '%1$s %2$s' );


?>
<?= wordwrap( strip_tags( $this->get( 'intro', '' ) ) ) ?>


<?= wordwrap( strip_tags( $this->translate( 'controller/jobs', 'Your voucher' ) . ': ' . $this->voucher ) ) ?>


<?php $price = $this->orderProductItem->getPrice(); $priceCurrency = $this->translate( 'currency', $price->getCurrencyId() ) ?>
<?php $value = sprintf( $priceFormat, $this->number( $price->getValue() + $price->getRebate(), $price->getPrecision() ), $priceCurrency ) ?>
<?= wordwrap( strip_tags( sprintf( $this->translate( 'controller/jobs', 'The value of your voucher is %1$s', 'The value of your vouchers are %1$s', count( (array) $this->voucher ) ), $value ) ) ) ?>


<?= wordwrap( strip_tags( $this->translate( 'controller/jobs', 'You can use your vouchers at any time in our online shop' ) ) ) ?>
