<?php

/**
 * @file
 * Card paragraphs related functions.
 */

declare(strict_types=1);

use Drupal\civictheme\CivicthemeConstants;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Render\Markup;

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__civictheme_promo_card(array &$variables): void {
  _drevops_preprocess_paragraph__paragraph_field__summary($variables);
}

/**
 * Pre-process for the Summary paragraph field.
 */
function _drevops_preprocess_paragraph__paragraph_field__summary(array &$variables): void {
  $summary = drevops_get_field_value__summary($variables['paragraph']);
  if (!empty($summary)) {
    $length = CivicthemeConstants::COMPONENT_SUMMARY_DEFAULT_LENGTH;

    $component_name = _civictheme_get_configurable_component_name_from_bundle($variables['paragraph']->bundle());
    if ($component_name) {
      $length = civictheme_get_theme_config_manager()->loadForComponent($component_name, 'summary_length', CivicthemeConstants::COMPONENT_SUMMARY_DEFAULT_LENGTH);
    }

    $summary_trimmed = text_summary($summary);

    if (!_civictheme_feature_is_optedout('process', CivicthemeConstants::OPTOUT_SUMMARY_HIDE_ELLIPSIS)) {
      $summary_trimmed = Unicode::truncate($summary_trimmed, $length, TRUE, TRUE);
    }

    $variables['summary'] = Markup::create($summary_trimmed);
  }
}

/**
 * Gets values from the Summary field.
 *
 * DrevOps theme overrides the behavior of the summary field to support HTML.
 */
function drevops_get_field_value__summary(FieldableEntityInterface $entity, string $field_name = 'field_c_p_summary'): ?string {
  if (!civictheme_field_has_value($entity, $field_name)) {
    return NULL;
  }

  return $entity->get($field_name)->getString();
}
