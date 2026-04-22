<?php

/**
 * @file
 * CSP settings.
 */

declare(strict_types=1);

if ($settings['environment'] === ENVIRONMENT_CI || $settings['environment'] === ENVIRONMENT_LOCAL) {
  // Disable CSP locally and in CI as we do not serve the site over HTTPS.
  $config['csp.settings']['enforce']['directives']['upgrade-insecure-requests'] = FALSE;
}
