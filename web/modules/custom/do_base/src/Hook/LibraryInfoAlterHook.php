<?php

declare(strict_types=1);

namespace Drupal\do_base\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Library info alter hooks for do_base module.
 */
final class LibraryInfoAlterHook {

  /**
   * Implements hook_library_info_alter().
   *
   * Attaches Gherkin language support whenever Highlight.js is loaded.
   * The CDN common bundle does not include Gherkin, so we load it separately.
   */
  #[Hook('library_info_alter')]
  public function alter(array &$libraries, string $extension): void {
    if ($extension === 'highlight_js' && isset($libraries['highlight_js.custom'])) {
      $libraries['highlight_js.custom']['dependencies'][] = 'do_base/highlight_js.gherkin';
    }
  }

}
