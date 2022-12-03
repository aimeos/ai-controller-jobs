<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2022
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
	 * 'invoice' => array(
	 *     2 => 'order.type',
	 *     3 => 'order.datepayment',
	 *     4 => 'order.statuspayment',
	 * ),
	 * 'address' => array(
	 *     2 => 'order.address.type',
	 *     3 => 'order.address.firstname',
	 *     4 => 'order.address.lastname',
	 * ),
	 * 'service' => array(
	 *     2 => 'order.service.type',
	 *     3 => 'order.service.code',
	 * ),
	 * 'coupon' => array(
	 *     2 => 'order.coupon.code',
	 * ),
	 * 'product' => array(
	 *     2 => 'order.product.type',
	 *     3 => 'order.product.prodcode',
	 *     4 => 'order.product.quantity',
	 * ),
	 *
	 * @return array Associative list of domains as keys ("item" is special for the order itself) and a list of
	 * 	positions and the domain item keys as values.
	 */
	protected function getDefaultMapping() : array
	{
		return array(
			'invoice' => array(
				2 => 'order.type',
				3 => 'order.datepayment',
				4 => 'order.statuspayment',
				5 => 'order.datedelivery',
				6 => 'order.statusdelivery',
				7 => 'order.relatedid',
				8 => 'order.customerid',
				9 => 'order.sitecode',
				10 => 'order.languageid',
				11 => 'order.currencyid',
				12 => 'order.price',
				13 => 'order.costs',
				14 => 'order.rebate',
				15 => 'order.taxvalue',
				16 => 'order.taxflag',
				17 => 'order.status',
				18 => 'order.comment',
			),
			'address' => array(
				2 => 'order.address.type',
				3 => 'order.address.salutation',
				4 => 'order.address.company',
				5 => 'order.address.vatid',
				6 => 'order.address.title',
				7 => 'order.address.firstname',
				8 => 'order.address.lastname',
				9 => 'order.address.address1',
				10 => 'order.address.address2',
				11 => 'order.address.address3',
				12 => 'order.address.postal',
				13 => 'order.address.city',
				14 => 'order.address.state',
				15 => 'order.address.countryid',
				16 => 'order.address.languageid',
				17 => 'order.address.telephone',
				18 => 'order.address.telefax',
				19 => 'order.address.email',
				20 => 'order.address.website',
				21 => 'order.address.longitude',
				22 => 'order.address.latitude',
			),
			'service' => array(
				2 => 'order.service.type',
				3 => 'order.service.code',
				4 => 'order.service.name',
				5 => 'order.service.mediaurl',
				6 => 'order.service.price',
				7 => 'order.service.costs',
				8 => 'order.service.rebate',
				9 => 'order.service.taxrate',
				10 => 'order.service.attribute.type',
				11 => 'order.service.attribute.code',
				12 => 'order.service.attribute.name',
				13 => 'order.service.attribute.value',
			),
			'coupon' => array(
				2 => 'order.coupon.code',
			),
			'product' => array(
				2 => 'order.product.type',
				3 => 'order.product.stocktype',
				4 => 'order.product.vendor',
				5 => 'order.product.prodcode',
				6 => 'order.product.productid',
				7 => 'order.product.quantity',
				8 => 'order.product.name',
				9 => 'order.product.mediaurl',
				10 => 'order.product.price',
				11 => 'order.product.costs',
				12 => 'order.product.rebate',
				13 => 'order.product.taxrate',
				14 => 'order.product.status',
				15 => 'order.product.position',
				16 => 'order.product.attribute.type',
				17 => 'order.product.attribute.code',
				18 => 'order.product.attribute.name',
				19 => 'order.product.attribute.value',
			),
		);
	}


	/**
	 * Returns the processor object for saving the order related information
	 *
	 * @param array $mappings Associative list of processor types as keys and index/data mappings as values
	 * @return array Associative list types as keys and processor objects as values
	 */
	protected function getProcessors( array $mappings ) : array
	{
		$list = [];
		$context = $this->context();
		$config = $context->config();

		foreach( $mappings as $type => $mapping )
		{
			if( ctype_alnum( $type ) === false )
			{
				$classname = is_string( $type ) ? '\\Aimeos\\Controller\\Common\\Order\\Export\\Csv\\Processor\\' . $type : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$name = $config->get( 'controller/common/order/export/csv/processor/' . $type . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false )
			{
				$classname = is_string( $name ) ? '\\Aimeos\\Controller\\Common\\Order\\Export\\Csv\\Processor\\' . $type . '\\' . $name : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$classname = '\\Aimeos\\Controller\\Common\\Order\\Export\\Csv\\Processor\\' . ucfirst( $type ) . '\\' . $name;

			if( class_exists( $classname ) === false ) {
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Class "%1$s" not found', $classname ) );
			}

			$object = new $classname( $context, $mapping );

			\Aimeos\MW\Common\Base::checkClass( '\\Aimeos\\Controller\\Common\\Order\\Export\\Csv\\Processor\\Iface', $object );

			$list[$type] = $object;
		}

		return $list;
	}
}
