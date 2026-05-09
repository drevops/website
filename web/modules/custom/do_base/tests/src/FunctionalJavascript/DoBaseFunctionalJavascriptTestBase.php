<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\do_base\Traits\ArrayTrait;
use Drupal\Tests\do_base\Traits\AssertTrait;
use Drupal\Tests\do_base\Traits\BrowserHtmlDebugTrait;
use Drupal\Tests\do_base\Traits\MockTrait;
use Drupal\Tests\do_base\Traits\ReflectionTrait;

/**
 * Class DoBaseFunctionalJavascriptTestBase.
 *
 * Base class for functional JavaScript tests.
 *
 * @package Drupal\do_base\Tests
 */
abstract class DoBaseFunctionalJavascriptTestBase extends WebDriverTestBase {

  use ArrayTrait;
  use AssertTrait;
  use BrowserHtmlDebugTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
