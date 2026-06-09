<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\drupal_helpers\Helper;

/**
 * Flip every component paragraph to the dark theme.
 *
 * The redesign renders the whole site dark. CivicTheme themes each component
 * individually through the shared `field_c_p_theme` field, so this switches all
 * existing paragraphs - across every bundle - to `dark`. The revision is
 * updated in place so the referencing entity revision keeps resolving to it.
 * Content created by later deploy hooks is built dark directly.
 */
function do_base_deploy_components_dark(array &$sandbox): ?string {
  return Helper::entity($sandbox)->batchEntity('paragraph', NULL, static function ($paragraph): void {
    if (!$paragraph->hasField('field_c_p_theme')) {
      return;
    }

    if ($paragraph->get('field_c_p_theme')->value === 'dark') {
      return;
    }

    $paragraph->set('field_c_p_theme', 'dark');
    $paragraph->setNewRevision(FALSE);
    $paragraph->save();
  });
}
