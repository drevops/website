<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\do_base\Traits\ExportedConfigTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the image styles the theme renders images through.
 *
 * A style name reaches the theme layer as a plain string, so a missing style or
 * an effect dropped in a routine re-export fails silently. The page still
 * renders; only the size and the crop of the image change.
 */
#[CoversNothing]
#[Group('do_base')]
class ImageStyleConfigTest extends UnitTestCase {

  use ExportedConfigTrait;

  /**
   * Machine name of the style behind the banner's featured image.
   */
  protected const FEATURED_STYLE = 'banner_featured';

  /**
   * Tests that every style the theme asks for by name is exported.
   */
  #[DataProvider('dataProviderStyleNamedByTheme')]
  public function testStyleNamedByThemeIsExported(string $name): void {
    // Act.
    $style = $this->loadConfig('image.style.' . $name . '.yml');

    // Assert.
    $this->assertSame($name, $style['name']);
  }

  /**
   * Data provider for testStyleNamedByThemeIsExported.
   */
  public static function dataProviderStyleNamedByTheme(): \Iterator {
    foreach (static::themeStyleNames() as $name) {
      yield $name => [$name];
    }
  }

  /**
   * Tests that the featured image is cropped around the editor's focal point.
   *
   * The banner sizes the image with CSS, so the browser trims everything
   * outside a centred box. A focal point crop places the subject at that
   * centre.
   */
  public function testFeaturedImageCropsOnTheFocalPoint(): void {
    // Act.
    $effect = $this->effect(static::FEATURED_STYLE, 'focal_point_scale_and_crop');

    // Assert.
    $this->assertNotNull($effect, 'The featured image style does not crop on a focal point.');
    $this->assertSame('focal_point', $effect['data']['crop_type']);
    $this->assertGreaterThan(0, $effect['data']['width']);
    $this->assertGreaterThan(0, $effect['data']['height']);
  }

  /**
   * Tests that the styles rendering uploaded imagery convert to WebP.
   *
   * The theme renders only photographic content through a style: brand renders
   * and generated art uploaded as multi-megabyte PNGs. A style without a
   * convert effect serves the uploaded format.
   */
  #[DataProvider('dataProviderStyleNamedByTheme')]
  public function testStyleNamedByThemeConvertsToWebp(string $name): void {
    // Act.
    $effect = $this->effect($name, 'image_convert');

    // Assert.
    $this->assertNotNull($effect, sprintf('Style "%s" does not convert.', $name));
    $this->assertSame('webp', $effect['data']['extension']);
  }

  /**
   * Returns an effect of a style, or NULL when the style does not have one.
   */
  protected function effect(string $name, string $effect_id): ?array {
    $effects = $this->loadConfig('image.style.' . $name . '.yml')['effects'];

    foreach ($effects as $effect) {
      if ($effect['id'] === $effect_id) {
        return $effect;
      }
    }

    return NULL;
  }

  /**
   * Lists the style names the theme passes to the image render helpers.
   *
   * Names are read from the theme rather than listed here, so a component that
   * starts rendering through a style of its own is covered without editing this
   * test.
   */
  protected static function themeStyleNames(): array {
    $helpers = [
      'civictheme_media_image_get_variables',
      '_civictheme_preprocess_paragraph__paragraph_field__image',
      '_civictheme_preprocess_paragraph__node_field__image',
    ];

    $pattern = sprintf('~(?:%s)\([^)]*,\s*\'([a-z0-9_]+)\'\s*\)~', implode('|', $helpers));

    $names = [];

    foreach (glob(dirname(__DIR__, 7) . '/web/themes/custom/drevops/includes/*.inc') ?: [] as $file) {
      if (preg_match_all($pattern, (string) file_get_contents($file), $matches) === 0) {
        continue;
      }

      $names = array_merge($names, $matches[1]);
    }

    $names = array_unique($names);
    sort($names);

    if ($names === []) {
      throw new \RuntimeException('No image styles found in the theme; the scanned helper names are out of date.');
    }

    return $names;
  }

}
