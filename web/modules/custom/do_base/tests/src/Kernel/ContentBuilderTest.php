<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\do_base\ContentBuilder;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the ContentBuilder typed paragraph builders.
 */
#[CoversClass(ContentBuilder::class)]
#[Group('do_base')]
class ContentBuilderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'file',
    'text',
    'link',
    'paragraphs',
    'entity_reference_revisions',
    'do_base',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installEntitySchema('paragraph');

    $this->createParagraphTextField('field_c_p_theme', 'string');
    $this->createParagraphTextField('field_c_p_title', 'string');
    $this->createParagraphTextField('field_c_p_summary', 'string_long');
    $this->createParagraphTextField('field_c_p_content', 'text_long');
    $this->createParagraphTextField('field_p_suffix', 'string');
    $this->createParagraphTextField('field_p_appearance', 'string');
    $this->createParagraphTextField('field_p_eyebrow', 'string');
    $this->createParagraphTextField('field_c_p_list_column_count', 'integer');
    $this->createParagraphTextField('field_p_tagline', 'string');
    $this->createParagraphTextField('field_p_includes', 'string', -1);
    $this->createParagraphTextField('field_p_price_label', 'string');
    $this->createParagraphTextField('field_p_price_value', 'string');
    $this->createParagraphTextField('field_c_p_link', 'link');
    $this->createParagraphTextField('field_c_p_links', 'link', -1);
    $this->createParagraphTextField('field_c_p_list_items', 'entity_reference_revisions', -1, ['target_type' => 'paragraph']);

    $this->createParagraphType('civictheme_content', ['field_c_p_theme', 'field_c_p_content']);
    $this->createParagraphType('civictheme_snippet', ['field_c_p_theme', 'field_c_p_title', 'field_c_p_summary']);
    $this->createParagraphType('do_fact_card', ['field_c_p_theme', 'field_c_p_title', 'field_p_suffix', 'field_c_p_summary']);
    $this->createParagraphType('civictheme_manual_list', ['field_c_p_theme', 'field_c_p_title', 'field_c_p_list_column_count', 'field_p_appearance', 'field_p_eyebrow', 'field_c_p_list_items']);
    $this->createParagraphType('civictheme_callout', ['field_c_p_theme', 'field_c_p_title', 'field_c_p_content', 'field_c_p_links']);
    $this->createParagraphType('do_service_detail', ['field_c_p_theme', 'field_c_p_title', 'field_p_tagline', 'field_c_p_content', 'field_p_includes', 'field_p_price_label', 'field_p_price_value', 'field_c_p_link']);

    NodeType::create(['type' => 'civictheme_page', 'name' => 'Page'])->save();
    NodeType::create(['type' => 'bare', 'name' => 'Bare'])->save();

    $this->createNodeField('field_c_n_banner_title', 'string');
    $this->createNodeField('field_c_n_banner_type', 'string');
    $this->createNodeField('field_c_n_banner_theme', 'string');
    $this->createNodeField('field_c_n_banner_background', 'string');
    $this->createNodeField('field_c_n_banner_featured_image', 'string');
    $this->createNodeField('field_c_n_banner_components', 'entity_reference_revisions', ['target_type' => 'paragraph']);
    $this->createNodeField('field_c_n_banner_components_bott', 'entity_reference_revisions', ['target_type' => 'paragraph']);
  }

  /**
   * Tests snippet() builds a dark snippet with title and summary.
   */
  public function testSnippet(): void {
    $snippet = ContentBuilder::snippet('Snippet title', 'Snippet summary');

    $this->assertInstanceOf(Paragraph::class, $snippet);
    $this->assertSame('civictheme_snippet', $snippet->bundle());
    $this->assertSame('dark', $snippet->get('field_c_p_theme')->value);
    $this->assertSame('Snippet title', $snippet->get('field_c_p_title')->value);
    $this->assertSame('Snippet summary', $snippet->get('field_c_p_summary')->value);
  }

  /**
   * Tests factCard() builds a dark fact card with figure, suffix and label.
   */
  public function testFactCard(): void {
    $card = ContentBuilder::factCard('40', 'Projects', '+');

    $this->assertInstanceOf(Paragraph::class, $card);
    $this->assertSame('do_fact_card', $card->bundle());
    $this->assertSame('dark', $card->get('field_c_p_theme')->value);
    $this->assertSame('40', $card->get('field_c_p_title')->value);
    $this->assertSame('+', $card->get('field_p_suffix')->value);
    $this->assertSame('Projects', $card->get('field_c_p_summary')->value);
  }

  /**
   * Tests factCard() default suffix is empty.
   */
  public function testFactCardDefaultSuffix(): void {
    $card = ContentBuilder::factCard('0', 'Downtime');

    $this->assertSame('', $card->get('field_p_suffix')->value);
  }

  /**
   * Tests manualList() saves its items and references them.
   */
  public function testManualList(): void {
    $items = [
      ContentBuilder::snippet('One', 'First'),
      ContentBuilder::snippet('Two', 'Second'),
    ];

    $list = ContentBuilder::manualList('How it works', 2, 'numbered', $items, 'Process');

    $this->assertInstanceOf(Paragraph::class, $list);
    $this->assertSame('civictheme_manual_list', $list->bundle());
    $this->assertSame('dark', $list->get('field_c_p_theme')->value);
    $this->assertSame('How it works', $list->get('field_c_p_title')->value);
    $this->assertSame(2, $list->get('field_c_p_list_column_count')->value);
    $this->assertSame('numbered', $list->get('field_p_appearance')->value);
    $this->assertSame('Process', $list->get('field_p_eyebrow')->value);
    $this->assertCount(2, $list->get('field_c_p_list_items'));

    // Each item is saved by the builder before being referenced.
    $this->assertNotNull($items[0]->id());
    $this->assertNotNull($items[1]->id());
  }

  /**
   * Tests manualList() default eyebrow is empty.
   */
  public function testManualListDefaultEyebrow(): void {
    $list = ContentBuilder::manualList('Stats', 3, 'stat', [ContentBuilder::snippet('A', 'B')]);

    $this->assertSame('', $list->get('field_p_eyebrow')->value);
  }

  /**
   * Tests callout() with a root-relative link stores an internal URI.
   */
  public function testCalloutWithLink(): void {
    $callout = ContentBuilder::callout('Ready?', '<p>Body</p>', 'Contact us', '/contact');

    $this->assertInstanceOf(Paragraph::class, $callout);
    $this->assertSame('civictheme_callout', $callout->bundle());
    $this->assertSame('dark', $callout->get('field_c_p_theme')->value);
    $this->assertSame('Ready?', $callout->get('field_c_p_title')->value);
    $this->assertSame('<p>Body</p>', $callout->get('field_c_p_content')->value);
    $this->assertSame('civictheme_rich_text', $callout->get('field_c_p_content')->format);
    $this->assertSame('internal:/contact', $callout->get('field_c_p_links')->uri);
    $this->assertSame('Contact us', $callout->get('field_c_p_links')->title);
  }

  /**
   * Tests callout() without a link omits the button.
   */
  public function testCalloutWithoutLink(): void {
    $callout = ContentBuilder::callout('Ready?', '<p>Body</p>');

    $this->assertSame('civictheme_callout', $callout->bundle());
    $this->assertTrue($callout->get('field_c_p_links')->isEmpty());
  }

  /**
   * Tests serviceDetail() builds the service paragraph with all fields.
   */
  public function testServiceDetail(): void {
    $service = ContentBuilder::serviceDetail(
      'Platform audit',
      'Know where you stand',
      ['First paragraph.', 'Second paragraph.'],
      ['Architecture review', 'Performance report'],
      'Typical engagement',
      '$40K - $180K',
      'Book a call',
      '/contact',
    );

    $this->assertInstanceOf(Paragraph::class, $service);
    $this->assertSame('do_service_detail', $service->bundle());
    $this->assertSame('dark', $service->get('field_c_p_theme')->value);
    $this->assertSame('Platform audit', $service->get('field_c_p_title')->value);
    $this->assertSame('Know where you stand', $service->get('field_p_tagline')->value);
    $this->assertSame('<p>First paragraph.</p><p>Second paragraph.</p>', $service->get('field_c_p_content')->value);
    $this->assertSame('Typical engagement', $service->get('field_p_price_label')->value);
    $this->assertSame('$40K - $180K', $service->get('field_p_price_value')->value);
    $this->assertSame('internal:/contact', $service->get('field_c_p_link')->uri);
    $this->assertSame('Book a call', $service->get('field_c_p_link')->title);
    $this->assertCount(2, $service->get('field_p_includes'));
  }

  /**
   * Tests contentRichText() builds a dark content paragraph.
   */
  public function testContentRichText(): void {
    $content = ContentBuilder::contentRichText('<p>Hello</p>');

    $this->assertInstanceOf(Paragraph::class, $content);
    $this->assertSame('civictheme_content', $content->bundle());
    $this->assertSame('dark', $content->get('field_c_p_theme')->value);
    $this->assertSame('<p>Hello</p>', $content->get('field_c_p_content')->value);
    $this->assertSame('civictheme_rich_text', $content->get('field_c_p_content')->format);
  }

  /**
   * Tests stageBanner() turns the node banner into the dark hero.
   */
  public function testStageBanner(): void {
    $node = Node::create([
      'type' => 'civictheme_page',
      'title' => 'Home',
      'field_c_n_banner_background' => 'old-background',
      'field_c_n_banner_featured_image' => 'old-image',
    ]);

    $previous = ContentBuilder::stageBanner($node, 'Hero heading', 'Hero subtitle', ['title' => 'Talk to us', 'uri' => '/contact']);

    $this->assertSame([], $previous);
    $this->assertSame('Hero heading', $node->get('field_c_n_banner_title')->value);
    $this->assertSame('hero', $node->get('field_c_n_banner_type')->value);
    $this->assertSame('dark', $node->get('field_c_n_banner_theme')->value);
    $this->assertTrue($node->get('field_c_n_banner_background')->isEmpty());
    $this->assertTrue($node->get('field_c_n_banner_featured_image')->isEmpty());

    $components = $node->get('field_c_n_banner_components')->referencedEntities();
    $this->assertCount(1, $components);

    $intro = reset($components);
    $this->assertInstanceOf(Paragraph::class, $intro);
    $this->assertSame('civictheme_content', $intro->bundle());
    $this->assertSame('dark', $intro->get('field_c_p_theme')->value);
    $this->assertStringContainsString('Hero subtitle', $intro->get('field_c_p_content')->value);
    $this->assertStringContainsString('href="/contact"', $intro->get('field_c_p_content')->value);
    $this->assertStringContainsString('Talk to us', $intro->get('field_c_p_content')->value);
    $this->assertNotNull($intro->id());
  }

  /**
   * Tests stageBanner() without a subtitle adds no banner component.
   */
  public function testStageBannerWithoutSubtitle(): void {
    $node = Node::create(['type' => 'civictheme_page', 'title' => 'Plain']);

    ContentBuilder::stageBanner($node, 'Just a heading');

    $this->assertSame('Just a heading', $node->get('field_c_n_banner_title')->value);
    $this->assertTrue($node->get('field_c_n_banner_components')->isEmpty());
  }

  /**
   * Tests stageBanner() skips banner fields the node bundle does not have.
   */
  public function testStageBannerSkipsMissingFields(): void {
    $node = Node::create(['type' => 'bare', 'title' => 'No banner fields']);

    $previous = ContentBuilder::stageBanner($node, 'Heading', 'Subtitle', ['title' => 'Go', 'uri' => '/contact']);

    $this->assertSame([], $previous);
    $this->assertFalse($node->hasField('field_c_n_banner_title'));
  }

  /**
   * Tests stageBanner() returns previously referenced banner components.
   */
  public function testStageBannerReturnsPreviousComponents(): void {
    $existing = ContentBuilder::contentRichText('<p>Old</p>');
    $existing->save();

    $node = Node::create([
      'type' => 'civictheme_page',
      'title' => 'Home',
      'field_c_n_banner_components' => [$existing],
    ]);

    $previous = ContentBuilder::stageBanner($node, 'Hero heading', 'New subtitle');

    $this->assertCount(1, $previous);
    $previous_item = reset($previous);
    $this->assertInstanceOf(EntityInterface::class, $previous_item);
    $this->assertSame($existing->id(), $previous_item->id());
  }

  /**
   * Tests deleteEntities() deletes each saved entity.
   */
  public function testDeleteEntities(): void {
    $one = ContentBuilder::contentRichText('<p>One</p>');
    $one->save();
    $two = ContentBuilder::contentRichText('<p>Two</p>');
    $two->save();

    $id_one = $one->id();
    $id_two = $two->id();

    ContentBuilder::deleteEntities([$one, $two]);

    $this->assertNull(Paragraph::load($id_one));
    $this->assertNull(Paragraph::load($id_two));
  }

  /**
   * Tests contactInfo() returns the expected contact rich-text markers.
   */
  public function testContactInfo(): void {
    $html = ContentBuilder::contactInfo();

    $this->assertStringContainsString('info@drevops.com', $html);
    $this->assertStringContainsString('What to expect', $html);
  }

  /**
   * Tests blogBody() returns the expected blog rich-text markers.
   */
  public function testBlogBody(): void {
    $html = ContentBuilder::blogBody();

    $this->assertStringContainsString('ct-table', $html);
    $this->assertStringContainsString('language-php', $html);
  }

  /**
   * Creates a paragraph field storage and config on the given bundles.
   *
   * @param string $field_name
   *   The field machine name.
   * @param string $type
   *   The field storage type.
   * @param int $cardinality
   *   The field cardinality.
   * @param array $settings
   *   Field storage settings.
   */
  protected function createParagraphTextField(string $field_name, string $type, int $cardinality = 1, array $settings = []): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'paragraph',
      'type' => $type,
      'cardinality' => $cardinality,
      'settings' => $settings,
    ])->save();
  }

  /**
   * Creates a paragraph type and attaches the given fields to it.
   *
   * @param string $bundle
   *   The paragraph type machine name.
   * @param string[] $field_names
   *   The field machine names to attach.
   */
  protected function createParagraphType(string $bundle, array $field_names): void {
    ParagraphsType::create(['id' => $bundle, 'label' => $bundle])->save();

    foreach ($field_names as $field_name) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'paragraph',
        'bundle' => $bundle,
        'label' => $field_name,
      ])->save();
    }
  }

  /**
   * Creates a node field storage and config on the civictheme_page bundle.
   *
   * @param string $field_name
   *   The field machine name.
   * @param string $type
   *   The field storage type.
   * @param array $settings
   *   Field storage settings.
   */
  protected function createNodeField(string $field_name, string $type, array $settings = []): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $type,
      'settings' => $settings,
    ])->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'civictheme_page',
      'label' => $field_name,
    ])->save();
  }

}
