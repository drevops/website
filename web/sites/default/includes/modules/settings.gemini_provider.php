<?php

/**
 * @file
 * Gemini AI provider settings.
 */

declare(strict_types=1);

// Provide the Gemini API key from an environment variable so the secret stays
// out of exported configuration, consistent with other third-party credentials.
if (!empty(getenv('DRUPAL_AI_PROVIDER_GEMINI_API_KEY'))) {
  $config['key.key.gemini']['key_provider_settings']['key_value'] = getenv('DRUPAL_AI_PROVIDER_GEMINI_API_KEY');
}
