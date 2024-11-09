<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2024
 * @package Controller
 * @subpackage Order
 */


namespace Aimeos\Controller\Jobs\Order\Email\Voucher;


/**
 * Order voucher e-mail job controller.
 *
 * @package Controller
 * @subpackage Order
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	/** controller/jobs/order/email/voucher/name
	 * Class name of the used order email voucher scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Order\Email\Voucher\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Order\Email\Voucher\Myvoucher
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/order/email/voucher/name = Myvoucher
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyVoucher"!
	 *
	 * @param string Last part of the class name
	 * @since 2014.03
	 */

	/** controller/jobs/order/email/voucher/decorators/excludes
	 * Excludes decorators added by the "common" option from the order email voucher controllers
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
	 *  controller/jobs/order/email/voucher/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/email/voucher/decorators/global
	 * @see controller/jobs/order/email/voucher/decorators/local
	 */

	/** controller/jobs/order/email/voucher/decorators/global
	 * Adds a list of globally available decorators only to the order email voucher controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/order/email/voucher/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/email/voucher/decorators/excludes
	 * @see controller/jobs/order/email/voucher/decorators/local
	 */

	/** controller/jobs/order/email/voucher/decorators/local
	 * Adds a list of local decorators only to the order email voucher controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Order\Email\Voucher\Decorator\*") around this job controller.
	 *
	 *  controller/jobs/order/email/voucher/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Order\Email\Voucher\Decorator\Decorator2" only to this job
	 * controller.
	 *
	 * @param array List of decorator names
	 * @since 2015.09
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/order/email/voucher/decorators/excludes
	 * @see controller/jobs/order/email/voucher/decorators/global
	 */


	use \Aimeos\Controller\Jobs\Mail;


	private ?string $couponId = null;


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Voucher related e-mails' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Sends the e-mail with the voucher to the customer' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->context();
		$manager = \Aimeos\MShop::create( $context, 'order' );

		$filter = $this->filter( $manager->filter() );
		$cursor = $manager->cursor( $filter );

		while( $items = $manager->iterate( $cursor, ['order/address', 'order/product'] ) ) {
			$this->notify( $items );
		}
	}


	/**
	 * Returns the delivery address item of the order
	 *
	 * @param \Aimeos\MShop\Order\Item\Iface $orderBaseItem Order including address items
	 * @return \Aimeos\MShop\Order\Item\Address\Iface Delivery or voucher address item
	 * @throws \Aimeos\Controller\Jobs\Exception If no address item is available
	 */
	protected function address( \Aimeos\MShop\Order\Item\Iface $orderBaseItem ) : \Aimeos\MShop\Order\Item\Address\Iface
	{
		$type = \Aimeos\MShop\Order\Item\Address\Base::TYPE_DELIVERY;
		if( ( $addr = current( $orderBaseItem->getAddress( $type ) ) ) !== false && $addr->getEmail() !== '' ) {
			return $addr;
		}

		$type = \Aimeos\MShop\Order\Item\Address\Base::TYPE_PAYMENT;
		if( ( $addr = current( $orderBaseItem->getAddress( $type ) ) ) !== false && $addr->getEmail() !== '' ) {
			return $addr;
		}

		$msg = sprintf( 'No address with e-mail found in order base with ID "%1$s"', $orderBaseItem->getId() );
		throw new \Aimeos\Controller\Jobs\Exception( $msg );
	}


	/**
	 * Creates coupon codes for the bought vouchers
	 *
	 * @param \Aimeos\Map $orderProdItems Complete order including addresses, products, services
	 */
	protected function createCoupons( \Aimeos\Map $orderProdItems )
	{
		$map = [];
		$manager = \Aimeos\MShop::create( $this->context(), 'order/product/attribute' );

		foreach( $orderProdItems as $orderProductItem )
		{
			if( $orderProductItem->getAttribute( 'coupon-code', 'coupon' ) ) {
				continue;
			}

			$codes = [];

			for( $i = 0; $i < $orderProductItem->getQuantity(); $i++ )
			{
				$str = $i . getmypid() . microtime( true ) . $orderProductItem->getId();
				$code = substr( strtoupper( sha1( $str ) ), -8 );
				$map[$code] = $orderProductItem->getId();
				$codes[] = $code;
			}

			$item = $manager->create()->setCode( 'coupon-code' )->setType( 'coupon' )->setValue( $codes );
			$orderProductItem->setAttributeItem( $item );
		}

		$this->saveCoupons( $map );
		return $orderProdItems;
	}


	/**
	 * Returns the coupon ID for the voucher coupon
	 *
	 * @return string Unique ID of the coupon item
	 */
	protected function couponId() : string
	{
		if( !isset( $this->couponId ) )
		{
			$manager = \Aimeos\MShop::create( $this->context(), 'coupon' );
			$filter = $manager->filter()->add( 'coupon.provider', '=~', 'Voucher' )->slice( 0, 1 );

			if( ( $item = $manager->search( $filter )->first() ) === null ) {
				throw new \Aimeos\Controller\Jobs\Exception( 'No coupon provider "Voucher" available' );
			}

			$this->couponId = $item->getId();
		}

		return $this->couponId;
	}


	/**
	 * Returns the PDF file name
	 *
	 * @param string $code Voucher code
	 * @return string PDF file name
	 */
	protected function filename( string $code ) : string
	{
		return $this->context()->translate( 'controller/jobs', 'Voucher' ) . '-' . $code . '.pdf';
	}


	/**
	 * Returns the filter for searching the appropriate orders
	 *
	 * @param \Aimeos\Base\Criteria\Iface $filter Order filter object
	 * @return \Aimeos\Base\Criteria\Iface Filter object with conditions set
	 */
	protected function filter( \Aimeos\Base\Criteria\Iface $filter ) : \Aimeos\Base\Criteria\Iface
	{
		$limitDate = date( 'Y-m-d H:i:s', time() - $this->limit() * 86400 );

		$filter->add( $filter->and( [
			$filter->compare( '>=', 'order.mtime', $limitDate ),
			$filter->compare( '==', 'order.statuspayment', $this->status() ),
			$filter->compare( '==', 'order.product.type', 'voucher' ),
			$filter->compare( '==', $filter->make( 'order:status', [$this->type(), '1'] ), 0 ),
		] ) );


		return $filter;
	}


	/**
	 * Returns the number of days after no e-mail will be sent anymore
	 *
	 * @return int Number of days
	 */
	protected function limit() : int
	{
		/** controller/jobs/order/email/voucher/limit-days
		 * Only send voucher e-mails of orders that were created in the past within the configured number of days
		 *
		 * The voucher e-mails are normally send immediately after the voucher
		 * status has changed. This option prevents e-mails for old order from
		 * being send in case anything went wrong or an update failed to avoid
		 * confusion of customers.
		 *
		 * @param integer Number of days
		 * @since 2014.03
		 * @see controller/jobs/order/email/delivery/limit-days
		 * @see controller/jobs/service/delivery/process/limit-days
		 */
		return (int) $this->context()->config()->get( 'controller/jobs/order/email/voucher/limit-days', 30 );
	}


	/**
	 * Sends the voucher e-mail for the given orders
	 *
	 * @param \Aimeos\Map $items List of order items implementing \Aimeos\MShop\Order\Item\Iface with their IDs as keys
	 */
	protected function notify( \Aimeos\Map $items )
	{
		$context = $this->context();
		$sites = $this->sites( $items->getSiteId()->unique() );

		$couponManager = \Aimeos\MShop::create( $context, 'coupon' );
		$orderProdManager = \Aimeos\MShop::create( $context, 'order/product' );

		foreach( $items as $id => $item )
		{
			$couponManager->begin();
			$orderProdManager->begin();

			try
			{
				$products = $this->products( $item );
				$orderProdManager->save( $this->createCoupons( $products ) );

				$addr = $this->address( $item );
				$context->locale()->setLanguageId( $addr->getLanguageId() );

				$list = $sites->get( $item->getSiteId(), map() );
				$view = $this->view( $item, $list->getTheme()->filter()->last() );

				$this->send( $view, $products, $addr, $list->getLogo()->filter()->last() );
				$this->update( $id );

				$orderProdManager->commit();
				$couponManager->commit();

				$str = sprintf( 'Sent voucher e-mails for order ID "%1$s"', $item->getId() );
				$context->logger()->info( $str, 'email/order/voucher' );
			}
			catch( \Exception $e )
			{
				$orderProdManager->rollback();
				$couponManager->rollback();

				$str = 'Error while trying to send voucher e-mails for order ID "%1$s": %2$s';
				$msg = sprintf( $str, $item->getId(), $e->getMessage() . PHP_EOL . $e->getTraceAsString() );
				$context->logger()->info( $msg, 'email/order/voucher' );
			}
		}
	}


	/**
	 * Returns the generated PDF file for the order
	 *
	 * @param \Aimeos\Base\View\Iface $view View object with address and order item assigned
	 * @return string|null PDF content or NULL for no PDF file
	 */
	protected function pdf( \Aimeos\Base\View\Iface $view ) : ?string
	{
		$config = $this->context()->config();

		/** controller/jobs/order/email/voucher/pdf
		 * Enables attaching a PDF to the voucher e-mail
		 *
		 * The voucher PDF contains the same information like the HTML e-mail.
		 *
		 * @param bool TRUE to enable attaching the PDF, FALSE to skip the PDF
		 * @since 2022.10
		 */
		if( !$config->get( 'controller/jobs/order/email/voucher/pdf', true ) ) {
			return null;
		}

		$pdf = new class( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false ) extends \TCPDF {
			private ?\Closure $headerFcn = null;
			private ?\Closure $footerFcn = null;

			public function Footer() { return ( $fcn = $this->footerFcn ) ? $fcn( $this ) : null; }
			public function Header() { return ( $fcn = $this->headerFcn ) ? $fcn( $this ) : null; }
			public function setFooterFunction( \Closure $fcn ) { $this->footerFcn = $fcn; }
			public function setHeaderFunction( \Closure $fcn ) { $this->headerFcn = $fcn; }
		};
		$pdf->setCreator( PDF_CREATOR );
		$pdf->setAuthor( 'Aimeos' );

		/** controller/jobs/order/email/voucher/template-pdf
		 * Relative path to the template for the PDF part of the voucher emails.
		 *
		 * The template file contains the text and processing instructions
		 * to generate the result shown in the body of the frontend. The
		 * configuration string is the path to the template file relative
		 * to the templates directory (usually in templates/controller/jobs).
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates.
		 *
		 * @param string Relative path to the template
		 * @since 2022.10
		 * @see controller/jobs/order/email/voucher/template-html
		 * @see controller/jobs/order/email/voucher/template-text
		 */
		$template = $config->get( 'controller/jobs/order/email/voucher/template-pdf', 'order/email/voucher/pdf' );

		// Generate HTML before creating first PDF page to include header added in template
		$content = $view->set( 'pdf', $pdf )->render( $template );

		$pdf->addPage();
		$pdf->writeHtml( $content );
		$pdf->lastPage();

		return $pdf->output( '', 'S' );
	}


	/**
	 * Returns the ordered voucher products from the basket.
	 *
	 * @param \Aimeos\MShop\Order\Item\Iface $orderBaseItem Basket object
	 * @return \Aimeos\Map List of order product items for the voucher products
	 */
	protected function products( \Aimeos\MShop\Order\Item\Iface $orderBaseItem ) : \Aimeos\Map
	{
		$list = [];

		foreach( $orderBaseItem->getProducts() as $orderProductItem )
		{
			if( $orderProductItem->getType() === 'voucher' ) {
				$list[] = $orderProductItem;
			}

			foreach( $orderProductItem->getProducts() as $subProductItem )
			{
				if( $subProductItem->getType() === 'voucher' ) {
					$list[] = $subProductItem;
				}
			}
		}

		return map( $list );
	}


	/**
	 * Saves the given coupon codes
	 *
	 * @param array $map Associative list of coupon codes as keys and reference Ids as values
	 */
	protected function saveCoupons( array $map )
	{
		$couponId = $this->couponId();
		$manager = \Aimeos\MShop::create( $this->context(), 'coupon/code' );

		foreach( $map as $code => $ref )
		{
			$item = $manager->create()->setParentId( $couponId )
				->setCode( $code )->setRef( $ref )->setCount( null ); // unlimited

			$manager->save( $item );
		}
	}


	/**
	 * Sends the voucher related e-mail for a single order
	 *
	 * @param \Aimeos\Base\View\Iface $view Populated view object
	 * @param \Aimeos\Map $orderProducts List of ordered voucher products
	 * @param \Aimeos\MShop\Common\Item\Address\Iface $address Address item
	 * @param string|null $logoPath Relative path to the logo in the fs-media file system
	 */
	protected function send( \Aimeos\Base\View\Iface $view, \Aimeos\Map $orderProducts,
		\Aimeos\MShop\Common\Item\Address\Iface $address, ?string $logoPath = null )
	{
		/** controller/jobs/order/email/voucher/template-html
		 * Relative path to the template for the HTML part of the voucher emails.
		 *
		 * The template file contains the HTML code and processing instructions
		 * to generate the result shown in the body of the frontend. The
		 * configuration string is the path to the template file relative
		 * to the templates directory (usually in templates/controller/jobs).
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates.
		 *
		 * @param string Relative path to the template
		 * @since 2022.04
		 * @see controller/jobs/order/email/voucher/template-text
		 */

		/** controller/jobs/order/email/voucher/template-text
		 * Relative path to the template for the text part of the voucher emails.
		 *
		 * The template file contains the text and processing instructions
		 * to generate the result shown in the body of the frontend. The
		 * configuration string is the path to the template file relative
		 * to the templates directory (usually in templates/controller/jobs).
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates.
		 *
		 * @param string Relative path to the template
		 * @since 2022.04
		 * @see controller/jobs/order/email/voucher/template-html
		 */

		$context = $this->context();
		$config = $context->config();
		$logo = $this->call( 'mailLogo', $logoPath );
		$view->orderAddressItem = $address;

		foreach( $orderProducts as $orderProductItem )
		{
			if( !empty( $codes = $orderProductItem->getAttribute( 'coupon-code', 'coupon' ) ) )
			{
				foreach( (array) $codes as $code )
				{
					$view->orderProductItem = $orderProductItem;
					$view->voucher = $code;

					$msg = $this->call( 'mailTo', $address );
					$view->logo = $msg->embed( $logo, basename( (string) $logoPath ) );

					$msg->subject( $context->translate( 'controller/jobs', 'Your voucher' ) )
						->html( $view->render( $config->get( 'controller/jobs/order/email/voucher/template-html', 'order/email/voucher/html' ) ) )
						->text( $view->render( $config->get( 'controller/jobs/order/email/voucher/template-text', 'order/email/voucher/text' ) ) )
						->attach( $this->pdf( $view ), $this->call( 'filename', $code ), 'application/pdf' )
						->send();
				}
			}
		}
	}


	/**
	 * Returns the site items for the given site codes
	 *
	 * @param iterable $siteIds List of site IDs
	 * @return \Aimeos\Map Site items with codes as keys
	 */
	protected function sites( iterable $siteIds ) : \Aimeos\Map
	{
		$map = [];
		$manager = \Aimeos\MShop::create( $this->context(), 'locale/site' );

		foreach( $siteIds as $siteId )
		{
			$list = explode( '.', trim( $siteId, '.' ) );
			$map[$siteId] = $manager->getPath( end( $list ) );
		}

		return map( $map );
	}


	/**
	 * Returns the payment status for which the e-mails should be sent
	 *
	 * @return int Payment status
	 */
	protected function status() : int
	{
		/** controller/jobs/order/email/voucher/status
		 * Only send e-mails containing voucher for these payment status values
		 *
		 * E-mail containing vouchers can be sent for these payment status values:
		 *
		 * * 0: deleted
		 * * 1: canceled
		 * * 2: refused
		 * * 3: refund
		 * * 4: pending
		 * * 5: authorized
		 * * 6: received
		 *
		 * @param integer Payment status constant
		 * @since 2018.07
		 * @see controller/jobs/order/email/voucher/limit-days
		 */
		return (int) $this->context()->config()->get( 'controller/jobs/order/email/voucher/status', \Aimeos\MShop\Order\Item\Base::PAY_RECEIVED );
	}


	/**
	 * Returns the status type for filtering the orders
	 *
	 * @return string Status type
	 */
	protected function type() : string
	{
		return \Aimeos\MShop\Order\Item\Status\Base::EMAIL_VOUCHER;
	}


	/**
	 * Adds the status of the delivered e-mail for the given order ID
	 *
	 * @param string $orderId Unique order ID
	 */
	protected function update( string $orderId )
	{
		$orderStatusManager = \Aimeos\MShop::create( $this->context(), 'order/status' );

		$statusItem = $orderStatusManager->create()->setParentId( $orderId )->setValue( 1 )
			->setType( \Aimeos\MShop\Order\Item\Status\Base::EMAIL_VOUCHER );

		$orderStatusManager->save( $statusItem );
	}


	/**
	 * Returns the view populated with common data
	 *
	 * @param \Aimeos\MShop\Order\Item\Iface $base Basket including addresses
	 * @param string|null $theme Theme name
	 * @return \Aimeos\Base\View\Iface View object
	 */
	protected function view( \Aimeos\MShop\Order\Item\Iface $base, ?string $theme = null ) : \Aimeos\Base\View\Iface
	{
		$address = $this->address( $base );
		$langId = $address->getLanguageId() ?: $base->locale()->getLanguageId();

		$view = $this->call( 'mailView', $langId );
		$view->intro = $this->call( 'mailIntro', $address );
		$view->css = $this->call( 'mailCss', $theme );
		$view->address = $address;
		$view->urlparams = [
			'currency' => $base->getPrice()->getCurrencyId(),
			'site' => $base->getSiteCode(),
			'locale' => $langId,
		];

		return $view;
	}
}
