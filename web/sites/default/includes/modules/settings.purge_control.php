<?php

/**
 * @file
 * Purge Control settings.
 */

declare(strict_types=1);

$settings['config_exclude_modules'][] = 'purge_control';

if ($settings['environment'] === ENVIRONMENT_LOCAL || $settings['environment'] === ENVIRONMENT_CI) {
  $config['purge_control.settings']['disable_purge'] = TRUE;
  $config['purge_control.settings']['purge_auto_control'] = FALSE;
}
