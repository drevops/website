<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\do_base\Hook\LibraryInfoAlterHook;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the library definitions this site alters.
 *
 * The hook is driven directly, so each extension it recognises is reachable on
 * its own. That the editing area then loads no remote font is asserted against
 * a running site.
 */
#[Group('do_base')]
class LibraryInfoAlterHookTest extends DoBaseUnitTestBase {

  /**
   * Library CKEditor 5 builds from every theme's editor stylesheets.
   */
  protected const string EDITOR_LIBRARY = 'internal.drupal.ckeditor5.stylesheets';

  /**
   * Path the base theme is installed at.
   */
  protected const string BASE_THEME_PATH = 'themes/contrib/civictheme';

  /**
   * Path the sub-theme is installed at.
   */
  protected const string SUB_THEME_PATH = 'themes/custom/drevops';

  /**
   * Tests that the base theme's editor stylesheets are dropped.
   */
  public function testBaseThemeEditorStylesheetsAreDropped(): void {
    // Prepare.
    $libraries = $this->editorLibrary();

    // Act.
    $this->hook()->alter($libraries, 'ckeditor5');

    // Assert.
    $this->assertSame([
      '/' . static::SUB_THEME_PATH . '/dist/styles.editor.css',
      '/' . static::SUB_THEME_PATH . '/dist/styles.variables.css',
    ], array_keys($libraries[static::EDITOR_LIBRARY]['css']['theme']));
  }

  /**
   * Tests that the options of the stylesheets that remain are kept.
   */
  public function testRemainingStylesheetsKeepTheirOptions(): void {
    // Prepare.
    $libraries = $this->editorLibrary();
    $libraries[static::EDITOR_LIBRARY]['css']['theme']['/' . static::SUB_THEME_PATH . '/dist/styles.editor.css'] = ['weight' => 10];

    // Act.
    $this->hook()->alter($libraries, 'ckeditor5');

    // Assert.
    $this->assertSame(['weight' => 10], $libraries[static::EDITOR_LIBRARY]['css']['theme']['/' . static::SUB_THEME_PATH . '/dist/styles.editor.css']);
  }

  /**
   * Tests that a stylesheet outside the base theme's own files is kept.
   *
   * @param string $path
   *   Path the stylesheet is keyed by.
   */
  #[DataProvider('dataProviderStylesheetOutsideTheBaseThemeIsKept')]
  public function testStylesheetOutsideTheBaseThemeIsKept(string $path): void {
    // Prepare.
    $libraries = $this->editorLibrary();
    $libraries[static::EDITOR_LIBRARY]['css']['theme'][$path] = [];

    // Act.
    $this->hook()->alter($libraries, 'ckeditor5');

    // Assert.
    $this->assertArrayHasKey($path, $libraries[static::EDITOR_LIBRARY]['css']['theme']);
  }

  /**
   * Data provider for testStylesheetOutsideTheBaseThemeIsKept.
   */
  public static function dataProviderStylesheetOutsideTheBaseThemeIsKept(): \Iterator {
    yield 'a generated file' => ['/sites/default/files/css-variables.civictheme.css'];
    yield 'an external stylesheet' => ['https://example.com/' . self::BASE_THEME_PATH . '/editor.css'];
    yield 'a theme whose name starts the same' => ['/' . self::BASE_THEME_PATH . '_subtheme/dist/styles.editor.css'];
  }

  /**
   * Tests that the stylesheets are left alone without the base theme.
   *
   * A site that drops the base theme has no build of its own to fall back on.
   */
  public function testStylesheetsAreLeftAloneWithoutTheBaseTheme(): void {
    // Prepare.
    $libraries = $this->editorLibrary();
    $expected = $libraries;

    // Act.
    $this->hook(base_theme_exists: FALSE)->alter($libraries, 'ckeditor5');

    // Assert.
    $this->assertSame($expected, $libraries);
  }

  /**
   * Tests that a library list holding no editor stylesheets is left alone.
   *
   * @param array<string, mixed> $libraries
   *   The library definitions as the extension declares them.
   */
  #[DataProvider('dataProviderLibrariesWithoutEditorStylesheetsAreLeftAlone')]
  public function testLibrariesWithoutEditorStylesheetsAreLeftAlone(array $libraries): void {
    // Prepare.
    $expected = $libraries;

    // Act.
    $this->hook()->alter($libraries, 'ckeditor5');

    // Assert.
    $this->assertSame($expected, $libraries);
  }

  /**
   * Data provider for testLibrariesWithoutEditorStylesheetsAreLeftAlone.
   */
  public static function dataProviderLibrariesWithoutEditorStylesheetsAreLeftAlone(): \Iterator {
    yield 'no libraries at all' => [[]];
    yield 'no editor library' => [['ckeditor5' => ['js' => ['ckeditor5.js' => []]]]];
    yield 'editor library without stylesheets' => [[self::EDITOR_LIBRARY => []]];
    yield 'editor library with an empty stylesheet list' => [[self::EDITOR_LIBRARY => ['css' => ['theme' => []]]]];
  }

  /**
   * Tests that the stylesheets of another extension are left alone.
   */
  public function testStylesheetsOfAnotherExtensionAreLeftAlone(): void {
    // Prepare.
    $libraries = $this->editorLibrary();
    $expected = $libraries;

    // Act.
    $this->hook()->alter($libraries, 'civictheme');

    // Assert.
    $this->assertSame($expected, $libraries);
  }

  /**
   * Tests that Gherkin support is added to the syntax highlighter.
   */
  public function testGherkinIsAddedToTheSyntaxHighlighter(): void {
    // Prepare.
    $libraries = ['highlight_js.custom' => ['dependencies' => ['highlight_js/highlight_js']]];

    // Act.
    $this->hook()->alter($libraries, 'highlight_js');

    // Assert.
    $this->assertSame([
      'highlight_js/highlight_js',
      'do_base/highlight_js.gherkin',
    ], $libraries['highlight_js.custom']['dependencies']);
  }

  /**
   * Tests that a syntax highlighter without the custom bundle is left alone.
   */
  public function testSyntaxHighlighterWithoutTheCustomBundleIsLeftAlone(): void {
    // Prepare.
    $libraries = ['highlight_js.other' => ['dependencies' => []]];
    $expected = $libraries;

    // Act.
    $this->hook()->alter($libraries, 'highlight_js');

    // Assert.
    $this->assertSame($expected, $libraries);
  }

  /**
   * Builds the hook under test.
   *
   * @param bool $base_theme_exists
   *   Whether the base theme is installed.
   */
  protected function hook(bool $base_theme_exists = TRUE): LibraryInfoAlterHook {
    $themes = $this->createMock(ThemeExtensionList::class);
    $themes->method('exists')->willReturn($base_theme_exists);
    $themes->method('getPath')->willReturn(static::BASE_THEME_PATH);

    return new LibraryInfoAlterHook($themes);
  }

  /**
   * Builds the editor stylesheet library as CKEditor 5 assembles it.
   *
   * The base theme's stylesheets come first, keyed from the docroot with a
   * leading slash, in the order Ckeditor5Hooks::themeCss() merges them.
   *
   * @return array<string, array<string, array<string, array<string, mixed>>>>
   *   The library definitions of the ckeditor5 extension.
   */
  protected function editorLibrary(): array {
    return [
      static::EDITOR_LIBRARY => [
        'css' => [
          'theme' => [
            '/' . static::BASE_THEME_PATH . '/dist/civictheme.editor.css' => [],
            '/' . static::BASE_THEME_PATH . '/dist/civictheme.variables.css' => [],
            '/' . static::SUB_THEME_PATH . '/dist/styles.editor.css' => [],
            '/' . static::SUB_THEME_PATH . '/dist/styles.variables.css' => [],
          ],
        ],
      ],
    ];
  }

}
