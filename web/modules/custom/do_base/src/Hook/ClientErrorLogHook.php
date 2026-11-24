<?php

declare(strict_types=1);

namespace Drupal\do_base\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Reports browser errors on administration pages to the site log.
 */
final class ClientErrorLogHook {

  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected AdminContext $adminContext,
    protected AccountInterface $currentUser,
  ) {}

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function attach(array &$attachments): void {
    $attachments['#cache']['contexts'][] = 'user.roles:authenticated';
    $attachments['#cache']['contexts'][] = 'route';

    // The logging endpoint only accepts reports from logged-in users.
    if ($this->currentUser->isAnonymous() || !$this->adminContext->isAdminRoute($this->routeMatch->getRouteObject())) {
      return;
    }

    $attachments['#attached']['library'][] = 'do_base/client_error_log';
  }

}
