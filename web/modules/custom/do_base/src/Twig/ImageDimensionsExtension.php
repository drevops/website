<?php

declare(strict_types=1);

namespace Drupal\do_base\Twig;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\image\ImageStyleInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Resolves the intrinsic dimensions of an image URL for templates.
 *
 * Images are rendered by including a component from Twig rather than through a
 * render element, so there is no preprocess step in which to work these out.
 */
final class ImageDimensionsExtension extends AbstractExtension {

  public function __construct(
    private readonly ImageFactory $imageFactory,
    private readonly StreamWrapperManagerInterface $streamWrapperManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly string $appRoot,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('do_image_dimensions', [$this, 'dimensions']),
    ];
  }

  /**
   * Resolves the rendered dimensions of an image URL.
   *
   * @param string|null $url
   *   Absolute or root-relative URL of the image.
   *
   * @return array
   *   Associative array of 'width' and 'height', empty when the file cannot be
   *   read as an image.
   */
  public function dimensions(?string $url): array {
    if ($url === NULL || $url === '') {
      return [];
    }

    $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
    if ($path === '') {
      return [];
    }

    $wrapper = $this->streamWrapperManager->getViaScheme('public');
    $public_path = $wrapper instanceof PublicStream ? $wrapper->getDirectoryPath() : '';

    if ($public_path !== '' && str_starts_with($path, $public_path . '/')) {
      return $this->managedFile(urldecode(substr($path, strlen($public_path) + 1)));
    }

    return $this->vector($path);
  }

  /**
   * Resolves dimensions for a file in the public files directory.
   *
   * Dimensions are calculated from the source file and the style's own
   * transform rather than measured on the derivative, so they are correct even
   * on the request that generates that derivative for the first time.
   *
   * @param string $relative
   *   Path relative to the public files directory.
   *
   * @return array
   *   Associative array of 'width' and 'height', or empty.
   */
  protected function managedFile(string $relative): array {
    $style = NULL;

    if (preg_match('~^styles/([^/]+)/[^/]+/(.+)$~', $relative, $matches) === 1) {
      $style = $this->entityTypeManager->getStorage('image_style')->load($matches[1]);
      $relative = $matches[2];
    }

    // A convert effect appends its own extension to the source file name, so a
    // derivative path is not always a source path.
    $candidates = [$relative];
    $trimmed = preg_replace('~\.[a-z0-9]+$~i', '', $relative);
    if (!empty($trimmed) && $trimmed !== $relative) {
      $candidates[] = $trimmed;
    }

    foreach ($candidates as $candidate) {
      $uri = 'public://' . $candidate;
      $image = $this->imageFactory->get($uri);

      if (!$image->isValid()) {
        continue;
      }

      $dimensions = ['width' => $image->getWidth(), 'height' => $image->getHeight()];

      if ($style instanceof ImageStyleInterface) {
        $style->transformDimensions($dimensions, $uri);
      }

      if (empty($dimensions['width']) || empty($dimensions['height'])) {
        return [];
      }

      return $dimensions;
    }

    return [];
  }

  /**
   * Resolves dimensions declared inside an SVG shipped with an extension.
   *
   * No image toolkit can measure a vector, so the ratio is read from the
   * markup. Only the ratio matters here: the rendered size comes from CSS, and
   * the attributes exist so the browser can reserve the right shape.
   *
   * @param string $path
   *   Path relative to the web root.
   *
   * @return array
   *   Associative array of 'width' and 'height', or empty.
   */
  protected function vector(string $path): array {
    if (!str_ends_with(strtolower($path), '.svg')) {
      return [];
    }

    $file = $this->appRoot . '/' . $path;
    if (!is_file($file) || !is_readable($file)) {
      return [];
    }

    $markup = file_get_contents($file, FALSE, NULL, 0, 2048);
    if ($markup === FALSE) {
      return [];
    }

    if (preg_match('~viewBox\s*=\s*["\']\s*[\d.+-]+[,\s]+[\d.+-]+[,\s]+([\d.]+)[,\s]+([\d.]+)~i', $markup, $matches) !== 1) {
      return [];
    }

    $width = (int) round((float) $matches[1]);
    $height = (int) round((float) $matches[2]);

    if ($width <= 0 || $height <= 0) {
      return [];
    }

    return ['width' => $width, 'height' => $height];
  }

}
