<?php

/**
 * @file
 * Seckit settings.
 */

declare(strict_types=1);

if ($settings['environment'] == ENVIRONMENT_CI || $settings['environment'] == ENVIRONMENT_LOCAL) {
  // Disable HTTPS upgrade in local and CI to avoid cert errors.
  $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
}
