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
