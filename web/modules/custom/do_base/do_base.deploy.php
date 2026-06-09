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
 * Rebuild the homepage to the redesign.
 *
 * The hero is the first content section, so the node banner is cleared. New
 * references are saved before the previous paragraphs are deleted, so a failed
 * save can never leave the node pointing at deleted entities.
 */
function do_base_deploy_homepage(): string {
  // Resolve the configured front page rather than assuming a fixed node ID.
  $front = (string) \Drupal::config('system.site')->get('page.front');
  $node = preg_match('#^/node/(\d+)$#', $front, $matches) ? Node::load((int) $matches[1]) : NULL;

  if (!$node instanceof Node) {
    return 'Homepage node not found - skipped.';
  }

  $stale = array_merge(_do_base_stage_clear_banner($node), _do_base_stage_components($node, 'homepage'));
  $node->save();
  _do_base_delete_entities($stale);

  return 'Homepage rebuilt.';
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

  $stale = array_merge(_do_base_stage_clear_banner($node), _do_base_stage_components($node, 'services'));
  $node->save();
  _do_base_delete_entities($stale);

  return 'Services page rebuilt.';
}

/**
 * Rebuild the Contact page to the redesign.
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

  $stale = _do_base_stage_components($node, 'blog');
  $node->save();
  _do_base_delete_entities($stale);

  return 'Blog demo article seeded.';
}

/**
 * Stage redesign content sections on a node from a content directory.
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
