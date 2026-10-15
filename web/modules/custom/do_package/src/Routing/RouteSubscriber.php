<?php

declare(strict_types=1);

namespace Drupal\do_package\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Keeps responses from the private file routes out of every cache.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Routes that serve files from the private file system.
   */
  public const array ROUTES = [
    'system.files',
    'system.private_file_download',
  ];

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    foreach (self::ROUTES as $route_name) {
      $route = $collection->get($route_name);

      if (!$route instanceof Route) {
        continue;
      }

      // A denied download otherwise carries the public page max-age, so a
      // cache keyed on the URL alone serves that 403 to allowed accounts.
      $route->setOption('no_cache', TRUE);
    }
  }

}
