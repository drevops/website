<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit\Twig;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\do_base\Twig\IconAssetsExtension;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for IconAssetsExtension.
 */
#[CoversClass(IconAssetsExtension::class)]
#[Group('do_base')]
class IconAssetsExtensionTest extends UnitTestCase {

  /**
   * Tests that getGlobals() returns the active theme's assets directory.
   */
  #[DataProvider('dataProviderGetGlobals')]
  public function testGetGlobals(string $theme_name, string $expected): void {
    $active_theme = $this->createMock(ActiveTheme::class);
    $active_theme->method('getName')->willReturn($theme_name);

    $theme_manager = $this->createMock(ThemeManagerInterface::class);
    $theme_manager->method('getActiveTheme')->willReturn($active_theme);

    $extension = new IconAssetsExtension($theme_manager);

    $this->assertSame(['assets_dir' => $expected], $extension->getGlobals());
  }

  /**
   * Data provider for testGetGlobals().
   */
  public static function dataProviderGetGlobals(): \Iterator {
    yield 'drevops theme' => ['drevops', '@drevops/../assets'];
    yield 'civictheme theme' => ['civictheme', '@civictheme/../assets'];
  }

}
