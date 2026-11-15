/**
 * @file
 * Sends component-level interaction events to Google Analytics.
 * @param {object} Drupal
 * - The Drupal object.
 * @param {object} drupalSettings
 * - The Drupal settings object.
 * @param {function(string, string, (Document|Element)=): Element[]} once
 * - The once function.
 */

(function doAnalytics(Drupal, drupalSettings, once) {
  // Longest value Google Analytics 4 accepts for an event parameter.
  const MAX_LENGTH = 100;

  const CONTAINER = '[data-analytics-component]';

  const CONTROLS = [
    'a[href]',
    'button',
    'summary',
    '[role="button"]',
    '[role="tab"]',
    '[role="menuitem"]',
    'input[type="submit"]',
    'input[type="button"]',
    'input[type="reset"]',
    'input[type="checkbox"]',
    'input[type="radio"]',
  ].join(', ');

  const IGNORED = '[data-analytics-ignore], .contextual, .admin-toolbar, #toolbar-administration';

  const LIGHTBOX = '.photoswipe, [data-pswp-width]';

  const DOWNLOAD = /\.(pdf|docx?|xlsx?|pptx?|csv|txt|zip|gz|tar|rar|7z|json|xml|svg|png|jpe?g|gif|webp|mp3|mp4|mov)$/i;

  // Core marks the root of every rendered single-directory component.
  const SDC = '[data-component-id]';

  const HIDDEN_TEXT = '.ct-visually-hidden';

  const EMAIL = /[^\s@<>()]+@[^\s@<>()]+\.[a-z]{2,}/gi;

  const SCROLL_THRESHOLDS = [25, 50, 75, 100];

  const viewed = new WeakSet();

  let viewObservers = [];

  // Email addresses are personal information, which GA4 must not receive.
  function text(value) {
    return (value || '').replace(EMAIL, '[email]').replace(/\s+/g, ' ').trim().slice(0, MAX_LENGTH);
  }

  function send(name, params) {
    const payload = {};

    Object.keys(params).forEach((key) => {
      if (params[key] !== '' && params[key] !== null && params[key] !== undefined) {
        payload[key] = params[key];
      }
    });

    document.dispatchEvent(new CustomEvent('doanalytics', { detail: { name, params: payload } }));

    // The tag is only present for the visitors it is configured to measure.
    if (typeof window.gtag === 'function') {
      window.gtag('event', name, payload);
    }
  }

  function sameComponent(first, second) {
    return first.dataset.analyticsComponent === second.dataset.analyticsComponent && first.dataset.analyticsComponentId === second.dataset.analyticsComponentId;
  }

  // A template that includes a child component with its whole context repeats
  // the attributes on the child, so the outermost copy stands for both.
  function outermost(container) {
    let current = container;
    let next = current.parentElement ? current.parentElement.closest(CONTAINER) : null;

    while (next && sameComponent(next, current)) {
      current = next;
      next = current.parentElement ? current.parentElement.closest(CONTAINER) : null;
    }

    return current;
  }

  function containerOf(element) {
    const container = element.closest(CONTAINER);

    return container ? outermost(container) : null;
  }

  function parentOf(container) {
    return container.parentElement ? containerOf(container.parentElement) : null;
  }

  function position(container) {
    const parent = parentOf(container);
    const scope = parent || container.closest('[data-analytics-block]') || document;
    const siblings = Array.from(scope.querySelectorAll(CONTAINER)).filter((candidate) => outermost(candidate) === candidate && parentOf(candidate) === parent);

    return siblings.indexOf(container) + 1;
  }

  function sdcName(node) {
    return node.dataset.componentId.replace(/^[^:]*:/, '').replace(/-/g, '_');
  }

  // Returns the nearest component around an element and the first component
  // above it with a different name, such as 'link' and 'navigation'.
  function sdcNames(element) {
    const own = element.closest(SDC);

    if (!own) {
      return ['', ''];
    }

    let outer = own.parentElement ? own.parentElement.closest(SDC) : null;

    while (outer && sdcName(outer) === sdcName(own)) {
      outer = outer.parentElement ? outer.parentElement.closest(SDC) : null;
    }

    return [sdcName(own), outer ? sdcName(outer) : ''];
  }

  function pageContext() {
    const page = (drupalSettings.doAnalytics && drupalSettings.doAnalytics.page) || {};

    return {
      page_type: page.type || '',
      page_id: page.id || '',
    };
  }

  function context(element) {
    const container = containerOf(element);
    const parent = container ? parentOf(container) : null;
    const block = element.closest('[data-analytics-block]');
    const region = element.closest('[data-analytics-region]');
    const [own, outer] = sdcNames(element);

    return {
      component: container ? container.dataset.analyticsComponent : outer || own || 'page',
      component_id: container ? container.dataset.analyticsComponentId : '',
      component_title: container ? text(container.dataset.analyticsComponentTitle) : '',
      component_position: container ? position(container) : '',
      parent_component: parent ? parent.dataset.analyticsComponent : '',
      parent_component_id: parent ? parent.dataset.analyticsComponentId : '',
      region: region ? region.dataset.analyticsRegion : '',
      block: block ? block.dataset.analyticsBlock : '',
      ...pageContext(),
    };
  }

  function label(control) {
    if (control.matches('input[type="checkbox"], input[type="radio"]')) {
      return text(control.labels && control.labels.length ? control.labels[0].textContent : control.name);
    }

    if (control.matches('input')) {
      return text(control.value);
    }

    // Visible text goes before the title so that a hidden "opens in a new
    // window" suffix is not reported as the label of an icon link.
    const visible = control.cloneNode(true);

    visible.querySelectorAll(HIDDEN_TEXT).forEach((hidden) => hidden.remove());

    const image = control.querySelector('img[alt]');

    return text(
      control.getAttribute('aria-label') || text(visible.textContent) || control.getAttribute('title') || text(control.textContent) || (image ? image.alt : ''),
    );
  }

  // Email addresses, phone numbers and query strings are left out, as they can
  // identify a visitor.
  function target(control) {
    if (!control.matches('a[href]')) {
      return { link_type: 'button' };
    }

    const href = control.getAttribute('href');

    if (href.startsWith('#')) {
      return { link_type: 'anchor', link_url: text(href) };
    }

    let url;

    try {
      url = new URL(control.href, window.location.href);
    } catch {
      return { link_type: 'button' };
    }

    if (url.protocol === 'mailto:') {
      return { link_type: 'email' };
    }

    if (url.protocol === 'tel:') {
      return { link_type: 'phone' };
    }

    if (url.protocol !== 'http:' && url.protocol !== 'https:') {
      return { link_type: 'button' };
    }

    const internal = url.origin === window.location.origin;

    if (internal && url.hash && url.pathname === window.location.pathname) {
      return { link_type: 'anchor', link_url: text(url.hash) };
    }

    let type = internal ? 'internal' : 'external';

    if (control.matches(LIGHTBOX)) {
      type = 'lightbox';
    } else if (control.hasAttribute('download') || DOWNLOAD.test(url.pathname)) {
      type = 'download';
    }

    return {
      link_type: type,
      link_url: text(internal ? url.pathname : url.origin + url.pathname),
      link_domain: internal ? '' : url.hostname,
    };
  }

  function onClick(event) {
    // The clicked element is checked, as an ignored element can sit inside a
    // tracked link.
    if (!(event.target instanceof Element) || event.target.closest(IGNORED)) {
      return;
    }

    const control = event.target.closest(CONTROLS);

    if (!control) {
      return;
    }

    const destination = target(control);

    send('component_click', {
      ...context(control),
      element: sdcNames(control)[0],
      // The text of a phone link is usually the number itself.
      link_text: destination.link_type === 'phone' ? '' : label(control),
      ...destination,
    });
  }

  function onSubmit(event) {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.closest(IGNORED)) {
      return;
    }

    const formId = form.querySelector('input[name="form_id"]');

    send('component_form_submit', {
      ...context(form),
      form_id: text(form.dataset.analyticsForm || (formId ? formId.value : form.id)),
    });
  }

  // Only the length is sent: the copied text may be anything the visitor
  // selected.
  function onCopy() {
    const selection = window.getSelection();

    if (!selection || selection.isCollapsed || !selection.anchorNode) {
      return;
    }

    const node = selection.anchorNode;
    const element = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;

    if (!element || element.closest(IGNORED)) {
      return;
    }

    send('content_copy', {
      ...context(element),
      element: element.closest('pre, code') ? 'code' : sdcNames(element)[0],
      text_length: selection.toString().length,
    });
  }

  function markViewed(container) {
    if (viewed.has(container)) {
      return;
    }

    viewed.add(container);
    viewObservers.forEach((observer) => observer.unobserve(container));
    send('component_view', context(container));
  }

  function onHalfVisible(entries) {
    entries.filter((entry) => entry.isIntersecting && entry.intersectionRatio >= 0.5).forEach((entry) => markViewed(entry.target));
  }

  function onMiddleCrossed(entries) {
    entries.filter((entry) => entry.isIntersecting).forEach((entry) => markViewed(entry.target));
  }

  function observeViews(root) {
    if (!('IntersectionObserver' in window)) {
      return;
    }

    // A component crossing the middle of the viewport fills at least half of
    // it, so a component more than twice the viewport height is still viewed.
    if (!viewObservers.length) {
      viewObservers = [
        new IntersectionObserver(onHalfVisible, { threshold: 0.5 }),
        new IntersectionObserver(onMiddleCrossed, { rootMargin: '-50% 0px -50% 0px' }),
      ];
    }

    // An Ajax response can attach behaviors to a component itself, which a
    // selector search of its descendants does not return.
    const self = root instanceof Element && root.matches(CONTAINER) ? once('do-analytics-view', root) : [];

    self
      .concat(once('do-analytics-view', CONTAINER, root))
      .filter((container) => outermost(container) === container && !container.closest(IGNORED))
      .forEach((container) => viewObservers.forEach((observer) => observer.observe(container)));
  }

  function trackScroll() {
    const reached = [];
    let pending = false;

    function onScroll() {
      if (pending) {
        return;
      }

      pending = true;

      window.requestAnimationFrame(() => {
        pending = false;

        const scrollable = document.documentElement.scrollHeight - window.innerHeight;

        if (scrollable <= 0) {
          return;
        }

        const percent = Math.round((window.scrollY / scrollable) * 100);

        SCROLL_THRESHOLDS.filter((threshold) => percent >= threshold && !reached.includes(threshold)).forEach((threshold) => {
          reached.push(threshold);
          send('scroll_depth', { percent: threshold, ...pageContext() });
        });

        if (reached.length === SCROLL_THRESHOLDS.length) {
          window.removeEventListener('scroll', onScroll);
        }
      });
    }

    window.addEventListener('scroll', onScroll, { passive: true });
  }

  Drupal.behaviors.doAnalytics = {
    attach(root) {
      // Capture runs before component handlers that stop propagation.
      if (once('do-analytics', 'html').length) {
        document.addEventListener('click', onClick, true);
        document.addEventListener('submit', onSubmit, true);
        document.addEventListener('copy', onCopy, true);
        trackScroll();
      }

      observeViews(root);
    },
  };
})(Drupal, drupalSettings, once);
