<?php

declare(strict_types=1);

namespace Drupal\do_base;

/**
 * Reads the dimensions an SVG declares in its markup.
 *
 * No image toolkit can measure a vector, so the size comes from the 'viewBox'.
 */
final class SvgDimensions {

  /**
   * Number of bytes read from the start of the file.
   */
  private const int READ_LENGTH = 2048;

  /**
   * Reads the width and height from an SVG's 'viewBox'.
   *
   * @param string $path
   *   Path or stream wrapper URI of a readable SVG file.
   *
   * @return array<string, int>
   *   Associative array of 'width' and 'height', or empty when the file cannot
   *   be read or declares no usable 'viewBox'.
   */
  public static function read(string $path): array {
    if (!is_file($path) || !is_readable($path)) {
      return [];
    }

    $markup = file_get_contents($path, FALSE, NULL, 0, self::READ_LENGTH);

    if ($markup === FALSE || preg_match('~viewBox\s*=\s*["\']\s*[\d.+-]+[,\s]+[\d.+-]+[,\s]+([\d.]+)[,\s]+([\d.]+)~i', $markup, $matches) !== 1) {
      return [];
    }

    $width = (int) round((float) $matches[1]);
    $height = (int) round((float) $matches[2]);

    if ($width <= 0 || $height <= 0) {
      return [];
    }

    return ['width' => $width, 'height' => $height];
  }

}
