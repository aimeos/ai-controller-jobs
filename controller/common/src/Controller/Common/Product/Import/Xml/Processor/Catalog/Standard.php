<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Product\Import\Xml\Processor\Catalog;


/**
 * Catalog processor for product XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface
{
	/** controller/common/product/import/xml/processor/catalog/name
	 * Name of the catalog processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Product\Import\Xml\Processor\Catalog\Myname".
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
	public function process( \Aimeos\MShop\Common\Item\Iface $item, \DOMNode $node )
	{
		\Aimeos\MW\Common\Base::checkClass( \Aimeos\MShop\Common\Item\ListRef\Iface::class, $item );

		$listManager = \Aimeos\MShop::create( $this->getContext(), 'catalog/lists' );

		$search = $listManager->createSearch()->setSlice( 0, 10000 );
		$search->setConditions( $search->combine( '&&', [
			$search->compare( '==', 'catalog.lists.domain', 'product' ),
			$search->compare( '==', 'catalog.lists.refid', $item->getId() ),
		] ) );

		$listItems = $listManager->searchItems( $search );
		$catItems = $this->getCatalogItems( $node );
		$map = [];

		foreach( $listItems as $listItem ) {
			$map[$listItem->getParentId()][$listItem->getType()] = $listItem;
		}


		foreach( $node->childNodes as $node )
		{
			if( $node->nodeName !== 'catalogitem'
				|| ( $refattr = $node->attributes->getNamedItem( 'ref' ) ) === null
				|| !isset( $catItems[$refattr->nodeValue] )
			) {
				continue;
			}

			$list = [];
			$catcode = $refattr->nodeValue;
			$parentid = $catItems[$refattr->nodeValue]->getId();
			$typeattr = $node->attributes->getNamedItem( 'lists.type' );
			$type = ( $typeattr !== null ? $typeattr->nodeValue : 'default' );

			foreach( $node->attributes as $attrName => $attrNode ) {
				$list['catalog.' . $attrName] = $attrNode->nodeValue;
			}

			$name = 'catalog.lists.config';
			$list[$name] = ( isset( $list[$name] ) ? (array) json_decode( $list[$name] ) : [] );
			$list['catalog.lists.type'] = $type;


			if( isset( $map[$catcode][$type] ) ) {
				$listItem = $map[$catcode][$type]; unset( $map[$catcode][$type] );
			} else {
				$listItem = $listManager->createItem();
			}

			$listItem = $listItem->fromArray( $list )->setDomain( 'product' )
				->setRefId( $item->getId() )->setParentId( $parentid );
			$listManager->saveItem( $listItem, false );
		}

		$listManager->deleteItems( array_keys( $listItems ) );

		return $item;
	}


	/**
	 * Returns the catalog items referenced in the DOM node
	 *
	 * @param \DOMNode $node XML document node containing a list of nodes to process
	 * @return \Aimeos\MShop\Catalog\Item\Iface[] List of referenced catalog items
	 */
	protected function getCatalogItems( \DOMNode $node )
	{
		foreach( $node->childNodes as $node )
		{
			if( $node->nodeName === 'catalogitem'
				&& ( $refAttr = $node->attributes->getNamedItem( 'ref' ) ) !== null
			) {
				$codes[] = $refAttr->nodeValue;
			}
		}

		$items = [];

		if( !empty( $codes ) )
		{
			$manager = \Aimeos\MShop::create( $this->getContext(), 'catalog' );

			$search = $manager->createSearch()->setSlice( 0, count( $codes ) );
			$search->setConditions( $search->compare( '==', 'catalog.code', $codes ) );

			foreach( $manager->searchItems( $search ) as $item ) {
				$items[$item->getCode()] = $item;
			}
		}

		return $items;
	}
}
