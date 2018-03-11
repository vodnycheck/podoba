<?php
/* wppa-picture.php
* Package: wp-photo-album-plus
*
* Make the picture html
* Version 6.7.12
*
*/


// This function creates the html for the picture. May be photo, video, audio or photo with audio.
// The size will always be set to 100% width, so the calling wrapper div should take care of sizing.
// This function can be used for both resposive and static displays.
//
// Minimum requirements for input args:
//
// - id, The photo id ( numeric photo db table id )
// - type, Any one of the supported display types: sphoto, mphoto, xphoto, ( to be extended )
//
// Optional args:
//
// - class: Any css class specification.
//
// Returns: The html, or false on error.
// In case of error a red debug message will be printed directly to the output stream.
//
// Additional action: viewcount is bumped by this function if the displayed image is not a thumbnail sized one.
//
function wppa_get_picture_html( $args ) {

	// Init
	$defaults 	= array( 	'id' 		=> '0',
							'type' 		=> '',
							'class' 	=> '',
						);
	$args 		= wp_parse_args( $args, $defaults );

	$id 		= strval( intval ( $args['id'] ) );
	$type 		= $args['type'];
	$class 		= $args['class'];

	// Check existance of required args
	foreach( array( 'id', 'type' ) as $item ) {
		if ( ! $args[$item] ) {
			wppa_dbg_msg( 'Missing ' . $item . ' in call to wppa_get_picture_html()', 'red' );
			return false;
		}
	}

	// Check validity of args
	if ( ! wppa_photo_exists( $id ) ) {
		wppa_dbg_msg( 'Photo ' . $id . ' does not exist in call to wppa_get_picture_html(). Type = ' . $type, 'red', 'force' );
		return false;
	}
	$types = array(	'sphoto', 		// Single image with optional border like slideshow border
					'mphoto',		// Media type like single image. Caption should be provided in wrappping div
					'xphoto',		// Like xphoto with extended features
					'cover', 		// Album cover image
					'thumb',		// Normal tumbnail
					'ttthumb',		// Topten
					'comthumb',		// Comment widget
					'fthumb',		// Filmthumb
					'twthumb',		// Thumbnail widget
					'ltthumb',		// Lasten widget
					'albthumb',		// Album widget
					);
	if ( ! in_array( $type, $types ) ) {
		wppa_dbg_msg( 'Unimplemented type ' . $type . ' in call to wppa_get_picture_html()', 'red', 'force' );
		return false;
	}

	// Get other data
	$link 		= wppa_get_imglnk_a( $type, $id );
	$isthumb 	= strpos( $type, 'thumb' ) !== false;
	$file 		= $isthumb ? wppa_get_thumb_path( $id ) : wppa_get_photo_path( $id );
	$href 		= $isthumb ? wppa_get_thumb_url( $id ) : wppa_get_photo_url( $id );
	$autocol 	= wppa( 'auto_colwidth' ) || ( wppa( 'fullsize' ) > 0 && wppa( 'fullsize' ) <= 1.0 );
	$title 		= $link ? esc_attr( $link['title'] ) : esc_attr( stripslashes( wppa_get_photo_name( $id ) ) );
	$alt 		= wppa_get_imgalt( $id );

	// Find image style
	switch ( $type ) {
		case 'sphoto':
			$style = 'width:100%;margin:0;';
			if ( ! wppa_in_widget() ) {
				switch ( wppa_opt( 'fullimage_border_width' ) ) {
					case '':
						$style .= 	'padding:0;' .
									'border:none;';
						break;
					case '0':
						$style .= 	'padding:0;' .
									'border:1px solid ' . wppa_opt( 'bcolor_fullimg' ) . ';' .
									'box-sizing:border-box;';
						break;
					default:
						$style .= 	'padding:' . ( wppa_opt( 'fullimage_border_width' ) - '1' ) . 'px;' .
									'border:1px solid ' . wppa_opt( 'bcolor_fullimg' ) . ';' .
									'box-sizing:border-box;' .
									'background-color:' . wppa_opt( 'bgcolor_fullimg' ) . ';';

						// If we do round corners...
						if ( wppa_opt( 'bradius' ) > '0' ) {

							// then also here
							$style .= 'border-radius:' . wppa_opt( 'fullimage_border_width' ) . 'px;';
						}
				}
			}
			break;
		case 'mphoto':
		case 'xphoto':
			$style = 'width:100%;margin:0;padding:0;border:none;';
			break;
		default:
			wppa_dbg_msg( 'Style for type ' . $type . ' is not implemented yet in wppa_get_picture_html()', 'red', 'force' );
			return false;

	}
	if ( $link['is_lightbox'] ) {
		$style .= 'cursor:url( ' . wppa_get_imgdir() . wppa_opt( 'magnifier' ) . ' ),pointer;';
		$title = wppa_zoom_in( $id );
	}

	// Create the html. To prevent mis-alignment of the audio control bar
	// on theme Twenty Seventeen, we wrap it in a div with zero fontsize.
	$result = '<div style="font-size:0;" >';

	// The link
	if ( $link ) {

		// Link is lightbox
		if ( $link['is_lightbox'] ) {
			$lbtitle 	= wppa_get_lbtitle( $type, $id );
			$videobody 	= esc_attr( wppa_get_video_body( $id ) );
			$audiobody 	= esc_attr( wppa_get_audio_body( $id ) );
			$videox 	= wppa_get_videox( $id );
			$videoy 	= wppa_get_videoy( $id );
			$result .=
			'<a' .
				' href="' . $link['url'] . '"' .
				( $lbtitle ? ' ' . wppa( 'lbtitle' ) . '="'.$lbtitle.'"' : '' ) .
				( $videobody ? ' data-videohtml="' . $videobody . '"' : '' ) .
				( $audiobody ? ' data-audiohtml="' . $audiobody . '"' : '' ) .
				( $videox ? ' data-videonatwidth="' . $videox . '"' : '' ) .
				( $videoy ? ' data-videonatheight="' . $videoy . '"' : '' ) .
				' ' . wppa( 'rel' ) . '="'.wppa_opt( 'lightbox_name' ).'"' .
				( $link['target'] ? ' target="' . $link['target'] . '"' : '' ) .
				' class="thumb-img"' .
				' id="a-' . $id . '-' . wppa( 'mocc' ) . '"' .
				' data-alt="' . esc_attr( wppa_get_imgalt( $id, true ) ) . '"' .
				' >';
		}

		// Link is NOT lightbox
		else {
			$result .=
			'<a' .
				( wppa_is_mobile() ?
					' ontouchstart="wppaStartTime();" ontouchend="wppaTapLink(\'' . $id . '\',\'' . $link['url'] . '\');" ' :
					' onclick="_bumpClickCount( \'' . $id . '\' );window.open(\'' . $link['url'] . '\', \'' . $link['target'] . '\' )"'
				) .
				' title="' . $link['title'] . '"' .
				' class="thumb-img"' .
				' id="a-' . $id . '-' . wppa( 'mocc' ) . '"' .
				' style="cursor:pointer;"' .
				' >';
		}
	}

	// The image
	// Video?
	if ( wppa_is_video( $id ) ) {
		$result .=
		wppa_get_video_html( array( 'id' 		=> $id,
									'controls' 	=> ! $link,
									'style' 	=> $style,
									'class' 	=> $class,
								)
							);

	}

	// No video, just a photo
	else {
		$result .=
		'<img' .
			' id="ph-' . $id . '-' . wppa( 'mocc' ) . '"' .
			' src="' . $href . '"' .
			' ' . wppa_get_imgalt( $id ) .
			( $class ? ' class="' . $class . '" ' : '' ) .
			( $title ? ' title="' . $title . '" ' : '' ) .
			' style="' . $style . '"' .
		' />';
	}

	// Close the link
	if ( $link ) {
		$result .= '</a>';
	}

	// Add audio?			sphoto
	if ( wppa_has_audio( $id ) ) {

		$result .= '<div style="position:relative;z-index:11;" >';

		// Find style for audio controls
		switch ( $type ) {
			case 'sphoto':
				$pad = ( wppa_opt( 'fullimage_border_width' ) === '' ) ? 0 : wppa_opt( 'fullimage_border_width' );
				$bot = ( wppa_opt( 'fullimage_border_width' ) === '' ) ? 0 : wppa_opt( 'fullimage_border_width' );

				$style = 	'margin:0;' .
							'padding:0 ' . $pad . 'px;' .
							'bottom:' . $bot .'px;';

				$class = 	'size-medium wppa-sphoto wppa-sphoto-' . wppa( 'mocc' );
				break;
			case 'mphoto':
			case 'xphoto':
				$style = 	'margin:0;' .
							'padding:0;' .
							'bottom:0;';
				$class = 	'size-medium wppa-' . $type . ' wppa-' . $type . '-' . wppa( 'mocc' );
				break;
			default:
				$style = 	'margin:0;' .
							'padding:0;';

				$class = 	'';
		}

		// Get the html for audio
		$result .= wppa_get_audio_html( array(	'id' 		=> 	$id,
												'cursor' 	=> 	'cursor:pointer;',
												'style' 	=> 	$style .
																'position:absolute;' .
																'box-sizing:border-box;' .
																'width:100%;' .
																'border:none;' .
																'height:' . wppa_get_audio_control_height() . 'px;' .
																'border-radius:0;',
												'class' 	=> 	$class,
											)
									);
		$result .= '</div>';
	}

	$result .= '</div>';

	// Update statistics
	if ( ! wppa_in_widget() ) {
		wppa_bump_viewcount( 'photo', $id );
	}

	// Done !
	return $result;
}

/*
// Local subroutine to get max width of image
function wppa_get_xmax( $id, $usethumb ) {

	if ( $usethumb ) {
		$xmax = wppa_get_photo_item( $id, 'thumbx' );
	}
	elseif ( wppa_is_video( $id ) ) {
		$xmax = wppa_get_photo_item( $id, 'videox' );
		if ( ! $xmax ) {
			$xmax = wppa_opt( 'video_width' );
		}
		if ( wppa_switch( 'enlarge' ) ) {
			$xmax = wppa_opt( 'colwidth' );
		}
	}
	else {
		$xmax = wppa_get_photo_item( $id, 'photox' );
		if ( wppa_switch( 'enlarge' ) ) {
			$xmax = wppa_opt( 'colwidth' );
		}
	}
	if ( $xmax == 'auto' ) {
		$xmax = wppa_opt( 'initial_colwidth' );
	}

	return $xmax;
}

*/
// Subroutine to get initial width of image
/*
function wppa_get_xinit( $id, $isthumb ) {

	if ( $isthumb ) {
		$result = '';
	}
	else {
		$f = wppa( 'fullsize' );
		if ( ! $f ) {
			$result = wppa_opt( 'initial_colwidth' );
		}
		if ( $f > 0 && $f <= 1 ) {
			$result = wppa_opt( 'initial_colwidth' ) * $f;
		}
		else {
			$result = $f;
		}
	}

	return $result;
}
*/