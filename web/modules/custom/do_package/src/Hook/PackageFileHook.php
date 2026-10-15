<?php

declare(strict_types=1);

namespace Drupal\do_package\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\media\MediaInterface;

/**
 * Deletes package files as soon as no package revision references them.
 */
final class PackageFileHook {

  /**
   * IDs of the files held by packages being deleted, keyed by media ID.
   *
   * @var array<int|string, array<mixed>>
   */
  protected array $deletedPackageFileIds = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUsageInterface $fileUsage,
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_predelete() for media.
   */
  #[Hook('media_predelete')]
  public function mediaPredelete(MediaInterface $media): void {
    if ($media->bundle() !== PackageAccessHook::BUNDLE) {
      return;
    }

    // Revision field rows are deleted before hook_media_delete() runs.
    $this->deletedPackageFileIds[$media->id()] = $this->revisionFileIds($media);
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for media.
   */
  #[Hook('media_delete')]
  public function mediaDelete(MediaInterface $media): void {
    if ($media->bundle() !== PackageAccessHook::BUNDLE) {
      return;
    }

    $fids = $this->deletedPackageFileIds[$media->id()] ?? [];
    unset($this->deletedPackageFileIds[$media->id()]);

    $this->deleteUnusedFiles($media, $fids);
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for media.
   */
  #[Hook('media_update')]
  public function mediaUpdate(MediaInterface $media): void {
    $original = $media->getOriginal();

    if ($media->bundle() !== PackageAccessHook::BUNDLE || !$original instanceof MediaInterface) {
      return;
    }

    $this->deleteUnusedFiles($media, array_diff($this->fileIds($original), $this->fileIds($media)));
  }

  /**
   * Implements hook_ENTITY_TYPE_revision_delete() for media.
   */
  #[Hook('media_revision_delete')]
  public function mediaRevisionDelete(MediaInterface $media): void {
    if ($media->bundle() !== PackageAccessHook::BUNDLE) {
      return;
    }

    $this->deleteUnusedFiles($media, $this->fileIds($media));
  }

  /**
   * Deletes the files that no package revision or other entity still uses.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The package that held the files.
   * @param array<mixed> $fids
   *   IDs of the files the package held.
   */
  protected function deleteUnusedFiles(MediaInterface $media, array $fids): void {
    if ($fids === []) {
      return;
    }

    foreach ($this->entityTypeManager->getStorage('file')->loadMultiple($fids) as $file) {
      if ($file instanceof FileInterface && !$this->isReferenced($media, $file) && !$this->isUsedElsewhere($media, $file)) {
        $file->delete();
      }
    }
  }

  /**
   * Checks whether a revision of any package references the file.
   */
  protected function isReferenced(MediaInterface $media, FileInterface $file): bool {
    $query = $this->entityTypeManager->getStorage('media')->getQuery()->allRevisions()->accessCheck(FALSE);

    return $query->condition($this->sourceFieldName($media), $file->id())->range(0, 1)->execute() !== [];
  }

  /**
   * Checks whether an entity other than the package uses the file.
   */
  protected function isUsedElsewhere(MediaInterface $media, FileInterface $file): bool {
    $usage = $this->fileUsage->listUsage($file);
    // Usage by this package stays recorded for files of older revisions after
    // a delete, so the revision check replaces it.
    unset($usage['file']['media'][$media->id()]);

    foreach ($usage as $types) {
      if (array_filter($types) !== []) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns the IDs of the files any revision of the package references.
   *
   * @return array<mixed>
   *   The file IDs.
   */
  protected function revisionFileIds(MediaInterface $media): array {
    $storage = $this->entityTypeManager->getStorage('media');

    if (!$storage instanceof RevisionableStorageInterface) {
      // @codeCoverageIgnoreStart
      throw new \LogicException('The media storage is not revisionable.');
      // @codeCoverageIgnoreEnd
    }

    $revision_ids = $storage->getQuery()->allRevisions()->accessCheck(FALSE)->condition('mid', $media->id())->execute();
    $fids = [];

    foreach ($storage->loadMultipleRevisions(array_keys($revision_ids)) as $revision) {
      if ($revision instanceof MediaInterface) {
        $fids = [...$fids, ...$this->fileIds($revision)];
      }
    }

    return array_unique($fids);
  }

  /**
   * Returns the IDs of the files a package revision references.
   *
   * @return array<mixed>
   *   The file IDs.
   */
  protected function fileIds(MediaInterface $media): array {
    $fid = $media->getSource()->getSourceFieldValue($media);

    return $fid === NULL ? [] : [$fid];
  }

  /**
   * Returns the name of the field holding the package file.
   */
  protected function sourceFieldName(MediaInterface $media): string {
    return (string) $media->getSource()->getConfiguration()['source_field'];
  }

}
