/*
 * ============================================================
 * DrevOps — Scroll Reveal
 * ============================================================
 *
 * IntersectionObserver that adds a .visible class to any
 * element with .component-reveal when it enters the viewport.
 * Stagger delays are handled via CSS (.component-reveal-d1 … .component-reveal-d6).
 *
 * ============================================================
 */
(function() {
  'use strict';

  var els = document.querySelectorAll('.component-reveal');
  if (!els.length) return;

  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  els.forEach(function(el) { observer.observe(el); });
})();
