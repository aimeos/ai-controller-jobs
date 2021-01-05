<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 */

/** controller/jobs/product/export/partials/media
 * Name of the partial used for exporting the media items into the product XML
 *
 * When exporting products into XML files, the assoicated media items are
 * added to the product XML node. This partial receives the list and media
 * items that associate the media to the product. Then, the partial creates
 * the XML tags for these items that will be inserted into the product XML.
 *
 * @param string Name of the product media partial
 * @since 2019.04
 * @category Developer
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
