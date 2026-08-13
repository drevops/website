<?php

/**
 * @file
 * Image list paragraph preprocessing.
 */

declare(strict_types=1);

use Drupal\media\MediaInterface;

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__image_list(array &$variables): void {
  _civictheme_preprocess_paragraph__paragraph_field__theme($variables);
  _civictheme_preprocess_paragraph__paragraph_field__vertical_spacing($variables);
  _civictheme_preprocess_paragraph__paragraph_field__background($variables);
  _civictheme_preprocess_paragraph__paragraph_field__title($variables);
  _civictheme_preprocess_paragraph__paragraph_field__content($variables);
  _drevops_preprocess_paragraph__paragraph_field__images($variables);
}

/**
 * Pre-process for Images paragraph field.
 *
 * CivicTheme's own image pre-processor reads a single-valued field, so the
 * multi-value counterpart is resolved here.
 */
function _drevops_preprocess_paragraph__paragraph_field__images(array &$variables): void {
  $variables['images'] = [];

  $entities = civictheme_get_field_value($variables['paragraph'], 'field_p_images', build: $variables) ?? [];

  foreach ($entities as $entity) {
    if (!$entity instanceof MediaInterface) {
      continue;
    }

    $image = civictheme_media_image_get_variables($entity, 'image_list');

    if (empty($image['url'])) {
      continue;
    }

    $variables['images'][] = [
      'url' => $image['url'],
      // CivicTheme image media carry the alt text on the file field, and fall
      // back to the media name, which is what the library shows.
      'alt' => $image['alt'] !== '' ? $image['alt'] : $entity->label(),
    ];
  }
}
