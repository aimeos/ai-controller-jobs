<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2026
 */

$enc = $this->encoder();

$detailTarget = $this->config( 'client/html/catalog/detail/url/target' );
$detailCntl = $this->config( 'client/html/catalog/detail/url/controller', 'catalog' );
$detailAction = $this->config( 'client/html/catalog/detail/url/action', 'detail' );
$detailFilter = array_flip( $this->config( 'client/html/catalog/detail/url/filter', ['d_prodid'] ) );
$detailConfig = $this->config( 'client/html/catalog/detail/url/config', [] );
$detailConfig['absoluteUri'] = true;

$locales = $this->get( 'siteLocales', map() );
$sites = $locales->groupBy( 'locale.siteid' );


echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";


foreach( $this->get( 'siteItems', [] ) as $id => $item )
{
	$langIds = [];
	$slug = $item->getName( 'url' );
	$date = str_replace( ' ', 'T', $item->getTimeModified() ?? '' ) . date( 'P' );

	foreach( $locales as $locale )
	{
		$langId = $locale->getLanguageId();

		if( isset( $langIds[$langId] ) ) {
			continue;
		}
		$langIds[$langId] = true;

		$name = \Aimeos\Base\Str::slug( $item->getName( 'url', $langId ) );
		$params = ['path' => $name, 'd_name' => $name, 'd_prodid' => $id, 'd_pos' => '', 'site' => $locale->getSiteCode()];

		if( count( $locales ) > 1 )
		{
			$params['locale'] = $langId;
			$params['currency'] = $locale->getCurrencyId();
		}

		$url = $this->url( $item->getTarget() ?: $detailTarget, $detailCntl, $detailAction, array_diff_key( $params, $detailFilter ), [], $detailConfig );

		echo '<url><loc>' . $enc->xml( $url ) . '</loc><lastmod>' . $date . "</lastmod></url>\n";
	}
}

echo "</urlset>\n";