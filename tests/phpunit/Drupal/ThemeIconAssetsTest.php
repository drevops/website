<?php

declare(strict_types=1);

namespace Drupal;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Class ThemeIconAssetsTest.
 *
 * Tests that the sub-theme ships every icon the base theme provides.
 *
 * Icons resolve from a single directory: the `assets_dir` Twig global points
 * at the active sub-theme, so an icon the base theme references but the
 * sub-theme does not carry would silently render nothing.
 */
#[Group('theme_assets')]
class ThemeIconAssetsTest extends TestCase {

  /**
   * Path to the sub-theme icon directory, relative to the repository root.
   */
  protected const SUBTHEME_ICONS = 'web/themes/custom/drevops/assets/icons';

  /**
   * Path to the base theme icon directory, relative to the repository root.
   */
  protected const BASETHEME_ICONS = 'web/themes/contrib/civictheme/assets/icons';

  /**
   * Test that the sub-theme carries every base theme icon.
   */
  public function testSubthemeCoversAllBaseThemeIcons(): void {
    $base_icons = $this->iconNames(static::BASETHEME_ICONS);
    $subtheme_icons = $this->iconNames(static::SUBTHEME_ICONS);

    $this->assertNotEmpty($base_icons, 'Base theme icon directory should not be empty.');

    $missing = array_diff($base_icons, $subtheme_icons);

    $this->assertSame([], array_values($missing), sprintf('Sub-theme is missing base theme icons: %s. Copy them into %s.', implode(', ', $missing), static::SUBTHEME_ICONS));
  }

  /**
   * Collect SVG icon names from a directory.
   *
   * @param string $directory
   *   Directory path relative to the repository root.
   *
   * @return array
   *   Sorted list of icon names without the extension.
   */
  protected function iconNames(string $directory): array {
    $path = dirname(__DIR__, 3) . '/' . $directory;

    $this->assertDirectoryExists($path);

    $names = [];
    foreach (glob($path . '/*.svg') ?: [] as $file) {
      $names[] = basename($file, '.svg');
    }

    sort($names);

    return $names;
  }

}
