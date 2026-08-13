<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\do_base\NavigationScriptHash;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;

/**
 * Tests the policy hashes derived for the navigation toolbar.
 *
 * Each case runs against a fixture template, so the shapes core could ship are
 * reachable without waiting for core to ship them. That the real template still
 * yields a hash is asserted in PageAttachmentsTest.
 */
#[Group('do_base')]
class NavigationScriptHashTest extends DoBaseUnitTestBase {

  /**
   * Hash of the single inline script in the one-script fixture.
   */
  protected const string FIXTURE_HASH = 'sha256-QqG8njF9OO0E/DqB83tb/tPrgXeKY3UpxJUtfSDJrfE=';

  /**
   * Warnings the logger was handed.
   *
   * @var string[]
   */
  protected array $warnings = [];

  /**
   * Values written to the cache, keyed by cache id.
   *
   * @var array<string, mixed>
   */
  protected array $written = [];

  /**
   * Number of times the module path was resolved.
   */
  protected int $lookups = 0;

  /**
   * Tests that the hash of the template's inline script is returned.
   */
  public function testHashIsDerivedFromTheTemplate(): void {
    // Prepare.
    $service = $this->service('nav_one_script');

    // Act.
    $hashes = $service->getHashes();

    // Assert.
    $this->assertSame([static::FIXTURE_HASH], $hashes);
    $this->assertSame([], $this->warnings);
  }

  /**
   * Tests that a template holding several scripts yields a hash for each.
   */
  public function testEveryInlineScriptIsHashed(): void {
    // Prepare.
    $service = $this->service('nav_two_scripts');

    // Act.
    $hashes = $service->getHashes();

    // Assert.
    $this->assertSame([
      static::FIXTURE_HASH,
      'sha256-HnXEzlQbnSQv+1FZ26Ok9doVoToGeqg+vJ5anDdB8FQ=',
    ], $hashes);
    $this->assertSame([], $this->warnings);
  }

  /**
   * Tests that a template this cannot read is reported and yields nothing.
   *
   * @param string $fixture
   *   Directory the template is looked for in.
   * @param string $expected
   *   Text the logged warning is expected to carry.
   */
  #[DataProvider('dataProviderUnusableTemplateIsReported')]
  public function testUnusableTemplateIsReported(string $fixture, string $expected): void {
    // Prepare.
    $service = $this->service($fixture);

    // Act.
    $hashes = $service->getHashes();

    // Assert.
    $this->assertSame([], $hashes);
    $this->assertCount(1, $this->warnings);
    $this->assertStringContainsString($expected, $this->warnings[0]);
  }

  /**
   * Data provider for testUnusableTemplateIsReported.
   */
  public static function dataProviderUnusableTemplateIsReported(): \Iterator {
    yield 'the template is not there' => ['nav_absent', 'is not readable'];
    yield 'the template holds no script' => ['nav_no_script', 'holds no inline script'];
    yield 'the script is built by Twig' => ['nav_twig_script', 'carries Twig syntax'];
  }

  /**
   * Tests that a site without the toolbar module is not reported.
   *
   * Nothing renders the script there, so there is nothing to allow and nothing
   * worth telling an administrator about.
   */
  public function testAbsentModuleIsNotReported(): void {
    // Prepare.
    $service = $this->service('nav_one_script', module_exists: FALSE);

    // Act.
    $hashes = $service->getHashes();

    // Assert.
    $this->assertSame([], $hashes);
    $this->assertSame([], $this->warnings);
  }

  /**
   * Tests that derived hashes are handed to the cache.
   */
  public function testDerivedHashesAreCached(): void {
    // Prepare.
    $service = $this->service('nav_one_script');

    // Act.
    $service->getHashes();

    // Assert.
    $this->assertSame(['do_base:navigation_script_hashes' => [static::FIXTURE_HASH]], $this->written);
  }

  /**
   * Tests that a template this cannot read is cached as well.
   *
   * Without it, an unreadable template would log on every request.
   */
  public function testUnusableTemplateIsCached(): void {
    // Prepare.
    $service = $this->service('nav_absent');

    // Act.
    $service->getHashes();

    // Assert.
    $this->assertSame(['do_base:navigation_script_hashes' => []], $this->written);
  }

  /**
   * Tests that cached hashes are used without reading the template again.
   */
  public function testCachedHashesAreUsed(): void {
    // Prepare.
    $service = $this->service('nav_one_script', cached: ['sha256-cached']);

    // Act.
    $hashes = $service->getHashes();

    // Assert.
    $this->assertSame(['sha256-cached'], $hashes);
    $this->assertSame(0, $this->lookups, 'The template is not expected to be located when the cache holds hashes.');
    $this->assertSame([], $this->written);
  }

  /**
   * Tests that a cache entry holding something else is passed over.
   */
  public function testUnusableCacheEntryIsPassedOver(): void {
    // Prepare.
    $service = $this->service('nav_one_script', cached: 'not an array');

    // Act.
    $hashes = $service->getHashes();

    // Assert.
    $this->assertSame([static::FIXTURE_HASH], $hashes);
  }

  /**
   * Tests that the template is read once however often hashes are asked for.
   */
  public function testTemplateIsReadOncePerRequest(): void {
    // Prepare.
    $service = $this->service('nav_one_script');

    // Act.
    $service->getHashes();
    $service->getHashes();
    $service->getHashes();

    // Assert.
    $this->assertSame(1, $this->lookups);
  }

  /**
   * Builds the service under test.
   *
   * @param string $fixture
   *   Directory under tests/fixtures standing in for the module directory.
   * @param bool $module_exists
   *   Whether the module rendering the toolbar is installed.
   * @param mixed $cached
   *   Data a cache hit returns, or NULL for a cache miss.
   */
  protected function service(string $fixture, bool $module_exists = TRUE, mixed $cached = NULL): NavigationScriptHash {
    $modules = $this->createMock(ModuleExtensionList::class);
    $modules->method('exists')->willReturn($module_exists);
    $modules->method('getPath')->willReturnCallback(function () use ($fixture): string {
      $this->lookups++;

      return $fixture;
    });

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn($cached === NULL ? FALSE : (object) ['data' => $cached]);
    $cache->method('set')->willReturnCallback(function (string $cid, mixed $data): void {
      $this->written[$cid] = $data;
    });

    $logger = $this->createMock(LoggerInterface::class);
    $logger->method('warning')->willReturnCallback(function (string $message): void {
      $this->warnings[] = $message;
    });

    return new NavigationScriptHash($modules, $cache, $logger, __DIR__ . '/../../fixtures');
  }

}
