<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
 */

$enc = $this->encoder()

?>
<media>
	<?php foreach( $this->listItems as $listItem ) : ?>
		<?php if( $refItem = $listItem->getRefItem() ) : ?>
			<mediaitem lists.type="<?= $enc->attr( $listItem->getType() ); ?>" lists.config="<?= $enc->attr( json_encode( $listItem->getConfig() ) ); ?>"
				lists.datestart="<?= $enc->attr( str_replace( ' ', 'T', $listItem->getDateStart() ) ); ?>" lists.dateend="<?= $enc->attr( str_replace( ' ', 'T', $listItem->getDateEnd() ) ); ?>"
				lists.position="<?= $enc->attr( $listItem->getPosition() ); ?>" lists.status="<?= $enc->attr( $listItem->getStatus() ); ?>">
				<media.type><![CDATA[<?= $enc->xml( $refItem->getType() ) ?>]]></media.type>
				<media.languageid><![CDATA[<?= $enc->xml( $refItem->getLanguageId() ) ?>]]></media.languageid>
				<media.label><![CDATA[<?= $enc->xml( $refItem->getLabel() ) ?>]]></media.label>
				<media.url><![CDATA[<?= $enc->xml( $refItem->getUrl() ) ?>]]></media.url>
				<media.preview><![CDATA[<?= $enc->xml( $refItem->getPreview() ) ?>]]></media.preview>
				<media.mimetype><![CDATA[<?= $enc->xml( $refItem->getMimetype() ) ?>]]></media.mimetype>
				<media.status><![CDATA[<?= $enc->xml( $refItem->getStatus() ) ?>]]></media.status>
				<property>
					<?php foreach( $refItem->getPropertyItems() as $propItem ) : ?>
						<propertyitem>
							<product.property.type><![CDATA[<?= $enc->xml( $propItem->getType() ) ?>]]></product.property.type>
							<product.property.languageid><![CDATA[<?= $enc->xml( $propItem->getLanguageId() ) ?>]]></product.property.languageid>
							<product.property.value><![CDATA[<?= $enc->xml( $propItem->getValue() ) ?>]]></product.property.value>
						</propertyitem>
					<?php endforeach ?>
				</property>
			</mediaitem>
		<?php endif ?>
	<?php endforeach ?>
</media>
