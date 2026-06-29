<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Creates the content authoring API service account.
 */
function do_content_api_deploy_service_account(): string {
  $username = 'do_content_api_service';

  $storage = \Drupal::entityTypeManager()->getStorage('user');
  $existing = $storage->loadByProperties(['name' => $username]);

  if ($existing) {
    $account = reset($existing);

    // Fail closed on a username collision: only an account this hook created
    // (matching the deterministic service email) is reconciled, so a
    // pre-existing human account that happens to share the name is never
    // granted API access.
    if (!$account instanceof UserInterface || $account->getEmail() !== $username . '@example.com') {
      return sprintf('Account "%s" exists but is not the service account; left unchanged.', $username);
    }

    // Reconcile to exactly the least-privilege role set, dropping any extra
    // roles that would widen the externally authenticated account. Status is
    // left untouched so a deliberately blocked account stays disabled.
    if ($account->getRoles(TRUE) !== ['do_content_api']) {
      foreach ($account->getRoles(TRUE) as $role) {
        $account->removeRole($role);
      }
      $account->addRole('do_content_api');
      $account->save();

      return sprintf('Service account "%s" reconciled to least-privilege role set.', $username);
    }

    return sprintf('Service account "%s" already exists; skipped.', $username);
  }

  // The role grants 'use key authentication', so key_auth issues the API key
  // automatically when the account is inserted with the role assigned.
  $account = User::create([
    'name' => $username,
    'mail' => $username . '@example.com',
    'status' => 1,
    'roles' => ['do_content_api'],
  ]);
  $account->save();

  return sprintf('Created service account "%s" (uid %d). Retrieve its API key at /user/%d/key-auth.', $username, $account->id(), $account->id());
}
