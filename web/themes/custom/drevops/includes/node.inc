<?php

/**
 * @file
 * Node related functions.
 */

declare(strict_types=1);

/**
 * Implements template_preprocess_node().
 */
function _drevops_preprocess_node__civictheme_promo_card(array &$variables): void {
  // @see https://www.drupal.org/project/civictheme/issues/3415756
  _drevops_preprocess_paragraph__node_field__date($variables);
  _drevops_preprocess_node__card_summary($variables);
}

/**
 * Caps the card summary to the configured promo-card length.
 *
 * Node-sourced promo cards can carry the full node summary, which makes the
 * cards grow well past their intended size. Re-trim it from the source field to
 * the promo card's configured summary length so cards stay compact and uniform.
 */
function _drevops_preprocess_node__card_summary(array &$variables): void {
  $node = $variables['node'] ?? NULL;

  if (!$node instanceof \Drupal\node\NodeInterface || !$node->hasField('field_c_n_summary') || $node->get('field_c_n_summary')->isEmpty()) {
    return;
  }

  $summary = trim(strip_tags((string) $node->get('field_c_n_summary')->value));

  if ($summary === '') {
    return;
  }

  // The promo-card summary length configured for this site; a compact teaser
  // that keeps the listing cards close to the design's height.
  $length = 160;

  if (mb_strlen($summary) <= $length) {
    return;
  }

  $variables['summary'] = \Drupal\Core\Render\Markup::create(\Drupal\Component\Utility\Unicode::truncate($summary, $length, TRUE, TRUE));
}

/**
 * Pre-process for Date node field.
 */
function _drevops_preprocess_paragraph__node_field__date(array &$variables): void {
  $node = $variables['node'] ?? civictheme_get_field_value($variables['paragraph'], 'field_c_p_reference', TRUE);
  if ($node) {
    $variables['date'] = _civictheme_node_get_updated_date($node, 'civictheme_short_date');
    $variables['date_iso'] = _civictheme_node_get_updated_date($node, 'c');
    $variables['reading_time'] = _drevops_node_reading_time($node);
  }
}

/**
 * Estimates a node's reading time from its component text.
 *
 * Walks the node's section components and sums the words across their text
 * fields, then converts the count to whole minutes at an average adult reading
 * pace, so cards can show the design's "date / N min read" meta line.
 */
function _drevops_node_reading_time(\Drupal\Core\Entity\FieldableEntityInterface $node): ?string {
  if (!$node->hasField('field_c_n_components')) {
    return NULL;
  }

  $words = 0;
  $text_fields = ['field_c_p_content', 'field_c_p_summary', 'field_c_p_title'];

  foreach ($node->get('field_c_n_components')->referencedEntities() as $component) {
    foreach ($text_fields as $field_name) {
      if ($component->hasField($field_name) && !$component->get($field_name)->isEmpty()) {
        $words += str_word_count(strip_tags($component->get($field_name)->getString()));
      }
    }
  }

  if ($words < 1) {
    return NULL;
  }

  // 200 words per minute is the conventional average adult reading pace.
  $minutes = max(1, (int) ceil($words / 200));

  return $minutes . ' min read';
}
