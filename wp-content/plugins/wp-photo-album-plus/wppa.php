<?php
/*
 * Plugin Name: WP Photo Album Plus
 * Description: Easily manage and display your photo albums and slideshows within your WordPress site.
 * Version: 6.8.00
 * Author: J.N. Breetvelt a.k.a. OpaJaap
 * Author URI: http://wppa.opajaap.nl/
 * Plugin URI: http://wordpress.org/extend/plugins/wp-photo-album-plus/
 * Text Domain: wp-photo-album-plus
 * Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly (1)" );

require_once 'wppa-init.php';

/* See explanation on activation hook in wppa-setup.php */
register_activation_hook( __FILE__, 'wppa_activate_plugin' );

/* WP GLOBALS */
global $wpdb;
global $wp_version;

/* WPPA GLOBALS */
global $wppa_revno; 		$wppa_revno = '6800';				// WPPA db version
global $wppa_api_version; 	$wppa_api_version = '6-8-00-012';	// WPPA software version

/* start timers */
global $wppa_starttime; $wppa_starttime = microtime( true );
global $wppa_loadtime; $wppa_loadtime = - microtime( true );
global $wppa_timestamp_start; $wppa_timestamp_start = time();

/* CONSTANTS
/*
/* Check for php version
/* PHP_VERSION_ID is available as of PHP 5.2.7, if our
/* version is lower than that, then emulate it
*/
if ( ! defined( 'PHP_VERSION_ID' ) ) {
	$version = explode( '.', PHP_VERSION );
	define( 'PHP_VERSION_ID', ( $version[0] * 10000 + $version[1] * 100 + $version[2] ) );
}

/* To run WPPA+ on a multisite in single site mode,
/* add to wp-config.php: define('WPPA_MULTISITE_GLOBAL', true); */
if ( ! defined('WPPA_MULTISITE_GLOBAL') ) {
	define( 'WPPA_MULTISITE_GLOBAL', false );
}

/* To run WPPA+ in a multisite old style mode,
/* add to wp-config.php: define('WPPA_MULTISITE_BLOGSDIR', true); */
if ( ! defined('WPPA_MULTISITE_BLOGSDIR') ) {
	define( 'WPPA_MULTISITE_BLOGSDIR', false );
}

/* To run WPPA+ in a multisite new style, new implementation mode,
/* add to wp-config.php: define('WPPA_MULTISITE_INDIVIDUAL', true); */
if ( ! defined('WPPA_MULTISITE_INDIVIDUAL') ) {
	define( 'WPPA_MULTISITE_INDIVIDUAL', false );
}

/* Choose the right db prifix */
if ( is_multisite() && WPPA_MULTISITE_GLOBAL ) {
	$wppa_prefix = $wpdb->base_prefix;
}
else {
	$wppa_prefix = $wpdb->prefix;
}

/* DB Tables */
define( 'WPPA_ALBUMS',   $wppa_prefix . 'wppa_albums' );
define( 'WPPA_PHOTOS',   $wppa_prefix . 'wppa_photos' );
define( 'WPPA_RATING',   $wppa_prefix . 'wppa_rating' );
define( 'WPPA_COMMENTS', $wppa_prefix . 'wppa_comments' );
define( 'WPPA_IPTC',	 $wppa_prefix . 'wppa_iptc' );
define( 'WPPA_EXIF', 	 $wppa_prefix . 'wppa_exif' );
define( 'WPPA_INDEX', 	 $wppa_prefix . 'wppa_index' );
define( 'WPPA_SESSION',	 $wppa_prefix . 'wppa_session' );

/* Paths and urls */ 									// Standard examples
define( 'WPPA_FILE', basename( __FILE__ ) );			// wppa.php
define( 'WPPA_PATH', dirname( __FILE__ ) );				// /.../wp-content/plugins/wp-photo-album-plus
define( 'WPPA_NAME', basename( dirname( __FILE__ ) ) );	// wp-photo-album-plus
define( 'WPPA_URL',  plugins_url() . '/' . WPPA_NAME ); // http://.../wp-photo-album-plus
define( 'WPPA_ABSPATH', wppa_flips( ABSPATH ) ); 		// ABSPATH formatted for Windows servers

// Although i may not use wp constants directly,
// there is no function that returns the path to wp-content,
// so, if you changed the location of wp-content, i have to use WP_CONTENT_DIR,
// because wp-content needs not to be relative to ABSPATH
if ( defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WPPA_CONTENT_PATH', wppa_flips( WP_CONTENT_DIR ) );
}

// In the normal case i use content_url() with the site_url() part replaced by WPPA_ABSPATH,
// i.e. ABSPATH with the slashes in the right direction (in case of windows server)
else {
	define( 'WPPA_CONTENT_PATH',
		str_replace( wppa_trimflips( site_url() ) . '/',
		WPPA_ABSPATH, wppa_flips( content_url() ) )
		);												// /.../wp-content
}

// Also define my url to wp-content:
define( 'WPPA_CONTENT_URL', content_url() );

// Now you can convert a path to an url vv form files inside wp-content as follows
// $path = str_replace( WPPA_CONTENT_URL, SWPPA_CONTENT_PATH, $url );
// $url = str_replace( WPPA_CONTENT_PATH, SWPPA_CONTENT_URL, $path );

global $wppa_log_file;	$wppa_log_file = WPPA_CONTENT_PATH . '/wppa-log.txt';

define( 'WPPA_NONCE' , 'wppa-update-check' );

// set WPPA_DEBUG to true to produces success/fale messages during setup and sets debug switch on.
define( 'WPPA_DEBUG', false );

/* DONE with trivial constants */

/* Declare init actions */

/* START SESSION */
add_action( 'init', 'wppa_session_start', 1 );

/* Init path and url constants */
add_action( 'init', 'wppa_init_path_and_url_constants', 7 );

/* Load language */
add_action( 'plugins_loaded', 'wppa_load_plugin_textdomain' );

/* SET UP array $wppa, array $wppa_opt. Must be done after language has been set */
add_action( 'init', 'wppa_initialize_runtime', 11 );

/* Load adminbar menu if required, after translations loaded */
add_action( 'init', 'wppa_admin_bar_init', 12);

/* END SESSION */
add_action( 'shutdown', 'wppa_session_end' );



$wppa_loadtime += microtime(true);

