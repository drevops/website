<?php

/**
 * @file
 * Cloudflare settings.
 */

declare(strict_types=1);

if (!empty(getenv('DRUPAL_CLOUDFLARE_API_TOKEN'))) {
  $config['cloudflare.settings']['api_token'] = getenv('DRUPAL_CLOUDFLARE_API_TOKEN');
  $config['cloudflare.settings']['auth_using'] = 'token';
}
else {
  $config['cloudflare.settings']['valid_credentials'] = FALSE;
}

if ($settings['environment'] === ENVIRONMENT_LOCAL || $settings['environment'] === ENVIRONMENT_CI) {
  $config['cloudflare.settings']['bypass_host'] = getenv('VORTEX_LOCALDEV_URL') ?: 'localhost';
}
