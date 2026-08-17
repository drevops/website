<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Kernel;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\csp\Csp;
use Drupal\do_base\Hook\PageAttachmentsHook;
use Drupal\do_base\NavigationScriptHash;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests what is attached to every page of the site.
 *
 * The hook is driven directly with the route match set per case, so each page
 * it recognises is reachable without a rendered response.
 */
#[Group('do_base')]
#[RunTestsInSeparateProcesses]
class PageAttachmentsTest extends DoBaseKernelTestBase {

  /**
   * Machine name of the media type holding banner backgrounds.
   */
  protected const string MEDIA_TYPE = 'civictheme_image';

  /**
   * Image style the banner paints its background with.
   */
  protected const string IMAGE_STYLE = 'banner_background';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'media',
    'node',
    'csp',
    'do_base',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['field', 'system', 'image', 'media', 'node']);

    $style = ImageStyle::create(['name' => static::IMAGE_STYLE, 'label' => 'Banner background']);
    $style->addImageEffect([
      'id' => 'image_scale',
      'data' => ['width' => 1600],
    ]);
    $style->save();

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    $this->createImageMediaType();
    $this->createBannerBackgroundField();
  }

  /**
   * Tests that a nonce is asked for so core's inline scripts survive.
   */
  public function testNonceIsAskedFor(): void {
    // Prepare.
    $this->setRoute('entity.node.canonical');

    // Act.
    $attachments = $this->attach();

    // Assert.
    $this->assertSame([Csp::POLICY_UNSAFE_INLINE], $attachments['#attached']['csp_nonce']['script']);
    $this->assertContains('csp/nonce', $attachments['#attached']['library']);
  }

  /**
   * Tests that exactly one script hash is allowed, with a fallback source.
   */
  public function testInlineScriptIsAllowedByHash(): void {
    // Prepare.
    $this->setRoute('entity.node.canonical');

    // Act.
    $attachments = $this->attach();

    // Assert.
    $this->assertSame([[Csp::POLICY_UNSAFE_INLINE]], array_values($attachments['#attached']['csp_hash']['script-src-elem']));
  }

  /**
   * Tests that the hash allowed is the one for the script core renders.
   *
   * The hashes are derived from the shipped template, so this asserts that the
   * template core currently ships is still one the derivation can read. A core
   * release that restructures it fails here rather than only logging a warning.
   */
  public function testAllowedHashMatchesTheTemplate(): void {
    // Prepare.
    $this->setRoute('entity.node.canonical');
    $template = file_get_contents($this->root . '/core/modules/navigation/layouts/navigation.html.twig');

    // Act.
    $attachments = $this->attach();
    $found = preg_match('#<script>(.*?)</script>#s', (string) $template, $matches);

    // Assert.
    $this->assertSame(1, $found, 'The navigation layout is expected to render one inline script.');
    $this->assertSame(
      'sha256-' . base64_encode(hash('sha256', $matches[1], TRUE)),
      array_key_first($attachments['#attached']['csp_hash']['script-src-elem']),
    );
  }

  /**
   * Tests that a site without the policy module gets no policy attachments.
   */
  public function testSiteWithoutThePolicyModuleGetsNoPolicySources(): void {
    // Prepare.
    $this->setRoute('entity.node.canonical', ['node' => $this->createPage($this->createImageMedia())]);
    $modules = $this->createMock(ModuleHandlerInterface::class);
    $modules->method('moduleExists')->willReturn(FALSE);

    $hook = new PageAttachmentsHook(
      $this->container->get('current_route_match'),
      $this->container->get('entity_type.manager'),
      $modules,
      $this->container->get(NavigationScriptHash::class),
    );

    // Act.
    $attachments = [];
    $hook->attach($attachments);

    // Assert.
    $this->assertArrayHasKey('html_head_link', $attachments['#attached'], 'The rest of the hook is expected to run without the policy module.');
    $this->assertArrayNotHasKey('csp_nonce', $attachments['#attached']);
    $this->assertArrayNotHasKey('csp_hash', $attachments['#attached']);
  }

  /**
   * Tests that the banner background is preloaded on the pages showing it.
   *
   * @param string $route_name
   *   Name of the route being visited.
   * @param string $parameter
   *   Name of the route parameter holding the node.
   * @param array<string, mixed> $options
   *   Options the route carries.
   */
  #[DataProvider('dataProviderBannerBackgroundIsPreloaded')]
  public function testBannerBackgroundIsPreloaded(string $route_name, string $parameter, array $options = []): void {
    // Prepare.
    $this->setRoute($route_name, [$parameter => $this->createPage($this->createImageMedia())], $options);

    // Act.
    $attachments = $this->attach();

    // Assert.
    $this->assertSame([
      'rel' => 'preload',
      'as' => 'image',
      'fetchpriority' => 'high',
    ], array_diff_key($attachments['#attached']['html_head_link'][0][0], ['href' => NULL]));
    $this->assertStringContainsString('/styles/' . static::IMAGE_STYLE . '/', $attachments['#attached']['html_head_link'][0][0]['href']);
  }

  /**
   * Data provider for testBannerBackgroundIsPreloaded.
   */
  public static function dataProviderBannerBackgroundIsPreloaded(): \Iterator {
    yield 'the node page' => ['entity.node.canonical', 'node'];
    yield 'an older revision' => ['entity.node.revision', 'node_revision'];
    yield 'the latest draft' => ['entity.node.latest_version', 'node'];
    yield 'a preview link' => ['entity.node.preview_link', 'node', ['_preview_link_route' => TRUE]];
  }

  /**
   * Tests that no preload is emitted on pages that draw no banner.
   *
   * @param string $route_name
   *   Name of the route being visited.
   * @param string|null $parameter
   *   Name of the route parameter holding the node, or NULL for a route
   *   carrying no node at all.
   */
  #[DataProvider('dataProviderBannerBackgroundIsNotPreloaded')]
  public function testBannerBackgroundIsNotPreloaded(string $route_name, ?string $parameter): void {
    // Prepare.
    $parameters = $parameter === NULL ? [] : [$parameter => $this->createPage($this->createImageMedia())];
    $this->setRoute($route_name, $parameters);

    // Act.
    $attachments = $this->attach();

    // Assert.
    $this->assertArrayNotHasKey('html_head_link', $attachments['#attached']);
  }

  /**
   * Data provider for testBannerBackgroundIsNotPreloaded.
   */
  public static function dataProviderBannerBackgroundIsNotPreloaded(): \Iterator {
    yield 'the edit form' => ['entity.node.edit_form', 'node'];
    yield 'the delete form' => ['entity.node.delete_form', 'node'];
    yield 'the revision list' => ['entity.node.version_history', 'node'];
    yield 'a devel tab' => ['entity.node.devel_load', 'node'];
    yield 'a page with no node behind it' => ['system.admin_content', NULL];
  }

  /**
   * Tests that a page with no background of its own preloads nothing.
   */
  public function testPageWithoutBackgroundPreloadsNothing(): void {
    // Prepare.
    $this->setRoute('entity.node.canonical', ['node' => $this->createPage()]);

    // Act.
    $attachments = $this->attach();

    // Assert.
    $this->assertArrayNotHasKey('html_head_link', $attachments['#attached']);
  }

  /**
   * Runs the hook over an empty set of attachments.
   *
   * The hook is taken from the container, so the wiring it is registered with
   * is asserted alongside its behaviour.
   *
   * @return array<string, array<string, mixed>>
   *   The attachments the hook has added to.
   */
  protected function attach(): array {
    $hook = $this->container->get(PageAttachmentsHook::class);

    if (!$hook instanceof PageAttachmentsHook) {
      throw new \UnexpectedValueException('The hook is expected to be registered as a service.');
    }

    $attachments = [];
    $hook->attach($attachments);

    return $attachments;
  }

  /**
   * Puts a request for the given route on the stack.
   *
   * @param string $route_name
   *   Name of the route being visited.
   * @param array<string, mixed> $parameters
   *   Upcast route parameters, keyed by name.
   * @param array<string, mixed> $options
   *   Options the route carries.
   */
  protected function setRoute(string $route_name, array $parameters = [], array $options = []): void {
    // A route match only carries the parameters its path declares.
    $path = '/test' . implode('', array_map(static fn(string $name): string => '/{' . $name . '}', array_keys($parameters)));

    $route = new Route($path);
    foreach ($options as $name => $value) {
      $route->setOption($name, $value);
    }

    $request = Request::create($path);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, $route_name);
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);
    foreach ($parameters as $name => $value) {
      $request->attributes->set($name, $value);
    }

    $requests = $this->container->get('request_stack');
    $current = $requests->getCurrentRequest();
    // The kernel started a session on the request it booted with, and the test
    // base clears that session on the current request as it tears down.
    if ($current !== NULL && $current->hasSession()) {
      $request->setSession($current->getSession());
    }

    $requests->push($request);
  }

  /**
   * Creates a page, optionally carrying a banner background.
   */
  protected function createPage(?MediaInterface $background = NULL): NodeInterface {
    $node = Node::create([
      'type' => 'page',
      'title' => '[TEST] Page',
      'field_c_n_banner_background' => $background instanceof MediaInterface ? ['target_id' => $background->id()] : NULL,
    ]);
    $node->save();

    return $node;
  }

  /**
   * Creates a media item wrapping an image file.
   */
  protected function createImageMedia(): MediaInterface {
    $media = Media::create([
      'bundle' => static::MEDIA_TYPE,
      'name' => '[TEST] Banner Background',
      'field_c_m_image' => ['target_id' => $this->createImageFile()->id(), 'alt' => '[TEST] Background'],
    ]);
    $media->save();

    return $media;
  }

  /**
   * Creates a file entity around a copy of a core fixture.
   */
  protected function createImageFile(): FileInterface {
    $uri = 'public://' . $this->randomMachineName() . '.png';
    file_put_contents($uri, (string) file_get_contents($this->root . '/core/tests/fixtures/files/image-1.png'));

    $file = File::create(['uri' => $uri]);
    $file->setPermanent();
    $file->save();

    return $file;
  }

  /**
   * Creates the media type banner backgrounds live in.
   */
  protected function createImageMediaType(): void {
    $media_type = MediaType::create([
      'id' => static::MEDIA_TYPE,
      'label' => 'Image',
      'source' => 'image',
    ]);
    $media_type->save();

    FieldStorageConfig::create([
      'entity_type' => 'media',
      'field_name' => 'field_c_m_image',
      'type' => 'image',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'media',
      'bundle' => static::MEDIA_TYPE,
      'field_name' => 'field_c_m_image',
      'label' => 'Image',
      'settings' => ['alt_field' => TRUE, 'file_extensions' => 'png jpg svg'],
    ])->save();

    $media_type->set('source_configuration', ['source_field' => 'field_c_m_image'])->save();
  }

  /**
   * Adds the banner background field pages carry.
   */
  protected function createBannerBackgroundField(): void {
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_c_n_banner_background',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'media'],
    ])->save();

    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'field_c_n_banner_background',
      'label' => 'Banner background',
      'settings' => [
        'handler' => 'default:media',
        'handler_settings' => ['target_bundles' => [static::MEDIA_TYPE => static::MEDIA_TYPE]],
      ],
    ])->save();
  }

}
