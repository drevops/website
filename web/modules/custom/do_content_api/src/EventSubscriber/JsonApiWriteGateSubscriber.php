<?php

declare(strict_types=1);

namespace Drupal\do_content_api\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Restricts JSON:API write requests to the content authoring API.
 *
 * Enabling JSON:API writes (read_only: false) would otherwise expose the
 * standard write endpoints to every role that can already mutate content. This
 * narrows that surface back to the dedicated authoring permission while leaving
 * reads open to everyone.
 */
final class JsonApiWriteGateSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected AccountInterface $account,
  ) {}

  /**
   * Denies JSON:API mutations to anyone without the authoring permission.
   */
  public function onRequest(RequestEvent $event): void {
    $request = $event->getRequest();

    if ($request->isMethodSafe()) {
      return;
    }

    if (!str_starts_with((string) $request->attributes->get('_route'), 'jsonapi.')) {
      return;
    }

    if (!$this->account->hasPermission('use content authoring api')) {
      throw new AccessDeniedHttpException('JSON:API writes are restricted to the content authoring API.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run after the router has resolved the route name but before the
    // controller is invoked.
    return [KernelEvents::REQUEST => ['onRequest', 30]];
  }

}
