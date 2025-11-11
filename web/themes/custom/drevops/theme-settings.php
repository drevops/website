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
 */
function drevops_form_system_theme_settings_alter(array &$form, FormStateInterface &$form_state): void {
  // @todo Move to CivicthemeSettingsFormSectionComponents::form.
  $theme_config_manager = \Drupal::service('class_resolver')->getInstanceFromDefinition(CivicthemeConfigManager::class);
  if (isset($form['components']['header'])) {
    $form['components']['header']['is_sticky'] = [
      '#title' => t('Sticky'),
      '#description' => t('Make the header sticky and semi-transparent.'),
      '#type' => 'checkbox',
      '#default_value' => (bool) $theme_config_manager->load('components.header.is_sticky', FALSE),
    ];
  }
}
