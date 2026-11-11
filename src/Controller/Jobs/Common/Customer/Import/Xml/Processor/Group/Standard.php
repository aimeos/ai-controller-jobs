<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019-2026
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Customer\Import\Xml\Processor\Group;


/**
 * Group processor for customer XML imports
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Import\Xml\Processor\Iface
{
	/** controller/jobs/customer/import/xml/processor/group/name
	 * Name of the group processor implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Controller\Jobs\Common\Customer\Import\Xml\Processor\Group\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the processor class name
	 * @since 2019.04
	 */

	 private array $map = [];


	/**
	 * Initializes the object
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 */
	public function __construct( \Aimeos\MShop\ContextIface $context )
	{
		parent::__construct( $context );

		$manager = \Aimeos\MShop::create( $context, 'group' );
		$filter = $manager->filter();
		$config = $context->config();

		/** controller/jobs/customer/import/xml/processor/group/allowed
		 * List of group codes that can be assigned to imported customers
		 *
		 * Only groups whose codes are part of this list are assigned to the
		 * customers during the import. If the list is empty, all groups are
		 * allowed except the ones from the "denied" setting.
		 *
		 * @param array List of group codes
		 * @since 2026.04
		 * @see controller/jobs/customer/import/xml/processor/group/denied
		 * @see controller/jobs/common/import/xml/processor/group/allowed
		 */
		// "common" settings are used by previous versions
		$allowed = $config->get( 'controller/jobs/customer/import/xml/processor/group/allowed', $config->get( 'controller/jobs/common/import/xml/processor/group/allowed',
			$config->get( 'controller/jobs/customer/import/csv/processor/group/allowed' ) ) );

		if( $allowed ) {
			$filter->add( 'group.code', '==', (array) $allowed );
		}

		/** controller/jobs/customer/import/xml/processor/group/denied
		 * List of group codes that must not be assigned to imported customers
		 *
		 * Groups whose codes are part of this list are never assigned to the
		 * customers during the import. By default, the "admin", "editor" and
		 * "super" groups are denied to prevent granting backend access by importing
		 * customer data. Existing members of the denied groups aren't updated by
		 * the import.
		 *
		 * @param array List of group codes
		 * @since 2026.04
		 * @see controller/jobs/customer/import/xml/processor/group/allowed
		 * @see controller/jobs/common/import/xml/processor/group/denied
		 */
		$denied = $config->get( 'controller/jobs/customer/import/xml/processor/group/denied', $config->get( 'controller/jobs/common/import/xml/processor/group/denied',
			$config->get( 'controller/jobs/customer/import/csv/processor/group/denied', ['admin', 'editor', 'super'] ) ) );

		if( $denied ) {
			$filter->add( 'group.code', '!=', (array) $denied );
		}

		$cursor = $manager->cursor( $filter );

		while( $items = $manager->iterate( $cursor ) )
		{
			foreach( $items as $item ) {
				$this->map[$item->getCode()] = $item->getId();
			}
		}
	}


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

		$ids = [];

		foreach( $node->childNodes as $node )
		{
			if( $node->nodeName !== 'groupitem' || ( $refattr = $node->attributes->getNamedItem( 'ref' ) ) === null ) {
				continue;
			}

			$code = \Aimeos\Base\Str::decode( $refattr->nodeValue );

			if( isset( $this->map[$code] ) ) {
				$ids[] = $this->map[$code];
			}
		}

		// @phpstan-ignore return.type
		return $item->setGroups( $ids );
	}
}
