<?php
/* wppa-qr-widget.php
* Package: wp-photo-album-plus
*
* display qr code
* Version 6.8.00
*/


class wppaQRWidget extends WP_Widget {

    /** constructor */
    function __construct() {
		$widget_ops = array( 'classname' => 'qr_widget', 'description' => __( 'Display the QR code of the current url' , 'wp-photo-album-plus' ) );
		parent::__construct( 'qr_widget', __( 'WPPA+ QR Widget', 'wp-photo-album-plus' ), $widget_ops );
    }

	/** @see WP_Widget::widget */
    function widget( $args, $instance ) {
		global $wpdb;
		global $widget_content;

 		require_once(dirname(__FILE__) . '/wppa-links.php');
		require_once(dirname(__FILE__) . '/wppa-styles.php');
		require_once(dirname(__FILE__) . '/wppa-functions.php');
		require_once(dirname(__FILE__) . '/wppa-thumbnails.php');
		require_once(dirname(__FILE__) . '/wppa-boxes-html.php');
		require_once(dirname(__FILE__) . '/wppa-slideshow.php');
		wppa_initialize_runtime();

		extract( $args );

 		$title 			= apply_filters('widget_title', empty( $instance['title'] ) ? __( 'QR Widget' , 'wp-photo-album-plus' ) : $instance['title']);
		$qrsrc 			= 'http' . ( is_ssl() ? 's' : '' ) . '://api.qrserver.com/v1/create-qr-code/' .
							'?format=svg' .
							'&size='. wppa_opt( 'qr_size' ).'x'.wppa_opt( 'qr_size' ) .
							'&color='.trim( wppa_opt( 'qr_color' ), '#' ) .
							'&bgcolor='.trim( wppa_opt( 'qr_bgcolor' ), '#' ) .
							'&data=' . urlencode( $_SERVER['SCRIPT_URI'] );

		// Get the qrcode
		$qrsrc = wppa_create_qrcode_cache( $qrsrc );

		// Make the html
		$widget_content =
		'<div style="text-align:center;" >' .
			'<img id="wppa-qr-img" src="' . $qrsrc . '" title="' . esc_attr( $_SERVER['SCRIPT_URI'] ) . '" alt="' . __('QR code', 'wp-photo-album-plus') . '" />' .
		'</div>' .
		'<div style="clear:both" ></div>';

		$widget_content .=
		'<script type="text/javascript">
			/*[CDATA[*/
			var wppaQRUrl = document.location.href;

			wppaConsoleLog( "doc.loc.href = " + wppaQRUrl );

			function wppaQRUpdate( arg ) {
				if ( arg ) {
					wppaQRUrl = arg;
				}
				wppaAjaxSetQrCodeSrc( wppaQRUrl, "wppa-qr-img" );
				document.getElementById( "wppa-qr-img" ).title = wppaQRUrl;
				return;
			}

			jQuery(document).ready(function(){
				wppaQRUpdate();
			});
			/*]]*/
		</script>';

		echo $before_widget . $before_title . $title . $after_title . $widget_content . $after_widget;
    }

    /** @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

        return $instance;
    }

    /** @see WP_Widget::form */
    function form( $instance ) {

		// Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => __( 'QR Widget' , 'wp-photo-album-plus') ) );

		// Title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) );

		// Explanation
		echo
		'<p>' .
			__( 'You can set the sizes and colors in this widget in the <b>Photo Albums -> Settings</b> admin page Table IX-K1.x.', 'wp-photo-album-plus' ) .
		'</p>';

    }

} // class wppaQRWidget

// register wppaQRWidget widget
add_action('widgets_init', 'wppa_register_QRWidget' );

function wppa_register_QRWidget() {
	register_widget( "wppaQRWidget" );
}
