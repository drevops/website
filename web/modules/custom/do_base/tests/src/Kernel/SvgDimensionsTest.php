<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Kernel;

use Drupal\do_base\SvgDimensions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests reading the dimensions an SVG declares.
 */
#[Group('do_base')]
#[RunTestsInSeparateProcesses]
class SvgDimensionsTest extends DoBaseKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'do_base'];

  /**
   * Tests the dimensions read from an SVG's markup.
   *
   * @param string $markup
   *   Markup of the SVG file.
   * @param array<string, int> $expected
   *   The expected dimensions.
   */
  #[DataProvider('dataProviderRead')]
  public function testRead(string $markup, array $expected): void {
    // Prepare.
    $uri = 'public://' . $this->randomMachineName() . '.svg';
    file_put_contents($uri, $markup);

    // Act.
    $dimensions = SvgDimensions::read($uri);

    // Assert.
    $this->assertSame($expected, $dimensions);
  }

  /**
   * Data provider for testRead.
   */
  public static function dataProviderRead(): \Iterator {
    yield 'a viewBox' => ['<svg viewBox="0 0 1600 700"/>', ['width' => 1600, 'height' => 700]];
    yield 'a comma separated viewBox' => ['<svg viewBox="0,0,120,60"/>', ['width' => 120, 'height' => 60]];
    yield 'a fractional viewBox' => ["<svg viewBox='0 0 99.6 50.4'/>", ['width' => 100, 'height' => 50]];
    yield 'an exponent in the viewBox' => ['<svg viewBox="0 0 200 1e2"/>', ['width' => 200, 'height' => 100]];
    yield 'a viewBox with 3 values' => ['<svg viewBox="0 0 200"/>', []];
    yield 'a viewBox with a word in it' => ['<svg viewBox="0 0 wide 100"/>', []];
    yield 'no viewBox' => ['<svg width="16" height="16"/>', []];
    yield 'a zero size viewBox' => ['<svg viewBox="0 0 0 10"/>', []];
    yield 'not an SVG' => ['plain text', []];
  }

  /**
   * Tests that a file that cannot be read yields no dimensions.
   */
  public function testMissingFileYieldsNothing(): void {
    // Act.
    $dimensions = SvgDimensions::read('public://absent.svg');

    // Assert.
    $this->assertSame([], $dimensions);
  }

}
