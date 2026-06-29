<?php

declare(strict_types=1);

namespace Drupal\do_content_api\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;

/**
 * Grants paragraph create access to authorised content-authoring clients.
 */
final class EntityCreateAccessHook {

  /**
   * Paragraph bundles that may be created through the authoring API.
   */
  public const array ALLOWED_PARAGRAPH_BUNDLES = [
    'civictheme_content',
    'civictheme_callout',
    'civictheme_promo',
    'civictheme_campaign',
    'civictheme_next_step',
    'civictheme_accordion',
    'civictheme_accordion_panel',
    'civictheme_manual_list',
    'civictheme_promo_card',
    'civictheme_event_card',
    'civictheme_subject_card',
    'civictheme_navigation_card',
    'civictheme_publication_card',
    'civictheme_service_card',
    'civictheme_fast_fact_card',
    'civictheme_snippet',
  ];

  /**
   * Implements hook_entity_create_access().
   */
  #[Hook('entity_create_access')]
  public function entityCreateAccess(AccountInterface $account, array $context, ?string $entity_bundle): AccessResultInterface {
    if (($context['entity_type_id'] ?? NULL) !== 'paragraph') {
      return AccessResult::neutral();
    }

    // Editorial users keep the stock paragraphs access behaviour.
    if (!$account->hasPermission('use content authoring api')) {
      return AccessResult::neutral()->cachePerPermissions();
    }

    // A bundle-less capability check gets no opinion.
    if ($entity_bundle === NULL) {
      return AccessResult::neutral()->cachePerPermissions();
    }

    // The paragraphs access handler returns neutral for every non-HTML request
    // format. Restore create access for the allow-listed bundles and explicitly
    // deny the rest so no other handler can widen the authoring surface.
    return in_array($entity_bundle, self::ALLOWED_PARAGRAPH_BUNDLES, TRUE)
      ? AccessResult::allowed()->cachePerPermissions()
      : AccessResult::forbidden()->cachePerPermissions();
  }

}
