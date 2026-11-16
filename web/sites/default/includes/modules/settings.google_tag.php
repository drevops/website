<?php

/**
 * @file
 * Google tag settings.
 */

declare(strict_types=1);

if ($settings['environment'] !== ENVIRONMENT_PROD) {
  $google_tag_container = 'google_tag.container.G-LCQ5CZ3H3W.680a169398dd50.88125398';
  $google_tag_id = getenv('DRUPAL_GOOGLE_TAG_ID');

  if (!empty($google_tag_id)) {
    // Report to a separate GA4 property, so test traffic stays out of the
    // production reports.
    $config[$google_tag_container]['tag_container_ids'] = [$google_tag_id];
  }
  else {
    // Axel still queues events in 'window.dataLayer' without the tag.
    $config[$google_tag_container]['status'] = FALSE;
  }
}
