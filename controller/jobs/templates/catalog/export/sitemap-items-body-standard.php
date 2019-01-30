<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2017
 */

$enc = $this->encoder();

$listTarget = $this->config( 'client/html/catalog/lists/url/target' );
$listCntl = $this->config( 'client/html/catalog/lists/url/controller', 'catalog' );
$listAction = $this->config( 'client/html/catalog/lists/url/action', 'list' );
$listConfig = $this->config( 'client/html/catalog/lists/url/config', [] );

$freq = $enc->xml( $this->get( 'siteFreq', 'daily' ) );

?>
<?php foreach( $this->get( 'siteItems', [] ) as $id => $item ) : ?>
<?php
		$date = str_replace( ' ', 'T', $item->getTimeModified() ) . date( 'P' );
		$params = array( 'f_name' => $item->getName( 'url' ), 'f_catid' => $id );
		$url = $this->url( $item->getTarget() ?: $listTarget, $listCntl, $listAction, $params, [], $listConfig );
?>
	<url><loc><?php echo $enc->xml( $url ); ?></loc><lastmod><?php echo $date; ?></lastmod><changefreq><?php echo $freq; ?></changefreq></url>
<?php endforeach; ?>
