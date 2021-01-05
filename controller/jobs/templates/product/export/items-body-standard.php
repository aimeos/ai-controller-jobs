<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 */

$enc = $this->encoder()

?>
<?php foreach( $this->get( 'exportItems', [] ) as $item ) : ?>
<productitem ref="<?= $enc->attr( $item->getCode() ) ?>">
	<product.type><![CDATA[<?= $enc->xml( $item->getType() ) ?>]]></product.type>
	<product.code><![CDATA[<?= $enc->xml( $item->getCode() ) ?>]]></product.code>
	<product.label><![CDATA[<?= $enc->xml( $item->getLabel() ) ?>]]></product.label>
	<product.status><![CDATA[<?= $enc->xml( $item->getStatus() ) ?>]]></product.status>
	<product.config><![CDATA[<?= $enc->xml( json_encode( $item->getConfig() ) ) ?>]]></product.config>
	<product.datestart><![CDATA[<?= $enc->xml( str_replace( ' ', 'T', $item->getDateStart() ) ) ?>]]></product.datestart>
	<product.dateend><![CDATA[<?= $enc->xml( str_replace( ' ', 'T', $item->getDateEnd() ) ) ?>]]></product.dateend>
	<lists>
		<?php foreach( $item->getDomains() as $domain ) : ?>
			<?= $this->partial(
				'product/export/items-partial-' . str_replace( '/', '', $domain ) . '-standard',
				['listItems' => $item->getListItems( $domain )]
			) ?>
		<?php endforeach ?>
	</lists>
	<property>
		<?php foreach( $item->getPropertyItems() as $propItem ) : ?>
			<propertyitem>
				<product.property.type><![CDATA[<?= $enc->xml( $propItem->getType() ) ?>]]></product.property.type>
				<product.property.languageid><![CDATA[<?= $enc->xml( $propItem->getLanguageId() ) ?>]]></product.property.languageid>
				<product.property.value><![CDATA[<?= $enc->xml( $propItem->getValue() ) ?>]]></product.property.value>
			</propertyitem>
		<?php endforeach ?>
	</property>
</productitem>
<?php endforeach ?>
