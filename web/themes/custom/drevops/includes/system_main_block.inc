<?php

/**
 * @file
 * Preprocess for system_main_block block.
 */

declare(strict_types=1);

use Drupal\Core\Template\Attribute;

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

  // CivicTheme's block template renders the wrapper from a separate
  // "drupal_attributes" object, so the spacing class must be added there to
  // reach the markup.
  if (isset($variables['drupal_attributes']) && $variables['drupal_attributes'] instanceof Attribute) {
    $variables['drupal_attributes']->addClass('ct-vertical-spacing--both');

    return;
  }

  $variables['attributes']['class'][] = 'ct-vertical-spacing--both';
}
