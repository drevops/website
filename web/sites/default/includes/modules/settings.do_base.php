<?php

/**
 * @file
 * Settings for the DrevOps Website Base module.
 */

declare(strict_types=1);

if ($settings['environment'] === ENVIRONMENT_CI) {
  $settings['suspend_mail_send'] = TRUE;
}
