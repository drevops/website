<?php

declare(strict_types=1);

namespace Drupal\do_ai_alt_text\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\do_ai_alt_text\AltTextGenerator;
use Drupal\media\MediaInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Replaces the alt text of a media item's images with AI generated text.
 */
#[Action(
  id: 'do_ai_alt_text_regenerate',
  label: new TranslatableMarkup('Re-generate image alt text with AI'),
  type: 'media',
)]
class RegenerateImageAltText extends ActionBase implements ContainerFactoryPluginInterface {

  const RESULT_UPDATED = 'updated';

  const RESULT_SKIPPED = 'skipped';

  const RESULT_FAILED = 'failed';

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AltTextGenerator $generator,
    protected AccountInterface $currentUser,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('do_ai_alt_text.generator'),
      $container->get('current_user'),
      $container->get('logger.factory')->get('do_ai_alt_text'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    if ($entity instanceof MediaInterface) {
      $this->regenerate($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities): void {
    $operations = [];

    foreach ($entities as $entity) {
      if ($entity instanceof MediaInterface) {
        $operations[] = [[static::class, 'batchRegenerate'], [$entity->id()]];
      }
    }

    if ($operations === []) {
      return;
    }

    // Every image is its own round trip to the provider, so the selection is
    // spread over a batch rather than described within a single request.
    batch_set([
      'title' => $this->t('Re-generating image alt text with AI'),
      'operations' => $operations,
      'finished' => [static::class, 'batchFinished'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $account ?: $this->currentUser;

    if (!$object instanceof MediaInterface) {
      $access = AccessResult::forbidden('Only media items can be described.');
    }
    else {
      $access = AccessResult::allowedIfHasPermission($account, 'generate ai alt tags')->andIf($object->access('update', $account, TRUE));
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Describes one media item's images, keeping a batch running on failure.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media item to describe.
   *
   * @return string
   *   One of the RESULT_* outcomes.
   */
  protected function regenerate(MediaInterface $media): string {
    try {
      return $this->generator->regenerateForMedia($media) > 0 ? static::RESULT_UPDATED : static::RESULT_SKIPPED;
    }
    catch (\Exception $exception) {
      $this->logger->error('Could not re-generate alt text for media @id: @message', [
        '@id' => $media->id(),
        '@message' => $exception->getMessage(),
      ]);

      return static::RESULT_FAILED;
    }
  }

  /**
   * Batch operation callback.
   *
   * @param int|string $media_id
   *   Identifier of the media item to describe.
   * @param array $context
   *   Batch context.
   */
  public static function batchRegenerate(int|string $media_id, array &$context): void {
    $context['results'] += [
      static::RESULT_UPDATED => 0,
      static::RESULT_SKIPPED => 0,
      static::RESULT_FAILED => 0,
    ];

    $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);

    // A batch outlives the request that queued it, so access is re-checked
    // rather than trusted from the form submission.
    if (!$media instanceof MediaInterface || !$media->access('update')) {
      $context['results'][static::RESULT_SKIPPED]++;

      return;
    }

    $context['message'] = new TranslatableMarkup('Re-generating alt text for @label', ['@label' => $media->label()]);

    /** @var \Drupal\do_ai_alt_text\Plugin\Action\RegenerateImageAltText $action */
    $action = \Drupal::service('plugin.manager.action')->createInstance('do_ai_alt_text_regenerate');
    $context['results'][$action->regenerate($media)]++;
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Whether the batch ran to completion.
   * @param array $results
   *   Outcome tally collected by the operations.
   */
  public static function batchFinished(bool $success, array $results): void {
    $messenger = \Drupal::messenger();

    if (!$success) {
      $messenger->addError(new TranslatableMarkup('Re-generating image alt text did not finish.'));

      return;
    }

    $translation = \Drupal::translation();
    $updated = $results[static::RESULT_UPDATED] ?? 0;
    $skipped = $results[static::RESULT_SKIPPED] ?? 0;
    $failed = $results[static::RESULT_FAILED] ?? 0;

    $messenger->addStatus($translation->formatPlural($updated, 'Re-generated the alt text of 1 media item.', 'Re-generated the alt text of @count media items.'));

    if ($skipped > 0) {
      $messenger->addWarning($translation->formatPlural($skipped, '1 media item had no image to describe.', '@count media items had no image to describe.'));
    }

    if ($failed > 0) {
      $messenger->addError($translation->formatPlural($failed, 'The alt text of 1 media item could not be re-generated. Check the logs for details.', 'The alt text of @count media items could not be re-generated. Check the logs for details.'));
    }
  }

}
