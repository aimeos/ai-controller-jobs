<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Group;


/**
 * Customer group processor for XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Iface
{
	use \Aimeos\Controller\Jobs\Common\Import\Xml\Traits;


	/** controller/jobs/common/import/xml/processor/group/name
	 * Name of the group processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Group\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2019.04
	 */


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

		$map = $this->getItems( $node->childNodes );
		$list = [];

		foreach( $node->childNodes as $node )
		{
			if( $node->nodeName !== 'groupitem' ) {
				continue;
			}

			if( ( $attr = $node->attributes->getNamedItem( 'ref' ) ) === null || !isset( $map[$attr->nodeValue] ) ) {
				continue;
			}

			$list[] = $map[$attr->nodeValue]->getId();
		}

		return $item->setGroups( $list );
	}


	/**
	 * Returns the attribute items for the given nodes
	 *
	 * @param \DomNodeList $nodes List of XML attribute item nodes
	 * @return \Aimeos\MShop\Customer\Item\Group\Iface[] Associative list of customer group items with codes as keys
	 */
	protected function getItems( \DomNodeList $nodes ) : array
	{
		$keys = $map = [];
		$manager = \Aimeos\MShop::create( $this->context(), 'group' );

		foreach( $nodes as $node )
		{
			if( $node->nodeName === 'groupitem' && ( $attr = $node->attributes->getNamedItem( 'ref' ) ) !== null ) {
				$keys[$attr->nodeValue] = null;
			}
		}

		$search = $manager->filter()->slice( 0, count( $keys ) );
		$search->setConditions( $search->compare( '==', 'group.code', array_keys( $keys ) ) );

		foreach( $manager->search( $search, [] ) as $item ) {
			$map[$item->getCode()] = $item;
		}

		return $map;
	}
}
