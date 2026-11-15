<?php

declare(strict_types=1);

namespace Drupal\do_analytics;

use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;

/**
 * Builds the data attributes the analytics tracker reads its context from.
 */
final class AnalyticsAttributes {

  /**
   * Type of the component an element belongs to, such as 'promo_card'.
   */
  public const string COMPONENT = 'data-analytics-component';

  /**
   * Entity behind the component, as '<entity type>:<id>'.
   */
  public const string COMPONENT_ID = 'data-analytics-component-id';

  /**
   * Human-readable title of the component.
   */
  public const string COMPONENT_TITLE = 'data-analytics-component-title';

  /**
   * Machine name of the theme region a block is placed in.
   */
  public const string REGION = 'data-analytics-region';

  /**
   * Machine name of a placed block.
   */
  public const string BLOCK = 'data-analytics-block';

  /**
   * Identifier of a form that stays the same wherever the form is placed.
   */
  public const string FORM = 'data-analytics-form';

  /**
   * Longest value Google Analytics 4 accepts for an event parameter.
   */
  public const int MAX_LENGTH = 100;

  /**
   * Prefix CivicTheme puts on its bundles and view modes.
   */
  private const string PREFIX = 'civictheme_';

  /**
   * Builds the attributes that mark an element as a component.
   *
   * @param string $name
   *   Machine name of the bundle or view mode the component renders.
   * @param string|null $id
   *   Identifier of the entity behind the component, or NULL when unsaved.
   * @param string $title
   *   Title of the component, possibly holding markup.
   *
   * @return array<string, string>
   *   The attributes, without the ones that have no value.
   */
  public static function component(string $name, ?string $id = NULL, string $title = ''): array {
    return array_filter([
      self::COMPONENT => self::name($name),
      self::COMPONENT_ID => $id ?? '',
      self::COMPONENT_TITLE => self::text($title),
    ], static fn(string $value): bool => $value !== '');
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
   */
  public static function text(string $value): string {
    $value = Html::decodeEntities(strip_tags($value));
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

}
