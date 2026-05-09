<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\do_base\Traits\ArrayTrait;
use Drupal\Tests\do_base\Traits\AssertTrait;
use Drupal\Tests\do_base\Traits\MockTrait;
use Drupal\Tests\do_base\Traits\ReflectionTrait;

/**
 * Class DoBaseFunctionalTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\do_base\Tests
 */
abstract class DoBaseFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
