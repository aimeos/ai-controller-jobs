<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2022
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Product\Bought;


/**
 * Job controller for bought together products.
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Products bought together' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Creates bought together product suggestions' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->context();
		$config = $context->config();


		/** controller/jobs/product/bought/max-items
		 * Maximum number of suggested items per product
		 *
		 * Each product can contain zero or more suggested products based on
		 * the used algorithm. The maximum number of items limits the quantity
		 * of products that are associated as suggestions to one product.
		 * Usually, you don't need more products than shown in the product
		 * detail view as suggested products.
		 *
		 * @param integer Number of suggested products
		 * @since 2014.09
		 * @category Developer
		 * @category User
		 * @see controller/jobs/product/bought/min-support
		 * @see controller/jobs/product/bought/min-confidence
		 * @see controller/jobs/product/bought/limit-days
		 */
		$maxItems = $config->get( 'controller/jobs/product/bought/max-items', 5 );

		/** controller/jobs/product/bought/min-support
		 * Minimum support value to sort out all irrelevant combinations
		 *
		 * A minimum support value of 0.02 requires the combination of two
		 * products to be in at least 2% of all orders to be considered relevant
		 * enough as product suggestion.
		 *
		 * You can tune this value for your needs, e.g. if you sell several
		 * thousands different products and you have only a few suggestions for
		 * all products, a lower value might work better for you. The other way
		 * round, if you sell less than thousand different products, you may
		 * have a lot of product suggestions of low quality. In this case it's
		 * better to increase this value, e.g. to 0.05 or higher.
		 *
		 * Caution: Decreasing the support to lower values than 0.01 exponentially
		 * increases the time for generating the suggestions. If your database
		 * contains a lot of orders, the time to complete the job may rise from
		 * hours to days!
		 *
		 * @param float Minimum support value from 0 to 1
		 * @since 2014.09
		 * @category Developer
		 * @category User
		 * @see controller/jobs/product/bought/max-items
		 * @see controller/jobs/product/bought/min-confidence
		 * @see controller/jobs/product/bought/limit-days
		 */
		$minSupport = $config->get( 'controller/jobs/product/bought/min-support', 0.02 );

		/** controller/jobs/product/bought/min-confidence
		 * Minimum confidence value for high quality suggestions
		 *
		 * The confidence value is used to remove low quality suggestions. Using
		 * a confidence value of 0.95 would only suggest product combinations
		 * that are almost always bought together. Contrary, a value of 0.1 would
		 * yield a lot of combinations that are bought together only in very rare
		 * cases.
		 *
		 * To get good product suggestions, the value should be at least above
		 * 0.5 and the higher the value, the better the suggestions. You can
		 * either increase the default value to get better suggestions or lower
		 * the value to get more suggestions per product if you have only a few
		 * ones in total.
		 *
		 * @param float Minimum confidence value from 0 to 1
		 * @since 2014.09
		 * @category Developer
		 * @category User
		 * @see controller/jobs/product/bought/max-items
		 * @see controller/jobs/product/bought/min-support
		 * @see controller/jobs/product/bought/limit-days
		 */
		$minConfidence = $config->get( 'controller/jobs/product/bought/min-confidence', 0.66 );

		/** controller/jobs/product/bought/limit-days
		 * Only use orders placed in the past within the configured number of days for calculating bought together products
		 *
		 * This option limits the orders that are evaluated for calculating the
		 * bought together products. Only ordered products that were bought by
		 * customers within the configured number of days are used.
		 *
		 * Limiting the orders taken into account to the last ones increases the
		 * quality of suggestions if customer interests shifts to new products.
		 * If you only have a few orders per month, you can also increase this
		 * value to several years to get enough suggestions. Please keep in mind
		 * that the more orders are evaluated, the longer the it takes to
		 * calculate the product combinations.
		 *
		 * @param integer Number of days
		 * @since 2014.09
		 * @category User
		 * @category Developer
		 * @see controller/jobs/product/bought/max-items
		 * @see controller/jobs/product/bought/min-support
		 * @see controller/jobs/product/bought/min-confidence
		 */
		$days = $config->get( 'controller/jobs/product/bought/limit-days', 180 );
		$date = date( 'Y-m-d H:i:s', time() - $days * 86400 );

		$domains = [
			'attribute', 'catalog', 'media', 'media/property', 'price',
			'product', 'product/property', 'supplier', 'text'
		];


		$manager = \Aimeos\MShop::create( $context, 'product' );
		$baseManager = \Aimeos\MShop::create( $context, 'order/base' );
		$baseProductManager = \Aimeos\MShop::create( $context, 'order/base/product' );

		$search = $baseProductManager->filter()->add( 'order.base.product.ctime', '>', $date );
		$filter = $baseManager->filter()->add( 'order.base.ctime', '>', $date )->slice( 0, 0 );

		$start = $total = 0;
		$baseManager->search( $filter, [], $total )->all();

		if( !$total ) {
			return;
		}

		do
		{
			$counts = $baseProductManager->aggregate( $search, 'order.base.product.productid' );
			$prodIds = $counts->keys()->all();
			$products = $manager->search( $manager->filter()->add( 'product.id', '==', $prodIds ), $domains );

			foreach( $counts as $id => $count )
			{
				if( ( $item = $products->get( $id ) ) === null ) {
					continue;
				}

				$listItems = $item->getListItems( 'product', 'bought-together' );

				if( $count / $total > $minSupport )
				{
					$productIds = $this->getSuggestions( $id, $prodIds, $count, $total, $maxItems,
						$minSupport, $minConfidence, $date );

					foreach( $productIds as $pid )
					{
						$litem = $item->getListItem( 'product', 'bought-together', $pid ) ?: $manager->createListItem();
						$item->addListItem( 'product', $litem->setRefId( $pid ) );
						$listItems->remove( $litem->getId() );
					}
				}

				$item->deleteListItems( $listItems );
			}

			$manager->save( $products );

			$count = count( $counts );
			$start += $count;
			$search->slice( $start );
		}
		while( $count >= $search->getLimit() );
	}


	/**
	 * Returns the IDs of the suggested products.
	 *
	 * @param string $id Product ID to calculate the suggestions for
	 * @param string[] $prodIds List of product IDs to create suggestions for
	 * @param int $count Number of ordered products
	 * @param int $total Total number of orders
	 * @param int $maxItems Maximum number of suggestions
	 * @param float $minSupport Minium support value for calculating the suggested products
	 * @param float $minConfidence Minium confidence value for calculating the suggested products
	 * @param string $date Date in YYYY-MM-DD HH:mm:ss format after which orders should be used for calculations
	 * @return \Aimeos\Map List of suggested product IDs
	 */
	protected function getSuggestions( string $id, array $prodIds, int $count, int $total, int $maxItems,
		float $minSupport, float $minConfidence, string $date ) : \Aimeos\Map
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'order/base/product' );

		$search = $manager->filter();
		$search->add( $search->and( [
			$search->is( 'order.base.product.productid', '==', $prodIds ),
			$search->is( 'order.base.product.ctime', '>', $date ),
			$search->is( $search->make( 'order.base.product.count', [(string) $id] ), '==', 1 ),
		] ) );
		$relativeCounts = $manager->aggregate( $search, 'order.base.product.productid' );


		unset( $relativeCounts[$id] );
		$supportA = $count / $total;
		$products = [];

		foreach( $relativeCounts as $prodId => $relCnt )
		{
			$supportAB = $relCnt / $total;

			if( $supportAB > $minSupport && ( $conf = ( $supportAB / $supportA ) ) > $minConfidence ) {
				$products[$prodId] = $conf;
			}
		}

		return map( $products )->arsort()->slice( 0, $maxItems, true )->keys();
	}
}
