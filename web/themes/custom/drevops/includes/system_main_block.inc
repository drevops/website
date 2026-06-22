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

  $routes = [
    'user.cancel_confirm',
    'user.login',
    'user.logout',
    'user.logout.confirm',
    'user.pass',
    'user.register',
    'user.reset',
    'user.reset.form',
    'user.reset.login',
  ];

  $route_name = \Drupal::routeMatch()->getRouteName();

  if (!in_array($route_name, $routes, TRUE)) {
    return;
  }

  _drevops_block_add_class($variables, 'ct-vertical-spacing--both');
}
