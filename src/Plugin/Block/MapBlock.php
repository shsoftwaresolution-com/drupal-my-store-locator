<?php

declare(strict_types=1);

namespace Drupal\my_store_locator\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\my_store_locator\ConnectButton;
use Drupal\my_store_locator\Form\MapSettingsFormTrait;
use Drupal\my_store_locator\MapEmbedBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shows a My Store Locator map.
 *
 * @Block(
 *   id = "my_store_locator_map",
 *   admin_label = @Translation("My Store Locator map"),
 *   category = @Translation("My Store Locator")
 * )
 */
#[Block(
  id: 'my_store_locator_map',
  admin_label: new TranslatableMarkup('My Store Locator map'),
  category: new TranslatableMarkup('My Store Locator'),
)]
class MapBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use MapSettingsFormTrait;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected MapEmbedBuilder $embedBuilder,
    protected ConnectButton $connectButton,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('my_store_locator.embed_builder'),
      $container->get('my_store_locator.connect'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array_fill_keys(MapEmbedBuilder::KEYS, '');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $values = array_intersect_key($this->configuration, array_flip(MapEmbedBuilder::KEYS));
    $defaults = $this->embedBuilder->resolve();
    $form['my_store_locator_intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Empty fields use the defaults of the <a href=":url">My Store Locator settings</a>.', [
        ':url' => Url::fromRoute('my_store_locator.settings')->toString(),
      ]),
    ];
    $form += $this->mapSettingsElements($values, $this->connectButton->element(), TRUE, $defaults);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $this->validateMapSettings($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach ($this->submittedMapSettings($form_state) as $key => $value) {
      $this->configuration[$key] = trim((string) $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->embedBuilder->build($this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['config:my_store_locator.settings']);
  }

}
