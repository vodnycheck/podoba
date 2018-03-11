<?php
/* wppa-lasten-widget.php
* Package: wp-photo-album-plus
*
* display the last uploaded photos
* Version 6.7.01
*/

class LasTenWidget extends WP_Widget {

    /** constructor */
    function __construct() {
		$widget_ops = array( 'classname' => 'wppa_lasten_widget', 'description' => __( 'Display most recently uploaded photos', 'wp-photo-album-plus' ) );
		parent::__construct( 'wppa_lasten_widget', __( 'WPPA+ Last Ten Photos', 'wp-photo-album-plus' ), $widget_ops );
    }

	/** @see WP_Widget::widget */
    function widget($args, $instance) {
		global $wpdb;
		global $wppa_opt;

		require_once(dirname(__FILE__) . '/wppa-links.php');
		require_once(dirname(__FILE__) . '/wppa-styles.php');
		require_once(dirname(__FILE__) . '/wppa-functions.php');
		require_once(dirname(__FILE__) . '/wppa-thumbnails.php');
		require_once(dirname(__FILE__) . '/wppa-boxes-html.php');
		require_once(dirname(__FILE__) . '/wppa-slideshow.php');
		wppa_initialize_runtime();

		wppa( 'in_widget', 'lasten' );
		wppa_bump_mocc();

        extract( $args );

		$instance 		= wp_parse_args( (array) $instance, array(
														'title' => __( 'Last Ten Photos', 'wp-photo-album-plus' ),
														'album' => '',
														'albumenum' => '',
														'timesince' => 'yes',
														'display' => 'thumbs',
														'includesubs' => 'no',
														) );
		$widget_title 	= apply_filters('widget_title', $instance['title'] );
		$page 			= in_array( wppa_opt( 'lasten_widget_linktype' ), wppa( 'links_no_page' ) ) ?
							'' :
							wppa_get_the_landing_page( 'lasten_widget_linkpage', __( 'Last Ten Photos', 'wp-photo-album-plus' ) );
		$max  			= wppa_opt( 'lasten_count' );
		$album 			= $instance['album'];
		$timesince 		= wppa_checked( $instance['timesince'] ) ? 'yes' : 'no';
		$display 		= $instance['display'];
		$albumenum 		= $instance['albumenum'];
		$subs 			= wppa_checked( $instance['includesubs'] );

		switch ( $album ) {
			case '-99': // 'Multiple see below' is a list of id, seperated by comma's
				$album = str_replace( ',', '.', $albumenum );
				if ( $subs ) {
					$album = wppa_expand_enum( wppa_alb_to_enum_children( $album ) );
				}
				$album = str_replace( '.', ',', $album );
				break;
			case '0': // ---all---
				break;
			case '-2': // ---generic---
				$albs = $wpdb->get_results( "SELECT `id` FROM `" . WPPA_ALBUMS . "` WHERE `a_parent` = '0'", ARRAY_A );
				$album = '';
				foreach ( $albs as $alb ) {
					$album .= '.' . $alb['id'];
				}
				$album = ltrim( $album, '.' );
				if ( $subs ) {
					$album = wppa_expand_enum( wppa_alb_to_enum_children( $album ) );
				}
				$album = str_replace( '.', ',', $album );
				break;
			default:
				if ( $subs ) {
					$album = wppa_expand_enum( wppa_alb_to_enum_children( $album ) );
					$album = str_replace( '.', ',', $album );
				}
				break;
		}
		$album = trim( $album, ',' );

		// Eiter look at timestamp or at date/time modified
		$order_by = wppa_switch( 'lasten_use_modified' ) ? 'modified' : 'timestamp';

		// If you want only 'New' photos in the selection, the period must be <> 0;
		if ( wppa_switch( 'lasten_limit_new' ) && wppa_opt( 'max_photo_newtime' ) ) {
			$newtime = " `" . $order_by . "` >= ".( time() - wppa_opt( 'max_photo_newtime' ) );
			if ( $album ) {
				$q = "SELECT * FROM `".WPPA_PHOTOS."` WHERE (".$newtime.") AND `album` IN ( ".$album." ) AND ( `status` <> 'pending' AND `status` <> 'scheduled' ) ORDER BY `" . $order_by . "` DESC LIMIT " . $max;
			}
			else {
				$q = "SELECT * FROM `".WPPA_PHOTOS."` WHERE (".$newtime.") AND `status` <> 'pending' AND `status` <> 'scheduled' ORDER BY `" . $order_by . "` DESC LIMIT " . $max;
			}
		}
		else {
			if ( $album ) {
				$q = "SELECT * FROM `".WPPA_PHOTOS."` WHERE `album` IN ( ".$album." ) AND ( `status` <> 'pending' AND `status` <> 'scheduled' ) ORDER BY `" . $order_by . "` DESC LIMIT " . $max;
			}
			else {
				$q = "SELECT * FROM `".WPPA_PHOTOS."` WHERE `status` <> 'pending' AND `status` <> 'scheduled' ORDER BY `" . $order_by . "` DESC LIMIT " . $max;
			}
		}

		$thumbs 		= $wpdb->get_results( $q, ARRAY_A );

		$widget_content = "\n".'<!-- WPPA+ LasTen Widget start -->';
		$maxw 			= wppa_opt( 'lasten_size' );
		$maxh 			= $maxw;
		$lineheight 	= wppa_opt( 'fontsize_widget_thumb' ) * 1.5;
		$maxh 			+= $lineheight;

		if ( $timesince == 'yes' ) $maxh += $lineheight;

		$count = '0';

		if ( $thumbs ) foreach ( $thumbs as $image ) {

			$thumb = $image;

			// Make the HTML for current picture
			if ( $display == 'thumbs' ) {
				$widget_content .= "\n".'<div class="wppa-widget" style="width:'.$maxw.'px; height:'.$maxh.'px; margin:4px; display:inline; text-align:center; float:left;">';
			}
			else {
				$widget_content .= "\n".'<div class="wppa-widget" >';
			}
			if ( $image ) {
				$no_album = !$album;
				if ($no_album) $tit = __( 'View the most recent uploaded photos', 'wp-photo-album-plus' ); else $tit = esc_attr(__(stripslashes($image['description'])));
				$link       = wppa_get_imglnk_a('lasten', $image['id'], '', $tit, '', $no_album, str_replace( ',', '.', $album ) );
				$file       = wppa_get_thumb_path($image['id']);
				$imgstyle_a = wppa_get_imgstyle_a( $image['id'], $file, $maxw, 'center', 'ltthumb');
				$imgurl 	= wppa_get_thumb_url( $image['id'], true, '', $imgstyle_a['width'], $imgstyle_a['height'] );
				$imgevents 	= wppa_get_imgevents('thumb', $image['id'], true);
				$title 		= $link ? esc_attr(stripslashes($link['title'])) : '';

				$widget_content .= wppa_get_the_widget_thumb('lasten', $image, $album, $display, $link, $title, $imgurl, $imgstyle_a, $imgevents);

				$widget_content .= "\n\t".'<div style="font-size:' . wppa_opt( 'fontsize_widget_thumb' ) . 'px; line-height:'.$lineheight.'px;">';
				if ( $timesince == 'yes' ) {
					$widget_content .= "\n\t".'<div>'.wppa_get_time_since( $image[$order_by] ).'</div>';
				}
				$widget_content .= '</div>';
			}
			else {	// No image
				$widget_content .= __( 'Photo not found', 'wp-photo-album-plus' );
			}
			$widget_content .= "\n".'</div>';
			$count++;
			if ( $count == wppa_opt( 'lasten_count' ) ) break;

		}
		else $widget_content .= __( 'There are no uploaded photos (yet)', 'wp-photo-album-plus' );

		$widget_content .= '<div style="clear:both"></div>';
		$widget_content .= "\n".'<!-- WPPA+ LasTen Widget end -->';

		echo "\n" . $before_widget;
		if ( !empty( $widget_title ) ) { echo $before_title . $widget_title . $after_title; }
		echo $widget_content . $after_widget;

		wppa( 'in_widget', false );
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] 			= strip_tags($new_instance['title']);
		$instance['album'] 			= strval( intval( $new_instance['album'] ) );
		$instance['albumenum'] 		= $new_instance['albumenum'];
		if ( $instance['album'] != '-99' ) $instance['albumenum'] = '';
		$instance['timesince'] 		= $new_instance['timesince'];
		$instance['display'] 		= $new_instance['display'];
		$instance['includesubs'] 	= $new_instance['includesubs'];

        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
		global $wppa_opt;
		//Defaults
		$instance 		= wp_parse_args( (array) $instance, array(
															'title' 		=> __( 'Last Ten Photos', 'wp-photo-album-plus' ),
															'album' 		=> '0',
															'albumenum' 	=> '',
															'timesince' 	=> 'yes',
															'display' 		=> 'thumbs',
															'includesubs' 	=> 'no',
															) );

		// Title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) );

		// Album
		$body = wppa_album_select_a( array( 'selected' 		=> $instance['album'],
											'addall' 		=> true,
											'addmultiple' 	=> true,
											'addnumbers' 	=> true,
											'path' 			=> wppa_switch( 'hier_albsel' ),
											) );
		echo
		wppa_widget_selection_frame( $this, 'album', $body, __( 'Album', 'wp-photo-album-plus' ) );

		// Album enumeration
		echo
		wppa_widget_input( 	$this,
							'albumenum',
							$instance['albumenum'],
							__( 'Albums', 'wp-photo-album-plus' ),
							__( 'Select --- multiple see below --- in the Album selection box. Then enter album numbers seperated by commas', 'wp-photo-album-plus' )
							);

		// Include subalbums
		echo
		wppa_widget_checkbox( $this, 'includesubs', $instance['includesubs'], __( 'Include subalbums', 'wp-photo-album-plus' ) );

		// Display type
		$options = array( 	__( 'thumbnail images', 'wp-photo-album-plus' ),
							__( 'photo names', 'wp-photo-album-plus' ),
							);
		$values  = array(	'thumbs',
							'names',
							);
		echo
		wppa_widget_selection( $this, 'display', $instance['display'], __( 'Display type', 'wp-photo-album-plus' ), $options, $values );

		// Time since
		echo
		wppa_widget_checkbox( $this, 'timesince', $instance['timesince'], __( 'Show time since', 'wp-photo-album-plus' ) );

		echo
		'<p>' .
			__( 'You can set the sizes in this widget in the <b>Photo Albums -> Settings</b> admin page.', 'wp-photo-album-plus' ) .
			' ' . __( 'Table I-F7 and 8', 'wp-photo-album-plus' ) .
		'</p>';

    }

} // class LasTenWidget

// register LasTenWidget widget
add_action('widgets_init', 'wppa_register_LasTenWidget' );

function wppa_register_LasTenWidget() {
	register_widget("LasTenWidget");
}
