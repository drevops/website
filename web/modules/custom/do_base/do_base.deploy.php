<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\civictheme\CivicthemeColorManager;
use Drupal\civictheme\CivicthemeConstants;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
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
 * Assemble the front page from the shared components.
 */
function do_base_deploy_homepage_assemble(): string {
  $node = _do_base_resolve_front_page_node();

  if (!$node->hasField('field_c_n_components')) {
    return 'The front page has no components field; skipped the homepage assembly.';
  }

  // A hero opener as the first component means the page is already assembled,
  // so a re-run leaves the editor's later changes untouched.
  $existing = $node->get('field_c_n_components')->referencedEntities();
  $first = reset($existing);
  if ($first instanceof ParagraphInterface && $first->bundle() === 'hero') {
    return 'The homepage is already assembled.';
  }

  $sections = array_filter([
    _do_base_homepage_paragraph([
      'type' => 'hero',
      'field_c_p_type' => 'home',
      'field_c_p_subtitle' => 'Reliable websites, delivered faster',
      'field_c_p_title' => "Your website can't afford to wait.",
      'field_c_p_summary' => 'We build and support reliable websites for businesses and organisations that depend on them. Now delivered faster with AI-assisted development.',
      'field_c_p_links' => [['uri' => 'internal:/contact', 'title' => 'Talk to us']],
      'field_c_p_theme' => 'dark',
    ]),
    _do_base_homepage_paragraph([
      'type' => 'hero',
      'field_c_p_type' => 'section',
      'field_c_p_title' => 'For teams whose website is real infrastructure.',
      'field_c_p_summary' => "Your website isn't a brochure. It's where a lot of your work actually happens, which means it needs to be built properly and looked after over time. That's the work we do: complete, reliable websites for mid-market organisations and the teams who depend on them. And if we're not the right fit, we'll say so.",
      'field_c_p_theme' => 'dark',
    ]),
    _do_base_build_homepage_card_group(1, [
      _do_base_homepage_card('number', 'Website Delivery', "We build your site with automated testing and CI/CD from the first commit, so what launches is solid from day one, not a prototype you'll be fixing after go-live. AI-assisted delivery gets you there faster, at the same tested standard."),
      _do_base_homepage_card('number', 'Ongoing Support', 'Proactive maintenance from the people who built your platform. Security updates, monitoring, continuous improvement, and a direct line with no layers in between.'),
      _do_base_homepage_card('number', 'Upgrades & Migrations', 'Running an end-of-life Drupal 7 or 9 site? We handle the full migration with test coverage and zero-downtime deployments, so you stay compliant and your users never notice the switch.'),
      _do_base_homepage_card('number', 'Website as a Service', 'A professional, managed website for smaller organisations. A proven build, then hosting, security updates, and maintenance handled for a flat monthly fee. No lock-in, no surprises, and a simple way in that can grow into more.'),
    ]),
    _do_base_build_homepage_card_group(1, [
      _do_base_homepage_card('dot', 'The same standard, in fewer hours', 'AI takes on the repetitive production work, so a build that used to take weeks can take days. We quote it both ways and you decide. The tests, the CI, and the code you can read on GitHub all stay the same.'),
      _do_base_homepage_card('dot', 'Wondering whether AI-written code can be trusted?', "It's a fair thing to ask. Here's how we think about it: every change is still reviewed, and every build is tested and gated by CI before it ships. AI helps with the writing, never the checking. The guardrails that make this safe are our own, and they're open source, so you can see exactly how they work."),
      _do_base_homepage_card('dot', 'Your code and data stay yours', "Nothing you share trains an AI model, and nothing goes to a public AI service without your say-so. We've written down exactly how we handle it in our Responsible AI policy."),
    ]),
    _do_base_homepage_paragraph([
      'type' => 'campaign',
      'field_subtitle' => 'For teams already on Drupal',
      'field_title' => 'Curious what your next project would cost with us?',
      'field_content' => [
        'value' => "Send us a recent quote, your current scope, or just a link to your site. We'll show you what the same work would cost with us, by hand and AI-assisted, so you can see the difference for yourself. There's no commitment, and nothing to move. Sometimes it just helps to know what your options are.",
        'format' => 'civictheme_rich_text',
      ],
      'field_link' => [
        ['uri' => 'internal:/contact', 'title' => 'See what it would cost'],
        ['uri' => 'internal:/ai-integration-automation', 'title' => 'See how we work'],
      ],
      'field_c_p_theme' => 'dark',
    ]),
    _do_base_build_homepage_stat('The essentials', [
      ['value' => '1', 'suffix' => ' day', 'label' => 'To set up CI/CD on a new project'],
      ['value' => '10', 'suffix' => ' yrs', 'label' => 'Delivering reliable platforms'],
      ['value' => '40', 'suffix' => '+', 'label' => 'Open-source tools we maintain'],
      ['value' => '100', 'suffix' => '%', 'label' => 'Of projects ship with automated tests'],
    ]),
    _do_base_build_homepage_card_group(2, [
      _do_base_homepage_card('icon', 'Victorian Government', "Delivered Australia's first Docker-based government Drupal platform.", _do_base_ensure_homepage_icon('Homepage track record: Government', 'government.svg')),
      _do_base_homepage_card('icon', 'Australian Defence', 'Multiple classified platforms with complex security and compliance requirements.', _do_base_ensure_homepage_icon('Homepage track record: Defence', 'defence.svg')),
      _do_base_homepage_card('icon', 'GovCMS', "Drupal platform delivery on Australia's government hosting infrastructure.", _do_base_ensure_homepage_icon('Homepage track record: GovCMS', 'govcms.svg')),
      _do_base_homepage_card('icon', 'Education', 'University platforms with ongoing support, leading to internal referrals across departments.', _do_base_ensure_homepage_icon('Homepage track record: Education', 'education.svg')),
    ]),
    _do_base_build_homepage_card_group(1, [
      _do_base_homepage_card('dot', 'Automated testing is not optional', "Every platform ships with a full test suite. Functional, unit, and visual regression tests run on every commit. If it's not tested, it doesn't deploy."),
      _do_base_homepage_card('dot', 'One team, zero handovers', 'We handle development, DevOps, and production support. One team with full context, no vendors blaming each other, no knowledge lost between handoffs.'),
      _do_base_homepage_card('dot', 'Pricing that makes sense', "Flat-rate pricing with standard and rapid response options. We'll tell you what it costs upfront. No retainer games, no billable surprises, no markup on markup."),
      _do_base_homepage_card('dot', 'Direct line to the engineers', 'You talk to the people building your platform. We manage the project without adding layers between you and the work. Fast communication, honest updates, no runaround.'),
    ]),
    _do_base_build_homepage_card_group(1, [
      _do_base_homepage_card('number', 'Discovery', 'We review your website, understand your requirements and constraints, and scope the work, including whether AI-assisted delivery is the right fit. You get a clear proposal with flat-rate pricing before any work begins.'),
      _do_base_homepage_card('number', 'Delivery', 'Your site is built with automated testing and CI/CD from the first commit, with AI accelerating the production work and every change reviewed before it lands. Regular check-ins, transparent progress, and no surprises at the end.'),
      _do_base_homepage_card('number', 'Ongoing support', 'The same people who built your site maintain it. Security updates, continuous improvement, and proactive monitoring on a prepaid support arrangement.'),
    ]),
    _do_base_build_homepage_blog_teaser(),
    _do_base_homepage_paragraph([
      'type' => 'cta',
      'field_c_p_type' => 'display',
      'field_title' => "Let's talk about your website.",
      'field_subtitle' => "Tell us where things stand, what's working, and what's not. We'll be straight with you about whether we're the right fit.",
      'field_link' => [
        ['uri' => 'internal:/contact', 'title' => 'Start a conversation'],
        ['uri' => 'internal:/ai-integration-automation', 'title' => 'See what it would cost'],
        ['uri' => 'mailto:info@drevops.com', 'title' => 'info@drevops.com'],
      ],
      'field_c_p_theme' => 'dark',
    ]),
  ]);

  $references = [];
  foreach ($sections as $section) {
    $references[] = ['target_id' => $section->id(), 'target_revision_id' => $section->getRevisionId()];
  }

  // The hero leads the page, so the inherited CivicTheme banner is emptied here
  // and the banner block is hidden on the front page through its visibility.
  $banner_fields = [
    'field_c_n_banner_title',
    'field_c_n_banner_type',
    'field_c_n_banner_background',
    'field_c_n_banner_blend_mode',
    'field_c_n_banner_components',
  ];

  foreach ($banner_fields as $banner_field) {
    if ($node->hasField($banner_field)) {
      $node->set($banner_field, NULL);
    }
  }

  $node->set('field_c_n_components', $references);
  $node->save();

  return sprintf('Assembled the homepage from %d shared components.', count($references));
}

/**
 * Resolve the front-page node, creating one when the site has none.
 */
function _do_base_resolve_front_page_node(): NodeInterface {
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $front = (string) \Drupal::config('system.site')->get('page.front');

  if ($front !== '') {
    $path = \Drupal::service('path_alias.manager')->getPathByAlias($front);

    if (preg_match('#^/node/(\d+)$#', $path, $matches)) {
      $node = $node_storage->load((int) $matches[1]);

      if ($node instanceof NodeInterface) {
        return $node;
      }
    }
  }

  $node = $node_storage->create(['type' => 'civictheme_page', 'title' => 'Homepage', 'status' => 1]);
  $node->save();

  \Drupal::configFactory()->getEditable('system.site')->set('page.front', '/node/' . $node->id())->save();

  return $node;
}

/**
 * Create and save a homepage section paragraph from a values array.
 */
function _do_base_homepage_paragraph(array $values): Paragraph {
  $paragraph = Paragraph::create($values);
  $paragraph->save();

  return $paragraph;
}

/**
 * Build a card definition for a homepage card group.
 *
 * The icon is an optional CivicTheme icon media id used by the icon variant;
 * the number and dot variants leave it unset.
 */
function _do_base_homepage_card(string $type, string $title, string $summary, ?int $icon = NULL): array {
  return [
    'type' => $type,
    'title' => $title,
    'summary' => $summary,
    'icon' => $icon,
  ];
}

/**
 * Build a card group paragraph from a list of card definitions.
 *
 * Each card definition is an array of 'type', 'title', 'summary' and an
 * optional 'icon' media id. Cards inherit the group's dark scheme and the card
 * group renders the positional number marker from their order.
 */
function _do_base_build_homepage_card_group(int $columns, array $cards): Paragraph {
  $references = [];

  foreach ($cards as $card) {
    $values = [
      'type' => 'card',
      'field_c_p_type' => $card['type'],
      'field_c_p_title' => $card['title'],
      'field_c_p_summary' => $card['summary'],
      'field_c_p_theme' => 'dark',
    ];

    if (!empty($card['icon'])) {
      $values['field_c_p_icon'] = ['target_id' => $card['icon']];
    }

    $paragraph = _do_base_homepage_paragraph($values);
    $references[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
  }

  return _do_base_homepage_paragraph([
    'type' => 'card_group',
    'field_c_p_list_column_count' => $columns,
    'field_c_p_theme' => 'dark',
    'field_c_p_list_items' => $references,
  ]);
}

/**
 * Build a stat grid paragraph from a list of stat item definitions.
 *
 * Each item is an array of 'value', 'suffix' and 'label'; the value seeds the
 * count-up animation target.
 */
function _do_base_build_homepage_stat(string $subtitle, array $items): Paragraph {
  $references = [];

  foreach ($items as $item) {
    $paragraph = _do_base_homepage_paragraph([
      'type' => 'stat_item',
      'field_stat_value' => $item['value'],
      'field_stat_suffix' => $item['suffix'],
      'field_stat_label' => $item['label'],
    ]);
    $references[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
  }

  return _do_base_homepage_paragraph([
    'type' => 'stat',
    'field_subtitle' => $subtitle,
    'field_c_p_theme' => 'dark',
    'field_items' => $references,
  ]);
}

/**
 * Build the "From the blog" automated list teaser for the front page.
 *
 * Mirrors the /blog listing so the teaser renders the same promo cards, limited
 * to the three latest posts with a link through to the full listing.
 */
function _do_base_build_homepage_blog_teaser(): ?Paragraph {
  $term = _do_base_ensure_blog_term();

  if (!$term instanceof TermInterface) {
    return NULL;
  }

  return _do_base_homepage_paragraph([
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
}

/**
 * Load or create a CivicTheme icon media from a shipped SVG asset.
 *
 * Returns the media id, or NULL when the icon cannot be created so the card
 * still renders without an icon marker rather than aborting the deployment.
 */
function _do_base_ensure_homepage_icon(string $name, string $filename): ?int {
  try {
    $media_storage = \Drupal::entityTypeManager()->getStorage('media');

    $existing = $media_storage->loadByProperties(['bundle' => 'civictheme_icon', 'name' => $name]);
    if (!empty($existing)) {
      return (int) reset($existing)->id();
    }

    $source = \Drupal::service('extension.list.module')->getPath('do_base') . '/assets/homepage-icons/' . $filename;
    if (!is_file($source)) {
      return NULL;
    }

    $file_system = \Drupal::service('file_system');
    $directory = 'public://homepage-icons';
    if (!$file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      return NULL;
    }

    $uri = $file_system->copy($source, $directory . '/' . $filename, FileExists::Replace);

    $file = File::create(['uri' => $uri, 'status' => 1]);
    $file->save();

    $media = Media::create([
      'bundle' => 'civictheme_icon',
      'name' => $name,
      'status' => 1,
      'field_c_m_icon' => ['target_id' => $file->id()],
    ]);
    $media->save();

    return (int) $media->id();
  }
  catch (\Throwable) {
    return NULL;
  }
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
