<?php

namespace Drupal\simple_pass_reset\AccessChecks;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * A custom access check.
 */
class ResetPassAccessCheck implements AccessInterface {

  use StringTranslationTrait;

  /**
   * The User configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The User storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, LoggerInterface $logger, TimeInterface $time, ConfigFactoryInterface $config_factory) {
    $this->storage = $entity_manager->getStorage('user');
    $this->logger = $logger;
    $this->time = $time;
    $this->config = $config_factory->get('user.settings');
  }

  /**
   * Checks access to the password reset route.
   *
   * @param int $uid
   *   User ID of the user requesting reset.
   * @param int $timestamp
   *   Timestamp of the reset link creation.
   * @param string $hash
   *   Login link hash.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current session.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(int $uid, int $timestamp, $hash, AccountInterface $account) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->storage->load($uid);
    // Time out, in seconds, until login URL expires.
    $timeout = $this->config->get('password_reset_timeout');

    // Verify that the user exists and is active.
    if (is_null($user) || !$user->isActive()) {
      // Blocked or invalid user ID, so deny access. The parameters will be in
      // the watchdog's URL for the administrator to check.
      return AccessResult::forbidden();
    }

    // When processing the one-time login link, we have to make sure that a user
    // isn't already logged in.
    if ($account->isAuthenticated()) {
      // A different user is already logged in on the computer.
      if ($account->id() != $uid) {
        $this->logger->warning($this->t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href=":logout">log out</a> and try using the link again.',
          [
            '%other_user' => $account->getAccountName(),
            '%resetting_user' => $user->getAccountName(),
            ':logout' => Url::fromRoute('user.logout')->toString(),
          ]));
        // Throw access denied page.
        return AccessResult::forbidden();
      }
    }

    if ($timestamp <= $this->time->getRequestTime()) {
      // Bypass the timeout check if the user has not logged in before.
      if ($user->getLastLoginTime() && $this->time->getRequestTime() - $timestamp > $timeout) {
        $this->logger->error($this->t('You have tried to use a one-time login link that has expired. Please request a new one using the <a href=":link">link</a>.', [':link' => Url::fromRoute('user.pass')->toString()]));
        return AccessResult::forbidden();
      }
      elseif (($timestamp >= $user->getLastLoginTime()) && ($timestamp <= $this->time->getRequestTime()) && hash_equals($hash, user_pass_rehash($user, $timestamp))) {
        return AccessResult::Allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
