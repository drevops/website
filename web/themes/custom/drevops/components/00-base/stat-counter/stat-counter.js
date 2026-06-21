// phpcs:ignoreFile
/**
 * @file
 * Stat count-up behaviour.
 *
 * Animates each '.ct-stat-item__count' from zero to its 'data-target' value
 * when the stat grid scrolls into view, using an easeOut curve. Visitors who
 * prefer reduced motion, and anyone without IntersectionObserver, keep the
 * final value the template already renders. Each element is animated once and
 * ignored on subsequent attaches.
 */
function DrevOpsStatCounter() {
  const counters = document.querySelectorAll(
    '.ct-stat-item__count[data-target]:not([data-stat-bound])',
  );

  if (!counters.length) {
    return;
  }

  const reduceMotion = window.matchMedia
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const easeOut = (t) => 1 - ((1 - t) ** 3);

  const countUp = (element, target) => {
    if (!Number.isFinite(target)) {
      return;
    }

    const duration = target < 20 ? 1200 : 2000;
    let start = null;

    const step = (timestamp) => {
      if (start === null) {
        start = timestamp;
      }

      const progress = Math.min((timestamp - start) / duration, 1);
      element.textContent = Math.round(easeOut(progress) * target);

      if (progress < 1) {
        window.requestAnimationFrame(step);
      } else {
        element.textContent = target;
      }
    };

    window.requestAnimationFrame(step);
  };

  let observer = null;
  if ('IntersectionObserver' in window) {
    observer = new IntersectionObserver((entries, obs) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const target = parseInt(entry.target.getAttribute('data-target'), 10);
          countUp(entry.target, target);
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });
  }

  counters.forEach((element) => {
    element.setAttribute('data-stat-bound', '');

    const target = parseInt(element.getAttribute('data-target'), 10);

    if (Number.isNaN(target) || target < 0) {
      return;
    }

    // Without motion or an observer, keep the final value the template
    // already rendered.
    if (reduceMotion || target === 0 || !observer) {
      element.textContent = String(target);

      return;
    }

    // Reset to zero now, not on intersect, so the final value never flashes
    // before the grid scrolls into view.
    element.textContent = '0';
    observer.observe(element);
  });
}

DrevOpsStatCounter();
