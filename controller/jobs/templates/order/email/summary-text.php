<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2022
 */

/** Available data
 * - summaryBasket : Order base item (basket) with addresses, services, products, etc.
 */

$pricefmt = $this->translate( 'client/code', 'price:default' );
/// Price format with price value (%1$s) and currency (%2$s)
$pricefmt = ( $pricefmt === 'price:default' ? $this->translate( 'client', '%1$s %2$s' ) : $pricefmt );


?>
<?= strip_tags( $this->translate( 'client', 'Billing address' ) ) ?>:

<?php foreach( $this->summaryBasket->getAddress( 'payment' ) as $addr ) : ?>
<?= preg_replace( ["/\n+/m", '/ +/'], ["\n", ' '], trim( $this->encoder()->html( sprintf(
		/// Address format with company (%1$s), salutation (%2$s), title (%3$s), first name (%4$s), last name (%5$s),
		/// address part one (%6$s, e.g street), address part two (%7$s, e.g house number), address part three (%8$s, e.g additional information),
		/// postal/zip code (%9$s), city (%10$s), state (%11$s), country (%12$s), language (%13$s),
		/// e-mail (%14$s), phone (%15$s), facsimile/telefax (%16$s), web site (%17$s), vatid (%18$s)
		$this->translate( 'client', '%1$s
%2$s %3$s %4$s %5$s
%6$s %7$s
%8$s
%9$s %10$s
%11$s
%12$s
%13$s
%14$s
%15$s
%16$s
%17$s
%18$s
'
		),
		$addr->getCompany(),
		$this->translate( 'mshop/code', $addr->getSalutation() ),
		$addr->getTitle(),
		$addr->getFirstName(),
		$addr->getLastName(),
		$addr->getAddress1(),
		$addr->getAddress2(),
		$addr->getAddress3(),
		$addr->getPostal(),
		$addr->getCity(),
		$addr->getState(),
		$this->translate( 'country', $addr->getCountryId() ),
		$this->translate( 'language', $addr->getLanguageId() ),
		$addr->getEmail(),
		$addr->getTelephone(),
		$addr->getTelefax(),
		$addr->getWebsite(),
		$addr->getVatID()
	) ) ) )
?>
<?php endforeach ?>



<?= strip_tags( $this->translate( 'client', 'Delivery address' ) ) ?>:

<?php if( !empty( $addrItems = $this->summaryBasket->getAddress( 'delivery' ) ) ) : ?>
<?php	foreach( $addrItems as $addr ) : ?>
<?= preg_replace( ["/\n+/m", '/ +/'], ["\n", ' '], trim( $this->encoder()->html( sprintf(
		/// Address format with company (%1$s), salutation (%2$s), title (%3$s), first name (%4$s), last name (%5$s),
		/// address part one (%6$s, e.g street), address part two (%7$s, e.g house number), address part three (%8$s, e.g additional information),
		/// postal/zip code (%9$s), city (%10$s), state (%11$s), country (%12$s), language (%13$s),
		/// e-mail (%14$s), phone (%15$s), facsimile/telefax (%16$s), web site (%17$s), vatid (%18$s)
		$this->translate( 'client', '%1$s
%2$s %3$s %4$s %5$s
%6$s %7$s
%8$s
%9$s %10$s
%11$s
%12$s
%13$s
%14$s
%15$s
%16$s
%17$s
%18$s
'
		),
		$addr->getCompany(),
		$this->translate( 'mshop/code', $addr->getSalutation() ),
		$addr->getTitle(),
		$addr->getFirstName(),
		$addr->getLastName(),
		$addr->getAddress1(),
		$addr->getAddress2(),
		$addr->getAddress3(),
		$addr->getPostal(),
		$addr->getCity(),
		$addr->getState(),
		$this->translate( 'country', $addr->getCountryId() ),
		$this->translate( 'language', $addr->getLanguageId() ),
		$addr->getEmail(),
		$addr->getTelephone(),
		$addr->getTelefax(),
		$addr->getWebsite(),
		$addr->getVatID()
	) ) ) )
?>
<?php	endforeach ?>
<?php else : ?>
<?=		$this->translate( 'client', 'like billing address' ) ?>
<?php endif ?>



<?php if( ( $services = $this->summaryBasket->getService( 'delivery' ) ) !== [] ) : ?>
<?=		strip_tags( $this->translate( 'client', 'delivery' ) ) ?>:
<?php	foreach( $services as $service ) : ?>

<?=			strip_tags( $service->getName() ) ?>

<?php		foreach( $service->getAttributeItems() as $attribute )
			{
				$name = ( $attribute->getName() != '' ? $attribute->getName() : $this->translate( 'client/code', $attribute->getCode() ) );

				switch( $attribute->getValue() )
				{
					case 'array':
					case 'object':
						$value = join( ', ', (array) $attribute->getValue() );
						break;
					default:
						$value = $attribute->getValue();
				}

				echo '- ' . strip_tags( $name ) . ': ' . strip_tags( $value ) . "\n";
			}
?>
<?php	endforeach ?>
<?php endif ?>


<?php if( ( $services = $this->summaryBasket->getService( 'payment' ) ) !== [] ) : ?>
<?=		strip_tags( $this->translate( 'client', 'payment' ) ) ?>:
<?php	foreach( $services as $service ) : ?>

<?=			strip_tags( $service->getName() ) ?>

<?php		foreach( $service->getAttributeItems() as $attribute )
			{
				$name = ( $attribute->getName() != '' ? $attribute->getName() : $this->translate( 'client/code', $attribute->getCode() ) );

				switch( $attribute->getValue() )
				{
					case 'array':
					case 'object':
						$value = join( ', ', (array) $attribute->getValue() );
						break;
					default:
						$value = $attribute->getValue();
				}

				echo '- ' . strip_tags( $name ) . ': ' . strip_tags( $value ) . "\n";
			}
?>
<?php	endforeach ?>
<?php endif ?>


<?php if( !( $coupons = $this->summaryBasket->getCoupons() )->isEmpty() ) : ?>
<?= 	strip_tags( $this->translate( 'client', 'Coupons' ) ) ?>:
<?php	foreach( $coupons as $code => $products ) : ?>
<?= 		'- ' . $code . "\n" ?>
<?php	endforeach ?>

<?php endif ?>
<?php if( $this->summaryBasket->getCustomerReference() != '' ) : ?>
<?= 	strip_tags( $this->translate( 'client', 'Your reference number' ) ) ?>:
<?= 	strip_tags( $this->summaryBasket->getCustomerReference() ) . "\n" ?>

<?php endif ?>
<?php if( $this->summaryBasket->getComment() != '' ) : ?>
<?= 	strip_tags( $this->translate( 'client', 'Your comment' ) ) ?>:
<?= 	strip_tags( $this->summaryBasket->getComment() ) . "\n" ?>

<?php endif ?>


<?= strip_tags( $this->translate( 'client', 'Order details' ) ) ?>:
<?php foreach( $this->summaryBasket->getProducts() as $product ) : $priceItem = $product->getPrice() ?>

<?=		strip_tags( $product->getName() ) ?> (<?= $product->getProductCode() ?>)
<?php	foreach( ['variant', 'config', 'custom'] as $attrType ) : ?>
<?php		foreach( $product->getAttributeItems( $attrType ) as $attribute ) : ?>
- <?php 		echo strip_tags( $this->translate( 'client/code', $attribute->getCode() ) ) ?>: <?= $attribute->getQuantity() > 1 ? $attribute->getQuantity() . '× ' : '' ?><?= strip_tags( ( $attribute->getName() != '' ? $attribute->getName() : $attribute->getValue() ) ) ?>

<?php		endforeach ?>
<?php	endforeach ?>
<?php	foreach( $product->getAttributeItems( 'hidden' ) as $attribute ) : ?>
<?php		if( $this->orderItem->getStatusPayment() >= $this->config( 'client/html/common/summary/detail/download/payment-status', \Aimeos\MShop\Order\Item\Base::PAY_RECEIVED ) && $attribute->getCode() === 'download' ) : ?>
- <?=			strip_tags( $attribute->getName() ) ?>: <?= $this->link( 'client/html/account/download/url', ['dl_id' => $attribute->getId()], ['absoluteUri' => true] ) ?>

<?php		endif ?>
<?php	endforeach ?>
<?=		strip_tags( $this->translate( 'client', 'Quantity' ) ) ?>: <?= $product->getQuantity() ?>

<?=		strip_tags( $this->translate( 'client', 'Price' ) ) ?>: <?php printf( $pricefmt, $this->number( $priceItem->getValue() * $product->getQuantity(), $priceItem->getPrecision() ), $priceItem->getCurrencyId() ) ?>

<?php	if( ( $status = $product->getStatusDelivery() ) >= 0 ) : $key = 'stat:' . $status ?>
<?=			strip_tags( $this->translate( 'client', 'Status' ) ) ?>: <?= strip_tags( $this->translate( 'mshop/code', $key ) ) ?>
<?php	endif ?>
<?php endforeach ?>

<?php foreach( $this->summaryBasket->getService( 'delivery' ) as $service ) : ?>
<?php	if( $service->getPrice()->getValue() > 0 ) : $priceItem = $service->getPrice() ?>
<?=			strip_tags( $service->getName() ) ?>

<?=			strip_tags( $this->translate( 'client', 'Price' ) ) ?>: <?php printf( $pricefmt, $this->number( $priceItem->getValue(), $priceItem->getPrecision() ), $priceItem->getCurrencyId() ) ?>

<?php	endif ?>
<?php endforeach ?>
<?php foreach( $this->summaryBasket->getService( 'payment' ) as $service ) : ?>
<?php	if( $service->getPrice()->getValue() > 0 ) : $priceItem = $service->getPrice() ?>
<?=			strip_tags( $service->getName() ) ?>

<?=			strip_tags( $this->translate( 'client', 'Price' ) ) ?>: <?php printf( $pricefmt, $this->number( $priceItem->getValue(), $priceItem->getPrecision() ), $priceItem->getCurrencyId() ) ?>

<?php	endif ?>
<?php endforeach ?>

<?= strip_tags( $this->translate( 'client', 'Sub-total' ) ) ?>: <?php printf( $pricefmt, $this->number( $this->summaryBasket->getPrice()->getValue(), $this->summaryBasket->getPrice()->getPrecision() ), $this->summaryBasket->getPrice()->getCurrencyId() ) ?>

<?php if( ( $costs = $this->summaryBasket->getCosts() ) > 0 ) : ?>
<?= strip_tags( $this->translate( 'client', '+ Shipping' ) ) ?>: <?php printf( $pricefmt, $this->number( $costs, $this->summaryBasket->getPrice()->getPrecision() ), $this->summaryBasket->getPrice()->getCurrencyId() ) ?>

<?php endif ?>
<?php if( ( $costs = $this->summaryBasket->getCosts( 'payment' ) ) > 0 ) : ?>
<?php	echo strip_tags( $this->translate( 'client', '+ Payment costs' ) ) ?>: <?php printf( $pricefmt, $this->number( $costs, $this->summaryBasket->getPrice()->getPrecision() ), $this->summaryBasket->getPrice()->getCurrencyId() ) ?>

<?php endif ?>
<?php if( $this->summaryBasket->getPrice()->getTaxFlag() === true ) : ?>
<?php	echo strip_tags( $this->translate( 'client', 'Total' ) ) ?>: <?php printf( $pricefmt, $this->number( $this->summaryBasket->getPrice()->getValue() + $this->summaryBasket->getPrice()->getCosts(), $this->summaryBasket->getPrice()->getPrecision() ), $this->summaryBasket->getPrice()->getCurrencyId() ) ?>

<?php endif ?>
<?php foreach( $this->summaryBasket->getTaxes() as $taxName => $map ) : ?>
<?php 	foreach( $map as $taxRate => $priceItem ) : ?>
<?php		if( ( $taxValue = $priceItem->getTaxValue() ) > 0 ) : ?>
<?php			$taxFormat = ( $priceItem->getTaxFlag() ? $this->translate( 'client', 'Incl. %1$s%% %2$s' ) : $this->translate( 'client', '+ %1$s%% %2$s' ) ) ?>
<?php			echo strip_tags( sprintf( $taxFormat, $this->number( $taxRate ), $this->translate( 'client/code', $taxName ) ) ) ?>: <?php printf( $pricefmt, $this->number( $taxValue, $priceItem->getPrecision() ), $priceItem->getCurrencyId() ) ?>

<?php		endif ?>
<?php	endforeach ?>
<?php endforeach ?>
<?php if( $this->summaryBasket->getPrice()->getTaxFlag() === false ) : ?>
<?php	echo strip_tags( $this->translate( 'client', 'Total' ) ) ?>: <?php printf( $pricefmt, $this->number( $this->summaryBasket->getPrice()->getValue() + $this->summaryBasket->getPrice()->getCosts() + $this->summaryBasket->getPrice()->getTaxValue(), $this->summaryBasket->getPrice()->getPrecision() ), $this->summaryBasket->getPrice()->getCurrencyId() ) ?>

<?php endif ?>
<?php if( $this->summaryBasket->getPrice()->getRebate() > 0 ) : ?>
<?= strip_tags( $this->translate( 'client', 'Included rebates' ) ) ?>: <?php printf( $pricefmt, $this->number( $this->summaryBasket->getPrice()->getRebate(), $this->summaryBasket->getPrice()->getPrecision() ), $this->summaryBasket->getPrice()->getCurrencyId() ) ?>

<?php endif ?>
