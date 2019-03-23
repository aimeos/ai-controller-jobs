<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
 */

$enc = $this->encoder()

?>
<attribute>
	<?php foreach( $this->listItems as $listItem ) : ?>
		<?php if( $refItem = $listItem->getRefItem() ) : ?>
			<attributeitem ref="<?= $enc->attr( $refItem->getDomain() . '|' . $refItem->getType() . '|' . $refItem->getCode() ) ?>"
				lists.type="<?= $enc->attr( $listItem->getType() ) ?>" lists.config="<?= $enc->attr( json_encode( $listItem->getConfig() ) ) ?>"
				lists.datestart="<?= $enc->attr( str_replace( ' ', 'T', $listItem->getDateStart() ) ) ?>" lists.dateend="<?= $enc->attr( str_replace( ' ', 'T', $listItem->getDateEnd() ) ) ?>"
				lists.position="<?= $enc->attr( $listItem->getPosition() ) ?>" lists.status="<?= $enc->attr( $listItem->getStatus() ) ?>" />
		<?php endif ?>
	<?php endforeach ?>
</attribute>
