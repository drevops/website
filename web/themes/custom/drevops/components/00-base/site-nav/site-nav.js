// phpcs:ignoreFile
/**
 * @file
 * Site navigation behaviour.
 *
 * Drives the mobile slide-in menu. The trigger toggles the menu open and
 * closed, body scroll is locked while the menu is open, and activating any
 * link inside the menu closes it so navigation feels immediate.
 *
 * The behaviour binds by id ('siteNav' and 'navToggle'); when either is
 * missing it is a no-op, so it can be attached globally ahead of the markup
 * that uses it.
 */
function DrevOpsSiteNav() {
  const nav = document.getElementById('siteNav');
  const toggle = document.getElementById('navToggle');

  if (!nav || !toggle || toggle.hasAttribute('data-site-nav-bound')) {
    return;
  }

  let previousBodyOverflow = '';

  const setOpen = (isOpen) => {
    if (isOpen) {
      previousBodyOverflow = document.body.style.overflow;
    }

    nav.classList.toggle('is-open', isOpen);
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    document.body.style.overflow = isOpen ? 'hidden' : previousBodyOverflow;
  };

  toggle.addEventListener('click', () => {
    setOpen(!nav.classList.contains('is-open'));
  });

  nav.querySelectorAll('.component-nav-links a').forEach((link) => {
    link.addEventListener('click', () => setOpen(false));
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && nav.classList.contains('is-open')) {
      setOpen(false);
      toggle.focus();
    }
  });

  toggle.setAttribute('data-site-nav-bound', '');
}

DrevOpsSiteNav();
