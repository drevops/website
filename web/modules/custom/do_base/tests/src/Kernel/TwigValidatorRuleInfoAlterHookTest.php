<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Kernel;

use Drupal\do_base\Twig\ImageDimensionsExtension;
use Drupal\sdc_devel\TwigValidator\TwigValidator;
use Drupal\sdc_devel\TwigValidatorRuleInterface;
use Drupal\sdc_devel\ValidatorMessage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the component validator accepts this module's Twig functions.
 *
 * The validator is the one the SDC lint runs, so this covers the hook being
 * discovered as well as what it alters.
 */
#[Group('do_base')]
#[RunTestsInSeparateProcesses]
class TwigValidatorRuleInfoAlterHookTest extends DoBaseKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'do_base',
    'sdc_devel',
  ];

  /**
   * Tests that the function rule allows the image dimensions function.
   */
  public function testFunctionRuleAllowsImageDimensions(): void {
    // Act.
    $rule = $this->container->get('plugin.manager.twig_validator_rule')->createInstance('function');

    // Assert.
    $this->assertInstanceOf(TwigValidatorRuleInterface::class, $rule);
    $this->assertContains(ImageDimensionsExtension::FUNCTION_NAME, $rule->getNameAllow());
  }

  /**
   * Tests that a template calling the image dimensions function validates.
   */
  public function testTemplateCallingImageDimensionsValidates(): void {
    // Prepare.
    $validator = $this->container->get('sdc_devel.twig_validator');
    $this->assertInstanceOf(TwigValidator::class, $validator);

    // Act.
    $validator->validateSource(sprintf("{{ %s('/image.png').width }}", ImageDimensionsExtension::FUNCTION_NAME));

    // Assert.
    $messages = array_map(static fn(ValidatorMessage $message): string => (string) $message->message(), $validator->getMessages());
    $this->assertSame([], $messages);
  }

}
