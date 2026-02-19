/**
 * @file
 * Prevents form submission until the reCAPTCHA v3 token is populated.
 * @param {Object}   Drupal  - The Drupal object.
 * @param {Function} once    - The once function.
 */

(function doBaseRecaptchaV3Guard(Drupal, once) {
  /**
   * Enables all submit buttons in a form.
   *
   * @param {NodeList} submits  - The submit buttons to enable.
   */
  function enableSubmits(submits) {
    submits.forEach(function enableBtn(btn) {
      btn.disabled = false;
    });
  }

  /**
   * Watches a token input and enables submit buttons when the token is set.
   *
   * @param {HTMLElement} tokenInput  - The reCAPTCHA token hidden input.
   * @param {NodeList}    submits     - The submit buttons to control.
   */
  function watchToken(tokenInput, submits) {
    // Poll for the token value since hidden input value changes do not
    // reliably fire DOM events.
    const poll = setInterval(function checkToken() {
      if (tokenInput.value) {
        clearInterval(poll);
        enableSubmits(submits);
      }
    }, 200);

    // Safety timeout: re-enable buttons after 5 seconds regardless so the
    // form is never permanently locked.
    setTimeout(function safetyTimeout() {
      clearInterval(poll);
      enableSubmits(submits);
    }, 5000);
  }

  Drupal.behaviors.doBaseRecaptchaV3Guard = {
    attach: function attachGuard(context) {
      once('recaptcha-v3-guard', '.recaptcha-v3-token', context).forEach(
        function processToken(tokenInput) {
          const form = tokenInput.closest('form');
          if (!form) {
            return;
          }

          const submits = form.querySelectorAll('[type="submit"]');
          if (!submits.length) {
            return;
          }

          // Disable submit buttons until the token is ready.
          submits.forEach(function disableBtn(btn) {
            btn.disabled = true;
          });

          watchToken(tokenInput, submits);
        },
      );
    },
  };
})(Drupal, once);
