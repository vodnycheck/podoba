<?php
/* wppa-upload-widget.php
* Package: wp-photo-album-plus
*
* A wppa widget to upload photos
*
* Version 6.7.01
*/

class WppaUploadWidget extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'wppa_upload_widget', 'description' => __( 'Display upload photos dialog', 'wp-photo-album-plus' ) );
		parent::__construct( 'wppa_upload_widget', __( 'WPPA+ Upload Photos', 'wp-photo-album-plus' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		global $wpdb;

		require_once(dirname(__FILE__) . '/wppa-links.php');
		require_once(dirname(__FILE__) . '/wppa-styles.php');
		require_once(dirname(__FILE__) . '/wppa-functions.php');
		require_once(dirname(__FILE__) . '/wppa-thumbnails.php');
		require_once(dirname(__FILE__) . '/wppa-boxes-html.php');
		require_once(dirname(__FILE__) . '/wppa-slideshow.php');
		wppa_initialize_runtime();

		extract($args);
		$instance = wp_parse_args( (array) $instance,
									array( 	'title' 	=> __( 'Upload Photos', 'wp-photo-album-plus' ),
											'album' 	=> '0'
										));
 		$title = apply_filters( 'widget_title', $instance['title'] );
		$album = $instance['album'];

		if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `" . WPPA_ALBUMS . "` WHERE `id` = %d", $album ) ) ) {
			$album = '0';	// Album vanished
		}

		wppa_user_upload();	// Do the upload if required

		wppa( 'in_widget', 'upload' );
		wppa_bump_mocc();
		$mocc = wppa( 'mocc' );
		$is_responsive = wppa_opt( 'colwidth' ) == 'auto';

		if ( $is_responsive ) {	// Responsive widget
			$js = wppa_get_responsive_widget_js_html( $mocc );
		}
		else {
			$js = '';
		}
		$create = wppa_get_user_create_html( $album, wppa_opt( 'widget_width' ), 'widget' );
		$upload = wppa_get_user_upload_html( $album, wppa_opt( 'widget_width' ), 'widget', $is_responsive );

		// Anything to do?
		if ( ! $create && ! $upload ) {
			return;
		}

		$text =
		'<div' .
			' id="wppa-container-' . $mocc . '"' .
			' class="wppa-upload-widget"' .
			' style="margin-top:2px;margin-left:2px;"' .
			' >' .
			$js .
			$create .
			$upload .
		'</div>';

		echo $before_widget;
		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}
		echo $text;
		echo '<div style="clear:both"></div>';
		echo $after_widget;

		wppa( 'in_widget', false );
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['album'] = strval( intval( $new_instance['album'] ) );
		return $instance;
	}

	function form( $instance ) {

		$instance = wp_parse_args( (array) $instance, array( 'title' => __( 'Upload Photos', 'wp-photo-album-plus' ), 'album' => '0' ) );

		// Widget title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) );

		// Album selection
		$body = wppa_album_select_a( array( 'path' => wppa_switch( 'hier_albsel' ), 'selected' => $instance['album'], 'addselbox' => true ) );
		echo
		wppa_widget_selection_frame( $this, 'album', $body, __( 'Album', 'wp-photo-album-plus' ) );

	}
}

// register WppaUploadWidget
add_action('widgets_init', 'wppa_register_WppaUploadWidget' );

function wppa_register_WppaUploadWidget() {
	register_widget("WppaUploadWidget");
}