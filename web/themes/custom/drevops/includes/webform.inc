<?php

/**
 * @file
 * Webform confirmation.
 */

declare(strict_types=1);

use Drupal\Core\Template\Attribute;

/**
 * Implements template_preprocess_webform_confirmation().
 */
function drevops_preprocess_webform_confirmation(array &$variables): void {
  if (empty($variables['message'])) {
    return;
  }

  // The message arrives as processed text, which CivicTheme wraps in a
  // spaced content block, so only its text is passed to the message.
  $text = trim(strip_tags((string) \Drupal::service('renderer')->renderInIsolation($variables['message'])));

  if ($text === '') {
    return;
  }

  $variables['message'] = [
    '#type' => 'component',
    '#component' => 'civictheme:message',
    '#props' => [
      'type' => 'success',
      'theme' => 'light',
      // The component's own role is 'contentinfo', the page footer landmark.
      'has_aria' => FALSE,
      'attributes' => new Attribute(['role' => 'status']),
    ],
    '#slots' => [
      'content' => $text,
    ],
  ];
}
