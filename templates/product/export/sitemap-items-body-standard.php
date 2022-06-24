<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2022
 */

$enc = $this->encoder();

$detailTarget = $this->config( 'client/html/catalog/detail/url/target' );
$detailCntl = $this->config( 'client/html/catalog/detail/url/controller', 'catalog' );
$detailAction = $this->config( 'client/html/catalog/detail/url/action', 'detail' );
$detailFilter = array_flip( $this->config( 'client/html/catalog/detail/url/filter', ['d_prodid'] ) );
$detailConfig = $this->config( 'client/html/catalog/detail/url/config', [] );
$detailConfig['absoluteUri'] = true;

$freq = $enc->xml( $this->get( 'siteFreq', 'daily' ) );
$locales = $this->get( 'siteLocales', [] );


foreach( $this->get( 'siteItems', [] ) as $id => $item )
{
	$langIds = [];
	$slug = $item->getName( 'url' );
	$date = str_replace( ' ', 'T', $item->getTimeModified() ?? '' ) . date( 'P' );

	foreach( $locales as $locale )
	{
		if( isset( $langIds[$locale->getLanguageId()] ) ) {
			continue;
		}

		$name = $item->getName( 'url', $locale->getLanguageId() );
		$params = ['site' => $locale->getSiteCode(), 'd_name' => \Aimeos\Base\Str::slug( $name ), 'd_prodid' => $id, 'd_pos' => ''];

		if( count( $locales ) > 1 )
		{
			$params['locale'] = $locale->getLanguageId();
			$params['currency'] = $locale->getCurrencyId();
		}

		$url = $this->url( $item->getTarget() ?: $detailTarget, $detailCntl, $detailAction, array_diff_key( $params, $detailFilter ), [], $detailConfig );

		echo '<url><loc>' . $enc->xml( $url ) . '</loc><lastmod>' . $date . '</lastmod><changefreq>' . $freq . "</changefreq></url>\n";
	}
}
