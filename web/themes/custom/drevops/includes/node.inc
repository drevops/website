<?php

/**
 * @file
 * Node related functions.
 */

declare(strict_types=1);

use Drupal\node\NodeInterface;

/**
 * Prepends the table of contents to a node's full-view content.
 */
function _drevops_node_add_table_of_contents(NodeInterface $node, array &$variables): void {
  if (!civictheme_get_field_value($node, 'field_c_n_show_toc')) {
    return;
  }

  array_unshift($variables['content'], [
    '#theme' => 'civictheme_table_of_contents',
    '#position' => 'prepend',
    '#anchor_selector' => 'h2',
    '#title' => t('On this page'),
    '#scope_selector' => '.ct-layout__main',
    // The renderer orders children by weight, so position in the array alone
    // does not survive alongside the display's own weighted fields.
    '#weight' => -100,
  ]);
}

/**
 * Appends the topic tag list to a node's full-view content.
 */
function _drevops_node_add_topic_tags(NodeInterface $node, array &$variables): void {
  if (civictheme_get_field_value($node, 'field_c_n_hide_tags')) {
    return;
  }

  if (!$node->hasField('field_c_n_topics')) {
    return;
  }

  $tags = [];

  foreach ($node->get('field_c_n_topics')->referencedEntities() as $term) {
    if (!$term->access('view')) {
      continue;
    }

    // A tag with a URL renders as a link, which is what gives the post a
    // second inbound route that does not shift as the listing paginates.
    $tags[] = [
      'content' => (string) $term->label(),
      'url' => $term->toUrl()->toString(),
    ];
  }

  if ($tags === []) {
    return;
  }

  $variables['content'][] = [
    '#theme' => 'civictheme_tag_list',
    '#tags' => $tags,
    '#vertical_spacing' => 'both',
    '#weight' => 100,
  ];
}

/**
 * Implements template_preprocess_node().
 */
function _drevops_preprocess_node__civictheme_promo_card(array &$variables): void {
  // @see https://www.drupal.org/project/civictheme/issues/3415756
  _drevops_preprocess_paragraph__node_field__date($variables);
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
