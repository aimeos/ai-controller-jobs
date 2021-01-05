<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */

/** controller/jobs/product/export/partials/product
 * Name of the partial used for exporting the product items into the product XML
 *
 * When exporting products into XML files, the assoicated product items are
 * added to the product XML node. This partial receives the list and product
 * items that associate the products to the product. Then, the partial creates
 * the XML tags for these items that will be inserted into the product XML.
 *
 * @param string Name of the product product partial
 * @since 2019.04
 * @category Developer
 */

$enc = $this->encoder()

?>
<product>
	<?php foreach( $this->listItems as $listItem ) : ?>
		<?php if( $refItem = $listItem->getRefItem() ) : ?>
			<productitem ref="<?= $enc->attr( $refItem->getCode() ) ?>"
				lists.type="<?= $enc->attr( $listItem->getType() ) ?>" lists.config="<?= $enc->attr( json_encode( $listItem->getConfig() ) ) ?>"
				lists.datestart="<?= $enc->attr( str_replace( ' ', 'T', $listItem->getDateStart() ) ) ?>" lists.dateend="<?= $enc->attr( str_replace( ' ', 'T', $listItem->getDateEnd() ) ) ?>"
				lists.position="<?= $enc->attr( $listItem->getPosition() ) ?>" lists.status="<?= $enc->attr( $listItem->getStatus() ) ?>" />
		<?php endif ?>
	<?php endforeach ?>
</product>
