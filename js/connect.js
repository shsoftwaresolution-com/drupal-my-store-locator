/**
 * @file
 * "Connect with My Store Locator": opens the app's connect page in a popup
 * and fills the map token field with the token the user picks there.
 *
 * Flow: popup → app.my-store-locator.com/connect?platform=drupal&return_url=…
 * &state=… → the app sends the browser back to connect/return.html of this
 * module (same origin as this page), which posts
 * {type: 'msl:connect', token, map, state} to this window and closes. When a
 * Cross-Origin-Opener-Policy cut window.opener, the return page uses the
 * same-origin BroadcastChannel 'msl-connect' instead; this script acks it.
 */
((Drupal, drupalSettings, once) => {
  const STATE_KEY = 'myStoreLocatorConnectState';
  const TOKEN = /^[A-Za-z0-9_-]{6,64}$/;

  function randomState() {
    const bytes = new Uint8Array(16);
    window.crypto.getRandomValues(bytes);
    return Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
  }

  function setStatus(button, text, ok) {
    const status = button.parentElement.querySelector('.msl-connect-status');
    if (!status) {
      return;
    }
    status.textContent = text;
    status.classList.toggle('is-ok', ok === true);
    status.classList.toggle('is-error', ok === false);
  }

  Drupal.behaviors.myStoreLocatorConnect = {
    attach(context) {
      const settings = drupalSettings.myStoreLocator || {};
      once('msl-connect', '[data-msl-connect]', context).forEach((button) => {
        const form = button.closest('form');
        const input = form ? form.querySelector('.msl-token-input') : null;
        if (!settings.connectUrl || !input) {
          button.hidden = true;
          return;
        }

        const accept = (data) => {
          if (!data || typeof data !== 'object' || data.type !== 'msl:connect') {
            return false;
          }
          let expected = null;
          try {
            expected = window.sessionStorage.getItem(STATE_KEY);
          } catch (e) {
            expected = button.dataset.mslState || null;
          }
          if (!expected) {
            // No connect started in this window (e.g. another admin tab).
            return false;
          }
          if (data.state !== expected || !TOKEN.test(data.token || '')) {
            setStatus(button, Drupal.t('The connection could not be verified. Please try again or paste the map token.'), false);
            return false;
          }
          try {
            window.sessionStorage.removeItem(STATE_KEY);
          } catch (e) {
            // Storage not available; the state was kept on the button.
          }
          input.value = data.token;
          input.dispatchEvent(new Event('input', { bubbles: true }));
          input.dispatchEvent(new Event('change', { bubbles: true }));
          setStatus(
            button,
            data.map
              ? Drupal.t('Map "@map" connected. Save to apply.', { '@map': data.map })
              : Drupal.t('Map connected. Save to apply.'),
            true,
          );
          return true;
        };

        window.addEventListener('message', (event) => {
          if (event.origin === window.location.origin) {
            accept(event.data);
          }
        });

        if ('BroadcastChannel' in window) {
          const channel = new BroadcastChannel('msl-connect');
          channel.onmessage = (event) => {
            const state = event.data && event.data.state;
            if (accept(event.data)) {
              channel.postMessage({ type: 'msl:connect:ack', state });
            }
          };
        }

        button.addEventListener('click', (event) => {
          event.preventDefault();
          const state = randomState();
          button.dataset.mslState = state;
          try {
            window.sessionStorage.setItem(STATE_KEY, state);
          } catch (e) {
            // Fall back to the data attribute (checked above).
          }
          const url = `${settings.connectUrl}&state=${encodeURIComponent(state)}`;
          const popup = window.open(url, 'msl-connect', 'popup=yes,width=520,height=760');
          if (!popup) {
            setStatus(button, Drupal.t('The popup was blocked. Allow popups for this site or paste the map token.'), false);
            return;
          }
          setStatus(button, Drupal.t('Waiting for My Store Locator …'), null);
        });
      });
    },
  };
})(Drupal, drupalSettings, once);
