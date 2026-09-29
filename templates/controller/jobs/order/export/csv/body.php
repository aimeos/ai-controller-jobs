<?php

/**
 * Converts the values into a CSV line
 *
 * Quotes are escaped by doubling them and values starting with a character
 * spreadsheet applications interpret as formula are prefixed by a single quote
 * to prevent CSV/formula injection. Numeric values like "-5.00" are unchanged.
 */
$csvFcn = function( array $list ) {
	$list = array_map( function( $value ) {
		$value = (string) $value;

		if( !is_numeric( $value ) && preg_match( '/^[=+\-@\t\r]/', $value ) ) {
			$value = "'" . $value;
		}

		return str_replace( '"', '""', $value );
	}, $list );

	return '"' . join( '","', $list ) . '"' . "\n";
};

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
	echo $csvFcn( $orderFcn( $item ) );

	foreach( $item->getAddress( 'payment' ) as $address ) {
		echo $csvFcn( $addressFcn( $address ) );
	}

	foreach( $item->getAddress( 'delivery' ) as $address ) {
		echo $csvFcn( $addressFcn( $address ) );
	}

	foreach( $item->getService( 'payment' ) as $service ) {
		echo $csvFcn( $serviceFcn( $service ) );
	}

	foreach( $item->getService( 'delivery' ) as $service ) {
		echo $csvFcn( $serviceFcn( $service ) );
	}

	foreach( $item->getCoupons() as $code => $list ) {
		echo $csvFcn( ['coupon', $item->getId(), $code] );
	}

	foreach( $item->getProducts() as $product )
	{
		echo $csvFcn( $productFcn( $product ) );

		foreach( $product->getProducts() as $subProduct ) {
			echo $csvFcn( $productFcn( $subProduct ) );
		}
	}
}