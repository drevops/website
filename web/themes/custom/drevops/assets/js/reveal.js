/**
 * @file
 * Reveal-on-scroll behaviour.
 *
 * Adds an `is-visible` class to any `.dr-reveal` element when it enters the
 * viewport. Stagger delays are handled in CSS via `.dr-reveal--d1` ...
 * `.dr-reveal--d6`. Elements are only hidden when the `js` class is present on
 * the document (added by Drupal), so content remains visible without
 * JavaScript.
 */
((Drupal, once) => {
  Drupal.behaviors.drevopsReveal = {
    attach(context) {
      const elements = once('dr-reveal', '.dr-reveal', context);

      if (!elements.length) {
        return;
      }

      if (!('IntersectionObserver' in window)) {
        elements.forEach((el) => el.classList.add('is-visible'));
        return;
      }

      const observer = new IntersectionObserver(
        (entries, obs) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-visible');
              obs.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
      );

      elements.forEach((el) => observer.observe(el));
    },
  };
})(Drupal, once);
