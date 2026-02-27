<?php

declare(strict_types=1);

namespace Drupal\do_feed\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\do_feed\FeedUrlBuilderInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Manages path aliases for feed-enabled automated list paragraphs on save.
 */
final class EntityPresaveHook {

  use StringTranslationTrait;

  public function __construct(
    protected FeedUrlBuilderInterface $feedUrlBuilder,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MessengerInterface $messenger,
  ) {}

  /**
   * Implements hook_entity_presave() for paragraph entities.
   */
  #[Hook('entity_presave')]
  public function presave(EntityInterface $entity): void {
    if (!$entity instanceof ParagraphInterface || $entity->bundle() !== 'civictheme_automated_list') {
      return;
    }

    if (!$entity->hasField('field_c_p_list_feed_slug')) {
      // @codeCoverageIgnoreStart
      return;
      // @codeCoverageIgnoreEnd
    }

    $new_slug = $entity->get('field_c_p_list_feed_slug')->value;
    $old_slug = NULL;

    if (!$entity->isNew()) {
      $original = $entity->original ?? NULL;
      if ($original instanceof ParagraphInterface && $original->hasField('field_c_p_list_feed_slug')) {
        $old_slug = $original->get('field_c_p_list_feed_slug')->value;
      }
    }

    // Slug removed — delete alias.
    if (empty($new_slug) && !empty($old_slug)) {
      $this->deleteAliasForSlug($old_slug);
      return;
    }

    // No slug — nothing to do.
    if (empty($new_slug)) {
      // @codeCoverageIgnoreStart
      return;
      // @codeCoverageIgnoreEnd
    }

    // Check for duplicate slug on another paragraph.
    if ($this->isDuplicateSlug($new_slug, $entity)) {
      $this->messenger->addWarning($this->t('Feed slug "%slug" is already used by another automated list. The feed alias was not created.', ['%slug' => $new_slug]));
      return;
    }

    $new_internal = '/' . $this->feedUrlBuilder->buildInternalPath($entity);
    $new_alias = $this->feedUrlBuilder->buildAliasPath($entity);

    // Check if anything changed.
    if (!$entity->isNew() && $new_slug === $old_slug) {
      $old_internal = NULL;
      $original = $entity->original ?? NULL;
      if ($original instanceof ParagraphInterface) {
        $old_internal = '/' . $this->feedUrlBuilder->buildInternalPath($original);
      }
      // Only skip if the alias already exists — it may be missing if the
      // module was installed after the paragraph was first saved.
      if ($old_internal === $new_internal) {
        $alias_storage = $this->entityTypeManager->getStorage('path_alias');
        $existing = $alias_storage->loadByProperties(['alias' => $new_alias]);
        if (!empty($existing)) {
          return;
        }
      }
    }

    // Slug changed — remove old alias.
    if (!empty($old_slug) && $old_slug !== $new_slug) {
      $this->deleteAliasForSlug($old_slug);
    }

    $alias_storage = $this->entityTypeManager->getStorage('path_alias');

    // Look for existing alias with this alias path.
    $existing = $alias_storage->loadByProperties(['alias' => $new_alias]);

    if (!empty($existing)) {
      $alias = reset($existing);
      $alias->set('path', $new_internal);
      $alias->save();
    }
    else {
      $alias = $alias_storage->create([
        'path' => $new_internal,
        'alias' => $new_alias,
      ]);
      $alias->save();
    }
  }

  /**
   * Checks if the slug is already used by another paragraph.
   */
  protected function isDuplicateSlug(string $slug, ParagraphInterface $entity): bool {
    $query = $this->entityTypeManager->getStorage('paragraph')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'civictheme_automated_list')
      ->condition('field_c_p_list_feed_slug', $slug);

    if (!$entity->isNew()) {
      $query->condition('id', $entity->id(), '<>');
    }

    return !empty($query->execute());
  }

  /**
   * Deletes path alias for a given slug.
   */
  protected function deleteAliasForSlug(string $slug): void {
    $alias_path = sprintf('/%s/%s', $this->feedUrlBuilder->getPrefix(), $slug);
    $alias_storage = $this->entityTypeManager->getStorage('path_alias');
    $existing = $alias_storage->loadByProperties(['alias' => $alias_path]);

    foreach ($existing as $alias) {
      $alias->delete();
    }
  }

}
