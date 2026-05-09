<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\do_base\Traits\ArrayTrait;
use Drupal\Tests\do_base\Traits\AssertTrait;
use Drupal\Tests\do_base\Traits\MockTrait;
use Drupal\Tests\do_base\Traits\ReflectionTrait;

/**
 * Class DoBaseKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\do_base\Tests
 */
abstract class DoBaseKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
