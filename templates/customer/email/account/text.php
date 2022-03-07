<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2022
 */

?>
<?= wordwrap( strip_tags( $this->get( 'intro', '' ) ) ) ?>


<?= wordwrap( strip_tags( $this->translate( 'controller/jobs', 'An account has been created for you.' ) ) ) ?>


<?= strip_tags( $this->translate( 'controller/jobs', 'Your account' ) ) ?>

<?= $this->translate( 'controller/jobs', 'Account' ) ?>: <?= $this->get( 'account' ) ?>

<?= $this->translate( 'controller/jobs', 'Password' ) ?>: <?= $this->get( 'password' ) ?: $this->translate( 'controller/jobs', 'Like entered by you' ) ?>


<?= $this->translate( 'controller/jobs', 'Login' ) ?>: <?= $this->link( 'client/html/account/index/url', ['locale' => $this->addressItem->getLanguageId()], ['absoluteUri' => 1] ) ?>


<?= wordwrap( strip_tags( $this->translate( 'controller/jobs', 'If you have any questions, please reply to this e-mail' ) ) ) ?>
