<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\drupal_helpers\Helper;
use Drupal\drupal_helpers\Report\Reporter;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Replaces the placeholder thumbnail of every SVG icon with the icon itself.
 */
function do_base_deploy_refresh_svg_thumbnails(?array &$sandbox = NULL): ?string {
  $query = \Drupal::entityQuery('media')->accessCheck(FALSE)->condition('bundle', 'civictheme_icon');

  // Saving runs the presave hook that points the thumbnail at the SVG.
  return Helper::entity($sandbox)->batchQuery($query, static function (MediaInterface $media): void {
    $media->save();
  }, status: Reporter::UPDATED);
}

/**
 * Sets the aurora artwork as the sitewide banner's default background.
 */
function do_base_deploy_set_banner_background(): string {
  $block = \Drupal::service('entity.repository')->loadEntityByUuid('block_content', '50c52459-c100-4e4d-92a4-9c4c77d8f8d0');

  if (!$block instanceof BlockContentInterface || !$block->hasField('field_c_b_background_image')) {
    Helper::reporter()->skipped('The sitewide banner block does not exist.');

    return Helper::report();
  }

  $media = _do_base_asset_media('5f0b6c4e-2d7a-4e8b-9c31-a6f2d8e4b7c0', 'banner-aurora.svg', 'civictheme_icon', 'public://icons', 'Aurora banner background');

  if (!$media instanceof MediaInterface) {
    Helper::reporter()->skipped('The aurora banner background could not be created from the module assets.');

    return Helper::report();
  }

  if ((string) $block->get('field_c_b_background_image')->target_id === (string) $media->id()) {
    Helper::reporter()->skipped('The sitewide banner already uses the aurora background.');

    return Helper::report();
  }

  $block->set('field_c_b_background_image', ['target_id' => $media->id()]);
  $block->save();

  Helper::reporter()->updated('Set the aurora background on the sitewide banner.');

  return Helper::report();
}

/**
 * Loads or creates a media entity around a file in the module's assets.
 *
 * @param string $uuid
 *   UUID of the media entity, fixed so that every environment shares it.
 * @param string $filename
 *   Name of the file in the assets directory.
 * @param string $bundle
 *   Media type to create.
 * @param string $directory
 *   Directory the file is copied into.
 * @param string $name
 *   Name of the media entity.
 * @param array<string, mixed> $source_values
 *   Extra values for the source field item, such as the alt text.
 *
 * @return \Drupal\media\MediaInterface|null
 *   The media entity, or NULL when the file cannot be read or copied.
 */
function _do_base_asset_media(string $uuid, string $filename, string $bundle, string $directory, string $name, array $source_values = []): ?MediaInterface {
  $entity_type_manager = \Drupal::entityTypeManager();
  $media_storage = $entity_type_manager->getStorage('media');

  $existing = $media_storage->loadByProperties(['uuid' => $uuid]);
  $media = reset($existing);

  if ($media instanceof MediaInterface) {
    return $media;
  }

  $source = DRUPAL_ROOT . '/' . \Drupal::service('extension.list.module')->getPath('do_base') . '/assets/' . $filename;
  $media_type = $entity_type_manager->getStorage('media_type')->load($bundle);

  if (!is_file($source) || !$media_type instanceof MediaTypeInterface) {
    return NULL;
  }

  if (!\Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
    return NULL;
  }

  $file = \Drupal::service('file.repository')->writeData((string) file_get_contents($source), $directory . '/' . $filename, FileExists::Replace);

  if (!$file instanceof FileInterface) {
    return NULL;
  }

  $source_field = $media_type->getSource()->getConfiguration()['source_field'];

  $media = $media_storage->create([
    'bundle' => $bundle,
    'uuid' => $uuid,
    'name' => $name,
    'status' => 1,
    $source_field => ['target_id' => $file->id()] + $source_values,
  ]);
  $media->save();

  return $media;
}
