<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that SVG media display their own artwork.
 */
#[Group('do_base')]
#[RunTestsInSeparateProcesses]
class SvgMediaHookTest extends DoBaseKernelTestBase {

  /**
   * Machine name of the file-source media type.
   */
  protected const string MEDIA_TYPE = 'civictheme_icon';

  /**
   * Machine name of the media type's source field.
   */
  protected const string SOURCE_FIELD = 'field_c_m_icon';

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
    'csp',
    'do_base',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['field', 'system', 'image', 'media']);

    $this->createFileMediaType();
  }

  /**
   * Tests which files a media item uses as its own thumbnail.
   *
   * @param string $extension
   *   Extension of the source file.
   * @param bool $is_own_thumbnail
   *   Whether the source file is expected to be the thumbnail.
   */
  #[DataProvider('dataProviderThumbnail')]
  public function testThumbnail(string $extension, bool $is_own_thumbnail): void {
    // Prepare.
    $file = $this->createSourceFile($extension);

    // Act.
    $media = $this->createMedia($file);

    // Assert.
    $thumbnail = $media->get('thumbnail')->entity;
    $this->assertInstanceOf(FileInterface::class, $thumbnail);
    $this->assertSame($is_own_thumbnail, $thumbnail->getFileUri() === $file->getFileUri());
  }

  /**
   * Data provider for testThumbnail.
   */
  public static function dataProviderThumbnail(): \Iterator {
    yield 'an SVG' => ['svg', TRUE];
    yield 'a text file' => ['txt', FALSE];
  }

  /**
   * Tests that a placeholder thumbnail is replaced when the item is saved.
   */
  public function testPlaceholderThumbnailIsReplacedOnSave(): void {
    // Prepare.
    $file = $this->createSourceFile('svg');
    $media = $this->createMedia($file);
    $placeholder = $this->createSourceFile('png');
    $media->set('thumbnail', ['target_id' => $placeholder->id()]);

    // Act.
    $media->save();

    // Assert.
    $this->assertSame($file->id(), $media->get('thumbnail')->target_id);
  }

  /**
   * Tests how a thumbnail renders through an image style.
   *
   * @param string $extension
   *   Extension of the source file.
   * @param bool $is_styled
   *   Whether the image is expected to render through the image style.
   */
  #[DataProvider('dataProviderThumbnailRendering')]
  public function testThumbnailRendering(string $extension, bool $is_styled): void {
    // Prepare.
    $media = $this->createMedia($this->createSourceFile($extension));
    $build = $media->get('thumbnail')->view(['type' => 'image', 'settings' => ['image_style' => 'thumbnail']]);

    // Act.
    $markup = (string) $this->container->get('renderer')->renderInIsolation($build);

    // Assert.
    $this->assertStringContainsString('<img', $markup);
    $this->assertSame($is_styled, str_contains($markup, '/styles/thumbnail/'));
  }

  /**
   * Data provider for testThumbnailRendering.
   */
  public static function dataProviderThumbnailRendering(): \Iterator {
    yield 'an SVG renders the original file' => ['svg', FALSE];
    yield 'a placeholder renders through the style' => ['txt', TRUE];
  }

  /**
   * Creates a media item around a file.
   */
  protected function createMedia(FileInterface $file): MediaInterface {
    $media = Media::create([
      'bundle' => static::MEDIA_TYPE,
      'name' => '[TEST] Media',
      static::SOURCE_FIELD => ['target_id' => $file->id()],
    ]);
    $media->save();

    return $media;
  }

  /**
   * Creates a file with the given extension.
   */
  protected function createSourceFile(string $extension): FileInterface {
    $uri = 'public://' . $this->randomMachineName() . '.' . $extension;
    file_put_contents($uri, $extension === 'svg' ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"/>' : 'test');

    $file = File::create(['uri' => $uri]);
    $file->setPermanent();
    $file->save();

    return $file;
  }

  /**
   * Creates a media type whose items wrap a plain file.
   */
  protected function createFileMediaType(): void {
    $media_type = MediaType::create([
      'id' => static::MEDIA_TYPE,
      'label' => 'Icon',
      'source' => 'file',
    ]);
    $media_type->save();

    FieldStorageConfig::create([
      'entity_type' => 'media',
      'field_name' => static::SOURCE_FIELD,
      'type' => 'file',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'media',
      'bundle' => static::MEDIA_TYPE,
      'field_name' => static::SOURCE_FIELD,
      'label' => 'File',
      'settings' => ['file_extensions' => 'svg txt'],
    ])->save();

    $media_type->set('source_configuration', ['source_field' => static::SOURCE_FIELD])->save();
  }

}
