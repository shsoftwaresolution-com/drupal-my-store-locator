# My Store Locator

Shows a store finder map from [My Store
Locator](https://my-store-locator.com/en/?utm_source=drupal&utm_medium=integration&utm_campaign=drupal-plugin)
on your Drupal site, as a block. You manage your locations, opening hours,
categories, look and template in the My Store Locator web app; this module
embeds the map with the app's widget – no copy of your data is kept in Drupal.

For a full description of the module, visit the [project
page](https://www.drupal.org/project/my_store_locator).

Submit bug reports and feature suggestions, or track changes in the [issue
queue](https://www.drupal.org/project/issues/my_store_locator).


## Table of contents

- Requirements
- Installation
- Configuration
- Privacy
- Troubleshooting
- Maintainers


## Requirements

- Drupal 10 or 11 (core Block module).
- A My Store Locator account with at least one map. The Free plan works (1 map,
  25 locations): [create an
  account](https://app.my-store-locator.com/#/register?utm_source=drupal).

The map itself is loaded from `https://app.my-store-locator.com/widget.js` (an
external, hosted service).


## Installation

Install as you would normally install a contributed Drupal module:

```
composer require drupal/my_store_locator
drush en my_store_locator
```

For further information, see [Installing Drupal
Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. Go to **Administration » Configuration » Web services » My Store Locator**
   (`/admin/config/services/my-store-locator`, permission "Administer My Store
   Locator").
2. Set the **default map token**. You can paste any of:
   - the map token (for example `gf5jjebq0fae210403`),
   - the embed code from the app ("Embed": iframe or JavaScript widget),
   - the public map URL (`https://app.my-store-locator.com/#/m/<token>`).

   The module extracts and validates the token (6–64 letters, digits, `-`,
   `_`). The button **Connect with My Store Locator** opens the app in a popup,
   where you pick a map; the token is then filled in for you. (The connect page
   of the app is being rolled out – pasting the token always works.)
3. Choose the display defaults:
   - **Height**: `auto` (the map grows with its content, the page scrolls) or a
     fixed height such as `640px` or `80vh`.
   - **Language**: site language (German for `de*`, otherwise English), German
     or English.
   - **Consent mode** (default on): visitors see a privacy notice and the map
     loads only after a click.
   - **Lazy loading** (default on).
   - **Sticky header offset** in px, if your theme has a fixed header.
   - **Layout**: as set in the app, or *split* (finder template with height
     `auto`: the list scrolls with the page, the map stays in view).
4. Go to **Structure » Block layout**, click **Place block** in a region (for
   example *Content*) and choose **My Store Locator map**. Every block can
   override the token and each display setting; empty fields use the defaults.

Without a valid token the block renders nothing for visitors; administrators see
a short hint instead.

Templates, colours, fonts, fields and filters of the map are set in the My Store
Locator app. Changing the template there never changes the token, so the Drupal
block does not need to be touched.

### Theming

The block renders `my-store-locator-map.html.twig`. Without consent mode:

```html
<div class="msl-map" id="msl-map-…" data-mystorelocator data-token="…"
     data-height="auto" data-lang="de" data-lazy="true"></div>
```

plus the library `my_store_locator/widget` (the widget script, loaded once per
page, `async`). With consent mode, the container is a gate with a
click-to-load placeholder and no widget markers:

```html
<div class="msl-map-consent" data-msl-gate id="msl-map-…" data-token="…"
     data-height="auto" data-lang="de" data-lazy="true">…placeholder…</div>
```

plus the local library `my_store_locator/consent` (`js/msl-consent.js`). On the
click it turns every gated map into a widget container and injects the widget
script. Override the template in your theme if you need a wrapper; keep the
classes and `data-*` attributes.


## Privacy

- The map is an external service: it is loaded from **app.my-store-locator.com**
  (hosted in Germany).
- When the map loads, the visitor's browser connects to that server, which
  receives the visitor's **IP address** (as with any embedded content). Map
  tiles and address search go through the same server.
- **Consent mode** (default) shows a click-to-load placeholder (text and button
  in the map language: "Karte laden" / "Load map"). Nothing at all is loaded
  from app.my-store-locator.com before the visitor clicks – not even the widget
  script; the placeholder and its script are served by your Drupal site. The
  click loads every map on the page.
- The module sets no cookies, stores no visitor data, contains no tracking and
  does not phone home. Map statistics in the app are aggregated daily counters
  without cookies or stored IP addresses.
- Mention the service in your privacy policy. Details: [Datenschutz
  (German)](https://my-store-locator.com/datenschutz/) · [Privacy policy
  (English)](https://my-store-locator.com/en/privacy/). A data processing
  agreement (Art. 28 GDPR) is available in the app.


## Troubleshooting

- **The block is empty:** no valid token is set (log in as an administrator to
  see the hint), or the block is not visible on this page (check the block
  visibility settings).
- **"No valid map token found":** paste only the token, the complete embed code
  or the public map URL.
- **The map overlaps a fixed header:** set the sticky header offset to the
  header height.
- **The connect popup does not open:** allow popups for your site, or paste the
  token manually.


## Maintainers

- MAINTAINER_PLACEHOLDER (drupal.org user name) – SH Software Solution /
  Internet am Dornbusch, Frankfurt am Main

License: GPL-2.0-or-later (the license file is added by drupal.org). The My
Store Locator service itself is a commercial hosted service with a free plan.
