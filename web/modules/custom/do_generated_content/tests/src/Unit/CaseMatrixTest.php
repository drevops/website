<?php

declare(strict_types=1);

namespace Drupal\Tests\do_generated_content\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\do_generated_content\Generator\CaseMatrix;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the value assignment behind the generated content coverage claim.
 */
#[Group('DoGeneratedContent')]
class CaseMatrixTest extends UnitTestCase {

  /**
   * Number of entities each node generator creates.
   */
  protected const RUN_LENGTH = 20;

  /**
   * Tests that a run index maps onto the expected value.
   */
  #[DataProvider('dataProviderCycle')]
  public function testCycle(array $values, int $index, int $offset, mixed $expected): void {
    $this->assertSame($expected, CaseMatrix::cycle($values, $index, $offset));
  }

  /**
   * Data provider for testCycle().
   */
  public static function dataProviderCycle(): \Iterator {
    yield 'first value' => [['a', 'b', 'c'], 0, 0, 'a'];
    yield 'second value' => [['a', 'b', 'c'], 1, 0, 'b'];
    yield 'wraps around' => [['a', 'b', 'c'], 3, 0, 'a'];
    yield 'wraps around twice' => [['a', 'b', 'c'], 7, 0, 'b'];
    yield 'offset shifts the start' => [['a', 'b', 'c'], 0, 1, 'b'];
    yield 'offset wraps around' => [['a', 'b', 'c'], 2, 1, 'a'];
    yield 'single value' => [['only'], 5, 3, 'only'];
    yield 'non-sequential keys are ignored' => [[3 => 'a', 9 => 'b'], 1, 0, 'b'];
    yield 'integer values' => [[10, 20], 1, 0, 20];
  }

  /**
   * Tests that cycling an empty list is rejected rather than silently skipped.
   */
  public function testCycleRejectsEmptyValues(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Cannot cycle over an empty list of values.');

    CaseMatrix::cycle([], 0);
  }

  /**
   * Tests that a full run produces every value of a dimension.
   *
   * This is the guarantee the generators rely on. The largest list field on
   * the site declares 16 values, so the run length has to cover at least that.
   */
  #[DataProvider('dataProviderCycleCoversEveryValue')]
  public function testCycleCoversEveryValue(int $value_count): void {
    $values = range(1, $value_count);

    $produced = [];

    for ($index = 0; $index < self::RUN_LENGTH; $index++) {
      $produced[] = CaseMatrix::cycle($values, $index);
    }

    $this->assertEmpty(array_diff($values, $produced), sprintf('Every one of %s values is produced across a run.', $value_count));
  }

  /**
   * Data provider for testCycleCoversEveryValue().
   */
  public static function dataProviderCycleCoversEveryValue(): \Iterator {
    yield 'boolean-sized' => [2];
    yield 'moderation states' => [4];
    yield 'largest list field on the site' => [16];
    yield 'exactly the run length' => [self::RUN_LENGTH];
  }

  /**
   * Tests that each bit position reads the expected value.
   */
  #[DataProvider('dataProviderBit')]
  public function testBit(int $index, int $bit, bool $expected): void {
    $this->assertSame($expected, CaseMatrix::bit($index, $bit));
  }

  /**
   * Data provider for testBit().
   */
  public static function dataProviderBit(): \Iterator {
    yield 'zero has no bits set' => [0, 0, FALSE];
    yield 'one sets the lowest bit' => [1, 0, TRUE];
    yield 'one leaves the second bit clear' => [1, 1, FALSE];
    yield 'two sets the second bit' => [2, 1, TRUE];
    yield 'two leaves the lowest bit clear' => [2, 0, FALSE];
    yield 'three sets both low bits' => [3, 1, TRUE];
    yield 'eight sets the fourth bit' => [8, 3, TRUE];
    yield 'nineteen sets the lowest bit' => [19, 0, TRUE];
    yield 'nineteen leaves the third bit clear' => [19, 2, FALSE];
  }

  /**
   * Tests that boolean dimensions vary independently rather than in lockstep.
   */
  public function testBitProducesDistinctCombinations(): void {
    $combinations = [];

    for ($index = 0; $index < self::RUN_LENGTH; $index++) {
      $combinations[] = implode('', array_map(
        static fn(int $bit): string => CaseMatrix::bit($index, $bit) ? '1' : '0',
        range(0, 3)
      ));
    }

    $this->assertCount(16, array_unique($combinations), 'Four boolean dimensions walk all 16 combinations across a run.');
  }

  /**
   * Tests that both states of a single boolean dimension are produced.
   */
  #[DataProvider('dataProviderBitCoversBothStates')]
  public function testBitCoversBothStates(int $bit): void {
    $produced = [];

    for ($index = 0; $index < self::RUN_LENGTH; $index++) {
      $produced[] = CaseMatrix::bit($index, $bit);
    }

    $this->assertContains(TRUE, $produced);
    $this->assertContains(FALSE, $produced);
  }

  /**
   * Data provider for testBitCoversBothStates().
   */
  public static function dataProviderBitCoversBothStates(): \Iterator {
    yield 'bit 0' => [0];
    yield 'bit 1' => [1];
    yield 'bit 2' => [2];
    yield 'bit 3' => [3];
    yield 'bit 4' => [4];
  }

  /**
   * Tests that a subset takes the walked number of values.
   */
  #[DataProvider('dataProviderSubset')]
  public function testSubset(array $values, int $index, array $sizes, array $expected): void {
    $this->assertSame($expected, CaseMatrix::subset($values, $index, $sizes));
  }

  /**
   * Data provider for testSubset().
   */
  public static function dataProviderSubset(): \Iterator {
    yield 'empty subset' => [['a', 'b', 'c'], 0, [0, 2], []];
    yield 'two values' => [['a', 'b', 'c'], 1, [0, 2], ['b', 'c']];
    yield 'wraps past the end' => [['a', 'b', 'c'], 2, [3], ['c', 'a', 'b']];
    yield 'size larger than the list' => [['a', 'b'], 0, [5], ['a', 'b']];
    yield 'no values to draw from' => [[], 0, [3], []];
    yield 'non-sequential keys are ignored' => [[7 => 'a', 2 => 'b'], 0, [2], ['a', 'b']];
  }

  /**
   * Tests that a walk driven by a dense counter reaches every value.
   *
   * Nested components walk on a counter of their own rather than on the host
   * node's index: only a few node indexes carry a manual list, so a walk on
   * that index revisits the same few card bundles and never reaches the rest.
   */
  #[DataProvider('dataProviderDenseWalk')]
  public function testDenseWalkCoversEveryValue(int $value_count, int $stride, int $iterations): void {
    $values = range(1, $value_count);

    $produced = [];

    for ($iteration = 0; $iteration < $iterations; $iteration++) {
      for ($slot = 0; $slot < $stride; $slot++) {
        $produced[] = CaseMatrix::cycle($values, $iteration * $stride + $slot);
      }
    }

    $this->assertEmpty(array_diff($values, $produced), sprintf('%s iterations of %s slots reach all %s values.', $iterations, $stride, $value_count));
  }

  /**
   * Data provider for testDenseWalkCoversEveryValue().
   */
  public static function dataProviderDenseWalk(): \Iterator {
    yield 'manual list cards' => [13, 3, 5];
    yield 'slider slides' => [2, 3, 1];
    yield 'stride larger than the list' => [2, 3, 1];
    yield 'exactly one pass' => [9, 3, 3];
  }

  /**
   * Tests that every subset size is produced across a run.
   */
  public function testSubsetWalksEverySize(): void {
    $sizes = [0, 1, 3];

    $produced = [];

    for ($index = 0; $index < self::RUN_LENGTH; $index++) {
      $produced[] = count(CaseMatrix::subset(['a', 'b', 'c', 'd'], $index, $sizes));
    }

    $this->assertEmpty(array_diff($sizes, $produced));
  }

}
