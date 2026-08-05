<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\do_base\Traits\ExportedConfigTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that the exported date configuration renders Australian dates.
 *
 * Regional settings are routinely changed through the admin UI and re-exported,
 * which is how a US-defaulted pattern reaches the repository unnoticed. These
 * assertions run the shipped patterns against a reference date so a day-second
 * format fails the build rather than the reader.
 */
#[CoversNothing]
#[Group('do_base')]
class DateFormatConfigTest extends UnitTestCase {

  use ExportedConfigTrait;

  /**
   * Reference date rendered through each pattern: 4 August 2026, 2:30pm.
   *
   * Day and month are both unambiguous and differ from each other, so a
   * month-first pattern cannot render the same string as a day-first one.
   */
  protected const string REFERENCE_DATE = '2026-08-04 14:30:00';

  /**
   * Tests that a shipped date format renders in Australian order.
   */
  #[DataProvider('dataProviderDateFormatPattern')]
  public function testDateFormatPattern(string $config_name, string $expected): void {
    // Prepare.
    $pattern = $this->loadConfig('core.date_format.' . $config_name . '.yml')['pattern'];
    $date = new \DateTimeImmutable(self::REFERENCE_DATE);

    // Act.
    $rendered = $date->format($pattern);

    // Assert.
    $this->assertSame($expected, $rendered, sprintf('Date format "%s" does not render an Australian date.', $config_name));
  }

  /**
   * Data provider for testDateFormatPattern.
   */
  public static function dataProviderDateFormatPattern(): \Iterator {
    yield 'short' => ['short', '04/08/2026 - 14:30'];
    yield 'medium' => ['medium', 'Tue, 04/08/2026 - 14:30'];
    yield 'long' => ['long', 'Tuesday, 4 August 2026 - 14:30'];
    yield 'fallback' => ['fallback', 'Tue, 04/08/2026 - 14:30'];
    yield 'civictheme short date' => ['civictheme_short_date', '4 Aug 2026'];
    yield 'civictheme short date and time' => ['civictheme_short_date_and_time', '4 Aug 2026 - 14:30'];
  }

  /**
   * Tests that the machine-readable formats stay ISO 8601.
   *
   * These populate the datetime attribute of time elements and HTML5 date
   * input values, where the format is fixed by specification and localising
   * them would emit invalid markup.
   */
  #[DataProvider('dataProviderMachineReadableFormat')]
  public function testMachineReadableFormat(string $config_name, string $expected): void {
    // Prepare.
    $pattern = $this->loadConfig('core.date_format.' . $config_name . '.yml')['pattern'];

    // Assert.
    $this->assertSame($expected, $pattern);
  }

  /**
   * Data provider for testMachineReadableFormat.
   */
  public static function dataProviderMachineReadableFormat(): \Iterator {
    yield 'html date' => ['html_date', 'Y-m-d'];
    yield 'html datetime' => ['html_datetime', 'Y-m-d\TH:i:sO'];
    yield 'html month' => ['html_month', 'Y-m'];
    yield 'html time' => ['html_time', 'H:i:s'];
    yield 'html week' => ['html_week', 'Y-\WW'];
    yield 'html year' => ['html_year', 'Y'];
    yield 'html yearless date' => ['html_yearless_date', 'm-d'];
  }

  /**
   * Tests the regional settings that accompany the date formats.
   */
  #[DataProvider('dataProviderRegionalSetting')]
  public function testRegionalSetting(array $keys, mixed $expected): void {
    // Prepare.
    $value = $this->loadConfig('system.date.yml');

    // Act.
    foreach ($keys as $key) {
      $value = $value[$key];
    }

    // Assert.
    $this->assertSame($expected, $value);
  }

  /**
   * Data provider for testRegionalSetting.
   */
  public static function dataProviderRegionalSetting(): \Iterator {
    yield 'default timezone' => [['timezone', 'default'], 'Australia/Melbourne'];
    yield 'default country' => [['country', 'default'], 'AU'];
    yield 'week starts on Monday' => [['first_day'], 1];
  }

}
