<?php

declare(strict_types=1);

namespace Drupal\my_store_locator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\my_store_locator\ConnectButton;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Global settings of My Store Locator (defaults for every map block).
 */
class SettingsForm extends ConfigFormBase {

  use MapSettingsFormTrait;

  /**
   * The connect button builder.
   */
  protected ConnectButton $connectButton;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->connectButton = $container->get('my_store_locator.connect');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'my_store_locator_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['my_store_locator.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('my_store_locator.settings');
    $values = [];
    foreach (['token', 'height', 'lang', 'consent', 'lazy', 'offset', 'layout'] as $key) {
      $values[$key] = $config->get($key);
    }

    $form['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Show your <a href=":site" target="_blank" rel="noopener">My Store Locator</a> map on this site: connect your account (or paste the map token), then place the block "My Store Locator map" in a region on the <a href=":blocks">block layout</a> page. These settings are the defaults for every map block.', [
        ':site' => 'https://my-store-locator.com/en/?utm_source=drupal&utm_medium=integration&utm_campaign=drupal-plugin',
        ':blocks' => Url::fromRoute('block.admin_display')->toString(),
      ]),
    ];
    $form += $this->mapSettingsElements($values, $this->connectButton->element());

    $form['privacy'] = [
      '#type' => 'details',
      '#title' => $this->t('Privacy'),
      '#open' => FALSE,
      'text' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('The map is loaded from app.my-store-locator.com (hosted in Germany). When it loads, the visitor\'s browser sends its IP address to that server. With consent mode nothing is loaded from that server before the visitor clicks. This module itself stores no visitor data and sends nothing anywhere. Mention the service in your privacy policy: <a href=":de" target="_blank" rel="noopener">Datenschutz (German)</a>, <a href=":en" target="_blank" rel="noopener">Privacy policy (English)</a>.', [
          ':de' => 'https://my-store-locator.com/datenschutz/',
          ':en' => 'https://my-store-locator.com/en/privacy/',
        ]),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateMapSettings($form, $form_state);
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->submittedMapSettings($form_state);
    $this->config('my_store_locator.settings')
      ->set('token', (string) $settings['token'])
      ->set('height', trim((string) $settings['height']) ?: 'auto')
      ->set('lang', (string) $settings['lang'])
      ->set('consent', (bool) $settings['consent'])
      ->set('lazy', (bool) $settings['lazy'])
      ->set('offset', max(0, (int) $settings['offset']))
      ->set('layout', $settings['layout'] === 'split' ? 'split' : 'default')
      ->save();
    parent::submitForm($form, $form_state);
  }

}
