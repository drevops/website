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
use Drupal\webform\WebformInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Axel attributes added to components, blocks and confirmations.
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
   * @param int|null $id
   *   The paragraph ID, or NULL for an unsaved paragraph.
   * @param bool $has_title
   *   Whether the paragraph has a title field.
   * @param string|null $title
   *   The title field value.
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
    $paragraph->method('hasField')->willReturnCallback(static fn(string $name): bool => $name === 'field_c_p_title' && $has_title);
    $paragraph->method('get')->with('field_c_p_title')->willReturn($field);

    $variables = ['paragraph' => $paragraph, 'attributes' => ['class' => ['card']]];

    $this->hook()->paragraph($variables);

    $this->assertSame($expected + ['class' => ['card']], $variables['attributes']);
  }

  /**
   * Tests the title of a reference paragraph.
   *
   * @param bool $has_referenced
   *   Whether the reference field holds a node.
   * @param array<string, string> $expected
   *   The expected attributes.
   */
  #[DataProvider('dataProviderReferenceParagraph')]
  public function testReferenceParagraph(bool $has_referenced, array $expected): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('label')->willReturn('Website Development');

    $reference = $this->createMock(FieldItemListInterface::class);
    $reference->method('__get')->with('entity')->willReturn($has_referenced ? $node : NULL);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('bundle')->willReturn('civictheme_promo_card_ref');
    $paragraph->method('id')->willReturn(1303);
    $paragraph->method('getEntityTypeId')->willReturn('paragraph');
    $paragraph->method('hasField')->willReturnCallback(static fn(string $name): bool => $name === 'field_c_p_reference');
    $paragraph->method('get')->with('field_c_p_reference')->willReturn($reference);

    $variables = ['paragraph' => $paragraph, 'attributes' => []];

    $this->hook()->paragraph($variables);

    $this->assertSame($expected, $variables['attributes']);
  }

  /**
   * Data provider for testReferenceParagraph.
   */
  public static function dataProviderReferenceParagraph(): \Iterator {
    yield 'referenced content' => [
      TRUE,
      [
        'data-axel-event' => 'component_click',
        'data-axel-param-component' => 'promo_card_ref',
        'data-axel-param-component-id' => 'paragraph:1303',
        'data-axel-param-component-title' => 'Website Development',
      ],
    ];
    yield 'reference removed' => [
      FALSE,
      [
        'data-axel-event' => 'component_click',
        'data-axel-param-component' => 'promo_card_ref',
        'data-axel-param-component-id' => 'paragraph:1303',
      ],
    ];
  }

  /**
   * Tests that a paragraph's own title wins over the referenced content.
   */
  public function testOwnTitleWinsOverReference(): void {
    $title = $this->createMock(FieldItemListInterface::class);
    $title->method('__get')->with('value')->willReturn('Our services');

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('bundle')->willReturn('civictheme_promo_card_ref');
    $paragraph->method('id')->willReturn(1303);
    $paragraph->method('getEntityTypeId')->willReturn('paragraph');
    $paragraph->method('hasField')->willReturn(TRUE);
    $paragraph->method('get')->willReturnCallback(fn(string $name): FieldItemListInterface => $name === 'field_c_p_title' ? $title : $this->fail('The reference field must not be read.'));

    $variables = ['paragraph' => $paragraph, 'attributes' => []];

    $this->hook()->paragraph($variables);

    $this->assertSame('Our services', $variables['attributes']['data-axel-param-component-title']);
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
        'data-axel-event' => 'component_click',
        'data-axel-param-component' => 'promo_card',
        'data-axel-param-component-id' => 'paragraph:12',
        'data-axel-param-component-title' => 'Senior delivery',
      ],
    ];
    yield 'with an empty title' => [
      12,
      TRUE,
      NULL,
      [
        'data-axel-event' => 'component_click',
        'data-axel-param-component' => 'promo_card',
        'data-axel-param-component-id' => 'paragraph:12',
      ],
    ];
    yield 'without a title field' => [
      12,
      FALSE,
      NULL,
      [
        'data-axel-event' => 'component_click',
        'data-axel-param-component' => 'promo_card',
        'data-axel-param-component-id' => 'paragraph:12',
      ],
    ];
    yield 'unsaved' => [
      NULL,
      TRUE,
      'Senior delivery',
      [
        'data-axel-event' => 'component_click',
        'data-axel-param-component' => 'promo_card',
        'data-axel-param-component-title' => 'Senior delivery',
      ],
    ];
  }

  /**
   * Tests the title taken from the rich text of a paragraph.
   *
   * @param bool $has_title
   *   Whether the paragraph has a title field.
   * @param string|null $title
   *   The title field value.
   * @param string|null $content
   *   The rich text field value.
   * @param string|null $expected
   *   The expected title, or NULL when no title is rendered.
   */
  #[DataProvider('dataProviderContentHeading')]
  public function testContentHeading(bool $has_title, ?string $title, ?string $content, ?string $expected): void {
    $fields = [
      'field_c_p_title' => $title,
      'field_c_p_content' => $content,
    ];

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('bundle')->willReturn('civictheme_manual_list');
    $paragraph->method('id')->willReturn(1281);
    $paragraph->method('getEntityTypeId')->willReturn('paragraph');
    $paragraph->method('hasField')->willReturnCallback(static fn(string $name): bool => $name === 'field_c_p_content' || ($name === 'field_c_p_title' && $has_title));
    $paragraph->method('get')->willReturnCallback(function (string $name) use ($fields): FieldItemListInterface {
      $field = $this->createMock(FieldItemListInterface::class);
      $field->method('__get')->with('value')->willReturn($fields[$name]);

      return $field;
    });

    $variables = ['paragraph' => $paragraph, 'attributes' => []];

    $this->hook()->paragraph($variables);

    $this->assertSame($expected, $variables['attributes']['data-axel-param-component-title'] ?? NULL);
  }

  /**
   * Data provider for testContentHeading.
   */
  public static function dataProviderContentHeading(): \Iterator {
    yield 'heading with markup' => [TRUE, NULL, '<h2 class="text-align-center"><strong>Built by hand or AI-assisted.</strong> Your choice.</h2><p>Intro.</p>', 'Built by hand or AI-assisted. Your choice.'];
    yield 'first heading after text' => [TRUE, '', '<p>Intro.</p><h3>Good engineering</h3><h2>Later</h2>', 'Good engineering'];
    yield 'no heading' => [TRUE, NULL, '<p>Intro only.</p>', NULL];
    yield 'empty content' => [TRUE, NULL, NULL, NULL];
    yield 'own title wins' => [TRUE, 'Our work', '<h2>Websites we build</h2>', 'Our work'];
    yield 'without a title field' => [FALSE, NULL, '<h2>For teams whose website is infrastructure</h2>', NULL];
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
      'data-axel-event' => 'component_click',
      'data-axel-param-component' => 'promo_card',
      'data-axel-param-component-id' => 'node:171',
      'data-axel-param-component-title' => 'Generated blog post 1',
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
      'data-axel-event' => 'component_click',
      'data-axel-param-block' => 'drevops_footer_menu_1',
      'data-axel-param-region' => 'footer_middle_5',
      'id' => 'block-drevops-footer-menu-1',
    ], $variables['attributes']);
  }

  /**
   * Tests that a block whose config entity is gone is still named.
   */
  public function testBlockWithoutEntity(): void {
    $variables = ['elements' => ['#id' => 'drevops_removed'], 'attributes' => []];

    $this->hook()->block($variables);

    $this->assertSame(['data-axel-event' => 'component_click', 'data-axel-param-block' => 'drevops_removed'], $variables['attributes']);
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
   * Tests that a webform confirmation reports the submission of its webform.
   */
  public function testWebformConfirmation(): void {
    $webform = $this->createMock(WebformInterface::class);
    $webform->method('id')->willReturn('civictheme_enquiry');

    $variables = ['webform' => $webform, 'attributes' => ['class' => ['webform-confirmation']]];

    $this->hook()->webformConfirmation($variables);

    $this->assertSame(['data-axel-form-submitted' => 'webform:civictheme_enquiry', 'class' => ['webform-confirmation']], $variables['attributes']);
  }

  /**
   * Tests that a confirmation without a webform is left alone.
   */
  public function testWebformConfirmationWithoutWebform(): void {
    $variables = ['webform' => NULL, 'attributes' => []];

    $this->hook()->webformConfirmation($variables);

    $this->assertSame([], $variables['attributes']);
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
