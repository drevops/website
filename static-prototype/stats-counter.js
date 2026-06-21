/*
 * ============================================================
 * DrevOps — Stats Count-Up Animation
 * ============================================================
 *
 * Animates .stat-count elements from 0 to their data-target
 * value when they scroll into view. Uses an easeOut curve
 * for a natural deceleration effect.
 *
 * Expects:
 *   <span class="stat-count" data-target="42">0</span>
 *
 * ============================================================
 */
(function() {
  'use strict';

  var started = {};

  function easeOut(t) {
    return 1 - Math.pow(1 - t, 3);
  }

  function countUp(el, target, duration) {
    var start = null;
    var startVal = 0;
    function step(ts) {
      if (!start) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      var current = Math.round(easeOut(progress) * (target - startVal) + startVal);
      el.textContent = current;
      if (progress < 1) requestAnimationFrame(step);
      else el.textContent = target;
    }
    requestAnimationFrame(step);
  }

  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting && !started[entry.target.dataset.idx]) {
        started[entry.target.dataset.idx] = true;
        var target = parseInt(entry.target.dataset.target, 10);
        var duration = target === 0 ? 600 : (target < 20 ? 1200 : 2000);
        countUp(entry.target, target, duration);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.4 });

  document.querySelectorAll('.stat-count').forEach(function(el, i) {
    el.dataset.idx = i;
    observer.observe(el);
  });
})();
