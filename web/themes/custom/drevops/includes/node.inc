<?php

/**
 * @file
 * Node related functions.
 */

declare(strict_types=1);

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Implements template_preprocess_node().
 */
function _drevops_preprocess_node__civictheme_promo_card(array &$variables): void {
  // @see https://www.drupal.org/project/civictheme/issues/3415756
  _drevops_preprocess_paragraph__node_field__date($variables);
  _drevops_preprocess_node__node_field__read_time($variables);
}

/**
 * Pre-process for the Read time node field.
 */
function _drevops_preprocess_node__node_field__read_time(array &$variables): void {
  $node = $variables['node'] ?? NULL;
  if ($node instanceof FieldableEntityInterface && $node->hasField('field_read_time')) {
    $read_time = civictheme_get_field_value($node, 'field_read_time', TRUE);
    if (!empty($read_time)) {
      $variables['read_time'] = $read_time;
    }
  }
}

/**
 * Pre-process for Date node field.
 */
function _drevops_preprocess_paragraph__node_field__date(array &$variables): void {
  $node = $variables['node'] ?? civictheme_get_field_value($variables['paragraph'], 'field_c_p_reference', TRUE);
  if ($node) {
    $variables['date'] = _civictheme_node_get_updated_date($node, 'civictheme_short_date');
    $variables['date_iso'] = _civictheme_node_get_updated_date($node, 'c');
  }
}
