<?php

declare(strict_types=1);

namespace Drupal\do_ai_alt_text;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\ChatOutput;
use Drupal\ai\OperationType\GenericType\ImageFile;
use Drupal\ai\Plugin\ProviderProxy;
use Drupal\ai_image_alt_text\ProviderHelper;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\do_ai_alt_text\Exception\AltTextGenerationException;
use Drupal\file\FileInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Generates image alt text with AI and writes it back onto media items.
 */
class AltTextGenerator {

  /**
   * Length of the "alt" column on an image field.
   */
  const ALT_MAX_LENGTH = 512;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LanguageManagerInterface $languageManager,
    protected TwigEnvironment $twig,
    protected ProviderHelper $providerHelper,
    protected MimeTypeGuesserInterface $mimeTypeGuesser,
  ) {
  }

  /**
   * Replaces the alt text of every image a media item references.
   *
   * The media item is saved once all values are in place, so a failure part
   * way through leaves it exactly as it was.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media item holding the image fields.
   *
   * @return int
   *   Number of image values whose alt text was replaced.
   *
   * @throws \Drupal\do_ai_alt_text\Exception\AltTextGenerationException
   *   When alt text could not be generated for one of the images.
   */
  public function regenerateForMedia(MediaInterface $media): int {
    $langcode = $media->language()->getId();
    $field_names = $this->getAltTextFieldNames($media);
    $updated = 0;

    foreach ($field_names as $field_name) {
      foreach ($media->get($field_name) as $item) {
        $file = $item->entity;

        if (!$file instanceof FileInterface) {
          continue;
        }

        $mime_type = $file->getMimeType();

        if (!is_string($mime_type) || !str_starts_with($mime_type, 'image/')) {
          continue;
        }

        $item->alt = $this->generateForFile($file, $langcode);
        $updated++;
      }
    }

    if ($updated === 0) {
      return 0;
    }

    $this->syncThumbnailAltText($media, $field_names);
    $media->save();

    return $updated;
  }

  /**
   * Describes an image file as alt text.
   *
   * @param \Drupal\file\FileInterface $file
   *   Image file to describe.
   * @param string $langcode
   *   Language the alt text is written in.
   *
   * @return string
   *   Generated alt text.
   *
   * @throws \Drupal\do_ai_alt_text\Exception\AltTextGenerationException
   *   When the file is not an image, no provider is configured, the provider
   *   fails, or the provider returns nothing usable.
   */
  public function generateForFile(FileInterface $file, string $langcode): string {
    $mime_type = $file->getMimeType();

    if (!is_string($mime_type) || !str_starts_with($mime_type, 'image/')) {
      throw new AltTextGenerationException(sprintf('File %s is not an image.', $file->getFilename()));
    }

    $provider = $this->providerHelper->getSetProvider();
    $ai_provider = $provider['provider_id'] ?? NULL;

    if (!$ai_provider instanceof ProviderProxy) {
      throw new AltTextGenerationException('No AI provider is configured for image vision.');
    }

    $settings = $this->configFactory->get('ai_image_alt_text.settings');

    $prompt = $this->twig->renderInline((string) $settings->get('prompt'), [
      'entity_lang_name' => $this->languageManager->getLanguageName($langcode) ?: 'English',
      'filename' => $file->getFilename(),
    ]);

    $input = new ChatInput([
      new ChatMessage('user', (string) $prompt, [$this->buildImageFile($file, (string) $settings->get('image_style'))]),
    ]);

    try {
      $output = $ai_provider->chat($input, (string) ($provider['model_id'] ?? ''));
      $normalized = $output instanceof ChatOutput ? $output->getNormalized() : NULL;
      $alt_text = $normalized instanceof ChatMessage ? $this->normalise($normalized->getText()) : '';
    }
    catch (\Exception $exception) {
      throw new AltTextGenerationException(sprintf('The AI provider could not describe file %s: %s', $file->getFilename(), $exception->getMessage()), 0, $exception);
    }

    if ($alt_text === '') {
      throw new AltTextGenerationException(sprintf('The AI provider returned no alt text for file %s.', $file->getFilename()));
    }

    return $alt_text;
  }

  /**
   * Collects the fields whose image values carry an alt text.
   *
   * Base fields are skipped so that the read-only "thumbnail" media field is
   * never sent to the provider.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Entity to inspect.
   *
   * @return string[]
   *   Field names.
   */
  protected function getAltTextFieldNames(FieldableEntityInterface $entity): array {
    $field_names = [];

    foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
      if (!$definition instanceof FieldConfigInterface) {
        continue;
      }

      if ($definition->getType() !== 'image' || !$definition->getSetting('alt_field')) {
        continue;
      }

      $field_names[] = $field_name;
    }

    return $field_names;
  }

  /**
   * Carries new alt text over to the media item's thumbnail.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media item that was described.
   * @param string[] $field_names
   *   Fields that were described.
   */
  protected function syncThumbnailAltText(MediaInterface $media, array $field_names): void {
    $source_field = $media->getSource()->getConfiguration()['source_field'] ?? '';

    if (!in_array($source_field, $field_names, TRUE) || $media->get($source_field)->isEmpty()) {
      return;
    }

    // The thumbnail holds its own copy of the alt text and core refreshes it
    // only when the referenced file changes, which rewording alone is not.
    $media->get('thumbnail')->alt = $media->get($source_field)->alt;
  }

  /**
   * Wraps a file in the payload the provider expects.
   *
   * @param \Drupal\file\FileInterface $file
   *   Image file to send.
   * @param string $image_style_name
   *   Image style to scale the image down to before sending it.
   *
   * @return \Drupal\ai\OperationType\GenericType\ImageFile
   *   Image payload.
   *
   * @throws \Drupal\do_ai_alt_text\Exception\AltTextGenerationException
   *   When the image cannot be read from disk.
   */
  protected function buildImageFile(FileInterface $file, string $image_style_name): ImageFile {
    $uri = (string) $file->getFileUri();
    $mime_type = (string) $file->getMimeType();
    $filename = (string) $file->getFilename();

    $image_style = $image_style_name === '' ? NULL : $this->entityTypeManager->getStorage('image_style')->load($image_style_name);

    // A missing or unbuildable style only costs a larger payload, so fall back
    // to the original rather than failing the whole run.
    if ($image_style instanceof ImageStyleInterface) {
      $derivative_uri = $image_style->buildUri($uri);

      if ($image_style->createDerivative($uri, $derivative_uri) && file_exists($derivative_uri)) {
        $uri = $derivative_uri;
        // A style may convert the image, so the derivative's own type is what
        // the provider must be told about.
        $mime_type = $this->mimeTypeGuesser->guessMimeType($derivative_uri) ?: $mime_type;
        $filename = basename($derivative_uri);
      }
    }

    if (!file_exists($uri)) {
      throw new AltTextGenerationException(sprintf('Image file %s could not be read.', $filename));
    }

    $image = new ImageFile();
    $image->setBinary((string) file_get_contents($uri));
    $image->setMimeType($mime_type);
    $image->setFilename($filename);

    return $image;
  }

  /**
   * Flattens provider output into a value an alt attribute can hold.
   *
   * @param string $alt_text
   *   Raw provider output.
   *
   * @return string
   *   Single-line alt text, truncated to the column width.
   */
  protected function normalise(string $alt_text): string {
    $alt_text = trim((string) preg_replace('/\s+/u', ' ', $alt_text));

    return Unicode::truncate($alt_text, self::ALT_MAX_LENGTH, TRUE);
  }

}
