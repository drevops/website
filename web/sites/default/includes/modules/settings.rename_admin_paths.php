<?php

/**
 * @file
 * Rename Admin Paths settings.
 */

declare(strict_types=1);

// The repository is public, so the replacement for '/user' is read from the
// environment and never committed.
$user_path = trim((string) getenv('DRUPAL_USER_PATH'), '/');

if ($user_path !== '') {
  $config['rename_admin_paths.settings']['user_path'] = 1;
  $config['rename_admin_paths.settings']['user_path_value'] = $user_path;
}
