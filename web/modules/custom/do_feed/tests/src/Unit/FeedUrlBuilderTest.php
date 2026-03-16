<?php

declare(strict_types=1);

namespace Drupal\Tests\do_feed\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\do_feed\FeedUrlBuilder;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for FeedUrlBuilder.
 */
#[CoversClass(FeedUrlBuilder::class)]
#[Group('do_feed')]
class FeedUrlBuilderTest extends UnitTestCase {

  /**
   * Creates a FeedUrlBuilder with the given prefix.
   */
  protected function createBuilder(string $prefix = 'feed'): FeedUrlBuilder {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('path_prefix')->willReturn($prefix);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with('do_feed.settings')->willReturn($config);

    return new FeedUrlBuilder($config_factory);
  }

  /**
   * Creates a mock paragraph with given field values.
   */
  protected function createParagraph(
    ?string $content_type = NULL,
    array $topic_ids = [],
    array $section_ids = [],
    string $slug = 'blog',
  ): Paragraph {
    $paragraph = $this->createMock(Paragraph::class);

    // Content type field.
    $ct_field = $this->createMock(FieldItemListInterface::class);
    $ct_field->method('isEmpty')->willReturn($content_type === NULL);
    $ct_field->method('__get')->with('value')->willReturn($content_type);

    // Topics field.
    $topics_field = $this->createMock(FieldItemListInterface::class);
    $topics_field->method('isEmpty')->willReturn(empty($topic_ids));
    $topics_field->method('getValue')->willReturn(
      array_map(static fn($id): array => ['target_id' => $id], $topic_ids)
    );

    // Sections field.
    $sections_field = $this->createMock(FieldItemListInterface::class);
    $sections_field->method('isEmpty')->willReturn(empty($section_ids));
    $sections_field->method('getValue')->willReturn(
      array_map(static fn($id): array => ['target_id' => $id], $section_ids)
    );

    // Slug field.
    $slug_field = $this->createMock(FieldItemListInterface::class);
    $slug_field->method('__get')->with('value')->willReturn($slug);

    $paragraph->method('hasField')->willReturnCallback(static fn(string $name): bool => in_array($name, [
      'field_c_p_list_content_type',
      'field_c_p_list_topics',
      'field_c_p_list_site_sections',
      'field_c_p_list_feed_slug',
    ], TRUE));

    $paragraph->method('get')->willReturnCallback(
      static fn(string $name): MockObject => match ($name) {
        'field_c_p_list_content_type' => $ct_field,
        'field_c_p_list_topics' => $topics_field,
        'field_c_p_list_site_sections' => $sections_field,
        'field_c_p_list_feed_slug' => $slug_field,
        default => throw new \InvalidArgumentException('Unexpected field: ' . $name),
      }
    );

    return $paragraph;
  }

  /**
   * Tests internal path construction.
   */
  #[DataProvider('dataProviderBuildInternalPath')]
  public function testBuildInternalPath(?string $content_type, array $topic_ids, array $section_ids, string $expected, string $prefix = 'feed'): void {
    $builder = $this->createBuilder($prefix);
    $paragraph = $this->createParagraph($content_type, $topic_ids, $section_ids);

    $this->assertEquals($expected, $builder->buildInternalPath($paragraph));
  }

  /**
   * Data provider for testBuildInternalPath.
   */
  public static function dataProviderBuildInternalPath(): \Iterator {
    yield 'blog list with single topic' => [
      'civictheme_page', [1], [], 'feed/civictheme_page/1/all',
    ];
    yield 'multi-topic list' => [
      'civictheme_page', [1, 2, 3], [], 'feed/civictheme_page/1+2+3/all',
    ];
    yield 'no topics' => [
      'civictheme_page', [], [], 'feed/civictheme_page/all/all',
    ];
    yield 'no content type' => [
      NULL, [], [], 'feed/all/all/all',
    ];
    yield 'with site sections' => [
      'civictheme_page', [1], [5, 6], 'feed/civictheme_page/1/5+6',
    ];
    yield 'custom path prefix' => [
      'civictheme_page', [1], [], 'rss/civictheme_page/1/all', 'rss',
    ];
  }

  /**
   * Tests alias path construction.
   */
  public function testBuildAliasPath(): void {
    $builder = $this->createBuilder('feed');
    $paragraph = $this->createParagraph(slug: 'blog');

    $this->assertEquals('/feed/blog', $builder->buildAliasPath($paragraph));
  }

  /**
   * Tests alias path with custom prefix.
   */
  public function testBuildAliasPathWithCustomPrefix(): void {
    $builder = $this->createBuilder('rss');
    $paragraph = $this->createParagraph(slug: 'news');

    $this->assertEquals('/rss/news', $builder->buildAliasPath($paragraph));
  }

  /**
   * Tests getPrefix returns configured value.
   */
  public function testGetPrefix(): void {
    $builder = $this->createBuilder('custom');

    $this->assertEquals('custom', $builder->getPrefix());
  }

}
