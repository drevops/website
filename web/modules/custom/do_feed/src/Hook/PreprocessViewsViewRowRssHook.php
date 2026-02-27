<?php

declare(strict_types=1);

namespace Drupal\do_feed\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Strips SVG elements from RSS feed item descriptions.
 */
final class PreprocessViewsViewRowRssHook {

  /**
   * Implements hook_preprocess_views_view_row_rss().
   */
  #[Hook('preprocess_views_view_row_rss')]
  public function preprocess(array &$variables): void {
    if (!isset($variables['description']) || !is_string($variables['description'])) {
      return;
    }

    $variables['description'] = preg_replace('/<svg[^>]*>.*?<\/svg>/si', '', $variables['description']) ?? $variables['description'];
  }

}
