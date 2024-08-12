<?php

declare(strict_types=1);

namespace Drupal\Tests\do_core\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\do_core\Traits\ArrayTrait;
use Drupal\Tests\do_core\Traits\AssertTrait;
use Drupal\Tests\do_core\Traits\MockTrait;
use Drupal\Tests\do_core\Traits\ReflectionTrait;

/**
 * Class DoCoreUnitTestBase.
 *
 * Base class for all unit test cases.
 *
 * @package Drupal\do_core\Tests
 */
abstract class DoCoreUnitTestBase extends UnitTestCase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
