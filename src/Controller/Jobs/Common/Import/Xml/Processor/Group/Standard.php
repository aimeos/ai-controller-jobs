<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2026
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Group;


/**
 * Customer group processor for XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Iface
{
	use \Aimeos\Controller\Jobs\Common\Import\Xml\Traits;


	/** controller/jobs/common/import/xml/processor/group/name
	 * Name of the group processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Group\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @type string Last part of the processor class name
	 * @since 2019.04
	 */

	/** controller/jobs/common/import/xml/processor/group/allowed
	 * List of group codes that are allowed to be assigned to customers by imports
	 *
	 * Assigning privileged groups like "admin" or "editor" via imports would allow
	 * escalating privileges. If set, only the listed group codes can be assigned.
	 *
	 * @type array List of group codes
	 * @since 2025.10
	 * @see controller/jobs/common/import/xml/processor/group/denied
	 */

	/** controller/jobs/common/import/xml/processor/group/denied
	 * List of group codes that must not be assigned to customers by imports
	 *
	 * Prevents privilege escalation by disallowing privileged groups like "admin"
	 * and "editor" from being assigned to customers through XML imports.
	 *
	 * @type array List of group codes
	 * @since 2025.10
	 * @see controller/jobs/common/import/xml/processor/group/allowed
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
		\Aimeos\Utils::implements( $item, \Aimeos\MShop\Customer\Item\Iface::class );

		$map = $this->getItems( $node->childNodes );
		$list = [];

		foreach( $node->childNodes as $node )
		{
			if( $node->nodeName !== 'groupitem' ) {
				continue;
			}

			if( ( $attr = $node->attributes->getNamedItem( 'ref' ) ) === null ) {
				continue;
			}

			$attrValue = \Aimeos\Base\Str::decode( $attr->nodeValue );

			if( !isset( $map[$attrValue] ) ) {
				continue;
			}

			$list[] = $map[$attrValue]->getId();
		}

		// @phpstan-ignore return.type
		return $item->setGroups( $list );
	}


	/**
	 * Returns the attribute items for the given nodes
	 *
	 * @param \DOMNodeList<\DOMNode> $nodes List of XML attribute item nodes
	 * @return \Aimeos\MShop\Customer\Item\Group\Iface[] Associative list of customer group items with codes as keys
	 */
	protected function getItems( \DomNodeList $nodes ) : array
	{
		$keys = $map = [];
		$context = $this->context();
		$config = $context->config();
		$manager = \Aimeos\MShop::create( $context, 'group' );

		foreach( $nodes as $node )
		{
			// @phpstan-ignore property.notFound
			if( $node->nodeName === 'groupitem' && ( $attr = $node->attributes->getNamedItem( 'ref' ) ) !== null ) {
				$keys[\Aimeos\Base\Str::decode( $attr->nodeValue )] = null;
			}
		}

		$search = $manager->filter()->slice( 0, count( $keys ) );
		$search->add( 'group.code', '==', array_keys( $keys ) );

		// Only allow assigning group codes that are explicitly permitted (if configured)
		if( $allowed = $config->get( 'controller/jobs/common/import/xml/processor/group/allowed' ) ) {
			$search->add( 'group.code', '==', (array) $allowed );
		}

		// Never allow assigning privileged groups like "admin" or "editor" via imports
		if( $denied = $config->get( 'controller/jobs/common/import/xml/processor/group/denied', ['admin', 'editor'] ) ) {
			$search->add( 'group.code', '!=', (array) $denied );
		}

		foreach( $manager->search( $search, [] ) as $item ) {
			$map[$item->getCode()] = $item;
		}

		return $map;
	}
}
