<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2022
 * @package Controller
 * @subpackage Order
 */


namespace Aimeos\Controller\Jobs\Order\Email\Payment;


/**
 * Order payment e-mail job controller.
 *
 * @package Controller
 * @subpackage Order
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	use \Aimeos\Controller\Jobs\Mail;


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Order payment related e-mails' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Sends order confirmation or payment status update e-mails' );
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

		$orderManager = \Aimeos\MShop::create( $context, 'order' );

		/** controller/jobs/order/email/payment/limit-days
		 * Only send payment e-mails of orders that were created in the past within the configured number of days
		 *
		 * The payment e-mails are normally send immediately after the payment
		 * status has changed. This option prevents e-mails for old order from
		 * being send in case anything went wrong or an update failed to avoid
		 * confusion of customers.
		 *
		 * @param integer Number of days
		 * @since 2014.03
		 * @category User
		 * @category Developer
		 * @see controller/jobs/order/email/delivery/limit-days
		 * @see controller/jobs/service/delivery/process/limit-days
		 */
		$limit = $config->get( 'controller/jobs/order/email/payment/limit-days', 30 );
		$limitDate = date( 'Y-m-d H:i:s', time() - $limit * 86400 );

		$default = [
			\Aimeos\MShop\Order\Item\Base::PAY_REFUND,
			\Aimeos\MShop\Order\Item\Base::PAY_PENDING,
			\Aimeos\MShop\Order\Item\Base::PAY_AUTHORIZED,
			\Aimeos\MShop\Order\Item\Base::PAY_RECEIVED,
		];

		/** controller/jobs/order/email/payment/status
		 * Only send order payment notification e-mails for these payment status values
		 *
		 * Notification e-mail about payment status changes can be sent for these
		 * status values:
		 *
		 * * 0: deleted
		 * * 1: canceled
		 * * 2: refused
		 * * 3: refund
		 * * 4: pending
		 * * 5: authorized
		 * * 6: received
		 * * 7: transferred
		 *
		 * User-defined status values are possible but should be in the private
		 * block of values between 30000 and 32767.
		 *
		 * @param integer Payment status constant
		 * @since 2014.03
		 * @category User
		 * @category Developer
		 * @see controller/jobs/order/email/delivery/status
		 * @see controller/jobs/order/email/payment/limit-days
		 */
		foreach( (array) $config->get( 'controller/jobs/order/email/payment/status', $default ) as $status )
		{
			$param = [\Aimeos\MShop\Order\Item\Status\Base::EMAIL_PAYMENT, (string) $status];
			$filter = $orderManager->filter();
			$filter->add( $filter->and( [
				$filter->compare( '>=', 'order.mtime', $limitDate ),
				$filter->compare( '==', 'order.statuspayment', $status ),
				$filter->compare( '==', $filter->make( 'order:status', $param ), 0 ),
			] ) );

			$start = 0;
			$domains = ['order/base', 'order/base/address', 'order/base/product', 'order/base/service'];

			do
			{
				$items = $orderManager->search( $filter->slice( $start ), $domains );

				$this->notify( $items, $status );

				$count = count( $items );
				$start += $count;
			}
			while( $count >= $filter->getLimit() );
		}
	}


	/**
	 * Returns the address item from the order
	 *
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $basket Order including address items
	 * @return \Aimeos\MShop\Common\Item\Address\Iface Address item
	 * @throws \Aimeos\Controller\Jobs\Exception If no suitable address item is available
	 */
	protected function address( \Aimeos\MShop\Order\Item\Base\Iface $basket ) : \Aimeos\MShop\Common\Item\Address\Iface
	{
		if( ( $addr = current( $basket->getAddress( 'payment' ) ) ) !== false && $addr->getEmail() ) {
			return $addr;
		};

		$msg = sprintf( 'No address with e-mail found in order base with ID "%1$s"', $basket->getId() );
		throw new \Aimeos\Controller\Jobs\Exception( $msg );
	}


	/**
	 * Adds the given list of files as attachments to the mail message object
	 *
	 * @param \Aimeos\Base\Mail\Message\Iface $msg Mail message
	 * @param array $files List of absolute file paths
	 */
	protected function attachments( \Aimeos\Base\Mail\Message\Iface $msg ) : \Aimeos\Base\Mail\Message\Iface
	{
		$context = $this->context();
		$fs = $context->fs();

		/** controller/jobs/order/email/payment/attachments
		 * List of file paths whose content should be attached to all payment e-mails
		 *
		 * This configuration option allows you to add files to the e-mails that are
		 * sent to the customer when the payment status changes, e.g. for the order
		 * confirmation e-mail. These files can't be customer specific.
		 *
		 * @param array List of absolute file paths
		 * @since 2016.10
		 * @see controller/jobs/order/email/delivery/attachments
		 */
		$files = $context->config()->get( 'controller/jobs/order/email/payment/attachments', [] );

		foreach( $files as $filepath )
		{
			if( $fs->has( $filepath ) ) {
				$msg->attach( $fs->read( $filepath ), basename( $filepath ) );
			}
		}

		return $msg;
	}


	/**
	 * Sends the payment e-mail for the given orders
	 *
	 * @param \Aimeos\Map $items List of order items implementing \Aimeos\MShop\Order\Item\Iface with their IDs as keys
	 * @param int $status Delivery status value
	 */
	protected function notify( \Aimeos\Map $items, int $status )
	{
		$context = $this->context();
		$sites = $this->sites( $items->getBaseItem()->getSiteId()->unique() );

		foreach( $items as $id => $item )
		{
			try
			{
				$list = $sites->get( $item->getBaseItem()->getSiteId(), map() );

				$this->send( $item, $list->getTheme()->filter()->last(), $list->getLogo()->filter()->last() );
				$this->status( $id, $status );

				$str = sprintf( 'Sent order payment e-mail for order "%1$s" and status "%2$s"', $item->getId(), $status );
				$context->logger()->info( $str, 'email/order/payment' );
			}
			catch( \Exception $e )
			{
				$str = 'Error while trying to send payment e-mail for order ID "%1$s" and status "%2$s": %3$s';
				$msg = sprintf( $str, $item->getId(), $item->getStatusPayment(), $e->getMessage() );
				$context->logger()->error( $msg . PHP_EOL . $e->getTraceAsString(), 'email/order/payment' );
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

		/** controller/jobs/order/email/payment/pdf
		 * Enables attaching the order confirmation PDF to the payment e-mail
		 *
		 * The order confirmation PDF contains the same information like the
		 * HTML e-mail and can be also used as invoice if possible.
		 *
		 * @param bool TRUE to enable attaching the PDF, FALSE to skip the PDF
		 * @since 2022.04
		 */
		if( !$config->get( 'controller/jobs/order/email/payment/pdf', true ) ) {
			return null;
		}

		$pdf = new class( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false ) extends \TCPDF {
			private $headerFcn;
			private $footerFcn;

			public function Footer() { return ( $fcn = $this->footerFcn ) ? $fcn( $this ) : null; }
			public function Header() { return ( $fcn = $this->headerFcn ) ? $fcn( $this ) : null; }
			public function setFooterFunction( \Closure $fcn ) { $this->footerFcn = $fcn; }
			public function setHeaderFunction( \Closure $fcn ) { $this->headerFcn = $fcn; }
		};
		$pdf->setCreator( PDF_CREATOR );
		$pdf->setAuthor( 'Aimeos' );

		// Generate HTML before creating first PDF page to include header added in template
		$template = $config->get( 'controller/jobs/order/email/payment/template-pdf', 'order/email/payment/pdf' );
		$content = $view->set( 'pdf', $pdf )->render( $template );

		$pdf->addPage();
		$pdf->writeHtml( $content );
		$pdf->lastPage();

		return $pdf->output( '', 'S' );
	}


	/**
	 * Sends the payment related e-mail for a single order
	 *
	 * @param \Aimeos\MShop\Order\Item\Iface $order Order item
	 * @param string|null $theme Theme name or NULL for default theme
	 * @param string|null $logoPath Relative path to the logo in the fs-media file system
	 */
	protected function send( \Aimeos\MShop\Order\Item\Iface $order, string $theme = null, string $logoPath = null )
	{
		/** controller/jobs/order/email/payment/template-html
		 * Relative path to the template for the HTML part of the payment emails.
		 *
		 * The template file contains the HTML code and processing instructions
		 * to generate the result shown in the body of the frontend. The
		 * configuration string is the path to the template file relative
		 * to the templates directory (usually in controller/jobs/templates).
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates.
		 *
		 * @param string Relative path to the template
		 * @since 2022.04
		 * @see controller/jobs/order/email/payment/template-text
		 */

		/** controller/jobs/order/email/payment/template-text
		 * Relative path to the template for the text part of the payment emails.
		 *
		 * The template file contains the text and processing instructions
		 * to generate the result shown in the body of the frontend. The
		 * configuration string is the path to the template file relative
		 * to the templates directory (usually in controller/jobs/templates).
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates.
		 *
		 * @param string Relative path to the template
		 * @since 2022.04
		 * @see controller/jobs/order/email/payment/template-html
		 */

		$basket = $order->getBaseItem();
		$address = $this->address( $basket );

		$context = $this->context();
		$context->locale()->setLanguageId( $address->getLanguageId() );

		$msg = $this->call( 'mailTo', $address );
		$msg = $this->attachments( $msg );

		$view = $this->view( $basket, $theme );
		$view->logo = $msg->embed( $this->call( 'mailLogo', $logoPath ), basename( (string) $logoPath ) );
		$view->summaryBasket = $basket;
		$view->addressItem = $address;
		$view->orderItem = $order;

		$config = $context->config();
		$filename = $context->translate( 'client', 'Order' ) . '-' . $order->getOrderNumber() . '.pdf';

		/** controller/jobs/order/email/payment/bcc-email
		 * E-Mail address all payment e-mails should be also sent to
		 *
		 * Using this option you can send a copy of all payment related e-mails
		 * to a second e-mail account. This can be handy for testing and checking
		 * the e-mails sent to customers.
		 *
		 * It also allows shop owners with a very small volume of orders to be
		 * notified about payment changes. Be aware that this isn't useful if the
		 * order volumne is high or has peeks!
		 *
		 * @param string|array E-mail address or list of e-mail addresses
		 * @since 2014.03
		 */
		$msg->bcc( $config->get( 'controller/jobs/order/email/payment/bcc-email', [] ) );

		$msg->subject( sprintf( $context->translate( 'client', 'Your order %1$s' ), $order->getOrderNumber() ) )
			->html( $view->render( $config->get( 'controller/jobs/order/email/payment/template-html', 'order/email/payment/html' ) ) )
			->text( $view->render( $config->get( 'controller/jobs/order/email/payment/template-text', 'order/email/payment/text' ) ) )
			->attach( $this->pdf( $view ), $filename, 'application/pdf' )
			->send();
	}


	/**
	 * Adds the status of the delivered e-mail for the given order ID
	 *
	 * @param string $orderId Unique order ID
	 * @param int $value Status value
	 */
	protected function status( string $orderId, int $value )
	{
		$manager = \Aimeos\MShop::create( $this->context(), 'order/status' );

		$item = $manager->create()
			->setParentId( $orderId )
			->setType( \Aimeos\MShop\Order\Item\Status\Base::EMAIL_PAYMENT )
			->setValue( $value );

		$manager->save( $item );
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
	 * Returns the view populated with common data
	 *
	 * @param \Aimeos\MShop\Order\Item\Base\Iface $base Basket including addresses
	 * @param string|null $theme Theme name
	 * @return \Aimeos\Base\View\Iface View object
	 */
	protected function view( \Aimeos\MShop\Order\Item\Base\Iface $base, string $theme = null ) : \Aimeos\Base\View\Iface
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
