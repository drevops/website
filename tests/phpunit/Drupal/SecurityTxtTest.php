<?php

declare(strict_types=1);

namespace Drupal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Class SecurityTxtTest.
 *
 * Tests that the security.txt file follows RFC 9116 and is renewed before its
 * expiry date.
 *
 * @see https://www.rfc-editor.org/rfc/rfc9116
 */
#[Group('security_txt')]
class SecurityTxtTest extends TestCase {

  /**
   * Path to the security.txt file, relative to the repository root.
   */
  protected const FILE = 'web/.well-known/security.txt';

  /**
   * Days before the expiry date at which the file is due for renewal.
   */
  protected const RENEWAL_DAYS = 60;

  /**
   * Test that every line is a comment, a blank line or a field.
   */
  public function testFormat(): void {
    $this->assertStringEndsWith("\n", $this->contents(), 'The last line must end with a line break.');

    foreach ($this->lines() as $index => $line) {
      $this->assertMatchesRegularExpression('/^(#.*|[A-Za-z0-9-]+: \S.*)?$/', $line, sprintf('Line %d must be a comment, a blank line or a "Field: value" pair.', $index + 1));
    }
  }

  /**
   * Test that a required field is present.
   */
  #[DataProvider('dataProviderFieldIsPresent')]
  public function testFieldIsPresent(string $field): void {
    $this->assertNotEmpty($this->values($field), sprintf('The "%s" field must be present.', $field));
  }

  /**
   * Data provider for testFieldIsPresent().
   */
  public static function dataProviderFieldIsPresent(): \Iterator {
    yield 'contact' => ['Contact'];
    yield 'expires' => ['Expires'];
    yield 'preferred languages' => ['Preferred-Languages'];
    yield 'canonical' => ['Canonical'];
  }

  /**
   * Test that a single-value field appears at most once.
   */
  #[DataProvider('dataProviderFieldIsNotRepeated')]
  public function testFieldIsNotRepeated(string $field): void {
    $this->assertLessThanOrEqual(1, count($this->values($field)), sprintf('The "%s" field must not appear more than once.', $field));
  }

  /**
   * Data provider for testFieldIsNotRepeated().
   */
  public static function dataProviderFieldIsNotRepeated(): \Iterator {
    yield 'expires' => ['Expires'];
    yield 'preferred languages' => ['Preferred-Languages'];
  }

  /**
   * Test that the expiry date is not due for renewal.
   *
   * Fails once the expiry date is within the renewal window, so the file is
   * renewed before the published copy lapses.
   */
  public function testExpiresIsNotDueForRenewal(): void {
    $expires = $this->expires();
    $renew_by = new \DateTimeImmutable(sprintf('+%d days', static::RENEWAL_DAYS));

    $this->assertGreaterThan($renew_by->getTimestamp(), $expires->getTimestamp(), sprintf('The "Expires" date %s in %s is less than %d days away. Renew it to a date less than a year from today.', $expires->format('Y-m-d'), static::FILE, static::RENEWAL_DAYS));
  }

  /**
   * Test that the expiry date is less than a year away.
   */
  public function testExpiresIsLessThanAYearAway(): void {
    $expires = $this->expires();
    $year_away = new \DateTimeImmutable('+1 year');

    $this->assertLessThan($year_away->getTimestamp(), $expires->getTimestamp(), sprintf('The "Expires" date %s in %s is a year or more away. Set it to a date less than a year from today.', $expires->format('Y-m-d'), static::FILE));
  }

  /**
   * Read the file contents.
   *
   * @return string
   *   The file contents.
   */
  protected function contents(): string {
    $path = dirname(__DIR__, 3) . '/' . static::FILE;

    $this->assertFileExists($path);

    $contents = file_get_contents($path);

    if ($contents === FALSE) {
      $this->fail(sprintf('Unable to read %s.', static::FILE));
    }

    return $contents;
  }

  /**
   * Split the file contents into lines.
   *
   * @return list<string>
   *   Lines without their line endings.
   */
  protected function lines(): array {
    return explode("\n", rtrim(str_replace("\r\n", "\n", $this->contents()), "\n"));
  }

  /**
   * Collect the values of a field.
   *
   * @param string $field
   *   The field name, matched case-insensitively.
   *
   * @return list<string>
   *   The non-empty field values in file order.
   */
  protected function values(string $field): array {
    $values = [];

    foreach ($this->lines() as $line) {
      if (preg_match('/^([A-Za-z0-9-]+):\s*(\S.*)$/', $line, $matches) === 1 && strcasecmp($matches[1], $field) === 0) {
        $values[] = trim($matches[2]);
      }
    }

    return $values;
  }

  /**
   * Parse the expiry date.
   *
   * @return \DateTimeImmutable
   *   The expiry date.
   */
  protected function expires(): \DateTimeImmutable {
    $value = $this->values('Expires')[0] ?? '';

    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/i', $value, 'The "Expires" field must be an RFC 3339 date and time.');

    $expires = new \DateTimeImmutable($value);

    // PHP normalizes an impossible date or time, such as September 31, to
    // the next valid one, so the parsed value must match the input.
    $this->assertSame(strtoupper(substr($value, 0, 19)), $expires->format('Y-m-d\TH:i:s'), 'The "Expires" field must be a valid calendar date and time.');

    return $expires;
  }

}
