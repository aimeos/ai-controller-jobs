<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */

$enc = $this->encoder();
$date = date( 'c' );
$baseUrl = $this->get( 'baseUrl' );

?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>


<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach( $this->get( 'siteFiles', [] ) as $name ) : ?>
	<sitemap>
		<loc><?php echo $enc->xml( $baseUrl . basename( $name ) ); ?></loc>
		<lastmod><?php echo $date; ?></lastmod>
	</sitemap>
<?php endforeach; ?>
</sitemapindex>