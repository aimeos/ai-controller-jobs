<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2021
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Subscription\Process;


/**
 * Base job controller for subscription processing
 *
 * @package Controller
 * @subpackage Jobs
 */
abstract class Base
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/**
	 * Returns the processor object for managing the subscription resources
	 *
	 * @param array $pnames List of processor names
	 * @return array Associative list of processor names as keys and processor objects as values
	 */
	protected function getProcessors( array $pnames ) : array
	{
		$list = [];
		$context = $this->getContext();
		$config = $context->getConfig();

		foreach( $pnames as $pname )
		{
			if( ctype_alnum( $pname ) === false )
			{
				$classname = is_string( $pname ) ? '\\Aimeos\\Controller\\Common\\Subscription\\Process\\Processor\\' . $pname : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$name = $config->get( 'controller/common/subscription/process/processor/' . $pname . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false )
			{
				$classname = is_string( $name ) ? '\\Aimeos\\Controller\\Common\\Subscription\\Process\\Processor\\' . $pname . '\\' . $name : '<not a string>';
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Invalid characters in class name "%1$s"', $classname ) );
			}

			$classname = '\\Aimeos\\Controller\\Common\\Subscription\\Process\\Processor\\' . ucfirst( $pname ) . '\\' . $name;

			if( class_exists( $classname ) === false ) {
				throw new \Aimeos\Controller\Jobs\Exception( sprintf( 'Class "%1$s" not found', $classname ) );
			}

			$object = new $classname( $context );

			\Aimeos\MW\Common\Base::checkClass( '\\Aimeos\\Controller\\Common\\Subscription\\Process\\Processor\\Iface', $object );

			$list[$pname] = $object;
		}

		return $list;
	}
}
