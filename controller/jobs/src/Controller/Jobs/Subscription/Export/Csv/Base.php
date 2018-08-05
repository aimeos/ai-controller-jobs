<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018
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
	protected function getDefaultMapping()
	{
		return array(
			'subscription' => array(
				2 => 'subscription.interval',
				3 => 'subscription.datenext',
				4 => 'subscription.dateend',
			),
			'address' => array(
				2 => 'subscription.base.address.type',
				3 => 'subscription.base.address.salutation',
				4 => 'subscription.base.address.company',
				5 => 'subscription.base.address.vatid',
				6 => 'subscription.base.address.title',
				7 => 'subscription.base.address.firstname',
				8 => 'subscription.base.address.lastname',
				9 => 'subscription.base.address.address1',
				10 => 'subscription.base.address.address2',
				11 => 'subscription.base.address.address3',
				12 => 'subscription.base.address.postal',
				13 => 'subscription.base.address.city',
				14 => 'subscription.base.address.state',
				15 => 'subscription.base.address.countryid',
				16 => 'subscription.base.address.languageid',
				17 => 'subscription.base.address.telephone',
				18 => 'subscription.base.address.telefax',
				19 => 'subscription.base.address.email',
				20 => 'subscription.base.address.website',
				21 => 'subscription.base.address.longitude',
				22 => 'subscription.base.address.latitude',
			),
			'product' => array(
				2 => 'subscription.base.product.type',
				3 => 'subscription.base.product.stocktype',
				4 => 'subscription.base.product.suppliercode',
				5 => 'subscription.base.product.prodcode',
				6 => 'subscription.base.product.productid',
				7 => 'subscription.base.product.quantity',
				8 => 'subscription.base.product.name',
				9 => 'subscription.base.product.mediaurl',
				10 => 'subscription.base.product.price',
				11 => 'subscription.base.product.costs',
				12 => 'subscription.base.product.rebate',
				13 => 'subscription.base.product.taxrate',
				14 => 'subscription.base.product.status',
				15 => 'subscription.base.product.position',
				16 => 'subscription.base.product.attribute.type',
				17 => 'subscription.base.product.attribute.code',
				18 => 'subscription.base.product.attribute.name',
				19 => 'subscription.base.product.attribute.value',
			),
		);
	}


	/**
	 * Returns the processor object for saving the subscription/order related information
	 *
	 * @param array $mappings Associative list of processor types as keys and index/data mappings as values
	 * @return \Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Iface Processor object
	 */
	protected function getProcessors( array $mappings )
	{
		$list = [];
		$context = $this->getContext();
		$config = $context->getConfig();

		foreach( $mappings as $type => $mapping )
		{
			if( ctype_alnum( $type ) === false )
			{
				$classname = is_string($type) ? '\\Aimeos\\Controller\\Common\\Subscription\\Export\\Csv\\Processor\\' . $type : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$name = $config->get( 'controller/common/subscription/export/csv/processor/' . $type . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false )
			{
				$classname = is_string($name) ? '\\Aimeos\\Controller\\Common\\Subscription\\Export\\Csv\\Processor\\' . $type . '\\' . $name : '<not a string>';
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
