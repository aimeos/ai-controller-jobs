<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Order\Export\Csv;


/**
 * Common class for CSV order export job controllers and processors.
 *
 * @package Controller
 * @subpackage Common
 */
class Base
	extends \Aimeos\Controller\Jobs\Base
{
	/**
	 * Returns the default mapping for the CSV fields to the domain item keys
	 *
	 * Example:
	 *  'item' => array(
	 *  	0 => 'order.code', // e.g. unique EAN code
	 *  	1 => 'order.label', // UTF-8 encoded text, also used as order name
	 *  ),
	 *  'text' => array(
	 *  	3 => 'text.type', // e.g. "short" for short description
	 *  	4 => 'text.content', // UTF-8 encoded text
	 *  ),
	 *  'media' => array(
	 *  	5 => 'media.url', // relative URL of the order image on the server
	 *  ),
	 *  'price' => array(
	 *  	6 => 'price.value', // price with decimals separated by a dot, no thousand separator
	 *  	7 => 'price.taxrate', // tax rate with decimals separated by a dot
	 *  ),
	 *  'attribute' => array(
	 *  	8 => 'attribute.type', // e.g. "size", "length", "width", "color", etc.
	 *  	9 => 'attribute.code', // code of an existing attribute, new ones will be created automatically
	 *  ),
	 *  'order' => array(
	 *  	10 => 'order.code', // e.g. EAN code of another order
	 *  	11 => 'order.lists.type', // e.g. "suggestion" for suggested order
	 *  ),
	 *  'property' => array(
	 *  	12 => 'order.property.type', // e.g. "package-weight"
	 *  	13 => 'order.property.value', // arbitrary value for the corresponding type
	 *  ),
	 *  'catalog' => array(
	 *  	14 => 'catalog.code', // e.g. Unique category code
	 *  	15 => 'catalog.lists.type', // e.g. "promotion" for top seller orders
	 *  ),
	 *
	 * @return array Associative list of domains as keys ("item" is special for the order itself) and a list of
	 * 	positions and the domain item keys as values.
	 */
	protected function getDefaultMapping()
	{
		return array(
			'invoice' => array(
				0 => 'order.type',
				1 => 'order.datepayment',
				2 => 'order.statuspayment',
				3 => 'order.datedelivery',
				4 => 'order.statusdelivery',
				5 => 'order.relatedid',
				6 => 'order.base.customerid',
				7 => 'order.base.sitecode',
				8 => 'order.base.languageid',
				9 => 'order.base.currencyid',
				10 => 'order.base.price',
				11 => 'order.base.costs',
				12 => 'order.base.rebate',
				13 => 'order.base.taxvalue',
				14 => 'order.base.taxflag',
				15 => 'order.base.status',
				16 => 'order.base.comment',
			),
			'address' => array(
				0 => 'order.base.address.type',
				1 => 'order.base.address.salutation',
				2 => 'order.base.address.company',
				3 => 'order.base.address.vatid',
				4 => 'order.base.address.title',
				5 => 'order.base.address.firstname',
				6 => 'order.base.address.lastname',
				7 => 'order.base.address.address1',
				8 => 'order.base.address.address2',
				9 => 'order.base.address.address3',
				10 => 'order.base.address.postal',
				11 => 'order.base.address.city',
				12 => 'order.base.address.state',
				13 => 'order.base.address.countryid',
				14 => 'order.base.address.languageid',
				15 => 'order.base.address.telephone',
				16 => 'order.base.address.telefax',
				17 => 'order.base.address.email',
				18 => 'order.base.address.website',
				19 => 'order.base.address.longitude',
				20 => 'order.base.address.latitude',
			),
			'service' => array(
				0 => 'order.base.service.type',
				1 => 'order.base.service.code',
				2 => 'order.base.service.name',
				3 => 'order.base.service.mediaurl',
				4 => 'order.base.service.price',
				5 => 'order.base.service.costs',
				6 => 'order.base.service.rebate',
				7 => 'order.base.service.taxrate',
				8 => 'order.base.service.attribute.type',
				9 => 'order.base.service.attribute.name',
				10 => 'order.base.service.attribute.code',
				11 => 'order.base.service.attribute.value',
			),
			'coupon' => array(
				0 => 'order.base.coupon.code',
			),
			'product' => array(
				0 => 'order.base.product.type',
				1 => 'order.base.product.stocktype',
				2 => 'order.base.product.suppliercode',
				3 => 'order.base.product.prodcode',
				4 => 'order.base.product.productid',
				5 => 'order.base.product.quantity',
				6 => 'order.base.product.name',
				7 => 'order.base.product.mediaurl',
				8 => 'order.base.product.price',
				9 => 'order.base.product.costs',
				10 => 'order.base.product.rebate',
				11 => 'order.base.product.taxrate',
				12 => 'order.base.product.status',
				13 => 'order.base.product.position',
				14 => 'order.base.product.attribute.type',
				15 => 'order.base.product.attribute.name',
				16 => 'order.base.product.attribute.code',
				17 => 'order.base.product.attribute.value',
			),
		);
	}


	/**
	 * Returns the processor object for saving the order related information
	 *
	 * @param array $mappings Associative list of processor types as keys and index/data mappings as values
	 * @return \Aimeos\Controller\Common\Order\Export\Csv\Processor\Iface Processor object
	 */
	protected function getProcessors( array $mappings )
	{
		$list = [];
		$context = $this->getContext();
		$config = $context->getConfig();
		$iface = '\\Aimeos\\Controller\\Common\\Order\\Export\\Csv\\Processor\\Iface';

		foreach( $mappings as $type => $mapping )
		{
			if( ctype_alnum( $type ) === false )
			{
				$classname = is_string($type) ? '\\Aimeos\\Controller\\Common\\Order\\Export\\Csv\\Processor\\' . $type : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$name = $config->get( 'controller/common/order/export/csv/processor/' . $type . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false )
			{
				$classname = is_string($name) ? '\\Aimeos\\Controller\\Common\\Order\\Export\\Csv\\Processor\\' . $type . '\\' . $name : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$classname = '\\Aimeos\\Controller\\Common\\Order\\Export\\Csv\\Processor\\' . ucfirst( $type ) . '\\' . $name;

			if( class_exists( $classname ) === false ) {
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Class "%1$s" not found', $classname ) );
			}

			$object = new $classname( $context, $mapping );

			if( !( $object instanceof $iface ) ) {
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Class "%1$s" does not implement interface "%2$s"', $classname, $iface ) );
			}

			$list[$type] = $object;
		}

		return $list;
	}
}
