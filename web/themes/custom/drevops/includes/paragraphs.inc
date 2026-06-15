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

/**
 * Pre-process for the Layout paragraph field.
 */
function _drevops_preprocess_paragraph__paragraph_field__list_layout(array &$variables): void {
  $variables['layout'] = civictheme_get_field_value($variables['paragraph'], 'field_p_list_layout');
}

/**
 * Pre-process for the Appearance paragraph field.
 *
 * Maps the chosen appearance to a single modifier class so the theme can style
 * the list's children with CSS instead of storing bespoke markup as content.
 */
function _drevops_preprocess_paragraph__paragraph_field__appearance(array &$variables): void {
  $appearance = civictheme_get_field_value($variables['paragraph'], 'field_p_appearance');

  if (!$appearance) {
    return;
  }

  civictheme_add_modifier_class($variables, 'ct-list--style-' . $appearance);
}
