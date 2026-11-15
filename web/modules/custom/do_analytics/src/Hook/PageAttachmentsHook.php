<?php

declare(strict_types=1);

namespace Drupal\do_analytics\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;

/**
 * Attaches Axel and its settings to every front-end page.
 */
final class PageAttachmentsHook {

  /**
   * Axel event types to track.
   */
  private const array TYPES = ['click', 'form_start', 'field_complete', 'form_submit'];

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

    $attachments['#attached']['library'][] = 'do_analytics/axel';
    $attachments['#attached']['drupalSettings']['axel'] = [
      'types' => self::TYPES,
      'properties' => $this->page(),
    ];
    $attachments['#cache']['contexts'][] = 'route';
  }

  /**
   * Describes the current page as parameters added to every event.
   *
   * @return array<string, string>
   *   The node bundle as 'page_type' and '<entity type>:<id>' as 'page_id'
   *   when the page renders a node, otherwise the route name as 'page_type'.
   */
  protected function page(): array {
    $node = $this->routeMatch->getParameter('node');

    if ($node instanceof NodeInterface && $node->id() !== NULL) {
      return ['page_type' => $node->bundle(), 'page_id' => 'node:' . $node->id()];
    }

    return array_filter(['page_type' => (string) $this->routeMatch->getRouteName()]);
  }

}
