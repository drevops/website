/**
 * @file
 * CivicTheme Banner component.
 */

function CivicThemeBanner(el) {
  if (this.el || !el.classList.contains('ct-banner')) {
    return;
  }

  if (el.ctBanner) {
    return;
  }

  this.el = el;

  this.adjustTopOffset = this.adjustTopOffset.bind(this);
  this.adjustTopOffset();

  window.addEventListener('resize', this.adjustTopOffset);

  el.ctBanner = this;
}

/**
 * Adds header height to the banner top padding.
 */
CivicThemeBanner.prototype.adjustTopOffset = function () {
  const topOffsetElements = this.el.querySelectorAll('.ct-banner__inner__top-offset');

  if (topOffsetElements.length === 0) {
    return;
  }

  const header = document.querySelector('.ct-header');
  if (!header) {
    return;
  }

  // Get the actual height of the header.
  const headerHeight = header.offsetHeight;

  // Apply the header height as padding-top to each top-offset element.
  topOffsetElements.forEach((element) => {
    // Store original padding only once.
    if (!element.dataset.originalPaddingTop) {
      element.dataset.originalPaddingTop = getComputedStyle(element).paddingTop;
    }

    const originalPadding = parseFloat(element.dataset.originalPaddingTop) || 0;

    // Apply original padding + header height.
    element.style.paddingTop = `${originalPadding + headerHeight}px`;
  });
};

/**
 * Destroy an instance.
 */
CivicThemeBanner.prototype.destroy = function (el) {
  const instance = el.ctBanner;
  if (instance) {
    window.removeEventListener('resize', instance.adjustTopOffset);
  }
  delete el.ctBanner;
};

document.querySelectorAll('.ct-banner').forEach((el) => {
  new CivicThemeBanner(el);
});
