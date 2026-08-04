<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Generator;

/**
 * Formats a date offset from the moment of generation.
 *
 * Generated dates are relative rather than fixed so past, current and future
 * content stays past, current and future however long ago the site was built.
 */
final class RelativeDate {

  /**
   * Format a date offset from now, in UTC.
   *
   * @param string $modifier
   *   A relative date string, for example '-3 days'.
   * @param string $format
   *   A date format string.
   *
   * @return string
   *   The formatted date.
   */
  public static function format(string $modifier, string $format = 'Y-m-d'): string {
    return new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->modify($modifier)->format($format);
  }

}
