<?php

declare(strict_types=1);

namespace Drupal\Tests\drevops\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\do_core\Traits\ArrayTrait;
use Drupal\Tests\do_core\Traits\AssertTrait;
use Drupal\Tests\do_core\Traits\MockTrait;
use Drupal\Tests\do_core\Traits\ReflectionTrait;

/**
 * Class DrevopsUnitTestBase.
 *
 * Base class for all unit test cases.
 *
 * @package Drupal\drevops\Tests
 */
abstract class DrevopsUnitTestBase extends UnitTestCase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
