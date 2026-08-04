<?php

declare(strict_types=1);

namespace Drupal\Tests\do_generated_content\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\do_generated_content\Generator\ComponentGenerator;
use Drupal\do_generated_content\Generator\NodeGeneratorBase;
use Drupal\generated_content\Attribute\GeneratedContent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests that the generators cover everything the site's configuration allows.
 *
 * A component type added to a field, or a state added to the workflow, would
 * otherwise go ungenerated indefinitely: the site still looks populated, so
 * nothing reveals the gap.
 */
#[Group('DoGeneratedContent')]
class ComponentCoverageTest extends UnitTestCase {

  /**
   * Tests that every bundle a component field accepts can be generated.
   */
  #[DataProvider('dataProviderComponentFieldBundlesAreSupported')]
  public function testComponentFieldBundlesAreSupported(string $config_name): void {
    $target_bundles = array_keys($this->fieldSetting($config_name, 'target_bundles'));

    $this->assertNotEmpty($target_bundles, sprintf('%s declares target bundles.', $config_name));

    $unsupported = array_diff($target_bundles, ComponentGenerator::supportedBundles());

    $this->assertSame([], array_values($unsupported), sprintf('ComponentGenerator can build every bundle %s accepts.', $config_name));
  }

  /**
   * Data provider for testComponentFieldBundlesAreSupported().
   *
   * Derived from the exported configuration rather than listed by hand, so a
   * paragraph-reference field added later is covered without editing the test.
   * Scoped to node and paragraph fields: those are what generation populates,
   * and a paragraph field on any other entity type is outside its remit.
   */
  public static function dataProviderComponentFieldBundlesAreSupported(): \Iterator {
    $directory = dirname(__DIR__, 7) . '/config/default';

    $paragraph_types = [];

    foreach (glob($directory . '/paragraphs.paragraphs_type.*.yml') ?: [] as $file) {
      $paragraph_types[] = substr(basename($file, '.yml'), strlen('paragraphs.paragraphs_type.'));
    }

    foreach (['node', 'paragraph'] as $entity_type) {
      foreach (glob($directory . '/field.field.' . $entity_type . '.*.yml') ?: [] as $file) {
        $config = (array) Yaml::parseFile($file);

        $target_bundles = array_keys($config['settings']['handler_settings']['target_bundles'] ?? []);

        if (array_intersect($target_bundles, $paragraph_types) === []) {
          continue;
        }

        $name = basename($file, '.yml');

        yield $name => [$name];
      }
    }
  }

  /**
   * Tests that every supported bundle is a paragraph type the site has.
   */
  public function testSupportedBundlesExist(): void {
    $missing = array_filter(
      ComponentGenerator::supportedBundles(),
      fn(string $bundle): bool => !file_exists($this->configPath('paragraphs.paragraphs_type.' . $bundle))
    );

    $this->assertSame([], array_values($missing), 'Every supported bundle is a paragraph type in the exported configuration.');
  }

  /**
   * Tests that the supported bundle list carries no duplicates.
   */
  public function testSupportedBundlesAreUnique(): void {
    $bundles = ComponentGenerator::supportedBundles();

    $this->assertSame($bundles, array_values(array_unique($bundles)));
  }

  /**
   * Tests that a run covers every moderation state the workflow declares.
   */
  public function testModerationStatesAreCovered(): void {
    $workflow = $this->config('workflows.workflow.civictheme_editorial');

    $states = array_keys($workflow['type_settings']['states'] ?? []);

    $this->assertNotEmpty($states);

    $uncovered = array_diff($states, NodeGeneratorBase::MODERATION_STATES);

    $this->assertSame([], array_values($uncovered), 'Every workflow state is produced by a generation run.');
  }

  /**
   * Tests that the generators cover every content type the workflow moderates.
   */
  public function testEveryModeratedContentTypeIsGenerated(): void {
    $workflow = $this->config('workflows.workflow.civictheme_editorial');

    $bundles = $workflow['type_settings']['entity_types']['node'] ?? [];

    $this->assertNotEmpty($bundles);

    $plugin_dir = dirname(__DIR__, 3) . '/src/Plugin/GeneratedContent';

    $generated = [];

    foreach (glob($plugin_dir . '/Node*.php') ?: [] as $file) {
      /** @var class-string $class */
      $class = 'Drupal\\do_generated_content\\Plugin\\GeneratedContent\\' . basename($file, '.php');

      foreach (new \ReflectionClass($class)->getAttributes(GeneratedContent::class) as $attribute) {
        $generated[] = $attribute->newInstance()->bundle;
      }
    }

    $this->assertSame([], array_values(array_diff($bundles, $generated)), 'Every moderated content type has a node generator.');
  }

  /**
   * Read a handler setting from an exported field configuration.
   */
  protected function fieldSetting(string $config_name, string $setting): array {
    return $this->config($config_name)['settings']['handler_settings'][$setting] ?? [];
  }

  /**
   * Parse an exported configuration file.
   */
  protected function config(string $config_name): array {
    $path = $this->configPath($config_name);

    $this->assertFileExists($path);

    return (array) Yaml::parseFile($path);
  }

  /**
   * Build the path to an exported configuration file.
   */
  protected function configPath(string $config_name): string {
    return dirname(__DIR__, 7) . '/config/default/' . $config_name . '.yml';
  }

}
