<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 *
 * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.Before
 * phpcs:disable Squiz.WhiteSpace.FunctionSpacing.After
 */

declare(strict_types=1);

use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Installs custom theme.
 */
function do_core_deploy_install_theme(): void {
  \Drupal::service('theme_installer')->install(['olivero']);
  \Drupal::service('theme_installer')->install(['drevops']);
  \Drupal::service('config.factory')->getEditable('system.theme')->set('default', 'drevops')->save();
}

/**
 * Enables Redis module.
 */
function do_core_deploy_enable_redis(): void {
  $listing = new ExtensionDiscovery(\Drupal::root());
  $modules = $listing->scan('module');
  if (!empty($modules['redis'])) {
    \Drupal::service('module_installer')->install(['redis']);
  }
}


/**
 * Enables Search API and Search API Solr modules.
 */
function do_core_deploy_enable_clamav(): void {
  $listing = new ExtensionDiscovery(\Drupal::root());
  $modules = $listing->scan('module');
  if (!empty($modules['clamav'])) {
    \Drupal::service('module_installer')->install(['media']);
    \Drupal::service('module_installer')->install(['clamav']);
  }
}


/**
 * Enables Search API and Search API Solr modules.
 */
function do_core_deploy_enable_search_api_solr(): void {
  $listing = new ExtensionDiscovery(\Drupal::root());
  $modules = $listing->scan('module');
  if (!empty($modules['search_api']) && !empty($modules['search_api_solr']) && !empty($modules['ys_search'])) {
    \Drupal::service('module_installer')->install(['ys_search']);
  }
}
