<?php

/**
 * @file
 * DrevOps Hero paragraph component.
 */

declare(strict_types=1);

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__hero(array &$variables): void {
  _civictheme_preprocess_paragraph__paragraph_field__title($variables);
  _civictheme_preprocess_paragraph__paragraph_field__theme($variables);
  _civictheme_preprocess_paragraph__paragraph_field__image($variables);
  _civictheme_preprocess_paragraph__paragraph_field__links($variables);

  _drevops_preprocess_paragraph__hero_field__type($variables);
  _drevops_preprocess_paragraph__hero_field__eyebrow($variables);
  _drevops_preprocess_paragraph__hero_field__lead($variables);
  _drevops_preprocess_paragraph__hero_field__actions($variables);

  // CivicTheme's image helper exposes the media as 'image'; the hero renders it
  // as the article variant's full-bleed background.
  if (!empty($variables['image'])) {
    $variables['background_image'] = $variables['image'];
  }
}

/**
 * Pre-process for Hero type field.
 */
function _drevops_preprocess_paragraph__hero_field__type(array &$variables): void {
  $variables['type'] = civictheme_get_field_value($variables['paragraph'], 'field_c_p_type') ?? 'home';
}

/**
 * Pre-process for Hero eyebrow field.
 */
function _drevops_preprocess_paragraph__hero_field__eyebrow(array &$variables): void {
  $variables['eyebrow'] = civictheme_get_field_value($variables['paragraph'], 'field_c_p_subtitle');
}

/**
 * Pre-process for Hero lead field.
 */
function _drevops_preprocess_paragraph__hero_field__lead(array &$variables): void {
  $variables['lead'] = civictheme_get_field_value($variables['paragraph'], 'field_c_p_summary');
}

/**
 * Pre-process for Hero action buttons.
 */
function _drevops_preprocess_paragraph__hero_field__actions(array &$variables): void {
  if (empty($variables['links'])) {
    return;
  }

  // The first action leads as a secondary button and the second as tertiary,
  // matching the prototype's call-to-action hierarchy; at most two render.
  $styles = ['secondary', 'tertiary'];
  $actions = [];

  foreach (array_slice($variables['links'], 0, 2) as $index => $link) {
    $actions[] = $link + ['type' => $styles[$index]];
  }

  $variables['actions'] = $actions;
}
