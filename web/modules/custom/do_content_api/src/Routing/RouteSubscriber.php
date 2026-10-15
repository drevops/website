<?php

declare(strict_types=1);

namespace Drupal\do_content_api\Routing;

use Drupal\Core\Authentication\AuthenticationCollectorInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Enables key authentication on the routes the API client calls directly.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Routes outside JSON:API that accept the API key.
   */
  public const array ROUTES = [
    'subrequests.front-controller',
    'system.files',
    'system.private_file_download',
  ];

  public function __construct(
    protected AuthenticationCollectorInterface $authenticationCollector,
  ) {}

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    foreach (self::ROUTES as $route_name) {
      $route = $collection->get($route_name);

      if (!$route instanceof Route) {
        continue;
      }

      // A route without an _auth option accepts every global provider, so the
      // list starts from those to keep session cookies working.
      $auth = $route->getOption('_auth') ?? $this->globalProviders();

      // None of these routes lists key_auth, so an api-key request would
      // otherwise not authenticate as the service account.
      if (!in_array('key_auth', $auth, TRUE)) {
        $auth[] = 'key_auth';
        $route->setOption('_auth', $auth);
      }
    }
  }

  /**
   * Lists the authentication providers that apply to every route.
   */
  protected function globalProviders(): array {
    $provider_ids = array_keys($this->authenticationCollector->getSortedProviders());

    return array_values(array_filter($provider_ids, $this->authenticationCollector->isGlobal(...)));
  }

}
