<?php

/**
 * @file
 * DrevOps Hero paragraph component.
 */

declare(strict_types=1);

use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

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
  _drevops_preprocess_paragraph__hero__article($variables);

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
    $actions[] = array_merge($link, ['type' => $styles[$index]]);
  }

  $variables['actions'] = $actions;
}

/**
 * Source the article hero's heading, meta and tags from the post it opens.
 */
function _drevops_preprocess_paragraph__hero__article(array &$variables): void {
  if (($variables['type'] ?? NULL) !== 'article') {
    return;
  }

  $paragraph = $variables['paragraph'] ?? NULL;
  $node = $paragraph instanceof ParagraphInterface ? $paragraph->getParentEntity() : NULL;

  // The post's date, read time, title and topics live on the node, not on the
  // hero paragraph, so a post is described in one place. Skip when there is no
  // node parent, such as a paragraphs library preview.
  if (!$node instanceof NodeInterface) {
    return;
  }

  if (empty($variables['title'])) {
    $variables['title'] = $node->label();
  }

  $variables['article_date'] = _civictheme_node_get_updated_date($node, 'civictheme_short_date');
  $variables['article_date_iso'] = _civictheme_node_get_updated_date($node, 'c');

  if ($node->hasField('field_read_time')) {
    $variables['read_time'] = civictheme_get_field_value($node, 'field_read_time', TRUE);
  }

  $variables['article_tags'] = _drevops_hero_article_tags($node);
}

/**
 * Build the article hero tag list from a post's topics, minus the Blog marker.
 */
function _drevops_hero_article_tags(NodeInterface $node): array {
  if (!$node->hasField('field_c_n_topics')) {
    return [];
  }

  $tags = [];

  // The tag list renders each item as the tag's text, so the topics are passed
  // as plain label strings. The Blog topic only marks a page as a post for the
  // listing, so it is not a content tag and is left out of the hero.
  foreach ($node->get('field_c_n_topics')->referencedEntities() as $term) {
    if ($term->label() === 'Blog') {
      continue;
    }

    $tags[] = $term->label();
  }

  return $tags;
}
