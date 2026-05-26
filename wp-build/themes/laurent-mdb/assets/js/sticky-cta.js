/*
 * Maison de Bacon — sticky reservation CTA.
 *
 * Affiche le CTA flottant après que l'utilisateur a scrollé d'une fenêtre,
 * et le masque quand la section #reserver de la page (s'il y en a une) entre
 * dans le viewport — pour éviter le doublon visuel avec le CTA principal.
 *
 * Pas de dépendance jQuery / Elementor : vanilla JS, ~1KB.
 */
(function () {
	'use strict';

	if (typeof document === 'undefined') return;

	var cta = document.querySelector('[data-mdb-sticky-cta]');
	var body = document.body;

	var SHOW_AFTER_PX = window.innerHeight * 0.6;
	var ticking = false;

	function update() {
		ticking = false;
		var scrolled = window.scrollY || window.pageYOffset;

		// État scrollé du header (fond navy plein) — dès 30px.
		body.classList.toggle('mdb-scrolled', scrolled > 30);

		// Sticky CTA flottant — apparaît après ~60% de la fenêtre.
		if (cta) {
			cta.classList.toggle('is-visible', scrolled > SHOW_AFTER_PX);
		}
	}

	function onScroll() {
		if (!ticking) {
			window.requestAnimationFrame(update);
			ticking = true;
		}
	}

	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', function () {
		SHOW_AFTER_PX = window.innerHeight * 0.6;
	}, { passive: true });

	// Hide CTA when the in-page #reserver block is visible.
	var reserverBlock = document.getElementById('reserver') || document.querySelector('.book');
	if (reserverBlock && 'IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				document.body.classList.toggle('mdb-reserver-in-view', entry.isIntersecting);
			});
		}, { threshold: 0.2 });
		io.observe(reserverBlock);
	}

	// Masque aussi le CTA flottant quand le footer entre dans le viewport :
	// il chevauchait la barre légale, et « Réserver » est déjà dans la barre nav.
	var footer = document.querySelector('.elementor-108363, [data-elementor-type="footer"], footer');
	if (footer && 'IntersectionObserver' in window) {
		var ioFoot = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				document.body.classList.toggle('mdb-footer-in-view', entry.isIntersecting);
			});
		}, { threshold: 0 });
		ioFoot.observe(footer);
	}

	update();
})();
