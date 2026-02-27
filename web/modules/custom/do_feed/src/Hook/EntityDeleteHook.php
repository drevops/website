<?php

declare(strict_types=1);

namespace Drupal\do_feed\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\do_feed\FeedUrlBuilderInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Cleans up path aliases when feed-enabled paragraphs are deleted.
 */
final readonly class EntityDeleteHook {

  public function __construct(
    protected FeedUrlBuilderInterface $feedUrlBuilder,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_entity_delete() for paragraph entities.
   */
  #[Hook('entity_delete')]
  public function delete(EntityInterface $entity): void {
    if (!$entity instanceof ParagraphInterface || $entity->bundle() !== 'civictheme_automated_list') {
      return;
    }

    if (!$entity->hasField('field_c_p_list_feed_slug')) {
      return;
    }

    $slug = $entity->get('field_c_p_list_feed_slug')->value;

    if (empty($slug)) {
      return;
    }

    $alias_path = $this->feedUrlBuilder->buildAliasPath($entity);
    $alias_storage = $this->entityTypeManager->getStorage('path_alias');
    $existing = $alias_storage->loadByProperties(['alias' => $alias_path]);

    foreach ($existing as $alias) {
      $alias->delete();
    }
  }

}
