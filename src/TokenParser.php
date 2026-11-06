<?php

declare(strict_types=1);

namespace Drupal\my_store_locator;

/**
 * Extracts and validates My Store Locator map tokens.
 *
 * Accepts the raw token, the iframe or widget embed code from the app
 * (data-token="…" or …/#/m/<token>) or the public map URL.
 */
final class TokenParser {

  /**
   * Pattern of a valid map token.
   */
  public const PATTERN = '/^[A-Za-z0-9_-]{6,64}$/';

  /**
   * Returns the map token found in the input.
   *
   * @param string $input
   *   Raw token, embed code or public map URL.
   *
   * @return string|null
   *   The token, an empty string for empty input, or NULL when the input
   *   contains no valid token.
   */
  public static function extract(string $input): ?string {
    $input = trim(html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($input === '') {
      return '';
    }
    if (self::isValid($input)) {
      return $input;
    }
    if (preg_match('/data-token\s*=\s*["\']([^"\']+)["\']/i', $input, $match) && self::isValid($match[1])) {
      return $match[1];
    }
    if (preg_match('~#/m/([A-Za-z0-9_-]+)~', $input, $match) && self::isValid($match[1])) {
      return $match[1];
    }
    return NULL;
  }

  /**
   * Checks whether a string is a valid map token.
   */
  public static function isValid(string $token): bool {
    return (bool) preg_match(self::PATTERN, $token);
  }

}
