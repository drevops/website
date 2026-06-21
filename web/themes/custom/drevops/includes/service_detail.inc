<?php

/**
 * @file
 * DrevOps Service detail paragraph component.
 */

declare(strict_types=1);

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__service_detail(array &$variables): void {
  _civictheme_preprocess_paragraph__paragraph_field__theme($variables);
  _civictheme_preprocess_paragraph__paragraph_field__title($variables);
  _civictheme_preprocess_paragraph__paragraph_field__link($variables);

  $paragraph = $variables['paragraph'];

  $variables['tagline'] = civictheme_get_field_value($paragraph, 'field_c_p_subtitle');
  $variables['price_label'] = civictheme_get_field_value($paragraph, 'field_p_price_label');
  $variables['price'] = civictheme_get_field_value($paragraph, 'field_p_price');

  // CivicTheme's single-link helper exposes the action as 'link'; the footer
  // renders it as the call-to-action button.
  $variables['action'] = $variables['link'] ?? NULL;

  _drevops_preprocess_paragraph__service_detail_field__description($variables);
  _drevops_preprocess_paragraph__service_detail_field__includes($variables);
  _drevops_preprocess_paragraph__service_detail_field__index($variables);
}

/**
 * Pre-process for the Service detail description field.
 */
function _drevops_preprocess_paragraph__service_detail_field__description(array &$variables): void {
  $item = $variables['paragraph']->get('field_c_p_content')->first();
  if ($item === NULL || $item->value === NULL || $item->value === '') {
    return;
  }

  // Render the rich text through its own format so the two-column body holds
  // the markup directly, without a field wrapper around it.
  $variables['description'] = [
    '#type' => 'processed_text',
    '#text' => $item->value,
    '#format' => $item->format,
  ];
}

/**
 * Pre-process for the Service detail inclusions field.
 */
function _drevops_preprocess_paragraph__service_detail_field__includes(array &$variables): void {
  $includes = [];

  foreach ($variables['paragraph']->get('field_p_includes') as $item) {
    $value = trim((string) $item->value);
    if ($value !== '') {
      $includes[] = $value;
    }
  }

  $variables['includes'] = $includes;
}

/**
 * Pre-process for the Service detail leading index.
 *
 * The index reflects the component's position among its same-type siblings, so
 * a run of service details numbers sequentially regardless of the other
 * components placed around them.
 */
function _drevops_preprocess_paragraph__service_detail_field__index(array &$variables): void {
  $paragraph = $variables['paragraph'];
  $variables['index'] = 1;

  $parent = $paragraph->getParentEntity();
  $field_name = $paragraph->get('parent_field_name')->value;
  if (!$parent instanceof FieldableEntityInterface || empty($field_name) || !$parent->hasField($field_name)) {
    return;
  }

  $position = 0;
  foreach ($parent->get($field_name) as $field_item_list) {
    $values = $field_item_list->getValue();
    $sibling = $field_item_list->get('entity')->getValue();
    if (!$sibling instanceof ParagraphInterface || $sibling->bundle() !== $paragraph->bundle()) {
      continue;
    }

    $position++;
    if ((string) ($values['target_id'] ?? '') === (string) $paragraph->id()) {
      $variables['index'] = $position;
      return;
    }
  }
}
