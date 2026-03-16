<?php

declare(strict_types=1);

namespace Drupal\Tests\do_feed\Unit\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\do_feed\FeedUrlBuilder;
use Drupal\do_feed\Hook\PreprocessParagraphHook;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for PreprocessParagraphHook.
 */
#[CoversClass(PreprocessParagraphHook::class)]
#[Group('do_feed')]
class PreprocessParagraphHookTest extends UnitTestCase {

  /**
   * The hook instance under test.
   */
  protected PreprocessParagraphHook $hook;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('path_prefix')->willReturn('feed');

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with('do_feed.settings')->willReturn($config);

    $url_builder = new FeedUrlBuilder($config_factory);
    $this->hook = new PreprocessParagraphHook($url_builder);
  }

  /**
   * Tests that RSS button is injected when slug is set.
   */
  public function testRssButtonInjectedWhenSlugSet(): void {
    $slug_field = $this->createMock(FieldItemListInterface::class);
    $slug_field->method('isEmpty')->willReturn(FALSE);
    $slug_field->method('__get')->with('value')->willReturn('blog');

    $paragraph = $this->createMock(Paragraph::class);
    $paragraph->method('bundle')->willReturn('civictheme_automated_list');
    $paragraph->method('hasField')->with('field_c_p_list_feed_slug')->willReturn(TRUE);
    $paragraph->method('get')->with('field_c_p_list_feed_slug')->willReturn($slug_field);

    $variables = ['paragraph' => $paragraph];
    $this->hook->preprocessParagraph($variables);

    $this->assertArrayHasKey('footer', $variables);
    $this->assertEquals('inline_template', $variables['footer']['#type']);
    $this->assertStringContainsString('RSS Feed', $variables['footer']['#template']);
    $this->assertStringContainsString("civictheme:button", $variables['footer']['#template']);
    $this->assertEquals('/feed/blog', $variables['footer']['#context']['url']);
  }

  /**
   * Tests that no RSS button is injected when slug is empty.
   */
  public function testNoRssButtonWhenSlugEmpty(): void {
    $slug_field = $this->createMock(FieldItemListInterface::class);
    $slug_field->method('isEmpty')->willReturn(TRUE);

    $paragraph = $this->createMock(Paragraph::class);
    $paragraph->method('bundle')->willReturn('civictheme_automated_list');
    $paragraph->method('hasField')->with('field_c_p_list_feed_slug')->willReturn(TRUE);
    $paragraph->method('get')->with('field_c_p_list_feed_slug')->willReturn($slug_field);

    $variables = ['paragraph' => $paragraph];
    $this->hook->preprocessParagraph($variables);

    $this->assertArrayNotHasKey('footer', $variables);
  }

  /**
   * Tests that non-automated-list paragraphs are skipped.
   */
  public function testSkipNonAutomatedListBundle(): void {
    $paragraph = $this->createMock(Paragraph::class);
    $paragraph->method('bundle')->willReturn('civictheme_content');

    $variables = ['paragraph' => $paragraph];
    $this->hook->preprocessParagraph($variables);

    $this->assertArrayNotHasKey('footer', $variables);
  }

  /**
   * Tests that paragraphs without the slug field are skipped.
   */
  public function testSkipParagraphWithoutSlugField(): void {
    $paragraph = $this->createMock(Paragraph::class);
    $paragraph->method('bundle')->willReturn('civictheme_automated_list');
    $paragraph->method('hasField')->with('field_c_p_list_feed_slug')->willReturn(FALSE);

    $variables = ['paragraph' => $paragraph];
    $this->hook->preprocessParagraph($variables);

    $this->assertArrayNotHasKey('footer', $variables);
  }

  /**
   * Tests that button URL uses correct prefix and slug.
   */
  public function testButtonUrlUsesCorrectPrefixAndSlug(): void {
    $slug_field = $this->createMock(FieldItemListInterface::class);
    $slug_field->method('isEmpty')->willReturn(FALSE);
    $slug_field->method('__get')->with('value')->willReturn('news');

    $paragraph = $this->createMock(Paragraph::class);
    $paragraph->method('bundle')->willReturn('civictheme_automated_list');
    $paragraph->method('hasField')->with('field_c_p_list_feed_slug')->willReturn(TRUE);
    $paragraph->method('get')->with('field_c_p_list_feed_slug')->willReturn($slug_field);

    $variables = ['paragraph' => $paragraph];
    $this->hook->preprocessParagraph($variables);

    $this->assertEquals('/feed/news', $variables['footer']['#context']['url']);
  }

  /**
   * Tests that non-Paragraph entities are skipped.
   */
  public function testSkipNonParagraphEntity(): void {
    $variables = ['paragraph' => 'not_a_paragraph'];
    $this->hook->preprocessParagraph($variables);

    $this->assertArrayNotHasKey('footer', $variables);
  }

}
