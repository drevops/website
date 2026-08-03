<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Generator;

/**
 * Storage formats shared by the node and component generators.
 *
 * @codeCoverageIgnore
 */
final class Formats {

  /**
   * Text format used by every CivicTheme rich text field.
   */
  public const string TEXT = 'civictheme_rich_text';

  /**
   * Storage format of a datetime field.
   */
  public const string DATETIME = 'Y-m-d\TH:i:s';

}
