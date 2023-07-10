<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2016-2023
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
	/** controller/jobs/customer/email/account/name
	 * Class name of the used product notification e-mail scheduler controller implementation
	 *
	 * Each default job controller can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the controller factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Controller\Jobs\Customer\Email\Account\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Controller\Jobs\Customer\Email\Account\Myaccount
	 *
	 * then you have to set the this configuration option:
	 *
	 *  controller/jobs/customer/email/account/name = Myaccount
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyAccount"!
	 *
	 * @param string Last part of the class name
	 * @since 2016.04
	 */

	/** controller/jobs/customer/email/account/decorators/excludes
	 * Excludes decorators added by the "common" option from the customer email account controllers
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
	 *  controller/jobs/customer/email/account/decorators/excludes = array( 'decorator1' )
	 *
	 * This would remove the decorator named "decorator1" from the list of
	 * common decorators ("\Aimeos\Controller\Jobs\Common\Decorator\*") added via
	 * "controller/jobs/common/decorators/default" to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2016.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/customer/email/account/decorators/global
	 * @see controller/jobs/customer/email/account/decorators/local
	 */

	/** controller/jobs/customer/email/account/decorators/global
	 * Adds a list of globally available decorators only to the customer email account controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap global decorators
	 * ("\Aimeos\Controller\Jobs\Common\Decorator\*") around the job controller.
	 *
	 *  controller/jobs/customer/email/account/decorators/global = array( 'decorator1' )
	 *
	 * This would add the decorator named "decorator1" defined by
	 * "\Aimeos\Controller\Jobs\Common\Decorator\Decorator1" only to this job controller.
	 *
	 * @param array List of decorator names
	 * @since 2016.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/customer/email/account/decorators/excludes
	 * @see controller/jobs/customer/email/account/decorators/local
	 */

	/** controller/jobs/customer/email/account/decorators/local
	 * Adds a list of local decorators only to the customer email account controllers
	 *
	 * Decorators extend the functionality of a class by adding new aspects
	 * (e.g. log what is currently done), executing the methods of the underlying
	 * class only in certain conditions (e.g. only for logged in users) or
	 * modify what is returned to the caller.
	 *
	 * This option allows you to wrap local decorators
	 * ("\Aimeos\Controller\Jobs\Customer\Email\Account\Decorator\*") around this job controller.
	 *
	 *  controller/jobs/customer/email/account/decorators/local = array( 'decorator2' )
	 *
	 * This would add the decorator named "decorator2" defined by
	 * "\Aimeos\Controller\Jobs\Customer\Email\Account\Decorator\Decorator2" only to this job
	 * controller.
	 *
	 * @param array List of decorator names
	 * @since 2016.04
	 * @see controller/jobs/common/decorators/default
	 * @see controller/jobs/customer/email/account/decorators/excludes
	 * @see controller/jobs/customer/email/account/decorators/global
	 */


	use \Aimeos\Controller\Jobs\Mail;


	private array $sites = [];


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

				$pass = $list['customer.password'] ?? null;
				$item = $custManager->create()->fromArray( $list, true );
				$sites = $this->sites( $item->getSiteId() );

				$address = $item->getPaymentAddress();
				$context->locale()->setLanguageId( $address->getLanguageId() ); // for translation

				$view = $this->view( $address, $sites->getTheme()->filter()->last() );
				$view->account = $item->getCode();
				$view->password = $pass;

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
		 * to the templates directory (usually in templates/controller/jobs).
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
		 * to the templates directory (usually in templates/controller/jobs).
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
