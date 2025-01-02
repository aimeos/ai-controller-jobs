<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2025
 * @package Controller
 * @subpackage Common
 */


namespace Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Email;


/**
 * Customer group processor for subscriptions
 *
 * @package Controller
 * @subpackage Common
 */
class Standard
	extends \Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Base
	implements \Aimeos\Controller\Jobs\Common\Subscription\Process\Processor\Iface
{
	use \Aimeos\Controller\Jobs\Mail;


	/**
	 * Executed after the subscription renewal
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item
	 * @param \Aimeos\MShop\Order\Item\Iface $subscription Order item
	 */
	public function renewAfter( \Aimeos\MShop\Subscription\Item\Iface $subscription, \Aimeos\MShop\Order\Item\Iface $order )
	{
		if( $subscription->getReason() === \Aimeos\MShop\Subscription\Item\Iface::REASON_PAYMENT ) {
			$this->notify( $subscription, $order );
		}
	}


	/**
	 * Processes the end of the subscription
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item
	 * @param \Aimeos\MShop\Order\Item\Iface $subscription Order item
	 */
	public function end( \Aimeos\MShop\Subscription\Item\Iface $subscription, \Aimeos\MShop\Order\Item\Iface $order )
	{
		$this->notify( $subscription, $order );
	}


	/**
	 * Sends e-mails for the given subscription
	 *
	 * @param \Aimeos\MShop\Subscription\Item\Iface $subscription Subscription item object
	 * @param \Aimeos\MShop\Order\Item\Iface $subscription Order item
	 */
	protected function notify( \Aimeos\MShop\Subscription\Item\Iface $subscription, \Aimeos\MShop\Order\Item\Iface $order )
	{
		$address = current( $order->getAddress( 'payment' ) );

		$siteIds = explode( '.', trim( $order->getSiteId(), '.' ) );
		$sites = \Aimeos\MShop::create( $this->context(), 'locale/site' )->getPath( end( $siteIds ) );

		$view = $this->view( $order, $sites->getTheme()->filter()->last() );
		$view->subscriptionItem = $subscription;
		$view->addressItem = $address;

		foreach( $order->getProducts() as $orderProduct )
		{
			if( $orderProduct->getId() == $subscription->getOrderProductId() ) {
				$this->send( $view->set( 'orderProductItem', $orderProduct ), $address, $sites->getLogo()->filter()->last() );
			}
		}
	}


	/**
	 * Sends the subscription e-mail to the customer
	 *
	 * @param \Aimeos\Base\View\Iface $view View object
	 * @param \Aimeos\MShop\Order\Item\Address\Iface $address Address item
	 * @param string|null $logoPath Path to the logo
	 */
	protected function send( \Aimeos\Base\View\Iface $view, \Aimeos\MShop\Order\Item\Address\Iface $address, ?string $logoPath = null )
	{
		/** controller/jobs/order/email/subscription/template-html
		 * Relative path to the template for the HTML part of the subscription emails.
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
		 * @see controller/jobs/order/email/subscription/template-text
		 */

		/** controller/jobs/order/email/subscription/template-text
		 * Relative path to the template for the text part of the subscription emails.
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
		 * @see controller/jobs/order/email/subscription/template-html
		 */

		$context = $this->context();
		$config = $context->config();

		$msg = $this->call( 'mailTo', $address );
		$view->logo = $msg->embed( $this->call( 'mailLogo', $logoPath ), basename( (string) $logoPath ) );

		$msg->subject( $context->translate( 'client', 'Your subscription' ) )
			->html( $view->render( $config->get( 'controller/jobs/order/email/subscription/template-html', 'order/email/subscription/html' ) ) )
			->text( $view->render( $config->get( 'controller/jobs/order/email/subscription/template-text', 'order/email/subscription/text' ) ) )
			->send();
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
		$address = current( $base->getAddress( 'payment' ) );
		$langId = $address->getLanguageId() ?: $base->locale()->getLanguageId();

		$view = $this->call( 'mailView', $langId );
		$view->intro = $this->call( 'mailIntro', $address );
		$view->css = $this->call( 'mailCss', $theme );
		$view->urlparams = [
			'currency' => $base->getPrice()->getCurrencyId(),
			'site' => $base->getSiteCode(),
			'locale' => $langId,
		];

		return $view;
	}
}
