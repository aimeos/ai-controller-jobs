<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */

/** controller/jobs/product/export/partials/text
 * Name of the partial used for exporting the text items into the product XML
 *
 * When exporting products into XML files, the assoicated text items are
 * added to the product XML node. This partial receives the list and text
 * items that associate the texts to the product. Then, the partial creates
 * the XML tags for these items that will be inserted into the product XML.
 *
 * @param string Name of the product text partial
 * @since 2019.04
 * @category Developer
 */

$enc = $this->encoder()

?>
<text>
	<?php foreach( $this->listItems as $listItem ) : ?>
		<?php if( $refItem = $listItem->getRefItem() ) : ?>
			<textitem lists.type="<?= $enc->attr( $listItem->getType() ); ?>" lists.config="<?= $enc->attr( json_encode( $listItem->getConfig() ) ); ?>"
				lists.datestart="<?= $enc->attr( str_replace( ' ', 'T', $listItem->getDateStart() ) ); ?>" lists.dateend="<?= $enc->attr( str_replace( ' ', 'T', $listItem->getDateEnd() ) ); ?>"
				lists.position="<?= $enc->attr( $listItem->getPosition() ); ?>" lists.status="<?= $enc->attr( $listItem->getStatus() ); ?>">
				<text.type><![CDATA[<?= $enc->xml( $refItem->getType() ) ?>]]></text.type>
				<text.languageid><![CDATA[<?= $enc->xml( $refItem->getLanguageId() ) ?>]]></text.languageid>
				<text.label><![CDATA[<?= $enc->xml( $refItem->getLabel() ) ?>]]></text.label>
				<text.content><![CDATA[<?= $enc->xml( $refItem->getContent() ) ?>]]></text.content>
				<text.status><![CDATA[<?= $enc->xml( $refItem->getStatus() ) ?>]]></text.status>
			</textitem>
		<?php endif ?>
	<?php endforeach ?>
</text>
