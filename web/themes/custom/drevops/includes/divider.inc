<?php

/**
 * @file
 * DrevOps Divider paragraph component.
 */

declare(strict_types=1);

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__divider(array &$variables): void {
  _civictheme_preprocess_paragraph__paragraph_field__theme($variables);
  _civictheme_preprocess_paragraph__paragraph_field__vertical_spacing($variables);
  _civictheme_preprocess_paragraph__paragraph_field__image($variables);

  _drevops_preprocess_paragraph__paragraph_field__size($variables);
  _drevops_preprocess_paragraph__paragraph_field__alignment($variables);
}
