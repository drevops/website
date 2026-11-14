<?php

declare(strict_types=1);

namespace Drupal\do_analytics\Hook;

use Drupal\block\BlockInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\do_analytics\AnalyticsAttributes;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Marks rendered components with the context the analytics tracker reports.
 */
final class PreprocessHook {

  /**
   * Field holding the title of a CivicTheme paragraph.
   */
  protected const string TITLE_FIELD = 'field_c_p_title';

  /**
   * View mode of a node rendered as its own page.
   */
  protected const string FULL_VIEW_MODE = 'full';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_preprocess_paragraph().
   */
  #[Hook('preprocess_paragraph')]
  public function paragraph(array &$variables): void {
    $paragraph = $variables['paragraph'] ?? NULL;

    if (!$paragraph instanceof ParagraphInterface) {
      return;
    }

    $title = $paragraph->hasField(self::TITLE_FIELD) ? (string) $paragraph->get(self::TITLE_FIELD)->value : '';

    AnalyticsAttributes::merge($variables, AnalyticsAttributes::component($paragraph->bundle(), $this->entityId($paragraph), $title));
  }

  /**
   * Implements hook_preprocess_node().
   */
  #[Hook('preprocess_node')]
  public function node(array &$variables): void {
    $node = $variables['node'] ?? NULL;
    $view_mode = $variables['view_mode'] ?? self::FULL_VIEW_MODE;

    // The page context already describes a node rendered as the page.
    if (!$node instanceof NodeInterface || $view_mode === self::FULL_VIEW_MODE || !empty($variables['page'])) {
      return;
    }

    AnalyticsAttributes::merge($variables, AnalyticsAttributes::component((string) $view_mode, $this->entityId($node), (string) $node->label()));
  }

  /**
   * Implements hook_preprocess_block().
   */
  #[Hook('preprocess_block')]
  public function block(array &$variables): void {
    $id = $variables['elements']['#id'] ?? NULL;

    // Blocks placed outside the block layout, such as by Layout Builder, have
    // no config entity to name them.
    if (!is_string($id) || $id === '') {
      return;
    }

    $attributes = [AnalyticsAttributes::BLOCK => $id];

    $block = $this->entityTypeManager->getStorage('block')->load($id);

    if ($block instanceof BlockInterface) {
      $attributes[AnalyticsAttributes::REGION] = $block->getRegion();
    }

    AnalyticsAttributes::merge($variables, $attributes);
  }

  /**
   * Identifies an entity as '<entity type>:<id>'.
   *
   * @return string|null
   *   The identifier, or NULL for an entity that has not been saved.
   */
  protected function entityId(EntityInterface $entity): ?string {
    $id = $entity->id();

    return $id === NULL ? NULL : $entity->getEntityTypeId() . ':' . $id;
  }

}
