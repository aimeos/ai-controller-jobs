<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2022-2025
 * @package Controller
 * @subpackage Jobs
 */


namespace Aimeos\Controller\Jobs;


/**
 * Mail trait for job controllers
 *
 * @package Controller
 * @subpackage Jobs
 */
trait Mail
{
	/**
	 * Returns the context object
	 *
	 * @return \Aimeos\MShop\ContextIface Context object
	 */
	abstract protected function context() : \Aimeos\MShop\ContextIface;


	/**
	 * Returns the CSS rules for the given theme
	 *
	 * @param string|null $theme Theme name
	 * @return string|null CSS rules
	 */
	protected function mailCss( ?string $theme ) : ?string
	{
		$theme = $theme ?: 'default';
		$fs = $this->context()->fs( 'fs-theme' );

		return $fs->has( $theme . '/email.css' ) ? $fs->read( $theme . '/email.css' ) : null;
	}


	/**
	 * Returns the e-mail intro message
	 *
	 * @param \Aimeos\MShop\Common\Item\Address\Iface $addr Address item object
	 * @return string Intro message with salutation
	 */
	protected function mailIntro( \Aimeos\MShop\Common\Item\Address\Iface $addr ) : string
	{
		switch( $addr->getSalutation() )
		{
			case '':
				/// E-mail intro with first name (%1$s) and last name (%2$s)
				$msg = $this->context()->translate( 'controller/jobs', 'Dear %1$s %2$s' ); break;
			case 'mr':
				/// E-mail intro with first name (%1$s) and last name (%2$s)
				$msg = $this->context()->translate( 'controller/jobs', 'Dear Mr %1$s %2$s' ); break;
			case 'ms':
				/// E-mail intro with first name (%1$s) and last name (%2$s)
				$msg = $this->context()->translate( 'controller/jobs', 'Dear Ms %1$s %2$s' ); break;
			default:
				$msg = $this->context()->translate( 'controller/jobs', 'Dear customer' );
		}

		return sprintf( $msg, $addr->getFirstName(), $addr->getLastName() );
	}


	/**
	 * Returns the logo for the given path
	 *
	 * @param string|null $path Logo path relative to fs-media file system
	 * @return string Binary logo data
	 */
	protected function mailLogo( ?string $path ) : string
	{
		$fs = $this->context()->fs( 'fs-media' );
		return $path && $fs->has( $path ) ? $fs->read( $path ) : '';
	}


	/**
	 * Prepares and returns a new mail message
	 *
	 * @param \Aimeos\MShop\Common\Item\Address\Iface $addr Address item object
	 * @return \Aimeos\Base\Mail\Message\Iface Prepared mail message
	 */
	protected function mailTo( \Aimeos\MShop\Common\Item\Address\Iface $addr ) : \Aimeos\Base\Mail\Message\Iface
	{
		$context = $this->context();
		$config = $context->config();

		return $context->mail()->create()
			->header( 'X-MailGenerator', 'Aimeos' )
			->from( $config->get( 'resource/email/from-email' ), $config->get( 'resource/email/from-name' ) )
			->to( $addr->getEMail(), $addr->getFirstName() . ' ' . $addr->getLastName() )
			->bcc( $config->get( 'resource/email/bcc-email' ) );
	}


	/**
	 * Returns the view for generating the mail message content
	 *
	 * @param string|null $langId Language ID the content should be generated for
	 * @return \Aimeos\Base\View\Iface View object
	 */
	protected function mailView( ?string $langId = null ) : \Aimeos\Base\View\Iface
	{
		$view = $this->context()->view();

		$helper = new \Aimeos\Base\View\Helper\Number\Locale( $view, $langId );
		$view->addHelper( 'number', $helper );

		$helper = new \Aimeos\Base\View\Helper\Translate\Standard( $view, $this->context()->i18n( $langId ?: 'en' ) );
		$view->addHelper( 'translate', $helper );

		return $view;
	}
}
