<?php

/**
 * @file
 * DrevOps Banner paragraph component.
 */

declare(strict_types=1);

use Drupal\civictheme\CivicthemeConstants;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Xss;
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

  // Brand hero: the "hero" banner type renders as a centered intro hero with
  // the atmospheric glow. CivicTheme has no "hero" layout, so map it onto the
  // "intro" layout and add the hero styling hook; the front page gets the
  // taller full-viewport variant.
  if ($type === 'hero') {
    $variables['type'] = 'intro';
    $is_front = \Drupal::service('path.matcher')->isFrontPage();
    $hero_class = $is_front ? 'ct-banner--hero ct-banner--hero-home' : 'ct-banner--hero';
    $variables['modifier_class'] = trim(($variables['modifier_class'] ?? '') . ' ' . $hero_class);

    // The design hero is a dark canvas with an atmospheric glow (rendered in
    // banner.scss), not a background image. The shared banner block ships a
    // default background image, so drop it here to render the clean hero.
    unset($variables['background_image'], $variables['background_image_blend_mode']);
  }

  // Fade hero: the featured image fills the banner behind a dark page-coloured
  // fade, with the title over it (used by blog posts). The banner renders the
  // "fade" type natively; here we only promote the featured image to the
  // full-bleed background so it is not also shown as a side image.
  if ($type === 'fade' && !empty($variables['featured_image'])) {
    $variables['background_image'] = $variables['featured_image'];
    $variables['featured_image'] = NULL;
  }

  // CivicTheme's civictheme_get_field_value() runs string fields through
  // strip_tags(), which removes the design's accent markup from the banner
  // title. Re-read the raw value (XSS-filtered to a safe inline set) so the
  // accent word survives; it is wrapped in Markup below to render unescaped.
  if (!empty($node) && $node->hasField('field_c_n_banner_title') && !$node->get('field_c_n_banner_title')->isEmpty()) {
    $allowed_tags = ['span', 'em', 'strong', 'br'];
    $variables['title'] = Xss::filter((string) $node->get('field_c_n_banner_title')->value, $allowed_tags);
  }

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
