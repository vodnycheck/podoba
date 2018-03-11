<?php
/* wppa-widget-functions.php
/* Package: wp-photo-album-plus
/*
/* Version 6.7.01
/*
*/

/*

This file contans functions to get the photo of the day selection pool and to get THE photo of the day.

Related settings are:

			'wppa_potd_album_type',
			'wppa_potd_album',
			'wppa_potd_include_subs',
			'wppa_potd_status_filter',
			'wppa_potd_inverse',
			'wppa_potd_method',
			'wppa_potd_period',
			'wppa_potd_offset',
			'wppa_potd_photo',

This fila also contains functions for the use in the widget activation screens for all widgets.
*/

// This function returns an array of photos that meet the current photo of the day selection criteria
function wppa_get_widgetphotos( $alb, $option = '' ) {
global $wpdb;

	if ( ! $alb ) return false;

	$photos = false;
	$query = '';

	// Compile status clause
	switch( wppa_opt( 'potd_status_filter' ) ) {
		case 'publish':
			$statusclause = " `status` = 'publish' ";
			break;
		case 'featured':
			$statusclause = " `status` = 'featured' ";
			break;
		case 'gold':
			$statusclause = " `status` = 'gold' ";
			break;
		case 'silver':
			$statusclause = " `status` = 'silver' ";
			break;
		case 'bronze':
			$statusclause = " `status` = 'bronze' ";
			break;
		case 'anymedal':
			$statusclause = " `status` IN ( 'gold', 'silver', 'bronze' ) ";
			break;
		default:
			$statusclause = " `status` <> 'scheduled' ";
			if ( ! is_user_logged_in() ) {
				$statusclause .= " AND `status` <> 'private' ";
			}
	}

	// If physical album(s) and include subalbums is active, make it an enumeration(with ',' as seperator)
	if ( wppa_opt( 'potd_album_type' ) == 'physical' && wppa_switch( 'potd_include_subs' ) ) {
		$alb = str_replace( ',', '.', $alb );
		$alb = wppa_expand_enum( wppa_alb_to_enum_children( $alb ) );
		$alb = str_replace( '.', ',', $alb );
	}

	// If physical albums and inverse selection is active, invert selection
	if ( wppa_opt( 'potd_album_type' ) == 'physical' && wppa_switch( 'potd_inverse' ) ) {
		$albs = explode( ',', $alb );
		$all  = $wpdb->get_col( "SELECT `id` FROM `" . WPPA_ALBUMS . "` " );
		$alb  = implode( ',', array_diff( $all, $albs ) );
	}

	/* Now find out the final query */

	/* Physical albums */

	// Is it a single album?
	if ( wppa_is_int( $alb ) ) {
		$query = $wpdb->prepare(	"SELECT `id`, `p_order` " .
									"FROM `" . WPPA_PHOTOS . "` " .
									"WHERE `album` = %s " .
									"AND " . $statusclause . $option,
									$alb );
	}

	// Is it an enumeration of album ids?
	elseif ( strchr( $alb, ',' ) ) {
		$alb = trim( $alb, ',' );

		$query = 	"SELECT `id`, `p_order` " .
					"FROM `" . WPPA_PHOTOS . "` " .
					"WHERE `album` IN ( " . $alb . " ) " .
					"AND " . $statusclause . $option;
	}

	/* Virtual albums */
	// Is it ALL?
	elseif ( $alb == 'all' ) {
		$query = 	"SELECT `id`, `p_order` " .
					"FROM `" . WPPA_PHOTOS . "` " .
					"WHERE " . $statusclause . $option;
	}

	// Is it SEP?
	elseif ( $alb == 'sep' ) {
		$albs = $wpdb->get_results( "SELECT `id`, `a_parent` FROM `" . WPPA_ALBUMS . "`", ARRAY_A );
		$query = "SELECT `id`, `p_order` FROM `" . WPPA_PHOTOS . "` WHERE ( `album` = '0' ";
		$first = true;
		foreach ( $albs as $a ) {
			if ( $a['a_parent'] == '-1' ) {
				$query .= "OR `album` = '" . $a['id'] . "' ";
			}
		}
		$query .= ") AND " . $statusclause . $option;
	}

	// Is it ALL-SEP?
	elseif ( $alb == 'all-sep' ) {
		$albs = $wpdb->get_results( "SELECT `id`, `a_parent` FROM `" . WPPA_ALBUMS . "`", ARRAY_A );
		$query = "SELECT `id`, `p_order` FROM `" . WPPA_PHOTOS . "` WHERE ( `album` IN ('0'";
		foreach ( $albs as $a ) {
			if ( $a['a_parent'] != '-1' ) {
				$query .= ",'" . $a['id'] . "'";
			}
		}
		$query .= ") ) AND " . $statusclause . $option;
	}

	// Is it Topten?
	elseif ( $alb == 'topten' ) {

		// Find the 'top' policy
		switch ( wppa_opt( 'topten_sortby' ) ) {
			case 'mean_rating':
				$sortby = '`mean_rating` DESC, `rating_count` DESC, `views` DESC';
				break;
			case 'rating_count':
				$sortby = '`rating_count` DESC, `mean_rating` DESC, `views` DESC';
				break;
			case 'views':
				$sortby = '`views` DESC, `mean_rating` DESC, `rating_count` DESC';
				break;
			default:
				wppa_error_message( 'Unimplemented sorting method' );
				$sortby = '';
				break;
		}

		// It is assumed that status is ok for top rated photos
		$query = "SELECT `id`, `p_order` FROM `" . WPPA_PHOTOS . "` ORDER BY " . $sortby . " LIMIT " . wppa_opt( 'topten_count' );
		$query .= $option;
	}

	// Do the query
	if ( $query ) {
		$photos = $wpdb->get_results( $query, ARRAY_A );
		wppa_dbg_msg( 'Potd query: '.$query );
	}
	else {
		$photos = array();
	}

	// Ready
	return $photos;
}


// get the photo of the day
function wppa_get_potd() {
global $wpdb;
static $potd;

	// Been here before? ( except for random )
	if ( wppa_opt( 'potd_method' ) != '2' ) {
		if ( $potd ) return $potd;
	}

	$id = 0;
	switch ( wppa_opt( 'potd_method' ) ) {

		// Fixed photo
		case '1':
			$id = wppa_opt( 'potd_photo' );
			break;

		// Random
		case '2':
			$album = wppa_opt( 'potd_album' );
			if ( $album == 'topten' ) {
				$images = wppa_get_widgetphotos( $album );
				if ( count( $images ) > 1 ) {	// Select a random first from the current selection
					$idx = rand( 0, count( $images ) - 1 );
					$id = $images[$idx]['id'];
				}
			}
			elseif ( $album != '' ) {
				$images = wppa_get_widgetphotos( $album, "ORDER BY RAND() LIMIT 0,1" );
				$id = $images[0]['id'];
			}
			break;

		// Last upload
		case '3':
			$album = wppa_opt( 'potd_album' );
			if ( $album == 'topten' ) {
				$images = wppa_get_widgetphotos( $album );
				if ( $images ) {

					// find last uploaded image in the $images pool
					$temp = 0;
					foreach( $images as $img ) {
						if ( $img['timestamp'] > $temp ) {
							$temp = $img['timestamp'];
							$image = $img;
						}
					}
					$id = $image['id'];
				}
			}
			elseif ( $album != '' ) {
				$images = wppa_get_widgetphotos( $album, "ORDER BY timestamp DESC LIMIT 0,1" );
				$id = $images[0]['id'];
			}
			break;

		// Change every
		case '4':
			$album = wppa_opt( 'potd_album' );
			if ( $album != '' ) {
				$per = wppa_opt( 'potd_period' );
				$photos = wppa_get_widgetphotos( $album );
				if ( $per == '0' ) {
					if ( $photos ) {
						$id = $photos[rand( 0, count( $photos )-1 )]['id'];
					}
				}
				elseif ( $per == 'day-of-week' ) {
					if ( $photos ) {
						$d = date_i18n( "w" );
						$d -= get_option( 'wppa_potd_offset', '0' );
						while ( $d < '1' ) $d += '7';
						foreach ( $photos as $img ) {
							if ( $img['p_order'] == $d ) $id = $img['id'];
						}
					}
				}
				elseif ( $per == 'day-of-month' ) {
					if ( $photos ) {
						$d = strval(intval(date_i18n( "d" )));
						$d -= get_option( 'wppa_potd_offset', '0' );
						while ( $d < '1' ) $d += '31';
						foreach ( $photos as $img ) {
							if ( $img['p_order'] == $d ) $id = $img['id'];
						}
					}
				}
				elseif ( $per == 'day-of-year' ) {
					if ( $photos ) {
						$d = strval(intval(date_i18n( "z" )));
						$d -= get_option( 'wppa_potd_offset', '0' );
						while ( $d < '0' ) $d += '366';
						foreach ( $photos as $img ) {
							if ( $img['p_order'] == $d ) $id = $img['id'];
						}
					}
				}
				else {
					$u = date_i18n( "U" ); // Seconds since 1-1-1970, local
					$u /= 3600;		//  hours since
					$u = floor( $u );
					$u /= $per;
					$u = floor( $u );

					// Find the right photo out of the photos found by wppa_get_widgetphotos(),
					// based on the Change every { any timeperiod } algorithm.
					if ( $photos ) {
						$p = count( $photos );
						$idn = fmod( $u, $p );

						// If from topten,...
						if ( $album == 'topten' ) {

							// Do a re-read of the same to order by rand, reproduceable
							// This can not be done by wppa_get_widgetphotos(),
							// it does already ORDER BY for the top selection criterium.
							// So we save the ids, and do a SELECT WHERE id IN ( array of found ids ) ORDER BY RAND( seed )
							$ids = array();
							foreach( $photos as $photo ) {
								$ids[] = $photo['id'];
							}
							$photos = $wpdb->get_results( 	"SELECT `id`, `p_order` " .
															"FROM `".WPPA_PHOTOS."` " .
															"WHERE `id` IN (" . implode( ',', $ids ) . ") " .
															"ORDER BY RAND(".$idn.")",
															ARRAY_A );
						}

						// Not from topten, use wppa_get_widgetphotos() to get a reproduceable random sequence
						else {
							$photos = wppa_get_widgetphotos( $album, " ORDER BY RAND(".$idn.")" );
						}

						// Image found
						$id = $photos[$idn]['id'];
					}
				}
			}
			break;

	}

	if ( $id ) {
		$result = wppa_cache_photo( $id );
	}
	else {
		$result = false;
	}

	$potd = $result;
	return $result;
}

// Get widget checkbox html
function wppa_widget_checkbox( $class, $item, $value, $label, $subtext = '' ) {

	$result =
	'<p style="clear:both;" >' .
		'<input' .
			' id="' . $class->get_field_id( $item ) . '"' .
			' name="' . $class->get_field_name( $item ) . '"' .
			' type="checkbox"' .
			wppa_checked( $value ) .
		' />&nbsp;' .
		'<label' .
			' for="' . $class->get_field_id( $item ) . '"' .
			' >' .
			$label .
		'</label>' .
		( $subtext ? '<small ><br />' . $subtext . '</small>' : '' ) .
	'</p>';

	return $result;
}

// Widget input html
//
// Typical usage:
//
// echo 		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) );
//
function wppa_widget_input( $class, $item, $value, $label, $subtext = '' ) {

	$result =
	'<p style="clear:both;" >' .
		'<label' .
			' for="' . $class->get_field_id( $item ) . '"' .
			' >' .
			$label . ':' .
		'</label>' .
		'<input' .
			' class="widefat"' .
			' id="' . $class->get_field_id( $item ) . '"' .
			' name="' . $class->get_field_name( $item ) . '"' .
			' type="text"' .
			' value="' . esc_attr( $value ) . '"' .
		'/>' .
		( $subtext ? '<small>' . $subtext . '</small>' : '' ) .
	'</p>';

	return $result;
}

function wppa_widget_textarea( $class, $item, $value, $label ) {

	$result =
	'<p>' .
		'<label' .
			' for="' . $class->get_field_id( $item ) . '"' .
			' >' .
			$label . ':' .
		'</label>' .
		'<textarea' .
			' class="widefat"' .
			' rows="16"' .
			' id="' . $class->get_field_id( 'text' ) . '"' .
			' name="' . $class->get_field_name( 'text' ) . '"' .
			' >' .
			$value .
		'</textarea>' .
	'</p>';

	return $result;
}

// Widget input number_format
function wppa_widget_number( $class, $item, $value, $label, $min, $max, $subtext = '', $float = false ) {

	$_50 = wppa_is_ie() ? '60px;': '60%';

	$result =
	'<p' . ( $float ? ' style="width:50%;float:left;"' : '' ) . '>' .
		'<label' .
			' for="' . $class->get_field_id( $item ) . '"' .
			' >' .
			$label . ':' .
		'</label>' .
		'<br />' .
		'<input' .
			' id="' . $class->get_field_id( $item ) . '"' .
			' name="' . $class->get_field_name( $item ) . '"' .
			' style="' . ( $float ? 'width:' . $_50 . ';' : '' ) . '"' .
			' type="number"' .
			' min="' . $min . '"' .
			' max="' . $max . '"' .
			' value="' . esc_attr( $value ) . '"' .
			' onchange="if(jQuery(this).val()<' . $min . '||jQuery(this).val()>' . $max . '){alert(\'' . esc_js( sprintf( __( 'Please enter a number >= %1s and <= %2s', 'wp-photo-album-plus'),$min, $max ) ) . '\');jQuery(this).val(\'' . $max . '\');return false;}"' .
		'/>' .
		( $subtext ? '<small>' . $subtext . '</small>' : '' ) .
	'</p>';

	return $result;
}

// Widget selection box
function wppa_widget_selection( $class, $item, $value, $label, $options, $values, $disabled = array(), $cls = 'widefat', $subtext = '' ) {

	$result =
	'<p>' .
		'<label' .
			' for="' . $class->get_field_id( $item ) . '"' .
			' >' .
			$label . ':' .
		'</label>' .
		( $cls ? '' : '<br />' ) .
		'<select' .
			' class="' . $cls . '"' .
			' id="' . $class->get_field_id( $item ) . '"' .
			' name="' . $class->get_field_name( $item ) . '"' .
			' >';

			foreach( array_keys( $options ) as $key ) {
				$result .=
				'<option' .
					' value="' . $values[$key] . '"' .
					( $value == $values[$key] ? ' selected="selected"' : '' ) .
					( isset( $disabled[$key] ) && $disabled[$key] ? ' disabled="disabled"' : '' ) .
					' >' .
					__( $options[$key] ) .
				'</option>';
			}

		$result .=
		'</select>' .
		( $subtext ? '<small>' . ( $cls ? '' : '<br />' ) .$subtext . '</small>' : '' ) .
	'</p>';

	return $result;
}

// Widget selection box frame
function wppa_widget_selection_frame( $class, $item, $body, $label, $multi = false, $subtext = '' ) {

	$result =
	'<p>' .
		'<label' .
			' for="' . $class->get_field_id( $item ) . '"' .
			' >' .
			$label . ':' .
		'</label>' .
		'<select' .
			' class="widefat"' .
			' id="' . $class->get_field_id( $item ) . '"' .
			' name="' . $class->get_field_name( $item ) . ( $multi ? '[]' : '' ) . '"' .
			( $multi ? ' multiple="multiple"' : '' ) .
			' >' .
			$body .
		'</select>' .
		( $subtext ? '<small >' . $subtext . '</small>' : '' ) .
	'</p>';

	return $result;
}

// Get checked html
function wppa_checked( $arg ) {

	// Backward compat yes/no selectionbox
	if ( $arg == 'no' ) {
		$result = '';
	}

	// 'yes' or 'on'
	elseif ( $arg ) {
		$result = ' checked="checked"';
	}

	// ''
	else {
		$result = '';
	}

	return $result;
}