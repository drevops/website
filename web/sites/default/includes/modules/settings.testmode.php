<?php

/**
 * @file
 * Testmode settings.
 */

declare(strict_types=1);

$settings['config_exclude_modules'][] = 'testmode';

// The automated list view backs every CivicTheme list component. Listing it
// here restricts those lists to test content while test mode is on, so a
// scenario counting list items is not affected by the content already in the
// database.
$config['testmode.settings']['views_node'] = ['content', 'civictheme_automated_list'];
