<?php

/**
 * @file
 * DrevOps Contact detail paragraph component.
 */

declare(strict_types=1);

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__contact_detail(array &$variables): void {
  _civictheme_preprocess_paragraph__paragraph_field__theme($variables);

  _drevops_preprocess_paragraph__contact_detail_field__label($variables);
  _drevops_preprocess_paragraph__contact_detail_field__value($variables);
  _drevops_preprocess_paragraph__contact_detail_field__note($variables);
}

/**
 * Pre-process for the Contact detail label field.
 */
function _drevops_preprocess_paragraph__contact_detail_field__label(array &$variables): void {
  $variables['label'] = civictheme_get_field_value($variables['paragraph'], 'field_c_p_subtitle');
}

/**
 * Pre-process for the Contact detail value field.
 */
function _drevops_preprocess_paragraph__contact_detail_field__value(array &$variables): void {
  // Read the raw stored text rather than the rendered field: an email becomes a
  // mailto link and a phone number a tel link so the value is actionable, while
  // anything else stays plain text.
  $raw = $variables['paragraph']->get('field_c_p_content')->value ?? '';
  $text = trim(html_entity_decode(strip_tags((string) $raw), ENT_QUOTES));

  $value = ['text' => $text, 'url' => '', 'is_email' => FALSE];

  if ($text !== '') {
    if (filter_var($text, FILTER_VALIDATE_EMAIL)) {
      $value['url'] = 'mailto:' . $text;
      $value['is_email'] = TRUE;
    }
    elseif (preg_match('/^\+?[0-9 ()-]{6,}$/', $text)) {
      $digits = preg_replace('/[^0-9]/', '', $text);

      if ($digits !== '') {
        $value['url'] = 'tel:' . (str_starts_with($text, '+') ? '+' : '') . $digits;
      }
    }
  }

  $variables['value'] = $value;
}

/**
 * Pre-process for the Contact detail note field.
 */
function _drevops_preprocess_paragraph__contact_detail_field__note(array &$variables): void {
  $variables['note'] = civictheme_get_field_value($variables['paragraph'], 'field_c_p_summary');
}
