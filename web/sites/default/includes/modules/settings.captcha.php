<?php

/**
 * @file
 * Captcha settings.
 */

declare(strict_types=1);

if ($settings['environment'] == ENVIRONMENT_CI || $settings['environment'] == ENVIRONMENT_LOCAL) {
  $settings['disable_captcha'] = TRUE;
}

// Set credentials, but only if the environment variables are present.
if (!empty(getenv('DRUPAL_RECAPTCHA_SITE_KEY')) && !empty(getenv('DRUPAL_RECAPTCHA_SECRET_KEY'))) {
  $config['recaptcha_v3.settings']['site_key'] = getenv('DRUPAL_RECAPTCHA_SITE_KEY');
  $config['recaptcha_v3.settings']['secret_key'] = getenv('DRUPAL_RECAPTCHA_SECRET_KEY');
}
