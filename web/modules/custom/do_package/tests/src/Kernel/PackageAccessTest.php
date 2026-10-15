<?php

declare(strict_types=1);

namespace Drupal\Tests\do_package\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\do_package\Hook\PackageAccessHook;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests package access through core media and private file handling.
 */
#[CoversClass(PackageAccessHook::class)]
#[Group('do_package')]
#[RunTestsInSeparateProcesses]
class PackageAccessTest extends PackageKernelTestBase {

  /**
   * Accounts keyed by the audience they stand for.
   *
   * @var array<string, \Drupal\Core\Session\AccountInterface>
   */
  protected array $accounts = [];

  /**
   * A published package.
   */
  protected MediaInterface $package;

  /**
   * The private file the package holds.
   */
  protected FileInterface $packageFile;

  /**
   * A published media item of another file bundle.
   */
  protected MediaInterface $document;

  /**
   * The public file the document holds.
   */
  protected FileInterface $documentFile;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $document_type = $this->createMediaType('file', ['id' => 'document', 'label' => 'Document']);
    $document_field = $this->sourceFieldName($document_type);

    $this->packageFile = $this->createFile(PackageAccessHook::DIRECTORY . 'test.zip');
    $this->package = $this->createPackage($this->packageFile);

    $this->documentFile = $this->createFile('public://documents/test.txt');
    $this->document = Media::create(['bundle' => 'document', 'name' => '[TEST] Document', $document_field => $this->documentFile->id(), 'uid' => 1]);
    $this->document->save();

    $anonymous_role = Role::load(RoleInterface::ANONYMOUS_ID);
    $this->assertInstanceOf(RoleInterface::class, $anonymous_role);
    $this->grantPermissions($anonymous_role, ['view media']);

    $this->accounts = [
      'site_administrator' => $this->createAccount([PackageAccessHook::PERMISSION, 'view media', 'administer media']),
      'content_api' => $this->createAccount([PackageAccessHook::PERMISSION, 'view media', 'create media', 'view own unpublished media']),
      'content_author' => $this->createAccount(['view media', 'create media', 'update any media', 'delete any media', 'view all media revisions', 'access media overview']),
      'anonymous' => new AnonymousUserSession(),
    ];
  }

  /**
   * Tests operations on a published package.
   */
  #[DataProvider('dataProviderPackageAccess')]
  public function testPackageAccess(string $audience, string $operation, bool $expected): void {
    // Act.
    $result = $this->package->access($operation, $this->accounts[$audience]);

    // Assert.
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testPackageAccess().
   */
  public static function dataProviderPackageAccess(): \Iterator {
    yield 'site administrator views' => ['site_administrator', 'view', TRUE];
    yield 'site administrator updates' => ['site_administrator', 'update', TRUE];
    yield 'site administrator deletes' => ['site_administrator', 'delete', TRUE];
    yield 'content api service views' => ['content_api', 'view', TRUE];
    yield 'content api service cannot update' => ['content_api', 'update', FALSE];
    yield 'content api service cannot delete' => ['content_api', 'delete', FALSE];
    yield 'content author cannot view' => ['content_author', 'view', FALSE];
    yield 'content author cannot update' => ['content_author', 'update', FALSE];
    yield 'content author cannot delete' => ['content_author', 'delete', FALSE];
    yield 'content author cannot view revisions' => ['content_author', 'view all revisions', FALSE];
    yield 'anonymous cannot view' => ['anonymous', 'view', FALSE];
  }

  /**
   * Tests who may create a package.
   */
  #[DataProvider('dataProviderPackageCreateAccess')]
  public function testPackageCreateAccess(string $audience, bool $expected): void {
    // Prepare.
    $handler = $this->container->get('entity_type.manager')->getAccessControlHandler('media');

    // Act.
    $result = $handler->createAccess(PackageAccessHook::BUNDLE, $this->accounts[$audience]);

    // Assert.
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testPackageCreateAccess().
   */
  public static function dataProviderPackageCreateAccess(): \Iterator {
    yield 'site administrator' => ['site_administrator', TRUE];
    yield 'content api service' => ['content_api', TRUE];
    yield 'content author' => ['content_author', FALSE];
    yield 'anonymous' => ['anonymous', FALSE];
  }

  /**
   * Tests downloading the private file of a published package.
   */
  #[DataProvider('dataProviderFileDownload')]
  public function testFileDownload(string $audience, bool $expected): void {
    // Prepare.
    $account = $this->accounts[$audience];
    $this->setCurrentUser($account);

    // Act.
    $access = $this->packageFile->access('download', $account);
    $headers = $this->container->get('module_handler')->invokeAll('file_download', [$this->packageFile->getFileUri()]);

    // Assert.
    $this->assertSame($expected, $access);

    if ($expected) {
      $this->assertNotContains(-1, $headers);
      $this->assertArrayHasKey('Content-Type', $headers);
    }
    else {
      $this->assertContains(-1, $headers);
    }
  }

  /**
   * Data provider for testFileDownload().
   */
  public static function dataProviderFileDownload(): \Iterator {
    yield 'site administrator' => ['site_administrator', TRUE];
    yield 'content api service' => ['content_api', TRUE];
    yield 'content author' => ['content_author', FALSE];
    yield 'anonymous' => ['anonymous', FALSE];
  }

  /**
   * Tests that an unpublished package is withdrawn from the API service.
   */
  public function testUnpublishedPackage(): void {
    // Act.
    $this->package->setUnpublished()->save();
    $this->container->get('entity_type.manager')->getAccessControlHandler('media')->resetCache();

    // Assert.
    $this->assertTrue($this->package->access('view', $this->accounts['site_administrator']));
    $this->assertTrue($this->packageFile->access('download', $this->accounts['site_administrator']));
    $this->assertFalse($this->package->access('view', $this->accounts['content_api']));
    $this->assertFalse($this->packageFile->access('download', $this->accounts['content_api']));
  }

  /**
   * Tests that media of other bundles keeps core's access rules.
   */
  public function testOtherBundleUnaffected(): void {
    // Assert.
    $this->assertTrue($this->document->access('view', $this->accounts['anonymous']));
    $this->assertTrue($this->document->access('update', $this->accounts['content_author']));
    $this->assertTrue($this->document->access('delete', $this->accounts['content_author']));
  }

  /**
   * Tests which media an entity query returns to each audience.
   */
  #[DataProvider('dataProviderEntityQuery')]
  public function testEntityQuery(string $audience, bool $access_check, bool $expected_package): void {
    // Prepare.
    $this->setCurrentUser($this->accounts[$audience]);

    // Act.
    $ids = $this->container->get('entity_type.manager')->getStorage('media')->getQuery()->accessCheck($access_check)->execute();

    // Assert.
    $this->assertContains((string) $this->document->id(), $ids);
    $this->assertSame($expected_package, in_array((string) $this->package->id(), $ids, TRUE));
  }

  /**
   * Data provider for testEntityQuery().
   */
  public static function dataProviderEntityQuery(): \Iterator {
    yield 'site administrator' => ['site_administrator', TRUE, TRUE];
    yield 'content api service' => ['content_api', TRUE, TRUE];
    yield 'content author' => ['content_author', TRUE, FALSE];
    yield 'anonymous' => ['anonymous', TRUE, FALSE];
    yield 'content author without access checks' => ['content_author', FALSE, TRUE];
  }

  /**
   * Tests which files an entity query returns to each audience.
   */
  #[DataProvider('dataProviderFileEntityQuery')]
  public function testFileEntityQuery(string $audience, bool $access_check, bool $expected_package_file): void {
    // Prepare.
    $this->setCurrentUser($this->accounts[$audience]);

    // Act.
    $ids = $this->container->get('entity_type.manager')->getStorage('file')->getQuery()->accessCheck($access_check)->execute();

    // Assert.
    $this->assertContains((string) $this->documentFile->id(), $ids);
    $this->assertSame($expected_package_file, in_array((string) $this->packageFile->id(), $ids, TRUE));
  }

  /**
   * Data provider for testFileEntityQuery().
   */
  public static function dataProviderFileEntityQuery(): \Iterator {
    yield 'site administrator' => ['site_administrator', TRUE, TRUE];
    yield 'content api service' => ['content_api', TRUE, TRUE];
    yield 'content author' => ['content_author', TRUE, FALSE];
    yield 'anonymous' => ['anonymous', TRUE, FALSE];
    yield 'content author without access checks' => ['content_author', FALSE, TRUE];
  }

  /**
   * Creates a regular account holding the given permissions.
   */
  protected function createAccount(array $permissions): AccountInterface {
    $account = $this->createUser($permissions);
    $this->assertInstanceOf(AccountInterface::class, $account);

    return $account;
  }

}
