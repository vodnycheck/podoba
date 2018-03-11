<?php
/* wppa-potd-widget.php
* Package: wp-photo-album-plus
*
* display the widget
* Version 6.7.01
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

class PhotoOfTheDay extends WP_Widget {

    /** constructor */
    function __construct() {
		$widget_ops = array( 'classname' => 'wppa_widget', 'description' => __( 'Display Photo Of The Day', 'wp-photo-album-plus' ) );	//
		parent::__construct( 'wppa_widget', __( 'WPPA+ Photo Of The Day', 'wp-photo-album-plus' ), $widget_ops );															//
    }

	/** @see WP_Widget::widget */
    function widget($args, $instance) {
		global $wpdb;

		wppa( 'in_widget', 'potd' );
		wppa_bump_mocc();

		require_once(dirname(__FILE__) . '/wppa-links.php');
		require_once(dirname(__FILE__) . '/wppa-styles.php');
		require_once(dirname(__FILE__) . '/wppa-functions.php');
		require_once(dirname(__FILE__) . '/wppa-thumbnails.php');
		require_once(dirname(__FILE__) . '/wppa-boxes-html.php');
		require_once(dirname(__FILE__) . '/wppa-slideshow.php');
		wppa_initialize_runtime();

        extract( $args );

		// Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => wppa_opt( 'potd_title' ) ) );

		$widget_title = apply_filters('widget_title', $instance['title']);

		// get the photo  ($image)
		$image = wppa_get_potd();

		// Make the HTML for current picture
		$widget_content = "\n".'<!-- WPPA+ Photo of the day Widget start -->';

		$ali = wppa_opt( 'potd_align' );
		if ( $ali != 'none' ) {
			$align = 'text-align:'.$ali.';';
		}
		else $align = '';

		$widget_content .= "\n".'<div class="wppa-widget-photo" style="' . $align . ' padding-top:2px;position:relative;" >';

		if ( $image ) {

			$id 		= $image['id'];
			$w 			= wppa_opt( 'potd_widget_width' );
			$ratio 		= wppa_get_photoy( $id ) / wppa_get_photox( $id );
			$h 			= round( $w * $ratio );
			$usethumb	= wppa_use_thumb_file( $id, wppa_opt( 'potd_widget_width' ), '0' );
			$imgurl 	= $usethumb ? wppa_get_thumb_url( $id, true, '', $w, $h ) : wppa_get_photo_url( $id, true, '', $w, $h );
			$name 		= wppa_get_photo_name( $id );
			$page 		= ( in_array( wppa_opt( 'potd_linktype' ), wppa( 'links_no_page' ) ) && ! wppa_switch( 'potd_counter' ) ) ? '' : wppa_get_the_landing_page( 'potd_linkpage', __('Photo of the day', 'wp-photo-album-plus') );
			$link 		= wppa_get_imglnk_a( 'potdwidget' , $id );
			$is_video 	= wppa_is_video( $id );
			$has_audio 	= wppa_has_audio( $id );

			if ( $link['is_lightbox'] ) {
				$lightbox = ( $is_video ? ' data-videohtml="' . esc_attr( wppa_get_video_body( $id ) ) . '"' .
							' data-videonatwidth="'.wppa_get_videox( $id ).'"' .
							' data-videonatheight="'.wppa_get_videoy( $id ).'"' : '' ) .
							( $has_audio ? ' data-audiohtml="' . esc_attr( wppa_get_audio_body( $id ) ) . '"' : '' ) .
							' ' . wppa( 'rel' ) . '="' . wppa_opt( 'lightbox_name' ) . '"' .
							' data-alt="' . esc_attr( wppa_get_imgalt( $id, true ) ) . '"';
			}
			else {
				$lightbox = '';
			}

			if ( $link ) {
				if ( $link['is_lightbox'] ) {
					$cursor = ' cursor:url('.wppa_get_imgdir().wppa_opt( 'magnifier').'),pointer;';
					$title  = wppa_zoom_in( $id );
					$ltitle = wppa_get_lbtitle('potd', $id);
				}
				else {
					$cursor = ' cursor:pointer;';
					$title  = $link['title'];
					$ltitle = $title;
				}
			}
			else {
				$cursor = ' cursor:default;';
				$title = esc_attr(stripslashes(__($image['name'], 'wp-photo-album-plus')));
			}

			// The medal if on top
			$widget_content .= wppa_get_medal_html_a( array( 'id' => $id, 'size' => 'M', 'where' => 'top' ) );

			// The link, if any
			if ($link) $widget_content .= "\n\t".'<a href = "'.$link['url'].'" target="'.$link['target'].'" '.$lightbox.' ' . wppa( 'lbtitle' ) . '="'.$ltitle.'">';

				// The image
				if ( wppa_is_video( $id ) ) {
					$widget_content .= "\n\t\t".wppa_get_video_html( array ( 	'id' 		=> $id,
																				'width' 	=> wppa_opt( 'potd_widget_width' ),
																				'title' 	=> $title,
																				'controls' 	=> ( wppa_opt( 'potd_linktype' ) == 'none' ),
																				'cursor' 	=> $cursor
																	));
				}
				else {
					$widget_content .= 	'<img' .
											' src="'.$imgurl.'"' .
											' style="width: '.wppa_opt( 'potd_widget_width' ).'px;'.$cursor.'"' .
											' ' . wppa_get_imgalt( $id ) .
											( $title ? 'title="' . $title . '"' : '' ) .
											' />';
				}

			// Close the link
			if ( $link ) $widget_content .= '</a>';

			// The medal if at the bottom
			$widget_content .= wppa_get_medal_html_a( array( 'id' => $id, 'size' => 'M', 'where' => 'bot' ) );

			// The counter
			if ( wppa_switch( 'potd_counter' ) ) { 	// If we want this
				$alb = wppa_get_photo_item( $id, 'album' );
				$c = $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPPA_PHOTOS . "` WHERE `album` = " . $alb ) - 1;
				if ( $c > 0 ) {
					if ( wppa_opt( 'potd_counter_link' ) == 'thumbs' ) {
						$lnk = wppa_get_album_url( $alb, $page, 'thumbs', '1' );
					}
					elseif ( wppa_opt( 'potd_counter_link' ) == 'slide' ) {
						$lnk = wppa_get_slideshow_url( $alb, $page, $id, '1' );
					}
					elseif ( wppa_opt( 'potd_counter_link' ) == 'single' ) {
						$lnk = wppa_encrypt_url( get_permalink( $page ) . '?occur=1&photo=' . $id );
					//	wppa_get_image_page_url_by_id( $id, true, false, $page );
					}
					else {
						wppa_log( 'Err', 'Unimplemented counter link type in wppa-potd-widget: ' . wppa_opt( 'potd_counter_link' ) );
					}

					$widget_content .= 	'<a href="' . $lnk . '" >' .
											'<div style="font-size:12px;position:absolute;right:4px;bottom:4px;" >+' . $c . '</div>' .
										'</a>';
				}
			}

			// Audio
			if ( wppa_has_audio( $id ) ) {
				$widget_content .= wppa_get_audio_html( array ( 	'id' 		=> $id,
																	'width' 	=> wppa_opt( 'potd_widget_width' ),
																	'controls' 	=> true
													));
			}

		}
		else {	// No image
			$widget_content .= __( 'Photo not found', 'wp-photo-album-plus' );
		}
		$widget_content .= "\n".'</div>';

		// Add subtitle, if any
		if ( $image ) {
			switch ( wppa_opt( 'potd_subtitle' ) ) {
				case 'none':
					break;
				case 'name':
					$widget_content .= '<div class="wppa-widget-text wppa-potd-text" style="'.$align.'">' . wppa_get_photo_name( $id ) . '</div>';
					break;
				case 'desc':
					$widget_content .= "\n".'<div class="wppa-widget-text wppa-potd-text" style="'.$align.'">' . wppa_get_photo_desc( $id ) . '</div>';
					break;
				case 'owner':
					$owner = $image['owner'];
					$user = wppa_get_user_by('login', $owner);
					$owner = $user->display_name;
					$widget_content .= "\n".'<div class="wppa-widget-text wppa-potd-text" style="'.$align.'">'.__('By:', 'wp-photo-album-plus').' ' . $owner . '</div>';
					break;
				default:
					wppa_log( 'Err', 'Unimplemented potd_subtitle found in wppa-potd-widget: ' . wppa_opt( 'potd_subtitle' ) );
			}
		}

		$widget_content .= '<div style="clear:both;" ></div>';

		$widget_content .= "\n".'<!-- WPPA+ Photo of the day Widget end -->';

		echo "\n" . $before_widget;
		if ( !empty( $widget_title ) ) { echo $before_title . $widget_title . $after_title; }
		echo $widget_content . $after_widget;

		wppa( 'in_widget', false );
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
		$instance = wp_parse_args( (array) $instance, array( 'title' => wppa_opt( 'potd_title' ) ) );

		// Title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) );

		// Explanation
		echo
		'<p>' .
			__( 'You can set the content and the sizes in this widget in the <b>Photo Albums -> Photo of the day</b> admin page.', 'wp-photo-album-plus' ) .
		'</p>';
    }

} // class PhotoOfTheDay

require_once 'wppa-widget-functions.php';

// register PhotoOfTheDay widget
add_action( 'widgets_init', 'wppa_register_PhotoOfTheDay' );

function wppa_register_PhotoOfTheDay() {
	register_widget( 'PhotoOfTheDay' );
}
