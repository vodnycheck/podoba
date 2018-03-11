<?php
/* wppa-multitag-widget.php
* Package: wp-photo-album-plus
*
* display the multitag widget
* Version 6.7.09
*
*/

class MultitagPhotos extends WP_Widget {

    /** constructor */
    function __construct() {
		$widget_ops = array( 'classname' => 'wppa_multitag_photos', 'description' => __( 'Display checkboxes to select photos by one or more tags', 'wp-photo-album-plus' ) );
		parent::__construct( 'wppa_multitag_photos', __( 'WPPA+ Photo Tags Filter', 'wp-photo-album-plus' ), $widget_ops );

		$this -> defaults['title'] = __( 'Photo Tags Filter', 'wp-photo-album-plus' );
    }

	var $defaults = array( 	'title' 	=> '',
							'cols' 		=> '2',
							'tags' 		=> '',
							);

	/** @see WP_Widget::widget */
    function widget($args, $instance) {
		global $widget_content;

		wppa( 'in_widget', 'multitag' );
		wppa_bump_mocc();

		require_once(dirname(__FILE__) . '/wppa-links.php');
		require_once(dirname(__FILE__) . '/wppa-styles.php');
		require_once(dirname(__FILE__) . '/wppa-functions.php');
		require_once(dirname(__FILE__) . '/wppa-thumbnails.php');
		require_once(dirname(__FILE__) . '/wppa-boxes-html.php');
		require_once(dirname(__FILE__) . '/wppa-slideshow.php');
		wppa_initialize_runtime();

        extract( $args );

		$instance = wppa_parse_args( (array) $instance, $this -> defaults );

 		$widget_title = apply_filters( 'widget_title', $instance['title'] );

		// Display the widget
		echo $before_widget;

		if ( ! empty( $widget_title ) ) { echo $before_title . $widget_title . $after_title; }

		$tags = is_array( $instance['tags'] ) ? implode( ',', $instance['tags'] ) : '';

		echo '<div class="wppa-multitag-widget" >' . wppa_get_multitag_html( $instance['cols'], $tags ) . '</div>';
		echo '<div style="clear:both"></div>';
		echo $after_widget;

		wppa( 'in_widget', false );
    }

    /** @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {

		// Defaults
		$instance = wppa_parse_args( $new_instance, $this->defaults );

		// Sanitize
		$instance['title'] 	= strip_tags( $instance['title'] );
		$instance['cols'] 	= min( max( '1', $instance['cols'] ), '6' );

        return $instance;
    }

    /** @see WP_Widget::form */
    function form( $instance ) {

		//Defaults
		$instance = wppa_parse_args( $instance, $this->defaults );

		$title 		= $instance['title'];
		$cols 		= $instance['cols'];
		$stags 		= (array) $instance['tags'];
		if ( empty( $stags ) ) $stags = array();

		// Title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) );

		// Columns
		echo
		wppa_widget_number( $this, 'cols', $instance['cols'], __( 'Number of columns', 'wp-photo-album-plus' ), '1', '6' );

		// Tags selection
		$tags = wppa_get_taglist();
		$body = '<option value="" >' . __( '--- all ---', 'wp-photo-album-plus' ) . '</option>';
		if ( $tags ) foreach ( array_keys( $tags ) as $tag ) {
			if ( in_array( $tag, $stags ) ) $sel = ' selected="selected"'; else $sel = '';
			$body .= '<option value="' . $tag . '"' . $sel . ' >' . $tag . '</option>';
		}
		echo
		wppa_widget_selection_frame( $this, 'tags', $body, __( 'Select multiple tags or --- all ---', 'wp-photo-album-plus' ), 'multi' );

		// Currently selected
		if ( isset( $instance['tags']['0'] ) && $instance['tags']['0'] ) $s = implode( ',', $instance['tags'] ); else $s = __( '--- all ---', 'wp-photo-album-plus' );
		echo
		'<p style="word-break:break-all;" >' .
			__( 'Currently selected tags', 'wp-photo-album-plus' ) . ':' .
			'<br />' .
			'<b>' .
				$s .
			'</b>' .
		'</p>';
    }

} // class MultitagPhotos

// register Photo Tags widget
add_action('widgets_init', 'wppa_register_MultitagPhotos' );

function wppa_register_MultitagPhotos() {
	register_widget("MultitagPhotos");
}
