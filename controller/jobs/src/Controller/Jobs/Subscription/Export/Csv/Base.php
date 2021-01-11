<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Subscription\Export\Csv;


/**
 * Common class for CSV subscription export job controllers and processors.
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
	 * 'subscription' => array(
	 *     2 => 'subscription.interval',
	 *     3 => 'subscription.datenext',
	 *     4 => 'subscription.dateend',
	 * ),
	 * 'address' => array(
	 *     2 => 'subscription.base.address.type',
	 *     3 => 'subscription.base.address.firstname',
	 *     4 => 'subscription.base.address.lastname',
	 * ),
	 * 'product' => array(
	 *     2 => 'subscription.base.product.type',
	 *     3 => 'subscription.base.product.prodcode',
	 *     4 => 'subscription.base.product.quantity',
	 * ),
	 *
	 * @return array Associative list of domains as keys and a list of
	 * 	positions and the domain item keys as values.
	 */
	protected function getDefaultMapping() : array
	{
		return array(
			'subscription' => array(
				2 => 'subscription.interval',
				3 => 'subscription.datenext',
				4 => 'subscription.dateend',
				5 => 'subscription.period',
				6 => 'subscription.status',
				7 => 'subscription.ctime',
				8 => 'subscription.ordbaseid',
			),
			'address' => array(
				2 => 'order.base.address.type',
				3 => 'order.base.address.salutation',
				4 => 'order.base.address.company',
				5 => 'order.base.address.vatid',
				6 => 'order.base.address.title',
				7 => 'order.base.address.firstname',
				8 => 'order.base.address.lastname',
				9 => 'order.base.address.address1',
				10 => 'order.base.address.address2',
				11 => 'order.base.address.address3',
				12 => 'order.base.address.postal',
				13 => 'order.base.address.city',
				14 => 'order.base.address.state',
				15 => 'order.base.address.countryid',
				16 => 'order.base.address.languageid',
				17 => 'order.base.address.telephone',
				18 => 'order.base.address.telefax',
				19 => 'order.base.address.email',
				20 => 'order.base.address.website',
				21 => 'order.base.address.longitude',
				22 => 'order.base.address.latitude',
			),
			'product' => array(
				2 => 'order.base.product.type',
				3 => 'order.base.product.stocktype',
				4 => 'order.base.product.suppliername',
				5 => 'order.base.product.prodcode',
				6 => 'order.base.product.productid',
				7 => 'order.base.product.quantity',
				8 => 'order.base.product.name',
				9 => 'order.base.product.mediaurl',
				10 => 'order.base.product.price',
				11 => 'order.base.product.costs',
				12 => 'order.base.product.rebate',
				13 => 'order.base.product.taxrate',
				14 => 'order.base.product.status',
				15 => 'order.base.product.position',
				16 => 'order.base.product.attribute.type',
				17 => 'order.base.product.attribute.code',
				18 => 'order.base.product.attribute.name',
				19 => 'order.base.product.attribute.value',
			),
		);
	}


	/**
	 * Returns the processor object for saving the subscription/order related information
	 *
	 * @param array $mappings Associative list of processor types as keys and index/data mappings as values
	 * @return array Associative list of processor types as keys and processor objects as values
	 */
	protected function getProcessors( array $mappings ) : array
	{
		$list = [];
		$context = $this->getContext();
		$config = $context->getConfig();

		foreach( $mappings as $type => $mapping )
		{
			if( ctype_alnum( $type ) === false )
			{
				$classname = is_string( $type ) ? '\\Aimeos\\Controller\\Common\\Subscription\\Export\\Csv\\Processor\\' . $type : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$name = $config->get( 'controller/common/subscription/export/csv/processor/' . $type . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false )
			{
				$classname = is_string( $name ) ? '\\Aimeos\\Controller\\Common\\Subscription\\Export\\Csv\\Processor\\' . $type . '\\' . $name : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$classname = '\\Aimeos\\Controller\\Common\\Subscription\\Export\\Csv\\Processor\\' . ucfirst( $type ) . '\\' . $name;

			if( class_exists( $classname ) === false ) {
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Class "%1$s" not found', $classname ) );
			}

			$object = new $classname( $context, $mapping );

			\Aimeos\MW\Common\Base::checkClass( '\\Aimeos\\Controller\\Common\\Subscription\\Export\\Csv\\Processor\\Iface', $object );

			$list[$type] = $object;
		}

		return $list;
	}
}
