<?php

/**
 * @file
 * Service detail paragraph component.
 */

declare(strict_types=1);

use Drupal\Core\Url;

/**
 * Implements template_preprocess_paragraph().
 *
 * Maps the service-detail fields onto the drevops:service-detail component
 * props and renders the rich-text description for its slot.
 */
function drevops_preprocess_paragraph__do_service_detail(array &$variables): void {
  $paragraph = $variables['paragraph'];

  $variables['sd_title'] = (string) $paragraph->get('field_c_p_title')->value;
  $variables['sd_tagline'] = (string) $paragraph->get('field_p_tagline')->value;
  $variables['sd_price_label'] = (string) $paragraph->get('field_p_price_label')->value;
  $variables['sd_price_value'] = (string) $paragraph->get('field_p_price_value')->value;

  $includes = [];

  foreach ($paragraph->get('field_p_includes') as $item) {
    $includes[] = (string) $item->value;
  }

  $variables['sd_includes'] = $includes;

  $content = $paragraph->get('field_c_p_content');
  $variables['sd_description'] = '';

  if (!$content->isEmpty()) {
    $build = [
      '#type' => 'processed_text',
      '#text' => $content->value,
      '#format' => $content->format,
    ];
    $variables['sd_description'] = \Drupal::service('renderer')->renderInIsolation($build);
  }

  $variables['sd_link_url'] = '';
  $variables['sd_link_title'] = '';

  if (!$paragraph->get('field_c_p_link')->isEmpty()) {
    $link = $paragraph->get('field_c_p_link')->first();
    $variables['sd_link_url'] = Url::fromUri((string) $link->uri)->toString();
    $variables['sd_link_title'] = (string) $link->title;
  }
}
