<?php

/**
 * @file
 * Content moderation block preprocessing.
 */

declare(strict_types=1);

/**
 * Implements hook_preprocess_block() for content moderation control block.
 *
 * Adds container class to prevent edge-to-edge display when sidebar is hidden.
 */
function _drevops_preprocess_block__content_moderation_control(array &$variables): void {
  if (!isset($variables['plugin_id']) || !str_contains($variables['plugin_id'], 'content_moderation_control')) {
    return;
  }

  $variables['attributes']['class'][] = 'container';
}
