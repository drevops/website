<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\do_base\Traits\ArrayTrait;
use Drupal\Tests\do_base\Traits\AssertTrait;
use Drupal\Tests\do_base\Traits\MockTrait;
use Drupal\Tests\do_base\Traits\ReflectionTrait;

/**
 * Class DoBaseUnitTestBase.
 *
 * Base class for all unit test cases.
 *
 * @package Drupal\do_base\Tests
 */
abstract class DoBaseUnitTestBase extends UnitTestCase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
