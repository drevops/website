<?php

declare(strict_types=1);

namespace Drupal\Tests\do_analytics\Unit\Hook;

use Drupal\block\BlockInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\do_analytics\Hook\PreprocessHook;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the analytics attributes added to paragraphs, nodes and blocks.
 *
 * That the attributes reach the rendered component roots is asserted in the
 * Behat feature.
 */
#[CoversClass(PreprocessHook::class)]
#[Group('do_analytics')]
class PreprocessHookTest extends UnitTestCase {

  /**
   * Tests the attributes added to a paragraph.
   *
   * @param array<string, string> $expected
   *   The expected attributes.
   */
  #[DataProvider('dataProviderParagraph')]
  public function testParagraph(?int $id, bool $has_title, ?string $title, array $expected): void {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('__get')->with('value')->willReturn($title);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('bundle')->willReturn('civictheme_promo_card');
    $paragraph->method('id')->willReturn($id);
    $paragraph->method('getEntityTypeId')->willReturn('paragraph');
    $paragraph->method('hasField')->with('field_c_p_title')->willReturn($has_title);
    $paragraph->method('get')->with('field_c_p_title')->willReturn($field);

    $variables = ['paragraph' => $paragraph, 'attributes' => ['class' => ['card']]];

    $this->hook()->paragraph($variables);

    $this->assertSame($expected + ['class' => ['card']], $variables['attributes']);
  }

  /**
   * Data provider for testParagraph.
   */
  public static function dataProviderParagraph(): \Iterator {
    yield 'with a title' => [
      12,
      TRUE,
      'Senior delivery',
      [
        'data-analytics-component' => 'promo_card',
        'data-analytics-component-id' => 'paragraph:12',
        'data-analytics-component-title' => 'Senior delivery',
      ],
    ];
    yield 'with an empty title' => [
      12,
      TRUE,
      NULL,
      [
        'data-analytics-component' => 'promo_card',
        'data-analytics-component-id' => 'paragraph:12',
      ],
    ];
    yield 'without a title field' => [
      12,
      FALSE,
      NULL,
      [
        'data-analytics-component' => 'promo_card',
        'data-analytics-component-id' => 'paragraph:12',
      ],
    ];
    yield 'unsaved' => [
      NULL,
      TRUE,
      'Senior delivery',
      [
        'data-analytics-component' => 'promo_card',
        'data-analytics-component-title' => 'Senior delivery',
      ],
    ];
  }

  /**
   * Tests that variables without a paragraph are left alone.
   */
  public function testParagraphMissing(): void {
    $variables = ['paragraph' => 'not a paragraph', 'attributes' => []];

    $this->hook()->paragraph($variables);

    $this->assertSame([], $variables['attributes']);
  }

  /**
   * Tests the attributes added to a node rendered inside another page.
   */
  public function testNodeCard(): void {
    $variables = ['node' => $this->node(), 'view_mode' => 'civictheme_promo_card', 'page' => FALSE, 'attributes' => []];

    $this->hook()->node($variables);

    $this->assertSame([
      'data-analytics-component' => 'promo_card',
      'data-analytics-component-id' => 'node:171',
      'data-analytics-component-title' => 'Generated blog post 1',
    ], $variables['attributes']);
  }

  /**
   * Tests that a node rendered as the page itself is left alone.
   *
   * @param array $variables
   *   The template variables, without the node.
   */
  #[DataProvider('dataProviderNodePageIsLeftAlone')]
  public function testNodePageIsLeftAlone(array $variables): void {
    $variables += ['node' => $this->node(), 'attributes' => []];

    $this->hook()->node($variables);

    $this->assertSame([], $variables['attributes']);
  }

  /**
   * Data provider for testNodePageIsLeftAlone.
   */
  public static function dataProviderNodePageIsLeftAlone(): \Iterator {
    yield 'full view mode' => [['view_mode' => 'full', 'page' => FALSE]];
    yield 'no view mode' => [['page' => FALSE]];
    yield 'page flag' => [['view_mode' => 'default', 'page' => TRUE]];
  }

  /**
   * Tests that variables without a node are left alone.
   */
  public function testNodeMissing(): void {
    $variables = ['node' => NULL, 'view_mode' => 'civictheme_promo_card', 'attributes' => []];

    $this->hook()->node($variables);

    $this->assertSame([], $variables['attributes']);
  }

  /**
   * Tests the attributes added to a placed block.
   */
  public function testBlock(): void {
    $block = $this->createMock(BlockInterface::class);
    $block->method('getRegion')->willReturn('footer_middle_5');

    $variables = ['elements' => ['#id' => 'drevops_footer_menu_1'], 'attributes' => ['id' => 'block-drevops-footer-menu-1']];

    $this->hook(['drevops_footer_menu_1' => $block])->block($variables);

    $this->assertSame([
      'data-analytics-block' => 'drevops_footer_menu_1',
      'data-analytics-region' => 'footer_middle_5',
      'id' => 'block-drevops-footer-menu-1',
    ], $variables['attributes']);
  }

  /**
   * Tests that a block whose config entity is gone is still named.
   */
  public function testBlockWithoutEntity(): void {
    $variables = ['elements' => ['#id' => 'drevops_removed'], 'attributes' => []];

    $this->hook()->block($variables);

    $this->assertSame(['data-analytics-block' => 'drevops_removed'], $variables['attributes']);
  }

  /**
   * Tests that a block placed outside the block layout is left alone.
   *
   * @param array $elements
   *   The block render elements.
   */
  #[DataProvider('dataProviderBlockWithoutId')]
  public function testBlockWithoutId(array $elements): void {
    $variables = ['elements' => $elements, 'attributes' => []];

    $this->hook()->block($variables);

    $this->assertSame([], $variables['attributes']);
  }

  /**
   * Data provider for testBlockWithoutId.
   */
  public static function dataProviderBlockWithoutId(): \Iterator {
    yield 'no id' => [[]];
    yield 'empty id' => [['#id' => '']];
    yield 'not a string' => [['#id' => 12]];
  }

  /**
   * Builds the hook under test.
   *
   * @param array<string, \Drupal\block\BlockInterface> $blocks
   *   The block config entities the storage holds, keyed by id.
   */
  protected function hook(array $blocks = []): PreprocessHook {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturnCallback(static fn(string $id): ?BlockInterface => $blocks[$id] ?? NULL);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('block')->willReturn($storage);

    return new PreprocessHook($entity_type_manager);
  }

  /**
   * Builds a saved node.
   */
  protected function node(): NodeInterface {
    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn(171);
    $node->method('getEntityTypeId')->willReturn('node');
    $node->method('label')->willReturn('Generated blog post 1');

    return $node;
  }

}
