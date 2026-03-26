<?php

declare(strict_types=1);

namespace Drupal\Tests\do_feed\Unit\Hook;

use Drupal\do_feed\Hook\PreprocessViewsViewRowRssHook;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for PreprocessViewsViewRowRssHook.
 */
#[CoversClass(PreprocessViewsViewRowRssHook::class)]
#[Group('do_feed')]
class PreprocessViewsViewRowRssHookTest extends UnitTestCase {

  /**
   * Tests SVG stripping from RSS item descriptions.
   */
  #[DataProvider('dataProviderSvgStripping')]
  public function testSvgStripping(string $input, string $expected): void {
    $hook = new PreprocessViewsViewRowRssHook();
    $variables = ['description' => $input];

    $hook->preprocess($variables);

    $this->assertEquals($expected, $variables['description']);
  }

  /**
   * Data provider for `testSvgStripping()`.
   *
   * @return \Iterator<string, array{string, string}>
   *   Test cases: input description, expected output.
   */
  public static function dataProviderSvgStripping(): \Iterator {
    yield 'no svg' => [
      '<p>Hello world</p>',
      '<p>Hello world</p>',
    ];
    yield 'simple svg' => [
      '<p>Text</p><svg><path d="M0 0"/></svg><p>More</p>',
      '<p>Text</p><p>More</p>',
    ];
    yield 'svg with attributes' => [
      '<p>Text</p><svg width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg><p>More</p>',
      '<p>Text</p><p>More</p>',
    ];
    yield 'multiple svgs' => [
      '<svg><path/></svg><p>Between</p><svg class="icon"><rect/></svg>',
      '<p>Between</p>',
    ];
    yield 'multiline svg' => [
      "<p>Text</p>\n<svg\n  class=\"icon\"\n  viewBox=\"0 0 24 24\">\n  <path d=\"M0 0\"/>\n</svg>\n<p>More</p>",
      "<p>Text</p>\n\n<p>More</p>",
    ];
    yield 'svg with inner svg-like content' => [
      '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0"/></svg><p>After</p>',
      '<p>After</p>',
    ];
    yield 'empty string' => ['', ''];
  }

  /**
   * Tests that non-string or missing descriptions are skipped.
   */
  #[DataProvider('dataProviderSkipsNonString')]
  public function testSkipsNonString(array $variables, array $expected): void {
    $hook = new PreprocessViewsViewRowRssHook();

    $hook->preprocess($variables);

    $this->assertEquals($expected, $variables);
  }

  /**
   * Data provider for `testSkipsNonString()`.
   *
   * @return \Iterator<string, array{array<string, mixed>, array<string, mixed>}>
   *   Test cases: input variables, expected variables.
   */
  public static function dataProviderSkipsNonString(): \Iterator {
    yield 'no description key' => [
      ['title' => 'Test'],
      ['title' => 'Test'],
    ];
    yield 'null description' => [
      ['description' => NULL],
      ['description' => NULL],
    ];
    yield 'array description' => [
      ['description' => ['#markup' => '<svg/>']],
      ['description' => ['#markup' => '<svg/>']],
    ];
  }

}
