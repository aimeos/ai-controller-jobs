<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2023
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
	 *     2 => 'subscription.address.type',
	 *     3 => 'subscription.address.firstname',
	 *     4 => 'subscription.address.lastname',
	 * ),
	 * 'product' => array(
	 *     2 => 'subscription.product.type',
	 *     3 => 'subscription.product.prodcode',
	 *     4 => 'subscription.product.quantity',
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
	 * Returns the processor object for saving the subscription/order related information
	 *
	 * @param array $mappings Associative list of processor types as keys and index/data mappings as values
	 * @return array Associative list of processor types as keys and processor objects as values
	 * @throws \LogicException If class can't be instantiated
	 */
	protected function getProcessors( array $mappings ) : array
	{
		$list = [];
		$context = $this->context();
		$config = $context->config();

		foreach( $mappings as $type => $mapping )
		{
			if( ctype_alnum( $type ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $type ), 400 );
			}

			$name = $config->get( 'controller/common/subscription/export/csv/processor/' . $type . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $name ), 400 );
			}

			$classname = '\\Aimeos\\Controller\\Common\\Subscription\\Export\\Csv\\Processor\\' . ucfirst( $type ) . '\\' . $name;
			$interface = \Aimeos\Controller\Common\Subscription\Export\Csv\Processor\Iface::class;

			$object = \Aimeos\Utils::create( $classname, [$context, $mapping], $interface );

			$list[$type] = $object;
		}

		return $list;
	}
}
