<?php
/* wppa-thumbnail-widget.php
* Package: wp-photo-album-plus
*
* display thumbnail photos
* Version 6.7.01
*/

class ThumbnailWidget extends WP_Widget {

    /** constructor */
    function __construct() {
		$widget_ops = array( 'classname' => 'wppa_thumbnail_widget', 'description' => __( 'Display thumbnails of the photos in an album', 'wp-photo-album-plus' ) );
		parent::__construct( 'wppa_thumbnail_widget', __( 'WPPA+ Thumbnail Photos', 'wp-photo-album-plus' ), $widget_ops );
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

		wppa( 'in_widget', 'tn' );
		wppa_bump_mocc();

        extract( $args );

		$instance 		= wp_parse_args( (array) $instance, array(
														'title' 	=> __( 'Thumbnail Photos', 'wp-photo-album-plus' ),
														'album' 	=> 'no',
														'link' 		=> '',
														'linktitle' => '',
														'name' 		=> 'no',
														'display' 	=> 'thumbs',
														'sortby' 	=> wppa_get_photo_order('0'),
														'limit' 	=> wppa_opt( 'thumbnail_widget_count' )
														) );
		$widget_title 	= apply_filters( 'widget_title', $instance['title'] );
//		$widget_title 	= apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		$widget_link	= $instance['link'];
		$page 			= in_array( wppa_opt( 'thumbnail_widget_linktype' ), wppa( 'links_no_page' ) ) ? '' : wppa_get_the_landing_page('thumbnail_widget_linkpage', __('Thumbnail photos', 'wp-photo-album-plus'));
		$max  			= $instance['limit'];
		$sortby 		= $instance['sortby'];
		$album 			= $instance['album'];
		$name 			= wppa_checked( $instance['name'] ) ? 'yes' : 'no';
		$display 		= $instance['display'];
		$linktitle 		= $instance['linktitle'];

		$generic = ( $album == '-2' );
		if ( $generic ) {
			$album = '0';
			$max += '1000';
		}
		$separate = ( $album == '-1' );
		if ( $separate ) {
			$album = '0';
			$max += '1000';
		}

		if ( $album ) {
			$thumbs = $wpdb->get_results($wpdb->prepare( "SELECT * FROM `".WPPA_PHOTOS."` WHERE `status` <> 'pending' AND `status` <> 'scheduled' AND `album` = %s ".$sortby." LIMIT %d", $album, $max ), 'ARRAY_A' );
		}
		else {
			$thumbs = $wpdb->get_results($wpdb->prepare( "SELECT * FROM `".WPPA_PHOTOS."` WHERE `status` <> 'pending' AND `status` <> 'scheduled'".$sortby." LIMIT %d", $max ), 'ARRAY_A' );
		}

		global $widget_content;
		$widget_content = "\n".'<!-- WPPA+ thumbnail Widget start -->';
		$maxw = wppa_opt( 'thumbnail_widget_size' );
		$maxh = $maxw;
		$lineheight = wppa_opt( 'fontsize_widget_thumb' ) * 1.5;
		$maxh += $lineheight;
		if ( $name == 'yes' ) $maxh += $lineheight;

		$count = '0';
		if ( $thumbs ) foreach ( $thumbs as $image ) {

			$thumb = $image;

			if ( $generic && wppa_is_separate( $thumb['album'] ) ) continue;
			if ( $separate && ! wppa_is_separate( $thumb['album'] ) ) continue;

			// Make the HTML for current picture
			if ( $display == 'thumbs' ) {
				$widget_content .= "\n".'<div class="wppa-widget" style="width:'.$maxw.'px; height:'.$maxh.'px; margin:4px; display:inline; text-align:center; float:left;">';
			}
			else {
				$widget_content .= "\n".'<div class="wppa-widget" >';
			}
			if ($image) {
				$link       = wppa_get_imglnk_a('tnwidget', $image['id']);
				$file       = wppa_get_thumb_path($image['id']);
				$imgstyle_a = wppa_get_imgstyle_a( $image['id'], $file, $maxw, 'center', 'twthumb');
				$imgurl 	= wppa_get_thumb_url( $image['id'], true, '', $imgstyle_a['width'], $imgstyle_a['height'] );
				$imgevents 	= wppa_get_imgevents('thumb', $image['id'], true);
				$title 		= $link ? esc_attr(stripslashes($link['title'])) : '';

				wppa_do_the_widget_thumb('thumbnail', $image, $album, $display, $link, $title, $imgurl, $imgstyle_a, $imgevents);

				$widget_content .= "\n\t".'<div style="font-size:'.wppa_opt( 'fontsize_widget_thumb' ).'px; line-height:'.$lineheight.'px;">';
				if ( $name == 'yes' && $display == 'thumbs' ) {
					$widget_content .= "\n\t".'<div>'.__(stripslashes($image['name']), 'wp-photo-album-plus').'</div>';
				}
				$widget_content .= "\n\t".'</div>';
			}
			else {	// No image
				$widget_content .= __( 'Photo not found', 'wp-photo-album-plus' );
			}
			$widget_content .= "\n".'</div>';
			$count++;
			if ( $count == $instance['limit'] ) break;

		}
		else $widget_content .= __( 'There are no photos (yet)', 'wp-photo-album-plus' );

		$widget_content .= '<div style="clear:both"></div>';
		$widget_content .= "\n".'<!-- WPPA+ thumbnail Widget end -->';

		echo "\n" . $before_widget;
		if ( !empty( $widget_title ) ) {

			echo $before_title;

			if (!empty($widget_link)) {
				echo "\n".'<a href="'.$widget_link.'" title="'.$linktitle.'" >'.$widget_title.'</a>';
			}
			else {
				echo $widget_title;
			}

			echo $after_title;
		}

		echo $widget_content . $after_widget;

		wppa( 'in_widget', false );
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] 		= strip_tags($new_instance['title']);
		$instance['link'] 		= strip_tags($new_instance['link']);
		$instance['album'] 		= $new_instance['album'];
		$instance['name'] 		= $new_instance['name'];
		$instance['display'] 	= $new_instance['display'];
		$instance['linktitle']	= $new_instance['linktitle'];
		$instance['sortby'] 	= $new_instance['sortby'];
		$instance['limit']		= strval(intval($new_instance['limit']));

        return $instance;
    }

    /** @see WP_Widget::form */
    function form( $instance ) {

		//Defaults
		$instance = wp_parse_args( (array) $instance, array(
															'title'		=> __( 'Thumbnail Photos', 'wp-photo-album-plus' ),
															'link'	 	=> '',
															'linktitle' => '',
															'album' 	=> '0',
															'name' 		=> 'no',
															'display' 	=> 'thumbs',
															'sortby' 	=> wppa_get_photo_order('0'),
															'limit' 	=> wppa_opt( 'thumbnail_widget_count' )
															) );

		// Title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) ) .

		// Link from the widget title
		wppa_widget_input( $this, 'link', $instance['link'], __( 'Link from the title', 'wp-photo-album-plus' ) ) .

		// Tooltip on the link from the title
		wppa_widget_input( $this, 'linktitle', $instance['linktitle'], __( 'Link Title ( tooltip )', 'wp-photo-album-plus' ) );

		// Album
		$body = wppa_album_select_a( array( 'selected' => $instance['album'], 'addseparate' => true, 'addall' => true, 'path' => wppa_switch( 'hier_albsel' ) ) );
		echo
		wppa_widget_selection_frame( $this, 'album', $body, __( 'Album', 'wp-photo-album-plus' ) );

		// Sort by
		$options = array( 	__( '--- none ---', 'wp-photo-album-plus' ),
							__( 'Order #', 'wp-photo-album-plus' ),
							__( 'Name', 'wp-photo-album-plus' ),
							__( 'Random', 'wp-photo-album-plus' ),
							__( 'Rating mean value desc', 'wp-photo-album-plus' ),
							__( 'Number of votes desc', 'wp-photo-album-plus' ),
							__( 'Timestamp desc', 'wp-photo-album-plus' ),
							);
		$values  = array(	'',
							'ORDER BY `p_order`',
							'ORDER BY `name`',
							'ORDER BY RAND()',
							'ORDER BY `mean_rating` DESC',
							'ORDER BY `rating_count` DESC',
							'ORDER BY `timestamp` DESC',
							);
		echo
		wppa_widget_selection( $this, 'sortby', $instance['sortby'], __( 'Sort by', 'wp-photo-album-plus' ), $options, $values );

		// Max number
		echo
		wppa_widget_number( $this, 'limit', $instance['limit'], __( 'Max number', 'wp-photo-album-plus' ), '1', '100' );

		// Display type
		$options = array( 	__( 'thumbnail images', 'wp-photo-album-plus' ),
							__( 'photo names', 'wp-photo-album-plus' ),
							);
		$values  = array( 	'thumbs',
							'names',
							);
		echo
		wppa_widget_selection( $this, 'display', $instance['display'], __( 'Display', 'wp-photo-album-plus' ), $options, $values );

		// Names under thumbs
		echo
		wppa_widget_checkbox( $this, 'name', $instance['name'], __( 'Show photo names under thumbnails', 'wp-photo-album-plus' ) );

		echo
		'<p>' .
			__( 'You can set the sizes in this widget in the <b>Photo Albums -> Settings</b> admin page.', 'wp-photo-album-plus' ) .
			' ' . __( 'Table I-F5 and 6', 'wp-photo-album-plus' ) .
		'</p>';

    }

} // class thumbnailWidget

// register thumbnailWidget widget
add_action('widgets_init', 'wppa_register_ThumbnailWidget' );

function wppa_register_ThumbnailWidget() {
	register_widget("ThumbnailWidget");
}
