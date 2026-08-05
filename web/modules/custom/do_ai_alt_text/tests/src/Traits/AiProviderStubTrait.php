<?php

declare(strict_types=1);

namespace Drupal\Tests\do_ai_alt_text\Traits;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\ChatOutput;
use Drupal\ai\Plugin\ProviderProxy;
use Drupal\ai_image_alt_text\ProviderHelper;

/**
 * Stands in for the AI provider so no case reaches a real model.
 */
trait AiProviderStubTrait {

  /**
   * Chat input the stub was last called with.
   */
  protected ?ChatInput $capturedChatInput = NULL;

  /**
   * Number of times the stub was asked to describe an image.
   */
  protected int $chatCalls = 0;

  /**
   * Exception the stub throws instead of answering.
   */
  protected ?\Exception $chatFailure = NULL;

  /**
   * Call number the stub starts throwing on, counting from one.
   */
  protected int $chatFailsAtCall = 1;

  /**
   * Builds a provider that answers with the given text.
   *
   * @param string $answer
   *   Text the provider answers with.
   *
   * @return \Drupal\ai\Plugin\ProviderProxy
   *   Provider double. The AI module always hands back a proxy, which routes
   *   chat() through __call() rather than implementing an interface.
   */
  protected function createAiProvider(string $answer): ProviderProxy {
    $record = function (ChatInput $input): void {
      $this->capturedChatInput = $input;
      $this->chatCalls++;

      if ($this->chatFailure instanceof \Exception && $this->chatCalls >= $this->chatFailsAtCall) {
        throw $this->chatFailure;
      }
    };

    return new class($answer, $record) extends ProviderProxy {

      public function __construct(protected string $answer, protected \Closure $record) {
      }

      /**
       * Answers a chat request with the fixed text this stub was built with.
       *
       * @param \Drupal\ai\OperationType\Chat\ChatInput $input
       *   Prompt and images the caller assembled.
       * @param string $model_id
       *   Model the caller asked for.
       * @param array $tags
       *   Tags the caller attached to the request.
       *
       * @return \Drupal\ai\OperationType\Chat\ChatOutput
       *   Fixed answer.
       */
      public function chat(ChatInput $input, string $model_id, array $tags = []): ChatOutput {
        ($this->record)($input);

        return new ChatOutput(new ChatMessage('assistant', $this->answer), [], []);
      }

    };
  }

  /**
   * Builds the contrib helper that resolves the configured provider.
   *
   * @param \Drupal\ai\Plugin\ProviderProxy|null $provider
   *   Provider to resolve to, or NULL for a site with no provider set up.
   *
   * @return \Drupal\ai_image_alt_text\ProviderHelper
   *   Provider helper double.
   */
  protected function createAiProviderHelper(?ProviderProxy $provider): ProviderHelper {
    $provider_helper = $this->createMock(ProviderHelper::class);
    $provider_helper->method('getSetProvider')->willReturn($provider instanceof ProviderProxy ? ['provider_id' => $provider, 'model_id' => 'test-model'] : NULL);

    return $provider_helper;
  }

}
