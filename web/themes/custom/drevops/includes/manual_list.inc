<?php

/**
 * @file
 * Manual list paragraph component.
 */

declare(strict_types=1);

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__civictheme_manual_list(array &$variables): void {
  _drevops_preprocess_paragraph__paragraph_field__list_layout($variables);
  _drevops_preprocess_paragraph__paragraph_field__do_style($variables);

  // The design precedes a section title with a small coral category label. It
  // is stored in field_do_eyebrow and rendered above the title by the manual
  // list template.
  $variables['eyebrow'] = civictheme_get_field_value($variables['paragraph'], 'field_do_eyebrow');
}
