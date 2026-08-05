<?php

declare(strict_types=1);

namespace Drupal\Tests\do_ai_alt_text\Unit;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatInterface;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\ChatOutput;
use Drupal\ai_image_alt_text\ProviderHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\do_ai_alt_text\AltTextGenerator;
use Drupal\do_ai_alt_text\Exception\AltTextGenerationException;
use Drupal\file\FileInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Tests for AltTextGenerator.
 */
#[CoversClass(AltTextGenerator::class)]
#[Group('do_ai_alt_text')]
class AltTextGeneratorTest extends UnitTestCase {

  /**
   * Prompt stored in the contrib module's settings.
   */
  const PROMPT = 'Describe {{ filename }} in {{ entity_lang_name }}.';

  /**
   * Chat input the provider was last called with.
   */
  protected ?ChatInput $capturedInput = NULL;

  /**
   * Tests that provider output becomes the alt text.
   */
  public function testGenerateForFileReturnsProviderText(): void {
    // Prepare.
    $generator = $this->createGenerator($this->createProvider('A dog asleep on a rug.'));

    // Act.
    $result = $generator->generateForFile($this->createFile(), 'en');

    // Assert.
    $this->assertSame('A dog asleep on a rug.', $result);
  }

  /**
   * Tests that the configured prompt is rendered with the file context.
   */
  public function testGenerateForFileRendersPrompt(): void {
    // Prepare.
    $generator = $this->createGenerator($this->createProvider('Alt text.'));

    // Act.
    $generator->generateForFile($this->createFile(), 'de');

    // Assert.
    $this->assertInstanceOf(ChatInput::class, $this->capturedInput);
    $this->assertSame('Describe image.png in German.', $this->capturedInput->getMessages()[0]->getText());
  }

  /**
   * Tests that provider output is flattened onto a single line.
   */
  #[DataProvider('dataProviderNormalisation')]
  public function testGenerateForFileNormalisesProviderText(string $raw, string $expected): void {
    // Prepare.
    $generator = $this->createGenerator($this->createProvider($raw));

    // Act.
    $result = $generator->generateForFile($this->createFile(), 'en');

    // Assert.
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for testGenerateForFileNormalisesProviderText().
   */
  public static function dataProviderNormalisation(): \Iterator {
    yield 'surrounding whitespace' => ['  A cat.  ', 'A cat.'];
    yield 'newlines' => ["A cat\non a mat.", 'A cat on a mat.'];
    yield 'repeated spaces' => ['A    cat.', 'A cat.'];
    yield 'tabs' => ["A\tcat.", 'A cat.'];
    yield 'carriage returns' => ["A cat.\r\n", 'A cat.'];
  }

  /**
   * Tests that output longer than the alt column is truncated.
   */
  public function testGenerateForFileTruncatesLongText(): void {
    // Prepare.
    $raw = trim(str_repeat('description ', 100));
    $generator = $this->createGenerator($this->createProvider($raw));

    // Act.
    $result = $generator->generateForFile($this->createFile(), 'en');

    // Assert.
    $this->assertLessThanOrEqual(AltTextGenerator::ALT_MAX_LENGTH, mb_strlen($result));
    $this->assertStringStartsWith($result, $raw);
  }

  /**
   * Tests that a file that is not an image is refused.
   */
  public function testGenerateForFileRejectsNonImage(): void {
    // Prepare.
    $generator = $this->createGenerator($this->createProvider('Alt text.'));

    // Assert.
    $this->expectException(AltTextGenerationException::class);
    $this->expectExceptionMessage('File image.png is not an image.');

    // Act.
    $generator->generateForFile($this->createFile('application/pdf'), 'en');
  }

  /**
   * Tests that a file with an unknown type is refused.
   */
  public function testGenerateForFileRejectsUnknownMimeType(): void {
    // Prepare.
    $generator = $this->createGenerator($this->createProvider('Alt text.'));

    // Assert.
    $this->expectException(AltTextGenerationException::class);
    $this->expectExceptionMessage('is not an image');

    // Act.
    $generator->generateForFile($this->createFile(NULL), 'en');
  }

  /**
   * Tests that a missing provider is reported rather than fataling.
   */
  public function testGenerateForFileRequiresProvider(): void {
    // Prepare.
    $generator = $this->createGenerator(NULL);

    // Assert.
    $this->expectException(AltTextGenerationException::class);
    $this->expectExceptionMessage('No AI provider is configured for image vision.');

    // Act.
    $generator->generateForFile($this->createFile(), 'en');
  }

  /**
   * Tests that a failing provider call is wrapped with the file context.
   */
  public function testGenerateForFileWrapsProviderFailure(): void {
    // Prepare.
    $provider = $this->createMock(ChatInterface::class);
    $provider->method('chat')->willThrowException(new \RuntimeException('Quota exceeded.'));
    $generator = $this->createGenerator($provider);

    // Assert.
    $this->expectException(AltTextGenerationException::class);
    $this->expectExceptionMessage('The AI provider could not describe file image.png: Quota exceeded.');

    // Act.
    $generator->generateForFile($this->createFile(), 'en');
  }

  /**
   * Tests that an empty answer never becomes an empty alt attribute.
   */
  public function testGenerateForFileRejectsEmptyResponse(): void {
    // Prepare.
    $generator = $this->createGenerator($this->createProvider("  \n  "));

    // Assert.
    $this->expectException(AltTextGenerationException::class);
    $this->expectExceptionMessage('The AI provider returned no alt text for file image.png.');

    // Act.
    $generator->generateForFile($this->createFile(), 'en');
  }

  /**
   * Tests that a file missing from disk is reported rather than fataling.
   */
  public function testGenerateForFileRejectsUnreadableFile(): void {
    // Prepare.
    $generator = $this->createGenerator($this->createProvider('Alt text.'));

    // Assert.
    $this->expectException(AltTextGenerationException::class);
    $this->expectExceptionMessage('Image file image.png could not be read.');

    // Act.
    $generator->generateForFile($this->createFile('image/png', __DIR__ . '/gone.png'), 'en');
  }

  /**
   * Tests that the configured image style is sent instead of the original.
   */
  public function testGenerateForFileSendsImageStyleDerivative(): void {
    // Prepare.
    $image_style = $this->createMock(ImageStyleInterface::class);
    $image_style->method('buildUri')->willReturn($this->derivativePath());
    $image_style->method('createDerivative')->willReturn(TRUE);
    $generator = $this->createGenerator($this->createProvider('Alt text.'), $image_style);

    // Act.
    $generator->generateForFile($this->createFile(), 'en');

    // Assert.
    $image = $this->capturedInput->getMessages()[0]->getImages()[0];
    $this->assertSame('derivative.png', $image->getFilename());
    $this->assertSame('image/png', $image->getMimeType());
  }

  /**
   * Tests that an unbuildable style falls back to the original image.
   */
  public function testGenerateForFileFallsBackWhenDerivativeFails(): void {
    // Prepare.
    $image_style = $this->createMock(ImageStyleInterface::class);
    $image_style->method('buildUri')->willReturn($this->derivativePath());
    $image_style->method('createDerivative')->willReturn(FALSE);
    $generator = $this->createGenerator($this->createProvider('Alt text.'), $image_style);

    // Act.
    $generator->generateForFile($this->createFile(), 'en');

    // Assert.
    $image = $this->capturedInput->getMessages()[0]->getImages()[0];
    $this->assertSame('image.png', $image->getFilename());
    $this->assertSame(file_get_contents($this->fixturePath()), $image->getBinary());
  }

  /**
   * Tests that a deleted image style falls back to the original image.
   */
  public function testGenerateForFileFallsBackWhenStyleIsMissing(): void {
    // Prepare.
    $generator = $this->createGenerator($this->createProvider('Alt text.'), NULL, 'ai_image_alt_text');

    // Act.
    $generator->generateForFile($this->createFile(), 'en');

    // Assert.
    $image = $this->capturedInput->getMessages()[0]->getImages()[0];
    $this->assertSame('image.png', $image->getFilename());
  }

  /**
   * Builds a generator wired to the given provider and image style.
   *
   * @param \Drupal\ai\OperationType\Chat\ChatInterface|null $provider
   *   Provider the helper resolves to, or NULL for an unconfigured site.
   * @param \Drupal\image\ImageStyleInterface|null $image_style
   *   Image style the storage returns, or NULL when it no longer exists.
   * @param string $image_style_name
   *   Image style name held in the contrib module's settings.
   *
   * @return \Drupal\do_ai_alt_text\AltTextGenerator
   *   Generator under test.
   */
  protected function createGenerator(?ChatInterface $provider, ?ImageStyleInterface $image_style = NULL, string $image_style_name = ''): AltTextGenerator {
    if ($image_style instanceof ImageStyleInterface) {
      $image_style_name = 'ai_image_alt_text';
    }

    $settings = $this->createMock(ImmutableConfig::class);
    $settings->method('get')->willReturnMap([
      ['prompt', self::PROMPT],
      ['image_style', $image_style_name],
    ]);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with('ai_image_alt_text.settings')->willReturn($settings);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn($image_style);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('image_style')->willReturn($storage);

    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language_manager->method('getLanguageName')->willReturnMap([['en', 'English'], ['de', 'German']]);

    $twig = $this->createMock(TwigEnvironment::class);
    $twig->method('renderInline')->willReturnCallback(static fn(string $template, array $context): string => strtr($template, [
      '{{ filename }}' => $context['filename'],
      '{{ entity_lang_name }}' => $context['entity_lang_name'],
    ]));

    $provider_helper = $this->createMock(ProviderHelper::class);
    $provider_helper->method('getSetProvider')->willReturn($provider === NULL ? NULL : ['provider_id' => $provider, 'model_id' => 'test-model']);

    $mime_type_guesser = $this->createMock(MimeTypeGuesserInterface::class);
    $mime_type_guesser->method('guessMimeType')->willReturn('image/png');

    return new AltTextGenerator($config_factory, $entity_type_manager, $language_manager, $twig, $provider_helper, $mime_type_guesser);
  }

  /**
   * Builds a provider that answers with the given text.
   *
   * @param string $text
   *   Text the provider answers with.
   *
   * @return \Drupal\ai\OperationType\Chat\ChatInterface
   *   Provider double.
   */
  protected function createProvider(string $text): ChatInterface {
    $provider = $this->createMock(ChatInterface::class);
    $provider->method('chat')->willReturnCallback(function (ChatInput $input) use ($text): ChatOutput {
      $this->capturedInput = $input;

      return new ChatOutput(new ChatMessage('assistant', $text), [], []);
    });

    return $provider;
  }

  /**
   * Builds an image file double pointing at the fixture image.
   *
   * @param string|null $mime_type
   *   Mime type the file reports.
   * @param string|null $uri
   *   Location the file reports, defaulting to the fixture image.
   *
   * @return \Drupal\file\FileInterface
   *   File double.
   */
  protected function createFile(?string $mime_type = 'image/png', ?string $uri = NULL): FileInterface {
    $file = $this->createMock(FileInterface::class);
    $file->method('getMimeType')->willReturn($mime_type);
    $file->method('getFilename')->willReturn('image.png');
    $file->method('getFileUri')->willReturn($uri ?? $this->fixturePath());

    return $file;
  }

  /**
   * Location of the fixture image.
   */
  protected function fixturePath(): string {
    return dirname(__DIR__, 2) . '/fixtures/image.png';
  }

  /**
   * Location standing in for a generated image style derivative.
   */
  protected function derivativePath(): string {
    return dirname(__DIR__, 2) . '/fixtures/derivative.png';
  }

}
