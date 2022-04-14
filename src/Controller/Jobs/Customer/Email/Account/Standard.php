<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2016-2022
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs\Customer\Email\Account;


/**
 * Customer account e-mail job controller
 *
 * @package Controller
 * @subpackage Jobs
 */
class Standard
	extends \Aimeos\Controller\Jobs\Base
	implements \Aimeos\Controller\Jobs\Iface
{
	use \Aimeos\Controller\Jobs\Mail;


	private $sites = [];


	/**
	 * Returns the localized name of the job.
	 *
	 * @return string Name of the job
	 */
	public function getName() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Customer account e-mails' );
	}


	/**
	 * Returns the localized description of the job.
	 *
	 * @return string Description of the job
	 */
	public function getDescription() : string
	{
		return $this->context()->translate( 'controller/jobs', 'Sends e-mails for new customer accounts' );
	}


	/**
	 * Executes the job.
	 *
	 * @throws \Aimeos\Controller\Jobs\Exception If an error occurs
	 */
	public function run()
	{
		$context = $this->context();
		$queue = $context->queue( 'mq-email', 'customer/email/account' );
		$custManager = \Aimeos\MShop::create( $context, 'customer' );

		while( ( $msg = $queue->get() ) !== null )
		{
			try
			{
				if( ( $list = json_decode( $msg->getBody(), true ) ) === null )
				{
					$str = sprintf( 'Invalid JSON encode message: %1$s', $msg->getBody() );
					throw new \Aimeos\Controller\Jobs\Exception( $str );
				}

				$password = $list['customer.password'] ?? null;
				$item = $custManager->create()->fromArray( $list, true );
				$sites = $this->sites( $item->getSiteId() );

				$address = $item->getPaymentAddress();
				$context->locale()->setLanguageId( $address->getLanguageId() ); // for translation

				$view = $this->view( $address, $sites->getTheme()->filter()->last() );
				$view->account = $item->getCode();
				$view->password = $password;

				$this->send( $view, $address, $sites->getLogo()->filter()->last() );

				$str = sprintf( 'Sent customer account e-mail to "%1$s"', $address->getEmail() );
				$context->logger()->debug( $str, 'email/customer/account' );
			}
			catch( \Exception $e )
			{
				$str = 'Error while trying to send customer account e-mail: ' . $e->getMessage();
				$context->logger()->error( $str . PHP_EOL . $e->getTraceAsString(), 'email/customer/account' );
			}

			$queue->del( $msg );
		}
	}


	/**
	 * Sends the account creation e-mail to the e-mail address of the customer
	 *
	 * @param \Aimeos\Base\View\Iface $view View object
	 * @param \Aimeos\MShop\Common\Item\Address\Iface $address Address item
	 * @param string|null $logoPath Path to the logo
	 */
	protected function send( \Aimeos\Base\View\Iface $view, \Aimeos\MShop\Common\Item\Address\Iface $address, string $logoPath = null )
	{
		/** controller/jobs/customer/email/account/template-html
		 * Relative path to the template for the HTML part of the account emails.
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
		 * @see controller/jobs/customer/email/account/template-text
		 */

		/** controller/jobs/customer/email/account/template-text
		 * Relative path to the template for the text part of the account emails.
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
		 * @see controller/jobs/customer/email/account/template-html
		 */

		$context = $this->context();
		$config = $context->config();

		$msg = $this->call( 'mailTo', $address );
		$view->logo = $msg->embed( $this->call( 'mailLogo', $logoPath ), basename( (string) $logoPath ) );

		$msg->subject( $context->translate( 'client', 'Your new account' ) )
			->html( $view->render( $config->get( 'controller/jobs/customer/email/account/template-html', 'customer/email/account/html' ) ) )
			->text( $view->render( $config->get( 'controller/jobs/customer/email/account/template-text', 'customer/email/account/text' ) ) )
			->send();
	}


	/**
	 * Returns the list of site items from the given site ID up to the root site
	 *
	 * @param string|null $siteId Site ID like "1.2.4."
	 * @return \Aimeos\Map List of site items
	 */
	protected function sites( string $siteId = null ) : \Aimeos\Map
	{
		if( !$siteId && !isset( $this->sites[''] ) ) {
			$this->sites[''] = map( \Aimeos\MShop::create( $this->context(), 'locale/site' )->find( 'default' ) );
		}

		if( !isset( $this->sites[(string) $siteId] ) )
		{
			$manager = \Aimeos\MShop::create( $this->context(), 'locale/site' );
			$siteIds = explode( '.', trim( (string) $siteId, '.' ) );

			$this->sites[$siteId] = $manager->getPath( end( $siteIds ) );
		}

		return $this->sites[$siteId];
	}


	/**
	 * Returns the view populated with common data
	 *
	 * @param \Aimeos\MShop\Common\Item\Address\Iface $address Address item
	 * @param string|null $theme Theme name
	 * @return \Aimeos\Base\View\Iface View object
	 */
	protected function view( \Aimeos\MShop\Common\Item\Address\Iface $address, string $theme = null ) : \Aimeos\Base\View\Iface
	{
		$view = $this->call( 'mailView', $address->getLanguageId() );
		$view->intro = $this->call( 'mailIntro', $address );
		$view->css = $this->call( 'mailCss', $theme );
		$view->addressItem = $address;
		$view->urlparams = [
			'site' => $this->context()->locale()->getSiteItem()->getCode(),
			'locale' => $address->getLanguageId(),
		];

		return $view;
	}
}
