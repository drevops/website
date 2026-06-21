// phpcs:ignoreFile
/**
 * @file
 * Reveal behaviour.
 *
 * Adds a 'visible' class to every '.component-reveal' element once it
 * scrolls into view, driving the staggered fade-up defined in reveal.scss.
 * Each element is revealed once and ignored on subsequent attaches.
 *
 * The entrance animation is gated behind 'prefers-reduced-motion' in CSS, so
 * visitors who prefer reduced motion always see content in its final state.
 */
function DrevOpsReveal() {
  const elements = document.querySelectorAll(
    '.component-reveal:not([data-reveal-bound])',
  );

  if (!elements.length) {
    return;
  }

  if (!('IntersectionObserver' in window)) {
    // Reveal immediately when the observer is unavailable so that content is
    // never left hidden.
    elements.forEach((element) => {
      element.setAttribute('data-reveal-bound', '');
      element.classList.add('visible');
    });

    return;
  }

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        obs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  elements.forEach((element) => {
    element.setAttribute('data-reveal-bound', '');
    observer.observe(element);
  });
}

DrevOpsReveal();
