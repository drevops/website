<?php

declare(strict_types=1);

namespace Drupal\Tests\drevops\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\do_core\Traits\ArrayTrait;
use Drupal\Tests\do_core\Traits\AssertTrait;
use Drupal\Tests\do_core\Traits\MockTrait;
use Drupal\Tests\do_core\Traits\ReflectionTrait;

/**
 * Class DrevopsKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\drevops\Tests
 */
abstract class DrevopsKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
