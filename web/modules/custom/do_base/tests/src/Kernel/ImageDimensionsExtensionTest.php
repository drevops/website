<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Kernel;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\do_base\Twig\ImageDimensionsExtension;
use Drupal\image\Entity\ImageStyle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the dimensions resolved for a rendered image.
 */
#[Group('do_base')]
class ImageDimensionsExtensionTest extends DoBaseKernelTestBase {

  /**
   * Dimensions of the source image written for each test.
   */
  protected const SOURCE_WIDTH = 800;
  protected const SOURCE_HEIGHT = 400;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'file',
    'image',
    'do_base',
  ];

  /**
   * The extension under test.
   */
  protected ImageDimensionsExtension $extension;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installConfig(['system', 'image']);

    $this->extension = $this->container->get('do_base.twig.image_dimensions');

    $directory = 'public://do_test';
    $this->container->get('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $this->writeImage($directory . '/source.png');
  }

  /**
   * Tests that an unprocessed image reports the dimensions of the file.
   */
  public function testOriginalReportsItsOwnDimensions(): void {
    // Act.
    $dimensions = $this->extension->dimensions($this->publicUrl('do_test/source.png'));

    // Assert.
    $this->assertSame(['width' => static::SOURCE_WIDTH, 'height' => static::SOURCE_HEIGHT], $dimensions);
  }

  /**
   * Tests that a derivative reports the dimensions the style produces.
   */
  public function testDerivativeReportsTheStyleDimensions(): void {
    // Prepare.
    $this->createScaleStyle('do_test_scale', 400);

    // Act.
    $dimensions = $this->extension->dimensions($this->publicUrl('styles/do_test_scale/public/do_test/source.png'));

    // Assert.
    $this->assertSame(['width' => 400, 'height' => 200], $dimensions);
  }

  /**
   * Tests that a converted derivative is traced back to its source file.
   *
   * A convert effect appends its extension to the source file name, so the
   * derivative path is not a path any file sits at.
   */
  public function testConvertedDerivativeIsTracedToItsSource(): void {
    // Prepare.
    $this->createScaleStyle('do_test_convert', 200);

    // Act.
    $dimensions = $this->extension->dimensions($this->publicUrl('styles/do_test_convert/public/do_test/source.png.webp'));

    // Assert.
    $this->assertSame(['width' => 200, 'height' => 100], $dimensions);
  }

  /**
   * Tests that a URL carrying a query string is still resolved.
   */
  public function testQueryStringIsIgnored(): void {
    // Act.
    $dimensions = $this->extension->dimensions($this->publicUrl('do_test/source.png') . '?itok=abc123');

    // Assert.
    $this->assertSame(['width' => static::SOURCE_WIDTH, 'height' => static::SOURCE_HEIGHT], $dimensions);
  }

  /**
   * Tests that a vector reports the ratio declared in its markup.
   */
  public function testVectorReportsItsViewBox(): void {
    // Prepare.
    $path = 'do_test_vector.svg';
    $file = \Drupal::root() . '/' . $path;
    file_put_contents($file, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 60"></svg>');

    // Act.
    $dimensions = $this->extension->dimensions('/' . $path);

    // Assert.
    $this->assertSame(['width' => 120, 'height' => 60], $dimensions);

    unlink($file);
  }

  /**
   * Tests that a URL nothing can be measured from yields no dimensions.
   */
  #[DataProvider('dataProviderUnmeasurableUrlYieldsNothing')]
  public function testUnmeasurableUrlYieldsNothing(?string $url): void {
    // Act.
    $dimensions = $this->extension->dimensions($url);

    // Assert.
    $this->assertSame([], $dimensions);
  }

  /**
   * Data provider for testUnmeasurableUrlYieldsNothing.
   */
  public static function dataProviderUnmeasurableUrlYieldsNothing(): \Iterator {
    yield 'null' => [NULL];
    yield 'empty' => [''];
    yield 'no path' => ['https://example.com'];
    yield 'missing vector' => ['/themes/custom/absent.svg'];
  }

  /**
   * Tests that a managed file that cannot be read yields no dimensions.
   */
  #[DataProvider('dataProviderUnreadableManagedFileYieldsNothing')]
  public function testUnreadableManagedFileYieldsNothing(string $relative): void {
    // Act.
    $dimensions = $this->extension->dimensions($this->publicUrl($relative));

    // Assert.
    $this->assertSame([], $dimensions);
  }

  /**
   * Data provider for testUnreadableManagedFileYieldsNothing.
   */
  public static function dataProviderUnreadableManagedFileYieldsNothing(): \Iterator {
    yield 'absent file' => ['do_test/absent.png'];
    yield 'absent derivative source' => ['styles/do_test_scale/public/do_test/absent.png'];
  }

  /**
   * Returns the URL a file in the public directory is served at.
   */
  protected function publicUrl(string $relative): string {
    $wrapper = $this->container->get('stream_wrapper_manager')->getViaScheme('public');
    $this->assertInstanceOf(PublicStream::class, $wrapper);

    return '/' . $wrapper->getDirectoryPath() . '/' . $relative;
  }

  /**
   * Writes a PNG of known dimensions.
   */
  protected function writeImage(string $uri): void {
    $path = $this->container->get('file_system')->realpath($uri);
    $this->assertIsString($path);

    $resource = imagecreatetruecolor(static::SOURCE_WIDTH, static::SOURCE_HEIGHT);
    imagepng($resource, $path);
    imagedestroy($resource);
  }

  /**
   * Creates an image style that scales to a given width.
   */
  protected function createScaleStyle(string $name, int $width): void {
    $style = ImageStyle::create(['name' => $name, 'label' => $name]);
    $style->addImageEffect([
      'id' => 'image_scale',
      'data' => ['width' => $width, 'height' => NULL, 'upscale' => FALSE],
    ]);
    $style->save();
  }

}
