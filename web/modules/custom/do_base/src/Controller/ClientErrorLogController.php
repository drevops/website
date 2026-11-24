<?php

declare(strict_types=1);

namespace Drupal\do_base\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writes errors reported by the browser to the site log.
 */
class ClientErrorLogController extends ControllerBase {

  /**
   * Longest value kept for the message, which carries an Ajax response body.
   */
  protected const int MESSAGE_LENGTH = 4000;

  /**
   * Longest value kept for every other text field.
   */
  protected const int FIELD_LENGTH = 1000;

  public function __construct(
    #[Autowire(service: 'logger.channel.javascript')]
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Logs one browser error.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request carrying the error as a JSON object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An empty response, or a 400 response when the body is not an object
   *   with a message.
   */
  public function log(Request $request): Response {
    $report = json_decode($request->getContent(), TRUE);

    if (!is_array($report) || $this->text($report, 'message', static::MESSAGE_LENGTH) === '') {
      return new Response('', Response::HTTP_BAD_REQUEST);
    }

    $this->logger->error('Browser @kind on @url<br /><pre>@name: @message</pre>Source: @source:@line:@column<br />User agent: @agent<br /><pre>@stack</pre>', [
      '@kind' => $this->text($report, 'kind'),
      '@url' => $this->text($report, 'url'),
      '@name' => $this->text($report, 'name') ?: 'Error',
      '@message' => $this->text($report, 'message', static::MESSAGE_LENGTH),
      '@source' => $this->text($report, 'source'),
      '@line' => (int) ($report['line'] ?? 0),
      '@column' => (int) ($report['column'] ?? 0),
      '@agent' => Unicode::truncate((string) $request->headers->get('User-Agent', ''), static::FIELD_LENGTH),
      '@stack' => $this->text($report, 'stack'),
    ]);

    return new Response('', Response::HTTP_NO_CONTENT);
  }

  /**
   * Reads a string field from the report, cut to a maximum length.
   *
   * @param array<mixed> $report
   *   The decoded report.
   * @param string $key
   *   Name of the field.
   * @param int $length
   *   Longest value kept.
   *
   * @return string
   *   The value, or an empty string when the field is missing or not scalar.
   */
  protected function text(array $report, string $key, int $length = self::FIELD_LENGTH): string {
    $value = $report[$key] ?? '';

    return is_scalar($value) ? Unicode::truncate(trim((string) $value), $length) : '';
  }

}
