/*
 * Maison de Bacon — mega-menu off-canvas (modèle Terre Blanche).
 *
 * Le bouton MENU de la barre ouvre un panneau latéral gauche (rubriques).
 * Cliquer une rubrique à sous-pages affiche un sous-panneau à droite
 * (sous-liens + image + CTA). Fermeture par croix, overlay, ou Escape.
 *
 * Comportement identique desktop / mobile. Vanilla JS, aucune dépendance.
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
	var paneClosers = panel.querySelectorAll('[data-mdb-pane-close]');
	var activeSection = null;

	// Durée de la transition CSS d'un volet (transform), voir overrides.css.
	var PANE_SLIDE_MS = 420;
	var leaveTimer = null;

	// Marque la rubrique active dans la colonne de gauche (sans toucher aux volets).
	function markActiveSection(id) {
		sectionLinks.forEach(function (link) {
			var isActive = link.getAttribute('data-mdb-section') === id;
			link.classList.toggle('is-active', isActive);
			link.closest('.mdb-panel__item').classList.toggle('is-active', isActive);
		});
		activeSection = id;
	}

	function paneById(id) {
		for (var i = 0; i < panes.length; i++) {
			if (panes[i].getAttribute('data-mdb-pane') === id) return panes[i];
		}
		return null;
	}

	// Repli des volets dans leur état de repos (hors écran à gauche, cachés).
	function resetPanes() {
		clearTimeout(leaveTimer);
		panes.forEach(function (p) {
			p.classList.remove('is-current', 'is-leaving', 'is-entering');
			p.hidden = true;
		});
	}

	function showPane(id) {
		var next = paneById(id);
		if (!next) return;
		var current = null;
		panes.forEach(function (p) { if (p.classList.contains('is-current')) current = p; });

		var alreadyOpen = sub && sub.classList.contains('is-open');

		if (!alreadyOpen || !current) {
			// Premier affichage : le volet entre depuis la gauche. On le rend
			// visible à sa position de repos (hors écran), reflow, puis is-current
			// la frame suivante pour déclencher la transition (sinon il saute).
			clearTimeout(leaveTimer);
			if (sub) sub.classList.add('is-open');
			next.hidden = false;
			next.classList.remove('is-leaving', 'is-current');
			next.classList.add('is-entering');
			void next.offsetWidth;
			requestAnimationFrame(function () {
				next.classList.remove('is-entering');
				next.classList.add('is-current');
			});
			markActiveSection(id);
			return;
		}

		if (current === next) return; // déjà affiché

		// Changement de rubrique : les DEUX volets bougent EN MÊME TEMPS, même
		// couloir (vers la gauche). L'ancien sort par la gauche, le nouveau entre
		// depuis la gauche en le recouvrant → ils se croisent.
		clearTimeout(leaveTimer);

		// Volet entrant : positionné hors écran à gauche, prêt, AU-DESSUS.
		next.hidden = false;
		next.classList.remove('is-leaving', 'is-current');
		next.classList.add('is-entering');
		void next.offsetWidth; // reflow : fige la position de départ

		// Volet sortant : quitte l'état courant pour partir vers la gauche.
		current.classList.remove('is-current');
		current.classList.add('is-leaving');

		// Frame suivante : l'entrant prend la place (is-current → translateX(0)).
		requestAnimationFrame(function () {
			next.classList.remove('is-entering');
			next.classList.add('is-current');
		});

		markActiveSection(id);

		// Nettoyer le volet sortant une fois sa sortie finie.
		var leaving = current;
		leaveTimer = setTimeout(function () {
			leaving.classList.remove('is-leaving');
			leaving.hidden = true;
		}, PANE_SLIDE_MS + 60);
	}

	// Réinitialise le sous-panneau (rubrique active désélectionnée). Appelé à la
	// fermeture du panneau pour repartir d'un état propre à la réouverture.
	function closePane() {
		if (sub) sub.classList.remove('is-open');
		resetPanes();
		sectionLinks.forEach(function (link) {
			link.classList.remove('is-active');
			link.closest('.mdb-panel__item').classList.remove('is-active');
		});
		activeSection = null;
	}

	function openPanel() {
		panel.classList.add('is-open');
		panel.setAttribute('aria-hidden', 'false');
		body.classList.add('mdb-panel-open');
		openers.forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
		// Pas de pré-affichage : le sous-panneau reste masqué jusqu'au 1er clic
		// sur une rubrique.
	}

	function closePanel() {
		panel.classList.remove('is-open');
		panel.setAttribute('aria-hidden', 'true');
		body.classList.remove('mdb-panel-open');
		openers.forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
		closePane();
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

	// La croix du sous-panneau ramène à la liste des rubriques (ne ferme PAS tout
	// le panneau) — surtout sur mobile où le sous-panneau recouvre la liste.
	paneClosers.forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			closePane();
		});
	});

	sectionLinks.forEach(function (link) {
		var id = link.getAttribute('data-mdb-section');
		// Tout au clic : 1er clic sur une rubrique affiche son sous-panneau (sans
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
