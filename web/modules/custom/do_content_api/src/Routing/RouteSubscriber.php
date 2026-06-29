<?php

declare(strict_types=1);

namespace Drupal\do_content_api\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Enables key authentication on the subrequests front controller.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    $route = $collection->get('subrequests.front-controller');

    if ($route === NULL) {
      // @codeCoverageIgnoreStart
      return;
      // @codeCoverageIgnoreEnd
    }

    $auth = $route->getOption('_auth') ?? [];

    // The route ships without key_auth, so an api-key blueprint request would
    // otherwise resolve to the anonymous user.
    if (!in_array('key_auth', $auth, TRUE)) {
      $auth[] = 'key_auth';
      $route->setOption('_auth', $auth);
    }
  }

}
