<?php

/**
 * @file
 * Media component alterations.
 */

declare(strict_types=1);

use Drupal\media\MediaInterface;

/**
 * Implements hook_preprocess_HOOK().
 */
function drevops_preprocess_media__civictheme_image(array &$variables): void {
  $media = $variables['media'] ?? NULL;

  // A source file that cannot be read as an image leaves the URL empty.
  if (!$media instanceof MediaInterface || empty($variables['url'])) {
    return;
  }

  // CivicTheme builds this URL from the source file, so it serves the original
  // upload.
  $image = civictheme_media_image_get_variables($media, 'wide');

  if (empty($image['url'])) {
    return;
  }

  $variables['url'] = $image['url'];

  // Dimensions measured on the source no longer match once a style resizes the
  // image. The image component resolves them from the URL.
  $variables['width'] = NULL;
  $variables['height'] = NULL;
}
