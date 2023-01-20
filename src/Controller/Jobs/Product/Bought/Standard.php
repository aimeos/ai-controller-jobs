<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2014
 * @copyright Aimeos (aimeos.org), 2015-2023
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
	/** controller/jobs/product/bought/name
	 * Class name of the used product suggestions scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Product\Bought\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Product\Bought\Myalgorithm
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/product/bought/name = Myalgorithm
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyOptimizer"!
	 *
	 * @param string Last part of the class name
	 * @since 2014.03
	 */

	/** controller/jobs/product/bought/decorators/excludes
	 * Excludes decorators added by the "common" option from the product bought job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to remove a decorator added via
	 * "controller/jobs/common/decorators/default" before they are wrapped
	 * around the job controller.
	 *
	 *  controller/jobs/product/bought/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2014.03
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/product/bought/decorators/global
	 * @see controller/jobs/product/bought/decorators/local
	 */

	/** controller/jobs/product/bought/decorators/global
	 * Adds a list of globally available decorators only to the product bought job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/product/bought/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2014.03
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/product/bought/decorators/excludes
	 * @see controller/jobs/product/bought/decorators/local
	 */

	/** controller/jobs/product/bought/decorators/local
	 * Adds a list of local decorators only to the product bought job controller
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Product\Bought\Decorator\*") around the job
	 * controller.
	 *
	 *  controller/jobs/product/bought/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Product\Bought\Decorator\Decorator2"
	 * only to the job controller.
	 *
	 * @param array List of decorator names
	 * @since 2014.03
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/product/bought/decorators/excludes
	 * @see controller/jobs/product/bought/decorators/global
	 */


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
		if( empty( $total = $this->total() ) ) {
			return;
		}

		$start = 0;
		$size = $this->size();
		$counts = $this->counts();
		$ids = $counts->keys();

		while( !( $prodIds = $ids->slice( $start, $size ) )->isEmpty() )
		{
			$this->update( $counts, $prodIds, $total );
			$start += $size;
		}
	}


	/**
	 * Returns the minimum confidence value for high quality suggestions
	 *
	 * @return float Minimum confidence value from 0 to 1
	 */
	protected function confidence() : float
	{
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
		 * @see controller/jobs/product/bought/max-items
		 * @see controller/jobs/product/bought/min-support
		 * @see controller/jobs/product/bought/limit-days
		 * @see controller/jobs/product/bought/size
		 */
		return $this->context()->config()->get( 'controller/jobs/product/bought/min-confidence', 0.66 );
	}


	/**
	 * Returns how often the product has been bought
	 *
	 * @return \Aimeos\Map Map of product IDs as keys and count as values
	 */
	protected function counts() : \Aimeos\Map
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'order/product' );
		$filter = $manager->filter()->add( 'order.product.ctime', '>', $this->ctime() )->slice( 0, 0x7fffffff );

		return $manager->aggregate( $filter, 'order.product.productid' );
	}


	/**
	 * Returns the date of the oldest ordered product to use
	 *
	 * @return string Date of the oldest ordered product to use
	 */
	protected function ctime() : string
	{
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
		 * @see controller/jobs/product/bought/max-items
		 * @see controller/jobs/product/bought/min-support
		 * @see controller/jobs/product/bought/min-confidence
		 * @see controller/jobs/product/bought/size
		 */
		$days = $this->context()->config()->get( 'controller/jobs/product/bought/limit-days', 360 );
		return date( 'Y-m-d H:i:s', time() - $days * 86400 );
	}


	/**
	 * Returns the domain names to fetch for each product
	 *
	 * @return array Domain names to fetch for each product
	 */
	protected function domains() : array
	{
		return $this->context()->config()->get( 'mshop/product/manager/domains', [] ) + ['product'];
	}


	/**
	 * Returns the maximum number of suggested items per product
	 *
	 * @return int Number of suggested products
	 */
	protected function max() : int
	{
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
		 * @see controller/jobs/product/bought/min-support
		 * @see controller/jobs/product/bought/min-confidence
		 * @see controller/jobs/product/bought/limit-days
		 * @see controller/jobs/product/bought/size
		 */
		return $this->context()->config()->get( 'controller/jobs/product/bought/max-items', 5 );
	}


	/**
	 * Returns the relative counts for the given product IDs
	 *
	 * @param string $id Product ID to calculate the suggestions for
	 * @param iterable $prodIds List of product IDs to create suggestions for
	 * @return \Aimeos\Map Map with the product IDs as keys and the relative counts as values
	 */
	protected function relative( string $id, iterable $prodIds ) : \Aimeos\Map
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'order/product' );

		$search = $manager->filter();
		$search->add( $search->and( [
			$search->is( 'order.product.productid', '==', $prodIds ),
			$search->is( 'order.product.ctime', '>', $this->ctime() ),
			$search->is( $search->make( 'order.product:count', [$id] ), '==', 1 ),
		] ) );

		return $manager->aggregate( $search, 'order.product.productid' )->remove( $id );
	}


	/**
	 * Returns the number of items processed at once
	 *
	 * @return int Number of items processed at once
	 */
	protected function size() : int
	{
		/** controller/jobs/product/bought/size
		 * Number of items processed at once
		 *
		 * The items which are bought together are processed in batches to reduce
		 * the time needed for associating all items. Higher numbers can improve
		 * the speed while requiring more memory.
		 *
		 * @param integer Number of items processed at once
		 * @since 2023.01
		 * @see controller/jobs/product/bought/max-items
		 * @see controller/jobs/product/bought/min-support
		 * @see controller/jobs/product/bought/min-confidence
		 * @see controller/jobs/product/bought/limit-days
		 */
		return $this->context()->config()->get( 'controller/jobs/product/bought/size', 100 );
	}


	/**
	 * Returns the IDs of the suggested products.
	 *
	 * @param string $id Product ID to calculate the suggestions for
	 * @param iterable $prodIds List of product IDs to create suggestions for
	 * @param int $count Number of ordered products
	 * @param int $total Total number of orders
	 * @return \Aimeos\Map List of suggested product IDs
	 */
	protected function suggest( string $id, iterable $prodIds, int $count, int $total ) : \Aimeos\Map
	{
		$products = [];
		$supportA = $count / $total;

		$minSupport = $this->support();
		$minConfidence = $this->confidence();

		foreach( $this->relative( $id, $prodIds ) as $prodId => $relCnt )
		{
			$supportAB = $relCnt / $total;

			if( $supportAB > $minSupport && ( $conf = ( $supportAB / $supportA ) ) > $minConfidence ) {
				$products[$prodId] = $conf;
			}
		}

		return map( $products )->arsort()->keys();
	}


	/**
	 * Returns the minimum support value to sort out irrelevant combinations
	 *
	 * @return float Minimum support value from 0 to 1
	 */
	protected function support() : float
	{
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
		 * @see controller/jobs/product/bought/max-items
		 * @see controller/jobs/product/bought/min-confidence
		 * @see controller/jobs/product/bought/limit-days
		 * @see controller/jobs/product/bought/size
		 */
		return $this->context()->config()->get( 'controller/jobs/product/bought/min-support', 0.02 );
	}


	/**
	 * Returns the total number of orders available
	 *
	 * @return int Total number of orders
	 */
	protected function total() : int
	{
		$total = 0;

		$manager = \Aimeos\MShop::create( $this->context(), 'order' );
		$filter = $manager->filter()->add( 'order.ctime', '>', $this->ctime() )->slice( 0, 0 );
		$manager->search( $filter, [], $total )->all();

		return $total;
	}


	/**
	 * Updates the products bought together for the given item
	 *
	 * @param \Aimeos\Map $counts Map of product IDs as keys and count as values
	 * @param iterable $prodIds List of product IDs to create suggestions for
	 * @param int $total Total number of orders
	 */
	protected function update( iterable $counts, iterable $prodIds, int $total )
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'product' );
		$filter = $manager->filter()->add( 'product.id', '==', $prodIds )->slice( 0, 0x7fffffff );
		$products = $manager->search( $filter, $this->domains() );

		foreach( $counts as $id => $count )
		{
			if( $item = $products->get( $id ) )
			{
				$listItems = $item->getListItems( 'product', 'bought-together' );

				if( $count / $total > $this->support() )
				{
					$productIds = $this->suggest( $id, $prodIds, $count, $total )->slice( 0, $this->max() );

					foreach( $productIds as $pid )
					{
						$litem = $item->getListItem( 'product', 'bought-together', $pid, false ) ?: $manager->createListItem();
						$item->addListItem( 'product', $litem->setType( 'bought-together' )->setRefId( $pid ) );
						$listItems->remove( $litem->getId() );
					}
				}

				$item->deleteListItems( $listItems );
			}
		}

		$manager->save( $products );
	}
}
