<?php
/* wppa-init.php
* Package: wp-photo-album-plus
*
* This file loads required php files and contains all functions used in init actions.
*
* Version 6.7.09
*/

/* LOAD SIDEBAR WIDGETS */
require_once 'wppa-potd-widget.php';
require_once 'wppa-search-widget.php';
require_once 'wppa-topten-widget.php';
require_once 'wppa-featen-widget.php';
require_once 'wppa-slideshow-widget.php';
require_once 'wppa-gp-widget.php';
require_once 'wppa-comment-widget.php';
require_once 'wppa-thumbnail-widget.php';
require_once 'wppa-lasten-widget.php';
require_once 'wppa-album-widget.php';
require_once 'wppa-qr-widget.php';
require_once 'wppa-tagcloud-widget.php';
require_once 'wppa-multitag-widget.php';
require_once 'wppa-upload-widget.php';
require_once 'wppa-super-view-widget.php';
require_once 'wppa-upldr-widget.php';
require_once 'wppa-bestof-widget.php';
require_once 'wppa-album-navigator-widget.php';
require_once 'wppa-stereo-widget.php';
require_once 'wppa-admins-choice-widget.php';

/* COMMON FUNCTIONS */
require_once 'wppa-common-functions.php';
require_once 'wppa-utils.php';
require_once 'wppa-exif-iptc-common.php';
require_once 'wppa-index.php';
require_once 'wppa-statistics.php';
require_once 'wppa-wpdb-insert.php';
require_once 'wppa-wpdb-update.php';
require_once 'wppa-users.php';
require_once 'wppa-watermark.php';
require_once 'wppa-setup.php';
require_once 'wppa-session.php';
require_once 'wppa-source.php';
require_once 'wppa-items.php';
require_once 'wppa-date-time.php';
require_once 'wppa-htaccess.php';
require_once 'wppa-video.php';
require_once 'wppa-audio.php';
require_once 'wppa-mobile.php';
require_once 'wppa-stereo.php';
require_once 'wppa-encrypt.php';
require_once 'wppa-photo-files.php';
require_once 'wppa-cron.php';
require_once 'wppa-maintenance.php';
require_once 'wppa-tinymce-common.php';

/* Load cloudinary if configured and php version >= 5.3 */
if ( PHP_VERSION_ID >= 50300 ) require_once 'wppa-cloudinary.php';

/* DO THE ADMIN/NON ADMIN SPECIFIC STUFF */
if ( is_admin() ) require_once 'wppa-admin.php';
else require_once 'wppa-non-admin.php';

/* ADD AJAX */
if ( defined( 'DOING_AJAX' ) ) {
	require_once 'wppa-ajax.php';
}

// To fix a problem in Windows local host systems:
function wppa_trims( $txt ) {
	return trim( $txt, "\\/" );
}
function wppa_flips( $txt ) {
	return str_replace( "\\", "/", $txt );
}
function wppa_trimflips( $txt ) {
	return wppa_flips( wppa_trims ( $txt ) );
}

// Load textdomain conditionally
function wppa_load_plugin_textdomain() {
global $wppa_lang;
global $wppa_locale;
global $wp_version;

	// 'Our' usefull language info
	$wppa_locale = get_locale() ? get_locale() : 'en_US';
	$wppa_lang = substr( $wppa_locale, 0, 2 );

	// Load language if wp does not do it
	if ( $wp_version < '4.6' || is_file( dirname( __FILE__ ) . '/languages/wp-photo-album-plus-' . $wppa_locale . '.mo' ) ) {
		load_plugin_textdomain( 'wp-photo-album-plus', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}
}

// Compute all non-trivial constants and create required directories
function wppa_init_path_and_url_constants() {
global $blog_id;

	// Upload ( .../wp-content/uploads ) is always relative to ABSPATH,
	// see http://codex.wordpress.org/Editing_wp-config.php#Moving_wp-content_folder
	//
	// Assumption: site_url() corresponds with ABSPATH
	// Our version ( WPPA_UPLOAD ) of the relative part of the path/url to the uploads dir
	// is calculated form wp_upload_dir() by substracting ABSPATH from the uploads basedir.
	$wp_uploaddir = wp_upload_dir();

	// Unfortunately $wp_uploaddir['basedir'] does very often not contain the data promised
	// by the docuentation, so it is unreliable.
	$rel_uploads_path = defined( 'WPPA_REL_UPLOADS_PATH') ?
		wppa_trims( WPPA_REL_UPLOADS_PATH ) :
		'wp-content/uploads';

	// The depot dir is also relative to ABSPATH but on the same level as uploads,
	// but without '/wppa-depot'.
	// If you want to change the name of wp-content, you have also to define WPPA_REL_DEPOT_PATH
	// as being the relative path to the parent of wppa-depot.
	$rel_depot_path = defined( 'WPPA_REL_DEPOT_PATH' ) ?
		wppa_trims( WPPA_REL_DEPOT_PATH ) :
		'wp-content';

	// For multisite the uploads are in /wp-content/blogs.dir/<blogid>/,
	// so we hope still below ABSPATH
	$wp_content_multi = wppa_trims( str_replace( WPPA_ABSPATH, '', WPPA_CONTENT_PATH ) );

	// To test the multisite paths and urls, set $debug_multi = true
	$debug_multi = false;

	// Define paths and urls
	if ( $debug_multi || ( is_multisite() && ! WPPA_MULTISITE_GLOBAL ) ) {
		if ( WPPA_MULTISITE_BLOGSDIR ) {	// Old multisite individual
			define( 'WPPA_UPLOAD', wppa_trims( $wp_content_multi . '/blogs.dir/' . $blog_id ) );
			define( 'WPPA_UPLOAD_PATH', WPPA_ABSPATH.WPPA_UPLOAD . '/wppa' );
			define( 'WPPA_UPLOAD_URL', site_url() . '/' . WPPA_UPLOAD . '/wppa' );
			define( 'WPPA_DEPOT',
				wppa_trims( $wp_content_multi . '/blogs.dir/' . $blog_id . '/wppa-depot' ) );
			define( 'WPPA_DEPOT_PATH', WPPA_ABSPATH.WPPA_DEPOT );
			define( 'WPPA_DEPOT_URL', site_url() . '/' . WPPA_DEPOT );
		}
		elseif ( WPPA_MULTISITE_INDIVIDUAL ) {	// New multisite individual
			define( 'WPPA_UPLOAD', $rel_uploads_path . '/sites/'.$blog_id);
			define( 'WPPA_UPLOAD_PATH', ABSPATH.WPPA_UPLOAD.'/wppa');
			define( 'WPPA_UPLOAD_URL', get_bloginfo('wpurl').'/'.WPPA_UPLOAD.'/wppa');
			define( 'WPPA_DEPOT', $rel_uploads_path . '/sites/'.$blog_id.'/wppa-depot' );
			define( 'WPPA_DEPOT_PATH', ABSPATH.WPPA_DEPOT );
			define( 'WPPA_DEPOT_URL', get_bloginfo('wpurl').'/'.WPPA_DEPOT );
		}
		else { 	// Not working default multisite
			$user = is_user_logged_in() ? '/' . wppa_get_user() : '';
			define( 'WPPA_UPLOAD', $rel_uploads_path );
			define( 'WPPA_UPLOAD_PATH', WPPA_ABSPATH . WPPA_UPLOAD . $user . '/wppa' );
			define( 'WPPA_UPLOAD_URL', site_url() . '/' . WPPA_UPLOAD . $user . '/wppa' );
			define( 'WPPA_DEPOT', wppa_trims( $rel_depot_path . '/wppa-depot' . $user ) );
			define( 'WPPA_DEPOT_PATH', WPPA_ABSPATH . WPPA_DEPOT );
			define( 'WPPA_DEPOT_URL', site_url() . '/' . WPPA_DEPOT );
		}
	}
	else {	// Single site or multisite global
		define( 'WPPA_UPLOAD', $rel_uploads_path );
		if ( ! defined( 'WPPA_UPLOAD_PATH' ) ) {
			define( 'WPPA_UPLOAD_PATH', WPPA_ABSPATH . WPPA_UPLOAD . '/wppa' );
		}
		if ( ! defined( 'WPPA_UPLOAD_URL' ) ) {
			define( 'WPPA_UPLOAD_URL', site_url() . '/' . WPPA_UPLOAD . '/wppa' );
		}
		$user = is_user_logged_in() ? '/' . wppa_get_user() : '';
		define( 'WPPA_DEPOT', wppa_trims( $rel_depot_path . '/wppa-depot' . $user ) );
		if ( ! defined( '_WPPA_DEPOT_PATH' ) ) {
			define( 'WPPA_DEPOT_PATH', WPPA_ABSPATH . WPPA_DEPOT );
		}
		else {
			define( 'WPPA_DEPOT_PATH', _WPPA_DEPOT_PATH . WPPA_DEPOT );
		}
		if ( ! defined( '_WPPA_DEPOT_URL' ) ) {
			define( 'WPPA_DEPOT_URL', site_url() . '/' . WPPA_DEPOT );
		}
		else {
			define( 'WPPA_DEPOT_URL', _WPPA_DEPOT_URL . WPPA_DEPOT );
		}
	}

	wppa_mktree( WPPA_UPLOAD_PATH );	// Whatever (faulty) path has been calculated, it will be
	wppa_mktree( WPPA_UPLOAD_PATH . '/thumbs' );	// Just to make sure the chmod is right ( 755 )
	wppa_mktree( WPPA_DEPOT_PATH );		// created and not prevent plugin to activate or function
}

function wppa_verify_multisite_config() {

	if ( ! is_admin() ) return;
	if ( ! is_multisite() ) return;
	if ( wppa( 'ajax' ) ) return;

	if ( WPPA_MULTISITE_GLOBAL ) return;
	if ( WPPA_MULTISITE_BLOGSDIR ) return;
	if ( WPPA_MULTISITE_INDIVIDUAL ) return;

	$errtxt = __('</strong><h3>WP Photo ALbum Plus Error message</h3>This is a multi site installation. One of the following 3 lines must be entered in wp-config.php:', 'wp-photo-album-plus');
	$errtxt .= __('<br /><br /><b>define( \'WPPA_MULTISITE_INDIVIDUAL\', true );</b> <small>// Multisite WP 3.5 or later with every site its own albums and photos</small>', 'wp-photo-album-plus');
	$errtxt .= __('<br /><b>define( \'WPPA_MULTISITE_BLOGSDIR\', true );</b> <small>// Multisite prior to WP 3.5 with every site its own albums and photos</small>', 'wp-photo-album-plus');
	$errtxt .= __('<br /><b>define( \'WPPA_MULTISITE_GLOBAL\', true );</b> <small>// Multisite with one common set of albums and photos</small>', 'wp-photo-album-plus');
	$errtxt .= __('<br /><br />For more information see: <a href="https://wordpress.org/plugins/wp-photo-album-plus/faq/">the faq</a>', 'wp-photo-album-plus');
	$errtxt .= __('<br /><br /><em>If you upload photos, they will be placed in the wrong location and will not be visible for visitors!</em><strong>', 'wp-photo-album-plus');

	wppa_error_message( $errtxt );
}

function wppa_admin_bar_init() {

	if ( ( is_admin() && get_option( 'wppa_adminbarmenu_admin' ) == 'yes' ) ||
		( ! is_admin() && get_option( 'wppa_adminbarmenu_frontend' ) == 'yes' ) ) {

		if ( current_user_can('wppa_admin') ||
			 current_user_can('wppa_upload') ||
			 current_user_can('wppa_import') ||
			 current_user_can('wppa_moderate') ||
			 current_user_can('wppa_export') ||
			 current_user_can('wppa_settings') ||
			 current_user_can('wppa_potd') ||
			 current_user_can('wppa_comments') ||
			 current_user_can('wppa_help') ) {
				require_once 'wppa-adminbar.php';
		}
	}
}

function wppa_maintenance_messages() {
global $pagenow;

	if ( ! current_user_can( 'wppa_settings' ) ) {
		return;
	}

	// Rerate required?
	if ( get_option( 'wppa_rating_on' ) == 'yes' && get_option( 'wppa_rerate_status' ) ) {
		if ( strpos( get_option( 'wppa_rerate_status' ), 'cron' ) === false ) {
			wppa_error_message( __('The avarage ratings need to be recalculated. Please run <i>Photo Albums -> Settings</i> admin page <i>Table VIII-A5</i>' , 'wp-photo-album-plus') );
		}
	}

	// Cron jobs postponed?
	if ( get_option( 'wppa_maint_ignore_cron' ) == 'yes' ) {
		wppa_warning_message( __( 'Please do not forget to re-enable cron jobs for wppa when you are ready doing your bulk actions. See <i>Table VIII-A0.2</i>', 'wp-photo-album-plus') );
	}

	// Finish setup
	if ( get_option( 'wppa_prevrev' ) == '100' && get_option('wppa_i_done') != 'done' && $_SERVER['QUERY_STRING'] != 'page=wppa_options' ) {
		wppa_ok_message( __('Please finish setting up WP Photo Album Plus on', 'wp-photo-album-plus') . ' <a href="'.get_admin_url().'admin.php?page=wppa_options">' . __('this page', 'wp-photo-album-plus') . '</a>' );
	}
}

function wppa_check_tag_system() {
global $wpdb;

	if ( current_user_can( 'wppa_settings' ) ) {
		if ( get_option( 'wppa_tags_ok' ) != '1' ) {
			$tag = $wpdb->get_var( "SELECT `tags` FROM `" . WPPA_PHOTOS . "` WHERE `tags` <> '' ORDER BY `id` DESC LIMIT 1" );
			if ( $tag ) {
				if ( substr( $tag, 0, 1 ) != ',' ) {
					add_action('admin_notices', 'wppa_tag_message');
					update_option( 'wppa_sanitize_tags_status', 'required' );
				}
				else {
					update_option( 'wppa_tags_ok', '1' );
				}
			}
		}
	}
}
function wppa_tag_message() {
	wppa_error_message( __('</strong>The tags system needs to be converted. Please run <b>Photo Albums -> Settings</b> admin page <b>Table VIII-B16</b><strong>' , 'wp-photo-album-plus') );
}

function wppa_check_cat_system() {
global $wpdb;

	if ( current_user_can( 'wppa_settings' ) ) {
		if ( get_option( 'wppa_cats_ok' ) != '1' ) {
			$tag = $wpdb->get_var( "SELECT `cats` FROM `" . WPPA_ALBUMS . "` WHERE `cats` <> '' ORDER BY `id` DESC LIMIT 1" );
			if ( $tag ) {
				if ( substr( $tag, 0, 1 ) != ',' ) {
					add_action('admin_notices', 'wppa_cat_message');
					update_option( 'wppa_sanitize_cats_status', 'required' );
				}
				else {
					update_option( 'wppa_cats_ok', '1' );
				}
			}
		}
	}
}
function wppa_cat_message() {
	wppa_error_message( __('</strong>The cats system needs to be converted. Please run <b>Photo Albums -> Settings</b> admin page <b>Table VIII-B17</b><strong>' , 'wp-photo-album-plus') );
}

function wppa_scripts_are_obssolete() {
global $wpdb;

	// This notice dismissed?
	if ( get_option( 'wppa_dismiss_admin_notice_scripts_are_obsolete', 'no' ) == 'yes' ) {
		return;
	}

	$has_wppa_scripts = $wpdb->get_results( "SELECT `ID`, `post_title`, `post_content`, `post_type` " .
											"FROM `" . $wpdb->prefix . 'posts' ."` " .
											"WHERE `post_status` = 'publish' " .
											"AND ( `post_type` = 'post' OR `post_type` = 'page' ) " .
											"AND `post_content` LIKE '%\\%\\%wppa\\%\\%%' " , ARRAY_A );

	if ( $has_wppa_scripts ) {
		foreach( array_keys( $has_wppa_scripts ) as $key ) {
			if ( strpos( $has_wppa_scripts[$key]['post_content'], '%%wppa%%' ) === false ) {
				unset( $has_wppa_scripts[$key] );
			}
		}
	}

	if ( ! empty( $has_wppa_scripts ) ) {
		$msg = __( 'WPPA scripts will no longer be supported in version 6.6. Please convert the %%wppa%% scripts to [wppa][/wppa] shortcodes before upgrading to version 6.6.', 'wp-photo-album-plus' );
		$msg .= '<br /><br />';
		$msg .= __( 'WPPA scripts found in the following Pages / Posts', 'wp-photo-album-plus' );
		$msg .= '<br /><br />';
		foreach( $has_wppa_scripts as $item ) {
			$msg .= $item['ID'] . ' <a href="'. admin_url( 'post.php?post=' . $item['ID'] . '&action=edit' ) .'" title="Edit this ' . $item['post_type'] . '" >' . $item['post_title'] . '</a>' .
			' at loc:' . strpos( $item['post_content'], '%%wppa%%' ) . ' :' . htmlspecialchars( substr( $item['post_content'] , strpos( $item['post_content'], '%%wppa%%' ), 60 ) ) .  '...' .
			'<br />';
		}
		$msg .= '<br />' .
				'<div style="text-align:center;" >' .
					'<em style="float:left;" >Enter your script to convert here:</em>' .
					'<em>Click to convert</em>' .
					'<em style="float:right;" >Copy the result into your page/post:</em>' .
					'<br />' .
					'<input type="text" id="script" style="width:40%; float:left;" />' .
					'<input type="button" value="' . esc_attr( '>>>>>' ) . '" onclick="wppaConvertScriptToShortcode(\'script\',\'shortcode\')" />' .
					'<input type="text" id="shortcode" style="width:40%; float:right;" />' .
				'</div>' .
				'<br />';

		$msg .= '<br />' . sprintf( __( 'For more information see the %s documentation page', 'wp-photo-album-plus' ), '<a href="http://wppa.nl/changelog/script-to-shortcode-conversion/" target="_blank" >Script to shortcode conversion</a>' );
		$msg .= '<br /><div style="float:right;" ><input type="checkbox" onchange="wppaDismissAdminNotice(\'dismiss_admin_notice_scripts_are_obsolete\', this);" > Dismiss this message</div><div style="clear:both;" ></div>';
		$msg .= wp_nonce_field('wppa-nonce', 'wppa-nonce', false, false);
		wppa_warning_message( $msg );
	}
}

/* This function will add "donate" link to main plugins page */
function wppa_donate_link($links, $file) {
	if ( $file == plugin_basename(__FILE__) ) {
		$donate_link_usd = '<a target="_blank" title="Paypal" href="https://' .
			'www.paypal.com/cgi-bin/webscr?cmd=_donations&business=OpaJaap@OpaJaap.nl&item_name=' .
			'WP-Photo-Album-Plus&item_number=Support-Open-Source&currency_code=USD&lc=US">' .
			'Donate USD</a>';
		$donate_link_eur = '<a target="_blank" title="Paypal" href="https://' .
			'www.paypal.com/cgi-bin/webscr?cmd=_donations&business=OpaJaap@OpaJaap.nl&item_name=' .
			'WP-Photo-Album-Plus&item_number=Support-Open-Source&currency_code=EUR&lc=US">' .
			'Donate EUR</a>';
		$docs_link = '<a target="_blank" href="http://wppa.opajaap.nl/" title=' .
			'"Docs & Demos" >Documentation and examples</a>';

		$links[] = $donate_link_usd . ' | ' . $donate_link_eur . ' | ' . $docs_link;
	}
	return $links;
}