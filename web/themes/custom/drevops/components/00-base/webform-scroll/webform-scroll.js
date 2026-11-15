// phpcs:ignoreFile
/**
 * @file
 * Webform scroll offset.
 *
 * Webform scrolls an Ajax-updated form to its top minus
 * Drupal.webform.scrollTopOffset, which only accounts for the admin toolbar.
 * The offset is read at scroll time, so a getter measures the sticky header
 * as it is then.
 */

(function (Drupal) {
  // Space left between the header and the scrolled-to form.
  const GAP = 24;

  Drupal.webform = Drupal.webform || {};

  Object.defineProperty(Drupal.webform, 'scrollTopOffset', {
    configurable: true,
    get() {
      const header = document.querySelector('.ct-header');

      return (header ? Math.max(header.getBoundingClientRect().bottom, 0) : 0) + GAP;
    },
    // Webform assigns its default on load; the measured value replaces it.
    set() {},
  });
})(Drupal);
