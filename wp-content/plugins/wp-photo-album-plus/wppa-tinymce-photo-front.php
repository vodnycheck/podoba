<?php
/* wppa-tinymce-photo-front.php
* Pachkage: wp-photo-album-plus
*
* Version 6.8.00
*
*/

if ( ! defined( 'ABSPATH' ) )
    die( "Can't load this file directly" );

add_action( 'init', 'wppa_tinymce_photo_action_init_front' );

function wppa_tinymce_photo_action_init_front() {

	if ( wppa_switch( 'photo_shortcode_enabled' ) && wppa_opt( 'photo_shortcode_fe_type' ) != '-none-' ) {

		add_filter( 'mce_buttons', 'wppa_filter_mce_photo_button_front', 11 );
		add_filter( 'mce_external_plugins', 'wppa_filter_mce_photo_plugin_front' );
	}
}

function wppa_filter_mce_photo_button_front( $buttons ) {

	// add a separation before our button.
	array_push( $buttons, ' ', 'wppa_photo_button' );
	return $buttons;
}

function wppa_filter_mce_photo_plugin_front( $plugins ) {

	// this plugin file will work the magic of our button
	if ( is_file( WPPA_PATH . '/js/wppa-tinymce-photo-front.min.js' ) ) {
		$file = 'js/wppa-tinymce-photo-front.min.js';
	}
	else {
		$file = 'js/wppa-tinymce-photo-front.js';
	}
	$plugins['wppaphoto'] = plugin_dir_url( __FILE__ ) . $file;
	return $plugins;
}

add_action( 'wp_head', 'wppa_inject_3_js' );

function wppa_inject_3_js() {
global $wppa_api_version;
static $done;
global $wpdb;

	if ( wppa_switch( 'photo_shortcode_enabled' ) && ! $done ) {

		// Find an existing photo
		$id = $wpdb->get_var( "SELECT `id` FROM " . WPPA_PHOTOS . " WHERE `ext` <> 'xxx' ORDER BY `timestamp` DESC LIMIT 1" );

		// Fake we are in a widget, to prevent wppa_get_picture_html() from bumping viewcount
		wppa( 'in_widget', true );

		// Things that wppa-tinymce.js AND OTHER MODULES!!! need to know
		echo
		'<script type="text/javascript">
		/* <![CDATA[ */
		wppaImageDirectory = "' . wppa_get_imgdir() . '";
		wppaAjaxUrl = "' . admin_url( 'admin-ajax.php' ) . '";
		wppaPhotoDirectory = "' . WPPA_UPLOAD_URL . '/";
		wppaNoPreview = "' . __( 'No Preview available', 'wp-photo-album-plus' ) . '";
		wppaTxtProcessing = "' . __( 'Processing...', 'wp-photo-album-plus' ) . '";
		wppaTxtDone = "' . __( 'Done!', 'wp-photo-album-plus' ) . '";
		wppaTxtErrUnable = "' . __( 'ERROR: unable to upload files.', 'wp-photo-album-plus' ) . '";
		wppaOutputType = "' . wppa_opt( 'photo_shortcode_fe_type' ) . '";
		wppaShortcodeTemplate = "' . esc_js( wppa_get_picture_html( array( 'id' => $id, 'type' => 'sphoto' ) ) ) . '";
		wppaShortcodeTemplateId = "' . $id . '.' . wppa_get_photo_item( $id, 'ext' ) . '";
		/* ]]> */
		</script>';

		// Reset faked widget
		wppa( 'in_widget', false );

		$done = true;

	}
}

