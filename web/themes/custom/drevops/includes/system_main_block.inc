<?php

/**
 * @file
 * Preprocess for system_main_block block.
 */

declare(strict_types=1);

/**
 * Preprocess for system_main_block block.
 */
function _drevops_preprocess_block__system_main_block(array &$variables): void {
  if (!isset($variables['elements']['#base_plugin_id']) || $variables['elements']['#base_plugin_id'] != 'system_main_block') {
    return;
  }

  $route_match = \Drupal::routeMatch();
  $route_name = $route_match->getRouteName();
  if ($route_name !== 'user.login' && $route_name !== 'user.pass') {
    return;
  }

  $variables['attributes']['class'][] = 'ct-vertical-spacing--both';
}
