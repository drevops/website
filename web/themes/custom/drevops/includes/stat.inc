<?php

/**
 * @file
 * DrevOps Stat grid paragraph components.
 */

declare(strict_types=1);

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__stat(array &$variables): void {
  _civictheme_preprocess_paragraph__paragraph_field__theme($variables);
  $variables['subtitle'] = civictheme_get_field_value($variables['paragraph'], 'field_subtitle', TRUE);
}

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__stat_item(array &$variables): void {
  $variables['value'] = civictheme_get_field_value($variables['paragraph'], 'field_stat_value', TRUE);
  $variables['suffix'] = civictheme_get_field_value($variables['paragraph'], 'field_stat_suffix', TRUE);
  $variables['label'] = civictheme_get_field_value($variables['paragraph'], 'field_stat_label', TRUE);
}
