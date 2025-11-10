// phpcs:ignoreFile
/**
 * @file
 * CivicTheme Spotlight component.
 */
function CivicThemeSpotlight(el) {
  if (this.el || !el.classList.contains('ct-spotlight')) {
    return;
  }

  if (el.ctSpotlight) {
    return;
  }

  this.el = el;

  this.colCount = parseInt(el.getAttribute('data-grid-cols') || 1, 10);
  if (!this.colCount || this.colCount < 2) {
    return;
  }

  this.updateHeight = this.updateHeight.bind(this);
  this.updateHeight();

  el.ctSpotlight = this;
}

CivicThemeSpotlight.prototype.updateHeight = function () {
  const children = Array.from(this.el.children);
  if (children.length < 2) return;

  const row1Count = this.colCount;
  const row2Count = this.colCount - 1;

  const row1Items = children.slice(1, row1Count);
  const row2Items = children.slice(row1Count, row1Count + row2Count);

  const getMaxHeight = (elements) => elements.reduce((max, el) => Math.max(max, el.offsetHeight), 0);

  const row1Height = getMaxHeight(row1Items);
  const row2Height = getMaxHeight(row2Items);
  const totalHeight = row1Height + row2Height;

  children[0].style.height = `${totalHeight}px`;
};

CivicThemeSpotlight.prototype.destroy = function (el) {
  const instance = el.ctSpotlight;
  if (instance && instance.el && instance.el.children && instance.el.children[0]) {
    instance.el.children[0].removeAttribute('style');
  }

  delete el.ctSpotlight;
};

document.querySelectorAll('[data-grid]').forEach((el) => {
  // Delay initialisation if should be responsive.
  const breakpointExpr = el.getAttribute('data-responsive');
  if (breakpointExpr) {
    window.addEventListener('ct-responsive', (evt) => {
      evt.detail.evaluate(breakpointExpr, CivicThemeSpotlight, el);
    }, false);
    return;
  }

  new CivicThemeSpotlight(el);
});
