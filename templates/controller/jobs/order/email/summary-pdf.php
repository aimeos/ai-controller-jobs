<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2023
 */

/** Available data
 * - orderItem: Order item
 * - summaryBasket : Order item (basket) with addresses, services, products, etc.
 */

$totalQty = 0;
$enc = $this->encoder();

/// Price format with price value (%1$s) and currency (%2$s)
$pricefmt = $this->translate( 'controller/jobs', 'price:default' );
$pricefmt = ( $pricefmt === 'price:default' ? $this->translate( 'controller/jobs', '%1$s %2$s' ) : $pricefmt );


?>
<style>
	.basket .label { width: 45%; text-align: left }
	.basket .code { width: 20%; text-align: center }
	.basket .quantity { width: 15%; text-align: center }
	.basket .price { width: 20%; text-align: right }
	.basket .header .price { text-align: center }
	.basket .header { background-color: #f8f8f8; font-weight: bold }
	.basket .subtotal, .basket .total { background-color: #f8f8f8; font-weight: bold }
	.basket .product { border: 1px solid #f8f8f8 }
</style>
<table class="basket" cellpadding="5">
	<tr class="header">
		<th class="label"><?= $enc->html( $this->translate( 'controller/jobs', 'Name' ), $enc::TRUST ) ?></th>
		<th class="code"><?= $enc->html( $this->translate( 'controller/jobs', 'Article no.' ), $enc::TRUST ) ?></th>
		<th class="quantity"><?= $enc->html( $this->translate( 'controller/jobs', 'Qty' ), $enc::TRUST ) ?></th>
		<th class="price"><?= $enc->html( $this->translate( 'controller/jobs', 'Sum' ), $enc::TRUST ) ?></th>
	</tr>
	<?php foreach( $this->summaryBasket->getProducts() as $product ) : $totalQty += $product->getQuantity() ?>
		<tr class="body product">
			<td class="label">
				<?= $enc->html( $product->getName(), $enc::TRUST ) ?>
				<?php if( ( $desc = $product->getDescription() ) !== '' ) : ?>
					<p class="product-description"><?= $enc->html( $desc ) ?></p>
				<?php endif ?>
				<?php foreach( ['variant', 'config', 'custom'] as $attrType ) : ?>
					<?php if( !( $attributes = $product->getAttributeItems( $attrType ) )->isEmpty() ) : ?>
						<ul class="attr-list attr-type-<?= $enc->attr( $attrType ) ?>">
							<?php foreach( $attributes as $attribute ) : ?>
								<li class="attr-item attr-code-<?= $enc->attr( $attribute->getCode() ) ?>">
									<span class="name"><?= $enc->html( $this->translate( 'controller/jobs', $attribute->getCode() ) ) ?>:</span>
									<span class="value">
										<?php if( $attribute->getQuantity() > 1 ) : ?>
											<?= $enc->html( $attribute->getQuantity() ) ?>×
										<?php endif ?>
										<?= $enc->html( $attrType !== 'custom' && $attribute->getName() ? $attribute->getName() : $attribute->getValue() ) ?>
									</span>
								</li>
							<?php endforeach ?>
						</ul>
					<?php endif ?>
				<?php endforeach ?>
			</td>
			<td class="code">
				<?= $product->getProductCode() ?>
			</td>
			<td class="quantity">
				<?= $enc->html( $product->getQuantity() ) ?>
			</td>
			<td class="price">
				<?= $enc->html( sprintf( $pricefmt, $this->number( $product->getPrice()->getValue() * $product->getQuantity(), $product->getPrice()->getPrecision() ), $this->translate( 'currency', $product->getPrice()->getCurrencyId() ) ) ) ?>
			</td>
		</tr>
	<?php endforeach ?>

	<?php foreach( $this->summaryBasket->getService( 'delivery' ) as $service ) : ?>
		<?php if( $service->getPrice()->getValue() > 0 ) : $priceItem = $service->getPrice() ?>
			<tr class="body delivery">
				<td class="label"><?= $enc->html( $service->getName() ) ?></td>
				<td class="code"></td>
				<td class="quantity">1</td>
				<td class="price"><?= $enc->html( sprintf( $pricefmt, $this->number( $priceItem->getValue(), $priceItem->getPrecision() ), $this->translate( 'currency', $priceItem->getCurrencyId() ) ) ) ?></td>
			</tr>
		<?php endif ?>
	<?php endforeach ?>

	<?php foreach( $this->summaryBasket->getService( 'payment' ) as $service ) : ?>
		<?php if( $service->getPrice()->getValue() > 0 ) : $priceItem = $service->getPrice() ?>
			<tr class="body payment">
				<td class="label"><?= $enc->html( $service->getName() ) ?></td>
				<td class="code"></td>
				<td class="quantity">1</td>
				<td class="price"><?= $enc->html( sprintf( $pricefmt, $this->number( $priceItem->getValue(), $priceItem->getPrecision() ), $this->translate( 'currency', $priceItem->getCurrencyId() ) ) ) ?></td>
			</tr>
		<?php endif ?>
	<?php endforeach ?>

	<?php if( $this->summaryBasket->getPrice()->getCosts() > 0 || $this->summaryBasket->getPrice()->getTaxFlag() === false ) : ?>
		<tr class="footer subtotal">
			<td class="label"><?= $enc->html( $this->translate( 'controller/jobs', 'Sub-total' ) ) ?></td>
			<td class="code"></td>
			<td class="quantity"></td>
			<td class="price"><?= $enc->html( sprintf( $pricefmt, $this->number( $this->summaryBasket->getPrice()->getValue(), $this->summaryBasket->getPrice()->getPrecision() ), $this->translate( 'currency', $this->summaryBasket->getPrice()->getCurrencyId() ) ) ) ?></td>
		</tr>
	<?php endif ?>

	<?php if( ( $costs = $this->summaryBasket->getCosts() ) > 0 ) : ?>
		<tr class="footer delivery">
			<td class="label"><?= $enc->html( $this->translate( 'controller/jobs', '+ Shipping' ) ) ?></td>
			<td class="code"></td>
			<td class="quantity"></td>
			<td class="price"><?= $enc->html( sprintf( $pricefmt, $this->number( $costs, $this->summaryBasket->getPrice()->getPrecision() ), $this->translate( 'currency', $this->summaryBasket->getPrice()->getCurrencyId() ) ) ) ?></td>
		</tr>
	<?php endif ?>

	<?php if( ( $costs = $this->summaryBasket->getCosts( 'payment' ) ) > 0 ) : ?>
		<tr class="footer payment">
			<td class="label"><?= $enc->html( $this->translate( 'controller/jobs', '+ Payment costs' ) ) ?></td>
			<td class="code"></td>
			<td class="quantity"></td>
			<td class="price"><?= $enc->html( sprintf( $pricefmt, $this->number( $costs, $this->summaryBasket->getPrice()->getPrecision() ), $this->translate( 'currency', $this->summaryBasket->getPrice()->getCurrencyId() ) ) ) ?></td>
		</tr>
	<?php endif ?>

	<?php if( $this->summaryBasket->getPrice()->getTaxFlag() === true ) : ?>
		<tr class="footer total">
			<td class="label"><?= $enc->html( $this->translate( 'controller/jobs', 'Total' ) ) ?></td>
			<td class="code"></td>
			<td class="quantity"><?= $enc->html( $totalQty ) ?></td>
			<td class="price"><?= $enc->html( sprintf( $pricefmt, $this->number( $this->summaryBasket->getPrice()->getValue() + $this->summaryBasket->getPrice()->getCosts(), $this->summaryBasket->getPrice()->getPrecision() ), $this->translate( 'currency', $this->summaryBasket->getPrice()->getCurrencyId() ) ) ) ?></td>
		</tr>
	<?php endif ?>

	<?php foreach( $this->summaryBasket->getTaxes() as $taxName => $map ) : ?>
		<?php foreach( $map as $taxRate => $priceItem ) : ?>
			<?php if( ( $taxValue = $priceItem->getTaxValue() ) > 0 ) : ?>
				<tr class="footer tax">
					<td class="label"><?= $enc->html( sprintf( $priceItem->getTaxFlag() ? $this->translate( 'controller/jobs', 'Incl. %1$s%% %2$s' ) : $this->translate( 'controller/jobs', '+ %1$s%% %2$s' ), $this->number( $taxRate ), $this->translate( 'controller/jobs', $taxName ) ) ) ?></td>
					<td class="code"></td>
					<td class="quantity"></td>
					<td class="price"><?= $enc->html( sprintf( $pricefmt, $this->number( $taxValue, $priceItem->getPrecision() ), $this->translate( 'currency', $priceItem->getCurrencyId() ) ) ) ?></td>
				</tr>
			<?php endif ?>
		<?php endforeach ?>
	<?php endforeach ?>

	<?php if( $this->summaryBasket->getPrice()->getTaxFlag() === false ) : ?>
		<tr class="footer total">
			<td class="label"><?= $enc->html( $this->translate( 'controller/jobs', 'Total' ) ) ?></td>
			<td class="code"></td>
			<td class="quantity"><?= $enc->html( $totalQty ) ?></td>
			<td class="price"><?= $enc->html( sprintf( $pricefmt, $this->number( $this->summaryBasket->getPrice()->getValue() + $this->summaryBasket->getPrice()->getCosts() + $this->summaryBasket->getPrice()->getTaxValue(), $this->summaryBasket->getPrice()->getPrecision() ), $this->translate( 'currency', $this->summaryBasket->getPrice()->getCurrencyId() ) ) ) ?></td>
		</tr>
	<?php endif ?>

	<?php if( $this->summaryBasket->getPrice()->getRebate() > 0 ) : ?>
		<tr class="footer rebate">
			<td class="label"><?= $enc->html( $this->translate( 'controller/jobs', 'Included rebates' ) ) ?></td>
			<td class="code"></td>
			<td class="quantity"></td>
			<td class="price"><?= $enc->html( sprintf( $pricefmt, $this->number( $this->summaryBasket->getPrice()->getRebate(), $this->summaryBasket->getPrice()->getPrecision() ), $this->translate( 'currency', $this->summaryBasket->getPrice()->getCurrencyId() ) ) ) ?></td>
		</tr>
	<?php endif ?>
</table>
