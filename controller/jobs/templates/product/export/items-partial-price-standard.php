<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
 */

$enc = $this->encoder()

?>
<price>
	<?php foreach( $this->listItems as $listItem ) : ?>
		<?php if( $refItem = $listItem->getRefItem() ) : ?>
			<priceitem lists.type="<?= $enc->attr( $listItem->getType() ); ?>" lists.config="<?= $enc->attr( json_encode( $listItem->getConfig() ) ); ?>"
				lists.datestart="<?= $enc->attr( str_replace( ' ', 'T', $listItem->getDateStart() ) ); ?>" lists.dateend="<?= $enc->attr( str_replace( ' ', 'T', $listItem->getDateEnd() ) ); ?>"
				lists.position="<?= $enc->attr( $listItem->getPosition() ); ?>" lists.status="<?= $enc->attr( $listItem->getStatus() ); ?>">
				<price.type><![CDATA[<?= $enc->xml( $refItem->getType() ) ?>]]></price.type>
				<price.currencyid><![CDATA[<?= $enc->xml( $refItem->getCurrencyId() ) ?>]]></price.currencyid>
				<price.taxrate><![CDATA[<?= $enc->xml( $refItem->getTaxrate() ) ?>]]></price.taxrate>
				<price.quantity><![CDATA[<?= $enc->xml( $refItem->getQuantity() ) ?>]]></price.quantity>
				<price.value><![CDATA[<?= $enc->xml( $refItem->getValue() ) ?>]]></price.value>
				<price.costs><![CDATA[<?= $enc->xml( $refItem->getCosts() ) ?>]]></price.costs>
				<price.rebate><![CDATA[<?= $enc->xml( $refItem->getRebate() ) ?>]]></price.rebate>
				<price.label><![CDATA[<?= $enc->xml( $refItem->getLabel() ) ?>]]></price.label>
				<price.status><![CDATA[<?= $enc->xml( $refItem->getStatus() ) ?>]]></price.status>
			</priceitem>
		<?php endif ?>
	<?php endforeach ?>
</price>
