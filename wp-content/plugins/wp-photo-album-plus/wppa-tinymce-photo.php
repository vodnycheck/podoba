<?php
/* wppa-tinymce-photo.php
* Pachkage: wp-photo-album-plus
*
* Version 6.7.10
*
*/

if ( ! defined( 'ABSPATH' ) )
    die( "Can't load this file directly" );

add_action( 'init', 'wppa_tinymce_photo_action_init' ); // 'admin_init'

function wppa_tinymce_photo_action_init() {

	if ( wppa_switch( 'photo_shortcode_enabled' ) ) {

		add_filter( 'mce_buttons', 'wppa_filter_mce_photo_button', 11 );
		add_filter( 'mce_external_plugins', 'wppa_filter_mce_photo_plugin' );
	}
}

function wppa_filter_mce_photo_button( $buttons ) {

	// add a separation before our button.
	array_push( $buttons, ' ', 'wppa_photo_button' );
	return $buttons;
}

function wppa_filter_mce_photo_plugin( $plugins ) {

	// this plugin file will work the magic of our button
		if ( is_file( WPPA_PATH . '/js/wppa-tinymce-photo.min.js' ) ) {
		$file = 'js/wppa-tinymce-photo.min.js';
	}
	else {
		$file = 'js/wppa-tinymce-photo.js';
	}
	$plugins['wppaphoto'] = plugin_dir_url( __FILE__ ) . $file;
	return $plugins;
}

add_action( 'admin_head', 'wppa_inject_2_js' );

function wppa_inject_2_js() {
global $wppa_api_version;
static $done;

	if ( wppa_switch( 'photo_shortcode_enabled' ) && ! $done ) {

		// Things that wppa-tinymce.js AND OTHER MODULES!!! need to know
		echo('<script type="text/javascript">'."\n");
		echo('/* <![CDATA[ */'."\n");
			echo("\t".'wppaImageDirectory = "'.wppa_get_imgdir().'";'."\n");
			echo("\t".'wppaAjaxUrl = "'.admin_url('admin-ajax.php').'";'."\n");
			echo("\t".'wppaPhotoDirectory = "'.WPPA_UPLOAD_URL.'/";'."\n");
			echo("\t".'wppaThumbDirectory = "'.WPPA_UPLOAD_URL.'/thumbs/";'."\n");
			echo("\t".'wppaTempDirectory = "'.WPPA_UPLOAD_URL.'/temp/";'."\n");
			echo("\t".'wppaFontDirectory = "'.WPPA_UPLOAD_URL.'/fonts/";'."\n");
			echo("\t".'wppaNoPreview = "'.__('No Preview available', 'wp-photo-album-plus').'";'."\n");
			echo("\t".'wppaVersion = "'.$wppa_api_version.'";'."\n");
			echo("\t".'wppaSiteUrl = "'.site_url().'";'."\n");
			echo("\t".'wppaWppaUrl = "'.WPPA_URL.'";'."\n");
			echo("\t".'wppaIncludeUrl = "'.trim(includes_url(), '/').'";'."\n");
			echo("\t".'wppaUIERR = "'.__('Unimplemented virtual album', 'wp-photo-album-plus').'";');
			echo("\t".'wppaTxtProcessing = "'.__('Processing...', 'wp-photo-album-plus').'";');
			echo("\t".'wppaTxtDone = "'.__('Done!', 'wp-photo-album-plus').'";');
			echo("\t".'wppaTxtErrUnable = "'.__( 'ERROR: unable to upload files.', 'wp-photo-album-plus' ).'";');
		echo("/* ]]> */\n");
		echo("</script>\n");

		$done = true;
	}
}

