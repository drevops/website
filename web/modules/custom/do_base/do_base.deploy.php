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
 * The redesign renders the whole site dark. CivicTheme themes each component
 * individually through the shared `field_c_p_theme` field, so this switches all
 * existing paragraphs - across every bundle - to `dark`. The revision is
 * updated in place so the referencing entity revision keeps resolving to it.
 * Content created by later deploy hooks is built dark directly.
 */
function do_base_deploy_components_dark(?array &$sandbox): ?string {
  return Helper::entity($sandbox)->batchEntity('paragraph', NULL, static function ($paragraph): void {
    if (!$paragraph->hasField('field_c_p_theme')) {
      return;
    }

    if ($paragraph->get('field_c_p_theme')->value === 'dark') {
      return;
    }

    $paragraph->set('field_c_p_theme', 'dark');
    $paragraph->setNewRevision(FALSE);
    $paragraph->save();
  });
}

/**
 * Rebuild the homepage (node 1) to the redesign.
 *
 * Sets the hero (banner) copy and replaces the page components with the
 * redesign sections, each rendered as a full-width dark content paragraph from
 * the markup in this module's content/homepage directory.
 */
function do_base_deploy_homepage(): string {
  // Resolve the configured front page rather than assuming a fixed node ID.
  $front = (string) \Drupal::config('system.site')->get('page.front');
  $node = preg_match('#^/node/(\d+)$#', $front, $matches) ? Node::load((int) $matches[1]) : NULL;

  if (!$node instanceof Node) {
    return 'Homepage node not found - skipped.';
  }

  // The hero is the first content section (content/homepage/00-hero.html), so
  // clear the node banner to avoid a duplicate heading above it.
  _do_base_clear_banner($node);
  _do_base_set_components($node, 'homepage');
  $node->save();

  return 'Homepage rebuilt.';
}

/**
 * Empty a node's banner so it renders nothing.
 *
 * Clears the banner title and deletes any banner component paragraphs.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node whose banner should be emptied.
 */
function _do_base_clear_banner(Node $node): void {
  if ($node->hasField('field_c_n_banner_title')) {
    $node->set('field_c_n_banner_title', '');
  }

  foreach (['field_c_n_banner_components', 'field_c_n_banner_components_bott'] as $field) {
    if (!$node->hasField($field)) {
      continue;
    }

    foreach ($node->get($field)->referencedEntities() as $existing) {
      $existing->delete();
    }

    $node->set($field, []);
  }
}

/**
 * Rebuild the Services page to the redesign.
 */
function do_base_deploy_services(): string {
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
    'uuid' => 'b78dd34e-e1b2-480a-9056-80902d410008',
  ]);
  $node = reset($nodes);

  if (!$node instanceof Node) {
    return 'Services node not found - skipped.';
  }

  // The hero is the first content section, so clear the node banner.
  _do_base_clear_banner($node);
  _do_base_set_components($node, 'services');
  $node->save();

  return 'Services page rebuilt.';
}

/**
 * Rebuild the Contact page to the redesign.
 *
 * Keeps the existing contact webform and adds the redesign contact details
 * column. The webform is rendered through a CivicTheme webform paragraph.
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

  // The hero is the first content section, so clear the node banner.
  _do_base_clear_banner($node);

  // Build the content first so a missing content directory aborts before any
  // existing components are removed. The hero leads, then the webform, then the
  // remaining detail sections (contact info).
  $sections = _do_base_html_paragraphs('contact');
  $hero = array_shift($sections);

  foreach ($node->get('field_c_n_components')->referencedEntities() as $existing) {
    $existing->delete();
  }

  $webform = Paragraph::create([
    'type' => 'civictheme_webform',
    'field_c_p_theme' => 'dark',
    'field_c_p_webform' => 'contact',
  ]);
  $webform->save();

  $components = array_values(array_filter([$hero, $webform, ...$sections]));
  $node->set('field_c_n_components', $components);
  $node->save();

  return 'Contact page rebuilt.';
}

/**
 * Seed the redesign demo blog article.
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

  _do_base_set_components($node, 'blog');
  $node->save();

  return 'Blog demo article seeded.';
}

/**
 * Replace a node's components with full-width content paragraphs from markup.
 *
 * Existing components are deleted first so the hook is idempotent and does not
 * orphan paragraphs on re-run.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node to rebuild.
 * @param string $dir
 *   The content sub-directory under this module's content directory.
 */
function _do_base_set_components(Node $node, string $dir): void {
  if (!$node->hasField('field_c_n_components')) {
    return;
  }

  // Build the new components first; this throws if the content is missing, so
  // existing components are never deleted without a replacement.
  $components = _do_base_html_paragraphs($dir);

  foreach ($node->get('field_c_n_components')->referencedEntities() as $existing) {
    $existing->delete();
  }

  $node->set('field_c_n_components', $components);
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
    throw new \RuntimeException(sprintf('Redesign content directory not found: %s', $path));
  }

  $files = glob($path . '/*.html') ?: [];
  sort($files);

  if (empty($files)) {
    throw new \RuntimeException(sprintf('No redesign content files found in: %s', $path));
  }

  $paragraphs = [];

  foreach ($files as $file) {
    $html = file_get_contents($file);

    if ($html === FALSE) {
      throw new \RuntimeException(sprintf('Failed to read redesign content file: %s', $file));
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
