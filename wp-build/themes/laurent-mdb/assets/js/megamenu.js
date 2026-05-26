/*
 * Maison de Bacon — mega-menu off-canvas (modèle Terre Blanche).
 *
 * Le bouton MENU de la barre ouvre un panneau latéral gauche (rubriques).
 * Survoler/cliquer une rubrique à sous-pages déploie un sous-panneau à droite
 * (sous-liens + image + CTA). Fermeture par croix, overlay, ou Escape.
 *
 * Vanilla JS, aucune dépendance.
 */
(function () {
	'use strict';

	if (typeof document === 'undefined') return;

	var panel = document.getElementById('mdb-panel');
	if (!panel) return;

	var body = document.body;
	var openers = document.querySelectorAll('[data-mdb-menu-open]');
	var closers = panel.querySelectorAll('[data-mdb-menu-close]');
	var sectionLinks = panel.querySelectorAll('[data-mdb-section]');
	var panes = panel.querySelectorAll('[data-mdb-pane]');
	var sub = panel.querySelector('[data-mdb-sub]');
	var activeSection = null;

	function showPane(id) {
		panes.forEach(function (pane) {
			pane.hidden = pane.getAttribute('data-mdb-pane') !== id;
		});
		sectionLinks.forEach(function (link) {
			var isActive = link.getAttribute('data-mdb-section') === id;
			link.classList.toggle('is-active', isActive);
			link.closest('.mdb-panel__item').classList.toggle('is-active', isActive);
		});
		if (sub) sub.classList.add('is-open');
		activeSection = id;
	}

	function openPanel() {
		panel.classList.add('is-open');
		panel.setAttribute('aria-hidden', 'false');
		body.classList.add('mdb-panel-open');
		openers.forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
		// Pré-affiche la première rubrique à sous-pages pour ne pas laisser le sous-panneau vide.
		if (!activeSection && sectionLinks.length) {
			showPane(sectionLinks[0].getAttribute('data-mdb-section'));
		}
	}

	function closePanel() {
		panel.classList.remove('is-open');
		panel.setAttribute('aria-hidden', 'true');
		body.classList.remove('mdb-panel-open');
		openers.forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
	}

	openers.forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			openPanel();
		});
	});

	closers.forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			closePanel();
		});
	});

	sectionLinks.forEach(function (link) {
		var id = link.getAttribute('data-mdb-section');
		// Tout au clic : 1er clic sur une rubrique ouvre son sous-panneau (sans
		// naviguer), 2e clic sur la rubrique déjà active navigue vers sa page.
		link.addEventListener('click', function (e) {
			if (activeSection !== id) {
				e.preventDefault();
				showPane(id);
			}
		});
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && panel.classList.contains('is-open')) {
			closePanel();
		}
	});
})();
