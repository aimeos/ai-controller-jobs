<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2022
 */

/** Available data
 * - orderItem: Order item
 * - summaryBasket : Order base item (basket) with addresses, services, products, etc.
 */


$key = 'stat:' . $this->orderItem->getStatusDelivery();
$orderStatus = $this->translate( 'mshop/code', $key );
$orderDate = date_create( $this->orderItem->getTimeCreated() )->format( $this->translate( 'controller/jobs', 'Y-m-d' ) );


?>
<?php switch( $this->addressItem->getSalutation() ) : case 'mr': ?>
<?= 	sprintf( $this->translate( 'controller/jobs', 'Dear Mr %1$s %2$s' ), $this->addressItem->getFirstName(), $this->addressItem->getLastName() ) ?>
<?php break; case 'ms': ?>
<?= 	sprintf( $this->translate( 'controller/jobs', 'Dear Ms %1$s %2$s' ), $this->addressItem->getFirstName(), $this->addressItem->getLastName() ) ?>
<?php break; default: ?>
<?= 	sprintf( $this->translate( 'controller/jobs', 'Dear %1$s %2$s' ), $this->addressItem->getFirstName(), $this->addressItem->getLastName() ) ?>
<?php endswitch ?>


<?php switch( $this->orderItem->getStatusDelivery() ) : case 3: /// Delivery e-mail intro with order ID (%1$s), order date (%2$s) and delivery status (%3%s) ?>
<?= 	sprintf( $this->translate( 'controller/jobs', 'Your order %1$s from %2$s has been dispatched.' ), $this->orderItem->getOrderNumber(), $orderDate, $orderStatus ) ?>
<?php break; case 6: /// Delivery e-mail intro with order ID (%1$s), order date (%2$s) and delivery status (%3%s) ?>
<?= 	sprintf( $this->translate( 'controller/jobs', 'The parcel for your order %1$s from %2$s could not be delivered.' ), $this->orderItem->getOrderNumber(), $orderDate, $orderStatus ) ?>
<?php break; case 7: /// Delivery e-mail intro with order ID (%1$s), order date (%2$s) and delivery status (%3%s) ?>
<?= 	sprintf( $this->translate( 'controller/jobs', 'We received the returned parcel for your order %1$s from %2$s.' ), $this->orderItem->getOrderNumber(), $orderDate, $orderStatus ) ?>
<?php break; default: /// Delivery e-mail intro with order ID (%1$s), order date (%2$s) and delivery status (%3%s) ?>
<?= 	sprintf( $this->translate( 'controller/jobs', 'The delivery status of your order %1$s from %2$s has been changed to "%3$s".' ), $this->orderItem->getOrderNumber(), $orderDate, $orderStatus ) ?>
<?php endswitch ?>


<?= $this->partial( 'order/email/summary-text', ['orderItem' => $this->orderItem, 'summaryBasket' => $this->summaryBasket] ) ?>


<?= wordwrap( strip_tags( $this->translate( 'controller/jobs', 'If you have any questions, please reply to this e-mail' ) ) ) ?>


<?= wordwrap( strip_tags( $this->translate( 'controller/jobs', 'All orders are subject to our terms and conditions.' ) ) ) ?>
