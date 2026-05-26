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

define( 'MDB_THEME_VERSION', '0.5.3' );
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
 * Sticky reservation CTA — injecté en bas du <body> sur toutes les pages publiques.
 *
 * Cible : Zenchef restaurant. À adapter selon page (rooftop a un rid distinct).
 */
add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}

	$default_url = 'https://bookings.zenchef.com/results?rid=354476&pid=1001';
	$url         = apply_filters( 'mdb_reservation_url', $default_url );
	$label       = apply_filters( 'mdb_reservation_label', mdb_t( 'Réserver' ) );
	?>
	<a class="mdb-sticky-cta" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" data-mdb-sticky-cta>
		<span class="mdb-sticky-cta__label"><?php echo esc_html( $label ); ?></span>
		<span class="mdb-sticky-cta__arrow" aria-hidden="true">→</span>
	</a>
	<?php
}, 5 );

/**
 * Le site est-il rendu en anglais ? (TranslatePress positionne la locale
 * courante à en_US sous /en/.) Sert à traduire le « chrome » géré par le thème.
 */
function mdb_is_en() {
	$loc = function_exists( 'get_locale' ) ? get_locale() : 'fr_FR';
	return strpos( $loc, 'en' ) === 0;
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
		'Restaurant'                 => 'Restaurant',
		'Roof Top'                   => 'Roof Top',
		'Séjour'                     => 'Accommodation',
		'La Carte'                   => 'The Menu',
		'Événements'                 => 'Events',
		'La Maison'                  => 'About Us',
		// Eyebrows
		'La table'                   => 'The table',
		'Le bar'                     => 'The bar',
		"L'hébergement"              => 'Accommodation',
		'Les saveurs'                => 'Flavours',
		'Privatisation'              => 'Private hire',
		'Notre histoire'             => 'Our story',
		// Sous-liens
		'Restaurant de Bacon'        => 'Restaurant de Bacon',
		'La tradition Bacon'         => 'The Bacon tradition',
		"L'Esprit du midi"           => 'The Midday Spirit',
		"L'Esprit du soir"           => 'The Evening Spirit',
		'Le Rooftop Club Bacon'      => 'The Rooftop Club Bacon',
		'Les En-K du bar'            => "The Bar's En-K",
		"L'Appartement de Victor"    => "L'Appartement de Victor",
		'Villa Les Roches de Bacon'  => 'Villa Les Roches de Bacon',
		'Notre carte'                => 'Our menu',
		'Desserts'                   => 'Desserts',
		'Vos événements'             => 'Events',
		'Votre devis événementiel'   => 'Get a quote',  // libellé du bouton discover
		'Le Chef — Nicolas Davouze'  => 'The Chef — Nicolas Davouze',
		'Presse'                     => 'Press',
		'Contact'                    => 'Contact',
		// Boutons / divers
		'Menu'                       => 'Menu',
		'Réserver'                   => 'Book',
		'Carte cadeau'               => 'Gift card',
	);
	return isset( $en[ $fr ] ) ? $en[ $fr ] : $fr;
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
function mdb_megamenu_data() {
	$base = 'https://staging.maisondebacon.fr';

	$menu = array(
		array(
			'label'    => 'Restaurant',
			'url'      => $base . '/restaurant-de-bacon/',
			'eyebrow'  => 'La table',
			'image_id' => 109523,
			'children' => array(
				array( 'label' => 'Restaurant de Bacon', 'url' => $base . '/restaurant-de-bacon/' ),
				array( 'label' => "La tradition Bacon", 'url' => $base . '/tradition-bacon/' ),
				array( 'label' => "L'Esprit du midi", 'url' => $base . '/lesprit-du-midi/' ),
				array( 'label' => "L'Esprit du soir", 'url' => $base . '/lesprit-du-soir/' ),
			),
		),
		array(
			'label'    => 'Roof Top',
			'url'      => $base . '/le-rooftop-club-bacon/',
			'eyebrow'  => 'Le bar',
			'image_id' => 109265,
			'children' => array(
				array( 'label' => 'Le Rooftop Club Bacon', 'url' => $base . '/le-rooftop-club-bacon/' ),
				array( 'label' => "Les En-K du bar", 'url' => $base . '/les-en-k-du-bar/' ),
			),
		),
		array(
			'label'    => 'Séjour',
			'url'      => $base . '/lappartement-de-victor/',
			'eyebrow'  => "L'hébergement",
			'image_id' => 109241,
			'children' => array(
				array( 'label' => "L'Appartement de Victor", 'url' => $base . '/lappartement-de-victor/' ),
				array( 'label' => 'Villa Les Roches de Bacon', 'url' => $base . '/villa-les-roches-de-bacon/' ),
			),
		),
		array(
			'label'    => 'La Carte',
			'url'      => $base . '/notre-carte/',
			'eyebrow'  => 'Les saveurs',
			'image_id' => 109101,
			'children' => array(
				array( 'label' => 'Notre carte', 'url' => $base . '/notre-carte/' ),
				array( 'label' => 'Desserts', 'url' => $base . '/desserts/' ),
			),
		),
		array(
			// Pas de sous-menu : la rubrique navigue directement vers la page
			// Événements (le contenu d'un sous-panneau serait trop maigre).
			'label' => 'Événements',
			'url'   => $base . '/vos-evenements/',
		),
		array(
			'label'    => 'La Maison',
			'url'      => $base . '/nicolas-davouze/',
			'eyebrow'  => 'Notre histoire',
			'image_id' => 109528,
			'children' => array(
				array( 'label' => 'Le Chef — Nicolas Davouze', 'url' => $base . '/nicolas-davouze/' ),
				array( 'label' => 'Presse', 'url' => $base . '/presse/' ),
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
	?>
	<div class="mdb-panel" id="mdb-panel" aria-hidden="true">
		<div class="mdb-panel__overlay" data-mdb-menu-close></div>

		<aside class="mdb-panel__nav" role="dialog" aria-modal="true" aria-label="Menu principal">
			<div class="mdb-panel__head">
				<button type="button" class="mdb-panel__close" aria-label="Fermer le menu" data-mdb-menu-close>
					<span aria-hidden="true">&times;</span>
				</button>
				<div class="mdb-lang mdb-panel__lang">
					<a href="/" class="mdb-lang__item is-active" hreflang="fr">FR</a>
					<span class="mdb-lang__sep">·</span>
					<a href="/en/" class="mdb-lang__item" hreflang="en">EN</a>
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

			<div class="mdb-panel__foot">
				<a class="mdb-btn mdb-btn--ghost mdb-btn--block" href="https://maisondebacon.bonkdo.com/fr/" target="_blank" rel="noopener"><span class="mdb-btn__dot" aria-hidden="true"></span><?php echo esc_html( mdb_t( 'Carte cadeau' ) ); ?></a>
				<a class="mdb-btn mdb-btn--solid mdb-btn--block" href="<?php echo esc_url( $reserve_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( mdb_t( 'Réserver' ) ); ?></a>
			</div>
		</aside>

		<div class="mdb-panel__sub" data-mdb-sub>
			<?php foreach ( $menu as $i => $item ) : ?>
				<?php if ( empty( $item['children'] ) ) { continue; } ?>
				<div class="mdb-panel__pane" data-mdb-pane="<?php echo esc_attr( $i ); ?>" hidden>
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
		array( 'restaurant-de-bacon', 'le-rooftop-club-bacon', 'lappartement-de-victor', 'villa-les-roches-de-bacon' )
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
 * Hook réservé pour les corrections de hiérarchie de titres (point #3 du brief).
 * À implémenter quand on aura audité les pages page par page.
 */
// add_filter( 'the_content', 'mdb_normalize_headings', 99 );
