<?php

$subscriptionFcn = function( \Aimeos\MShop\Subscription\Item\Iface $item ) {
	return [
		'subscription',
		$item->getId(),
		$item->getInterval(),
		$item->getDateNext(),
		$item->getDateEnd(),
		$item->getPeriod(),
		$item->getStatus(),
		$item->getOrderId(),
	];
};

$addressFcn = function( \Aimeos\MShop\Order\Item\Address\Iface $item ) {
	return [
		'address',
		$item->getParentId(),
		$item->getType(),
		$item->getSalutation(),
		$item->getCompany(),
		$item->getVatID(),
		$item->getTitle(),
		$item->getFirstName(),
		$item->getLastName(),
		$item->getAddress1(),
		$item->getAddress2(),
		$item->getAddress3(),
		$item->getPostal(),
		$item->getCity(),
		$item->getState(),
		$item->getCountryId(),
		$item->getLanguageId(),
		$item->getTelephone(),
		$item->getTelefax(),
		$item->getEmail(),
		$item->getWebsite(),
		$item->getLongitude(),
		$item->getLatitude(),
	];
};

$productFcn = function( \Aimeos\MShop\Order\Item\Product\Iface $item ) {
	$list = [
		'product',
		$item->getParentId(),
		$item->getType(),
		$item->getStockType(),
		$item->getVendor(),
		$item->getProductCode(),
		$item->getScale(),
		$item->getQuantity(),
		$item->getQuantityOpen(),
		$item->getName(),
		$item->getDescription(),
		$item->getMediaUrl(),
		$item->getPrice()->getValue(),
		$item->getPrice()->getCosts(),
		$item->getPrice()->getRebate(),
		$item->getPrice()->getTaxrate(),
		$item->getPrice()->getTaxvalue(),
		$item->getPrice()->getTaxflag(),
		$item->getStatusPayment(),
		$item->getStatusDelivery(),
		$item->getTimeframe(),
		$item->getPosition(),
		$item->getNotes(),
	];

	if( $attr = $item->getAttributeItems()->first() )
	{
		$list[] = $attr->getType();
		$list[] = $attr->getCode();
		$list[] = $attr->getName();
		$list[] = $attr->getValue();
	}

	return $list;
};


foreach( $this->get( 'items', [] ) as $item )
{
	echo '"' . join( '","', $subscriptionFcn( $item ) ) . '"' . "\n";

	if( $orderItem = $item->getOrderItem() )
	{
		foreach( $orderItem->getAddress( 'payment' ) as $address ) {
			echo '"' . join( '","', $addressFcn( $address ) ) . '"' . "\n";
		}

		foreach( $orderItem->getAddress( 'delivery' ) as $address ) {
			echo '"' . join( '","', $addressFcn( $address ) ) . '"' . "\n";
		}

		foreach( $orderItem->getProducts() as $product )
		{
			echo '"' . join( '","', $productFcn( $product ) ) . '"' . "\n";

			foreach( $product->getProducts() as $subProduct ) {
				echo '"' . join( '","', $productFcn( $subProduct ) ) . '"' . "\n";
			}
		}
	}
}