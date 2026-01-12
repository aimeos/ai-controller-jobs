<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2026
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Lists\Catalog;


/**
 * Catalog list processor for XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Iface
{
	use \Aimeos\Controller\Jobs\Common\Import\Xml\Traits;


	/** controller/jobs/common/import/xml/processor/lists/catalog/name
	 * Name of the lists processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Lists\Catalog\Myname".
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
		\Aimeos\Utils::implements( $item, \Aimeos\MShop\Common\Item\ListsRef\Iface::class );

		$context = $this->context();
		$resource = $item->getResourceType();
		$listItems = $item->getListItems( 'catalog', null, null, false );
		$manager = \Aimeos\MShop::create( $context, $resource );
		$map = $this->getItems( $node->childNodes );

		foreach( $node->childNodes as $node )
		{
			$attributes = $node->attributes;

			if( $node->nodeName !== 'catalogitem' ) {
				continue;
			}

			if( ( $attr = $attributes->getNamedItem( 'ref' ) ) === null ) {
				continue;
			}

			$attrValue = \Aimeos\Base\Str::decode( $attr->nodeValue );

			if( !isset( $map[$attrValue] ) ) {
				continue;
			}

			$list = [];
			$refItem = $map[$attrValue];
			$type = ( $attr = $attributes->getNamedItem( 'lists.type' ) ) !== null ? $attr->nodeValue : 'default';

			if( ( $listItem = $item->getListItem( 'catalog', $type, $refItem->getId() ) ) === null ) {
				$listItem = $manager->createListItem();
			} else {
				unset( $listItems[$listItem->getId()] );
			}

			foreach( $attributes as $attrName => $attrNode ) {
				$list[$resource . '.' . $attrName] = \Aimeos\Base\Str::decode( $attrNode->nodeValue );
			}

			$name = $resource . '.lists.config';
			$list[$name] = ( isset( $list[$name] ) ? (array) json_decode( $list[$name] ) : [] );
			$list[$resource . '.lists.type'] = $type;

			$this->addType( $resource . '/lists/type', 'catalog', $type );

			$listItem = $listItem->fromArray( $list );
			$item->addListItem( 'catalog', $listItem, $refItem );
		}

		return $item->deleteListItems( $listItems->toArray() );
	}


	/**
	 * Returns the catalog items for the given nodes
	 *
	 * @param \DomNodeList $nodes List of XML catalog item nodes
	 * @return \Aimeos\MShop\Catalog\Item\Iface[] Associative list of catalog items with codes as keys
	 */
	protected function getItems( \DomNodeList $nodes ) : array
	{
		$codes = $map = [];
		$manager = \Aimeos\MShop::create( $this->context(), 'catalog' );

		foreach( $nodes as $node )
		{
			if( $node->nodeName === 'catalogitem' && ( $attr = $node->attributes->getNamedItem( 'ref' ) ) !== null ) {
				$codes[\Aimeos\Base\Str::decode( $attr->nodeValue )] = null;
			}
		}

		$search = $manager->filter()->slice( 0, count( $codes ) );
		$search->setConditions( $search->compare( '==', 'catalog.code', array_keys( $codes ) ) );

		foreach( $manager->search( $search, [] ) as $item ) {
			$map[$item->getCode()] = $item;
		}

		return $map;
	}
}
