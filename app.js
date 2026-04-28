/* Maison de Bacon — front-end behaviour, vanilla JS */
(() => {
  'use strict';

  /* ---------- nav scroll state ---------- */
  const nav = document.querySelector('[data-nav]');
  if (nav) {
    // Pages without a dark hero (data-nav="solid") keep the navy background at all times.
    const alwaysSolid = nav.getAttribute('data-nav') === 'solid';
    const onScroll = () => {
      nav.classList.toggle('is-scrolled', alwaysSolid || window.scrollY > 30);
    };
    onScroll();
    if (!alwaysSolid) window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------- mobile menu ---------- */
  const burger = document.querySelector('[data-burger]');
  const panel  = document.querySelector('[data-mobile-panel]');
  const panelClose = document.querySelector('[data-mobile-close]');
  const togglePanel = (open) => {
    if (!panel) return;
    panel.classList.toggle('is-open', open);
    document.documentElement.style.overflow = open ? 'hidden' : '';
  };
  burger && burger.addEventListener('click', () => togglePanel(true));
  panelClose && panelClose.addEventListener('click', () => togglePanel(false));
  panel && panel.querySelectorAll('a').forEach(a => a.addEventListener('click', () => togglePanel(false)));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') togglePanel(false); });

  /* ---------- reveal on scroll ---------- */
  const reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && reveals.length) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('is-in');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    reveals.forEach(el => io.observe(el));
  } else {
    reveals.forEach(el => el.classList.add('is-in'));
  }

  /* ---------- menu tabs (only on menu page) ---------- */
  const tabs = document.querySelectorAll('[data-tab]');
  const panels = document.querySelectorAll('[data-tab-panel]');
  if (tabs.length) {
    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const target = tab.getAttribute('data-tab');
        tabs.forEach(t => t.setAttribute('aria-selected', String(t === tab)));
        panels.forEach(p => {
          const match = p.getAttribute('data-tab-panel') === target;
          p.hidden = !match;
        });
      });
    });
  }

  /* ---------- year stamp ---------- */
  const yr = document.querySelector('[data-year]');
  if (yr) yr.textContent = String(new Date().getFullYear());

})();
