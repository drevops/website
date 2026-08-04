<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Generator;

/**
 * Assigns field values across a generation run.
 *
 * Random draws only make full coverage likely: over 20 iterations a 16-value
 * field misses at least one value more often than not. These helpers walk the
 * values instead, so every value is produced at least once.
 */
final class CaseMatrix {

  /**
   * Pick the value a run index maps onto.
   *
   * @param array $values
   *   Values to walk. Keys are ignored.
   * @param int $index
   *   Zero-based run index.
   * @param int $offset
   *   Shifts where the walk starts, so two dimensions of the same length do
   *   not produce the same value on every index.
   *
   * @return mixed
   *   The value for this index.
   */
  public static function cycle(array $values, int $index, int $offset = 0): mixed {
    if ($values === []) {
      throw new \InvalidArgumentException('Cannot cycle over an empty list of values.');
    }

    $values = array_values($values);
    $count = count($values);

    // PHP's modulo keeps the sign of its left operand, so a negative index or
    // offset would land outside the list.
    return $values[(($index + $offset) % $count + $count) % $count];
  }

  /**
   * Read a single bit of a run index.
   *
   * Boolean dimensions take a bit each rather than the index itself: cycling
   * every boolean on the index locks them in lockstep, so a run would only
   * ever produce the all-on and all-off combinations.
   *
   * @param int $index
   *   Zero-based run index.
   * @param int $bit
   *   Zero-based bit position to read.
   *
   * @return bool
   *   The bit value.
   */
  public static function bit(int $index, int $bit): bool {
    return (bool) (intdiv($index, 2 ** $bit) % 2);
  }

  /**
   * Take a subset whose size walks the given sizes.
   *
   * @param array $values
   *   Values to draw from. Keys are ignored.
   * @param int $index
   *   Zero-based run index.
   * @param array $sizes
   *   Subset sizes to walk, for example [0, 1, 3].
   * @param int $offset
   *   Shifts where the size walk starts.
   *
   * @return array
   *   Up to the walked size of values, fewer when fewer are available.
   */
  public static function subset(array $values, int $index, array $sizes, int $offset = 0): array {
    $values = array_values($values);
    $size = min(self::cycle($sizes, $index, $offset), count($values));

    $subset = [];

    for ($i = 0; $i < $size; $i++) {
      $subset[] = $values[($index + $i) % count($values)];
    }

    return $subset;
  }

}
