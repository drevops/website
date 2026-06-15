/**
 * @file
 * Reveal-on-scroll behaviour.
 *
 * Mounts a subtle fade-and-rise reveal on the outer wrapper of each homepage
 * section component (manual lists and callouts) as it enters the viewport. The
 * design uses this treatment on the homepage only; every other page renders its
 * sections statically.
 *
 * The hiding class is added by this script, never in the stylesheet, so the
 * content is only ever hidden once the reveal has been initialised. If the
 * script does not run, the browser lacks IntersectionObserver, or the visitor
 * prefers reduced motion, every section stays fully visible.
 */
((Drupal, once) => {
  Drupal.behaviors.drevopsReveal = {
    attach(context) {
      if (!document.body.classList.contains('path-frontpage')) {
        return;
      }

      const elements = once('do-reveal', '.ct-list, .ct-callout', context);

      if (!elements.length) {
        return;
      }

      const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        return;
      }

      const observer = new IntersectionObserver(
        (entries, obs) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add('do-reveal--in');
              obs.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
      );

      elements.forEach((el) => {
        el.classList.add('do-reveal');
        observer.observe(el);
      });
    },
  };
})(Drupal, once);
