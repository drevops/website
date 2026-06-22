// Mobile navigation toggle: opens/closes the slide-in menu and locks scroll.
(function () {
  var nav = document.getElementById('siteNav');
  var toggle = document.getElementById('navToggle');

  if (!nav || !toggle) {
    return;
  }

  function closeMenu() {
    nav.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  toggle.addEventListener('click', function () {
    var isOpen = nav.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });

  nav.querySelectorAll('.component-nav-links a').forEach(function (link) {
    link.addEventListener('click', closeMenu);
  });
})();
