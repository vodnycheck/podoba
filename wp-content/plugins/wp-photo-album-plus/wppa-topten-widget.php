<?php
/* wppa-topten-widget.php
* Package: wp-photo-album-plus
*
* display the top rated photos
* Version 6.7.01
*/

class TopTenWidget extends WP_Widget {

    /** constructor */
    function __construct() {
		$widget_ops = array( 'classname' => 'wppa_topten_widget', 'description' => __( 'Display top rated photos', 'wp-photo-album-plus' ) );
		parent::__construct( 'wppa_topten_widget', __( 'WPPA+ Top Ten Photos', 'wp-photo-album-plus' ), $widget_ops );
    }

	/** @see WP_Widget::widget */
    function widget($args, $instance) {
		global $wpdb;

		require_once(dirname(__FILE__) . '/wppa-links.php');
		require_once(dirname(__FILE__) . '/wppa-styles.php');
		require_once(dirname(__FILE__) . '/wppa-functions.php');
		require_once(dirname(__FILE__) . '/wppa-thumbnails.php');
		require_once(dirname(__FILE__) . '/wppa-boxes-html.php');
		require_once(dirname(__FILE__) . '/wppa-slideshow.php');
		wppa_initialize_runtime();

        wppa( 'in_widget', 'topten' );
		wppa_bump_mocc();

		extract( $args );

		$instance 		= wp_parse_args( (array) $instance, array(
														'title' => __( 'Top Ten Photos', 'wp-photo-album-plus' ),
														'sortby' => 'mean_rating',
														'title' => '',
														'album' => '',
														'display' => 'thumbs',
														'meanrat' => 'yes',
														'ratcount' => 'yes',
														'viewcount' => 'yes',
														'includesubs' => 'yes',
														'medalsonly' => 'no',
														'showowner' => 'no',
														'showalbum' => 'no'
														) );
 		$widget_title 	= apply_filters('widget_title', $instance['title'] );
		$page 			= in_array( wppa_opt( 'topten_widget_linktype' ), wppa( 'links_no_page' ) ) ? '' : wppa_get_the_landing_page('topten_widget_linkpage', __('Top Ten Photos', 'wp-photo-album-plus'));
		$albumlinkpage 	= wppa_get_the_landing_page('topten_widget_album_linkpage', __('Top Ten Photo album', 'wp-photo-album-plus'));
		$max  			= wppa_opt( 'topten_count' );
		$album 			= $instance['album'];
		switch ( $instance['sortby'] ) {
			case 'mean_rating':
				$sortby = '`mean_rating` DESC, `rating_count` DESC, `views` DESC';
				break;
			case 'rating_count':
				$sortby = '`rating_count` DESC, `mean_rating` DESC, `views` DESC';
				break;
			case 'views':
				$sortby = '`views` DESC, `mean_rating` DESC, `rating_count` DESC';
				break;
		}
		$display 		= $instance['display'];
		$meanrat		= wppa_checked( $instance['meanrat'] ) ? 'yes' : false;
		$ratcount 		= wppa_checked( $instance['ratcount'] ) ? 'yes' : false;
		$viewcount 		= wppa_checked( $instance['viewcount'] ) ? 'yes' : false;
		$includesubs 	= wppa_checked( $instance['includesubs'] ) ? 'yes' : false;
		$albenum 		= '';
		$medalsonly 	= wppa_checked( $instance['medalsonly'] ) ? 'yes' : false;
		$showowner 		= wppa_checked( $instance['showowner'] ) ? 'yes' : false;
		$showalbum 		= wppa_checked( $instance['showalbum'] ) ? 'yes' : false;

		wppa( 'medals_only', $medalsonly );

		$likes = wppa_opt( 'rating_display_type' ) == 'likes';

		// When likes only, mean rating has no meaning, chan to (rating)(like)count
		if ( $likes && $instance['sortby'] == 'mean_rating' ) {
			$instance['sortby'] = 'rating_count';
		}

		// Album specified?
		if ( $album ) {

			// All albums ?
			if ( $album == '-2' ) {
				$album = '0';
			}

			// Albums of owner is current logged in user or public?
			if ( $album == '-3' ) {
				$temp = $wpdb->get_results( "SELECT `id` FROM `".WPPA_ALBUMS."` WHERE `owner` = '--- public ---' OR `owner` = '" . wppa_get_user() . "' ORDER BY `id`", ARRAY_A );
				$album = '';
				if ( $temp ) {
					foreach( $temp as $t ) {
						$album .= '.' . $t['id'];
					}
					$album = ltrim( $album, '.' );
				}
			}

			// Including subalbums?
			if ( $includesubs ) {
				$albenum = wppa_alb_to_enum_children( $album );
				$albenum = wppa_expand_enum( $albenum );
				$album = str_replace( '.', ',', $albenum );
			}

			// Doit
			if ( $medalsonly ) {
				$thumbs = $wpdb->get_results( 	"SELECT * FROM `".WPPA_PHOTOS."` " .
												"WHERE `album` IN (".$album.") " .
												"AND `status` IN ( 'gold', 'silver', 'bronze' ) " .
												"ORDER BY " . $sortby . " " .
												"LIMIT " . $max, ARRAY_A );
			}
			else {
				$thumbs = $wpdb->get_results( 	"SELECT * FROM `".WPPA_PHOTOS."` " .
												"WHERE `album` IN (".$album.") " .
												"ORDER BY " . $sortby . " " .
												"LIMIT " . $max, ARRAY_A );
			}

		}

		// No album specified
		else {
			if ( $medalsonly ) {
				$thumbs = $wpdb->get_results( 	"SELECT * FROM `".WPPA_PHOTOS."` " .
												"WHERE `status` IN ( 'gold', 'silver', 'bronze' ) " .
												"AND `album` > '0' " .
												"ORDER BY " . $sortby . " " .
												"LIMIT " . $max, ARRAY_A );
			}
			else {
				$thumbs = $wpdb->get_results( 	"SELECT * FROM `".WPPA_PHOTOS."` " .
												"WHERE `album` > '0' " .
												"ORDER BY " . $sortby . " " .
												"LIMIT " . $max, ARRAY_A );
			}
		}

		$widget_content = "\n".'<!-- WPPA+ TopTen Widget start -->';
		$maxw = wppa_opt( 'topten_size' );
		$maxh = $maxw;
		$lineheight = wppa_opt( 'fontsize_widget_thumb' ) * 1.5;
		$maxh += $lineheight;
		if ( $meanrat ) 	$maxh += $lineheight;
		if ( $ratcount ) 	$maxh += $lineheight;
		if ( $viewcount ) 	$maxh += $lineheight;
		if ( $showowner ) 	$maxh += $lineheight;
		if ( $showalbum ) 	$maxh += $lineheight;

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
				if ($no_album) $tit = __('View the top rated photos', 'wp-photo-album-plus'); else $tit = esc_attr(__(stripslashes($image['description'])));
				$compressed_albumenum = wppa_compress_enum( $albenum );
				$link       = wppa_get_imglnk_a('topten', $image['id'], '', $tit, '', $no_album, $compressed_albumenum );
				$file       = wppa_get_thumb_path($image['id']);
				$imgstyle_a = wppa_get_imgstyle_a( $image['id'], $file, $maxw, 'center', 'ttthumb');
				$imgurl 	= wppa_get_thumb_url($image['id'], true, '', $imgstyle_a['width'], $imgstyle_a['height']);
				$imgevents 	= wppa_get_imgevents('thumb', $image['id'], true);
				$title 		= $link ? esc_attr(stripslashes($link['title'])) : '';

				$widget_content .= wppa_get_the_widget_thumb('topten', $image, $album, $display, $link, $title, $imgurl, $imgstyle_a, $imgevents);

				$widget_content .= "\n\t".'<div style="font-size:'.wppa_opt( 'fontsize_widget_thumb' ).'px; line-height:'.$lineheight.'px;">';

					// Display (owner) ?
					if ( $showowner ) {
						$widget_content .= '<div>(' . $image['owner'] . ')</div>';
					}

					// Display (album) ?
					if ( $showalbum ) {
						$href = wppa_convert_to_pretty( wppa_encrypt_url( wppa_get_album_url( $image['album'], $albumlinkpage, 'content', '1' ) ) );
						$widget_content .= '<div>(<a href="' . $href . '" >' . wppa_get_album_name( $image['album'] ) . '</a>)</div>';
					}

					// Display the rating
					if ( $likes ) {
						$lt = wppa_get_like_title_a( $image['id'] );
					}
					switch ( $instance['sortby'] ) {

						case 'mean_rating':

							if ( $meanrat == 'yes' ) {
								$widget_content .=
								'<div>' .
									wppa_get_rating_by_id( $image['id'] ) .
								'</div>';
							}
							if ( $ratcount == 'yes' ) {
								$n = wppa_get_rating_count_by_id( $image['id'] );
								$widget_content .=
								'<div>' .
									sprintf( _n( '%d vote', '%d votes', $n, 'wp-photo-album-plus' ), $n ) .
								'</div>';
							}
							if ( $viewcount == 'yes' ) {
								$n = $image['views'];
								$widget_content .=
								'<div>' .
									sprintf( _n( '%d view', '%d views', $n, 'wp-photo-album-plus' ), $n ) .
								'</div>';
							}
							break;

						case 'rating_count':
							if ( $ratcount 	== 'yes' ) {
								$n = wppa_get_rating_count_by_id( $image['id'] );
								$widget_content .=
								'<div>' .
									( $likes ? $lt['display'] : sprintf( _n( '%d vote', '%d votes', $n, 'wp-photo-album-plus' ), $n ) ) .
								'</div>';
							}
							if ( $meanrat  	== 'yes' ) {
								$widget_content .=
								'<div>' .
									wppa_get_rating_by_id( $image['id'] ) .
								'</div>';
							}
							if ( $viewcount == 'yes' ) {
								$n = $image['views'];
								$widget_content .=
								'<div>' .
									sprintf( _n( '%d view', '%d views', $n, 'wp-photo-album-plus' ), $n ) .
								'</div>';
							}
							break;

						case 'views':
							if ( $viewcount == 'yes' ) {
								$n = $image['views'];
								$widget_content .=
								'<div>' .
									sprintf( _n( '%d view', '%d views', $n, 'wp-photo-album-plus' ), $n ) .
								'</div>';
							}
							if ( $meanrat  	== 'yes' ) {
								$widget_content .=
								'<div>' .
									wppa_get_rating_by_id( $image['id'] ) .
								'</div>';
							}
							if ( $ratcount 	== 'yes' ) {
								$n = wppa_get_rating_count_by_id( $image['id'] );
								$widget_content .=
								'<div>' .
									( $likes ? $lt['display'] : sprintf( _n( '%d vote', '%d votes', $n, 'wp-photo-album-plus' ), $n ) ) .
								'</div>';
							}
							break;
					}
				$widget_content .= '</div>';
			}
			else {	// No image
				$widget_content .= __( 'Photo not found', 'wp-photo-album-plus' );
			}
			$widget_content .= "\n".'</div>';
		}
		else $widget_content .= __( 'There are no rated photos (yet)', 'wp-photo-album-plus' );

		$widget_content .= '<div style="clear:both"></div>';
		$widget_content .= "\n".'<!-- WPPA+ TopTen Widget end -->';

		echo "\n" . $before_widget;
		if ( !empty( $widget_title ) ) { echo $before_title . $widget_title . $after_title; }
		echo $widget_content . $after_widget;

		//wppa( 'in_widget', false );
		wppa_reset_occurrance();
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] 			= strip_tags( $new_instance['title'] );
		$instance['album'] 			= strval( intval( $new_instance['album'] ) );
		$instance['sortby'] 		= $new_instance['sortby'];
		$instance['display'] 		= $new_instance['display'];
		$instance['meanrat']		= $new_instance['meanrat'];
		$instance['ratcount'] 		= $new_instance['ratcount'];
		$instance['viewcount'] 		= $new_instance['viewcount'];
		$instance['includesubs'] 	= $new_instance['includesubs'];
		$instance['medalsonly'] 	= $new_instance['medalsonly'];
		$instance['showalbum'] 		= $new_instance['showalbum'];
		$instance['showowner'] 		= $new_instance['showowner'];

        return $instance;
    }

    /** @see WP_Widget::form */
    function form( $instance ) {

		// Defaults
		$instance 		= wp_parse_args( (array) $instance, array(
														'sortby' => 'mean_rating',
														'title' => __( 'Top Ten Photos', 'wp-photo-album-plus' ),
														'album' => '0',
														'display' => 'thumbs',
														'meanrat' => 'yes',
														'ratcount' => 'yes',
														'viewcount' => 'yes',
														'includesubs' => 'yes',
														'medalsonly' => 'no',
														'showowner' => 'no',
														'showalbum' => 'no'

														) );

		// Title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) );

		// Album
		$body = wppa_album_select_a( array( 'selected' => $instance['album'], 'addall' => true, 'addowner' => true, 'path' => wppa_switch( 'hier_albsel' ) ) );
		echo
		wppa_widget_selection_frame( $this, 'album', $body, __( 'Album', 'wp-photo-album-plus' ) );

		// Display type
		$options = array( 	__( 'thumbnail images', 'wp-photo-album-plus' ),
							__( 'photo names', 'wp-photo-album-plus' ),
							);
		$values  = array( 	'thumbs',
							'names',
							);
		echo
		wppa_widget_selection( $this, 'display', $instance['display'], __( 'Display', 'wp-photo-album-plus' ), $options, $values );

		// Sortby
		$options = array(	__( 'Mean value', 'wp-photo-album-plus' ),
							__( 'Number of votes', 'wp-photo-album-plus' ),
							__( 'Number of views', 'wp-photo-album-plus' ),
							);
		$values  = array(	'mean_rating',
							'rating_count',
							'views',
							);
		echo
		wppa_widget_selection( $this, 'sortby', $instance['sortby'], __( 'Sort by', 'wp-photo-album-plus' ), $options, $values );

		// Include sub albums
		echo
		wppa_widget_checkbox( $this, 'includesubs', $instance['includesubs'], __( 'Include sub albums', 'wp-photo-album-plus' ) );

		// Medals only
		echo
		wppa_widget_checkbox( $this, 'medalsonly', $instance['medalsonly'], __( 'Only with medals', 'wp-photo-album-plus' ) );

		// Subtitles
		echo
		__( 'Subtitles', 'wp-photo-album-plus' ) . ':' .
		'<div style="padding:6px;border:1px solid lightgray;margin-top:2px;" >' .

			// Owner
			wppa_widget_checkbox( $this, 'showowner', $instance['showowner'], __( 'Owner', 'wp-photo-album-plus' ) ) .

			// Album
			wppa_widget_checkbox( $this, 'showalbum', $instance['showalbum'], __( 'Album', 'wp-photo-album-plus' ) ) .

			// Mean rating
			wppa_widget_checkbox( $this, 'meanrat', $instance['meanrat'], __( 'Mean rating', 'wp-photo-album-plus' ) ) .

			// Rating count
			wppa_widget_checkbox( $this, 'ratcount', $instance['ratcount'], __( 'Rating count', 'wp-photo-album-plus' ) ) .

			// View count
			wppa_widget_checkbox( $this, 'viewcount', $instance['viewcount'], __( 'View count', 'wp-photo-album-plus' ) );

		echo
		'</div>';

		echo
		'<p>' .
			__( 'You can set the sizes in this widget in the <b>Photo Albums -> Settings</b> admin page.', 'wp-photo-album-plus' ) .
			' ' . __( 'Table I-F1 and 2', 'wp-photo-album-plus' ) .
		'</p>';

    }

} // class TopTenWidget

// register TopTenWidget widget
add_action('widgets_init', 'wppa_register_TopTenWidget' );

function wppa_register_TopTenWidget() {
	register_widget("TopTenWidget");
}
