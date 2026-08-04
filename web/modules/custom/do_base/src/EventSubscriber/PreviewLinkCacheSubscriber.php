<?php

declare(strict_types=1);

namespace Drupal\do_base\EventSubscriber;

use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;

/**
 * Keeps preview link responses out of every cache.
 *
 * A preview link shows unpublished content to whoever holds its token, and that
 * token can expire or be regenerated at any moment. Any stored copy outlives
 * it: a shared cache would go on serving the content to whoever asks for that
 * URL, and a browser cache would surface it from history once the token had
 * stopped working.
 */
final class PreviewLinkCacheSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected KillSwitch $killSwitch,
  ) {}

  /**
   * Marks a preview link response as never storable.
   */
  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $route = $event->getRequest()->attributes->get(RouteObjectInterface::ROUTE_OBJECT);

    if (!$route instanceof Route || $route->getOption('_preview_link_route') !== TRUE) {
      return;
    }

    // The internal page cache decides from the response policy rather than
    // from the header, so setting the header alone would not stop it storing
    // the page.
    $this->killSwitch->trigger();

    // Rendering the page starts a session, which on its own only earns the
    // response 'private'. That bars shared caches but still lets the
    // recipient's own browser keep a copy, so ask for no storage at all.
    $event->getResponse()->headers->set('Cache-Control', 'no-store');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Later than FinishResponseSubscriber, which sets Cache-Control itself.
    return [KernelEvents::RESPONSE => ['onResponse', -10]];
  }

}
