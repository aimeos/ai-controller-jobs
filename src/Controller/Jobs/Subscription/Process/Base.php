<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2026
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
	 * @throws \LogicException If class can't be instantiated
	 */
	protected function getProcessors( array $pnames ) : array
	{
		$list = [];
		$context = $this->context();
		$interface = \Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Iface::class;

		foreach( $pnames as $pname )
		{
			if( ctype_alnum( $pname ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $pname ), 400 );
			}

			$name = $context->config()->get( 'controller/jobs/subscription/process/processor/' . $pname . '/name', 'Standard' );

			if( ctype_alnum( $name ) === false ) {
				throw new \LogicException( sprintf( 'Invalid characters in class name "%1$s"', $name ), 400 );
			}

			$classname = '\\Aimeos\\Controller\\Jobs\\Common\\Subscription\\Process\\Processor\\' . ucfirst( $pname ) . '\\' . $name;

			$list[$pname] = \Aimeos\Utils::create( $classname, [$context], $interface );
		}

		return $list;
	}
}
