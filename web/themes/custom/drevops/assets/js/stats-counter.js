/**
 * @file
 * Stats count-up behaviour.
 *
 * Animates `.stat-count` elements from 0 to their `data-target` value when
 * they scroll into view, using an easeOut curve. Without JavaScript or an
 * IntersectionObserver the element keeps its server-rendered target value.
 *
 * Expects: <span class="stat-count" data-target="42">42</span>
 */
((Drupal, once) => {
  const easeOut = (t) => 1 - (1 - t) ** 3;

  const countUp = (el, target, duration) => {
    let start = null;

    const step = (timestamp) => {
      if (start === null) {
        start = timestamp;
      }

      const progress = Math.min((timestamp - start) / duration, 1);
      el.textContent = String(Math.round(easeOut(progress) * target));

      if (progress < 1) {
        window.requestAnimationFrame(step);
      } else {
        el.textContent = String(target);
      }
    };

    window.requestAnimationFrame(step);
  };

  Drupal.behaviors.drevopsStatsCounter = {
    attach(context) {
      const elements = once('dr-stat-count', '.stat-count', context);

      if (!elements.length || !('IntersectionObserver' in window)) {
        return;
      }

      const observer = new IntersectionObserver(
        (entries, obs) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) {
              return;
            }

            const el = entry.target;
            const target = parseInt(el.dataset.target, 10) || 0;
            const duration = target === 0 ? 600 : target < 20 ? 1200 : 2000;

            countUp(el, target, duration);
            obs.unobserve(el);
          });
        },
        { threshold: 0.4 },
      );

      elements.forEach((el) => observer.observe(el));
    },
  };
})(Drupal, once);
