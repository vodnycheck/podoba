<?php
/* wppa-super-view-widget.php
* Package: wp-photo-album-plus
*
* ask the album / display you want
* Version 6.7.01
*/


class WppaSuperView extends WP_Widget {

    /** constructor */
    function __construct() {
		$widget_ops = array( 'classname' => 'wppa_super_view', 'description' => __( 'Display a super selection dialog', 'wp-photo-album-plus' ) );
		parent::__construct( 'wppa_super_view', __( 'WPPA+ Super View', 'wp-photo-album-plus'), $widget_ops );
    }

	/** @see WP_Widget::widget */
    function widget($args, $instance) {
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
		$instance = wp_parse_args( (array) $instance, array(
														'title' => __( 'Super View', 'wp-photo-album-plus' ),
														'root'	=> '0',
														'sort'	=> true,
														) );

 		$widget_title 	= apply_filters( 'widget_title', $instance['title'] );
		$album_root 	= $instance['root'];
		$sort 			= wppa_checked( $instance['sort'] ) ? true : false;

		wppa( 'in_widget', 'superview' );
		wppa_bump_mocc();

		$widget_content = wppa_get_superview_html( $album_root, $sort );

		wppa( 'in_widget', false );

		echo $before_widget . $before_title . $widget_title . $after_title . $widget_content . $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] 	= strip_tags($new_instance['title']);
		$instance['root'] 	= $new_instance['root'];
		$instance['sort']	= $new_instance['sort'];

        return $instance;
    }

    /** @see WP_Widget::form */
    function form( $instance ) {

		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 	'title' => __( 'Super View' , 'wp-photo-album-plus' ),
																'root' 	=> '0',
																'sort'	=> true
															) );

		// Title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) );

		// Root
		$body = wppa_album_select_a( array( 'selected' => $instance['root'], 'addall' => true, 'addseparate' => true, 'addgeneric' => true, 'path' => true ) );
		echo
		wppa_widget_selection_frame( $this, 'root', $body, __( 'Enable (sub)albums of', 'wp-photo-album-plus' ) );

		// Sort
		echo
		wppa_widget_checkbox( 	$this,
								'sort',
								$instance['sort'],
								__( 'Sort alphabetically', 'wp-photo-album-plus' ),
								__( 'If unticked, the album sort method for the album or system will be used', 'wp-photo-album-plus' )
								);

    }

} // class WppaSuperView

// register WppaSuperView widget
add_action('widgets_init', 'wppa_register_wppaSuperView' );

function wppa_register_wppaSuperView() {
	register_widget("WppaSuperView");
}
