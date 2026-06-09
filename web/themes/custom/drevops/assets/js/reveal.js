/**
 * @file
 * Reveal-on-scroll behaviour.
 *
 * Adds a `visible` class to any `.component-reveal` element when it enters the
 * viewport. Stagger delays are handled in CSS via `.component-reveal-d1` ...
 * `.component-reveal-d6`. Elements are only hidden when the `js` class is on
 * the document (added by Drupal), so content stays visible without JavaScript.
 */
((Drupal, once) => {
  Drupal.behaviors.drevopsReveal = {
    attach(context) {
      const elements = once('dr-reveal', '.component-reveal', context);

      if (!elements.length) {
        return;
      }

      if (!('IntersectionObserver' in window)) {
        elements.forEach((el) => el.classList.add('visible'));
        return;
      }

      const observer = new IntersectionObserver(
        (entries, obs) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
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
