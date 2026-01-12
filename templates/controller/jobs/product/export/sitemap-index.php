<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2026
 */

$enc = $this->encoder();
$baseUrl = $this->get( 'baseUrl' );
$date = date( 'c' );

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach( $this->get( 'siteFiles', [] ) as $name ) {
	echo '<sitemap><loc>' . $enc->xml( $baseUrl . $name ) . '</loc><lastmod>' . $date . '</lastmod></sitemap>' . "\n";
}

echo '</sitemapindex>' . "\n";