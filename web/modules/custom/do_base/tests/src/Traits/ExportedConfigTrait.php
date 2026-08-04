<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Traits;

use Symfony\Component\Yaml\Yaml;

/**
 * Trait ExportedConfigTrait.
 *
 * Provides access to the configuration exported into the repository.
 *
 * @codeCoverageIgnore
 */
trait ExportedConfigTrait {

  /**
   * Reads an exported configuration file from the default config directory.
   */
  protected function loadConfig(string $file_name): array {
    $path = dirname($this->root) . '/config/default/' . $file_name;
    $this->assertFileExists($path);

    return Yaml::parseFile($path);
  }

  /**
   * Lists the bundle names of an entity type from its exported config files.
   */
  protected function loadBundleNames(string $config_prefix): array {
    $files = glob(dirname($this->root) . '/config/default/' . $config_prefix . '.*.yml') ?: [];
    $this->assertNotEmpty($files, sprintf('No exported bundles found for "%s".', $config_prefix));

    return array_map(static fn(string $file): string => substr(basename($file, '.yml'), strlen($config_prefix) + 1), $files);
  }

}
