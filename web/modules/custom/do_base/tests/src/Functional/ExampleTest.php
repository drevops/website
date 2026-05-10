<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * Class ExampleTest.
 *
 * Example functional test case class.
 *
 * @package Drupal\do_base\Tests
 */
#[Group('DoBase')]
class ExampleTest extends DoBaseFunctionalTestBase {

  /**
   * Tests addition.
   */
  #[Group('addition')]
  public function testAddition(): void {
    $this->assertEquals(2, 1 + 1);
  }

  /**
   * Tests subtraction.
   */
  #[Group('functional:subtraction')]
  public function testSubtraction(): void {
    $this->assertEquals(1, 2 - 1);
  }

}
