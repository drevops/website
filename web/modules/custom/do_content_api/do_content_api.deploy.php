<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\user\Entity\User;

/**
 * Creates the content authoring API service account.
 */
function do_content_api_deploy_service_account(): string {
  $username = 'do_content_api_service';

  $storage = \Drupal::entityTypeManager()->getStorage('user');
  $existing = $storage->loadByProperties(['name' => $username]);

  if ($existing) {
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
