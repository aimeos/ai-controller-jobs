<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Lists\Text;


/**
 * Text list processor for XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Iface
{
	use \Aimeos\Controller\Jobs\Common\Import\Xml\Traits;


	/** controller/jobs/common/import/xml/processor/lists/text/name
	 * Name of the lists processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Lists\Text\Myname".
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

		$listItems = $item->getListItems( 'text', null, null, false )->reverse();
		$resource = $item->getResourceType();
		$context = $this->context();

		$manager = \Aimeos\MShop::create( $context, $resource );
		$textManager = \Aimeos\MShop::create( $context, 'text' );

		foreach( $node->childNodes as $refNode )
		{
			if( $refNode->nodeName !== 'textitem' ) {
				continue;
			}

			if( ( $listItem = $listItems->pop() ) === null ) {
				$listItem = $manager->createListItem();
			}

			if( ( $refItem = $listItem->getRefItem() ) === null ) {
				$refItem = $textManager->create();
			}

			$list = [];

			foreach( $refNode->childNodes as $tag )
			{
				if( in_array( $tag->nodeName, ['lists'] ) ) {
					$refItem = $this->getProcessor( $tag->nodeName )->process( $refItem, $tag );
				} else {
					$list[$tag->nodeName] = \Aimeos\Base\Str::decode( $tag->nodeValue, ENT_XML1 );
				}
			}

			$refItem = $refItem->fromArray( $list );

			foreach( $refNode->attributes as $attrName => $attrNode ) {
				$list[$resource . '.' . $attrName] = \Aimeos\Base\Str::decode( $attrNode->nodeValue, ENT_XML1 );
			}

			$name = $resource . '.lists.config';
			$list[$name] = ( isset( $list[$name] ) ? (array) json_decode( $list[$name] ) : [] );
			$name = $resource . '.lists.type';
			$list[$name] = ( isset( $list[$name] ) ? $list[$name] : 'default' );

			$this->addType( $resource . '/lists/type', 'text', $list[$resource . '.lists.type'] );

			$listItem = $listItem->fromArray( $list );
			$item->addListItem( 'text', $listItem, $refItem );
		}

		return $item->deleteListItems( $listItems->toArray() );
	}
}
