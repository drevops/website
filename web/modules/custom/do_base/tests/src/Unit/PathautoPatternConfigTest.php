<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\do_base\Traits\ExportedConfigTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

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

  use ExportedConfigTrait;

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
  public static function dataProviderAliasPattern(): \Iterator {
    yield 'blog post' => ['blog', '/blog/[node:title]'];
    yield 'event' => ['civictheme_event', '/events/[node:title]'];
    yield 'project' => ['project', '/work/[node:title]'];
  }

  /**
   * Tests that each pattern is scoped to the bundle it is named for.
   */
  #[DataProvider('dataProviderPatternIsScopedToItsBundle')]
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
   * Data provider for testPatternIsScopedToItsBundle.
   */
  public static function dataProviderPatternIsScopedToItsBundle(): \Iterator {
    yield 'blog post' => ['blog'];
    yield 'event' => ['civictheme_event'];
    yield 'project' => ['project'];
  }

}
