<?php

/**
 * @file
 * Paragraph-related theme helpers.
 *
 * Used as a unified place to populate variables.
 *
 * For readability, similar paragraph and node functions are grouped together
 * and sorted alphabetically.
 */

declare(strict_types=1);

/**
 * Pre-process for the Size paragraph field.
 */
function _drevops_preprocess_paragraph__paragraph_field__size(array &$variables): void {
  $variables['size'] = civictheme_get_field_value($variables['paragraph'], 'field_p_size');
}

/**
 * Pre-process for the Alignment paragraph field.
 */
function _drevops_preprocess_paragraph__paragraph_field__alignment(array &$variables): void {
  $variables['alignment'] = civictheme_get_field_value($variables['paragraph'], 'field_p_alignment');
}
