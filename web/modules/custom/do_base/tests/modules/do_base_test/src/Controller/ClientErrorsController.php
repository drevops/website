<?php

declare(strict_types=1);

namespace Drupal\do_base_test\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Answers Ajax requests with responses the browser cannot process.
 */
class ClientErrorsController {

  /**
   * Returns a server error.
   */
  public function serverError(): Response {
    return new Response('[TEST] Server error', Response::HTTP_INTERNAL_SERVER_ERROR, ['Content-Type' => 'text/plain']);
  }

  /**
   * Returns an HTML page where Ajax commands are expected.
   */
  public function notAjax(): Response {
    return new Response('<!DOCTYPE html><html><body>[TEST] Not an Ajax response</body></html>', Response::HTTP_OK, ['Content-Type' => 'text/html']);
  }

}
