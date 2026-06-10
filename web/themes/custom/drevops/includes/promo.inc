<?php

/**
 * @file
 * DrevOps Promo paragraph component (services-page service-detail card).
 */

declare(strict_types=1);

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Implements template_preprocess_paragraph().
 *
 * The services page renders each service as a promo whose rich-text body holds
 * the tagline, description, "what's included" list and price as a flat flow.
 * The design lays these out as a structured card (a header with the number,
 * title and tagline; a two-column body of description and inclusions; and a
 * footer with the price and the button). Split the body into those parts here
 * so the promo template can render the structured layout.
 */
function drevops_preprocess_paragraph__civictheme_promo(array &$variables): void {
  $paragraph = $variables['paragraph'];

  if (!$paragraph->hasField('field_c_p_content') || $paragraph->get('field_c_p_content')->isEmpty()) {
    return;
  }

  $dom = Html::load((string) $paragraph->get('field_c_p_content')->value);
  $xpath = new \DOMXPath($dom);

  $tagline = $xpath->query('//p[contains(@class, "ct-text-large")]')->item(0);
  $variables['sd_tagline'] = $tagline ? trim($tagline->textContent) : '';

  $description = '';
  foreach ($xpath->query('//p[not(@class)]') as $paragraph_node) {
    $description .= '<p>' . htmlspecialchars(trim($paragraph_node->textContent)) . '</p>';
  }
  $variables['sd_description'] = Markup::create($description);

  $includes = [];
  foreach ($xpath->query('//ul/li') as $item) {
    $includes[] = trim($item->textContent);
  }
  $variables['sd_includes'] = $includes;

  $price = $xpath->query('//p[contains(@class, "ct-text-small")]')->item(0);

  if ($price instanceof \DOMElement) {
    $label = $xpath->query('.//strong', $price)->item(0);
    $variables['sd_price_label'] = $label ? trim($label->textContent) : '';
    $variables['sd_price_value'] = trim(str_replace($variables['sd_price_label'], '', $price->textContent));
  }

  $variables['sd_title'] = $paragraph->hasField('field_c_p_title') ? $paragraph->get('field_c_p_title')->value : '';

  if ($paragraph->hasField('field_c_p_link') && !$paragraph->get('field_c_p_link')->isEmpty()) {
    $link = $paragraph->get('field_c_p_link')->first();
    $variables['sd_link_url'] = Url::fromUri($link->uri)->toString();
    $variables['sd_link_title'] = $link->title;
  }
}
