<?php

declare(strict_types=1);

namespace Drupal\do_base;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Psr\Log\LoggerInterface;

/**
 * Derives policy hashes for the inline scripts of the navigation toolbar.
 *
 * The toolbar renders a script inline and gives it no nonce, so a content
 * security policy that has a nonce admits it by hash alone. A hash covers the
 * exact bytes of the script, which belong to core and change with it, so they
 * are read from the shipped template on demand instead of being recorded here.
 */
class NavigationScriptHash {

  /**
   * Cache id the derived hashes are stored under.
   */
  protected const string CID = 'do_base:navigation_script_hashes';

  /**
   * Module rendering the toolbar.
   */
  protected const string MODULE = 'navigation';

  /**
   * Template holding the toolbar markup, relative to the module.
   */
  protected const string TEMPLATE = 'layouts/navigation.html.twig';

  /**
   * Hashes already derived in this request.
   *
   * @var string[]|null
   */
  protected ?array $hashes = NULL;

  public function __construct(
    protected ModuleExtensionList $moduleList,
    protected CacheBackendInterface $cache,
    protected LoggerInterface $logger,
    protected string $appRoot,
  ) {
  }

  /**
   * Returns a hash source for each inline script the toolbar renders.
   *
   * @return string[]
   *   Sources in the "sha256-{base64}" form, empty when the template holds no
   *   script this can account for.
   */
  public function getHashes(): array {
    if ($this->hashes !== NULL) {
      return $this->hashes;
    }

    $cached = $this->cache->get(static::CID);

    if ($cached !== FALSE && is_array($cached->data)) {
      $this->hashes = $cached->data;

      return $this->hashes;
    }

    $this->hashes = $this->derive();
    // An empty result is cached as well, so a template this cannot read logs
    // once per cache lifetime rather than on every request.
    $this->cache->set(static::CID, $this->hashes);

    return $this->hashes;
  }

  /**
   * Reads the template and hashes each inline script it holds.
   *
   * @return string[]
   *   Sources in the "sha256-{base64}" form.
   */
  protected function derive(): array {
    if (!$this->moduleList->exists(static::MODULE)) {
      return [];
    }

    $path = $this->appRoot . '/' . $this->moduleList->getPath(static::MODULE) . '/' . static::TEMPLATE;

    if (!is_file($path) || !is_readable($path)) {
      $this->logger->warning('No policy hash could be derived for the navigation toolbar: @path is not readable. Its inline script will be blocked.', ['@path' => $path]);

      return [];
    }

    $template = (string) file_get_contents($path);

    if (!preg_match_all('#<script>(.*?)</script>#s', $template, $matches)) {
      $this->logger->warning('No policy hash could be derived for the navigation toolbar: @path holds no inline script this can read. Any script it does render will be blocked.', ['@path' => $path]);

      return [];
    }

    $hashes = [];

    foreach ($matches[1] as $script) {
      // A hash has to match the rendered bytes, which Twig syntax makes
      // unknowable from the template alone.
      if (str_contains($script, '{{') || str_contains($script, '{%')) {
        $this->logger->warning('An inline script of the navigation toolbar in @path carries Twig syntax, so no policy hash can be derived for it and it will be blocked.', ['@path' => $path]);

        continue;
      }

      $hashes[] = 'sha256-' . base64_encode(hash('sha256', $script, TRUE));
    }

    return $hashes;
  }

}
