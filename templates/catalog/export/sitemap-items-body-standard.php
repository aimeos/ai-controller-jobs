<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2022
 */

$enc = $this->encoder();

$treeTarget = $this->config( 'client/html/catalog/tree/url/target' );
$treeCntl = $this->config( 'client/html/catalog/tree/url/controller', 'catalog' );
$treeAction = $this->config( 'client/html/catalog/tree/url/action', 'list' );
$treeFilter = array_flip( $this->config( 'client/html/catalog/tree/url/filter', [] ) );
$treeConfig = $this->config( 'client/html/catalog/tree/url/config', [] );
$treeConfig['absoluteUri'] = true;

$freq = $enc->xml( $this->get( 'siteFreq', 'daily' ) );
$locales = $this->get( 'siteLocales', [] );


foreach( $this->get( 'siteItems', [] ) as $id => $item )
{
	$langIds = [];
	$date = str_replace( ' ', 'T', $item->getTimeModified() ?? '' ) . date( 'P' );

	foreach( $locales as $locale )
	{
		$langId = $locale->getLanguageId();

		if( isset( $langIds[$langId] ) ) {
			continue;
		}
		$langIds[$langId] = true;

		$name = $item->getName( 'url', $langId );
		$params = ['site' => $locale->getSiteCode(), 'f_name' => \Aimeos\Base\Str::slug( $name ), 'f_catid' => $id];

		if( count( $locales ) > 1 ) {
			$params['locale'] = $langId;
		}

		$url = $this->url( $item->getTarget() ?: $treeTarget, $treeCntl, $treeAction, array_diff_key( $params, $treeFilter ), [], $treeConfig );

		echo '<url><loc>' . $enc->xml( $url ) . '</loc><lastmod>' . $date . '</lastmod><changefreq>' . $freq . "</changefreq></url>\n";
	}
}
