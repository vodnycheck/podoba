<?php
/* wppa-bestof-widget.php
* Package: wp-photo-album-plus
*
* display the best rated photos
* Version 6.7.06
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

class BestOfWidget extends WP_Widget {

    /** constructor */
    function __construct() {
		$widget_ops = array( 'classname' => 'wppa_bestof_widget', 'description' => __( 'Display thumbnails or owners of top rated photos', 'wp-photo-album-plus' ) );
		parent::__construct( 'wppa_bestof_widget', __( 'WPPA+ Best Of Photos', 'wp-photo-album-plus' ), $widget_ops );
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

        wppa( 'in_widget', 'bestof' );
		wppa_bump_mocc();

		extract( $args );

		$instance 		= wp_parse_args( (array) $instance, array(
														'title' 	=> __( 'Best Of Photos', 'wp-photo-album-plus' ),
														'count' 	=> '10',
														'sortby' 	=> 'maxratingcount',
														'display' 	=> 'photo',
														'period' 	=> 'thisweek',
														'maxratings'=> 'yes',
														'meanrat' 	=> 'yes',
														'ratcount' 	=> 'yes',
														'linktype' 	=> 'none',
														) );

 		$widget_title 	= apply_filters( 'widget_title', $instance['title'] );
		$page 			= in_array( $instance['linktype'], wppa( 'links_no_page' ) ) ? '' : wppa_get_the_landing_page( 'bestof_widget_linkpage', __( 'Best Of Photos', 'wp-photo-album-plus' ) );
		$count 			= $instance['count'] ? $instance['count'] : '10';
		$sortby 		= $instance['sortby'];
		$display 		= $instance['display'];
		$period 		= $instance['period'];
		$maxratings 	= wppa_checked( $instance['maxratings'] ) ? 'yes' : '';
		$meanrat		= wppa_checked( $instance['meanrat'] ) ? 'yes' : '';
		$ratcount 		= wppa_checked( $instance['ratcount'] ) ? 'yes' : '';
		$linktype 		= $instance['linktype'];
		$size 			= wppa_opt( 'widget_width' );
		$lineheight 	= wppa_opt( 'fontsize_widget_thumb' ) * 1.5;

		$widget_content = "\n".'<!-- WPPA+ BestOf Widget start -->';

		$widget_content .= wppa_bestof_html( array ( 	'page' 			=> $page,
														'count' 		=> $count,
														'sortby' 		=> $sortby,
														'display' 		=> $display,
														'period' 		=> $period,
														'maxratings' 	=> $maxratings,
														'meanrat' 		=> $meanrat,
														'ratcount' 		=> $ratcount,
														'linktype' 		=> $linktype,
														'size' 			=> $size,
														'lineheight' 	=> $lineheight,
														) );

		$widget_content .= '<div style="clear:both"></div>';
		$widget_content .= "\n".'<!-- WPPA+ BestOf Widget end -->';

		echo "\n" . $before_widget;
		if ( ! empty( $widget_title ) ) {
			echo $before_title . $widget_title . $after_title;
		}
		echo $widget_content . $after_widget;

		wppa( 'in_widget', false );
    }

    /** @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title'] 		= strip_tags( $new_instance['title'] );
		$instance['count'] 		= $new_instance['count'];
		$instance['sortby'] 	= $new_instance['sortby'];
		$instance['display'] 	= $new_instance['display'];
		$instance['period'] 	= $new_instance['period'];
		$instance['maxratings'] = $new_instance['maxratings'];
		$instance['meanrat']	= $new_instance['meanrat'];
		$instance['ratcount'] 	= $new_instance['ratcount'];
		$instance['linktype'] 	= $new_instance['linktype'];

        return $instance;
    }

    /** @see WP_Widget::form */
    function form( $instance ) {

		// Defaults
		$instance 		= wp_parse_args( (array) $instance, array(
														'title' 	=> __( 'Best Of Photos', 'wp-photo-album-plus' ),
														'count' 	=> '10',
														'sortby' 	=> 'maxratingcount',
														'display' 	=> 'photo',
														'period' 	=> 'thisweek',
														'maxratings'=> 'yes',
														'meanrat' 	=> 'yes',
														'ratcount' 	=> 'yes',
														'linktype' 	=> 'none'
														) );

		// WP Bug?
		if ( ! $instance['count'] ) {
			$instance['count'] = '10';
		}

		// Widget Title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) ) .

		// Max number to diaplsy
		wppa_widget_number( $this, 'count', $instance['count'], __( 'Max number of thumbnails', 'wp-photo-album-plus' ), '1', '25' );

		// What to display
		$options 	= array( 	__( 'Photos', 'wp-photo-album-plus' ),
								__( 'Owners', 'wp-photo-album-plus' ),
								);
		$values 	= array(	'photo',
								'owner',
								);

		echo
		wppa_widget_selection( $this, 'display', $instance['display'], __( 'Select photos or owners', 'wp-photo-album-plus' ), $options, $values, array(), '' );

		// Period
		$options 	= array( 	__( 'Last week', 'wp-photo-album-plus' ),
								__( 'This week', 'wp-photo-album-plus' ),
								__( 'Last month', 'wp-photo-album-plus' ),
								__( 'This month', 'wp-photo-album-plus' ),
								__( 'Last year', 'wp-photo-album-plus' ),
								__( 'This year', 'wp-photo-album-plus' ),
								);
		$values 	= array( 	'lastweek',
								'thisweek',
								'lastmonth',
								'thismonth',
								'lastyear',
								'thisyear',
								);
		echo
		wppa_widget_selection( $this, 'period', $instance['period'], __( 'Limit to ratings given during', 'wp-photo-album-plus' ), $options, $values, array(), '' );

		// Sort by
		$options 	= array( 	__( 'Number of max ratings', 'wp-photo-album-plus' ),
								__( 'Mean value', 'wp-photo-album-plus' ),
								__( 'Number of votes', 'wp-photo-album-plus' ),
								);
		$values 	= array( 	'maxratingcount',
								'meanrating',
								'ratingcount',
								);
		echo
		wppa_widget_selection( $this, 'sortby', $instance['sortby'], __( 'Sort by', 'wp-photo-album-plus' ), $options, $values, array(), '' ) .

		// Number of max ratings
		wppa_widget_checkbox( $this, 'maxratings', $instance['maxratings'], __( 'Show number of max ratings', 'wp-photo-album-plus' ) ) .

		// Mean rating
		wppa_widget_checkbox( $this, 'meanrat', $instance['meanrat'], __( 'Show mean rating', 'wp-photo-album-plus') ) .

		// Number of ratings
		wppa_widget_checkbox( $this, 'ratcount', $instance['ratcount'], __( 'Show number of ratings', 'wp-photo-album-plus') );

		// Link to
		$options 	= array( 	__( '--- none ---', 'wp-photo-album-plus' ),
								__( 'The authors album(s)', 'wp-photo-album-plus' ),
								__( 'The photos in the authors album(s), thumbnails', 'wp-photo-album-plus' ),
								__( 'The photos in the authors album(s), slideshow', 'wp-photo-album-plus' ),
								__( 'All the authors photos, thumbnails', 'wp-photo-album-plus' ),
								__( 'All the authors photos, slideshow', 'wp-photo-album-plus' ),
								__( 'Lightbox single image', 'wp-photo-album-plus' ),
								);
		$values 	= array( 	'none',
								'owneralbums',
								'ownerphotos',
								'ownerphotosslide',
								'upldrphotos',
								'upldrphotosslide',
								'lightboxsingle',
								);
		echo
		wppa_widget_selection( $this, 'linktype', $instance['linktype'], __( 'Link to', 'wp-photo-album-plus' ), $options, $values, array(), '' );

    }

} // class BestOfWidget

// register BestOfWidget widget
add_action('widgets_init', 'wppa_register_BestOfWidget' );

function wppa_register_BestOfWidget() {
	register_widget("BestOfWidget");
}