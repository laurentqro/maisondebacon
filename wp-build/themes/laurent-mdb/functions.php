<?php
/**
 * Maison de Bacon — thème enfant de Hello Elementor.
 *
 * Bootstrap : enqueue des styles, sticky CTA réservation, cap dimensions
 * images à l'upload, corrections sortie HTML pour SEO.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MDB_THEME_VERSION', '0.9.64' );
define( 'MDB_THEME_DIR', get_stylesheet_directory() );
define( 'MDB_THEME_URI', get_stylesheet_directory_uri() );

/**
 * Enqueue parent + child styles.
 *
 * Le parent Hello Elementor est minimal ; nos overrides passent en dernier
 * pour gagner la priorité CSS.
 */
add_action( 'wp_enqueue_scripts', function () {
	// Google Fonts — Cormorant Garamond (display), Tenor Sans (eyebrow), Inter Tight (body).
	wp_enqueue_style(
		'mdb-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400;1,500&family=Tenor+Sans&family=Inter+Tight:wght@300;400;500;600&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'mdb-parent',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme( get_template() )->get( 'Version' )
	);

	wp_enqueue_style(
		'mdb-tokens',
		MDB_THEME_URI . '/assets/css/tokens.css',
		array( 'mdb-parent' ),
		MDB_THEME_VERSION
	);

	wp_enqueue_style(
		'mdb-overrides',
		MDB_THEME_URI . '/assets/css/overrides.css',
		array( 'mdb-tokens' ),
		MDB_THEME_VERSION
	);

	wp_enqueue_script(
		'mdb-sticky-cta',
		MDB_THEME_URI . '/assets/js/sticky-cta.js',
		array(),
		MDB_THEME_VERSION,
		true
	);

	wp_enqueue_script(
		'mdb-megamenu',
		MDB_THEME_URI . '/assets/js/megamenu.js',
		array(),
		MDB_THEME_VERSION,
		true
	);
}, 20 );


/**
 * Le site est-il rendu en anglais ? (TranslatePress positionne la locale
 * courante à en_US sous /en/.) Sert à traduire le « chrome » géré par le thème.
 */
function mdb_is_en() {
	$loc = function_exists( 'get_locale' ) ? get_locale() : 'fr_FR';
	return strpos( $loc, 'en' ) === 0;
}

/**
 * URLs FR/EN de la PAGE COURANTE pour le sélecteur de langue du mega-menu.
 *
 * Demande à TranslatePress la version traduite de l'URL courante (et non
 * l'accueil). Fallback sûr ('/' et '/en/') si TRP est absent — ainsi le
 * sélecteur ne casse jamais, même hors contexte multilingue.
 */
function mdb_lang_switch_urls() {
	$fallback = array( 'fr' => home_url( '/' ), 'en' => home_url( '/en/' ) );

	if ( ! class_exists( 'TRP_Translate_Press' ) ) {
		return $fallback;
	}

	$trp = TRP_Translate_Press::get_trp_instance();
	if ( ! $trp ) {
		return $fallback;
	}

	$converter = $trp->get_component( 'url_converter' );
	if ( ! $converter || ! method_exists( $converter, 'get_url_for_language' ) ) {
		return $fallback;
	}

	$current = $converter->cur_page_url();

	return array(
		'fr' => $converter->get_url_for_language( 'fr_FR', $current ) ?: $fallback['fr'],
		'en' => $converter->get_url_for_language( 'en_US', $current ) ?: $fallback['en'],
	);
}

/**
 * Traduit un libellé du chrome (nav, boutons, footer) géré par le thème.
 *
 * Le contenu éditorial des pages reste traduit par TranslatePress ; ici on ne
 * couvre QUE les chaînes générées par le code, pour éviter les soucis
 * d'encodage (apostrophes, entités) côté dictionnaire TRP.
 */
function mdb_t( $fr ) {
	if ( ! mdb_is_en() ) {
		return $fr;
	}
	static $en = array(
		// Rubriques
		'Le Restaurant'              => 'The Restaurant',
		'Le Roof Top'                => 'The Roof Top',
		'La Carte'                   => 'The Menu',
		'Réceptions'                 => 'Private Events',
		'La Maison'                  => 'About Us',
		// Eyebrows
		'Les saveurs'                => 'Flavours',
		'Privatisation'              => 'Private hire',
		'Notre histoire'             => 'Our story',
		// Sous-liens
		'Notre carte'                => 'Our menu',
		"L'Esprit du midi"           => 'The Midday Spirit',
		"L'Esprit du soir"           => 'The Evening Spirit',
		'Tradition Bacon'            => 'The Bacon tradition',
		'Desserts'                   => 'Desserts',
		'Les En-K du bar'            => "The Bar's En-K",
		'Menu Fête des mères'        => "Mother's Day menu",
		'Vos événements'             => 'Your events',
		'Villa Les Roches de Bacon'  => 'Villa Les Roches de Bacon',
		"L'Appartement de Victor"    => "L'Appartement de Victor",
		'Le Chef Nicolas Davouze'    => 'The Chef Nicolas Davouze',
		'Presse'                     => 'Press',
		'Avis'                       => 'Reviews',
		'Contact'                    => 'Contact',
		// Boutons / divers
		'Menu'                       => 'Menu',
		'Réserver'                   => 'Book',
		'Carte cadeau'               => 'Gift card',
		'Nous trouver'               => 'Find us',
	);

	// Match direct.
	if ( isset( $en[ $fr ] ) ) {
		return $en[ $fr ];
	}

	// Tolérance : les titres saisis dans WP (menus, pages) utilisent souvent une
	// apostrophe typographique (’) et une casse variable (« Soir » vs « soir »).
	// On normalise l'apostrophe puis on tente une recherche insensible à la casse
	// pour que la traduction tienne quel que soit le libellé exact de l'éditrice.
	$norm = str_replace( array( '’', '&rsquo;' ), "'", $fr );
	if ( isset( $en[ $norm ] ) ) {
		return $en[ $norm ];
	}
	foreach ( $en as $key => $val ) {
		if ( strcasecmp( $key, $norm ) === 0 ) {
			return $val;
		}
	}

	return $fr;
}

/**
 * Structure du mega-menu off-canvas (modèle Terre Blanche).
 *
 * Chaque rubrique = un univers du lieu. `children` alimente le sous-panneau
 * droit (sous-pages + image d'ambiance + CTA). Éditer ici suffit à faire
 * évoluer le menu — aucune logique ailleurs.
 *
 * Filtrable via `mdb_megamenu` pour adaptation future (ACF, options…).
 */

/**
 * Emplacement de menu dédié au sous-panneau « La Carte » du mega-menu.
 * L'éditrice gère les liens via Apparence → Menus (menu « Mega-menu — La Carte »),
 * sans toucher au code. Voir mdb_megamenu_location_items().
 */
add_action( 'after_setup_theme', function () {
	register_nav_menus( array(
		'mdb_carte' => 'Mega-menu — La Carte',
	) );
} );

/**
 * Retourne les sous-liens (label + url) d'un emplacement de menu WordPress, au
 * format attendu par le sous-panneau du mega-menu. Renvoie un tableau vide si
 * aucun menu n'est assigné à l'emplacement (l'appelant utilise alors son
 * fallback codé en dur — rien ne casse tant que l'éditrice n'a pas créé le menu).
 *
 * On ne prend que les items de premier niveau (le sous-panneau est plat).
 */
function mdb_megamenu_location_items( $location ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return array();
	}

	$items = wp_get_nav_menu_items( $locations[ $location ] );
	if ( empty( $items ) ) {
		return array();
	}

	$children = array();
	foreach ( $items as $item ) {
		if ( (int) $item->menu_item_parent !== 0 ) {
			continue; // on ignore la sous-hiérarchie : sous-panneau plat
		}
		$children[] = array(
			'label' => $item->title,
			'url'   => $item->url,
		);
	}

	return $children;
}

function mdb_megamenu_data() {
	$base = 'https://staging.maisondebacon.fr';

	$menu = array(
		array(
			// Lien direct : la page Restaurant met déjà en avant ses menus et les
			// autres lieux ; un sous-panneau serait redondant.
			'label' => 'Le Restaurant',
			'url'   => $base . '/restaurant-de-bacon/',
		),
		array(
			// Lien direct (même logique que Le Restaurant).
			'label' => 'Le Roof Top',
			'url'   => $base . '/le-rooftop-club-bacon/',
		),
		array(
			// Hub des cartes : regroupe TOUS les menus, y compris la carte du bar.
			// Les sous-liens proviennent du menu WordPress « Mega-menu — La Carte »
			// (Apparence → Menus) si l'éditrice en a assigné un ; sinon on retombe
			// sur la liste codée en dur ci-dessous.
			'label'    => 'La Carte',
			'url'      => $base . '/notre-carte/',
			'eyebrow'  => 'Les saveurs',
			'image_id' => 109101,
			'children' => mdb_megamenu_location_items( 'mdb_carte' ) ?: array(
				array( 'label' => 'Notre carte', 'url' => $base . '/notre-carte/' ),
				array( 'label' => "L'Esprit du midi", 'url' => $base . '/lesprit-du-midi/' ),
				array( 'label' => "L'Esprit du soir", 'url' => $base . '/lesprit-du-soir/' ),
				array( 'label' => 'Tradition Bacon', 'url' => $base . '/tradition-bacon/' ),
				array( 'label' => 'Desserts', 'url' => $base . '/desserts/' ),
				array( 'label' => 'Les En-K du bar', 'url' => $base . '/les-en-k-du-bar/' ),
			),
		),
		array(
			// Privatisation : page devis + les 2 espaces privatisables.
			'label'    => 'Réceptions',
			'url'      => $base . '/vos-evenements/',
			'eyebrow'  => 'Privatisation',
			'image_id' => 109241,
			'children' => array(
				array( 'label' => 'Vos événements', 'url' => $base . '/vos-evenements/' ),
				array( 'label' => 'Villa Les Roches de Bacon', 'url' => $base . '/villa-les-roches-de-bacon/' ),
				array( 'label' => "L'Appartement de Victor", 'url' => $base . '/lappartement-de-victor/' ),
			),
		),
		array(
			'label'    => 'La Maison',
			'url'      => $base . '/nicolas-davouze/',
			'eyebrow'  => 'Notre histoire',
			'image_id' => 109528,
			'children' => array(
				array( 'label' => 'Le Chef Nicolas Davouze', 'url' => $base . '/nicolas-davouze/' ),
				array( 'label' => 'Presse', 'url' => $base . '/presse/' ),
				array( 'label' => 'Avis', 'url' => $base . '/avis/' ),
				array( 'label' => 'Contact', 'url' => $base . '/contact/' ),
			),
		),
	);

	return apply_filters( 'mdb_megamenu', $menu );
}

/**
 * Panneau off-canvas du mega-menu — injecté en bas du <body>.
 *
 * Deux colonnes : rubriques (gauche) + sous-panneau d'aperçu (droite) qui
 * change selon la rubrique active. Le JS gère l'ouverture et la bascule.
 */
add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}

	$menu        = mdb_megamenu_data();
	$reserve_url = apply_filters( 'mdb_reservation_url', 'https://bookings.zenchef.com/results?rid=354476&pid=1001' );
	$map_url     = 'https://www.google.com/maps/place/MAISON+DE+BACON/@43.5724048,7.1259668,4456m/data=!3m1!1e3!4m6!3m5!1s0x12cc2ad378559aab:0x90769c5d8f4a5c19!8m2!3d43.5695936!4d7.1391162!16s%2Fg%2F1tm1mnnz';

	// Liens FR/EN : version traduite de la PAGE COURANTE (via TranslatePress),
	// pas l'accueil. Fallback codé en dur si TRP indisponible.
	$lang = mdb_lang_switch_urls();
	?>
	<div class="mdb-panel" id="mdb-panel" aria-hidden="true">
		<div class="mdb-panel__overlay" data-mdb-menu-close></div>

		<aside class="mdb-panel__nav" role="dialog" aria-modal="true" aria-label="Menu principal">
			<div class="mdb-panel__head">
				<button type="button" class="mdb-panel__close" aria-label="Fermer le menu" data-mdb-menu-close>
					<span aria-hidden="true">&times;</span>
				</button>
				<div class="mdb-lang mdb-panel__lang">
					<a href="<?php echo esc_url( $lang['fr'] ); ?>" class="mdb-lang__item<?php echo mdb_is_en() ? '' : ' is-active'; ?>" hreflang="fr">FR</a>
					<span class="mdb-lang__sep">·</span>
					<a href="<?php echo esc_url( $lang['en'] ); ?>" class="mdb-lang__item<?php echo mdb_is_en() ? ' is-active' : ''; ?>" hreflang="en">EN</a>
				</div>
			</div>

			<p class="mdb-panel__eyebrow"><?php echo esc_html( mdb_t( 'Menu' ) ); ?></p>
			<ul class="mdb-panel__list">
				<?php foreach ( $menu as $i => $item ) : ?>
					<li class="mdb-panel__item<?php echo empty( $item['children'] ) ? '' : ' has-children'; ?>">
						<a href="<?php echo esc_url( $item['url'] ); ?>"
							class="mdb-panel__link"
							<?php if ( ! empty( $item['children'] ) ) : ?>
							data-mdb-section="<?php echo esc_attr( $i ); ?>"
							<?php endif; ?>>
							<span><?php echo esc_html( mdb_t( $item['label'] ) ); ?></span>
							<?php if ( ! empty( $item['children'] ) ) : ?>
								<span class="mdb-panel__chevron" aria-hidden="true">&rsaquo;</span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>

			<ul class="mdb-panel__secondary">
				<li><a href="https://maisondebacon.bonkdo.com/fr/" target="_blank" rel="noopener"><?php echo esc_html( mdb_t( 'Carte cadeau' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( $map_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( mdb_t( 'Nous trouver' ) ); ?></a></li>
			</ul>

			<div class="mdb-panel__foot">
				<a class="mdb-btn mdb-btn--solid mdb-btn--block" href="<?php echo esc_url( $reserve_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( mdb_t( 'Réserver' ) ); ?></a>
				<ul class="mdb-panel__social" aria-label="Réseaux sociaux">
					<li>
						<a href="https://www.facebook.com/maisondebacon" target="_blank" rel="noopener" aria-label="Facebook">
							<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07c0 6.02 4.39 11.02 10.13 11.93v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.09 24 18.09 24 12.07Z"/></svg>
						</a>
					</li>
					<li>
						<a href="https://www.instagram.com/maisondebacon/" target="_blank" rel="noopener" aria-label="Instagram">
							<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.72 3.72 0 0 1-1.38-.9 3.72 3.72 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16ZM12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.31-1.46.72-2.12 1.38C1.36 2.67.95 3.34.63 4.14.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.31.8.72 1.47 1.38 2.13.66.66 1.33 1.07 2.12 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56a5.88 5.88 0 0 0 2.13-1.38 5.88 5.88 0 0 0 1.38-2.13c.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91a5.88 5.88 0 0 0-1.38-2.12A5.88 5.88 0 0 0 19.86.63c-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0Zm0 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32ZM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm6.41-10.85a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88Z"/></svg>
						</a>
					</li>
				</ul>
			</div>
		</aside>

		<div class="mdb-panel__sub" data-mdb-sub>
			<?php foreach ( $menu as $i => $item ) : ?>
				<?php if ( empty( $item['children'] ) ) { continue; } ?>
				<div class="mdb-panel__pane" data-mdb-pane="<?php echo esc_attr( $i ); ?>" hidden>
					<button type="button" class="mdb-panel__pane-close" data-mdb-pane-close aria-label="Retour au menu">
						<span aria-hidden="true">&times;</span>
					</button>
					<?php if ( ! empty( $item['image_id'] ) ) : ?>
						<div class="mdb-panel__media">
							<?php
							// Taille 'large' + srcset (les originaux pèsent plusieurs Mo).
							echo wp_get_attachment_image(
								(int) $item['image_id'],
								'large',
								false,
								array( 'alt' => '', 'loading' => 'lazy' )
							);
							?>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $item['eyebrow'] ) ) : ?>
						<p class="mdb-panel__eyebrow"><?php echo esc_html( mdb_t( $item['eyebrow'] ) ); ?></p>
					<?php endif; ?>
					<ul class="mdb-panel__sublist">
						<?php foreach ( $item['children'] as $child ) : ?>
							<li>
								<a href="<?php echo esc_url( $child['url'] ); ?>" class="mdb-panel__sublink"><?php echo esc_html( mdb_t( $child['label'] ) ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}, 6 );

/**
 * Cap upload : redimensionne automatiquement toute image > 2400px (longueur max)
 * à l'upload. Solution au point #4 du brief (poids des images).
 *
 * S'exécute AVANT que WordPress ne crée ses tailles dérivées.
 */
add_filter( 'wp_handle_upload_prefilter', function ( $file ) {
	if ( empty( $file['type'] ) || strpos( $file['type'], 'image/' ) !== 0 ) {
		return $file;
	}

	$max_dim = (int) apply_filters( 'mdb_upload_max_dimension', 2400 );
	$quality = (int) apply_filters( 'mdb_upload_jpeg_quality', 82 );

	$editor = wp_get_image_editor( $file['tmp_name'] );
	if ( is_wp_error( $editor ) ) {
		return $file;
	}

	$size = $editor->get_size();
	if ( empty( $size['width'] ) || empty( $size['height'] ) ) {
		return $file;
	}

	if ( $size['width'] <= $max_dim && $size['height'] <= $max_dim ) {
		return $file;
	}

	$editor->resize( $max_dim, $max_dim, false );
	$editor->set_quality( $quality );
	$editor->save( $file['tmp_name'] );

	return $file;
} );

/**
 * Force jpeg_quality à 82% (par défaut WordPress = 82 mais certains plugins
 * peuvent l'avoir remonté). Cohérence avec mdb_upload_jpeg_quality.
 */
add_filter( 'jpeg_quality', function () { return 82; }, 99 );
add_filter( 'wp_editor_set_quality', function () { return 82; }, 99 );

/**
 * Désactive les emojis WordPress (gain de poids ~12KB JS + ~5 requêtes).
 */
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
} );

/**
 * Le header est position:fixed (transparent sur les heros). Les pages dont la
 * première section N'EST PAS un hero plein écran ont besoin d'un offset pour
 * que leur contenu ne passe pas sous le header. On pose une classe body sur
 * ces pages.
 *
 * Les pages "venue" (accueil + fiches lieux) ont un hero vidéo/image en tête
 * et n'ont donc PAS besoin de l'offset.
 */
add_filter( 'body_class', function ( $classes ) {
	$pages_with_hero = apply_filters(
		'mdb_pages_with_hero',
		array( 'restaurant-de-bacon', 'le-roof-top', 'le-rooftop-club-bacon', 'lappartement-de-victor', 'villa-les-roches-de-bacon' )
	);

	$has_hero = is_front_page()
		|| is_home()
		|| ( is_page() && in_array( get_post_field( 'post_name' ), $pages_with_hero, true ) );

	if ( ! $has_hero ) {
		$classes[] = 'mdb-needs-header-offset';
	}

	return $classes;
} );

/**
 * Permet de surcharger l'URL et le libellé du CTA réservation par page.
 * Le Roof Top a un compte Zenchef distinct (rid=367528).
 */
add_filter( 'mdb_reservation_url', function ( $url ) {
	if ( is_page( 'le-rooftop-club-bacon' ) ) {
		return 'https://bookings.zenchef.com/results?rid=367528';
	}
	return $url;
} );

/**
 * Localise en français les chaînes anglaises codées en dur du widget
 * événements ECTBE (plugin events-widgets-for-elementor-and-the-events-calendar).
 * Le site est en fr_FR (langue par défaut) mais le plugin n'embarque pas de
 * traduction FR pour ces libellés → ils s'affichaient en anglais sur la home FR
 * (« Find out more », « All day »). La version EN reste gérée par TranslatePress.
 */
add_filter( 'gettext_events-widgets-for-elementor-and-the-events-calendar', function ( $translated, $text ) {
	if ( mdb_is_en() ) {
		return $translated; // laisser TRP gérer l'anglais
	}
	$fr = array(
		'Find out more' => 'En savoir plus',
		'All day'       => 'Toute la journée',
	);
	return $fr[ $text ] ?? $translated;
}, 10, 2 );

/**
 * Hook réservé pour les corrections de hiérarchie de titres (point #3 du brief).
 * À implémenter quand on aura audité les pages page par page.
 */
// add_filter( 'the_content', 'mdb_normalize_headings', 99 );
