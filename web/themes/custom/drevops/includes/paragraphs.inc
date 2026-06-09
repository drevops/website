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
 * Pre-process for the bespoke design Style paragraph field.
 *
 * The design has section treatments CivicTheme has no component for (numbered
 * rows, statistics, a trust grid, dotted rows). Rather than embed markup, the
 * deploy hooks set `field_do_style` on a manual list and the theme renders the
 * treatment from a single modifier class on the list, styling the snippet
 * children with CSS. No markup is stored as content.
 */
function _drevops_preprocess_paragraph__paragraph_field__do_style(array &$variables): void {
  $style = civictheme_get_field_value($variables['paragraph'], 'field_do_style');

  if (!$style) {
    return;
  }

  civictheme_add_modifier_class($variables, 'ct-list--style-' . $style);
}
