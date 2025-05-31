<?php

/**
 * @file
 * DrevOps Banner paragraph component.
 */

declare(strict_types=1);

use Drupal\civictheme\CivicthemeConstants;

/**
 * Pre-process for Banner block.
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
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
}
