<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Customer\Import\Xml\Processor\Group;


/**
 * Group processor for customer XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Iface
{
	/** controller/jobs/customer/import/xml/processor/group/name
	 * Name of the group processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Customer\Import\Xml\Processor\Group\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2019.04
	 */

	 private \Aimeos\MShop\Common\Manager\Iface $groupManager;
	 private array $map = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context )
	{
		parent::__construct( $context );

		$this->groupManager = \Aimeos\MShop::create( $context, 'group' );
		$filter = $this->groupManager->filter();
		$config = $context->config();

		if( $allowed = $config->get( 'controller/jobs/customer/import/csv/processor/group/allowed' ) ) {
			$filter->add( 'group.code', '==', (array) $allowed );
		}

		if( $denied = $config->get( 'controller/jobs/customer/import/csv/processor/group/denied', ['admin', 'editor'] ) ) {
			$filter->add( 'group.code', '!=', (array) $denied );
		}

		$cursor = $this->groupManager->cursor( $filter );

		while( $items = $this->groupManager->iterate( $cursor ) )
		{
			foreach( $items as $item ) {
				$this->map[$item->getCode()] = $item->getId();
			}
		}
	}


	/**
	 * Updates the given item using the data from the DOM node
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Item which should be updated
	 * @param \DOMNode $node XML document node containing a list of nodes to process
	 * @return \Aimeos\MShop\Common\Item\Iface Updated item
	 */
	public function process( \Aimeos\MShop\Common\Item\Iface $item, \DOMNode $node ) : \Aimeos\MShop\Common\Item\Iface
	{
		\Aimeos\Utils::implements( $item, \Aimeos\MShop\Customer\Item\Iface::class );

		$ids = [];

		foreach( $node->childNodes as $node )
		{
			if( $node->nodeName === 'groupitem' && ( $refattr = $node->attributes->getNamedItem( 'ref' ) ) !== null )
			{
				$code = $refattr->nodeValue;

				if( !isset( $this->map[$code] ) ) {
					$this->map[$code] = $this->groupManager->find( $code )->getId();
				}

				$ids[] = $this->map[$code];
			}
		}

		return $item->setGroups( $ids );
	}
}
