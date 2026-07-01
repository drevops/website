<?php

declare(strict_types=1);

namespace Drupal\do_content_api\Hook;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderBefore;
use Drupal\Core\Session\AccountInterface;

/**
 * Enforces the authoring API moderation policy: draft pages, published media.
 */
final class ModerationPolicyHook {

  public function __construct(
    protected AccountInterface $account,
    protected ModerationInformationInterface $moderationInformation,
  ) {}

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave', order: new OrderBefore(['content_moderation']))]
  public function entityPresave(EntityInterface $entity): void {
    // User 1 bypasses every permission check, so the authoring-permission gate
    // below would also match the superuser and force its content into the API
    // moderation policy. The policy targets the dedicated API service account
    // only, so the superuser is excluded explicitly.
    if ((int) $this->account->id() === 1) {
      return;
    }

    if (!$this->account->hasPermission('use content authoring api')) {
      return;
    }

    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return;
    }

    // The policy governs authoring, which is entity creation. A later
    // moderation change - a reviewer publishing the draft - is an update and
    // must be left untouched, otherwise authored content could never be
    // published.
    if (!$entity->isNew()) {
      return;
    }

    // Pages authored through the API never go live directly; a human reviews
    // and publishes them, so they are forced to draft on creation.
    if ($entity->getEntityTypeId() === 'node') {
      $entity->set('moderation_state', 'draft');

      return;
    }

    // Images are assets and are published so an approved page renders at once.
    if ($entity->getEntityTypeId() === 'media') {
      $entity->set('moderation_state', 'published');
    }
  }

}
