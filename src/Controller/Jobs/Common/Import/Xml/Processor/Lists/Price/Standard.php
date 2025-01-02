<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Lists\Price;


/**
 * Price list processor for XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Iface
{
	use \Aimeos\Controller\Jobs\Common\Import\Xml\Traits;


	/** controller/jobs/common/import/xml/processor/lists/price/name
	 * Name of the lists processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Lists\Price\Myname".
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

		$listItems = $item->getListItems( 'price', null, null, false )->reverse();
		$resource = $item->getResourceType();
		$context = $this->context();

		$manager = \Aimeos\MShop::create( $context, $resource );
		$priceManager = \Aimeos\MShop::create( $context, 'price' );

		foreach( $node->childNodes as $refNode )
		{
			if( $refNode->nodeName !== 'priceitem' ) {
				continue;
			}

			if( ( $listItem = $listItems->pop() ) === null ) {
				$listItem = $manager->createListItem();
			}

			if( ( $refItem = $listItem->getRefItem() ) === null ) {
				$refItem = $priceManager->create();
			}

			$list = [];

			foreach( $refNode->childNodes as $tag )
			{
				if( in_array( $tag->nodeName, ['lists'] ) ) {
					$refItem = $this->getProcessor( $tag->nodeName )->process( $refItem, $tag );
				} else {
					$list[$tag->nodeName] = $tag->nodeValue;
				}
			}

			$refItem = $refItem->fromArray( $list );

			foreach( $refNode->attributes as $attrName => $attrNode ) {
				$list[$resource . '.' . $attrName] = $attrNode->nodeValue;
			}

			$name = $resource . '.lists.config';
			$list[$name] = ( isset( $list[$name] ) ? (array) json_decode( $list[$name] ) : [] );
			$name = $resource . '.lists.type';
			$list[$name] = ( isset( $list[$name] ) ? $list[$name] : 'default' );

			$this->addType( $resource . '/lists/type', 'price', $list[$resource . '.lists.type'] );

			$listItem = $listItem->fromArray( $list );
			$item->addListItem( 'price', $listItem, $refItem );
		}

		return $item->deleteListItems( $listItems->toArray() );
	}
}
