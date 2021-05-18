<?php
/**
 * Coletivo Theme Customizer.
 *
 * @package Coletivo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function coletivo_customize_register( $wp_customize ) {

	// Load custom controls.
	require_once get_template_directory() . '/inc/customizer-controls.php';

	// Remove default sections.
	$wp_customize->remove_section( 'colors' );
	$wp_customize->remove_section( 'background_image' );

	// Custom WP default control & settings.
	$wp_customize->get_section( 'title_tagline' )->title        = esc_html__( 'Site Title, Tagline & Logo', 'coletivo' );
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	/**
	 * Hook to add other customize
	 */
	do_action( 'coletivo_customize_before_register', $wp_customize );

	$pages           = get_pages();
	$option_pages    = array();
	$option_pages[0] = __( 'Select page', 'coletivo' );
	foreach ( $pages as $p ) {
		$option_pages[ $p->ID ] = $p->post_title;
	}
	$users           = get_users(
		array(
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'number'  => '',
		)
	);
	$option_users[0] = __( 'Select member', 'coletivo' );
	foreach ( $users as $user ) {
		$option_users[ $user->ID ] = $user->display_name;
	}

	/**
	 * Settings Components
	 */
	require_once get_template_directory() . '/inc/customizer-components/customizer-site-identity.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-site-options.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-global-settings.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-header.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-footer.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-blog-page-settings.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-hero-options.php';

	/**
	 * Sections Components
	 */
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-hero.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-features.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-slider.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-featured-page.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-services.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-portfolio.php';
	// Jetpack section.
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-video-lightbox.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-gallery.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-team.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-news.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-contact.php';
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-social.php';
	// Sections ordering.
	require_once get_template_directory() . '/inc/customizer-components/customizer-section-sections.php';

	/**
	 * Hook to add other customize
	 */
	do_action( 'coletivo_customize_after_register', $wp_customize );
}
add_action( 'customize_register', 'coletivo_customize_register' );

/**
* Selective refresh
*/
require_once get_template_directory() . '/inc/customizer-selective-refresh.php';

/**
 * Sanitize CSS code
 *
 * @param string $string The CSS code.
 *
 * @return string
 */
function coletivo_sanitize_css( $string ) {
	$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
	$string = wp_strip_all_tags( $string );
	return trim( $string );
}

/**
 * Sanitize color alpha
 *
 * @param string $color The color value.
 *
 * @return string
 */
function coletivo_sanitize_color_alpha( $color ) {
	$color = str_replace( '#', '', $color );
	if ( '' === $color ) {
		return '';
	}

	// 3 or 6 hex digits, or the empty string.
	if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', '#' . $color ) ) {
		// convert to rgb.
		$colour = $color;
		if ( 6 === strlen( $colour ) ) {
			list( $r, $g, $b ) = array( $colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5] );
		} elseif ( 3 === strlen( $colour ) ) {
			list( $r, $g, $b ) = array( $colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2] );
		} else {
			return false;
		}
		$r = hexdec( $r );
		$g = hexdec( $g );
		$b = hexdec( $b );
		return 'rgba( ' . join(
			',',
			array(
				'r' => $r,
				'g' => $g,
				'b' => $b,
				'a' => 1,
			)
		) . ')';

	}

	return false !== strpos( trim( $color ), 'rgb' ) ? $color : false;
}

/**
 * Sanitize repeatable data
 *
 * @param  string $input   The input string.
 * @param  object $setting $wp_customize.
 *
 * @return bool|mixed|string|void
 */
function coletivo_sanitize_repeatable_data_field( $input, $setting ) {
	$control = $setting->manager->get_control( $setting->id );

	$fields = $control->fields;
	if ( is_string( $input ) ) {
		$input = json_decode( wp_unslash( $input ), true );
	}
	$data = wp_parse_args( $input, array() );

	if ( ! is_array( $data ) ) {
		return false;
	}
	if ( ! isset( $data['_items'] ) ) {
		return false;
	}
	$data = $data['_items'];

	foreach ( $data as $i => $item_data ) {
		foreach ( $item_data as $id => $value ) {

			if ( isset( $fields[ $id ] ) ) {
				switch ( strtolower( $fields[ $id ]['type'] ) ) {
					case 'text':
						$data[ $i ][ $id ] = sanitize_text_field( $value );
						break;
					case 'textarea':
					case 'editor':
						$data[ $i ][ $id ] = wp_kses_post( $value );
						break;
					case 'color':
						$data[ $i ][ $id ] = sanitize_hex_color_no_hash( $value );
						break;
					case 'coloralpha':
						$data[ $i ][ $id ] = coletivo_sanitize_color_alpha( $value );
						break;
					case 'checkbox':
						$data[ $i ][ $id ] = coletivo_sanitize_checkbox( $value );
						break;
					case 'select':
						$data[ $i ][ $id ] = '';
						if ( is_array( $fields[ $id ]['options'] ) && ! empty( $fields[ $id ]['options'] ) ) {
							// if is multiple choices.
							if ( is_array( $value ) ) {
								foreach ( $value as $k => $v ) {
									if ( isset( $fields[ $id ]['options'][ $v ] ) ) {
										$value [ $k ] = $v;
									}
								}
								$data[ $i ][ $id ] = $value;
							} else { // is single choice.
								if ( isset( $fields[ $id ]['options'][ $value ] ) ) {
									$data[ $i ][ $id ] = $value;
								}
							}
						}

						break;
					case 'radio':
						$data[ $i ][ $id ] = sanitize_text_field( $value );
						break;
					case 'media':
						$value = wp_parse_args(
							$value,
							array(
								'url' => '',
								'id'  => false,
							)
						);

						$value['id']              = absint( $value['id'] );
						$data[ $i ][ $id ]['url'] = sanitize_text_field( $value['url'] );

						if ( wp_get_attachment_url( $value['id'] ) === $url ) {
							$data[ $i ][ $id ]['id']  = $value['id'];
							$data[ $i ][ $id ]['url'] = $url;
						} else {
							$data[ $i ][ $id ]['id'] = '';
						}

						break;
					default:
						$data[ $i ][ $id ] = wp_kses_post( $value );
				}
			} else {
				$data[ $i ][ $id ] = wp_kses_post( $value );
			}

			if ( count( $data[ $i ] ) !== count( $fields ) ) {
				foreach ( $fields as $k => $f ) {
					if ( ! isset( $data[ $i ][ $k ] ) ) {
						$data[ $i ][ $k ] = '';
					}
				}
			}
		}
	}

	return $data;
}

/**
 * Sanitize file URL.
 *
 * @param string $file_url The URL file.
 *
 * @return string
 */
function coletivo_sanitize_file_url( $file_url ) {
	$output   = '';
	$filetype = wp_check_filetype( $file_url );
	if ( $filetype['ext'] ) {
		$output = esc_url( $file_url );
	}
	return $output;
}

/**
 * Sanitize number
 *
 * @param int $input The input number.
 *
 * @return int
 */
function coletivo_sanitize_number( $input ) {
	return balanceTags( $input );
}

/**
 * Sanitize color
 *
 * @param string $color The color.
 *
 * @return string
 */
function coletivo_sanitize_hex_color( $color ) {
	if ( '' === $color ) {
		return '';
	}
	if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
		return $color;
	}
	return null;
}

/**
 * Sanitize checkbox
 *
 * @param bool $input The checkbox option.
 *
 * @return bool
 */
function coletivo_sanitize_checkbox( $input ) {
	if ( 1 === $input ) {
		return 1;
	} else {
		return 0;
	}
}

/**
 * Sanitize text
 *
 * @param string $string The input text.
 *
 * @return string
 */
function coletivo_sanitize_text( $string ) {
	return wp_kses_post( balanceTags( $string ) );
}

/**
 * Sanitize HTML
 *
 * @param string $string The URL.
 *
 * @return string
 */
function coletivo_sanitize_html_input( $string ) {
	return wp_kses_allowed_html( $string );
}

/**
 * Conditional to show more hero settings.
 *
 * @param string $control The option of Hero Fullscreen.
 *
 * @return bool
 */
function coletivo_hero_fullscreen_callback( $control ) {
	if ( $control->manager->get_setting( 'coletivo_hero_fullscreen' )->value() === '' ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Show on frontpage
 *
 * @return bool
 */
function coletivo_showon_frontpage() {
	return is_page_template( 'template-frontpage.php' );
}

/**
 * Show on frontpage when Jetpack is active
 *
 * @return bool
 */
function coletivo_is_jetpack_active() {
	return is_page_template( 'template-frontpage.php' ) && class_exists( 'Jetpack' );
}

/**
 * Remove deactivated sections.
 *
 * @param array $sections The array sections.
 *
 * @return array
 */
function coletivo_remove_deactivated_sections( $sections ) {
	if ( in_array( 'portfolio', $sections, true ) && ! coletivo_is_jetpack_active() ) {
		$key = array_search( 'portfolio', $sections, true );
		unset( $sections[ $key ] );
	}
	return $sections;
}
add_filter( 'coletivo_frontpage_sections_order', 'coletivo_remove_deactivated_sections', 9999 );

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function coletivo_customize_preview_js() {
	wp_enqueue_script( 'coletivo_customizer_liveview', get_template_directory_uri() . '/assets/js/customizer-liveview.js', array( 'customize-preview', 'customize-selective-refresh' ), VERSION, true );
}
add_action( 'customize_preview_init', 'coletivo_customize_preview_js', 65 );


/**
 * Customizer JS settings
 */
function coletivo_customize_js_settings() {
	if ( ! function_exists( 'coletivo_get_actions_required' ) ) {
		return;
	}
	$actions       = coletivo_get_actions_required();
	$n             = array_count_values( $actions );
	$number_action = 0;
	if ( $n && isset( $n['active'] ) ) {
		$number_action = $n['active'];
	}

	wp_localize_script(
		'customize-controls',
		'coletivo_customizer_settings',
		array(
			'number_action'     => $number_action,
			'is_plus_activated' => class_exists( 'coletivo_PLus' ) ? 'y' : 'n',
			'action_url'        => admin_url( 'themes.php?page=ft_coletivo&tab=actions_required' ),
		)
	);
}
add_action( 'customize_controls_enqueue_scripts', 'coletivo_customize_js_settings' );

/**
 * Customizer Icon picker
 */
function coletivo_customize_controls_enqueue_scripts() {
	wp_localize_script(
		'customize-controls',
		'C_Icon_Picker',
		apply_filters(
			'c_icon_picker_js_setup',
			array(
				'search' => esc_html__( 'Search', 'coletivo' ),
				'fonts'  => array(
					'font-awesome' => array(
						// Name of icon.
						'name'   => esc_html__( 'Font Awesome', 'coletivo' ),
						// prefix class example for font-awesome fa-fa-{name}.
						'prefix' => 'fa',
						// font url.
						'url'    => esc_url( add_query_arg( array( 'ver' => '4.7.0' ), get_template_directory_uri() . '/assets/css/font-awesome.min.css' ) ),
						// Icon class name, separated by |.
						'icons'  => 'fa-glass|fa-music|fa-search|fa-envelope-o|fa-heart|fa-star|fa-star-o|fa-user|fa-film|fa-th-large|fa-th|fa-th-list|fa-check|fa-times|fa-search-plus|fa-search-minus|fa-power-off|fa-signal|fa-cog|fa-trash-o|fa-home|fa-file-o|fa-clock-o|fa-road|fa-download|fa-arrow-circle-o-down|fa-arrow-circle-o-up|fa-inbox|fa-play-circle-o|fa-repeat|fa-refresh|fa-list-alt|fa-lock|fa-flag|fa-headphones|fa-volume-off|fa-volume-down|fa-volume-up|fa-qrcode|fa-barcode|fa-tag|fa-tags|fa-book|fa-bookmark|fa-print|fa-camera|fa-font|fa-bold|fa-italic|fa-text-height|fa-text-width|fa-align-left|fa-align-center|fa-align-right|fa-align-justify|fa-list|fa-outdent|fa-indent|fa-video-camera|fa-picture-o|fa-pencil|fa-map-marker|fa-adjust|fa-tint|fa-pencil-square-o|fa-share-square-o|fa-check-square-o|fa-arrows|fa-step-backward|fa-fast-backward|fa-backward|fa-play|fa-pause|fa-stop|fa-forward|fa-fast-forward|fa-step-forward|fa-eject|fa-chevron-left|fa-chevron-right|fa-plus-circle|fa-minus-circle|fa-times-circle|fa-check-circle|fa-question-circle|fa-info-circle|fa-crosshairs|fa-times-circle-o|fa-check-circle-o|fa-ban|fa-arrow-left|fa-arrow-right|fa-arrow-up|fa-arrow-down|fa-share|fa-expand|fa-compress|fa-plus|fa-minus|fa-asterisk|fa-exclamation-circle|fa-gift|fa-leaf|fa-fire|fa-eye|fa-eye-slash|fa-exclamation-triangle|fa-plane|fa-calendar|fa-random|fa-comment|fa-magnet|fa-chevron-up|fa-chevron-down|fa-retweet|fa-shopping-cart|fa-folder|fa-folder-open|fa-arrows-v|fa-arrows-h|fa-bar-chart|fa-twitter-square|fa-facebook-square|fa-camera-retro|fa-key|fa-cogs|fa-comments|fa-thumbs-o-up|fa-thumbs-o-down|fa-star-half|fa-heart-o|fa-sign-out|fa-linkedin-square|fa-thumb-tack|fa-external-link|fa-sign-in|fa-trophy|fa-github-square|fa-upload|fa-lemon-o|fa-phone|fa-square-o|fa-bookmark-o|fa-phone-square|fa-twitter|fa-facebook|fa-github|fa-unlock|fa-credit-card|fa-rss|fa-hdd-o|fa-bullhorn|fa-bell|fa-certificate|fa-hand-o-right|fa-hand-o-left|fa-hand-o-up|fa-hand-o-down|fa-arrow-circle-left|fa-arrow-circle-right|fa-arrow-circle-up|fa-arrow-circle-down|fa-globe|fa-wrench|fa-tasks|fa-filter|fa-briefcase|fa-arrows-alt|fa-users|fa-link|fa-cloud|fa-flask|fa-scissors|fa-files-o|fa-paperclip|fa-floppy-o|fa-square|fa-bars|fa-list-ul|fa-list-ol|fa-strikethrough|fa-underline|fa-table|fa-magic|fa-truck|fa-pinterest|fa-pinterest-square|fa-google-plus-square|fa-google-plus|fa-money|fa-caret-down|fa-caret-up|fa-caret-left|fa-caret-right|fa-columns|fa-sort|fa-sort-desc|fa-sort-asc|fa-envelope|fa-linkedin|fa-undo|fa-gavel|fa-tachometer|fa-comment-o|fa-comments-o|fa-bolt|fa-sitemap|fa-umbrella|fa-clipboard|fa-lightbulb-o|fa-exchange|fa-cloud-download|fa-cloud-upload|fa-user-md|fa-stethoscope|fa-suitcase|fa-bell-o|fa-coffee|fa-cutlery|fa-file-text-o|fa-building-o|fa-hospital-o|fa-ambulance|fa-medkit|fa-fighter-jet|fa-beer|fa-h-square|fa-plus-square|fa-angle-double-left|fa-angle-double-right|fa-angle-double-up|fa-angle-double-down|fa-angle-left|fa-angle-right|fa-angle-up|fa-angle-down|fa-desktop|fa-laptop|fa-tablet|fa-mobile|fa-circle-o|fa-quote-left|fa-quote-right|fa-spinner|fa-circle|fa-reply|fa-github-alt|fa-folder-o|fa-folder-open-o|fa-smile-o|fa-frown-o|fa-meh-o|fa-gamepad|fa-keyboard-o|fa-flag-o|fa-flag-checkered|fa-terminal|fa-code|fa-reply-all|fa-star-half-o|fa-location-arrow|fa-crop|fa-code-fork|fa-chain-broken|fa-question|fa-info|fa-exclamation|fa-superscript|fa-subscript|fa-eraser|fa-puzzle-piece|fa-microphone|fa-microphone-slash|fa-shield|fa-calendar-o|fa-fire-extinguisher|fa-rocket|fa-maxcdn|fa-chevron-circle-left|fa-chevron-circle-right|fa-chevron-circle-up|fa-chevron-circle-down|fa-html5|fa-css3|fa-anchor|fa-unlock-alt|fa-bullseye|fa-ellipsis-h|fa-ellipsis-v|fa-rss-square|fa-play-circle|fa-ticket|fa-minus-square|fa-minus-square-o|fa-level-up|fa-level-down|fa-check-square|fa-pencil-square|fa-external-link-square|fa-share-square|fa-compass|fa-caret-square-o-down|fa-caret-square-o-up|fa-caret-square-o-right|fa-eur|fa-gbp|fa-usd|fa-inr|fa-jpy|fa-rub|fa-krw|fa-btc|fa-file|fa-file-text|fa-sort-alpha-asc|fa-sort-alpha-desc|fa-sort-amount-asc|fa-sort-amount-desc|fa-sort-numeric-asc|fa-sort-numeric-desc|fa-thumbs-up|fa-thumbs-down|fa-youtube-square|fa-youtube|fa-xing|fa-xing-square|fa-youtube-play|fa-dropbox|fa-stack-overflow|fa-instagram|fa-flickr|fa-adn|fa-bitbucket|fa-bitbucket-square|fa-tumblr|fa-tumblr-square|fa-long-arrow-down|fa-long-arrow-up|fa-long-arrow-left|fa-long-arrow-right|fa-apple|fa-windows|fa-android|fa-linux|fa-dribbble|fa-skype|fa-foursquare|fa-trello|fa-female|fa-male|fa-gratipay|fa-sun-o|fa-moon-o|fa-archive|fa-bug|fa-vk|fa-weibo|fa-renren|fa-pagelines|fa-stack-exchange|fa-arrow-circle-o-right|fa-arrow-circle-o-left|fa-caret-square-o-left|fa-dot-circle-o|fa-wheelchair|fa-vimeo-square|fa-try|fa-plus-square-o|fa-space-shuttle|fa-slack|fa-envelope-square|fa-wordpress|fa-openid|fa-university|fa-graduation-cap|fa-yahoo|fa-google|fa-reddit|fa-reddit-square|fa-stumbleupon-circle|fa-stumbleupon|fa-delicious|fa-digg|fa-pied-piper-pp|fa-pied-piper-alt|fa-drupal|fa-joomla|fa-language|fa-fax|fa-building|fa-child|fa-paw|fa-spoon|fa-cube|fa-cubes|fa-behance|fa-behance-square|fa-steam|fa-steam-square|fa-recycle|fa-car|fa-taxi|fa-tree|fa-spotify|fa-deviantart|fa-soundcloud|fa-database|fa-file-pdf-o|fa-file-word-o|fa-file-excel-o|fa-file-powerpoint-o|fa-file-image-o|fa-file-archive-o|fa-file-audio-o|fa-file-video-o|fa-file-code-o|fa-vine|fa-codepen|fa-jsfiddle|fa-life-ring|fa-circle-o-notch|fa-rebel|fa-empire|fa-git-square|fa-git|fa-hacker-news|fa-tencent-weibo|fa-qq|fa-weixin|fa-paper-plane|fa-paper-plane-o|fa-history|fa-circle-thin|fa-header|fa-paragraph|fa-sliders|fa-share-alt|fa-share-alt-square|fa-bomb|fa-futbol-o|fa-tty|fa-binoculars|fa-plug|fa-slideshare|fa-twitch|fa-yelp|fa-newspaper-o|fa-wifi|fa-calculator|fa-paypal|fa-google-wallet|fa-cc-visa|fa-cc-mastercard|fa-cc-discover|fa-cc-amex|fa-cc-paypal|fa-cc-stripe|fa-bell-slash|fa-bell-slash-o|fa-trash|fa-copyright|fa-at|fa-eyedropper|fa-paint-brush|fa-birthday-cake|fa-area-chart|fa-pie-chart|fa-line-chart|fa-lastfm|fa-lastfm-square|fa-toggle-off|fa-toggle-on|fa-bicycle|fa-bus|fa-ioxhost|fa-angellist|fa-cc|fa-ils|fa-meanpath|fa-buysellads|fa-connectdevelop|fa-dashcube|fa-forumbee|fa-leanpub|fa-sellsy|fa-shirtsinbulk|fa-simplybuilt|fa-skyatlas|fa-cart-plus|fa-cart-arrow-down|fa-diamond|fa-ship|fa-user-secret|fa-motorcycle|fa-street-view|fa-heartbeat|fa-venus|fa-mars|fa-mercury|fa-transgender|fa-transgender-alt|fa-venus-double|fa-mars-double|fa-venus-mars|fa-mars-stroke|fa-mars-stroke-v|fa-mars-stroke-h|fa-neuter|fa-genderless|fa-facebook-official|fa-pinterest-p|fa-whatsapp|fa-server|fa-user-plus|fa-user-times|fa-bed|fa-viacoin|fa-train|fa-subway|fa-medium|fa-y-combinator|fa-optin-monster|fa-opencart|fa-expeditedssl|fa-battery-full|fa-battery-three-quarters|fa-battery-half|fa-battery-quarter|fa-battery-empty|fa-mouse-pointer|fa-i-cursor|fa-object-group|fa-object-ungroup|fa-sticky-note|fa-sticky-note-o|fa-cc-jcb|fa-cc-diners-club|fa-clone|fa-balance-scale|fa-hourglass-o|fa-hourglass-start|fa-hourglass-half|fa-hourglass-end|fa-hourglass|fa-hand-rock-o|fa-hand-paper-o|fa-hand-scissors-o|fa-hand-lizard-o|fa-hand-spock-o|fa-hand-pointer-o|fa-hand-peace-o|fa-trademark|fa-registered|fa-creative-commons|fa-gg|fa-gg-circle|fa-tripadvisor|fa-odnoklassniki|fa-odnoklassniki-square|fa-get-pocket|fa-wikipedia-w|fa-safari|fa-chrome|fa-firefox|fa-opera|fa-internet-explorer|fa-television|fa-contao|fa-500px|fa-amazon|fa-calendar-plus-o|fa-calendar-minus-o|fa-calendar-times-o|fa-calendar-check-o|fa-industry|fa-map-pin|fa-map-signs|fa-map-o|fa-map|fa-commenting|fa-commenting-o|fa-houzz|fa-vimeo|fa-black-tie|fa-fonticons|fa-reddit-alien|fa-edge|fa-credit-card-alt|fa-codiepie|fa-modx|fa-fort-awesome|fa-usb|fa-product-hunt|fa-mixcloud|fa-scribd|fa-pause-circle|fa-pause-circle-o|fa-stop-circle|fa-stop-circle-o|fa-shopping-bag|fa-shopping-basket|fa-hashtag|fa-bluetooth|fa-bluetooth-b|fa-percent|fa-gitlab|fa-wpbeginner|fa-wpforms|fa-envira|fa-universal-access|fa-wheelchair-alt|fa-question-circle-o|fa-blind|fa-audio-description|fa-volume-control-phone|fa-braille|fa-assistive-listening-systems|fa-american-sign-language-interpreting|fa-deaf|fa-glide|fa-glide-g|fa-sign-language|fa-low-vision|fa-viadeo|fa-viadeo-square|fa-snapchat|fa-snapchat-ghost|fa-snapchat-square|fa-pied-piper|fa-first-order|fa-yoast|fa-themeisle|fa-google-plus-official|fa-font-awesome|fa-handshake-o|fa-envelope-open|fa-envelope-open-o|fa-linode|fa-address-book|fa-address-book-o|fa-address-card|fa-address-card-o|fa-user-circle|fa-user-circle-o|fa-user-o|fa-id-badge|fa-id-card|fa-id-card-o|fa-quora|fa-free-code-camp|fa-telegram|fa-thermometer-full|fa-thermometer-three-quarters|fa-thermometer-half|fa-thermometer-quarter|fa-thermometer-empty|fa-shower|fa-bath|fa-podcast|fa-window-maximize|fa-window-minimize|fa-window-restore|fa-window-close|fa-window-close-o|fa-bandcamp|fa-grav|fa-etsy|fa-imdb|fa-ravelry|fa-eercast|fa-microchip|fa-snowflake-o|fa-superpowers|fa-wpexplorer|fa-meetup',
					),
				),
			)
		)
	);
}
add_action( 'customize_controls_enqueue_scripts', 'coletivo_customize_controls_enqueue_scripts' );

/**
 * Get customizer panel priority
 *
 * @param string $panel The customizer panel.
 *
 * @return int
 */
function coletivo_get_customizer_priority( $panel ) {
	$panel         = str_replace( array( 'coletivo_', '_panel' ), '', $panel );
	$section_order = get_theme_mod( 'coletivo_sections_order', 'hero,features,yourslider,featuredpage,services,portfolio,videolightbox,gallery,team,news,contact,social' );
	$index         = 129;
	$section_order = explode( ',', $section_order );
	foreach ( $section_order as $key => $value ) {
		$index++;
		if ( $panel === $value ) {
			break;
		}
	}
	return $index;
}
