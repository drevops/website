<?php

/**
 * @file
 * Page related functions.
 */

declare(strict_types=1);

/**
 * Generic page pre-processing.
 */
function _drevops_preprocess_page(array &$variables): void {
  $variables['header_is_sticky'] = civictheme_get_theme_config_manager()->load('components.header.is_sticky', FALSE);
}

/**
 * Preloads the Latin subset of both brand faces.
 *
 * A face is only requested once the browser matches a rule that needs it, late
 * enough that headings paint in the fallback and then reflow. Only the Latin
 * subsets are preloaded, because a preload the page does not use is itself a
 * wasted request.
 */
function _drevops_attach_font_preloads(array &$attachments): void {
  $theme_path = \Drupal::service('extension.list.theme')->getPath('drevops');

  $fonts = [
    '/' . $theme_path . '/dist/assets/fonts/lexend/lexend-latin.woff2',
    '/' . $theme_path . '/dist/assets/fonts/rubik/rubik-latin.woff2',
  ];

  foreach ($fonts as $font) {
    $attachments['#attached']['html_head_link'][] = [
      [
        'rel' => 'preload',
        'href' => $font,
        'as' => 'font',
        'type' => 'font/woff2',
        'crossorigin' => 'anonymous',
      ],
    ];
  }
}
