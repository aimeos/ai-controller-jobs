<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Address;


/**
 * Address processor for XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface
{
	/** controller/common/common/import/xml/processor/address/name
	 * Name of the address processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Common\Import\Xml\Processor\Address\Myname".
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
		\Aimeos\MW\Common\Base::checkClass( \Aimeos\MShop\Common\Item\AddressRef\Iface::class, $item );

		$manager = \Aimeos\MShop::create( $this->getContext(), $item->getResourceType() . '/address' );
		$addrItems = $item->getAddressItems()->reverse();

		foreach( $node->childNodes as $addrNode )
		{
			if( $addrNode->nodeName !== 'addressitem' ) {
				continue;
			}

			$list = [];

			foreach( $addrNode->childNodes as $tagNode ) {
				$list[$tagNode->nodeName] = $tagNode->nodeValue;
			}

			if( ( $addrItem = $addrItems->pop() ) !== null ) {
				$addrItems->remove( $addrItem->getId() );
			} else {
				$addrItem = $manager->create();
			}

			$item = $item->addAddressItem( $addrItem->fromArray( $list ), $addrItem->getId() );
		}

		return $item->deleteAddressItems( $addrItems->toArray() );
	}
}
