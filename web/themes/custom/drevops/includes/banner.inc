<?php

/**
 * @file
 * DrevOps Banner paragraph component.
 */

declare(strict_types=1);

use Drupal\civictheme\CivicthemeConstants;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Markup;

/**
 * Pre-process for Banner block.
 *
 * @see _civictheme_preprocess_block__civictheme_banner()
 * @see https://www.drupal.org/project/civictheme/issues/3525185
 */
function _drevops_preprocess_block__civictheme_banner(array &$variables): void {
  if ($variables['base_plugin_id'] !== 'block_content') {
    return;
  }

  $block = $variables['elements']['content']['#block_content'];
  if ($block->bundle() !== 'civictheme_banner' || ($block->hasField('field_c_b_type') && $block->field_c_b_type->isEmpty())) {
    return;
  }

  // Finds a banner type.
  $type = civictheme_get_field_value($block, 'field_c_b_banner_type', TRUE, CivicthemeConstants::BANNER_TYPE_DEFAULT);
  $route_match = \Drupal::routeMatch();
  $node = $route_match->getParameter('node_revision') ?: $route_match->getParameter('node');
  if (!empty($node)) {
    $type = civictheme_get_field_value($node, 'field_c_n_banner_type', TRUE, CivicthemeConstants::INHERIT);
  }

  $variables['type'] = $type;

  // Adds extra top padding for the banner when additional spacing is needed.
  $variables['with_offset'] = civictheme_get_theme_config_manager()->load('components.header.is_sticky', FALSE);

  // Fix double HTML escaping for banner title.
  // CivicTheme uses Xss::filter() on the title, which escapes HTML entities.
  // When Twig outputs this with {{ title }}, it escapes again.
  // Wrap in Markup to prevent double escaping.
  // Only process if title is a string (not an array or object).
  // @phpstan-ignore-next-line instanceof.alwaysFalse
  if (!empty($variables['title']) && is_string($variables['title']) && !($variables['title'] instanceof MarkupInterface)) {
    $variables['title'] = Markup::create($variables['title']);
  }
}

/**
 * Pre-process breadcrumb to fix double HTML escaping issue.
 *
 * CivicTheme's _civictheme_preprocess_block__civictheme_banner__breadcrumb()
 * uses Xss::filter() which returns a plain string. When this string is output
 * in Twig with {{ link.text }}, Twig auto-escapes it again, causing & to
 * display as &amp;.
 *
 * This function wraps the filtered text in Markup objects so Twig knows
 * they're already safe.
 *
 * @see _civictheme_preprocess_block__civictheme_banner__breadcrumb()
 */
function _drevops_preprocess_block__civictheme_banner__breadcrumb(array &$variables): void {
  if (empty($variables['breadcrumb']['links'])) {
    return;
  }

  // Wrap breadcrumb link text in Markup to prevent double escaping.
  // Only process if text is a string (not an array or object).
  foreach ($variables['breadcrumb']['links'] as &$link) {
    // @phpstan-ignore-next-line instanceof.alwaysFalse
    if (!empty($link['text']) && is_string($link['text']) && !($link['text'] instanceof MarkupInterface)) {
      $link['text'] = Markup::create($link['text']);
    }
  }
}
