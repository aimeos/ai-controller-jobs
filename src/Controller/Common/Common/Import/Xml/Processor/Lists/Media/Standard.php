<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2022
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Media;


/**
 * Media list processor for XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Common\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Common\Common\Import\Xml\Processor\Iface
{
	use \Aimeos\Controller\Common\Common\Import\Xml\Traits;


	/** controller/common/common/import/xml/processor/lists/media/name
	 * Name of the lists processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Common\Common\Import\Xml\Processor\Lists\Media\Myname".
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

		$listItems = $item->getListItems( 'media', null, null, false )->reverse();
		$resource = $item->getResourceType();
		$context = $this->context();

		$manager = \Aimeos\MShop::create( $context, $resource );
		$mediaManager = \Aimeos\MShop::create( $context, 'media' );

		foreach( $node->childNodes as $refNode )
		{
			if( $refNode->nodeName !== 'mediaitem' ) {
				continue;
			}

			if( ( $listItem = $listItems->pop() ) === null ) {
				$listItem = $manager->createListItem();
			}

			if( ( $refItem = $listItem->getRefItem() ) === null ) {
				$refItem = $mediaManager->create();
			}

			$list = [];

			foreach( $refNode->childNodes as $tag )
			{
				if( in_array( $tag->nodeName, ['lists', 'property'] ) ) {
					$refItem = $this->getProcessor( $tag->nodeName )->process( $refItem, $tag );
				} else {
					$list[$tag->nodeName] = $tag->nodeValue;
				}
			}

			$refItem = $this->update( $refItem, $list );

			foreach( $refNode->attributes as $attrName => $attrNode ) {
				$list[$resource . '.' . $attrName] = $attrNode->nodeValue;
			}

			$name = $resource . '.lists.config';
			$list[$name] = ( isset( $list[$name] ) ? (array) json_decode( $list[$name] ) : [] );
			$name = $resource . '.lists.type';
			$list[$name] = $list[$name] ?? 'default';

			$this->addType( $resource . '/lists/type', 'media', $list[$resource . '.lists.type'] );

			$listItem = $listItem->fromArray( $list );
			$item->addListItem( 'media', $listItem, $refItem );
		}

		return $item->deleteListItems( $listItems->toArray() );
	}


	/**
	 * Updates the media item with the given key/value pairs
	 *
	 * @param \Aimeos\MShop\Media\Item\Iface $refItem Media item to update
	 * @param array &$list Associative list of key/value pairs, matching pairs are removed
	 * @return \Aimeos\MShop\Media\Item\Iface Updated media item
	 */
	protected function update( \Aimeos\MShop\Media\Item\Iface $refItem, array &$list )
	{
		$context = $this->context();
		$fs = $context->fs( $refItem->getFileSystem() );
		$url = $list['media.url'] ?? '';

		try
		{
			if( isset( $list['media.previews'] ) && ( $map = json_decode( $list['media.previews'], true ) ) !== null ) {
				$refItem->setPreviews( $map )->setUrl( $url );
			} elseif( isset( $list['media.preview'] ) ) {
				$refItem->setPreview( $list['media.preview'] )->setUrl( $url );
			} elseif( $refItem->getPreviews() === [] || $refItem->getUrl() !== $url ) {
				$refItem = \Aimeos\MShop::create( $context, 'media' )->scale( $refItem->setUrl( $url ), true );
			} elseif( $fs->has( $url ) ) {
				$refItem = \Aimeos\MShop::create( $context, 'media' )->scale( $refItem->setUrl( $url ) );
			}

			unset( $list['media.previews'], $list['media.preview'] );
		}
		catch( \Aimeos\Controller\Common\Exception $e )
		{
			$msg = sprintf( 'Scaling image "%1$s" failed: %2$s', $url, $e->getMessage() );
			$context->logger()->error( $msg, 'import/xml/product' );
		}

		return $refItem->fromArray( $list );
	}
}
