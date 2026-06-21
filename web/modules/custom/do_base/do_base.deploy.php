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

  // Collect the backlog once, then drain it one batch per pass. Tracking the
  // ids in the sandbox (rather than re-querying for an empty read time) means
  // posts that are intentionally left blank do not keep reappearing, so the
  // hook still terminates. Editors can override the estimate at any time.
  if (!isset($sandbox['ids'])) {
    $sandbox['ids'] = array_values($node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'civictheme_page')
      ->condition('field_c_n_topics', $term->id())
      ->notExists('field_read_time')
      ->execute());
    $sandbox['backfilled'] = 0;
  }

  foreach (array_splice($sandbox['ids'], 0, 25) as $id) {
    $node = $node_storage->load($id);

    if ($node instanceof FieldableEntityInterface && $node->hasField('field_read_time')) {
      $words = _do_base_count_words($node);

      // Skip empty posts rather than label them "1 min read".
      if ($words > 0) {
        $minutes = max(1, (int) round($words / 200));
        $node->set('field_read_time', sprintf('%d min read', $minutes));
        $node->save();
        $sandbox['backfilled']++;
      }
    }
  }

  $sandbox['#finished'] = empty($sandbox['ids']) ? 1 : 0;

  return sprintf('Set the read time on %d blog post(s).', $sandbox['backfilled']);
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
 * Seed the Services page assembled from the shared components.
 */
function do_base_deploy_seed_services_page(): string {
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');

  // Reassemble the existing Services page when present so its /services alias
  // and revision history are preserved; create it only on a site that has none.
  $existing = $node_storage->loadByProperties([
    'type' => 'civictheme_page',
    'title' => 'Services',
  ]);
  $node = $existing ? reset($existing) : $node_storage->create([
    'type' => 'civictheme_page',
    'title' => 'Services',
    'status' => 1,
    'path' => ['alias' => '/services', 'pathauto' => 0],
  ]);

  // The components the page carried before the rebuild are deleted afterwards
  // so swapping the stack does not leave orphaned paragraphs behind.
  $superseded = $node->get('field_c_n_components')->referencedEntities();

  // Building, attaching and cleaning up run inside one transaction so a failure
  // part way through rolls back rather than leaving half-saved paragraphs
  // orphaned instead of attached to the page.
  $transaction = \Drupal::database()->startTransaction();

  try {
    $components = _do_base_build_services_components();

    $node->set('field_c_n_components', array_map(static fn (Paragraph $component): array => [
      'target_id' => $component->id(),
      'target_revision_id' => $component->getRevisionId(),
    ], $components));
    $node->save();

    foreach ($superseded as $paragraph) {
      $paragraph->delete();
    }
  }
  catch (\Throwable $exception) {
    $transaction->rollBack();

    throw $exception;
  }

  return sprintf('Assembled the Services page (node %s) from %d components.', $node->id(), count($components));
}

/**
 * Build and save the Services page components in their render order.
 *
 * @return \Drupal\paragraphs\Entity\Paragraph[]
 *   The saved hero, service-detail, card-list and call-to-action paragraphs.
 */
function _do_base_build_services_components(): array {
  $save_paragraph = static function (array $values): Paragraph {
    $paragraph = Paragraph::create($values);
    $paragraph->save();

    return $paragraph;
  };

  $components = [];

  $components[] = $save_paragraph([
    'type' => 'hero',
    'field_c_p_theme' => 'dark',
    'field_c_p_type' => 'inner',
    'field_c_p_subtitle' => 'What we do',
    'field_c_p_title' => 'Engineering that keeps your platform running.',
    'field_c_p_summary' => "We build, support, and upgrade reliable websites for businesses and organisations that can't afford downtime, security gaps, or slow delivery. Now faster, with AI-assisted development at the same tested standard.",
  ]);

  $services = [
    [
      'title' => 'Website Delivery',
      'tagline' => 'From requirements to production in one engagement.',
      'description' => "<p>We build your site end to end, with automated testing and CI/CD baked in from the first commit. Architecture, development, design, and deployment handled together, so what launches is solid, not a prototype you'll be fixing after go-live.</p><p>AI-assisted delivery gets you there faster, at the same tested standard. Every project ships with a complete test suite, documentation, and a handover that actually works.</p>",
      'includes' => [
        'Architecture and technical planning',
        'Custom design and development',
        'Automated testing on every change',
        'Continuous integration and deployment',
        'Content migration and data import',
        'Hosting setup and go-live support',
      ],
      'price_label' => 'Pricing',
      'price' => 'Fixed price, agreed up front',
      'action' => 'Discuss your project',
    ],
    [
      'title' => 'Ongoing Support',
      'tagline' => 'The people who built your site, keeping it running.',
      'description' => '<p>Proactive maintenance from the people who built your site. Security updates, performance monitoring, and continuous improvement, all on a predictable prepaid arrangement.</p><p>No ticket queues, no outsourced support desks. You talk directly to the people who know your code.</p>',
      'includes' => [
        'Security patches and platform updates',
        'Uptime and performance monitoring',
        'Bug fixes and minor enhancements',
        'Monthly reporting and recommendations',
        'A direct line, no ticket queue',
        'Priority response for critical issues',
      ],
      'price_label' => 'Support',
      'price' => 'Prepaid, month to month',
      'action' => 'Get a support quote',
    ],
    [
      'title' => 'Upgrades & Migrations',
      'tagline' => 'Move off end-of-life Drupal without breaking anything.',
      'description' => '<p>Drupal 7 and 9 are end-of-life. Drupal 10 follows in December 2026. We handle the full migration with test coverage and zero-downtime deployments, so your organisation stays compliant and your users stay unaffected.</p><p>We assess your current platform, map out compatibility, migrate your custom code, and deliver an upgraded site with full test coverage, with AI speeding up the heavy lifting.</p>',
      'includes' => [
        'Platform audit and risk assessment',
        'Module compatibility analysis',
        'Custom code migration and refactoring',
        'Data migration and content integrity checks',
        'Automated test suite for the upgraded site',
        'Zero-downtime deployment and rollback plan',
      ],
      'price_label' => 'Pricing',
      'price' => 'Fixed price after a free assessment',
      'action' => 'Book a free assessment',
    ],
  ];

  foreach ($services as $service) {
    $components[] = $save_paragraph([
      'type' => 'service_detail',
      'field_c_p_theme' => 'dark',
      'field_c_p_title' => $service['title'],
      'field_c_p_subtitle' => $service['tagline'],
      'field_c_p_content' => [
        'value' => $service['description'],
        'format' => 'civictheme_rich_text',
      ],
      'field_p_includes' => $service['includes'],
      'field_p_price_label' => $service['price_label'],
      'field_p_price' => $service['price'],
      'field_c_p_link' => [
        'uri' => 'internal:/contact',
        'title' => $service['action'],
      ],
    ]);
  }

  $approach = [
    [
      'title' => 'AI-accelerated delivery',
      'description' => 'AI does the heavy lifting on production work, so you get the same tested quality in a fraction of the build time. Every change is still reviewed and tested before it ships.',
    ],
    [
      'title' => 'Flat-rate pricing',
      'description' => 'We quote a fixed price upfront. No hourly billing surprises, no retainer games, no scope creep charges.',
    ],
    [
      'title' => 'Tested by default',
      'description' => "Every platform ships with automated tests. If it's not tested, it doesn't deploy. No exceptions.",
    ],
    [
      'title' => 'Direct communication',
      'description' => 'You talk to the engineers building your site. No project managers relaying messages, no layers in between.',
    ],
  ];

  $cards = [];
  foreach ($approach as $point) {
    $card = $save_paragraph([
      'type' => 'card',
      'field_c_p_type' => 'dot',
      'field_c_p_theme' => 'dark',
      'field_c_p_title' => $point['title'],
      'field_c_p_summary' => $point['description'],
    ]);

    $cards[] = [
      'target_id' => $card->id(),
      'target_revision_id' => $card->getRevisionId(),
    ];
  }

  $components[] = $save_paragraph([
    'type' => 'card_group',
    'field_c_p_theme' => 'dark',
    'field_c_p_list_column_count' => 2,
    'field_c_p_list_items' => $cards,
  ]);

  $components[] = $save_paragraph([
    'type' => 'cta',
    'field_c_p_theme' => 'dark',
    'field_c_p_type' => 'display',
    'field_title' => 'Ready to talk about your platform?',
    'field_subtitle' => "Tell us where things stand. We'll be straight with you about whether we're the right fit.",
    'field_link' => [
      ['uri' => 'internal:/contact', 'title' => 'Get in touch'],
    ],
  ]);

  return $components;
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
