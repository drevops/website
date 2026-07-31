<?php

/**
 * @file
 * Built-in error page functions.
 */

declare(strict_types=1);

use Drupal\Core\Url;

/**
 * Whether the current route serves Drupal's built-in 4xx page.
 *
 * Pointing "system.site" at a path for 403 or 404 routes the response through
 * that path instead, so a site that provides its own error page never reaches
 * these routes and keeps the theming of the page it points at.
 */
function _drevops_is_error_page(): bool {
  $routes = [
    'system.401',
    'system.403',
    'system.404',
    'system.4xx',
  ];

  return in_array(\Drupal::routeMatch()->getRouteName(), $routes, TRUE);
}

/**
 * Pre-process the page of a built-in error page.
 */
function _drevops_preprocess_page__error(array &$variables): void {
  if (!_drevops_is_error_page()) {
    return;
  }

  // A reader who cannot reach the page they asked for is not served by a menu
  // of the pages they can.
  $variables['hide_sidebar_left'] = TRUE;
  $variables['hide_sidebar_right'] = TRUE;

  // Releasing the region from the layout's container lets the message band
  // carry its own, so its background reaches the edge of the viewport the way
  // the bands above and below it do.
  $variables['content_contained'] = FALSE;
}

/**
 * Pre-process the main block of a built-in error page.
 */
function _drevops_preprocess_block__system_main_block__error(array &$variables): void {
  if (($variables['elements']['#base_plugin_id'] ?? NULL) !== 'system_main_block') {
    return;
  }

  if (!_drevops_is_error_page()) {
    return;
  }

  $content = $variables['content'];

  $variables['content'] = [
    '#type' => 'component',
    '#component' => 'civictheme:basic-content',
    '#props' => [
      'theme' => 'dark',
      'with_background' => TRUE,
      'vertical_spacing' => 'both',
    ],
    '#slots' => [
      'content' => [
        'message' => [
          // Every one of the routes above answers with a single sentence, and
          // the paragraph both marks it up as one and spaces it off the action
          // below through the content styles.
          '#type' => 'html_tag',
          '#tag' => 'p',
          'content' => $content,
        ],
        'action' => [
          '#type' => 'component',
          '#component' => 'civictheme:button',
          '#props' => [
            'theme' => 'dark',
            'kind' => 'link',
            'type' => 'primary',
            'text' => (string) t('Return to homepage'),
            'url' => Url::fromRoute('<front>')->toString(),
          ],
        ],
      ],
    ],
  ];
}
