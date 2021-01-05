<?php

/**
 * @copyright Aimeos GmbH (aimeos.com), 2019-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Group;


/**
 * Customer group processor for XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface
{
	use \Aimeos\Controller\Common\Common\Import\Xml\Traits;


	/** controller/common/common/import/xml/processor/group/name
	 * Name of the group processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Common\Import\Xml\Processor\Group\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2019.04
	 * @category Developer
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
		\Aimeos\MW\Common\Base::checkClass( \Aimeos\MShop\Customer\Item\Iface::class, $item );

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
		$manager = \Aimeos\MShop::create( $this->getContext(), 'customer/group' );

		foreach( $nodes as $node )
		{
			if( $node->nodeName === 'groupitem' && ( $attr = $node->attributes->getNamedItem( 'ref' ) ) !== null ) {
				$keys[$attr->nodeValue] = null;
			}
		}

		$search = $manager->filter()->slice( 0, count( $keys ) );
		$search->setConditions( $search->compare( '==', 'customer.group.code', array_keys( $keys ) ) );

		foreach( $manager->search( $search, [] ) as $item ) {
			$map[$item->getCode()] = $item;
		}

		return $map;
	}
}
