<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Class ExampleTest.
 *
 * Example functional test case class.
 *
 * @package Drupal\do_base\Tests
 */
#[Group('DoBase')]
#[RunTestsInSeparateProcesses]
class ExampleTest extends DoBaseFunctionalTestBase {

  /**
   * Tests addition.
   */
  #[Group('addition')]
  public function testAdd(): void {
    $this->assertEquals(2, 1 + 1);
  }

  /**
   * Tests subtraction.
   */
  #[Group('subtraction')]
  public function testSubtract(): void {
    $this->assertEquals(1, 2 - 1);
  }

}
