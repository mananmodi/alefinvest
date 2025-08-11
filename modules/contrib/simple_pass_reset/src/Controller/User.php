<?php

namespace Drupal\simple_pass_reset\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Alter User Reset password page.
 */
class User extends ControllerBase {

  /**
   * Get the page title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function title() {
    return $this->t('Choose a new password');
  }

  /**
   * Displays user password reset form.
   */
  public function resetPass(Request $request, $uid, $timestamp, $hash) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager()->getStorage('user')->load($uid);
    $account = $this->currentUser();

    // The current user is already logged in.
    if ($account->isAuthenticated() && $account->id() == $uid) {
      user_logout();
      // We need to begin the redirect process again because logging out will
      // destroy the session.
      return $this->redirect('user.reset', [
        'uid' => $uid,
        'timestamp' => $timestamp,
        'hash' => $hash,
      ]);
    }
    $formObject = $this->entityTypeManager->getFormObject('user', 'default')->setEntity($user);
    return $this->formBuilder()->getForm($formObject);
  }

}
