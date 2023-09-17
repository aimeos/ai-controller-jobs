<?php

$orderFcn = function( \Aimeos\MShop\Order\Item\Iface $item ) {
	return [
		'invoice',
		$item->getId(),
		$item->getChannel(),
		$item->getDatePayment(),
		$item->getStatusPayment(),
		$item->getDateDelivery(),
		$item->getStatusDelivery(),
		$item->getRelatedId(),
		$item->getCustomerId(),
		$item->getSitecode(),
		$item->locale()->getLanguageId(),
		$item->locale()->getCurrencyId(),
		$item->getPrice()->getValue(),
		$item->getPrice()->getCosts(),
		$item->getPrice()->getRebate(),
		$item->getPrice()->getTaxvalue(),
		$item->getPrice()->getTaxflag(),
		$item->getComment(),
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

$serviceFcn = function( \Aimeos\MShop\Order\Item\Service\Iface $item ) {
	$list = [
		'service',
		$item->getParentId(),
		$item->getType(),
		$item->getCode(),
		$item->getName(),
		$item->getMediaUrl(),
		$item->getPrice()->getValue(),
		$item->getPrice()->getCosts(),
		$item->getPrice()->getRebate(),
		$item->getPrice()->getTaxrate(),
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
	echo '"' . join( '","', $orderFcn( $item ) ) . '"' . "\n";

	foreach( $item->getAddress( 'payment' ) as $address ) {
		echo '"' . join( '","', $addressFcn( $address ) ) . '"' . "\n";
	}

	foreach( $item->getAddress( 'delivery' ) as $address ) {
		echo '"' . join( '","', $addressFcn( $address ) ) . '"' . "\n";
	}

	foreach( $item->getService( 'payment' ) as $service ) {
		echo '"' . join( '","', $serviceFcn( $service ) ) . '"' . "\n";
	}

	foreach( $item->getService( 'delivery' ) as $service ) {
		echo '"' . join( '","', $serviceFcn( $service ) ) . '"' . "\n";
	}

	foreach( $item->getCoupons() as $code => $list ) {
		echo '"coupon","' . $item->getId() . '""' . str_replace( '"', '\\"', $code ) . '"' . "\n";
	}

	foreach( $item->getProducts() as $product )
	{
		echo '"' . join( '","', $productFcn( $product ) ) . '"' . "\n";

		foreach( $product->getProducts() as $subProduct ) {
			echo '"' . join( '","', $productFcn( $subProduct ) ) . '"' . "\n";
		}
	}
}