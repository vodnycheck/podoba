<?php
/* wppa-admin.php
* Package: wp-photo-album-plus
*
* Contains the admin menu and startups the admin pages
* Version 6.8.00
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly (2)" );

/* CHECK INSTALLATION */
// Check setup
add_action ( 'init', 'wppa_setup', '8' );	// admin_init

/* ADMIN MENU */
add_action( 'admin_menu', 'wppa_add_admin' );

function wppa_add_admin() {
	global $wp_roles;
	global $wpdb;

	// Make sure admin has access rights
	if ( wppa_user_is( 'administrator' ) ) {
		$wp_roles->add_cap( 'administrator', 'wppa_admin' );
		$wp_roles->add_cap( 'administrator', 'wppa_upload' );
		$wp_roles->add_cap( 'administrator', 'wppa_import' );
		$wp_roles->add_cap( 'administrator', 'wppa_moderate' );
		$wp_roles->add_cap( 'administrator', 'wppa_export' );
		$wp_roles->add_cap( 'administrator', 'wppa_settings' );
		$wp_roles->add_cap( 'administrator', 'wppa_potd' );
		$wp_roles->add_cap( 'administrator', 'wppa_comments' );
		$wp_roles->add_cap( 'administrator', 'wppa_help' );
	}

	// See if there are comments pending moderation
	$com_pending = '';
	$com_pending_count = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_COMMENTS."` WHERE `status` = 'pending' OR `status` = 'spam'" );
	if ( $com_pending_count ) $com_pending = '<span class="update-plugins"><span class="plugin-count">'.$com_pending_count.'</span></span>';

	// See if there are uploads pending moderation
	$upl_pending = '';
	$upl_pending_count = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `status` = 'pending'" );
	if ( $upl_pending_count ) $upl_pending = '<span class="update-plugins"><span class="plugin-count">'.$upl_pending_count.'</span></span>';

	// Compute total pending moderation
	$tot_pending = '';
	$tot_pending_count = '0';
	if ( current_user_can('wppa_comments') || current_user_can('wppa_moderate') ) $tot_pending_count += $com_pending_count;
	if ( current_user_can('wppa_admin') || current_user_can('wppa_moderate') ) $tot_pending_count+= $upl_pending_count;
	if ( $tot_pending_count ) $tot_pending = '<span class="update-plugins"><span class="plugin-count">'.'<b>'.$tot_pending_count.'</b>'.'</span></span>';

	$icon_url = WPPA_URL . '/img/camera16.png';

	// 				page_title        menu_title                                      capability    menu_slug          function      icon_url    position
	add_menu_page( 'WP Photo Album', __('Photo&thinsp;Albums', 'wp-photo-album-plus').$tot_pending, 'wppa_admin', 'wppa_admin_menu', 'wppa_admin', $icon_url ); //,'10' );

	//                 parent_slug        page_title                             menu_title                             capability            menu_slug               function
	add_submenu_page( 'wppa_admin_menu',  __('Album Admin', 'wp-photo-album-plus'),			 __('Album Admin', 'wp-photo-album-plus').$upl_pending,'wppa_admin',        'wppa_admin_menu',      'wppa_admin' );
    add_submenu_page( 'wppa_admin_menu',  __('Upload Photos', 'wp-photo-album-plus'),           __('Upload Photos', 'wp-photo-album-plus'),          'wppa_upload',        'wppa_upload_photos',   'wppa_page_upload' );
	// Uploader without album admin rights, but when the upload_edit switch set, may edit his own photos
	if ( ! current_user_can('wppa_admin') && wppa_opt( 'upload_edit') != '-none-' ) {
		add_submenu_page( 'wppa_admin_menu',  __('Edit Photos', 'wp-photo-album-plus'), 		 __('Edit Photos', 'wp-photo-album-plus'), 		   'wppa_upload', 		 'wppa_edit_photo', 	 'wppa_edit_photo' );
	}
	add_submenu_page( 'wppa_admin_menu',  __('Import Photos', 'wp-photo-album-plus'),           __('Import Photos', 'wp-photo-album-plus'),          'wppa_import',        'wppa_import_photos',   'wppa_page_import' );
	add_submenu_page( 'wppa_admin_menu',  __('Moderate Photos', 'wp-photo-album-plus'),		 __('Moderate Photos', 'wp-photo-album-plus').(wppa_switch('moderate_bulk')?$upl_pending:$tot_pending), 'wppa_moderate', 	 'wppa_moderate_photos', 'wppa_page_moderate' );
	add_submenu_page( 'wppa_admin_menu',  __('Export Photos', 'wp-photo-album-plus'),           __('Export Photos', 'wp-photo-album-plus'),          'wppa_export',     	 'wppa_export_photos',   'wppa_page_export' );
    add_submenu_page( 'wppa_admin_menu',  __('Settings', 'wp-photo-album-plus'),                __('Settings', 'wp-photo-album-plus'),               'wppa_settings',      'wppa_options',         'wppa_page_options' );
	add_submenu_page( 'wppa_admin_menu',  __('Photo of the day Widget', 'wp-photo-album-plus'), __('Photo of the day', 'wp-photo-album-plus'),       'wppa_potd', 		 'wppa_photo_of_the_day', 'wppa_sidebar_page_options' );
	add_submenu_page( 'wppa_admin_menu',  __('Manage comments', 'wp-photo-album-plus'),         __('Comments', 'wp-photo-album-plus').$com_pending,  'wppa_comments',      'wppa_manage_comments', 'wppa_comment_admin' );
    add_submenu_page( 'wppa_admin_menu',  __('Help &amp; Info', 'wp-photo-album-plus'),         __('Documentation', 'wp-photo-album-plus'),        'wppa_help',          'wppa_help',            'wppa_page_help' );
	if ( get_option( 'wppa_logfile_on_menu' ) == 'yes' ) {
		add_submenu_page( 'wppa_admin_menu',  __('Logfile', 'wp-photo-album-plus'), 				__('Logfile', 'wp-photo-album-plus'), 				'administrator',  	 'wppa_log', 			'wppa_log_page' );
	}
}

/* ADMIN STYLES */
add_action( 'admin_init', 'wppa_admin_styles' );

function wppa_admin_styles() {
global $wppa_api_version;
	wp_register_style( 'wppa_admin_style', WPPA_URL.'/wppa-admin-styles.css', '', $wppa_api_version );
	wp_enqueue_style( 'wppa_admin_style' );
}

/* ADMIN SCRIPTS */
add_action( 'admin_init', 'wppa_admin_scripts' );

function wppa_admin_scripts() {
global $wppa_api_version;
	wp_register_script( 'wppa_upload_script', WPPA_URL.'/js/wppa-multifile-compressed.js', '', $wppa_api_version );
	wp_enqueue_script( 'wppa_upload_script' );
	if ( is_file( WPPA_PATH.'/js/wppa-admin-scripts.min.js' ) ) {
		wp_register_script( 'wppa_admin_script', WPPA_URL.'/js/wppa-admin-scripts.min.js', '', $wppa_api_version );
	}
	else {
		wp_register_script( 'wppa_admin_script', WPPA_URL.'/js/wppa-admin-scripts.js', '', $wppa_api_version );
	}
	wp_enqueue_script( 'wppa_admin_script' );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script( 'jquery-form' );
 	wp_enqueue_script( 'wppa-utils', WPPA_URL . '/js/wppa-utils.js', array(), $wppa_api_version );
}

/* ADMIN PAGE PHP's */

// Album admin page
function wppa_admin() {
	wppa_grant_albums();
	require_once 'wppa-album-admin-autosave.php';
	require_once 'wppa-photo-admin-autosave.php';
	require_once 'wppa-album-covers.php';
	wppa_publish_scheduled();
	_wppa_admin();
}
// Upload admin page
function wppa_page_upload() {
	if ( wppa_is_user_blacklisted() ) wp_die(__( 'Uploading is temporary disabled for you' , 'wp-photo-album-plus') );
	wppa_grant_albums();
	require_once 'wppa-upload.php';
	_wppa_page_upload();
}
// Edit photo(s)
function wppa_edit_photo() {
	if ( wppa_is_user_blacklisted() ) wp_die(__( 'Editing is temporary disabled for you' , 'wp-photo-album-plus') );
	require_once 'wppa-photo-admin-autosave.php';
	wppa_publish_scheduled();
	_wppa_edit_photo();
}
// Import admin page
function wppa_page_import() {
	if ( wppa_is_user_blacklisted() ) wp_die(__( 'Importing is temporary disabled for you' , 'wp-photo-album-plus') );
	wppa_grant_albums();
	require_once 'wppa-import.php';
	echo '<script type="text/javascript">/* <![CDATA[ */wppa_import = "'.__('Import', 'wp-photo-album-plus').'"; wppa_update = "'.__('Update', 'wp-photo-album-plus').'";/* ]]> */</script>';
	_wppa_page_import();
}
// Moderate admin page
function wppa_page_moderate() {
	require_once 'wppa-photo-admin-autosave.php';
	wppa_publish_scheduled();
	_wppa_moderate_photos();
}
// Export admin page
function wppa_page_export() {
	require_once 'wppa-export.php';
	_wppa_page_export();
}
// Settings admin page
function wppa_page_options() {
	require_once 'wppa-settings-autosave.php';
	_wppa_page_options();
}
// Photo of the day admin page
function wppa_sidebar_page_options() {
	require_once 'wppa-potd-admin.php';
	wppa_publish_scheduled();
	_wppa_sidebar_page_options();
}
// Comments admin page
function wppa_comment_admin() {
	require_once 'wppa-comment-admin.php';
	_wppa_comment_admin();
}
// Help admin page
function wppa_page_help() {
	require_once 'wppa-help.php';
	_wppa_page_help();
}

/* GENERAL ADMIN */

// General purpose admin functions
require_once 'wppa-admin-functions.php';
require_once 'wppa-tinymce-shortcodes.php';
require_once 'wppa-tinymce-photo.php';

/* This is for the changelog text when an update is available */
global $pagenow;
if ( 'plugins.php' === $pagenow )
{
    // Changelog update message
    $file   = basename( __FILE__ );
    $folder = basename( dirname( __FILE__ ) );
    $hook = "in_plugin_update_message-{$folder}/{$file}";
    add_action( $hook, 'wppa_update_message_cb', 20, 2 ); // hook for function below
}
function wppa_update_message_cb( $plugin_data, $r )
{
    $output = '<span style="margin-left:10px;color:#FF0000;">Please Read the ' .
		'<a href="http://wppa.opajaap.nl/changelog/" target="_blank" >Changelog</a>' .
		' Details Before Upgrading.</span>';

    return print $output;
}


/* Add "donate" link to main plugins page */
add_filter('plugin_row_meta', 'wppa_donate_link', 10, 2);

/* Check multisite config */
add_action('admin_notices', 'wppa_verify_multisite_config');

/* Check for pending maintenance procs */
add_action('admin_notices', 'wppa_maintenance_messages');

/* Check for old style scripting */
add_action('admin_notices', 'wppa_scripts_are_obssolete');

// Check if tags system needs conversion
add_action( 'admin_init', 'wppa_check_tag_system' );

// Check if cats system needs conversion
add_action( 'admin_init', 'wppa_check_cat_system' );

// Activity feed
if ( true ) {
	add_action( 'do_meta_boxes', 'wppa_activity' );
}
function wppa_activity(){
	if ( function_exists( 'wp_add_dashboard_widget' ) ) {
		wp_add_dashboard_widget( 'wppa-activity', __( 'Recent WPPA activity', 'wp-photo-album-plus' ), 'wppa_show_activity_feed' ); //, $control_callback = null, $callback_args = null )
	}
}
function wppa_show_activity_feed() {
global $wpdb;

	// Recently uploaded photos
	echo '<h3>' . __( 'Recently uploaded photos', 'wp-photo-album-plus' ) . '</h3>';
	$photos = $wpdb->get_results( "SELECT * FROM `" . WPPA_PHOTOS . "` ORDER BY `timestamp` DESC LIMIT 5", ARRAY_A );
	if ( ! empty( $photos ) ) {
		echo
		'<table>';
		foreach( $photos as $photo ) {
			echo
			'<tr>' .
				'<td>' .
					'<a href="' . wppa_get_photo_url( $photo['id'] ) . '" target="_blank" >' .
						'<img src="' . wppa_get_thumb_url( $photo['id'] ) . '" style="max-width:50px;max-height:50px;" /> ' .
					'</a>' .
				'</td>' .
				'<td>';
					$usr = wppa_get_user_by( 'login', $photo['owner'] );
					if ( $usr ) {
						$usr = $usr -> display_name;
					}
					else {
						$usr = $photo['owner'];
					}
					echo
					sprintf( 	__( 'by %s in album %s', 'wp-photo-album-plus' ),
								'<b>' . $usr . '</b>',
								'<b>' . wppa_get_album_name( $photo['album'] ) . '</b> (' . $photo['album'] . ')'
								) .
					'<br />' .
					wppa_local_date( '', $photo['timestamp'] ) .
				'</td>' .
			'</tr />';
		}
		echo
		'</table>';
	}
	else {
		echo
		'<p>' .
			__( 'There are no recently uploaded photos', 'wp-photo-album-plus' ) .
		'</p>';
	}
	echo '<br />';

	// Recent comments
	echo '<h3>' . __( 'Recent comments on photos', 'wp-photo-album-plus' ) . '</h3>';
	$comments = $wpdb->get_results( "SELECT * FROM `" . WPPA_COMMENTS . "` ORDER BY `timestamp` DESC LIMIT 5", ARRAY_A );
	if ( ! empty( $comments ) ) {
		echo
		'<table>';
		foreach( $comments as $comment ) {
			$photo = wppa_cache_photo( $comment['photo'] );
			echo
			'<tr>' .
				'<td>' .
					'<a href="' . wppa_get_photo_url( $photo['id'] ) . '" target="_blank" >' .
						'<img src="' . wppa_get_thumb_url( $photo['id'] ) . '" style="max-width:50px;max-height:50px;" /> ' .
					'</a>' .
				'</td>' .
				'<td>';
					$usr = wppa_get_user_by( 'login', $comment['user'] );
					if ( $usr ) {
						$usr = $usr->display_name;
					}
					else {
						$usr = $comment['user'];
					}
					echo
					'<i>' . $comment['comment'] . '</i>' .
					'<br />' .
					sprintf(	__( 'by %s', 'wp-photo-album-plus' ),
								'<b>' . $usr . '</b>' ) .
					'<br />' .
					wppa_local_date( '', $photo['timestamp'] ) .
				'</td>' .
			'</tr>';
		}
		echo
		'</table>';
	}
	else {
		echo
		'<p>' .
			__( 'There are no recent comments on photos', 'wp-photo-album-plus' ) .
		'</p>';
	}

}