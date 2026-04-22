<?php

/**
 * @file
 * CSP settings.
 */

declare(strict_types=1);

if ($settings['environment'] === ENVIRONMENT_CI || $settings['environment'] === ENVIRONMENT_LOCAL) {
  // Disable only the 'upgrade-insecure-requests' directive locally and in CI
  // where the site is served over HTTP. The rest of the CSP policy still
  // applies.
  $config['csp.settings']['enforce']['directives']['upgrade-insecure-requests'] = FALSE;
}
