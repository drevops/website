<?php

/**
 * @file
 * Generated content settings.
 */

declare(strict_types=1);

// Exclude the generated content modules from configuration export: they are
// enabled only in non-production environments. The dependency is listed
// explicitly because config_exclude_modules does not follow dependencies.
$settings['config_exclude_modules'][] = 'do_generated_content';
$settings['config_exclude_modules'][] = 'generated_content';
