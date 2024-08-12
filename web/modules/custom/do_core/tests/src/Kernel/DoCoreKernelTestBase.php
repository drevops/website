<?php

declare(strict_types=1);

namespace Drupal\Tests\do_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\do_core\Traits\ArrayTrait;
use Drupal\Tests\do_core\Traits\AssertTrait;
use Drupal\Tests\do_core\Traits\MockTrait;
use Drupal\Tests\do_core\Traits\ReflectionTrait;

/**
 * Class DoCoreKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\do_core\Tests
 */
abstract class DoCoreKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
