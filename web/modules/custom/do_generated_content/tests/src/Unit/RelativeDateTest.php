<?php

declare(strict_types=1);

namespace Drupal\Tests\do_generated_content\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\do_generated_content\Generator\RelativeDate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the relative date formatting used by generated dates.
 */
#[Group('DoGeneratedContent')]
class RelativeDateTest extends UnitTestCase {

  /**
   * Tests that an offset lands on the expected day.
   */
  #[DataProvider('dataProviderFormat')]
  public function testFormat(string $modifier): void {
    $expected = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->modify($modifier);

    $this->assertSame($expected->format('Y-m-d'), RelativeDate::format($modifier));
  }

  /**
   * Data provider for testFormat().
   */
  public static function dataProviderFormat(): \Iterator {
    yield 'now' => ['now'];
    yield 'past' => ['-30 days'];
    yield 'recent past' => ['-3 days'];
    yield 'future' => ['+30 days'];
  }

  /**
   * Tests that a datetime format keeps the time component.
   */
  public function testFormatWithTime(): void {
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', RelativeDate::format('+7 days', 'Y-m-d\TH:i:s'));
  }

  /**
   * Tests that ordering is preserved across offsets.
   */
  public function testOffsetsAreOrdered(): void {
    $past = RelativeDate::format('-30 days');
    $now = RelativeDate::format('now');
    $future = RelativeDate::format('+30 days');

    $this->assertLessThan($now, $past);
    $this->assertLessThan($future, $now);
  }

  /**
   * Tests that an unusable offset fails loudly.
   */
  public function testFormatRejectsInvalidModifier(): void {
    $this->expectException(\DateMalformedStringException::class);

    RelativeDate::format('not a date');
  }

}
