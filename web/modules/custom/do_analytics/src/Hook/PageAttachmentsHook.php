<?php

declare(strict_types=1);

namespace Drupal\do_analytics\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;

/**
 * Attaches the analytics tracker and the context of the current page.
 */
final class PageAttachmentsHook {

  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected AdminContext $adminContext,
  ) {}

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function attach(array &$attachments): void {
    if ($this->adminContext->isAdminRoute($this->routeMatch->getRouteObject())) {
      return;
    }

    $attachments['#attached']['library'][] = 'do_analytics/tracker';
    $attachments['#attached']['drupalSettings']['doAnalytics']['page'] = $this->page();
    $attachments['#cache']['contexts'][] = 'route';
  }

  /**
   * Describes the current page.
   *
   * @return array{type: string, id: string}
   *   The node bundle and '<entity type>:<id>' when the page renders a node,
   *   otherwise the route name and an empty id.
   */
  protected function page(): array {
    $node = $this->routeMatch->getParameter('node');

    if ($node instanceof NodeInterface && $node->id() !== NULL) {
      return ['type' => $node->bundle(), 'id' => 'node:' . $node->id()];
    }

    return ['type' => (string) $this->routeMatch->getRouteName(), 'id' => ''];
  }

}
