<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
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
				<text.content><![CDATA[<?= $enc->xml( $refItem->getLabel() ) ?>]]></text.content>
				<text.status><![CDATA[<?= $enc->xml( $refItem->getStatus() ) ?>]]></text.status>
			</textitem>
		<?php endif ?>
	<?php endforeach ?>
</text>
