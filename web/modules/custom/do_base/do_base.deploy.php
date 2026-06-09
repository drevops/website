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
function do_base_deploy_components_dark(array &$sandbox): ?string {
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
  $node = Node::load(1);

  if (!$node instanceof Node) {
    return 'Homepage node (1) not found - skipped.';
  }

  if ($node->hasField('field_c_n_banner_title')) {
    $node->set('field_c_n_banner_title', "Your website can't afford to wait.");
  }

  if ($node->hasField('field_c_n_summary')) {
    $node->set('field_c_n_summary', 'We build and support Drupal websites for government, enterprise, and education. One senior team, predictable costs, tested code, and one point of accountability across your entire platform lifecycle.');
  }

  _do_base_set_components($node, 'homepage');
  $node->save();

  return 'Homepage rebuilt.';
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

  foreach ($node->get('field_c_n_components')->referencedEntities() as $existing) {
    $existing->delete();
  }

  $node->set('field_c_n_components', _do_base_html_paragraphs($dir));
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
  $files = glob($path . '/*.html') ?: [];
  sort($files);

  $paragraphs = [];

  foreach ($files as $file) {
    $html = file_get_contents($file);

    if ($html === FALSE) {
      continue;
    }

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
