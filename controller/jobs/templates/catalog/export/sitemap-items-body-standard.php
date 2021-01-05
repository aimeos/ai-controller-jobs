<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */

$enc = $this->encoder();

$treeTarget = $this->config( 'client/html/catalog/tree/url/target' );
$treeCntl = $this->config( 'client/html/catalog/tree/url/controller', 'catalog' );
$treeAction = $this->config( 'client/html/catalog/tree/url/action', 'list' );
$treeConfig = $this->config( 'client/html/catalog/tree/url/config', [] );
$treeConfig['absoluteUri'] = true;

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
		$params = ['f_name' => \Aimeos\MW\Str::slug( $name ), 'f_catid' => $id];
		$url = $this->url( $item->getTarget() ?: $treeTarget, $treeCntl, $treeAction, $params, [], $treeConfig );

		echo '<url><loc>' . $enc->xml( $url ) . '</loc><lastmod>' . $date . '</lastmod><changefreq>' . $freq . "</changefreq></url>\n";
	}
}
