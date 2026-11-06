<?php

declare(strict_types=1);

namespace Drupal\my_store_locator;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Builds the render array of a My Store Locator map.
 */
class MapEmbedBuilder {

  use StringTranslationTrait;

  /**
   * Settings keys that a block may override.
   */
  public const KEYS = ['token', 'height', 'lang', 'consent', 'lazy', 'offset', 'layout'];

  /**
   * Pattern of an allowed height ("auto" or a CSS length).
   */
  public const HEIGHT_PATTERN = '/^(auto|\d{2,5}(\.\d+)?(px|vh|em|rem))$/';

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LanguageManagerInterface $languageManager,
    protected AccountInterface $currentUser,
  ) {}

  /**
   * Merges block overrides into the global settings.
   *
   * @param array $overrides
   *   Block settings; empty strings (or NULL) mean "use the global default".
   *
   * @return array
   *   Effective settings.
   */
  public function resolve(array $overrides = []): array {
    $config = $this->configFactory->get('my_store_locator.settings');
    $settings = [];
    foreach (self::KEYS as $key) {
      $settings[$key] = $config->get($key);
      if (isset($overrides[$key]) && $overrides[$key] !== '') {
        $settings[$key] = $overrides[$key];
      }
    }
    $settings['token'] = (string) $settings['token'];
    $settings['height'] = (string) ($settings['height'] ?: 'auto');
    $settings['lang'] = in_array($settings['lang'], ['auto', 'de', 'en'], TRUE) ? $settings['lang'] : 'auto';
    $settings['consent'] = (bool) $settings['consent'];
    $settings['lazy'] = (bool) $settings['lazy'];
    $settings['offset'] = max(0, min(2000, (int) $settings['offset']));
    $settings['layout'] = $settings['layout'] === 'split' ? 'split' : 'default';
    return $settings;
  }

  /**
   * Builds the map embed.
   *
   * @param array $overrides
   *   Block settings (see ::resolve()).
   *
   * @return array
   *   A render array; without a valid token it only carries cache metadata
   *   (visitors) or a hint (administrators).
   */
  public function build(array $overrides = []): array {
    $settings = $this->resolve($overrides);
    $cache = CacheableMetadata::createFromObject($this->configFactory->get('my_store_locator.settings'));
    $build = [];

    if (!TokenParser::isValid($settings['token'])) {
      $cache->addCacheContexts(['user.permissions']);
      if ($this->currentUser->hasPermission('administer my store locator')) {
        $build['hint'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('My Store Locator: no valid map token is set. Add it in the block settings or on the <a href=":url">settings page</a>. Visitors do not see this message.', [
            ':url' => Url::fromRoute('my_store_locator.settings')->toString(),
          ]),
          '#attributes' => ['class' => ['msl-map-missing-token']],
        ];
      }
      $cache->applyTo($build);
      return $build;
    }

    $lang = $settings['lang'];
    if ($lang === 'auto') {
      $cache->addCacheContexts(['languages:language_interface']);
      $current = $this->languageManager->getCurrentLanguage()->getId();
      $lang = str_starts_with(strtolower($current), 'de') ? 'de' : 'en';
    }

    $height = preg_match(self::HEIGHT_PATTERN, $settings['height']) ? $settings['height'] : 'auto';
    $attributes = [
      'id' => 'msl-map-' . bin2hex(random_bytes(5)),
      'data-token' => $settings['token'],
      'data-height' => $height,
      'data-lang' => $lang,
    ];
    if ($settings['lazy']) {
      $attributes['data-lazy'] = 'true';
    }
    if ($settings['offset'] > 0) {
      $attributes['data-offset'] = (string) $settings['offset'];
    }
    if ($settings['layout'] === 'split') {
      $attributes['data-layout'] = 'split';
    }

    if ($settings['consent']) {
      // Strict consent: no widget markers and no widget.js until the click,
      // so nothing is requested from app.my-store-locator.com before that.
      $build['map'] = [
        '#theme' => 'my_store_locator_map',
        '#attributes' => ['class' => ['msl-map-consent'], 'data-msl-gate' => ''] + $attributes,
        '#token' => $settings['token'],
        '#consent' => TRUE,
        '#placeholder_height' => $height === 'auto' ? '760px' : $height,
        '#consent_text' => $this->germanFallback((string) $this->t('This map is loaded from my-store-locator.com (own map server in Germany). Your IP address is transferred to this server.', [], ['langcode' => $lang]), $lang),
        '#consent_button' => $this->germanFallback((string) $this->t('Load map', [], ['langcode' => $lang]), $lang),
        '#attached' => ['library' => ['my_store_locator/consent']],
      ];
    }
    else {
      $build['map'] = [
        '#theme' => 'my_store_locator_map',
        '#attributes' => ['class' => ['msl-map'], 'data-mystorelocator' => ''] + $attributes,
        '#token' => $settings['token'],
        '#consent' => FALSE,
        '#attached' => ['library' => ['my_store_locator/widget']],
      ];
    }
    $cache->applyTo($build);
    return $build;
  }

  /**
   * German texts of the consent placeholder.
   *
   * Used when the map language is German but no German interface translation
   * is installed (for example a site without the Locale module).
   */
  protected const CONSENT_DE = [
    'This map is loaded from my-store-locator.com (own map server in Germany). Your IP address is transferred to this server.' => 'Diese Karte wird von my-store-locator.com geladen (eigener Kartenserver in Deutschland). Dabei wird Ihre IP-Adresse an diesen Server übertragen.',
    'Load map' => 'Karte laden',
  ];

  /**
   * Returns the built-in German text when no German translation is installed.
   *
   * @param string $text
   *   The text as returned by t() for the map language.
   * @param string $lang
   *   The map language (de or en).
   *
   * @return string
   *   The text in the map language.
   */
  protected function germanFallback(string $text, string $lang): string {
    return $lang === 'de' && isset(self::CONSENT_DE[$text]) ? self::CONSENT_DE[$text] : $text;
  }

}
