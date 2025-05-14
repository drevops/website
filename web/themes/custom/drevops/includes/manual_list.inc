<?php

/**
 * @file
 * Automated list paragraph component.
 */

declare(strict_types=1);

/**
 * Implements template_preprocess_paragraph().
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
function drevops_preprocess_paragraph__civictheme_manual_list(array &$variables): void {
  _drevops_preprocess_paragraph__paragraph_field__list_layout($variables);
}
