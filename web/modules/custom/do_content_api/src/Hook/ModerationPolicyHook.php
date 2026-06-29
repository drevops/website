<?php

declare(strict_types=1);

namespace Drupal\do_content_api\Hook;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderBefore;
use Drupal\Core\Session\AccountInterface;

/**
 * Enforces the authoring API moderation policy: draft pages, published media.
 */
final class ModerationPolicyHook {

  public function __construct(
    protected AccountInterface $currentUser,
    protected ModerationInformationInterface $moderationInformation,
  ) {}

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave', order: new OrderBefore(['content_moderation']))]
  public function entityPresave(EntityInterface $entity): void {
    if (!$this->currentUser->hasPermission('use content authoring api')) {
      return;
    }

    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return;
    }

    // Pages authored through the API never go live directly; a human reviews
    // and publishes them, so any published state is forced back to a draft.
    if ($entity->getEntityTypeId() === 'node') {
      $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
      $state = $workflow->getTypePlugin()->getState($entity->get('moderation_state')->value);

      if ($state->isPublishedState()) {
        $entity->set('moderation_state', 'draft');
      }

      return;
    }

    // Images are assets and are published so an approved page renders at once.
    if ($entity->getEntityTypeId() === 'media') {
      $entity->set('moderation_state', 'published');
    }
  }

}
