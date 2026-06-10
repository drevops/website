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

  $first_element = static function (string $query, ?\DOMNode $context = NULL) use ($xpath): ?\DOMElement {
    $nodes = $xpath->query($query, $context);
    $node = $nodes instanceof \DOMNodeList ? $nodes->item(0) : NULL;

    return $node instanceof \DOMElement ? $node : NULL;
  };

  $tagline = $first_element('//p[contains(@class, "ct-text-large")]');
  $variables['sd_tagline'] = $tagline instanceof \DOMElement ? trim($tagline->textContent) : '';

  $description = '';
  $description_nodes = $xpath->query('//p[not(@class)]');

  foreach ($description_nodes instanceof \DOMNodeList ? $description_nodes : [] as $node) {
    if ($node instanceof \DOMElement) {
      $description .= '<p>' . htmlspecialchars(trim($node->textContent)) . '</p>';
    }
  }

  $variables['sd_description'] = Markup::create($description);

  $includes = [];
  $include_nodes = $xpath->query('//ul/li');

  foreach ($include_nodes instanceof \DOMNodeList ? $include_nodes : [] as $node) {
    if ($node instanceof \DOMElement) {
      $includes[] = trim($node->textContent);
    }
  }

  $variables['sd_includes'] = $includes;

  $price = $first_element('//p[contains(@class, "ct-text-small")]');

  if ($price instanceof \DOMElement) {
    $label = $first_element('.//strong', $price);
    $variables['sd_price_label'] = $label instanceof \DOMElement ? trim($label->textContent) : '';
    $variables['sd_price_value'] = trim(str_replace($variables['sd_price_label'], '', $price->textContent));
  }

  $variables['sd_title'] = (string) $paragraph->get('field_c_p_title')->value;

  if ($paragraph->hasField('field_c_p_link') && !$paragraph->get('field_c_p_link')->isEmpty()) {
    $link = $paragraph->get('field_c_p_link')->first();
    $variables['sd_link_url'] = Url::fromUri((string) $link->uri)->toString();
    $variables['sd_link_title'] = (string) $link->title;
  }
}
