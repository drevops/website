<?php

declare(strict_types=1);

namespace Drupal\Tests\do_ai_alt_text\Kernel;

use Drupal\do_ai_alt_text\AltTextGenerator;
use Drupal\do_ai_alt_text\Exception\AltTextGenerationException;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\do_ai_alt_text\Traits\AiProviderStubTrait;
use Drupal\Tests\do_ai_alt_text\Traits\ImageMediaCreationTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for AltTextGenerator against real entities.
 */
#[CoversClass(AltTextGenerator::class)]
#[Group('do_ai_alt_text')]
class AltTextGeneratorTest extends KernelTestBase {

  use AiProviderStubTrait;
  use ImageMediaCreationTrait;
  use MediaTypeCreationTrait;

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
    'key',
    'ai',
    'ai_image_alt_text',
    'do_ai_alt_text',
  ];

  /**
   * Alt text the stubbed provider answers with.
   */
  const GENERATED_ALT = 'A generated description.';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['field', 'system', 'image', 'media', 'ai_image_alt_text']);

    $this->container->set('ai_image_alt_text.provider', $this->createAiProviderHelper($this->createAiProvider(self::GENERATED_ALT)));
  }

  /**
   * Tests that the alt text of a media item's image is replaced and saved.
   */
  public function testRegenerateForMediaReplacesAltText(): void {
    // Prepare.
    $media_type = $this->createMediaType('image', ['id' => 'test_image', 'label' => 'Image']);
    $field_name = $this->getSourceFieldName((string) $media_type->id());
    $media = $this->createImageMedia('test_image', $field_name, 'Hand written alt text.');

    // Act.
    $updated = $this->generator()->regenerateForMedia($media);

    // Assert.
    $this->assertSame(1, $updated);
    $this->assertSame(self::GENERATED_ALT, Media::load($media->id())->get($field_name)->alt);
    // The read-only thumbnail base field must never reach the provider.
    $this->assertSame(1, $this->chatCalls);
  }

  /**
   * Tests that the thumbnail's copy of the alt text is kept in step.
   */
  public function testRegenerateForMediaSyncsThumbnailAltText(): void {
    // Prepare.
    $media_type = $this->createMediaType('image', ['id' => 'test_image', 'label' => 'Image']);
    $field_name = $this->getSourceFieldName((string) $media_type->id());
    $media = $this->createImageMedia('test_image', $field_name, 'Hand written alt text.');
    $this->assertSame('Hand written alt text.', $media->get('thumbnail')->alt);

    // Act.
    $this->generator()->regenerateForMedia($media);

    // Assert.
    $this->assertSame(self::GENERATED_ALT, Media::load($media->id())->get('thumbnail')->alt);
  }

  /**
   * Tests that only the source field's text reaches the thumbnail.
   */
  public function testRegenerateForMediaLeavesThumbnailAloneForOtherFields(): void {
    // Prepare.
    $media_type = $this->createMediaType('file', ['id' => 'test_file', 'label' => 'File']);
    $this->addImageField('test_file', 'field_extra_images', 1);
    $file = File::create(['uri' => 'public://document.txt']);
    file_put_contents($file->getFileUri(), 'Text.');
    $file->save();
    $media = Media::create([
      'bundle' => 'test_file',
      'name' => 'Document',
      $this->getSourceFieldName((string) $media_type->id()) => ['target_id' => $file->id()],
      'field_extra_images' => ['target_id' => $this->createImageFile()->id(), 'alt' => 'Hand written alt text.'],
    ]);
    $media->save();
    $thumbnail_alt = $media->get('thumbnail')->alt;

    // Act.
    $updated = $this->generator()->regenerateForMedia($media);

    // Assert.
    $this->assertSame(1, $updated);
    $this->assertSame($thumbnail_alt, Media::load($media->id())->get('thumbnail')->alt);
  }

  /**
   * Tests that every value of a multi-value image field is described.
   */
  public function testRegenerateForMediaDescribesEveryDelta(): void {
    // Prepare.
    $media_type = $this->createMediaType('image', ['id' => 'test_image', 'label' => 'Image']);
    $field_name = $this->getSourceFieldName((string) $media_type->id());
    $this->addImageField('test_image', 'field_extra_images', 2);
    $media = $this->createImageMedia('test_image', $field_name, 'Hand written alt text.');
    $media->set('field_extra_images', [
      ['target_id' => $this->createImageFile()->id(), 'alt' => 'First.'],
      ['target_id' => $this->createImageFile()->id(), 'alt' => 'Second.'],
    ]);
    $media->save();

    // Act.
    $updated = $this->generator()->regenerateForMedia($media);

    // Assert.
    $this->assertSame(3, $updated);
    $reloaded = Media::load($media->id());
    $this->assertSame(self::GENERATED_ALT, $reloaded->get('field_extra_images')->get(0)->get('alt')->getValue());
    $this->assertSame(self::GENERATED_ALT, $reloaded->get('field_extra_images')->get(1)->get('alt')->getValue());
  }

  /**
   * Tests that a field configured without an alt text is left alone.
   */
  public function testRegenerateForMediaSkipsFieldsWithoutAltText(): void {
    // Prepare.
    $media_type = $this->createMediaType('image', ['id' => 'test_image', 'label' => 'Image']);
    $field_name = $this->getSourceFieldName((string) $media_type->id());
    $field = FieldConfig::loadByName('media', 'test_image', $field_name);
    $this->assertInstanceOf(FieldConfig::class, $field);
    $field->setSetting('alt_field', FALSE)->save();
    $media = $this->createImageMedia('test_image', $field_name, 'Hand written alt text.');

    // Act.
    $updated = $this->generator()->regenerateForMedia($media);

    // Assert.
    $this->assertSame(0, $updated);
    $this->assertSame(0, $this->chatCalls);
  }

  /**
   * Tests that a media type carrying no image is a no-op.
   */
  public function testRegenerateForMediaSkipsMediaWithoutImages(): void {
    // Prepare.
    $media_type = $this->createMediaType('file', ['id' => 'test_file', 'label' => 'File']);
    $file = File::create(['uri' => 'public://document.txt']);
    file_put_contents($file->getFileUri(), 'Text.');
    $file->save();
    $media = Media::create([
      'bundle' => 'test_file',
      'name' => 'Document',
      $this->getSourceFieldName((string) $media_type->id()) => ['target_id' => $file->id()],
    ]);
    $media->save();

    // Act.
    $updated = $this->generator()->regenerateForMedia($media);

    // Assert.
    $this->assertSame(0, $updated);
    $this->assertSame(0, $this->chatCalls);
  }

  /**
   * Tests that an image field pointing at a deleted file is skipped.
   */
  public function testRegenerateForMediaSkipsMissingFile(): void {
    // Prepare.
    $media_type = $this->createMediaType('image', ['id' => 'test_image', 'label' => 'Image']);
    $field_name = $this->getSourceFieldName((string) $media_type->id());
    $media = $this->createImageMedia('test_image', $field_name, 'Hand written alt text.');
    File::load($media->get($field_name)->target_id)->delete();
    $media = Media::load($media->id());

    // Act.
    $updated = $this->generator()->regenerateForMedia($media);

    // Assert.
    $this->assertSame(0, $updated);
    $this->assertSame(0, $this->chatCalls);
  }

  /**
   * Tests that a failure part way through persists nothing.
   */
  public function testRegenerateForMediaLeavesEntityUntouchedOnFailure(): void {
    // Prepare.
    $media_type = $this->createMediaType('image', ['id' => 'test_image', 'label' => 'Image']);
    $field_name = $this->getSourceFieldName((string) $media_type->id());
    $this->addImageField('test_image', 'field_extra_images', 1);
    $media = $this->createImageMedia('test_image', $field_name, 'First alt text.');
    $media->set('field_extra_images', ['target_id' => $this->createImageFile()->id(), 'alt' => 'Second alt text.']);
    $media->save();

    // The first image is described before the second one fails, so the write
    // has already started when the run is abandoned.
    $this->chatFailure = new \RuntimeException('Quota exceeded.');
    $this->chatFailsAtCall = 2;

    // Assert.
    $this->expectException(AltTextGenerationException::class);

    // Act.
    try {
      $this->generator()->regenerateForMedia($media);
    }
    finally {
      $reloaded = Media::load($media->id());
      $this->assertSame(2, $this->chatCalls);
      $this->assertSame('First alt text.', $reloaded->get($field_name)->alt);
      $this->assertSame('Second alt text.', $reloaded->get('field_extra_images')->alt);
    }
  }

  /**
   * Returns the generator under test.
   */
  protected function generator(): AltTextGenerator {
    return $this->container->get('do_ai_alt_text.generator');
  }

}
