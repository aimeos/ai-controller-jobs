<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Product;


/**
 * Product list processor for XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface
{
	use \Aimeos\Controller\Common\Common\Import\Xml\Traits;


	/** controller/common/common/import/xml/processor/lists/product/name
	 * Name of the lists processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Product\Myname".
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
		\Aimeos\MW\Common\Base::checkClass( \Aimeos\MShop\Common\Item\ListsRef\Iface::class, $item );

		$context = $this->getContext();
		$resource = $item->getResourceType();
		$listItems = $item->getListItems( 'product', null, null, false );
		$listManager = \Aimeos\MShop::create( $context, $resource . '/lists' );
		$map = $this->getItems( $node->childNodes );

		foreach( $node->childNodes as $node )
		{
			$attributes = $node->attributes;

			if( $node->nodeName !== 'productitem' ) {
				continue;
			}

			if( ( $attr = $attributes->getNamedItem( 'ref' ) ) === null || !isset( $map[$attr->nodeValue] ) ) {
				continue;
			}

			$list = [];
			$refId = $map[$attr->nodeValue]->getId();
			$type = ( $attr = $attributes->getNamedItem( 'lists.type' ) ) !== null ? $attr->nodeValue : 'default';

			if( ( $listItem = $item->getListItem( 'product', $type, $refId ) ) === null ) {
				$listItem = $listManager->create();
			} else {
				unset( $listItems[$listItem->getId()] );
			}

			foreach( $attributes as $attrName => $attrNode ) {
				$list[$resource . '.' . $attrName] = $attrNode->nodeValue;
			}

			$name = $resource . '.lists.config';
			$list[$name] = ( isset( $list[$name] ) ? (array) json_decode( $list[$name] ) : [] );
			$list[$resource . '.lists.type'] = $type;

			$this->addType( $resource . '/lists/type', 'product', $type );

			$listItem = $listItem->fromArray( $list )->setRefId( $refId );
			$item = $item->addListItem( 'product', $listItem );
		}

		return $item->deleteListItems( $listItems->toArray() );
	}


	/**
	 * Returns the product items for the given nodes
	 *
	 * @param \DomNodeList $nodes List of XML product item nodes
	 * @return \Aimeos\MShop\Product\Item\Iface[] Associative list of product items with codes as keys
	 */
	protected function getItems( \DomNodeList $nodes ) : array
	{
		$codes = $map = [];
		$manager = \Aimeos\MShop::create( $this->getContext(), 'product' );

		foreach( $nodes as $node )
		{
			if( $node->nodeName === 'productitem' && ( $attr = $node->attributes->getNamedItem( 'ref' ) ) !== null ) {
				$codes[$attr->nodeValue] = null;
			}
		}

		$search = $manager->filter()->slice( 0, count( $codes ) );
		$search->setConditions( $search->compare( '==', 'product.code', array_keys( $codes ) ) );

		foreach( $manager->search( $search, [] ) as $item ) {
			$map[$item->getCode()] = $item;
		}

		return $map;
	}
}
