<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\drupal_helpers\Helper;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Flip every component paragraph to the dark theme.
 *
 * The design renders the whole site dark. CivicTheme themes each component
 * individually through the shared `field_c_p_theme` field, and list components
 * theme their rendered items separately through `field_c_p_list_item_theme`, so
 * this switches both - across every bundle - to `dark`. The revision is updated
 * in place so the referencing entity revision keeps resolving to it. Content
 * created by later deploy hooks is built dark directly.
 */
function do_base_deploy_components_dark(?array &$sandbox): ?string {
  $fields = ['field_c_p_theme', 'field_c_p_list_item_theme'];

  return Helper::entity($sandbox)->batchEntity('paragraph', NULL, static function ($paragraph) use ($fields): void {
    $changed = FALSE;

    foreach ($fields as $field) {
      if (!$paragraph->hasField($field)) {
        continue;
      }

      if ($paragraph->get($field)->value === 'dark') {
        continue;
      }

      $paragraph->set($field, 'dark');
      $changed = TRUE;
    }

    if (!$changed) {
      return;
    }

    $paragraph->setNewRevision(FALSE);
    $paragraph->save();
  });
}

/**
 * Flip CivicTheme block content to the dark theme.
 *
 * Page-chrome blocks (the mobile navigation drawer, banner, social links)
 * carry their own `field_c_b_theme`, and the mobile navigation also themes
 * its trigger through `field_c_b_trigger_theme`. The mobile navigation ships
 * light, so it ignored the dark design; switch every block - and its
 * trigger - to dark so the whole site, including the mobile drawer, is dark.
 */
function do_base_deploy_blocks_dark(?array &$sandbox): ?string {
  $fields = ['field_c_b_theme', 'field_c_b_trigger_theme'];

  return Helper::entity($sandbox)->batchEntity('block_content', NULL, static function ($block) use ($fields): void {
    $changed = FALSE;

    foreach ($fields as $field) {
      if (!$block->hasField($field)) {
        continue;
      }

      if ($block->get($field)->value === 'dark') {
        continue;
      }

      $block->set($field, 'dark');
      $changed = TRUE;
    }

    if (!$changed) {
      return;
    }

    $block->setNewRevision(FALSE);
    $block->save();
  });
}

/**
 * Rebuild the homepage from CivicTheme components.
 *
 * The hero is the node banner (populated, not cleared). Every section is built
 * from CivicTheme paragraph components - manual lists of snippets for the
 * services, stats, trust, "why" and process sections, and a callout for the
 * closing CTA - with the bespoke design treatments driven by `field_do_style`.
 * No markup is stored as content. New references are saved before the previous
 * paragraphs are deleted, so a failed save never leaves dangling references.
 */
function do_base_deploy_homepage(): string {
  // Resolve the configured front page rather than assuming a fixed node ID.
  $front = (string) \Drupal::config('system.site')->get('page.front');
  $node = preg_match('#^/node/(\d+)$#', $front, $matches) ? Node::load((int) $matches[1]) : NULL;

  if (!$node instanceof Node || !$node->hasField('field_c_n_components')) {
    return 'Homepage node not found - skipped.';
  }

  $components = [
    _do_base_manual_list('What we do', 1, 'numbered', [
      _do_base_snippet('Website Delivery', "Full Drupal website builds delivered with automated testing, CI/CD pipelines, and production-ready infrastructure. Your team gets a solid platform, not a prototype that needs fixing after launch."),
      _do_base_snippet('Ongoing Support', "Proactive platform maintenance from the same senior engineers who built it. Security updates, monitoring, continuous improvement, and direct communication with no layers in between."),
      _do_base_snippet('Upgrades & Migrations', "Drupal 7 and 9 are end-of-life. We handle the full migration with test coverage and zero-downtime deployments, so your organisation stays compliant and your users stay unaffected."),
    ]),
    _do_base_manual_list('The essentials', 4, 'stat', [
      _do_base_snippet('0', 'Juniors on your project'),
      _do_base_snippet('0', 'Excuses when something breaks'),
      _do_base_snippet('1 day', 'To set up CI/CD on a new project'),
      _do_base_snippet('0', 'Shortcuts in how we deliver'),
    ]),
    _do_base_manual_list("Trusted on projects where failure isn't an option.", 4, 'trust', [
      _do_base_snippet('Victorian Government', "Delivered Australia's first Docker-based government Drupal platform."),
      _do_base_snippet('Australian Defence', 'Multiple classified platforms with complex security and compliance requirements.'),
      _do_base_snippet('GovCMS', "Drupal platform delivery on Australia's government hosting infrastructure."),
      _do_base_snippet('Education', 'University platforms with ongoing support, leading to internal referrals across departments.'),
    ]),
    _do_base_manual_list('No filler. No overhead. Just good engineering.', 1, 'dotted', [
      _do_base_snippet('Automated testing is not optional', "Every platform ships with a full test suite. Functional, unit, and visual regression tests run on every commit. If it's not tested, it doesn't deploy."),
      _do_base_snippet('One team, zero handovers', 'We handle development, DevOps, and production support. One team with full context, no vendors blaming each other, no knowledge lost between handoffs.'),
      _do_base_snippet('Pricing that makes sense', "Flat-rate pricing with standard and rapid response options. We'll tell you what it costs upfront. No retainer games, no billable surprises, no markup on markup."),
      _do_base_snippet('Direct line to the engineers', 'You talk to the people building your platform. We manage the project without adding layers between you and the work. Fast communication, honest updates, no runaround.'),
    ]),
    _do_base_manual_list('A clear path from kickoff to ongoing support.', 1, 'numbered', [
      _do_base_snippet('Discovery', 'We review your website, understand your requirements and constraints, and scope the work. You get a clear proposal with flat-rate pricing before any work begins.'),
      _do_base_snippet('Delivery', 'Your site is built with automated testing and CI/CD from the first commit. Regular check-ins, transparent progress reporting, and no surprises at the end.'),
      _do_base_snippet('Ongoing support', 'The same senior team that built your site maintains it. Security updates, continuous improvement, and proactive monitoring on a prepaid support arrangement.'),
    ]),
    _do_base_callout("Let's talk about your website.", "Tell us where things stand, what's working, and what's not. We'll be straight with you about whether we're the right fit.", 'info@drevops.com', 'mailto:info@drevops.com'),
  ];

  foreach ($components as $component) {
    $component->save();
  }

  $stale = array_merge(
    _do_base_stage_banner($node, "Your website can't afford to wait."),
    $node->get('field_c_n_components')->referencedEntities()
  );
  $node->set('field_c_n_components', $components);
  $node->save();
  _do_base_delete_entities($stale);

  return 'Homepage rebuilt.';
}

/**
 * Rebuild the Services page to the design.
 */
function do_base_deploy_services(): string {
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
    'uuid' => 'b78dd34e-e1b2-480a-9056-80902d410008',
  ]);
  $node = reset($nodes);

  if (!$node instanceof Node) {
    return 'Services node not found - skipped.';
  }

  $stale = array_merge(_do_base_stage_clear_banner($node), _do_base_stage_components($node, 'services'));
  $node->save();
  _do_base_delete_entities($stale);

  return 'Services page rebuilt.';
}

/**
 * Rebuild the Contact page to the design.
 *
 * Keeps the existing contact webform (rendered through a CivicTheme webform
 * paragraph) between the hero and the contact-detail sections.
 */
function do_base_deploy_contact(): string {
  $path = \Drupal::service('path_alias.manager')->getPathByAlias('/contact');

  if (!preg_match('#^/node/(\d+)$#', $path, $matches)) {
    return 'Contact node not found - skipped.';
  }

  $node = Node::load((int) $matches[1]);

  if (!$node instanceof Node || !$node->hasField('field_c_n_components')) {
    return 'Contact node not usable - skipped.';
  }

  // Build the content first so a missing content directory aborts before
  // anything is staged. The hero leads, then the webform, then the contact
  // detail sections.
  $sections = _do_base_html_paragraphs('contact');
  $hero = array_shift($sections);

  $webform = Paragraph::create([
    'type' => 'civictheme_webform',
    'field_c_p_theme' => 'dark',
    'field_c_p_webform' => 'contact',
  ]);
  $webform->save();

  $stale = array_merge(
    _do_base_stage_clear_banner($node),
    $node->get('field_c_n_components')->referencedEntities()
  );
  $node->set('field_c_n_components', array_values(array_filter([$hero, $webform, ...$sections])));
  $node->save();
  _do_base_delete_entities($stale);

  return 'Contact page rebuilt.';
}

/**
 * Seed the design demo blog article.
 *
 * Idempotent: matches the existing article by title, otherwise creates it.
 */
function do_base_deploy_blog_demo(): string {
  $title = 'Why Your Drupal CI Pipeline Is Slower Than It Should Be';
  $storage = \Drupal::entityTypeManager()->getStorage('node');

  $existing = $storage->loadByProperties([
    'type' => 'civictheme_page',
    'title' => $title,
  ]);

  $node = $existing ? reset($existing) : Node::create([
    'type' => 'civictheme_page',
    'title' => $title,
  ]);

  $node->set('status', 1);

  if ($node->hasField('field_c_n_banner_title')) {
    $node->set('field_c_n_banner_title', $title);
  }

  $stale = _do_base_stage_components($node, 'blog');
  $node->save();
  _do_base_delete_entities($stale);

  return 'Blog demo article seeded.';
}

/**
 * Stage design content sections on a node from a content directory.
 *
 * Builds the new component paragraphs and assigns them to the node, returning
 * the previously referenced components for deletion after the node is saved.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node to rebuild.
 * @param string $dir
 *   The content sub-directory under this module's content directory.
 *
 * @return \Drupal\Core\Entity\EntityInterface[]
 *   The previously referenced component paragraphs, pending deletion.
 */
function _do_base_stage_components(Node $node, string $dir): array {
  if (!$node->hasField('field_c_n_components')) {
    return [];
  }

  // Build the new components first; this throws if the content is missing, so
  // the node is never left without a replacement.
  $components = _do_base_html_paragraphs($dir);
  $previous = $node->get('field_c_n_components')->referencedEntities();
  $node->set('field_c_n_components', $components);

  return $previous;
}

/**
 * Stage an emptied banner on a node.
 *
 * Clears the banner title and references, returning the previous banner
 * paragraphs for deletion after the node is saved.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node whose banner should be emptied.
 *
 * @return \Drupal\Core\Entity\EntityInterface[]
 *   The previously referenced banner paragraphs, pending deletion.
 */
function _do_base_stage_clear_banner(Node $node): array {
  if ($node->hasField('field_c_n_banner_title')) {
    $node->set('field_c_n_banner_title', '');
  }

  $previous = [];

  foreach (['field_c_n_banner_components', 'field_c_n_banner_components_bott'] as $field) {
    if (!$node->hasField($field)) {
      continue;
    }

    $previous = array_merge($previous, $node->get($field)->referencedEntities());
    $node->set($field, []);
  }

  return $previous;
}

/**
 * Populate the node banner so it renders as the page hero.
 *
 * The design's hero is the CivicTheme intro banner (themed in the subtheme),
 * not a content section. This sets the banner title, type and theme and clears
 * any previous banner components, returning them for deletion after the save.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node whose banner becomes the hero.
 * @param string $title
 *   The hero heading.
 *
 * @return \Drupal\Core\Entity\EntityInterface[]
 *   The previously referenced banner paragraphs, pending deletion.
 */
function _do_base_stage_banner(Node $node, string $title): array {
  if ($node->hasField('field_c_n_banner_title')) {
    $node->set('field_c_n_banner_title', $title);
  }

  if ($node->hasField('field_c_n_banner_type')) {
    $node->set('field_c_n_banner_type', 'intro');
  }

  if ($node->hasField('field_c_n_banner_theme')) {
    $node->set('field_c_n_banner_theme', 'dark');
  }

  $previous = [];

  foreach (['field_c_n_banner_components', 'field_c_n_banner_components_bott'] as $field) {
    if (!$node->hasField($field)) {
      continue;
    }

    $previous = array_merge($previous, $node->get($field)->referencedEntities());
    $node->set($field, []);
  }

  return $previous;
}

/**
 * Build a dark CivicTheme snippet (title + summary) list item.
 *
 * @param string $title
 *   The snippet title.
 * @param string $summary
 *   The snippet summary.
 *
 * @return \Drupal\paragraphs\Entity\Paragraph
 *   An unsaved snippet paragraph.
 */
function _do_base_snippet(string $title, string $summary): Paragraph {
  return Paragraph::create([
    'type' => 'civictheme_snippet',
    'field_c_p_theme' => 'dark',
    'field_c_p_title' => $title,
    'field_c_p_summary' => $summary,
  ]);
}

/**
 * Build a dark CivicTheme manual list of snippet items.
 *
 * The `$style` is stored in `field_do_style`; the theme renders the bespoke
 * design treatment (numbered, stat, trust, dotted) from a single list modifier
 * class, styling the snippet children with CSS - no markup is stored.
 *
 * @param string $title
 *   The section heading.
 * @param int $columns
 *   Column count (1-4).
 * @param string $style
 *   The design style key (numbered, stat, trust, dotted).
 * @param \Drupal\paragraphs\Entity\Paragraph[] $items
 *   The snippet list items.
 *
 * @return \Drupal\paragraphs\Entity\Paragraph
 *   An unsaved manual list paragraph referencing the saved items.
 */
function _do_base_manual_list(string $title, int $columns, string $style, array $items): Paragraph {
  foreach ($items as $item) {
    $item->save();
  }

  return Paragraph::create([
    'type' => 'civictheme_manual_list',
    'field_c_p_theme' => 'dark',
    'field_c_p_title' => $title,
    'field_c_p_list_column_count' => $columns,
    'field_do_style' => $style,
    'field_c_p_list_items' => $items,
  ]);
}

/**
 * Build a dark CivicTheme callout (title + rich-text body + link).
 *
 * @param string $title
 *   The callout heading.
 * @param string $body
 *   The callout body, rendered through the rich-text format.
 * @param string $link_title
 *   The link text.
 * @param string $link_uri
 *   The link URI.
 *
 * @return \Drupal\paragraphs\Entity\Paragraph
 *   An unsaved callout paragraph.
 */
function _do_base_callout(string $title, string $body, string $link_title, string $link_uri): Paragraph {
  return Paragraph::create([
    'type' => 'civictheme_callout',
    'field_c_p_theme' => 'dark',
    'field_c_p_title' => $title,
    'field_c_p_content' => [
      'value' => $body,
      'format' => 'civictheme_rich_text',
    ],
    'field_c_p_links' => [
      ['uri' => $link_uri, 'title' => $link_title],
    ],
  ]);
}

/**
 * Delete entities staged for removal after the referencing node was saved.
 *
 * @param \Drupal\Core\Entity\EntityInterface[] $entities
 *   Entities to delete.
 */
function _do_base_delete_entities(array $entities): void {
  foreach ($entities as $entity) {
    $entity->delete();
  }
}

/**
 * Build full-width dark content paragraphs from a content directory's markup.
 *
 * @param string $dir
 *   The content sub-directory under this module's content directory.
 *
 * @return \Drupal\paragraphs\Entity\Paragraph[]
 *   The created, saved paragraphs in filename order.
 */
function _do_base_html_paragraphs(string $dir): array {
  $path = \Drupal::service('extension.list.module')->getPath('do_base') . '/content/' . $dir;

  if (!is_dir($path)) {
    throw new \RuntimeException(sprintf('Design content directory not found: %s', $path));
  }

  $files = glob($path . '/*.html') ?: [];
  sort($files);

  if (empty($files)) {
    throw new \RuntimeException(sprintf('No design content files found in: %s', $path));
  }

  $paragraphs = [];

  foreach ($files as $file) {
    $html = file_get_contents($file);

    if ($html === FALSE) {
      throw new \RuntimeException(sprintf('Failed to read design content file: %s', $file));
    }

    // The content/ partials are trusted, version-controlled markup authored
    // by developers - not user input. The `full_html` format stores them
    // verbatim; Drupal core still strips <script> and on* via its XSS filter.
    $paragraph = Paragraph::create([
      'type' => 'civictheme_content',
      'field_c_p_theme' => 'dark',
      'field_c_p_content' => [
        'value' => trim($html),
        'format' => 'full_html',
      ],
    ]);
    $paragraph->save();

    $paragraphs[] = $paragraph;
  }

  return $paragraphs;
}
