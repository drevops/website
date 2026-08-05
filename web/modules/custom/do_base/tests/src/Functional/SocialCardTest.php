<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the image and text a page carries when it is shared.
 *
 * The cases that matter are the ones where the page has nothing to offer: a
 * thumbnail is optional, most pages have none, and those are the URLs that get
 * shared most. A card that silently loses its image is the failure this covers.
 */
#[Group('do_base')]
class SocialCardTest extends DoBaseFunctionalTestBase {

  /**
   * Machine name of the media type holding shareable images.
   */
  protected const MEDIA_TYPE = 'civictheme_image';

  /**
   * Site name asserted against, in place of the random installer default.
   */
  protected const SITE_NAME = '[TEST] DrevOps';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'file',
    'image',
    'media',
    'metatag',
    'metatag_open_graph',
    'metatag_twitter_cards',
    'do_base',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

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

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $this->createImageMediaType();
    $this->createThumbnailField();
    $this->createMetatagField();
  }

  /**
   * Tests that a thumbnail becomes the card image, sized by the image style.
   */
  public function testThumbnailBecomesTheCardImage(): void {
    // Prepare.
    $media = $this->createImageMedia($this->createImageFile(), '[TEST] A thumbnail');
    $node = $this->createPage('[TEST] Page With Thumbnail', $media);

    // Act.
    $this->drupalGet($node->toUrl());

    // Assert.
    $url = $this->metatagContent('og:image');
    $this->assertStringContainsString('/styles/social_share/', $url);
    $this->assertMatchesRegularExpression('#^https?://#', $url, 'Both networks reject a relative image URL.');
    $this->assertSame($url, $this->metatagContent('twitter:image'));
    $this->assertSame('1200', $this->metatagContent('og:image:width'));
    $this->assertSame('630', $this->metatagContent('og:image:height'));
    $this->assertSame('[TEST] A thumbnail', $this->metatagContent('og:image:alt'));
    $this->assertSame('[TEST] A thumbnail', $this->metatagContent('twitter:image:alt'));
  }

  /**
   * Tests that a page with no thumbnail still carries an image.
   */
  public function testPageWithoutThumbnailFallsBackToTheSiteImage(): void {
    // Prepare.
    $node = $this->createPage('[TEST] Page Without Thumbnail');

    // Act.
    $this->drupalGet($node->toUrl());

    // Assert.
    $this->assertFallbackImage();
  }

  /**
   * Tests that a thumbnail no image style can serve is passed over.
   *
   * The image field accepts SVG, and a file recorded in the database can be
   * absent from an environment that never received it. Either would otherwise
   * produce a derivative URL that resolves to nothing.
   */
  #[DataProvider('dataProviderUnusableThumbnailFallsBackToTheSiteImage')]
  public function testUnusableThumbnailFallsBackToTheSiteImage(string $extension, bool $write_file): void {
    // Prepare.
    $media = $this->createImageMedia($this->createImageFile($extension, $write_file), '[TEST] An unusable thumbnail');
    $node = $this->createPage('[TEST] Page With Unusable Thumbnail', $media);

    // Act.
    $this->drupalGet($node->toUrl());

    // Assert.
    $this->assertFallbackImage();
  }

  /**
   * Data provider for testUnusableThumbnailFallsBackToTheSiteImage.
   */
  public static function dataProviderUnusableThumbnailFallsBackToTheSiteImage(): \Iterator {
    yield 'no image toolkit can derive an svg' => ['svg', TRUE];
    yield 'the file is recorded but not present' => ['png', FALSE];
  }

  /**
   * Tests that a page which is not an entity still carries an image.
   */
  public function testPageWithoutAnEntityFallsBackToTheSiteImage(): void {
    // Act.
    $this->drupalGet('user/login');

    // Assert.
    $this->assertFallbackImage();
  }

  /**
   * Tests that an image chosen by hand on the entity is left alone.
   *
   * Its dimensions and alt text are unknown here, so asserting either would
   * describe the wrong file.
   */
  public function testImageSetByHandIsLeftAlone(): void {
    // Prepare.
    $chosen = 'https://example.com/chosen.png';
    $node = $this->createPage('[TEST] Page With Chosen Image');
    $node->set('field_n_metatags', json_encode(['og_image' => $chosen]))->save();

    // Act.
    $this->drupalGet($node->toUrl());

    // Assert.
    $this->assertSame($chosen, $this->metatagContent('og:image'));
    $this->assertSame($chosen, $this->metatagContent('twitter:image'), 'The choice is expected to carry over to the tag that has none.');
    $this->assertNull($this->getSession()->getPage()->find('css', 'meta[property="og:image:width"]'));
    $this->assertNull($this->getSession()->getPage()->find('css', 'meta[property="og:image:alt"]'));
    $this->assertNull($this->getSession()->getPage()->find('css', 'meta[name="twitter:image:alt"]'));
  }

  /**
   * Tests that an image chosen for X alone still leaves Open Graph an image.
   *
   * The metatag field exposes both image tags, so either can be set without
   * the other. The tag that was not set still needs an image of its own.
   */
  public function testImageChosenForTwitterAloneLeavesOpenGraphResolved(): void {
    // Prepare.
    $chosen = 'https://example.com/chosen.png';
    $node = $this->createPage('[TEST] Page With Chosen Twitter Image');
    $node->set('field_n_metatags', json_encode(['twitter_cards_image' => $chosen]))->save();

    // Act.
    $this->drupalGet($node->toUrl());

    // Assert.
    $this->assertSame($chosen, $this->metatagContent('twitter:image'));
    $this->assertStringEndsWith('/modules/custom/do_base/assets/social-share.jpg', $this->metatagContent('og:image'));
  }

  /**
   * Tests that the social tags take the page's own title and description.
   */
  public function testSocialTextMirrorsTheTitleAndDescription(): void {
    // Prepare.
    $node = $this->createPage('[TEST] Page With Text');
    $node->set('field_n_metatags', json_encode([
      'title' => '[TEST] A title',
      'description' => '[TEST] A description',
    ]))->save();

    // Act.
    $this->drupalGet($node->toUrl());

    // Assert.
    $this->assertSame('[TEST] A title', $this->metatagContent('og:title'));
    $this->assertSame('[TEST] A title', $this->metatagContent('twitter:title'));
    $this->assertSame('[TEST] A description', $this->metatagContent('og:description'));
    $this->assertSame('[TEST] A description', $this->metatagContent('twitter:description'));
  }

  /**
   * Tests that social text chosen by hand is left alone.
   */
  public function testSocialTextSetByHandIsLeftAlone(): void {
    // Prepare.
    $node = $this->createPage('[TEST] Page With Chosen Text');
    $node->set('field_n_metatags', json_encode([
      'description' => '[TEST] A description',
      'og_description' => '[TEST] A social description',
    ]))->save();

    // Act.
    $this->drupalGet($node->toUrl());

    // Assert.
    $this->assertSame('[TEST] A social description', $this->metatagContent('og:description'));
    $this->assertSame('[TEST] A social description', $this->metatagContent('twitter:description'));
  }

  /**
   * Asserts the page carries the site-wide image rather than one of its own.
   */
  protected function assertFallbackImage(): void {
    $url = $this->metatagContent('og:image');

    $this->assertStringEndsWith('/modules/custom/do_base/assets/social-share.jpg', $url);
    $this->assertMatchesRegularExpression('#^https?://#', $url, 'Both networks reject a relative image URL.');
    $this->assertSame($url, $this->metatagContent('twitter:image'));
    $this->assertSame('1200', $this->metatagContent('og:image:width'));
    $this->assertSame('630', $this->metatagContent('og:image:height'));
    $this->assertSame(static::SITE_NAME, $this->metatagContent('og:image:alt'));
    $this->assertSame(static::SITE_NAME, $this->metatagContent('twitter:image:alt'));
  }

  /**
   * Returns the content of a meta tag on the page currently loaded.
   *
   * Open Graph uses 'property' and Twitter uses 'name', so both are tried.
   */
  protected function metatagContent(string $name): string {
    $page = $this->getSession()->getPage();
    $element = $page->find('css', sprintf('meta[property="%s"]', $name)) ?? $page->find('css', sprintf('meta[name="%s"]', $name));

    $this->assertNotNull($element, sprintf('Expected a "%s" meta tag on the page.', $name));

    return (string) $element->getAttribute('content');
  }

  /**
   * Creates a page, optionally carrying a thumbnail.
   */
  protected function createPage(string $title, ?MediaInterface $thumbnail = NULL): NodeInterface {
    return $this->drupalCreateNode([
      'type' => 'page',
      'title' => $title,
      'field_c_n_thumbnail' => $thumbnail instanceof MediaInterface ? ['target_id' => $thumbnail->id()] : NULL,
    ]);
  }

  /**
   * Creates a media item wrapping an image file.
   */
  protected function createImageMedia(FileInterface $file, string $alt): MediaInterface {
    $media = Media::create([
      'bundle' => static::MEDIA_TYPE,
      'name' => '[TEST] Media ' . $this->randomMachineName(),
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

  /**
   * Adds the field an editor overrides meta tags through.
   */
  protected function createMetatagField(): void {
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_n_metatags',
      'type' => 'metatag',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'field_n_metatags',
      'label' => 'Meta tags',
    ])->save();
  }

}
