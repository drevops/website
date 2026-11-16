<?php

declare(strict_types=1);

namespace Drupal\do_analytics;

use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;

/**
 * Builds the Axel data attributes rendered into the page.
 *
 * @see https://github.com/drevops/axel#attributes
 */
final class AnalyticsAttributes {

  /**
   * Names the click event for every link and button inside the element.
   */
  public const string EVENT = 'data-axel-event';

  /**
   * Marks a form for the 'form_start' and 'field_complete' events.
   */
  public const string FORM = 'data-axel-form';

  /**
   * Holds the Drupal form ID, which Axel sends as 'form_id'.
   *
   * Drupal adds a random suffix to the 'id' and 'data-drupal-selector' of a
   * form on every AJAX rebuild, and the form ID stays the same.
   */
  public const string FORM_ID = 'data-axel-form-id';

  /**
   * Sends 'form_submit' for the named form when the element is on the page.
   */
  public const string FORM_SUBMITTED = 'data-axel-form-submitted';

  /**
   * Name of the event sent for clicks inside a component.
   */
  public const string CLICK_EVENT = 'component_click';

  /**
   * Longest value Google Analytics 4 accepts for an event parameter.
   */
  public const int MAX_LENGTH = 100;

  /**
   * Prefix of an attribute that adds a parameter to every event.
   */
  private const string PARAM_PREFIX = 'data-axel-param-';

  /**
   * Prefix CivicTheme puts on its bundles and view modes.
   */
  private const string PREFIX = 'civictheme_';

  /**
   * Pattern of an email address, which GA4 must not receive.
   */
  private const string EMAIL = '/[^\s@<>()]+@[^\s@<>()]+\.[a-z]{2,}/iu';

  /**
   * Builds the attributes that report clicks inside a component.
   *
   * @param string $name
   *   Machine name of the bundle or view mode the component renders.
   * @param string|null $id
   *   Identifier of the entity behind the component, or NULL when unsaved.
   * @param string $title
   *   Title of the component, possibly holding markup.
   *
   * @return array<string, string>
   *   The attributes, without the parameters that have no value.
   */
  public static function component(string $name, ?string $id = NULL, string $title = ''): array {
    return self::event([
      'component' => self::name($name),
      'component_id' => $id ?? '',
      'component_title' => self::text($title),
    ]);
  }

  /**
   * Builds the attributes that report clicks inside a placed block.
   *
   * @param string $id
   *   Machine name of the block.
   * @param string|null $region
   *   Theme region the block is placed in, or NULL when unknown.
   *
   * @return array<string, string>
   *   The attributes, without the parameters that have no value.
   */
  public static function block(string $id, ?string $region = NULL): array {
    return self::event([
      'block' => $id,
      'region' => $region ?? '',
    ]);
  }

  /**
   * Converts a parameter name into its Axel attribute name.
   */
  public static function param(string $name): string {
    return self::PARAM_PREFIX . str_replace('_', '-', $name);
  }

  /**
   * Converts a bundle or view mode machine name into a component name.
   */
  public static function name(string $machine_name): string {
    if (str_starts_with($machine_name, self::PREFIX)) {
      $machine_name = substr($machine_name, strlen(self::PREFIX));
    }

    return $machine_name;
  }

  /**
   * Reduces a value to plain text that fits an event parameter.
   *
   * Axel sends rendered parameters as they are, so email addresses become
   * '[email]' here.
   */
  public static function text(string $value): string {
    $value = Html::decodeEntities(strip_tags($value));
    $value = (string) preg_replace(self::EMAIL, '[email]', $value);
    $value = trim((string) preg_replace('/\s+/u', ' ', $value));

    return mb_substr($value, 0, self::MAX_LENGTH);
  }

  /**
   * Adds attributes to the 'attributes' of a set of template variables.
   *
   * The variable keeps its type, so a later preprocess function that merges
   * into the array with '+' still receives an array.
   *
   * @param array $variables
   *   The template variables.
   * @param array<string, string> $attributes
   *   The attributes to add.
   */
  public static function merge(array &$variables, array $attributes): void {
    if ($attributes === []) {
      return;
    }

    $existing = $variables['attributes'] ?? [];

    if ($existing instanceof Attribute) {
      foreach ($attributes as $name => $value) {
        $existing->setAttribute($name, $value);
      }

      return;
    }

    $variables['attributes'] = $attributes + (is_array($existing) ? $existing : []);
  }

  /**
   * Builds the click event attribute with its parameters.
   *
   * @param array<string, string> $params
   *   The parameters, keyed by name.
   *
   * @return array<string, string>
   *   The attributes, without the parameters that have no value.
   */
  protected static function event(array $params): array {
    $attributes = [self::EVENT => self::CLICK_EVENT];

    foreach (array_filter($params, static fn(string $value): bool => $value !== '') as $name => $value) {
      $attributes[self::param($name)] = $value;
    }

    return $attributes;
  }

}
