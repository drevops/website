<?php

/**
 * @file
 * DrevOps Contact area paragraph component.
 */

declare(strict_types=1);

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__contact_area(array &$variables): void {
  $paragraph = $variables['paragraph'];

  _civictheme_preprocess_paragraph__paragraph_field__theme($variables);

  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('paragraph');

  // Render each column's child paragraphs into its slot so the two-column SDC
  // simply prints what the editor placed in the main and aside fields.
  foreach (['main' => 'field_p_main', 'aside' => 'field_p_aside'] as $slot => $field_name) {
    $rendered = [];

    foreach (civictheme_get_field_referenced_entities($paragraph, $field_name, $variables) as $child) {
      $rendered[] = $view_builder->view($child);
    }

    $variables[$slot] = $rendered;
  }
}
