<?php

declare(strict_types=1);

namespace Drupal\do_base\Hook;

use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderAfter;

/**
 * Library info alter hooks for do_base module.
 */
final class LibraryInfoAlterHook {

  /**
   * Library holding the stylesheets CKEditor 5 loads for the editing area.
   */
  protected const string EDITOR_STYLESHEETS = 'internal.drupal.ckeditor5.stylesheets';

  /**
   * Theme whose editor stylesheets the sub-theme rebuilds.
   */
  protected const string BASE_THEME = 'civictheme';

  public function __construct(
    protected readonly ThemeExtensionList $themeList,
  ) {
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter', order: new OrderAfter(modules: ['ckeditor5']))]
  public function alter(array &$libraries, string $extension): void {
    if ($extension === 'highlight_js' && isset($libraries['highlight_js.custom'])) {
      // The CDN common bundle carries no Gherkin grammar, so it is loaded
      // separately.
      $libraries['highlight_js.custom']['dependencies'][] = 'do_base/highlight_js.gherkin';
    }

    if ($extension === 'ckeditor5' && isset($libraries[static::EDITOR_STYLESHEETS]['css']['theme'])) {
      // The base theme's stylesheets are merged into this list and .info.yml
      // cannot override them. Its build imports Google Fonts, which the policy
      // blocks, and a blocked @import fails the whole stylesheet.
      $libraries[static::EDITOR_STYLESHEETS]['css']['theme'] = $this->withoutBaseTheme($libraries[static::EDITOR_STYLESHEETS]['css']['theme']);
    }
  }

  /**
   * Filters out stylesheets that belong to the base theme.
   *
   * @param array<string, array<string, mixed>> $stylesheets
   *   Stylesheets keyed by path. A theme's own files are keyed from the
   *   docroot, with a leading slash; the key can also be an external URL or a
   *   path into the files directory.
   *
   * @return array<string, array<string, mixed>>
   *   The stylesheets that are not files of the base theme.
   */
  protected function withoutBaseTheme(array $stylesheets): array {
    if (!$this->themeList->exists(static::BASE_THEME)) {
      return $stylesheets;
    }

    $base_theme_path = $this->themeList->getPath(static::BASE_THEME) . '/';

    return array_filter($stylesheets, static fn(string $path): bool => !str_starts_with(ltrim($path, '/'), $base_theme_path), ARRAY_FILTER_USE_KEY);
  }

}
