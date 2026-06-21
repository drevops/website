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
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;

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

/**
 * Ensure the Blog topic term used to tag and list blog posts exists.
 */
function do_base_deploy_blog_topic(): string {
  $term = _do_base_ensure_blog_term();

  if (!$term instanceof TermInterface) {
    return 'The civictheme_topics vocabulary is unavailable; skipped the Blog topic term.';
  }

  return sprintf('Blog topic term is available (term %d).', $term->id());
}

/**
 * Backfill the read time on blog posts that do not have one yet.
 */
function do_base_deploy_blog_read_time(array &$sandbox): string {
  $term = _do_base_ensure_blog_term();

  if (!$term instanceof TermInterface) {
    $sandbox['#finished'] = 1;

    return 'The Blog topic term is unavailable; skipped the read time backfill.';
  }

  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $batch_size = 25;

  // Re-query blog posts that still have no read time and fill one batch per
  // pass. A saved node drops out of the next query, so repeated passes drain
  // the backlog without tracking offsets; the hook finishes once a pass finds
  // nothing left. Editors can override the estimate at any time.
  $ids = $node_storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', 'civictheme_page')
    ->condition('field_c_n_topics', $term->id())
    ->notExists('field_read_time')
    ->range(0, $batch_size)
    ->execute();

  $processed = 0;

  foreach ($ids as $id) {
    $node = $node_storage->load($id);

    if ($node instanceof FieldableEntityInterface && $node->hasField('field_read_time')) {
      $minutes = max(1, (int) round(_do_base_count_words($node) / 200));
      $node->set('field_read_time', sprintf('%d min read', $minutes));
      $node->save();
    }

    $processed++;
  }

  $backfilled = (int) ($sandbox['backfilled'] ?? 0) + $processed;
  $sandbox['backfilled'] = $backfilled;
  $sandbox['#finished'] = $processed === 0 ? 1 : 0;

  return sprintf('Set the read time on %d blog post(s).', $backfilled);
}

/**
 * Add the "From the blog" teaser to the front page when it is not present.
 */
function do_base_deploy_homepage_blog_teaser(): string {
  $front = \Drupal::config('system.site')->get('page.front');

  if (empty($front)) {
    return 'No front page is configured; skipped the homepage blog teaser.';
  }

  $path = \Drupal::service('path_alias.manager')->getPathByAlias($front);

  if (!preg_match('#^/node/(\d+)$#', $path, $matches)) {
    return 'The front page is not a node; skipped the homepage blog teaser.';
  }

  $node = \Drupal::entityTypeManager()->getStorage('node')->load((int) $matches[1]);

  if (!$node instanceof FieldableEntityInterface || !$node->hasField('field_c_n_components')) {
    return 'The front page has no components field; skipped the homepage blog teaser.';
  }

  // Idempotency guard: identify our teaser by its title so re-runs do not add a
  // second copy.
  foreach ($node->get('field_c_n_components')->referencedEntities() as $component) {
    if ($component->bundle() === 'civictheme_automated_list' && $component->hasField('field_c_p_title') && $component->get('field_c_p_title')->value === 'From the blog') {
      return 'The homepage blog teaser is already present.';
    }
  }

  $term = _do_base_ensure_blog_term();

  if (!$term instanceof TermInterface) {
    return 'The Blog topic term is unavailable; skipped the homepage blog teaser.';
  }

  // Mirror the configuration of the /blog listing so the teaser renders the
  // same promo cards, limited to the three latest posts with a link through to
  // the full listing.
  $teaser = Paragraph::create([
    'type' => 'civictheme_automated_list',
    'field_c_p_title' => 'From the blog',
    'field_c_p_theme' => 'dark',
    'field_c_p_vertical_spacing' => 'both',
    'field_c_p_list_type' => 'civictheme_automated_list__block1',
    'field_c_p_list_content_type' => 'civictheme_page',
    'field_c_p_list_topics' => ['target_id' => $term->id()],
    'field_c_p_list_column_count' => 3,
    'field_c_p_list_item_view_as' => 'civictheme_promo_card',
    'field_c_p_list_item_theme' => 'dark',
    'field_c_p_list_limit_type' => 'limited',
    'field_c_p_list_limit' => 3,
    'field_c_p_list_link_above' => ['uri' => 'internal:/blog', 'title' => 'All articles'],
  ]);
  $teaser->save();

  $node->get('field_c_n_components')->appendItem($teaser);
  $node->save();

  return 'Added the "From the blog" teaser to the front page.';
}

/**
 * Load the Blog topic term, creating it when the vocabulary allows.
 */
function _do_base_ensure_blog_term(): ?TermInterface {
  $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

  $existing = $storage->loadByProperties([
    'vid' => 'civictheme_topics',
    'name' => 'Blog',
  ]);

  if (!empty($existing)) {
    return reset($existing);
  }

  if (!Vocabulary::load('civictheme_topics')) {
    return NULL;
  }

  $term = $storage->create(['vid' => 'civictheme_topics', 'name' => 'Blog']);
  $term->save();

  return $term;
}

/**
 * Count words across an entity's text fields, recursing into its paragraphs.
 */
function _do_base_count_words(FieldableEntityInterface $entity): int {
  $text_types = ['string', 'string_long', 'text', 'text_long', 'text_with_summary'];
  $count = 0;

  foreach ($entity->getFields() as $field) {
    $type = $field->getFieldDefinition()->getType();

    if (in_array($type, $text_types, TRUE)) {
      foreach ($field as $item) {
        $count += str_word_count(strip_tags((string) ($item->value ?? '')));
      }
    }
    elseif ($type === 'entity_reference_revisions') {
      foreach ($field->referencedEntities() as $child) {
        if ($child instanceof FieldableEntityInterface) {
          $count += _do_base_count_words($child);
        }
      }
    }
  }

  return $count;
}
