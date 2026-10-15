<?php

declare(strict_types=1);

namespace Drupal\Tests\do_package\Kernel;

use Drupal\Core\File\FileSystemInterface;
use Drupal\do_package\Hook\PackageAccessHook;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Provides a package media type storing its files on the private scheme.
 */
abstract class PackageKernelTestBase extends KernelTestBase {

  use MediaTypeCreationTrait;
  use UserCreationTrait;

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
    'do_package',
  ];

  /**
   * The name of the field holding the package file.
   */
  protected string $packageField;

  /**
   * {@inheritdoc}
   */
  protected function setUpFilesystem(): void {
    parent::setUpFilesystem();

    // The private stream wrapper is registered only when this setting exists
    // as the container is built.
    mkdir($this->siteDirectory . '/private', 0775);
    $this->setSetting('file_private_path', $this->siteDirectory . '/private');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['field', 'system', 'user', 'image', 'media']);

    // Reserve uid 1; the superuser bypasses permission checks, so every
    // audience in a test must be a regular account.
    $this->createUser();

    $package_type = $this->createMediaType('file', ['id' => PackageAccessHook::BUNDLE, 'label' => 'Package']);
    $this->packageField = $this->sourceFieldName($package_type);
    $package_storage = FieldStorageConfig::loadByName('media', $this->packageField);
    $this->assertInstanceOf(FieldStorageConfig::class, $package_storage);
    $package_storage->setSetting('uri_scheme', 'private')->save();
  }

  /**
   * Creates a published package holding the given file.
   */
  protected function createPackage(FileInterface $file): MediaInterface {
    $package = Media::create(['bundle' => PackageAccessHook::BUNDLE, 'name' => '[TEST] Package', $this->packageField => $file->id(), 'uid' => 1]);
    $package->save();

    return $package;
  }

  /**
   * Creates a permanent file entity with content at the given URI.
   */
  protected function createFile(string $uri): FileInterface {
    $directory = dirname($uri);
    $this->container->get('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    file_put_contents($uri, '[TEST] content');

    $file = File::create(['uri' => $uri, 'uid' => 1]);
    $file->setPermanent();
    $file->save();

    return $file;
  }

  /**
   * Returns the name of a media type's source field.
   */
  protected function sourceFieldName(MediaTypeInterface $media_type): string {
    return $media_type->getSource()->getSourceFieldDefinition($media_type)->getName();
  }

}
