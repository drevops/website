<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\node\Entity\Node;
use Drupal\path_alias\Entity\PathAlias;

/**
 * Create Information page with civic theme and alias.
 */
function do_base_deploy_create_information_page(): void {
  // Check if page already exists.
  $existing_nodes = \Drupal::entityTypeManager()
    ->getStorage('node')
    ->loadByProperties([
      'type' => 'civictheme_page',
      'title' => 'Information',
    ]);

  if (empty($existing_nodes)) {
    // Create the node.
    $node = Node::create([
      'type' => 'civictheme_page',
      'title' => 'Information',
      'status' => 1,
      'uid' => 1,
      'body' => [
        'value' => '',
        'format' => 'basic_html',
      ],
    ]);
    $node->save();

    // Create the path alias.
    $path_alias = PathAlias::create([
      'path' => '/node/' . $node->id(),
      'alias' => '/information',
      'langcode' => 'en',
    ]);
    $path_alias->save();

    \Drupal::logger('do_base')->info('Information page created with ID: @id and alias /information', ['@id' => $node->id()]);
  }
  else {
    \Drupal::logger('do_base')->info('Information page already exists, skipping creation');
  }
}
