<?php

declare(strict_types=1);

namespace Drupal\my_store_locator;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Builds the "Connect with My Store Locator" button for admin forms.
 *
 * The button opens the app's connect page in a popup. The app sends the
 * browser back to connect/return.html of this module, which hands the
 * selected map token to this window (postMessage, same origin only).
 */
class ConnectButton {

  use StringTranslationTrait;

  /**
   * Base URL of the My Store Locator app.
   */
  public const APP_URL = 'https://app.my-store-locator.com';

  public function __construct(
    protected ExtensionPathResolver $extensionPathResolver,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * Returns the absolute URL of the connect return page.
   */
  public function returnUrl(): string {
    $path = $this->extensionPathResolver->getPath('module', 'my_store_locator') . '/connect/return.html';
    return $this->fileUrlGenerator->generateAbsoluteString($path);
  }

  /**
   * Returns the connect URL without the state parameter (added in JS).
   */
  public function connectUrl(): string {
    return self::APP_URL . '/connect?' . http_build_query([
      'platform' => 'drupal',
      'return_url' => $this->returnUrl(),
    ], '', '&', PHP_QUERY_RFC3986);
  }

  /**
   * Builds the button, a status line and the sign-up link.
   *
   * The token field of the same form must carry the class "msl-token-input".
   */
  public function element(): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['msl-connect']],
      'button' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t('Connect with My Store Locator'),
        '#attributes' => [
          'type' => 'button',
          'class' => ['button', 'msl-connect-button'],
          'data-msl-connect' => '',
        ],
      ],
      'status' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => '',
        '#attributes' => [
          'class' => ['msl-connect-status'],
          'aria-live' => 'polite',
        ],
      ],
      'signup' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('No account yet? <a href=":signup" target="_blank" rel="noopener">Create a free My Store Locator account</a>, add your locations and copy the map token from "Embed".', [
          ':signup' => self::APP_URL . '/#/register?utm_source=drupal',
        ]),
        '#attributes' => ['class' => ['description']],
      ],
      '#attached' => [
        'library' => ['my_store_locator/connect'],
        'drupalSettings' => [
          'myStoreLocator' => [
            'connectUrl' => $this->connectUrl(),
            'appOrigin' => self::APP_URL,
          ],
        ],
      ],
    ];
  }

}
