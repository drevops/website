<?php

/**
 * @file
 * XML sitemap settings.
 */

declare(strict_types=1);

// Cron regenerates the sitemap with no request to derive a host from, so
// xmlsitemap keeps its own base URL. Left unset it falls back to a state value
// seeded at install time from whichever host ran the installer.
$settings['xmlsitemap_base_url'] = 'https://www.drevops.com';
