<?php
/* wppa-gp-widget.php
* Package: wp-photo-album-plus
*
* A text widget that interpretes wppa shortcodes
*
* Version 6.7.01
*/

class WppaGpWidget extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'wppa_gp_widget', 'description' => __( 'General purpose widget that may contain [wppa][/wppa] shortcodes', 'wp-photo-album-plus' ) );
		parent::__construct( 'wppa_gp_widget', __( 'WPPA+ Text', 'wp-photo-album-plus' ), $widget_ops );
	}

	function widget( $args, $instance ) {

		require_once(dirname(__FILE__) . '/wppa-links.php');
		require_once(dirname(__FILE__) . '/wppa-styles.php');
		require_once(dirname(__FILE__) . '/wppa-functions.php');
		require_once(dirname(__FILE__) . '/wppa-thumbnails.php');
		require_once(dirname(__FILE__) . '/wppa-boxes-html.php');
		require_once(dirname(__FILE__) . '/wppa-slideshow.php');
		wppa_initialize_runtime();

		extract( $args );
		wppa( 'in_widget', 'gp' );
		wppa_bump_mocc();

		$instance 	= wp_parse_args( (array) $instance, array( 'title' => __( 'Text', 'wp-photo-album-plus' ), 'text' => '', 'loggedinonly' => false ) );
 		$title 		= apply_filters( 'widget_title', $instance['title'] );

		// Anything to do here?
		if ( wppa_checked( $instance['loggedinonly'] ) && ! is_user_logged_in() ) {
			return;
		}

		// Open the widget
		echo $before_widget;

		// Title optional
		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		// Body
		$text = $instance['text'];
		if ( wppa_checked( $instance['filter'] ) ) {	// Do wpautop BEFORE do_shortcode
			$text = wpautop( $text );
		}
		$text = do_shortcode( $text );
		$text = apply_filters( 'widget_text', $text );	// If shortcode at wppa filter priority, insert result. See wppa-filter.php

		echo '<div class="wppa-gp-widget" style="margin-top:2px; margin-left:2px;" >' . $text . '</div>';
		echo '<div style="clear:both"></div>';

		// Close widget
		echo $after_widget;

		wppa( 'in_widget', false );
		wppa( 'fullsize', '' );	// Reset to prevent inheritage of wrong size in case widget is rendered before main column

	}

	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title'] 			= strip_tags( $new_instance['title'] );
		if ( current_user_can('unfiltered_html') )
			$instance['text'] 		=  $new_instance['text'];
		else
			$instance['text'] 		= stripslashes( wp_filter_post_kses( addslashes( $new_instance['text'] ) ) ); // wp_filter_post_kses() expects slashed
		$instance['filter'] 		= $new_instance['filter'];
		$instance['loggedinonly'] 	= $new_instance['loggedinonly'];
		return $instance;
	}

	function form( $instance ) {

		$instance = wp_parse_args( (array) $instance, array( 'title' => __( 'Text', 'wp-photo-album-plus' ), 'text' => '', 'filter' => false, 'loggedinonly' => false ) );

		// Widget title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) ) .

		// Text area
		wppa_widget_textarea( $this, 'text', $instance['text'], __( 'Enter the content just like a normal text widget. This widget will interpret [wppa] shortcodes', 'wp-photo-album-plus' ) ) .

		// Run wpautop?
		wppa_widget_checkbox( $this, 'filter', $instance['filter'], __( 'Automatically add paragraphs', 'wp-photo-album-plus' ) ) .

		// Logged in only?
		wppa_widget_checkbox( $this, 'loggedinonly', $instance['loggedinonly'], __( 'Show to logged in users only', 'wp-photo-album-plus' ) );

	}
}
// register WppaGpWidget widget
add_action( 'widgets_init', 'wppa_register_WppaGpWidget' );

function wppa_register_WppaGpWidget() {
	register_widget( "WppaGpWidget" );
}