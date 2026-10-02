<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the removal of node links from rich text markup.
 */
#[Group('do_base')]
class StripNodeLinksTest extends DoBaseUnitTestBase {

  /**
   * UUID of the first node whose links are removed.
   */
  protected const string FIRST = 'ac67f4c4-f4a5-486e-b368-5eb0a4ccd617';

  /**
   * UUID of the second node whose links are removed.
   */
  protected const string SECOND = '3b586d60-9a97-4506-9f44-7f469f41f0cf';

  /**
   * UUID of a node whose links are kept.
   */
  protected const string KEPT = '9d0c3f5e-6b1a-4f7e-8c2d-0a1b2c3d4e5f';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    require_once dirname(__DIR__, 3) . '/do_base.deploy.php';
  }

  /**
   * Tests that the links to the given nodes are stripped from markup.
   *
   * @param string $html
   *   The markup to strip.
   * @param string[] $uuids
   *   UUIDs of the nodes whose links are removed.
   * @param string $expected
   *   The markup expected back.
   */
  #[DataProvider('dataProviderStripNodeLinks')]
  public function testStripNodeLinks(string $html, array $uuids, string $expected): void {
    // Act.
    $actual = _do_base_strip_node_links($html, $uuids);

    // Assert.
    $this->assertSame($expected, $actual);
  }

  /**
   * Data provider for testStripNodeLinks.
   */
  public static function dataProviderStripNodeLinks(): \Iterator {
    $uuids = [self::FIRST, self::SECOND];
    $first = self::link(self::FIRST, 'Privacy policy');
    $second = self::link(self::SECOND, 'Responsible AI policy');
    $kept = self::link(self::KEPT, 'Contact');
    $credit = 'Built with <a href="https://www.drupal.org/project/civictheme">CivicTheme</a>';

    yield 'links each on their own line' => [
      '<p class="text-align-right ct-text-small">©2026 DrevOps®</p><p class="text-align-right ct-text-small">' . $first . '<br>' . $second . '<br>' . $credit . '<br>&nbsp;</p>',
      $uuids,
      '<p class="text-align-right ct-text-small">©2026 DrevOps®</p><p class="text-align-right ct-text-small">' . $credit . '<br>&nbsp;</p>',
    ];
    yield 'link on the last line' => ['<p>' . $credit . '<br>' . $first . '</p>', $uuids, '<p>' . $credit . '</p>'];
    yield 'link alone in a paragraph' => ['<p>Copyright</p><p>' . $first . '</p>', $uuids, '<p>Copyright</p>'];
    yield 'link beside a non-breaking space' => ['<p>Copyright</p><p>' . $first . '&nbsp;</p>', $uuids, '<p>Copyright</p>'];
    yield 'whitespace around the line breaks' => ['<p>' . $first . " <br>\n" . $second . "\n<br> Built with CivicTheme</p>", $uuids, "<p> \n\n Built with CivicTheme</p>"];
    yield 'link to another node in between' => ['<p>' . $first . '<br>' . $kept . '<br>' . $second . '</p>', $uuids, '<p>' . $kept . '</p>'];
    yield 'link to another node only' => ['<p>' . $kept . '<br>Text</p>', $uuids, '<p>' . $kept . '<br>Text</p>'];
    yield 'link without a node reference' => ['<p><a href="/privacy-policy">Privacy policy</a><br>Text</p>', $uuids, '<p><a href="/privacy-policy">Privacy policy</a><br>Text</p>'];
    yield 'no links' => ['<p>©2026 DrevOps®</p>', $uuids, '<p>©2026 DrevOps®</p>'];
    yield 'no nodes to remove' => ['<p>' . $first . '<br>Text</p>', [], '<p>' . $first . '<br>Text</p>'];
  }

  /**
   * Builds a link to a node in the form the editor stores it.
   */
  protected static function link(string $uuid, string $text): string {
    return sprintf('<a href="/node/1" data-entity-type="node" data-entity-uuid="%s" data-entity-substitution="canonical">%s</a>', $uuid, $text);
  }

}
