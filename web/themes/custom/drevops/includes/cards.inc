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
use Drupal\media\MediaInterface;

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__civictheme_promo_card(array &$variables): void {
  _drevops_preprocess_paragraph__paragraph_field__summary($variables);
}

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__card(array &$variables): void {
  $paragraph = $variables['paragraph'];

  $variables['type'] = civictheme_get_field_value($paragraph, 'field_c_p_type') ?? 'dot';
  $variables['title'] = civictheme_get_field_value($paragraph, 'field_c_p_title');
  $variables['description'] = drevops_get_field_value__summary($paragraph);

  _civictheme_preprocess_paragraph__paragraph_field__theme($variables);

  // The card group renders its cards and tags each with its 1-based position so
  // the number marker reflects the card's order rather than an authored value.
  // A card rendered outside a group still leads from one.
  $variables['index'] = $variables['elements']['#card_index'] ?? 1;

  $variables['icon'] = NULL;
  $media = civictheme_get_field_value($paragraph, 'field_c_p_icon', TRUE, build: $variables);
  if ($media instanceof MediaInterface) {
    $image = civictheme_media_image_get_variables($media);
    if (!empty($image['url'])) {
      $variables['icon'] = [
        '#type' => 'inline_template',
        '#template' => civictheme_embed_svg($image['url'], ['ct-icon']),
      ];
    }
  }
}

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__card_group(array &$variables): void {
  $paragraph = $variables['paragraph'];

  $variables['column_count'] = (int) (civictheme_get_field_value($paragraph, 'field_c_p_list_column_count') ?? 1);

  _civictheme_preprocess_paragraph__paragraph_field__theme($variables);

  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('paragraph');

  $cards = [];
  $index = 0;
  foreach (civictheme_get_field_referenced_entities($paragraph, 'field_c_p_list_items', $variables) as $card) {
    $index++;
    $build = $view_builder->view($card);
    // Pass the position to the card so its number marker and reveal delay follow
    // the order set by the editor.
    $build['#card_index'] = $index;
    $cards[] = $build;
  }

  $variables['cards'] = $cards;
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
