<?php
/* wppa-stereo-widget.php
* Package: wp-photo-album-plus
*
* display the top rated photos
* Version 6.7.01
*/

class wppaStereoWidget extends WP_Widget {

    /** constructor */
    function __construct() {
		$widget_ops = array( 'classname' => 'wppa_stereo_widget', 'description' => __( 'Display stereo photo settings dialog', 'wp-photo-album-plus' ) );
		parent::__construct( 'wppa_stereo_widget', __( 'WPPA+ Stereo Photo Settings', 'wp-photo-album-plus' ), $widget_ops );
    }

	/** @see WP_Widget::widget */
    function widget( $args, $instance ) {
		global $wpdb;

		require_once(dirname(__FILE__) . '/wppa-links.php');
		require_once(dirname(__FILE__) . '/wppa-styles.php');
		require_once(dirname(__FILE__) . '/wppa-functions.php');
		require_once(dirname(__FILE__) . '/wppa-thumbnails.php');
		require_once(dirname(__FILE__) . '/wppa-boxes-html.php');
		require_once(dirname(__FILE__) . '/wppa-slideshow.php');
		wppa_initialize_runtime();

        wppa( 'in_widget', 'stereo' );
		wppa_bump_mocc();

		extract( $args );

		$instance 		= wp_parse_args( (array) $instance, array( 'title' => __('Stereo Photo Settings', 'wp-photo-album-plus') ) );


 		$widget_title 	= apply_filters('widget_title', $instance['title'] );

		$widget_content = "\n".'<!-- WPPA+ stereo Widget start -->';

		$widget_content .= wppa_get_stereo_html();

		$widget_content .= '<div style="clear:both"></div>';
		$widget_content .= "\n".'<!-- WPPA+ stereo Widget end -->';

		echo "\n" . $before_widget;
		if ( !empty( $widget_title ) ) { echo $before_title . $widget_title . $after_title; }
		echo $widget_content . $after_widget;

		wppa( 'in_widget', false );
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] 			= strip_tags( $new_instance['title'] );

        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {

		//Defaults
		$instance 		= wp_parse_args( (array) $instance, array( 'title' => __( 'Stereo Photo Settings', 'wp-photo-album-plus ' ) ) );

		// Title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) );

    }

} // class wppaStereoWidget

// register wppaStereoWidget widget
add_action('widgets_init', 'wppa_register_wppaStereoWidget' );

function wppa_register_wppaStereoWidget() {
	register_widget("wppaStereoWidget");
}
