<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Attribute;


/**
 * Attribute list processor for XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface
{
	use \Aimeos\Controller\Common\Common\Import\Xml\Traits;


	/** controller/common/common/import/xml/processor/lists/attribute/name
	 * Name of the lists processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Attribute\Myname".
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
		$listItems = $item->getListItems( 'attribute', null, null, false );
		$listManager = \Aimeos\MShop::create( $context, $resource . '/lists' );
		$map = $this->getItems( $node->childNodes );

		foreach( $node->childNodes as $node )
		{
			$attributes = $node->attributes;

			if( $node->nodeName !== 'attributeitem' ) {
				continue;
			}

			if( ( $attr = $attributes->getNamedItem( 'ref' ) ) === null ) {
				continue;
			}

			$key = md5( $attr->nodeValue );

			if( !isset( $map[$key] ) ) {
				continue;
			}

			$list = [];
			$refId = $map[$key]->getId();
			$type = ( $attr = $attributes->getNamedItem( 'lists.type' ) ) !== null ? $attr->nodeValue : 'default';

			if( ( $listItem = $item->getListItem( 'attribute', $type, $refId ) ) === null ) {
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

			$this->addType( $resource . '/lists/type', 'attribute', $type );

			$listItem = $listItem->fromArray( $list )->setRefId( $refId );
			$item = $item->addListItem( 'attribute', $listItem );
		}

		return $item->deleteListItems( $listItems->toArray() );
	}


	/**
	 * Returns the attribute items for the given nodes
	 *
	 * @param \DomNodeList $nodes List of XML attribute item nodes
	 * @return \Aimeos\MShop\Attribute\Item\Iface[] Associative list of attribute items with codes as keys
	 */
	protected function getItems( \DomNodeList $nodes ) : array
	{
		$keys = $map = [];
		$manager = \Aimeos\MShop::create( $this->getContext(), 'attribute' );

		foreach( $nodes as $node )
		{
			if( $node->nodeName === 'attributeitem' && ( $attr = $node->attributes->getNamedItem( 'ref' ) ) !== null ) {
				$keys[$attr->nodeValue] = null;
			}
		}

		$search = $manager->filter()->slice( 0, count( $keys ) );
		$search->setConditions( $search->compare( '==', 'attribute.key', array_keys( $keys ) ) );

		foreach( $manager->search( $search, [] ) as $item ) {
			$map[$item->getKey()] = $item;
		}

		return $map;
	}
}
