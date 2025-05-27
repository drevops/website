<?php

/**
 * @file
 * Theme settings form for Drevops theme.
 */

declare(strict_types=1);

use Drupal\civictheme\CivicthemeConfigManager;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_system_theme_settings_alter().
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
function drevops_form_system_theme_settings_alter(array &$form, FormStateInterface &$form_state): void {
  // @todo Move to CivicthemeSettingsFormSectionComponents::form.
  $theme_config_manager = \Drupal::service('class_resolver')->getInstanceFromDefinition(CivicthemeConfigManager::class);
  if (isset($form['components']['header'])) {
    $form['components']['header']['sticky'] = [
      '#title' => t('Sticky'),
      '#description' => t('Make the header sticky and semitransparent.'),
      '#type' => 'checkbox',
      '#default_value' => $theme_config_manager->load('components.header.sticky', FALSE),
    ];
  }
}
