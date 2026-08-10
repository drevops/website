<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Kernel;

use Drupal\do_base\Hook\MetatagsAlterHook;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the image and text resolved for a page's share card.
 *
 * The hook is driven directly rather than through a rendered page, so each
 * branch of the resolver is reachable on its own. The rendered end of this
 * lives in the Behat feature.
 */
#[Group('do_base')]
class MetatagsAlterHookTest extends DoBaseKernelTestBase {

  /**
   * Machine name of the media type holding shareable images.
   */
  protected const MEDIA_TYPE = 'civictheme_image';

  /**
   * Site name asserted against.
   */
  protected const SITE_NAME = '[TEST] DrevOps';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'media',
    'node',
    'do_base',
  ];

  /**
   * The hook under test.
   */
  protected MetatagsAlterHook $hook;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['field', 'system', 'image', 'media', 'node']);

    $this->config('system.site')->set('name', static::SITE_NAME)->save();

    // The site uses a focal point effect, which needs a module this test does
    // not install. Only the resulting dimensions matter here, so a core effect
    // producing the same size stands in for it.
    $style = ImageStyle::create(['name' => 'social_share', 'label' => 'Social share']);
    $style->addImageEffect([
      'id' => 'image_scale_and_crop',
      'data' => ['width' => 1200, 'height' => 630],
    ]);
    $style->save();

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    $this->createImageMediaType();
    $this->createThumbnailField();

    $this->hook = new MetatagsAlterHook(
      $this->container->get('entity_type.manager'),
      $this->container->get('config.factory'),
      $this->container->get('extension.list.module'),
    );
  }

  /**
   * Tests that a page with nothing of its own still carries an image.
   */
  public function testPageWithNothingOfItsOwnGetsTheSiteImage(): void {
    // Prepare.
    $metatags = [];

    // Act.
    $this->alter($metatags);

    // Assert.
    $this->assertFallbackImage($metatags);
  }

  /**
   * Tests that a thumbnail becomes the card image, sized by the image style.
   */
  public function testThumbnailBecomesTheCardImage(): void {
    // Prepare.
    $media = $this->createImageMedia($this->createImageFile(), '[TEST] A thumbnail');
    $metatags = [];

    // Act.
    $this->alter($metatags, $this->createPage($media));

    // Assert.
    $this->assertStringContainsString('/styles/social_share/', $metatags['og_image']);
    $this->assertMatchesRegularExpression('#^https?://#', $metatags['og_image']);
    $this->assertSame($metatags['og_image'], $metatags['twitter_cards_image']);
    $this->assertSame('1200', $metatags['og_image_width']);
    $this->assertSame('630', $metatags['og_image_height']);
    $this->assertSame('[TEST] A thumbnail', $metatags['og_image_alt']);
    $this->assertSame('[TEST] A thumbnail', $metatags['twitter_cards_image_alt']);
  }

  /**
   * Tests that a thumbnail no image style can serve is passed over.
   */
  #[DataProvider('dataProviderUnusableThumbnailIsPassedOver')]
  public function testUnusableThumbnailIsPassedOver(string $extension, bool $write_file): void {
    // Prepare.
    $media = $this->createImageMedia($this->createImageFile($extension, $write_file), '[TEST] Unusable');
    $metatags = [];

    // Act.
    $this->alter($metatags, $this->createPage($media));

    // Assert.
    $this->assertFallbackImage($metatags);
  }

  /**
   * Data provider for testUnusableThumbnailIsPassedOver.
   */
  public static function dataProviderUnusableThumbnailIsPassedOver(): \Iterator {
    yield 'no image toolkit can derive an svg' => ['svg', TRUE];
    yield 'the file is recorded but not present' => ['png', FALSE];
  }

  /**
   * Tests that an entity with no thumbnail to offer falls back.
   */
  #[DataProvider('dataProviderEntityWithoutThumbnailFallsBack')]
  public function testEntityWithoutThumbnailFallsBack(bool $with_field): void {
    // Prepare.
    $metatags = [];
    $node = $with_field ? $this->createPage() : $this->createNodeWithoutThumbnailField();

    // Act.
    $this->alter($metatags, $node);

    // Assert.
    $this->assertFallbackImage($metatags);
  }

  /**
   * Data provider for testEntityWithoutThumbnailFallsBack.
   */
  public static function dataProviderEntityWithoutThumbnailFallsBack(): \Iterator {
    yield 'the field exists but is empty' => [TRUE];
    yield 'the bundle has no thumbnail field' => [FALSE];
  }

  /**
   * Tests that a thumbnail holding no image at all is passed over.
   *
   * The image field is required on the media form, so only an item created
   * around that form reaches this state, but the resolver still meets it.
   */
  public function testThumbnailWithoutAnImageIsPassedOver(): void {
    // Prepare.
    $media = Media::create(['bundle' => static::MEDIA_TYPE, 'name' => '[TEST] Empty Media']);
    $media->save();
    $metatags = [];

    // Act.
    $this->alter($metatags, $this->createPage($media));

    // Assert.
    $this->assertFallbackImage($metatags);
  }

  /**
   * Tests that an image chosen by hand on the entity is left alone.
   */
  public function testImageChosenByHandIsLeftAlone(): void {
    // Prepare.
    $chosen = 'https://example.com/chosen.png';
    $metatags = ['og_image' => $chosen];

    // Act.
    $this->alter($metatags);

    // Assert.
    $this->assertSame($chosen, $metatags['og_image']);
    $this->assertSame($chosen, $metatags['twitter_cards_image'], 'The choice is expected to carry over to the tag that has none.');
    $this->assertArrayNotHasKey('og_image_width', $metatags);
    $this->assertArrayNotHasKey('og_image_height', $metatags);
    $this->assertArrayNotHasKey('og_image_alt', $metatags);
    $this->assertArrayNotHasKey('twitter_cards_image_alt', $metatags);
  }

  /**
   * Tests that an image chosen for X alone leaves Open Graph one of its own.
   */
  public function testImageChosenForTwitterAloneLeavesOpenGraphResolved(): void {
    // Prepare.
    $chosen = 'https://example.com/chosen.png';
    $metatags = ['twitter_cards_image' => $chosen];

    // Act.
    $this->alter($metatags);

    // Assert.
    $this->assertSame($chosen, $metatags['twitter_cards_image']);
    $this->assertStringEndsWith('/assets/social-share.jpg', $metatags['og_image']);
    $this->assertArrayNotHasKey('twitter_cards_image_alt', $metatags);
  }

  /**
   * Tests that the social tags take the page's own title and description.
   */
  public function testSocialTextIsTakenFromTheTitleAndDescription(): void {
    // Prepare.
    $metatags = ['title' => '[TEST] A title', 'description' => '[TEST] A description'];

    // Act.
    $this->alter($metatags);

    // Assert.
    $this->assertSame('[TEST] A title', $metatags['og_title']);
    $this->assertSame('[TEST] A title', $metatags['twitter_cards_title']);
    $this->assertSame('[TEST] A description', $metatags['og_description']);
    $this->assertSame('[TEST] A description', $metatags['twitter_cards_description']);
  }

  /**
   * Tests that social text chosen by hand is left alone.
   */
  #[DataProvider('dataProviderSocialTextChosenByHandIsLeftAlone')]
  public function testSocialTextChosenByHandIsLeftAlone(string $tag, string $source): void {
    // Prepare.
    $metatags = [$source => '[TEST] Derived', $tag => '[TEST] Chosen'];

    // Act.
    $this->alter($metatags);

    // Assert.
    $this->assertSame('[TEST] Chosen', $metatags[$tag]);
  }

  /**
   * Data provider for testSocialTextChosenByHandIsLeftAlone.
   */
  public static function dataProviderSocialTextChosenByHandIsLeftAlone(): \Iterator {
    yield 'open graph title' => ['og_title', 'title'];
    yield 'open graph description' => ['og_description', 'description'];
    yield 'twitter title' => ['twitter_cards_title', 'og_title'];
    yield 'twitter description' => ['twitter_cards_description', 'og_description'];
  }

  /**
   * Tests that a page missing a title or description gets no social text.
   *
   * An empty tag is dropped rather than rendered, so nothing is gained by
   * inventing a value here.
   */
  public function testSocialTextIsNotInventedWhenThereIsNone(): void {
    // Prepare.
    $metatags = [];

    // Act.
    $this->alter($metatags);

    // Assert.
    $this->assertArrayNotHasKey('og_title', $metatags);
    $this->assertArrayNotHasKey('og_description', $metatags);
    $this->assertArrayNotHasKey('twitter_cards_title', $metatags);
    $this->assertArrayNotHasKey('twitter_cards_description', $metatags);
  }

  /**
   * Tests that an over-long description is cut to what a result shows.
   */
  #[DataProvider('dataProviderLongDescriptionIsTrimmed')]
  public function testLongDescriptionIsTrimmed(string $tag): void {
    // Prepare.
    $description = '[TEST] ' . str_repeat('word ', 60);
    $attachments = $this->headAttachments([$tag => $description]);

    // Act.
    $this->hook->trimDescriptions($attachments);

    // Assert.
    $trimmed = $attachments['#attached']['html_head'][0][0]['#attributes']['content'];
    $this->assertLessThanOrEqual(MetatagsAlterHook::DESCRIPTION_MAX_LENGTH, mb_strlen($trimmed));
    $this->assertStringEndsWith('…', $trimmed);
    $this->assertStringStartsWith('[TEST] word', $trimmed);
  }

  /**
   * Data provider for testLongDescriptionIsTrimmed.
   */
  public static function dataProviderLongDescriptionIsTrimmed(): \Iterator {
    yield 'search result' => ['description'];
    yield 'open graph' => ['og_description'];
    yield 'twitter' => ['twitter_cards_description'];
  }

  /**
   * Tests that a description already short enough is left alone.
   */
  public function testShortDescriptionIsLeftAlone(): void {
    // Prepare.
    $description = '[TEST] Short enough to show in full.';
    $attachments = $this->headAttachments(['description' => $description]);

    // Act.
    $this->hook->trimDescriptions($attachments);

    // Assert.
    $this->assertSame($description, $attachments['#attached']['html_head'][0][0]['#attributes']['content']);
  }

  /**
   * Tests that tags other than the descriptions are left alone.
   */
  public function testOtherTagsAreLeftAlone(): void {
    // Prepare.
    $title = '[TEST] ' . str_repeat('word ', 60);
    $attachments = $this->headAttachments(['title' => $title]);

    // Act.
    $this->hook->trimDescriptions($attachments);

    // Assert.
    $this->assertSame($title, $attachments['#attached']['html_head'][0][0]['#attributes']['content']);
  }

  /**
   * Tests that attachments carrying no head elements are handled.
   */
  public function testAttachmentsWithoutHeadElementsAreHandled(): void {
    // Prepare.
    $attachments = [];

    // Act.
    $this->hook->trimDescriptions($attachments);

    // Assert.
    $this->assertSame([], $attachments);
  }

  /**
   * Tests that an article's structured data carries the resolved image.
   *
   * Only the Open Graph and Twitter tags get a host prepended for them, so an
   * absolute URL has to arrive here already built.
   */
  public function testArticleStructuredDataTakesTheSameImage(): void {
    // Prepare.
    $metatags = ['schema_article_type' => 'Article'];

    // Act.
    $this->alter($metatags);

    // Assert.
    $image = unserialize($metatags['schema_article_image'], ['allowed_classes' => FALSE]);
    $this->assertIsArray($image);
    $this->assertSame('ImageObject', $image['@type']);
    $this->assertSame($metatags['og_image'], $image['url']);
    $this->assertMatchesRegularExpression('#^https?://#', $image['url']);
  }

  /**
   * Tests that a page carrying no article markup gets no article image.
   */
  public function testPageWithoutArticleMarkupGetsNoArticleImage(): void {
    // Prepare.
    $metatags = [];

    // Act.
    $this->alter($metatags);

    // Assert.
    $this->assertArrayNotHasKey('schema_article_image', $metatags);
  }

  /**
   * Tests that an article image chosen by hand is left alone.
   */
  public function testArticleImageChosenByHandIsLeftAlone(): void {
    // Prepare.
    $metatags = ['schema_article_type' => 'Article', 'schema_article_image' => 'chosen'];

    // Act.
    $this->alter($metatags);

    // Assert.
    $this->assertSame('chosen', $metatags['schema_article_image']);
  }

  /**
   * Runs the hook over the given tags.
   */
  protected function alter(array &$metatags, ?NodeInterface $node = NULL): void {
    $context = ['entity' => $node];
    $this->hook->alter($metatags, $context);
  }

  /**
   * Builds page attachments carrying one rendered meta tag per given value.
   *
   * @param array $tags
   *   Tag content keyed by metatag name.
   *
   * @return array
   *   The attachments, shaped as metatag hands them over.
   */
  protected function headAttachments(array $tags): array {
    $attachments = [];

    foreach ($tags as $name => $content) {
      $attachments['#attached']['html_head'][] = [
        ['#tag' => 'meta', '#attributes' => ['name' => $name, 'content' => $content]],
        $name,
      ];
    }

    return $attachments;
  }

  /**
   * Asserts the tags carry the site-wide image rather than one of their own.
   */
  protected function assertFallbackImage(array $metatags): void {
    $this->assertStringEndsWith('/modules/custom/do_base/assets/social-share.jpg', $metatags['og_image']);
    $this->assertMatchesRegularExpression('#^https?://#', $metatags['og_image'], 'Both networks and schema.org reject a relative image URL.');
    $this->assertSame($metatags['og_image'], $metatags['twitter_cards_image']);
    $this->assertSame('1200', $metatags['og_image_width']);
    $this->assertSame('630', $metatags['og_image_height']);
    $this->assertSame(static::SITE_NAME, $metatags['og_image_alt']);
    $this->assertSame(static::SITE_NAME, $metatags['twitter_cards_image_alt']);
  }

  /**
   * Creates a page, optionally carrying a thumbnail.
   */
  protected function createPage(?MediaInterface $thumbnail = NULL): NodeInterface {
    $node = Node::create([
      'type' => 'page',
      'title' => '[TEST] Page',
      'field_c_n_thumbnail' => $thumbnail instanceof MediaInterface ? ['target_id' => $thumbnail->id()] : NULL,
    ]);
    $node->save();

    return $node;
  }

  /**
   * Creates a node of a bundle that has no thumbnail field at all.
   */
  protected function createNodeWithoutThumbnailField(): NodeInterface {
    NodeType::create(['type' => 'bare', 'name' => 'Bare'])->save();

    $node = Node::create(['type' => 'bare', 'title' => '[TEST] Bare Page']);
    $node->save();

    return $node;
  }

  /**
   * Creates a media item wrapping an image file.
   */
  protected function createImageMedia(FileInterface $file, string $alt): MediaInterface {
    $media = Media::create([
      'bundle' => static::MEDIA_TYPE,
      'name' => '[TEST] Media',
      'field_c_m_image' => ['target_id' => $file->id(), 'alt' => $alt],
    ]);
    $media->save();

    return $media;
  }

  /**
   * Creates a file entity, optionally without writing the file itself.
   */
  protected function createImageFile(string $extension = 'png', bool $write_file = TRUE): FileInterface {
    $uri = 'public://' . $this->randomMachineName() . '.' . $extension;

    if ($write_file) {
      $contents = $extension === 'svg'
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630"></svg>'
        : (string) file_get_contents($this->root . '/core/tests/fixtures/files/image-1.png');

      file_put_contents($uri, $contents);
    }

    $file = File::create(['uri' => $uri]);
    $file->setPermanent();
    $file->save();

    return $file;
  }

  /**
   * Creates the media type shareable images live in.
   */
  protected function createImageMediaType(): void {
    $media_type = MediaType::create([
      'id' => static::MEDIA_TYPE,
      'label' => 'Image',
      'source' => 'image',
    ]);
    $media_type->save();

    FieldStorageConfig::create([
      'entity_type' => 'media',
      'field_name' => 'field_c_m_image',
      'type' => 'image',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'media',
      'bundle' => static::MEDIA_TYPE,
      'field_name' => 'field_c_m_image',
      'label' => 'Image',
      'settings' => ['alt_field' => TRUE, 'file_extensions' => 'png jpg svg'],
    ])->save();

    $media_type->set('source_configuration', ['source_field' => 'field_c_m_image'])->save();
  }

  /**
   * Adds the thumbnail field pages carry.
   */
  protected function createThumbnailField(): void {
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_c_n_thumbnail',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'media'],
    ])->save();

    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'field_c_n_thumbnail',
      'label' => 'Thumbnail',
      'settings' => [
        'handler' => 'default:media',
        'handler_settings' => ['target_bundles' => [static::MEDIA_TYPE => static::MEDIA_TYPE]],
      ],
    ])->save();
  }

}
