/**
 * @file
 * Fails in the browser on demand.
 * @param {object} Drupal
 * - The Drupal object.
 * @param {function(string, string, (Document|Element)=): Element[]} once
 * - The once function.
 */

(function doBaseTestClientErrors(Drupal, once) {
  const actions = {
    throw() {
      throw new Error('[TEST] Uncaught error');
    },
    reject() {
      Promise.reject(new TypeError('[TEST] Rejected promise'));
    },
    // Throws more errors than a page may report.
    flood() {
      for (let i = 1; i <= 15; i++) {
        setTimeout(function throwFlood() {
          throw new Error(`[TEST] Flood ${i}`);
        }, 0);
      }
    },
  };

  Drupal.behaviors.doBaseTestClientErrors = {
    attach: function attachClientErrors(context) {
      once('do-base-test', '[data-do-base-test]', context).forEach(function bindButton(button) {
        button.addEventListener('click', actions[button.dataset.doBaseTest]);
      });
    },
  };
})(Drupal, once);
