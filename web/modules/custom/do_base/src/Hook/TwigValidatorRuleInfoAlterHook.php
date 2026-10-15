<?php

declare(strict_types=1);

namespace Drupal\do_base\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\do_base\Twig\ImageDimensionsExtension;
use Drupal\sdc_devel\TwigValidatorRulePluginBase;

/**
 * Twig validator rule info alter hooks for do_base module.
 */
final class TwigValidatorRuleInfoAlterHook {

  /**
   * Implements hook_twig_validator_rule_info_alter().
   */
  #[Hook('twig_validator_rule_info_alter')]
  public function alter(array &$definitions): void {
    // Writing to an absent rule creates a definition with no class, which
    // fails every validation run.
    if (!isset($definitions['function'])) {
      return;
    }

    $definitions['function']['rule_on_name'][TwigValidatorRulePluginBase::RULE_NAME_ALLOW][] = ImageDimensionsExtension::FUNCTION_NAME;
  }

}
