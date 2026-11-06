<?php

declare(strict_types=1);

namespace Drupal\my_store_locator\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\my_store_locator\MapEmbedBuilder;
use Drupal\my_store_locator\TokenParser;

/**
 * Form elements and validation shared by the settings form and the block.
 *
 * The using class must provide t() (StringTranslationTrait).
 */
trait MapSettingsFormTrait {

  /**
   * Builds the map settings elements.
   *
   * @param array $values
   *   Current values keyed by MapEmbedBuilder::KEYS.
   * @param array $connect
   *   The connect button element (ConnectButton::element()).
   * @param bool $override
   *   TRUE for the block form: every element gets a "use default" option.
   * @param array $defaults
   *   Effective global settings (shown to the user in the block form).
   *
   * @return array
   *   Form elements.
   */
  protected function mapSettingsElements(array $values, array $connect, bool $override = FALSE, array $defaults = []): array {
    $on_off = fn($value) => $value ? $this->t('on') : $this->t('off');
    $use_default = fn($shown) => $this->t('Default (@value)', ['@value' => $shown]);
    $lang_options = [
      'auto' => $this->t('Site language (German for de*, otherwise English)'),
      'de' => $this->t('German'),
      'en' => $this->t('English'),
    ];
    $layout_options = [
      'default' => $this->t('As set in the app'),
      'split' => $this->t('Split (finder template: list scrolls with the page, map stays in view)'),
    ];

    $elements['token'] = [
      '#type' => 'textfield',
      '#title' => $override ? $this->t('Map token') : $this->t('Default map token'),
      '#default_value' => $values['token'] ?? '',
      '#maxlength' => 4096,
      '#attributes' => ['class' => ['msl-token-input'], 'autocomplete' => 'off', 'spellcheck' => 'false'],
      '#description' => $override
        ? $this->t('Leave empty to use the default map token from the settings page. You can paste the token, the embed code or the public map URL.')
        : $this->t('Paste the map token, the embed code (iframe or widget) or the public map URL (…/#/m/&lt;token&gt;). You find it in the app under "Embed".'),
    ];
    $elements['connect'] = $connect;

    $elements['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $values['height'] ?? '',
      '#size' => 12,
      '#maxlength' => 16,
      '#required' => !$override,
      '#placeholder' => $override ? ($defaults['height'] ?? 'auto') : 'auto',
      '#description' => $this->t('"auto" lets the map grow with its content (the page scrolls), or a fixed height such as 640px or 80vh.') . ($override ? ' ' . $this->t('Leave empty to use the default.') : ''),
    ];
    $elements['lang'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => $override ? ['' => $use_default($lang_options[$defaults['lang'] ?? 'auto'])] + $lang_options : $lang_options,
      '#default_value' => $values['lang'] ?? ($override ? '' : 'auto'),
    ];
    if ($override) {
      $elements['consent'] = [
        '#type' => 'select',
        '#title' => $this->t('Consent mode (click to load)'),
        '#options' => [
          '' => $use_default($on_off($defaults['consent'] ?? TRUE)),
          '1' => $this->t('on'),
          '0' => $this->t('off'),
        ],
        '#default_value' => $values['consent'] ?? '',
      ];
      $elements['lazy'] = [
        '#type' => 'select',
        '#title' => $this->t('Lazy loading'),
        '#options' => [
          '' => $use_default($on_off($defaults['lazy'] ?? TRUE)),
          '1' => $this->t('on'),
          '0' => $this->t('off'),
        ],
        '#default_value' => $values['lazy'] ?? '',
      ];
    }
    else {
      $elements['consent'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Consent mode (click to load)'),
        '#default_value' => $values['consent'] ?? TRUE,
        '#description' => $this->t('Recommended (GDPR): visitors first see a notice and the map loads only after they click "Karte laden". Without consent mode, the map loads from app.my-store-locator.com right away.'),
      ];
      $elements['lazy'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Lazy loading'),
        '#default_value' => $values['lazy'] ?? TRUE,
        '#description' => $this->t('The browser loads the map only when it comes close to the visible area.'),
      ];
    }
    $elements['offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Sticky header offset'),
      '#field_suffix' => 'px',
      '#min' => 0,
      '#max' => 2000,
      '#default_value' => $values['offset'] ?? ($override ? '' : 0),
      '#placeholder' => $override ? (string) ($defaults['offset'] ?? 0) : '',
      '#description' => $this->t('Height of a fixed (sticky) header of your theme, so that the map stays below it. 0 = none.') . ($override ? ' ' . $this->t('Leave empty to use the default.') : ''),
    ];
    $elements['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#options' => $override ? ['' => $use_default($layout_options[$defaults['layout'] ?? 'default'])] + $layout_options : $layout_options,
      '#default_value' => $values['layout'] ?? ($override ? '' : 'default'),
      '#description' => $this->t('"Split" only applies with height "auto" and the finder template.'),
    ];
    return $elements;
  }

  /**
   * Validates the map settings and normalizes the token.
   *
   * @param array $element
   *   The (sub)form that holds the map settings elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The (sub)form state; values are read relative to it.
   */
  protected function validateMapSettings(array $element, FormStateInterface $form_state): void {
    $value = fn($key) => trim((string) $form_state->getValue($key));

    $token = TokenParser::extract($value('token'));
    if ($token === NULL) {
      $form_state->setError($element['token'], $this->t('No valid map token found. Paste the token (6–64 letters, digits, "-" or "_"), the embed code or the public map URL.'));
    }
    else {
      $form_state->setValue('token', $token);
    }

    $height = $value('height');
    if ($height !== '' && !preg_match(MapEmbedBuilder::HEIGHT_PATTERN, $height)) {
      $form_state->setError($element['height'], $this->t('Enter "auto" or a height such as 640px, 80vh or 40rem.'));
    }
  }

  /**
   * Returns the submitted map settings as a keyed array.
   */
  protected function submittedMapSettings(FormStateInterface $form_state): array {
    $settings = [];
    foreach (MapEmbedBuilder::KEYS as $key) {
      $settings[$key] = $form_state->getValue($key);
    }
    return $settings;
  }

}
