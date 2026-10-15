<?php

declare(strict_types=1);

namespace Drupal\Tests\do_package\Kernel;

use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\do_package\Hook\PackageAccessHook;
use Drupal\do_package\Hook\PackageFileHook;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that package files are deleted once no package references them.
 */
#[CoversClass(PackageFileHook::class)]
#[Group('do_package')]
#[RunTestsInSeparateProcesses]
class PackageFileTest extends PackageKernelTestBase {

  /**
   * Tests that deleting a package deletes the files of all its revisions.
   */
  public function testDeleteRemovesFilesOfEveryRevision(): void {
    // Prepare.
    $first = $this->createFile(PackageAccessHook::DIRECTORY . 'first.zip');
    $second = $this->createFile(PackageAccessHook::DIRECTORY . 'second.zip');
    $package = $this->createPackage($first);
    $this->replaceFile($package, $second, TRUE);

    // Act.
    $package->delete();

    // Assert.
    $this->assertFileRemoved($first);
    $this->assertFileRemoved($second);
  }

  /**
   * Tests that deleting a package keeps a file something else still uses.
   */
  #[DataProvider('dataProviderDeleteKeepsFileInUse')]
  public function testDeleteKeepsFileInUse(string $other_use): void {
    // Prepare.
    $file = $this->createFile(PackageAccessHook::DIRECTORY . 'shared.zip');
    $package = $this->createPackage($file);

    if ($other_use === 'package') {
      $this->createPackage($file);
    }
    else {
      $this->container->get('file.usage')->add($file, 'editor', 'node', '1');
    }

    // Act.
    $package->delete();

    // Assert.
    $this->assertFileKept($file);
  }

  /**
   * Data provider for testDeleteKeepsFileInUse().
   */
  public static function dataProviderDeleteKeepsFileInUse(): \Iterator {
    yield 'another package' => ['package'];
    yield 'another module' => ['module'];
  }

  /**
   * Tests replacing the file of a package.
   */
  #[DataProvider('dataProviderReplaceFile')]
  public function testReplaceFile(bool $new_revision, bool $expected_kept): void {
    // Prepare.
    $original = $this->createFile(PackageAccessHook::DIRECTORY . 'original.zip');
    $replacement = $this->createFile(PackageAccessHook::DIRECTORY . 'replacement.zip');
    $package = $this->createPackage($original);

    // Act.
    $this->replaceFile($package, $replacement, $new_revision);

    // Assert.
    $this->assertFileKept($replacement);

    if ($expected_kept) {
      $this->assertFileKept($original);
    }
    else {
      $this->assertFileRemoved($original);
    }
  }

  /**
   * Data provider for testReplaceFile().
   */
  public static function dataProviderReplaceFile(): \Iterator {
    yield 'new revision keeps the file of the previous revision' => [TRUE, TRUE];
    yield 'same revision deletes the replaced file' => [FALSE, FALSE];
  }

  /**
   * Tests that deleting a revision deletes a file no other revision holds.
   */
  public function testRevisionDelete(): void {
    // Prepare.
    $first = $this->createFile(PackageAccessHook::DIRECTORY . 'first.zip');
    $second = $this->createFile(PackageAccessHook::DIRECTORY . 'second.zip');
    $package = $this->createPackage($first);
    $first_revision_id = $package->getRevisionId();
    $package->setNewRevision();
    $package->save();
    $second_revision_id = $package->getRevisionId();
    $this->replaceFile($package, $second, TRUE);
    $storage = $this->container->get('entity_type.manager')->getStorage('media');
    $this->assertInstanceOf(RevisionableStorageInterface::class, $storage);

    // Act.
    $storage->deleteRevision($first_revision_id);

    // Assert.
    $this->assertFileKept($first);

    // Act.
    $storage->deleteRevision($second_revision_id);

    // Assert.
    $this->assertFileRemoved($first);
    $this->assertFileKept($second);
  }

  /**
   * Tests that deleting media of another bundle keeps its file.
   */
  public function testOtherBundleUnaffected(): void {
    // Prepare.
    $document_type = $this->createMediaType('file', ['id' => 'document', 'label' => 'Document']);
    $file = $this->createFile('public://documents/test.txt');
    $document = Media::create(['bundle' => 'document', 'name' => '[TEST] Document', $this->sourceFieldName($document_type) => $file->id(), 'uid' => 1]);
    $document->save();

    // Act.
    $document->delete();

    // Assert.
    $this->assertFileKept($file);
  }

  /**
   * Saves the package holding another file.
   */
  protected function replaceFile(MediaInterface $package, FileInterface $file, bool $new_revision): void {
    $package->set($this->packageField, $file->id());
    $package->setNewRevision($new_revision);
    $package->save();
  }

  /**
   * Asserts that the file record and its stored file exist.
   */
  protected function assertFileKept(FileInterface $file): void {
    $this->assertInstanceOf(FileInterface::class, File::load($file->id()));
    $this->assertFileExists($file->getFileUri());
  }

  /**
   * Asserts that the file record and its stored file are gone.
   */
  protected function assertFileRemoved(FileInterface $file): void {
    $this->assertNull(File::load($file->id()));
    $this->assertFileDoesNotExist($file->getFileUri());
  }

}
