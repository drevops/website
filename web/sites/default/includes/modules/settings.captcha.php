<?php

/**
 * @file
 * Captcha settings.
 */

declare(strict_types=1);

if ($settings['environment'] == ENVIRONMENT_CI || $settings['environment'] == ENVIRONMENT_LOCAL) {
  $settings['disable_captcha'] = TRUE;
}
