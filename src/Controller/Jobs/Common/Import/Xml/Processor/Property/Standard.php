<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Property;


/**
 * Property processor for XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Iface
{
	/** controller/jobs/common/import/xml/processor/property/name
	 * Name of the property processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Property\Myname".
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
		\Aimeos\Utils::implements( $item, \Aimeos\MShop\Common\Item\PropertyRef\Iface::class );

		$resource = $item->getResourceType();
		$manager = \Aimeos\MShop::create( $this->context(), $resource );
		$propItems = $item->getPropertyItems( null, false );
		$map = [];

		foreach( $propItems as $propItem ) {
			$map[$propItem->getType()][$propItem->getLanguageId()][$propItem->getValue()] = $propItem->getId();
		}

		foreach( $node->childNodes as $propNode )
		{
			if( $propNode->nodeName !== 'propertyitem' ) {
				continue;
			}

			$list = [];

			foreach( $propNode->childNodes as $tagNode ) {
				$list[$tagNode->nodeName] = \Aimeos\Base\Str::decode( $tagNode->nodeValue );
			}

			$propItem = $manager->createPropertyItem()->fromArray( $list );

			if( isset( $map[$propItem->getType()][$propItem->getLanguageId()][$propItem->getValue()] ) ) {
				$propItems->remove( $map[$propItem->getType()][$propItem->getLanguageId()][$propItem->getValue()] );
			} else {
				$item->addPropertyItem( $propItem );
			}

			$this->addType( $resource . '/property/type', 'product', $propItem->getType() );
		}

		return $item->deletePropertyItems( $propItems->toArray() );
	}
}
