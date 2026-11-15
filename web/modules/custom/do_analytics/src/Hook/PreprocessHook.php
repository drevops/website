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
use Drupal\webform\WebformInterface;

/**
 * Renders the Axel attributes that report clicks inside components.
 */
final class PreprocessHook {

  /**
   * Field holding the title of a CivicTheme paragraph.
   */
  private const string TITLE_FIELD = 'field_c_p_title';

  /**
   * Field holding the content a CivicTheme reference paragraph renders.
   */
  private const string REFERENCE_FIELD = 'field_c_p_reference';

  /**
   * View mode of a node rendered as its own page.
   */
  private const string FULL_VIEW_MODE = 'full';

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

    AnalyticsAttributes::merge($variables, AnalyticsAttributes::component($paragraph->bundle(), $this->entityId($paragraph), $this->paragraphTitle($paragraph)));
  }

  /**
   * Finds the title a paragraph is displayed under.
   *
   * A reference paragraph has no title of its own and displays the title of
   * the content it references.
   *
   * @return string
   *   The title, or an empty string when the paragraph has none.
   */
  protected function paragraphTitle(ParagraphInterface $paragraph): string {
    $title = $paragraph->hasField(self::TITLE_FIELD) ? (string) $paragraph->get(self::TITLE_FIELD)->value : '';

    if ($title !== '' || !$paragraph->hasField(self::REFERENCE_FIELD)) {
      return $title;
    }

    $referenced = $paragraph->get(self::REFERENCE_FIELD)->entity;

    return $referenced instanceof EntityInterface ? (string) $referenced->label() : '';
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

    $block = $this->entityTypeManager->getStorage('block')->load($id);
    $region = $block instanceof BlockInterface ? $block->getRegion() : NULL;

    AnalyticsAttributes::merge($variables, AnalyticsAttributes::block($id, $region));
  }

  /**
   * Implements hook_preprocess_HOOK() for 'webform_confirmation'.
   */
  #[Hook('preprocess_webform_confirmation')]
  public function webformConfirmation(array &$variables): void {
    $webform = $variables['webform'] ?? NULL;

    if (!$webform instanceof WebformInterface) {
      return;
    }

    AnalyticsAttributes::merge($variables, [AnalyticsAttributes::FORM_SUBMITTED => FormAlterHook::webformName((string) $webform->id())]);
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
