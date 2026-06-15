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
  _drevops_preprocess_paragraph__paragraph_field__appearance($variables);

  // The small coral category label shown above the section title.
  $variables['eyebrow'] = civictheme_get_field_value($variables['paragraph'], 'field_p_eyebrow');
}
