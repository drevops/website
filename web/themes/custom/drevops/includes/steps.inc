<?php

/**
 * @file
 * Steps paragraph preprocessing.
 */

declare(strict_types=1);

use Drupal\paragraphs\ParagraphInterface;

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__steps(array &$variables): void {
  _civictheme_preprocess_paragraph__paragraph_field__theme($variables);
  _civictheme_preprocess_paragraph__paragraph_field__vertical_spacing($variables);
  _civictheme_preprocess_paragraph__paragraph_field__background($variables);
  _civictheme_preprocess_paragraph__paragraph_field__title($variables);
  _civictheme_preprocess_paragraph__paragraph_field__content($variables);
  _drevops_preprocess_paragraph__paragraph_field__steps($variables);
}

/**
 * Pre-process for Steps items paragraph field.
 */
function _drevops_preprocess_paragraph__paragraph_field__steps(array &$variables): void {
  $variables['items'] = [];

  $steps = civictheme_get_field_value($variables['paragraph'], 'field_c_p_list_items', build: $variables) ?? [];

  foreach ($steps as $step) {
    if (!$step instanceof ParagraphInterface) {
      continue;
    }

    $variables['items'][] = [
      'title' => civictheme_get_field_value($step, 'field_c_p_title'),
      'body' => civictheme_get_field_value($step, 'field_c_p_summary'),
      'receive' => civictheme_get_field_value($step, 'field_p_receive'),
    ];
  }
}
