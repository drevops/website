<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests the URL alias patterns exported for each content type.
 *
 * An alias prefix is part of the site's public URL contract: changing one
 * invalidates external links, so each pattern is pinned to the prefix the
 * navigation and the Behat suite expect.
 */
#[CoversNothing]
#[Group('do_base')]
class PathautoPatternConfigTest extends UnitTestCase {

  /**
   * Tests that a content type generates aliases under its expected prefix.
   */
  #[DataProvider('dataProviderAliasPattern')]
  public function testAliasPattern(string $pattern_id, string $expected): void {
    // Act.
    $pattern = $this->loadConfig('pathauto.pattern.' . $pattern_id . '.yml')['pattern'];

    // Assert.
    $this->assertSame($expected, $pattern);
  }

  /**
   * Data provider for testAliasPattern.
   */
  public static function dataProviderAliasPattern(): array {
    return [
      'blog post' => ['blog', '/blog/[node:title]'],
      'event' => ['civictheme_event', '/events/[node:title]'],
      'project' => ['project', '/work/[node:title]'],
    ];
  }

  /**
   * Tests that each pattern is scoped to the bundle it is named for.
   */
  #[DataProvider('dataProviderAliasPattern')]
  public function testPatternIsScopedToItsBundle(string $pattern_id): void {
    // Prepare.
    $criteria = $this->loadConfig('pathauto.pattern.' . $pattern_id . '.yml')['selection_criteria'];
    $bundles = [];

    foreach ($criteria as $criterion) {
      $bundles = array_merge($bundles, array_keys($criterion['bundles'] ?? []));
    }

    // Assert.
    $this->assertSame([$pattern_id], $bundles);
  }

  /**
   * Reads an exported configuration file from the default config directory.
   */
  protected function loadConfig(string $file_name): array {
    $path = dirname($this->root) . '/config/default/' . $file_name;
    $this->assertFileExists($path);

    return Yaml::parseFile($path);
  }

}
