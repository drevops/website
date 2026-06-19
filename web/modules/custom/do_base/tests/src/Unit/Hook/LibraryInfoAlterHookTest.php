<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit\Hook;

use Drupal\do_base\Hook\LibraryInfoAlterHook;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for LibraryInfoAlterHook.
 */
#[CoversClass(LibraryInfoAlterHook::class)]
#[Group('do_base')]
class LibraryInfoAlterHookTest extends UnitTestCase {

  /**
   * Tests the Gherkin dependency attachment to Highlight.js.
   */
  #[DataProvider('dataProviderAlter')]
  public function testAlter(array $libraries, string $extension, bool $expected_attached): void {
    $hook = new LibraryInfoAlterHook();
    $hook->alter($libraries, $extension);

    $dependencies = $libraries['highlight_js.custom']['dependencies'] ?? [];

    if ($expected_attached) {
      $this->assertContains('do_base/highlight_js.gherkin', $dependencies);
    }
    else {
      $this->assertNotContains('do_base/highlight_js.gherkin', $dependencies);
    }
  }

  /**
   * Data provider for testAlter().
   */
  public static function dataProviderAlter(): \Iterator {
    yield 'matching extension and library attaches gherkin' => [
      ['highlight_js.custom' => ['dependencies' => ['core/drupal']]],
      'highlight_js',
      TRUE,
    ];

    yield 'wrong extension does not attach' => [
      ['highlight_js.custom' => ['dependencies' => ['core/drupal']]],
      'civictheme',
      FALSE,
    ];

    yield 'matching extension without custom library does not attach' => [
      ['highlight_js.other' => ['dependencies' => []]],
      'highlight_js',
      FALSE,
    ];
  }

}
