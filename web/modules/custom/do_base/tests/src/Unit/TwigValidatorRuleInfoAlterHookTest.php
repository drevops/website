<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\do_base\Hook\TwigValidatorRuleInfoAlterHook;
use Drupal\do_base\Twig\ImageDimensionsExtension;
use Drupal\sdc_devel\Plugin\TwigValidatorRule\TwigValidatorRuleFunction;
use Drupal\sdc_devel\TwigValidatorRulePluginBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Twig\Node\Expression\FunctionExpression;

/**
 * Tests the Twig validator rule definitions this site alters.
 *
 * The hook is driven directly, so each shape of the definitions is reachable on
 * its own. That the validator then accepts the function is asserted in the
 * kernel test of the same name.
 */
#[Group('do_base')]
class TwigValidatorRuleInfoAlterHookTest extends DoBaseUnitTestBase {

  /**
   * Tests that the image dimensions function is added to the allow list.
   *
   * @param array<int, array<int|string, string>> $rule_on_name
   *   The names the function rule declares, keyed by bucket.
   * @param list<string> $expected
   *   The names expected in the allow bucket.
   */
  #[DataProvider('dataProviderImageDimensionsFunctionIsAllowed')]
  public function testImageDimensionsFunctionIsAllowed(array $rule_on_name, array $expected): void {
    // Prepare.
    $definitions = ['function' => ['id' => 'function', 'rule_on_name' => $rule_on_name]];

    // Act.
    new TwigValidatorRuleInfoAlterHook()->alter($definitions);

    // Assert.
    $this->assertSame($expected, $definitions['function']['rule_on_name'][TwigValidatorRulePluginBase::RULE_NAME_ALLOW]);
  }

  /**
   * Data provider for testImageDimensionsFunctionIsAllowed.
   */
  public static function dataProviderImageDimensionsFunctionIsAllowed(): \Iterator {
    yield 'an existing allow list' => [
      [TwigValidatorRulePluginBase::RULE_NAME_ALLOW => ['include', 'range']],
      ['include', 'range', ImageDimensionsExtension::FUNCTION_NAME],
    ];
    yield 'no allow list' => [
      [TwigValidatorRulePluginBase::RULE_NAME_FORBID => ['url' => 'Keep components sandboxed.']],
      [ImageDimensionsExtension::FUNCTION_NAME],
    ];
    yield 'no names at all' => [
      [],
      [ImageDimensionsExtension::FUNCTION_NAME],
    ];
  }

  /**
   * Tests that nothing but the allow list of the function rule changes.
   */
  public function testEverythingElseIsKept(): void {
    // Prepare.
    $definitions = [
      'function' => [
        'id' => 'function',
        'twig_node_type' => FunctionExpression::class,
        'rule_on_name' => [
          TwigValidatorRulePluginBase::RULE_NAME_IGNORE => ['render_var'],
          TwigValidatorRulePluginBase::RULE_NAME_WARN => ['source' => 'Bad architecture.'],
          TwigValidatorRulePluginBase::RULE_NAME_FORBID => ['url' => 'Keep components sandboxed.'],
          TwigValidatorRulePluginBase::RULE_NAME_ALLOW => ['include'],
        ],
        'class' => TwigValidatorRuleFunction::class,
        'provider' => 'sdc_devel',
      ],
      'filter' => [
        'id' => 'filter',
        'rule_on_name' => [TwigValidatorRulePluginBase::RULE_NAME_ALLOW => ['default']],
      ],
    ];
    $expected = $definitions;
    $expected['function']['rule_on_name'][TwigValidatorRulePluginBase::RULE_NAME_ALLOW][] = ImageDimensionsExtension::FUNCTION_NAME;

    // Act.
    new TwigValidatorRuleInfoAlterHook()->alter($definitions);

    // Assert.
    $this->assertSame($expected, $definitions);
  }

  /**
   * Tests that definitions without the function rule are left alone.
   *
   * @param array<string, array<string, mixed>> $definitions
   *   The rule definitions as the plugin manager discovered them.
   */
  #[DataProvider('dataProviderDefinitionsWithoutTheFunctionRuleAreLeftAlone')]
  public function testDefinitionsWithoutTheFunctionRuleAreLeftAlone(array $definitions): void {
    // Prepare.
    $expected = $definitions;

    // Act.
    new TwigValidatorRuleInfoAlterHook()->alter($definitions);

    // Assert.
    $this->assertSame($expected, $definitions);
  }

  /**
   * Data provider for testDefinitionsWithoutTheFunctionRuleAreLeftAlone.
   */
  public static function dataProviderDefinitionsWithoutTheFunctionRuleAreLeftAlone(): \Iterator {
    yield 'no rules at all' => [[]];
    yield 'other rules only' => [['filter' => ['id' => 'filter', 'rule_on_name' => [TwigValidatorRulePluginBase::RULE_NAME_ALLOW => ['default']]]]];
  }

}
