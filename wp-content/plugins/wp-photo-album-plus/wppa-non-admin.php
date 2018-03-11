<?php
/* wppa-non-admin.php
* Package: wp-photo-album-plus
*
* Contains all the non admin stuff
* Version 6.7.10
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

/* API FILTER and FUNCTIONS */
require_once 'wppa-filter.php';
require_once 'wppa-slideshow.php';
require_once 'wppa-functions.php';
require_once 'wppa-breadcrumb.php';
require_once 'wppa-album-covers.php';
require_once 'wppa-links.php';
require_once 'wppa-boxes-html.php';
require_once 'wppa-styles.php';
require_once 'wppa-cart.php';
require_once 'wppa-thumbnails.php';
require_once 'wppa-picture.php';
require_once 'wppa-tinymce-photo-front.php';

/* LOAD STYLESHEET */
add_action('wp_enqueue_scripts', 'wppa_add_style');

function wppa_add_style() {
global $wppa_api_version;

	// Do they need us anyway?
	if ( ! wppa_wppa_on_page() ) return;

	// Are we allowed to look in theme?
	if ( wppa_switch( 'use_custom_style_file' ) ) {

		// In child theme?
		$userstyle = get_theme_root() . '/' . get_option('stylesheet') . '/wppa-style.css';
		if ( is_file($userstyle) ) {
			wp_register_style('wppa_style', get_theme_root_uri() . '/' . get_option('stylesheet')  . '/wppa-style.css', array(), $wppa_api_version);
			wp_enqueue_style('wppa_style');
			return;
		}

		// In theme?
		$userstyle = get_theme_root() . '/' . get_option('template') . '/wppa-style.css';
		if ( is_file($userstyle) ) {
			wp_register_style('wppa_style', get_theme_root_uri() . '/' . get_option('template')  . '/wppa-style.css', array(), $wppa_api_version);
			wp_enqueue_style('wppa_style');
			return;
		}
	}

	// Use standard
	wp_register_style('wppa_style', WPPA_URL.'/theme/wppa-style.css', array(), $wppa_api_version);
	wp_enqueue_style('wppa_style');

	// Dynamic css
	if ( ! wppa_switch( 'inline_css' ) ) {
		if ( ! file_exists( WPPA_PATH.'/wppa-dynamic.css' ) ) {
			wppa_create_wppa_dynamic_css();
			update_option( 'wppa_dynamic_css_version', get_option( 'wppa_dynamic_css_version', '0' ) + '1' );
		}
		if ( file_exists( WPPA_PATH.'/wppa-dynamic.css' ) ) {
			wp_enqueue_style( 'wppa-dynamic', WPPA_URL.'/wppa-dynamic.css', array('wppa_style'), get_option( 'wppa_dynamic_css_version' ) );
		}
	}
}

/* SEO META TAGS AND SM SHARE DATA */
add_action('wp_head', 'wppa_add_metatags', 5);

function wppa_add_metatags() {
global $wpdb;

	// Share info for sm that uses og
	$id = wppa_get_get( 'photo' );
	if ( ! wppa_photo_exists( $id ) ) {
		$id = false;
	}
	if ( $id ) {

		// SM may not accept images from the cloud.
		wppa( 'for_sm', true );

		// SM does not want version numbers
		wppa( 'no_ver', true );

		$imgurl = wppa_get_photo_url( $id );
		wppa( 'no_ver', false );
		wppa( 'for_sm', false );
	}
	else {
		$imgurl = '';
		echo '<!-- WPPA+ No Photo id -->';
	}

	if ( $id ) {

		if ( wppa_switch( 'share_twitter' ) ) {
			$thumb = wppa_cache_thumb( $id );

			// Twitter wants at least 280px in width, and at least 150px in height
			if ( $thumb ) {
				$x = wppa_get_photo_item( $id, 'photox' );
				$y = wppa_get_photo_item( $id, 'photoy' );
			}
			if ( $thumb && $x >= 280 && $y >= 150 ) {
				$card = 'summary_large_image';
			}
			else {
				$card = 'summary';
			}
			$title  = wppa_get_photo_name( $id );
			$desc 	= wppa_get_og_desc( $id, 'short' );
			$url 	= ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$site   = get_bloginfo( 'name' );
			$creat 	= wppa_opt( 'twitter_account' );

				echo '
<!-- WPPA+ Twitter Share data -->
<meta name="twitter:card" content="' . $card . '">
<meta name="twitter:site" content="' . esc_attr( $site ) . '">
<meta name="twitter:title" content="' . esc_attr( sanitize_text_field( $title ) ) . '">
<meta name="twitter:text:description" content="' . esc_attr( sanitize_text_field( $desc ) ) . '">
<meta name="twitter:image" content="' . esc_url( $imgurl ) . '">';
if ( $creat ) {
	echo '
<meta name="twitter:creator" content="' . $creat . '">';
}
echo '
<!-- WPPA+ End Twitter Share data -->
';
		}

		if ( wppa_switch( 'og_tags_on' ) ) {
			$thumb = wppa_cache_thumb( $id );
			if ( $thumb ) {
				$title  = wppa_get_photo_name( $id );
				$desc 	= wppa_get_og_desc( $id );
				$url 	= ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				$url 	= wppa_convert_to_pretty( $url, false, true );
				$site   = get_bloginfo('name');
				$mime 	= wppa_get_mime_type( $id );
				echo '
<!-- WPPA+ Og Share data -->
<meta property="og:site_name" content="' . esc_attr( sanitize_text_field( $site ) ) . '" />
<meta property="og:type" content="article" />
<meta property="og:url" content="' . $url . '" />
<meta property="og:title" content="' . esc_attr( sanitize_text_field( $title ) ) . '" />';
if ( $mime ) {
	echo '
<meta property="og:image" content="' . esc_url( sanitize_text_field( $imgurl ) ) . '" />
<meta property="og:image:type" content="' . $mime . '" />
<meta property="og:image:width" content="' . wppa_get_photox( $id ) . '" />
<meta property="og:image:height" content="' . wppa_get_photoy( $id ) . '" />';
}
if ( $desc ) {
	echo '
<meta property="og:description" content="' . esc_attr( sanitize_text_field( $desc ) ) . '" />';
}
echo '
<!-- WPPA+ End Og Share data -->
';
			}
		}

	}

	// To make sure we are on a page that contains at least [wppa] we check for Get var 'wppa-album'.
	// This also narrows the selection of featured photos to those that exist in the current album.
	$done = array();
	$album = '';
	if ( isset( $_REQUEST['album'] ) ) $album = $_REQUEST['album'];
	elseif ( isset( $_REQUEST['wppa-album'] ) ) $album = $_REQUEST['wppa-album'];
	$album = strip_tags( $album );
	if ( strlen( $album == 12 ) ) $album = wppa_get_get( 'album' );

	if ( $album ) {
		if ( wppa_switch( 'meta_page' ) ) {
			$photos = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPPA_PHOTOS."` WHERE `album` = %s AND `status` = 'featured'", $album ), ARRAY_A );
			wppa_cache_photo( 'add', $photos );
			if ( $photos ) {
				echo("\n<!-- WPPA+ BEGIN Featured photos on this page -->");
				foreach ( $photos as $photo ) {
					$id 		= $photo['id'];
					$content 	= esc_attr( sanitize_text_field( wppa_get_keywords( $id ) ) );
					if ( $content && ! in_array( $content, $done ) ) {
						echo'
<meta name="keywords" content="'.$content.'" >';
						$done[] = $content;
					}
				}
				echo("\n<!-- WPPA+ END Featured photos on this page -->\n");
			}
		}
	}

	// No photo and no album, give the plain photo links of all featured photos
	elseif ( wppa_switch( 'meta_all' ) ) {
		$photos = $wpdb->get_results( "SELECT * FROM `".WPPA_PHOTOS."` WHERE `status` = 'featured'", ARRAY_A);
		wppa_cache_photo( 'add', $photos );
		if ( $photos ) {
			echo("\n<!-- WPPA+ BEGIN Featured photos on this site -->");
			foreach ( $photos as $photo ) {
				$thumb 		= $photo;	// Set to global to reduce queries when getting the name
				$id 		= $photo['id'];
				$content 	= esc_attr( sanitize_text_field( wppa_get_keywords( $id ) ) );
				if ( $content && ! in_array( $content, $done ) ) {
					echo '
<meta name="keywords" content="'.$content.'" >';
					$done[] = $content;
				}
			}
			echo("\n<!-- WPPA+ END Featured photos on this site -->\n");
		}
	}

	// Facebook Admin and App
	if ( ( wppa_switch( 'share_on' ) ||  wppa_switch( 'share_on_widget' ) ) &&
		( wppa_switch( 'facebook_comments' ) || wppa_switch( 'facebook_like' ) || wppa_switch( 'share_facebook' ) ) ) {
		echo("\n<!-- WPPA+ BEGIN Facebook meta tags -->");
		if ( wppa_opt( 'facebook_admin_id' ) ) {
			echo ("\n\t<meta property=\"fb:admins\" content=\"".wppa_opt( 'facebook_admin_id' )."\" />");
		}
		if ( wppa_opt( 'facebook_app_id' ) ) {
			echo ("\n\t<meta property=\"fb:app_id\" content=\"".wppa_opt( 'facebook_app_id' )."\" />");
		}
		if ( $imgurl ) {
			echo '
<link rel="image_src" href="'.esc_url( $imgurl ).'" />';
		}
		echo '
<!-- WPPA+ END Facebook meta tags -->
';
	}
}

/* LOAD SLIDESHOW, THEME, AJAX and LIGHTBOX js, all in one file nowadays */
add_action('wp_enqueue_scripts', 'wppa_add_javascripts');

function wppa_add_javascripts() {
global $wppa_api_version;
global $wppa_lang;
global $wppa_js_page_data_file;
global $wppa_opt;

	// Do they need us anyway?
	if ( ! wppa_wppa_on_page() ) return;

	$footer = ( wppa_switch( 'defer_javascript' ) );

	// If the user wants the js in the footer, try to open a tempfile to collect the js data during processing the page
	// If opening a tempfile fails, revert to js in the header.
	if ( $footer ) {
		$tempdir 	= WPPA_UPLOAD_PATH.'/temp';
		if ( ! is_dir( $tempdir ) ) @ wppa_mktree( $tempdir );
		wppa_delete_obsolete_tempfiles();

		$wppa_js_page_data_file = WPPA_UPLOAD_PATH . '/temp/wppa.' . sanitize_file_name( $_SERVER['REMOTE_ADDR'] ) . '.js';
		$handle = fopen ( $wppa_js_page_data_file, 'wb' );

		if ( $handle ) {
			fwrite( $handle, '/* WPPA+ Generated Page dependant javascript */'."\n" );
			fclose ( $handle );
		}
		else {
			$wppa_js_page_data_file = '';
			$footer = false;
		}
	}

	// WPPA+ Javascript files.
	// All wppa+ js files come in 2 flavours: the normal version and a minified version.
	// If the minified version is available, it will be loaded, else the normal version.
	// If you want to debug js, just delete the minified version; this will cause the normal
	// - readable - version to be loaded.

	$any_lightbox = ( wppa_opt( 'lightbox_name' ) == 'wppa' ) &&
					( wppa_switch( 'lightbox_global' ) ||
						in_array( 'lightbox', $wppa_opt ) ||
						in_array( 'lightboxsingle', $wppa_opt )
					);

	$js_files = array ( 'wppa',
						'wppa-slideshow',
						'wppa-ajax-front',
						'wppa-lightbox',
						'wppa-popup',
						'wppa-touch',
						'wppa-utils',
					);

	$js_dept1 = array( 'jquery', 'jquery-form', 'wppa-utils' );
	if ( wppa_switch( 'ajax_render_modal' ) || wppa_opt( 'upload_edit' ) == 'new' || wppa_switch( 'photo_shortcode_enabled' ) ) {
		$js_dept1[] = 'jquery-ui-dialog';
	}

	$js_depts = array ( $js_dept1,
						array( 'jquery' ),
						array( 'jquery' ),
						array( 'jquery' ),
						array( 'jquery' ),
						array( 'jquery' ),
						array( 'jquery' ),
					);

	$js_doits = array ( true,
						true,
						true,
						$any_lightbox,
						true,
						wppa_switch( 'slide_swipe') || $any_lightbox,
						true,
					);

	$js_footer = array ( $footer,
						 $footer,
						 $footer,
						 $footer,
						 $footer,
						 $footer,
						 $footer,
					);

	foreach ( array_keys( $js_files ) as $idx ) {
		if ( $js_doits[$idx] ) {
			if ( is_file( dirname( __FILE__ ) . '/js/' . $js_files[$idx] . '.min.js' ) ) {
				wp_enqueue_script( $js_files[$idx], WPPA_URL . '/js/' . $js_files[$idx] . '.min.js', $js_depts[$idx], $wppa_api_version, $js_footer[$idx] );
			}
			else {
				wp_enqueue_script( $js_files[$idx], WPPA_URL . '/js/' . $js_files[$idx] . '.js', $js_depts[$idx], $wppa_api_version, $js_footer[$idx] );
			}
		}
	}

	// google maps
	if ( ( wppa_switch( 'load_map_api' ) || wppa_opt( 'gpx_implementation' ) == 'wppa-plus-embedded' ) && strpos( wppa_opt( 'custom_content' ), 'w#location' ) !== false ) {
		if ( wppa_opt( 'map_apikey' ) ) {
			wp_enqueue_script( 'wppa-geo', 'https://maps.googleapis.com/maps/api/js?key='.wppa_opt( 'map_apikey' ).'&sensor=false', '', $wppa_api_version, $footer );
		}
		else {
			wp_enqueue_script( 'wppa-geo', 'https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false', '', $wppa_api_version, $footer );
		}
	}
	// wppa-init
	if ( ! file_exists( WPPA_PATH.'/wppa-init.'.$wppa_lang.'.js' ) ) {
		wppa_create_wppa_init_js();
		update_option( 'wppa_ini_js_version_'.$wppa_lang, get_option( 'wppa_ini_js_version_'.$wppa_lang, '0' ) + '1' );
	}
	if ( file_exists( WPPA_PATH.'/wppa-init.'.$wppa_lang.'.js' ) ) {
		wp_enqueue_script( 'wppa-init', WPPA_URL.'/wppa-init.'.$wppa_lang.'.js', array('wppa'), get_option( 'wppa_ini_js_version_'.$wppa_lang, $footer ) );
	}
	// wppa.pagedata
	if ( $footer ) {
		wp_enqueue_script( 'wppa-pagedata', WPPA_UPLOAD_URL.'/temp/wppa.'.$_SERVER['REMOTE_ADDR'].'.js', array('wppa-init'), rand(0,4711), $footer );
	}
}

/* LOAD WPPA+ THEME */
add_action('init', 'wppa_load_theme', 100);

function wppa_load_theme() {

	// Are we allowed to look in theme?
	if ( wppa_switch( 'use_custom_theme_file' ) ) {

		$usertheme = get_theme_root() . '/' . get_option('template') . '/wppa-theme.php';
		if ( is_file( $usertheme ) ) {
			require_once $usertheme;
			return;
		}
	}
	require_once 'theme/wppa-theme.php';
}

/* LOAD FOOTER REQD DATA */
add_action('wp_footer', 'wppa_load_footer');

function wppa_load_footer() {
global $wpdb;
global $wppa_session;

	echo '
		<!-- start WPPA+ Footer data -->
		';

	// Do they use our lightbox?
	if ( wppa_opt( 'lightbox_name' ) == 'wppa' ) {
		$fontsize_lightbox = wppa_opt( 'fontsize_lightbox' ) ? wppa_opt( 'fontsize_lightbox' ) : '10';
		$d = wppa_switch( 'ovl_show_counter') ? 1 : 0;
		$ovlh = wppa_opt( 'ovl_txt_lines' ) == 'auto' ? 'auto' : ((wppa_opt( 'ovl_txt_lines' ) + $d) * ($fontsize_lightbox + 2));
		$txtcol = wppa_opt( 'ovl_theme' ) == 'black' ? '#a7a7a7' : '#272727';
		$dark = wppa( 'is_mobile' ) ? '0.1' : '0.1';

		// The lightbox overlay background
		echo
		'<div' .
			' id="wppa-overlay-bg"' .
			' style="' .
				'text-align:center;' .
				'display:none;' .
				'position:fixed;' .
				'top:0;' .
				'left:0;' .
				'z-index:100090;' .
				'width:100%;' .
				'height:2048px;' .
				'background-color:'.wppa_opt( 'ovl_bgcolor' ).';' .
				'"' .
			' onclick="wppaOvlOnclick(event)"' .
			' >';

			// Display legenda
			if ( wppa_switch( 'ovl_show_legenda' ) && ! wppa( 'is_mobile' ) ) {
				echo
				'<div' .
					' id="wppa-ovl-legenda-1"' .
					' onmouseover="jQuery(this).css(\'visibility\',\'visible\');"' .
					' onmouseout="jQuery(this).css(\'visibility\',\'hidden\');"' .
					' style="' .
						'position:absolute;' .
						'left:0;' .
						'top:0;' .
						'background-color:'.wppa_opt( 'ovl_theme' ).';' .
						'color:'.$txtcol.';' .
						'visibility:visible;' .
						'"' .
					' >
					'.__( 'Press f for fullscreen.' , 'wp-photo-album-plus').'
				</div>';
			}

		// Close lightbox overlay background
		echo
		'</div>';

		// The Lightbox Image container
		echo
		'<div'.
			' id="wppa-overlay-ic"'.
			' style="' .
				'position:fixed;' .
				'top:50%;' .
				'left:50%;' .
				'z-index:100095;' .
				'opacity:1;' .
				'box-shadow:none;' .
				'box-sizing:content-box;' .
				'"' .
			' >' .
		'</div>';

		// The 'exit' icon
		echo
		'<div' .
			' id="wppa-exit-btn"' .
			' style="height:' . wppa_opt( 'ovl_fsx_btn_size' ) . 'px;z-index:100098;position:fixed;top:0;right:0;opacity:0.75;display:none;"' .
			' onclick="wppaOvlHide()"' .
			' onmouseover="if(wppaOvlOpen){jQuery(this).stop().fadeTo(300,1);}else{jQuery(this).stop().fadeOut(300);}"' .
			' onmouseout="if(wppaOvlOpen){jQuery(this).stop().fadeTo(300,' . $dark . ');}else{jQuery(this).stop().fadeOut(300);}"' .
			' >' .
			wppa_get_svghtml( 'Exit', wppa_opt( 'ovl_fsx_btn_size' ) . 'px', true, true, '0', '0', '0', '0' ) .
		'</div>' .
		'<script>' .
			'jQuery(\'#wppa-exit-btn\').on(\'touchstart\',function(){jQuery(\'#wppa-exit-btn\').stop().fadeTo(300,1);});' .
			'jQuery(\'#wppa-exit-btn\').on(\'touchend\',function(){jQuery(\'#wppa-exit-btn\').stop().fadeTo(300,' . $dark . ');});' .
		'</script>';

		// The 'fullscreen' icon
		echo
		'<div' .
			' id="wppa-fulls-btn"' .
			' style="height:' . wppa_opt( 'ovl_fsx_btn_size' ) . 'px;z-index:100098;position:fixed;top:0;right:' . wppa_opt( 'ovl_fsx_btn_size' ) . 'px;opacity:0.75;display:none;"' .
			' onclick="wppaOvlFull()"' .
			' onmouseover="jQuery(this).stop().fadeTo(300,1);"' .
			' onmouseout="jQuery(this).stop().fadeTo(300,' . $dark . ');"' .
			' >' .
			wppa_get_svghtml( 'Full-Screen', wppa_opt( 'ovl_fsx_btn_size' ) . 'px', true, true, '0', '0', '0', '0' ) .
		'</div>' .
		'<script>' .
			'jQuery(\'#wppa-fulls-btn\').on(\'touchstart\',function(){jQuery(\'#wppa-fulls-btn\').stop().fadeTo(300,1);});' .
			'jQuery(\'#wppa-fulls-btn\').on(\'touchend\',function(){jQuery(\'#wppa-fulls-btn\').stop().fadeTo(300,' . $dark . ');});' .
		'</script>';

		// The Spinner image
		switch( wppa_opt( 'icon_corner_style' ) ) {
			case 'gif':
			case 'none':
				$bradius = '0';
				break;
			case 'light':
				$bradius = '12';
				break;
			case 'medium':
				$bradius = '24';
				break;
			case 'heavy':
				$bradius = '60';
				break;
		}

		if ( wppa_use_svg() ) {
			echo
			'<svg' .
				' id="wppa-ovl-spin"' .
				' width="120px"' .
				' height="120px"' .
				' xmlns="http://www.w3.org/2000/svg"' .
				' viewBox="0 0 100 100"' .
				' preserveAspectRatio="xMidYMid"' .
				' style="' .
					'width:120px;' .
					'height:120px;' .
					'position:fixed;' .
					'top:50%;' .
					'margin-top:-60px;' .
					'left:50%;' .
					'margin-left:-60px;' .
					'z-index:100100;' .
					'opacity:1;' .
					'display:none;' .
					'fill:' . wppa_opt( 'ovl_svg_color' ) . ';' .
					'background-color:' . wppa_opt( 'ovl_svg_bg_color' ) . ';' .
					'box-shadow:none;' .
					'border-radius:' . $bradius . 'px;' .
					'"' .				' >' .
				wppa_get_spinner_svg_body_html() .
			'</svg>';
		}
		else {
			echo
			'<img' .
				' id="wppa-ovl-spin"' .
				' alt="spinner"' .
				( wppa_use_svg() ? ' class="wppa-svg"' : '' ) .
				' style="' .
					'width:120px;' .
					'height:120px;' .
					'position:fixed;' .
					'top:50%;' .
					'margin-top:-60px;' .
					'left:50%;' .
					'margin-left:-60px;' .
					'z-index:100100;' .
					'opacity:1;' .
					'display:none;' .
					'fill:' . wppa_opt( 'ovl_svg_color' ) . ';' .
					'background-color:' . wppa_opt( 'ovl_svg_bg_color' ) . ';' .
					'box-shadow:none;' .
					'border-radius:' . $bradius . 'px;' .
					'"' .
				' src="' . wppa_get_imgdir() . ( wppa_use_svg() ? 'loader.svg' : 'loader.gif' ) . '"' .
			' />';
		}

		// The init vars
		echo '
		<script type="text/javascript">
			jQuery("#wppa-overlay-bg").css({height:window.innerHeight});
			wppaOvlModeInitial = "'.( wppa( 'is_mobile' ) ? wppa_opt( 'ovl_mode_initial_mob' ) : wppa_opt( 'ovl_mode_initial' ) ).'";
			wppaOvlTxtHeight = "'.$ovlh.'";
			wppaOvlOpacity = '.(wppa_opt( 'ovl_opacity' )/100).';
			wppaOvlOnclickType = "'.wppa_opt( 'ovl_onclick' ).'";
			wppaOvlTheme = "'.wppa_opt( 'ovl_theme' ).'";
			wppaOvlAnimSpeed = '.wppa_opt( 'ovl_anim' ).';
			wppaOvlSlideSpeed = '.wppa_opt( 'ovl_slide' ).';
			wppaVer4WindowWidth = 800;
			wppaVer4WindowHeight = 600;
			wppaOvlShowCounter = '.( wppa_switch( 'ovl_show_counter') ? 'true' : 'false' ).';
			'.( wppa_opt( 'fontfamily_lightbox' ) ? 'wppaOvlFontFamily = "'.wppa_opt( 'fontfamily_lightbox' ).'"' : '').'
			wppaOvlFontSize = "'.$fontsize_lightbox.'";
			'.( wppa_opt( 'fontcolor_lightbox' ) ? 'wppaOvlFontColor = "'.wppa_opt( 'fontcolor_lightbox' ).'"' : '').'
			'.( wppa_opt( 'fontweight_lightbox' ) ? 'wppaOvlFontWeight = "'.wppa_opt( 'fontweight_lightbox' ).'"' : '').'
			'.( wppa_opt( 'fontsize_lightbox' ) ? 'wppaOvlLineHeight = "'.(wppa_opt( 'fontsize_lightbox' ) + '2').'"' : '').'
			wppaOvlFullLegenda = "'.__('Keys: f = next mode; q,x = exit; p = previous, n = next, s = start/stop, d = dismiss this notice.', 'wp-photo-album-plus').'";
			wppaOvlFullLegendaSingle = "'.__('Keys: f = next mode; q,x = exit; d = dismiss this notice.', 'wp-photo-album-plus').'";
			wppaOvlVideoStart = '.( wppa_switch( 'ovl_video_start' ) ? 'true' : 'false' ).';
			wppaOvlAudioStart = '.( wppa_switch( 'ovl_audio_start' ) ? 'true' : 'false' ).';
			wppaOvlShowLegenda = '.( wppa_switch( 'ovl_show_legenda' ) && ! wppa( 'is_mobile' ) ? 'true' : 'false' ).';
			wppaOvlShowStartStop = '.( wppa_switch( 'ovl_show_startstop' ) ? 'true' : 'false' ).';
			wppaToggleFullScreen = "'. __( 'Toggle fullscreen', 'wp-photo-album-plus' ) . '";
			wppaIsMobile = '.( wppa_is_mobile() ? 'true' : 'false' ).';
			wppaOvlFsExitBtnSize = "'.wppa_opt( 'ovl_fsx_btn_size' ).'";
		</script>
		';
	}

	// The photo views cache
	echo '
	<script type="text/javascript" >';
		if ( isset( $wppa_session['photo'] ) ) {
			foreach ( array_keys( $wppa_session['photo'] ) as $p ) {
				echo '
				wppaPhotoView['.$p.'] = true;';
			}
		}
	echo '
	</script>';

	echo '
<!-- end WPPA+ Footer data -->
';

	// Debugging, show queries
	wppa_dbg_cachecounts('print');

	// Debugging, show active plugins
	if ( wppa( 'debug' ) ) {
		$plugins = get_option('active_plugins');
		wppa_dbg_msg('Active Plugins');
		foreach ( $plugins as $plugin ) {
			wppa_dbg_msg($plugin);
		}
		wppa_dbg_msg('End Active Plugins');
	}

	echo '
<!-- Nonce for various wppa actions -->';
	// Nonce field for Ajax bump view counter from lightbox, and rating
	wp_nonce_field( 'wppa-check', 'wppa-nonce', false, true );
	wp_nonce_field( 'wppa-qr-nonce', 'wppa-qr-nonce', false, true );

	echo '
<!-- Do user upload -->';
	// Do the upload if required and not yet done
	wppa_user_upload();

	// Done
	echo '
<!-- Done user upload -->';
}

/* FACEBOOK COMMENTS */
add_action('wp_footer', 'wppa_fbc_setup', 100);

function wppa_fbc_setup() {
global $wppa_locale;

	if ( wppa_switch( 'load_facebook_sdk' ) &&  			// Facebook sdk requested
		( 	wppa_switch( 'share_on' ) ||
			wppa_switch( 'share_on_widget' ) ||
			wppa_switch( 'share_on_thumbs' ) ||
			wppa_switch( 'share_on_lightbox' ) ||
			wppa_switch( 'share_on_mphoto' ) ) &&
		(	wppa_switch( 'share_facebook' ) ||
			wppa_switch( 'facebook_like' ) ||
			wppa_switch( 'facebook_comments' ) )			// But is it used by wppa?
	) {
		?>
		<!-- Facebook Comments for WPPA+ -->
		<div id="fb-root"></div>
		<script>(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/<?php echo $wppa_locale; ?>/all.js#xfbml=1";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
		</script>
	<?php
	}
}

/* CHECK REDIRECTION */
add_action( 'plugins_loaded', 'wppa_redirect', '1' );

function wppa_redirect() {

	// Do we come from a login form ?
	/*
	if ( isset( $_POST['redirect_to'] ) ) {
		wppa_log( 'obs', 'Redirect to: ' . $_POST['redirect_to'] );
		wp_redirect( $_POST['redirect_to'] );
	}
	*/

	if ( ! isset( $_SERVER["REQUEST_URI"] ) ) return;

	$uri = $_SERVER["REQUEST_URI"];
	$wppapos = stripos( $uri, '/wppaspec/' );

	if ( $wppapos && get_option('permalink_structure') ) {

		// old style solution, still required when qTranslate is active
		$plugins = implode( ',', get_option( 'active_plugins' ) );
		if ( stripos( $plugins, 'qtranslate' ) !== false ) {

			$newuri = wppa_convert_from_pretty( $uri );
			if ( $newuri == $uri ) return;

			// Although the url is urlencoded it is damaged by wp_redirect when it contains chars like ë, so we do a header() call
			header( 'Location: '.$newuri, true, 302 );
			exit;
		}

		// New style solution
		$newuri = wppa_convert_from_pretty($uri);
		if ( $newuri == $uri ) return;
		$_SERVER["REQUEST_URI"] = $newuri;
		wppa_convert_uri_to_get( $newuri );
	}
}

/* ADD PAGE SPECIFIC ( http or https ) URLS */
add_action( 'wp_head', 'wppa_add_page_specific_urls', '99' );

function wppa_add_page_specific_urls() {

	$result = '
<!-- WPPA+ BEGIN Page specific urls and device specific data -->
<script type="text/javascript">
	wppaImageDirectory = "' . wppa_get_imgdir() . '";
	wppaWppaUrl = "' . wppa_get_wppa_url() . '";
	wppaIncludeUrl = "' . trim( includes_url(), '/' ) . '";
	wppaAjaxUrl = "' . ( wppa_switch( 'ajax_non_admin' ) ? wppa_url( 'wppa-ajax-front.php' ) : admin_url( 'admin-ajax.php' ) ) . '";
	wppaUploadUrl = "' . WPPA_UPLOAD_URL . '";
	wppaIsIe = ' . ( wppa_is_ie() ? 'true' : 'false' ) . ';
	wppaIsSafari = ' . ( wppa_is_safari() ? 'true' : 'false' ) . ';
	wppaUseSvg = ' . ( wppa_use_svg() ? 'true' : 'false' ) . ';
	wppaSlideshowNavigationType = "' . wppa_get_navigation_type() . '";
	wppaAudioHeight = '.wppa_get_audio_control_height().';
</script>
<!-- WPPA+ END Page specific urls -->';

	// Relative urls?
	$result = wppa_make_relative( $result );

	echo $result;

}

/* ENABLE RENDERING */
add_action( 'wp_head', 'wppa_kickoff', '100' );

function wppa_kickoff() {
global $wppa_lang;
global $wppa_api_version;
global $wppa_init_js_data;
global $wppa_dynamic_css_data;

	// init.css failed?
	if ( $wppa_dynamic_css_data ) echo $wppa_dynamic_css_data;

	// init.js failed?
	if ( $wppa_init_js_data ) echo $wppa_init_js_data;
/* Obsolete?
	// Patch for chrome or Edge?
	// Test for chrome needs also test for NOT Edge, because browser signature of Edge also reports that it is chrome(-like)
	if ( false && isset($_SERVER["HTTP_USER_AGENT"] ) ) {
		echo '

<!-- WPPA+ Kickoff -->
<!-- Browser detected = '.wppa_decode_uri_component(strip_tags($_SERVER["HTTP_USER_AGENT"])).' -->';
		if ( strstr($_SERVER["HTTP_USER_AGENT"], 'Chrome') && ! strstr($_SERVER["HTTP_USER_AGENT"], 'Edge') && wppa_switch( 'ovl_chrome_at_top') ) echo '
<style type="text/css">
	#wppa-overlay-ic { padding-top: 5px !important; }
	#wppa-overlay-qt-img { top: 5px !important; }
</style>';
		if ( strstr($_SERVER["HTTP_USER_AGENT"], 'Edge') ) echo '
<style type="text/css">
	#wppa-overlay-ic { padding-top: 0px !important; }
	#wppa-overlay-qt-img { top: 5px !important; }
</style>';
	}
*/
	// Inline styles?
	if ( wppa_switch( 'inline_css') ) {
		echo '
<!-- WPPA+ Custom styles -->
<style type="text/css" >';
		if ( ! wppa_switch( 'ovl_fs_icons' ) ) {
			echo '#wppa-norms-btn, #wppa-fulls-btn { display:none; }';
		}
		echo wppa_opt( 'custom_style' ).'
</style>';
	}

	// Pinterest js
	if ( ( wppa_switch( 'share_on') || wppa_switch( 'share_on_widget') ) && wppa_switch( 'share_pinterest') ) {
		echo '
<!-- Pinterest share -->
<script type="text/javascript" src="//assets.pinterest.com/js/pinit.js"></script>';
	}

	if ( wppa( 'debug' ) ) {
		error_reporting( wppa( 'debug' ) );
		add_action( 'wp_footer', 'wppa_phpinfo' );
		add_action( 'wp_footer', 'wppa_errorlog' );
		echo '
<script type="text/javascript" >
	wppaDebug = true;
</script>';
	}

	wppa( 'rendering_enabled', true );
	echo '
<!-- Rendering enabled -->
<!-- /WPPA Kickoff -->

	';

}

/* SKIP JETPACK FOTON ON WPPA+ IMAGES */
add_filter('jetpack_photon_skip_image', 'wppa_skip_photon', 10, 3);
function wppa_skip_photon($val, $src, $tag) {
	$result = $val;
	if ( strpos($src, WPPA_UPLOAD_URL) !== false ) $result = true;
	return $result;
}

/* Create dynamic js init file */
function wppa_create_wppa_init_js() {
global $wppa_api_version;
global $wppa_lang;
global $wppa_init_js_data;

	// Init
	if ( is_numeric(wppa_opt( 'fullimage_border_width' )) ) $fbw = wppa_opt( 'fullimage_border_width' ) + '1'; else $fbw = '0';

	// Make content
	$content =
'/* -- WPPA+ Runtime parameters
/*
/* Dynamicly Created on '.date('c').'
/*
*/
';
	if ( ( WPPA_DEBUG || wppa_get_get( 'debug' ) || WP_DEBUG ) && ! wppa_switch( 'defer_javascript' ) ) {
	$content .= '
	/* Check if wppa.js and jQuery are present */
	if (typeof(_wppaSlides) == \'undefined\') alert(\'There is a problem with your theme. The file wppa.js is not loaded when it is expected (Errloc = wppa_kickoff).\');
	if (typeof(jQuery) == \'undefined\') alert(\'There is a problem with your theme. The jQuery library is not loaded when it is expected (Errloc = wppa_kickoff).\');
';	}
	/* This goes into wppa.js */
	$content .= '
	wppaVersion = "'.$wppa_api_version.'";
	wppaDebug = '.( wppa_switch( 'allow_debug' ) ? 'true' : 'false' ).';
	wppaBackgroundColorImage = "'.wppa_opt( 'bgcolor_img' ).'";
	wppaPopupLinkType = "'.wppa_opt( 'thumb_linktype' ).'";
	wppaAnimationType = "'.wppa_opt( 'animation_type' ).'";
	wppaAnimationSpeed = '.wppa_opt( 'animation_speed' ).';
	wppaThumbnailAreaDelta = '.wppa_get_thumbnail_area_delta().';
	wppaTextFrameDelta = '.wppa_get_textframe_delta().';
	wppaBoxDelta = '.wppa_get_box_delta().';
	wppaSlideShowTimeOut = '.wppa_opt( 'slideshow_timeout' ).';
	wppaFilmShowGlue = '.( wppa_switch( 'film_show_glue') ? 'true' : 'false' ).';
	wppaSlideShow = "'.__('Slideshow', 'wp-photo-album-plus').'";
	wppaStart = "'.__('Start', 'wp-photo-album-plus').'";
	wppaStop = "'.__('Stop', 'wp-photo-album-plus').'";
	wppaSlower = "'.__('Slower', 'wp-photo-album-plus').'";
	wppaFaster = "'.__('Faster', 'wp-photo-album-plus').'";
	wppaPhoto = "'.__('Photo', 'wp-photo-album-plus').'";
	wppaOf = "'.__('of', 'wp-photo-album-plus').'";
	wppaPreviousPhoto = "'.__('Previous photo', 'wp-photo-album-plus').'";
	wppaNextPhoto = "'.__('Next photo', 'wp-photo-album-plus').'";
	wppaPrevP = "'.__('Prev.', 'wp-photo-album-plus').'";
	wppaNextP = "'.__('Next', 'wp-photo-album-plus').'";
	wppaAvgRating = "'.__('Average&nbsp;rating', 'wp-photo-album-plus').'";
	wppaMyRating = "'.__('My&nbsp;rating', 'wp-photo-album-plus').'";
	wppaAvgRat = "'.__('Avg.', 'wp-photo-album-plus').'";
	wppaMyRat = "'.__('Mine', 'wp-photo-album-plus').'";
	wppaDislikeMsg = "'.__('You marked this image as inappropriate.', 'wp-photo-album-plus').'";
	wppaMiniTreshold = '.( wppa_opt( 'mini_treshold' ) ? wppa_opt( 'mini_treshold' ) : '0' ).';
	wppaRatingOnce = '.( wppa_switch( 'rating_change') || wppa_switch( 'rating_multi') ? 'false' : 'true' ).';
	wppaPleaseName = "'.__('Please enter your name', 'wp-photo-album-plus').'";
	wppaPleaseEmail = "'.__('Please enter a valid email address', 'wp-photo-album-plus').'";
	wppaPleaseComment = "'.__('Please enter a comment', 'wp-photo-album-plus').'";
	wppaHideWhenEmpty = '.( wppa_switch( 'hide_when_empty') ? 'true' : 'false' ).';
	wppaBGcolorNumbar = "'.wppa_opt( 'bgcolor_numbar' ).'";
	wppaBcolorNumbar = "'.wppa_opt( 'bcolor_numbar' ).'";
	wppaBGcolorNumbarActive = "'.wppa_opt( 'bgcolor_numbar_active' ).'";
	wppaBcolorNumbarActive = "'.wppa_opt( 'bcolor_numbar_active' ).'";
	wppaFontFamilyNumbar = "'.wppa_opt( 'fontfamily_numbar' ).'";
	wppaFontSizeNumbar = "'.wppa_opt( 'fontsize_numbar' ).'px";
	wppaFontColorNumbar = "'.wppa_opt( 'fontcolor_numbar' ).'";
	wppaFontWeightNumbar = "'.wppa_opt( 'fontweight_numbar' ).'";
	wppaFontFamilyNumbarActive = "'.wppa_opt( 'fontfamily_numbar_active' ).'";
	wppaFontSizeNumbarActive = "'.wppa_opt( 'fontsize_numbar_active' ).'px";
	wppaFontColorNumbarActive = "'.wppa_opt( 'fontcolor_numbar_active' ).'";
	wppaFontWeightNumbarActive = "'.wppa_opt( 'fontweight_numbar_active' ).'";
	wppaNumbarMax = "'.wppa_opt( 'numbar_max' ).'";
	wppaLang = "'.$wppa_lang.'";
	wppaNextOnCallback = '.( wppa_switch( 'next_on_callback') ? 'true' : 'false' ).';
	wppaStarOpacity = '.str_replace(',', '.',( wppa_opt( 'star_opacity' )/'100' )).';
	wppaEmailRequired = "'.wppa_opt( 'comment_email_required').'";
	wppaSlideBorderWidth = '.$fbw.';
	wppaAllowAjax = '.( wppa_switch( 'allow_ajax') ? 'true' : 'false' ).';
	wppaUsePhotoNamesInUrls = '.( wppa_switch( 'use_photo_names_in_urls') ? 'true' : 'false' ).';
	wppaThumbTargetBlank = '.( wppa_switch( 'thumb_blank') ? 'true' : 'false' ).';
	wppaRatingMax = '.wppa_opt( 'rating_max' ).';
	wppaRatingDisplayType = "'.wppa_opt( 'rating_display_type' ).'";
	wppaRatingPrec = '.wppa_opt( 'rating_prec' ).';
	wppaStretch = '.( wppa_switch( 'enlarge') ? 'true' : 'false' ).';
	wppaMinThumbSpace = '.wppa_opt( 'tn_margin' ).';
	wppaThumbSpaceAuto = '.( wppa_switch( 'thumb_auto') ? 'true' : 'false' ).';
	wppaMagnifierCursor = "'.wppa_opt( 'magnifier' ).'";
	wppaArtMonkyLink = "'.wppa_opt( 'art_monkey_link' ).'";
	wppaAutoOpenComments = '.( wppa_switch( 'auto_open_comments') ? 'true' : 'false' ).';
	wppaUpdateAddressLine = '.( wppa_switch( 'update_addressline') ? 'true' : 'false' ).';
	wppaFilmThumbTitle = "'.( wppa_opt( 'film_linktype' ) == 'lightbox' ? wppa_zoom_in( false ) : __('Double click to start/stop slideshow running', 'wp-photo-album-plus') ).'";
	wppaClickToView = "'.( wppa_opt( 'film_linktype' ) == 'lightbox' ? wppa_zoom_in( false ) : __('Click to view', 'wp-photo-album-plus') ).'";
	wppaVoteForMe = "'.__(wppa_opt( 'vote_button_text' ), 'wp-photo-album-plus').'";
	wppaVotedForMe = "'.__(wppa_opt( 'voted_button_text' ), 'wp-photo-album-plus').'";
	wppaSlideSwipe = '.( wppa_switch( 'slide_swipe') ? 'true' : 'false' ).';
	wppaMaxCoverWidth = '.wppa_opt( 'max_cover_width' ).';
	wppaDownLoad = "'.__('Download', 'wp-photo-album-plus').'";
	wppaSlideToFullpopup = '.( wppa_opt( 'slideshow_linktype' ) == 'fullpopup' ? 'true' : 'false' ).';
	wppaComAltSize = '.wppa_opt( 'comten_alt_thumbsize' ).';
	wppaBumpViewCount = '.( wppa_switch( 'track_viewcounts') ? 'true' : 'false' ).';
	wppaBumpClickCount = '.( wppa_switch( 'track_clickcounts') ? 'true' : 'false' ).';
	wppaShareHideWhenRunning = '.( wppa_switch( 'share_hide_when_running') ? 'true' : 'false' ).';
	wppaFotomoto = '.( wppa_switch( 'fotomoto_on') ? 'true' : 'false' ).';
	wppaArtMonkeyButton = '.( wppa_opt( 'art_monkey_display' ) == 'button' ? 'true' : 'false' ).';
	wppaFotomotoHideWhenRunning = '.( wppa_switch( 'fotomoto_hide_when_running') ? 'true' : 'false' ).';
	wppaCommentRequiredAfterVote = '.( wppa_switch( 'vote_needs_comment') ? 'true' : 'false' ).';
	wppaFotomotoMinWidth = '.wppa_opt( 'fotomoto_min_width' ).';
	wppaShortQargs = '.( wppa_switch( 'use_short_qargs') ? 'true' : 'false' ).';
	wppaOvlHires = '.( wppa_switch( 'lb_hres' ) ? 'true' : 'false' ).';
	wppaSlideVideoStart = '.( wppa_switch( 'start_slide_video' ) ? 'true' : 'false' ).';
	wppaSlideAudioStart = '.( wppa_switch( 'start_slide_audio' ) ? 'true' : 'false' ).';
	wppaRel = "'.( wppa_opt( 'lightbox_name' ) == 'wppa' ? 'data-rel' : 'rel' ).'";
	wppaOvlRadius = '.wppa_opt( 'ovl_border_radius' ).';
	wppaOvlBorderWidth = '.wppa_opt( 'ovl_border_width' ).';
	wppaEditPhotoWidth = "'.(wppa_opt( 'upload_edit' ) == 'new' ? 500 : 960).'";
	wppaThemeStyles = "'.(wppa_switch( 'upload_edit_theme_css') ? get_stylesheet_uri() : '' ).'";
	wppaStickyHeaderHeight = '.wppa_opt( 'sticky_header_size' ).';
	wppaRenderModal = ' . ( wppa_switch( 'ajax_render_modal' ) ? 'true' : 'false' ) . ';
	wppaModalQuitImg = "url(' . wppa_get_imgdir( 'smallcross-' . wppa_opt( 'ovl_theme') . '.gif' ) . ')";
	wppaBoxRadius = "' . wppa_opt( 'bradius' ) . '";
	wppaModalBgColor = "' . wppa_opt( 'bgcolor_modal' ) . '";
	wppaUploadEdit = "' . wppa_opt( 'upload_edit' ) . '";
	wppaSvgFillcolor = "' . wppa_opt( 'svg_color' ) . '";
	wppaSvgBgcolor = "' . wppa_opt( 'svg_bg_color' ) . '";
	wppaOvlSvgFillcolor = "' . wppa_opt( 'ovl_svg_color' ) . '";
	wppaOvlSvgBgcolor = "' . wppa_opt( 'ovl_svg_bg_color' ) . '";
	wppaSvgCornerStyle = "' . wppa_opt( 'icon_corner_style' ) . '";
	';

	// Open file
	$file = @ fopen ( WPPA_PATH.'/wppa-init.'.$wppa_lang.'.js', 'wb' );
	if ( $file ) {
		// Write file
		fwrite ( $file, $content );
		// Close file
		fclose ( $file );
		$wppa_init_js_data = '';
	}
	else {
		$wppa_init_js_data =
'<script type="text/javascript">
/* Warning: file wppa-init.'.$wppa_lang.'.js could not be created */
/* The content is therefor output here */

'.$content.'
</script>
';
	}
}

add_action( 'init', 'wppa_set_shortcode_priority', 100 );

function wppa_set_shortcode_priority() {

	$newpri = wppa_opt( 'shortcode_priority' );
	if ( $newpri == '11' ) return;	// Default, do not change

	$oldpri = has_filter( 'the_content', 'do_shortcode' );
	if ( $oldpri ) {
		remove_filter( 'the_content', 'do_shortcode', $oldpri );
		add_filter( 'the_content', 'do_shortcode', $newpri );
	}
}


/* We use bbPress */
// editor bbpress in tinymce mode
function wppa_enable_visual_editor_in_bbpress( $args = array() ) {

	if ( wppa_switch( 'photo_on_bbpress' ) ) {
		$args['tinymce'] = true;
		$args['teeny'] = false;
	}
    return $args;
}
add_filter( 'bbp_after_get_the_content_parse_args', 'wppa_enable_visual_editor_in_bbpress' );

// remove insert wp image button
function wppa_remove_image_button_in_bbpress( $buttons ) {

	if ( wppa_switch( 'photo_on_bbpress' ) ) {
		if ( ( $key = array_search( 'image', $buttons ) ) !== false ) {
			unset( $buttons[$key] );
		}
	}
	return $buttons ;
}
add_filter( 'bbp_get_teeny_mce_buttons', 'wppa_remove_image_button_in_bbpress' );

// enable processing shortcodes
function wppa_enable_shortcodes_in_bbpress( $content ) {

	if ( wppa_switch( 'photo_on_bbpress' ) ) {
		$content = do_shortcode( $content );
	}
	return $content;
}
add_filter( 'bbp_get_topic_content', 'wppa_enable_shortcodes_in_bbpress', 1000 );
add_filter( 'bbp_get_reply_content', 'wppa_enable_shortcodes_in_bbpress', 1000 );


// This function contains strings for i18n from files not included
// in the search for frontend required translatable strings
// Mainly from widgets
function wppa_dummy() {

	// Commet widget
	__( 'wrote' , 'wp-photo-album-plus' );
	__( 'Photo not found', 'wp-photo-album-plus' );
	__( 'There are no commented photos (yet)', 'wp-photo-album-plus' );

	// Featen widget
	__( 'View the featured photos', 'wp-photo-album-plus' );
	__( 'Photo not found', 'wp-photo-album-plus' );
	__( 'There are no featured photos (yet)', 'wp-photo-album-plus' );

	// Lasten widget
	__( 'View the most recent uploaded photos', 'wp-photo-album-plus' );
	__( 'Photo not found', 'wp-photo-album-plus' );
	__( 'There are no uploaded photos (yet)', 'wp-photo-album-plus' );

	// Potd widget
	__( 'Photo not found', 'wp-photo-album-plus' );
	__( 'By:', 'wp-photo-album-plus' );

	// Slideshow widget
	__( 'No album defined (yet)', 'wp-photo-album-plus' );

	// Thumbnail widget
	__( 'Photo not found', 'wp-photo-album-plus' );
	__( 'There are no photos (yet)', 'wp-photo-album-plus' );

	// Upldr widget
	__( 'There are too many registered users in the system for this widget' , 'wp-photo-album-plus' );
	__( 'Photos uploaded by', 'wp-photo-album-plus' );

	// Topten widget
	_n( '%d vote', '%d votes', $n, 'wp-photo-album-plus' );
	_n( '%d view', '%d views', $n, 'wp-photo-album-plus' );
	__( 'Photo not found', 'wp-photo-album-plus' );
	__( 'There are no rated photos (yet)', 'wp-photo-album-plus' );

}