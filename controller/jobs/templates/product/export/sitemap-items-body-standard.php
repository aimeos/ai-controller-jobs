<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */

$enc = $this->encoder();

$detailTarget = $this->config( 'client/html/catalog/detail/url/target' );
$detailCntl = $this->config( 'client/html/catalog/detail/url/controller', 'catalog' );
$detailAction = $this->config( 'client/html/catalog/detail/url/action', 'detail' );
$detailFilter = array_flip( $this->config( 'client/html/catalog/detail/url/filter', ['d_prodid'] ) );
$detailConfig = $this->config( 'client/html/catalog/detail/url/config', [] );
$detailConfig['absoluteUri'] = true;

$freq = $enc->xml( $this->get( 'siteFreq', 'daily' ) );

foreach( $this->get( 'siteItems', [] ) as $id => $item )
{
	$texts = [];
	$date = str_replace( ' ', 'T', $item->getTimeModified() ) . date( 'P' );

	foreach( $item->getListItems( 'text', 'default', 'url', false ) as $listItem )
	{
		if( $listItem->isAvailable() && ( $text = $listItem->getRefItem() ) !== null && $text->getStatus() > 0 ) {
			$texts[$text->getLanguageId()] = \Aimeos\MW\Str::slug( $text->getContent() );
		}
	}

	if( empty( $texts ) ) {
		$texts[''] = $item->getLabel();
	}

	foreach( $texts as $name )
	{
		$params = array_diff_key( ['d_name' => \Aimeos\MW\Str::slug( $name ), 'd_prodid' => $id, 'd_pos' => ''], $detailFilter );
		$url = $this->url( $item->getTarget() ?: $detailTarget, $detailCntl, $detailAction, $params, [], $detailConfig );

		echo '<url><loc>' . $enc->xml( $url ) . '</loc><lastmod>' . $date . '</lastmod><changefreq>' . $freq . "</changefreq></url>\n";
	}
}
