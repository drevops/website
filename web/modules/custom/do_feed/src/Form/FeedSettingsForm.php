<?php

declare(strict_types=1);

namespace Drupal\do_feed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for feed settings.
 */
final class FeedSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   The editable config names.
   */
  protected function getEditableConfigNames(): array {
    return ['do_feed.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'do_feed_settings_form';
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('do_feed.settings');

    $form['path_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path prefix'),
      '#description' => $this->t('The URL prefix for feed aliases (e.g., with prefix "feed", the alias is /feed/blog).'),
      '#default_value' => $config->get('path_prefix') ?? 'feed',
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('do_feed.settings')
      ->set('path_prefix', $form_state->getValue('path_prefix'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
