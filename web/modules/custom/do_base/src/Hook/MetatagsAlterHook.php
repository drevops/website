<?php

declare(strict_types=1);

namespace Drupal\do_base\Hook;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\media\MediaInterface;
use Drupal\schema_metatag\SchemaMetatagManager;

/**
 * Fills in the image and text a page carries when it is shared.
 */
final class MetatagsAlterHook {

  /**
   * The point past which a search result stops showing the description.
   */
  public const int DESCRIPTION_MAX_LENGTH = 155;

  /**
   * The tags carrying that description.
   */
  private const array DESCRIPTION_TAGS = ['description', 'og_description', 'twitter_cards_description'];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected ModuleExtensionList $moduleExtensionList,
  ) {}

  /**
   * Implements hook_metatags_alter().
   */
  #[Hook('metatags_alter')]
  public function alter(array &$metatags, array &$context): void {
    $entity = $context['entity'] ?? NULL;

    $this->mirrorSocialText($metatags);
    $this->setSocialImage($metatags, $entity instanceof ContentEntityInterface ? $entity : NULL);
  }

  /**
   * Implements hook_metatags_attachments_alter().
   */
  #[Hook('metatags_attachments_alter')]
  public function trimDescriptions(array &$attachments): void {
    // Tokens are replaced after hook_metatags_alter(), so a description is
    // still a token when the tags themselves are altered and only reaches its
    // full length here.
    foreach ($attachments['#attached']['html_head'] ?? [] as $delta => $element) {
      if (!in_array($element[1] ?? '', self::DESCRIPTION_TAGS, TRUE)) {
        continue;
      }

      $content = $element[0]['#attributes']['content'] ?? NULL;

      if (!is_string($content) || mb_strlen($content) <= self::DESCRIPTION_MAX_LENGTH) {
        continue;
      }

      $attachments['#attached']['html_head'][$delta][0]['#attributes']['content'] = Unicode::truncate($content, self::DESCRIPTION_MAX_LENGTH, TRUE, TRUE);
    }
  }

  /**
   * Gives the social tags the page title and description when they have none.
   */
  protected function mirrorSocialText(array &$metatags): void {
    // Metatag reads the entity and bundle defaults only on an ordinary entity
    // route: on the front page, 403 and 404 it stops after the global and
    // special defaults, so a description configured per bundle never reaches
    // those pages. Deriving the social text from the tags that are always
    // resolved covers them, and leaves one place to change the wording.
    $sources = [
      'og_title' => 'title',
      'og_description' => 'description',
      'twitter_cards_title' => 'og_title',
      'twitter_cards_description' => 'og_description',
    ];

    foreach ($sources as $tag => $source) {
      if (empty($metatags[$tag]) && !empty($metatags[$source])) {
        $metatags[$tag] = $metatags[$source];
      }
    }
  }

  /**
   * Gives the social tags the image that represents the page.
   */
  protected function setSocialImage(array &$metatags, ?ContentEntityInterface $entity): void {
    // An image already in the set was chosen by hand on the entity. Its
    // dimensions and alt text describe a file this code never saw, so neither
    // is asserted alongside it.
    if (empty($metatags['og_image'])) {
      $image = $this->socialImage($entity);

      $metatags['og_image'] = $image['url'];
      $metatags['og_image_width'] = (string) $image['width'];
      $metatags['og_image_height'] = (string) $image['height'];
      $metatags['og_image_alt'] = $image['alt'];
    }

    if (empty($metatags['twitter_cards_image'])) {
      $metatags['twitter_cards_image'] = $metatags['og_image'];

      if (!empty($metatags['og_image_alt'])) {
        $metatags['twitter_cards_image_alt'] = $metatags['og_image_alt'];
      }
    }

    // Structured data takes the same image, so an article that fell back to
    // the site-wide one is not left describing an image it does not have. The
    // type guard keeps this to pages already carrying Article markup, which is
    // the only group the tag belongs to.
    if (!empty($metatags['schema_article_type']) && empty($metatags['schema_article_image']) && class_exists(SchemaMetatagManager::class)) {
      $metatags['schema_article_image'] = SchemaMetatagManager::serialize([
        '@type' => 'ImageObject',
        'representativeOfPage' => 'True',
        'url' => $metatags['og_image'],
      ]);
    }
  }

  /**
   * Resolves the image that represents a page when it is shared.
   *
   * @return array{url: string, alt: string, width: int, height: int}
   *   The image URL, its alt text and the dimensions it is served at.
   */
  protected function socialImage(?ContentEntityInterface $entity): array {
    return ($entity instanceof ContentEntityInterface ? $this->socialImageFromThumbnail($entity) : NULL) ?? $this->fallbackImage();
  }

  /**
   * Derives a share image from an entity's thumbnail.
   *
   * @return array{url: string, alt: string, width: int, height: int}|null
   *   The image, or NULL when the entity has no thumbnail that can be derived
   *   into one.
   */
  protected function socialImageFromThumbnail(ContentEntityInterface $entity): ?array {
    if (!$entity->hasField('field_c_n_thumbnail')) {
      return NULL;
    }

    $media = $entity->get('field_c_n_thumbnail')->entity;

    if (!$media instanceof MediaInterface || !$media->hasField('field_c_m_image')) {
      return NULL;
    }

    $item = $media->get('field_c_m_image')->first();

    if (!$item instanceof ImageItem || !$item->entity instanceof FileInterface) {
      return NULL;
    }

    $style = $this->entityTypeManager->getStorage('image_style')->load('social_share');
    $uri = $item->entity->getFileUri();

    // The image field accepts SVG, which no image toolkit can derive, and a
    // referenced file can be absent on an environment that carries the database
    // but not the files. Either way the fallback image is a better share card
    // than a URL that resolves to nothing.
    if (!$style instanceof ImageStyleInterface || !$style->supportsUri($uri) || !file_exists($uri)) {
      return NULL;
    }

    $values = $item->getValue();

    // Reading the dimensions back from the style keeps them true to whatever
    // the effects actually do, so the tags cannot drift from the style config.
    $dimensions = ['width' => $values['width'] ?? NULL, 'height' => $values['height'] ?? NULL];
    $style->transformDimensions($dimensions, $uri);

    return [
      'url' => $style->buildUrl($uri),
      'alt' => (string) ($values['alt'] ?? ''),
      'width' => (int) $dimensions['width'],
      'height' => (int) $dimensions['height'],
    ];
  }

  /**
   * Builds the site-wide share image.
   *
   * @return array{url: string, alt: string, width: int, height: int}
   *   The image.
   */
  protected function fallbackImage(): array {
    $path = $this->moduleExtensionList->getPath('do_base');

    // Absolute, because the structured data tags render their value verbatim:
    // only the Open Graph and Twitter tags declare 'absolute_url' and get a
    // host prepended for them, and schema.org will not accept a relative URL.
    // The dimensions are stated rather than measured, so they must be kept in
    // step with the file itself if it is ever replaced.
    return [
      'url' => Url::fromUri('base:' . $path . '/assets/social-share.jpg', ['absolute' => TRUE])->toString(),
      'alt' => (string) $this->configFactory->get('system.site')->get('name'),
      'width' => 1200,
      'height' => 630,
    ];
  }

}
