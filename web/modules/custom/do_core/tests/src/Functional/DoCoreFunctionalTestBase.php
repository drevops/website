<?php

declare(strict_types=1);

namespace Drupal\Tests\do_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\do_core\Traits\ArrayTrait;
use Drupal\Tests\do_core\Traits\AssertTrait;
use Drupal\Tests\do_core\Traits\MockTrait;
use Drupal\Tests\do_core\Traits\ReflectionTrait;

/**
 * Class DoCoreKernelTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\do_core\Tests
 */
abstract class DoCoreFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
