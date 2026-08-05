<?php

declare(strict_types=1);

namespace Drupal\Tests\do_ai_alt_text\Kernel\Plugin\Action;

use Drupal\Core\Action\ActionInterface;
use Drupal\do_ai_alt_text\AltTextGenerator;
use Drupal\do_ai_alt_text\Exception\AltTextGenerationException;
use Drupal\do_ai_alt_text\Plugin\Action\RegenerateImageAltText;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests for RegenerateImageAltText.
 */
#[CoversClass(RegenerateImageAltText::class)]
#[Group('do_ai_alt_text')]
class RegenerateImageAltTextTest extends KernelTestBase {

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
    'key',
    'ai',
    'ai_image_alt_text',
    'do_ai_alt_text',
  ];

  /**
   * Generator the action delegates to.
   */
  protected MockObject $generator;

  /**
   * Entries written to the logger while a case runs.
   */
  protected array $logRecords = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['field', 'system', 'image', 'media', 'ai_image_alt_text']);

    $this->generator = $this->createMock(AltTextGenerator::class);
    $this->container->set('do_ai_alt_text.generator', $this->generator);

    $logger = $this->createMock(LoggerInterface::class);
    $logger->method('log')->willReturnCallback(function (mixed $level, string|\Stringable $message, array $context): void {
      $this->logRecords[] = ['message' => (string) $message, 'context' => $context];
    });
    $this->container->get('logger.factory')->addLogger($logger);

    $this->createMediaType('image', ['id' => 'test_image', 'label' => 'Image']);

    // Reserve uid 1; the superuser bypasses permission checks, so the actors
    // created in each case must be regular accounts.
    $this->createUser();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $batch = &batch_get();
    $batch = [];

    parent::tearDown();
  }

  /**
   * Tests that the action hands a media item to the generator.
   */
  public function testExecuteRegeneratesAltText(): void {
    // Prepare.
    $media = $this->createImageMedia();
    $this->generator->expects($this->once())->method('regenerateForEntity')->with($media)->willReturn(1);

    // Act.
    $this->action()->execute($media);
  }

  /**
   * Tests that anything other than a media item is ignored.
   */
  public function testExecuteIgnoresNonMedia(): void {
    // Prepare.
    $this->generator->expects($this->never())->method('regenerateForEntity');

    // Act.
    $this->action()->execute(NULL);
    $this->action()->execute(File::create(['uri' => 'public://ignored.png']));
  }

  /**
   * Tests that a generator failure is logged instead of aborting the run.
   */
  public function testExecuteLogsGeneratorFailure(): void {
    // Prepare.
    $media = $this->createImageMedia();
    $this->generator->method('regenerateForEntity')->willThrowException(new AltTextGenerationException('Quota exceeded.'));

    // Act.
    $this->action()->execute($media);

    // Assert.
    $this->assertContains('Could not re-generate alt text for media @id: @message', array_column($this->logRecords, 'message'));
    $this->assertSame('Quota exceeded.', end($this->logRecords)['context']['@message']);
  }

  /**
   * Tests who is allowed to run the action.
   */
  #[DataProvider('dataProviderAccess')]
  public function testAccess(array $permissions, bool $expected): void {
    // Prepare.
    $account = $this->createUser($permissions);
    $media = $this->createImageMedia();

    // Act.
    $result = $this->action()->access($media, $account);

    // Assert.
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testAccess().
   */
  public static function dataProviderAccess(): \Iterator {
    yield 'no permissions' => [[], FALSE];
    yield 'can generate but cannot edit media' => [['generate ai alt tags'], FALSE];
    yield 'can edit media but cannot generate' => [['update any media'], FALSE];
    yield 'can generate and can edit media' => [['generate ai alt tags', 'update any media'], TRUE];
  }

  /**
   * Tests that the action refuses anything other than a media item.
   */
  public function testAccessRefusesNonMedia(): void {
    // Prepare.
    $account = $this->createUser(['generate ai alt tags', 'update any media']);

    // Act.
    $result = $this->action()->access(File::create(['uri' => 'public://ignored.png']), $account, TRUE);

    // Assert.
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Tests that the current user is used when no account is given.
   */
  public function testAccessFallsBackToCurrentUser(): void {
    // Prepare.
    $this->setCurrentUser($this->createUser(['generate ai alt tags', 'update any media']));
    $media = $this->createImageMedia();

    // Act.
    $result = $this->action()->access($media);

    // Assert.
    $this->assertTrue($result);
  }

  /**
   * Tests that a selection is turned into one batch operation per item.
   */
  public function testExecuteMultipleQueuesOneOperationPerItem(): void {
    // Prepare.
    $first = $this->createImageMedia();
    $second = $this->createImageMedia();

    // Act.
    $this->action()->executeMultiple([$first, $second]);

    // Assert.
    $batch = batch_get();
    $this->assertCount(1, $batch['sets']);
    $this->assertSame([
      [[RegenerateImageAltText::class, 'batchRegenerate'], [$first->id()]],
      [[RegenerateImageAltText::class, 'batchRegenerate'], [$second->id()]],
    ], $batch['sets'][0]['operations']);
  }

  /**
   * Tests that a selection holding no media item queues nothing.
   */
  public function testExecuteMultipleQueuesNothingWithoutMedia(): void {
    // Act.
    $this->action()->executeMultiple([File::create(['uri' => 'public://ignored.png'])]);

    // Assert.
    $this->assertSame([], batch_get());
  }

  /**
   * Tests the outcome a batch operation records for each media item.
   */
  #[DataProvider('dataProviderBatchRegenerate')]
  public function testBatchRegenerateTalliesOutcome(int $regenerated, bool $fails, string $expected): void {
    // Prepare.
    $this->setCurrentUser($this->createUser(['generate ai alt tags', 'update any media']));
    $media = $this->createImageMedia();

    if ($fails) {
      $this->generator->method('regenerateForEntity')->willThrowException(new AltTextGenerationException('Quota exceeded.'));
    }
    else {
      $this->generator->method('regenerateForEntity')->willReturn($regenerated);
    }

    $context = ['results' => []];

    // Act.
    RegenerateImageAltText::batchRegenerate((int) $media->id(), $context);

    // Assert.
    $this->assertSame(1, $context['results'][$expected]);
  }

  /**
   * Data provider for testBatchRegenerateTalliesOutcome().
   */
  public static function dataProviderBatchRegenerate(): \Iterator {
    yield 'image described' => [1, FALSE, RegenerateImageAltText::RESULT_UPDATED];
    yield 'nothing to describe' => [0, FALSE, RegenerateImageAltText::RESULT_SKIPPED];
    yield 'provider failed' => [0, TRUE, RegenerateImageAltText::RESULT_FAILED];
  }

  /**
   * Tests that a batch operation re-checks access before describing an image.
   */
  public function testBatchRegenerateSkipsInaccessibleMedia(): void {
    // Prepare.
    $this->setCurrentUser($this->createUser(['generate ai alt tags']));
    $media = $this->createImageMedia();
    $this->generator->expects($this->never())->method('regenerateForEntity');
    $context = ['results' => []];

    // Act.
    RegenerateImageAltText::batchRegenerate((int) $media->id(), $context);

    // Assert.
    $this->assertSame(1, $context['results'][RegenerateImageAltText::RESULT_SKIPPED]);
  }

  /**
   * Tests that a media item deleted before the batch reached it is skipped.
   */
  public function testBatchRegenerateSkipsDeletedMedia(): void {
    // Prepare.
    $this->setCurrentUser($this->createUser(['generate ai alt tags', 'update any media']));
    $this->generator->expects($this->never())->method('regenerateForEntity');
    $context = ['results' => []];

    // Act.
    RegenerateImageAltText::batchRegenerate(404, $context);

    // Assert.
    $this->assertSame(1, $context['results'][RegenerateImageAltText::RESULT_SKIPPED]);
  }

  /**
   * Tests the summary reported once the batch has run.
   */
  #[DataProvider('dataProviderBatchFinished')]
  public function testBatchFinished(bool $success, array $results, string $type, string $expected): void {
    // Act.
    RegenerateImageAltText::batchFinished($success, $results);

    // Assert.
    $messages = \Drupal::messenger()->messagesByType($type);
    $this->assertSame($expected, (string) reset($messages));
  }

  /**
   * Data provider for testBatchFinished().
   */
  public static function dataProviderBatchFinished(): \Iterator {
    yield 'interrupted batch' => [FALSE, [], 'error', 'Re-generating image alt text did not finish.'];
    yield 'one item described' => [TRUE, ['updated' => 1], 'status', 'Re-generated the alt text of 1 media item.'];
    yield 'several items described' => [TRUE, ['updated' => 4], 'status', 'Re-generated the alt text of 4 media items.'];
    yield 'nothing described' => [TRUE, [], 'status', 'Re-generated the alt text of 0 media items.'];
    yield 'items skipped' => [TRUE, ['skipped' => 2], 'warning', '2 media items had no image to describe.'];
    yield 'one item skipped' => [TRUE, ['skipped' => 1], 'warning', '1 media item had no image to describe.'];
    yield 'items failed' => [TRUE, ['failed' => 3], 'error', 'The alt text of 3 media items could not be re-generated. Check the logs for details.'];
  }

  /**
   * Returns the action under test.
   */
  protected function action(): ActionInterface {
    return $this->container->get('plugin.manager.action')->createInstance('do_ai_alt_text_regenerate');
  }

  /**
   * Creates a media item holding the fixture image.
   */
  protected function createImageMedia(): MediaInterface {
    $file = File::create(['uri' => 'public://' . $this->randomMachineName() . '.png']);
    file_put_contents($file->getFileUri(), (string) file_get_contents(dirname(__DIR__, 4) . '/fixtures/image.png'));
    $file->save();

    $media = Media::create([
      'bundle' => 'test_image',
      'name' => 'Test image',
      'field_media_image' => ['target_id' => $file->id(), 'alt' => 'Hand written alt text.'],
    ]);
    $media->save();

    return $media;
  }

}
