<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2021
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Catalog;


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
	/** controller/common/common/import/xml/processor/catalog/name
	 * Name of the catalog processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Common\Import\Xml\Processor\Catalog\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2019.04
	 * @category Developer
	 */


	private $listTypes = [];


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

		$listManager = \Aimeos\MShop::create( $this->getContext(), 'catalog/lists' );
		$listItems = $this->getListItems( $item->getResourceType(), $item->getId() );
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


			if( isset( $map[$parentid][$type] ) )
			{
				$listItem = $map[$parentid][$type];
				unset( $listItems[$map[$parentid][$type]->getId()] );
				unset( $map[$parentid][$type] );
			}
			else
			{
				$listItem = $listManager->create();
			}

			$listItem = $listItem->fromArray( $list )->setDomain( $item->getResourceType() )
				->setRefId( $item->getId() )->setParentId( $parentid );
			$listManager->save( $listItem, false );
		}

		$listManager->delete( $listItems->toArray() );

		return $item;
	}


	/**
	 * Returns the catalog items referenced in the DOM node
	 *
	 * @param \DOMNode $node XML document node containing a list of nodes to process
	 * @return \Aimeos\MShop\Catalog\Item\Iface[] List of referenced catalog items
	 */
	protected function getCatalogItems( \DOMNode $node ) : array
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

			$search = $manager->filter()->slice( 0, count( $codes ) );
			$search->setConditions( $search->compare( '==', 'catalog.code', $codes ) );

			foreach( $manager->search( $search ) as $item ) {
				$items[$item->getCode()] = $item;
			}
		}

		return $items;
	}


	/**
	 * Returns the catalog list items for the given referenced ID
	 *
	 * @param string $domain Domain name the referenced ID belongs to
	 * @param string $id ID of the referenced domain item
	 * @return \Aimeos\Map List of catalog list items
	 */
	protected function getListItems( $domain, $id ) : \Aimeos\Map
	{
		$manager = \Aimeos\MShop::create( $this->getContext(), 'catalog/lists' );
		$search = $manager->filter()->slice( 0, 10000 );

		$expr = [
			$search->compare( '==', 'catalog.lists.domain', $domain ),
			$search->compare( '==', 'catalog.lists.type', $this->getListTypes( $domain ) ),
			$search->compare( '==', 'catalog.lists.refid', $id ),
		];

		return $manager->search( $search->setConditions( $search->and( $expr ) ) );
	}


	/**
	 * Returns the available catalog list types for the given domain
	 *
	 * @param string $domain Domain name the list types belong to
	 * @return string[] List of list type codes
	 */
	protected function getListTypes( $domain ) : array
	{
		if( !isset( $this->listTypes[$domain] ) )
		{
			$this->listTypes[$domain] = [];

			$manager = \Aimeos\MShop::create( $this->getContext(), 'catalog/lists/type' );

			$search = $manager->filter()->slice( 0, 10000 );
			$search->setConditions( $search->compare( '==', 'catalog.lists.type.domain', $domain ) );

			foreach( $manager->search( $search ) as $item ) {
				$this->listTypes[$domain][] = $item->getCode();
			}
		}

		return $this->listTypes[$domain];
	}
}
