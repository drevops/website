<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\civictheme\CivicthemeColorManager;
use Drupal\civictheme\CivicthemeConstants;

/**
 * Rebuild CivicTheme colour stylesheets from the imported colour configuration.
 */
function do_base_deploy_civictheme_colors(): string {
  if (!class_exists(CivicthemeColorManager::class)) {
    return 'CivicTheme is not available; skipped colour stylesheet rebuild.';
  }

  // CivicTheme compiles the colour palette into a generated stylesheet that is
  // rebuilt only when the theme settings form is saved. A configuration import
  // updates the stored palette but leaves the previous stylesheet on disk, so
  // purging it forces a rebuild from the imported configuration on the next
  // request - keeping the rendered colours in sync with the committed config.
  // A failed purge must not abort the deployment, so the error is reported
  // rather than thrown.
  try {
    \Drupal::classResolver(CivicthemeColorManager::class)->invalidateCache();
  }
  catch (\Throwable $exception) {
    return 'Failed to purge CivicTheme generated colour stylesheets: ' . $exception->getMessage();
  }

  return 'Purged CivicTheme generated colour stylesheets for rebuild from configuration.';
}

/**
 * Migrate every existing component to the dark colour scheme.
 */
function do_base_deploy_component_dark_theme(array &$sandbox): string {
  if (!class_exists(CivicthemeConstants::class)) {
    $sandbox['#finished'] = 1;

    return 'CivicTheme is not available; skipped the dark colour scheme migration.';
  }

  // CivicTheme scheme-selector fields grouped by the entity type that carries
  // them. Each stores 'light' or 'dark' and drives the rendered colour scheme.
  // Existing entities keep whatever value they were saved with, so any that are
  // not already dark are re-saved to align with the new dark field defaults.
  $theme_fields = [
    'paragraph' => ['field_c_p_theme', 'field_c_p_list_item_theme'],
    'block_content' => ['field_c_b_theme', 'field_c_b_trigger_theme'],
  ];

  $entity_type_manager = \Drupal::entityTypeManager();
  $batch_size = 50;
  $processed = 0;

  // Re-query the remaining non-dark entities on each pass and migrate up to one
  // batch. A saved entity drops out of the next query, so repeated passes drain
  // the backlog without tracking offsets in the sandbox; the hook finishes once
  // a pass finds nothing left to change.
  foreach ($theme_fields as $entity_type_id => $field_names) {
    $storage = $entity_type_manager->getStorage($entity_type_id);

    foreach ($field_names as $field_name) {
      if ($processed >= $batch_size) {
        break 2;
      }

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition($field_name, CivicthemeConstants::THEME_DARK, '<>')
        ->range(0, $batch_size - $processed)
        ->execute();

      foreach ($ids as $id) {
        $entity = $storage->load($id);

        if ($entity instanceof FieldableEntityInterface && $entity->hasField($field_name)) {
          $entity->set($field_name, CivicthemeConstants::THEME_DARK);
          $entity->save();
        }

        $processed++;
      }
    }
  }

  $migrated = (int) ($sandbox['migrated'] ?? 0) + $processed;
  $sandbox['migrated'] = $migrated;
  $sandbox['#finished'] = $processed === 0 ? 1 : 0;

  return sprintf('Set the dark colour scheme on %d component(s).', $migrated);
}
