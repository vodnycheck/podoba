<?php
/* wppa-utils.php
* Package: wp-photo-album-plus
*
* Contains low-level utility routines
* Version 6.7.09
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

global $wppa_supported_photo_extensions;
$wppa_supported_photo_extensions = array( 'jpg', 'jpeg', 'png', 'gif' );

// Get url in wppa dir
function wppa_url( $arg ) {
	return WPPA_URL . '/' . $arg;
}

// get url of thumb
function wppa_get_thumb_url( $id, $fix_poster_ext = true, $system = 'flat', $x = '0', $y = '0' ) {
global $blog_id;

	// Does photo exist?
	$thumb = wppa_cache_thumb( $id );
	if ( ! $thumb ) return '';

	// Set owner if required
	wppa_set_owner_to_name( $id );

	$thumb = wppa_cache_thumb( $id );

	// If in the cloud...
	$is_old = wppa_too_old_for_cloud( $id );
	if ( wppa_cdn( 'front' ) && ! wppa_is_multi( $id ) && ! $is_old && ! wppa_is_stereo( $id ) ) {
		if ( $x && $y ) {		// Only when size is given !! To prevent download of the fullsize image
			switch ( wppa_cdn( 'front' ) ) {
				case 'cloudinary':
					$transform	= explode( ':', wppa_opt( 'thumb_aspect' ) );
					$t 			= 'limit';
					if ( $transform['2'] == 'clip' ) $t = 'fill';
					if ( $transform['2'] == 'padd' ) $t = 'pad,b_black';
					$q 			= wppa_opt( 'jpeg_quality' );
					$sizespec 	= ( $x && $y ) ? 'w_'.$x.',h_'.$y.',c_'.$t.',q_'.$q.'/' : '';
					$prefix 	= ( is_multisite() && ! WPPA_MULTISITE_GLOBAL ) ? $blog_id.'-' : '';
					$s = is_ssl() ? 's' : '';
					$url = 'http'.$s.'://res.cloudinary.com/'.get_option('wppa_cdn_cloud_name').'/image/upload/'.$sizespec.$prefix.$thumb['id'].'.'.$thumb['ext'];
					return $url;
					break;

			}
		}
	}

	if ( get_option('wppa_file_system') == 'flat' ) $system = 'flat';	// Have been converted, ignore argument
	if ( get_option('wppa_file_system') == 'tree' ) $system = 'tree';	// Have been converted, ignore argument

	if ( $system == 'tree' ) {
		$result = WPPA_UPLOAD_URL . '/thumbs/' . wppa_expand_id( $thumb['id'] ) . '.' . $thumb['ext'];
	}
	else {
		$result = WPPA_UPLOAD_URL . '/thumbs/' . $thumb['id'] . '.' . $thumb['ext'];
	}

	if ( $fix_poster_ext ) {
		$result = wppa_fix_poster_ext( $result, $thumb['id'] );
	}

	$result .= '?ver=' . get_option( 'wppa_thumb_version', '1' );

	return $result;
}

// Bump thumbnail version number
function wppa_bump_thumb_rev() {
	wppa_update_option('wppa_thumb_version', get_option('wppa_thumb_version', '1') + '1');
}

// get path of thumb
function wppa_get_thumb_path( $id, $fix_poster_ext = true, $system = 'flat' ) {

	$thumb = wppa_cache_thumb( $id );
	if ( ! $thumb ) {
		return false;
	}

	if ( get_option( 'wppa_file_system' ) == 'flat' ) $system = 'flat';	// Has been converted, ignore argument
	if ( get_option( 'wppa_file_system' ) == 'tree' ) $system = 'tree';	// Has been converted, ignore argument

	if ( $system == 'tree' ) {
		$result = WPPA_UPLOAD_PATH.'/thumbs/'.wppa_expand_id($thumb['id'], true).'.'.$thumb['ext'];
	}
	else {
		$result = WPPA_UPLOAD_PATH.'/thumbs/'.$thumb['id'].'.'.$thumb['ext'];
	}

	if ( $fix_poster_ext ) {
		$result = wppa_fix_poster_ext( $result, $thumb['id'] );
	}

	return $result;
}

// get url of a full sized image
function wppa_get_photo_url( $id, $fix_poster_ext = true, $system = 'flat', $x = '0', $y = '0' ) {
global $blog_id;
global $wppa_supported_stereo_types;

	// Does photo exist?
	$thumb = wppa_cache_thumb( $id );
	if ( ! $thumb ) return '';

 	// Set owner if required
	wppa_set_owner_to_name( $id );

	// Must re-get cached thumb
	$thumb = wppa_cache_thumb( $id );


	if ( is_feed() && wppa_switch( 'feed_use_thumb') ) return wppa_get_thumb_url($id, true, $system);

	// If in the cloud...
	$for_sm = wppa( 'for_sm' ); 				// Social media do not accept cloud images
	$is_old = wppa_too_old_for_cloud( $id );
	if ( wppa_cdn( 'front' ) && ! wppa_is_multi( $id ) && ! $is_old && ! wppa_is_stereo( $id ) && ! $for_sm && ! $thumb['magickstack'] ) {
		switch ( wppa_cdn( 'front' ) ) {
			case 'cloudinary':
				$x = round($x);
				$y = round($y);
				$prefix 	= ( is_multisite() && ! WPPA_MULTISITE_GLOBAL ) ? $blog_id.'-' : '';
				$t 			= wppa_switch( 'enlarge') ? 'fit' : 'limit';
				$q 			= wppa_opt( 'jpeg_quality' );
				$sizespec 	= ( $x && $y ) ? 'w_'.$x.',h_'.$y.',c_'.$t.',q_'.$q.'/' : '';
				$s = is_ssl() ? 's' : '';
				$url = 'http'.$s.'://res.cloudinary.com/'.get_option('wppa_cdn_cloud_name').'/image/upload/'.$sizespec.$prefix.$thumb['id'].'.'.$thumb['ext'];
				return $url;
				break;

		}
	}

	// Stereo?
	if ( wppa_is_stereo( $id ) ) {

		// Get type from cookie
		$st = isset( $_COOKIE["stereotype"] ) ? $_COOKIE["stereotype"] : 'color';
		if ( ! in_array( $st, $wppa_supported_stereo_types ) ) {
			$st = '_flat';
		}

		// Get glass from cookie
		$sg = 'rc';
		if ( isset( $_COOKIE["stereoglass"] ) && $_COOKIE["stereoglass"] == 'greenmagenta' ) {
			$sg = 'gm';
		}

		// Create the file if not present
		if ( ! is_file( wppa_get_stereo_path( $id, $st, $sg ) ) ) {
			wppa_create_stereo_image( $id, $st, $sg );
		}

		// Build the url
		if ( $st == '_flat' ) {
			$url = WPPA_UPLOAD_URL . '/stereo/' . $id . '-' . $st . '.jpg' . '?ver=' . get_option( 'wppa_photo_version', '1' );
		}
		else {
			$url = WPPA_UPLOAD_URL . '/stereo/' . $id . '-' . $st . '-' . $sg . '.jpg' . '?ver=' . get_option( 'wppa_photo_version', '1' );
		}

		// Done
		return $url;
	}

	if ( get_option('wppa_file_system') == 'flat' ) $system = 'flat';	// Have been converted, ignore argument
	if ( get_option('wppa_file_system') == 'tree' ) $system = 'tree';	// Have been converted, ignore argument

	if ( $system == 'tree' ) {
		$result = WPPA_UPLOAD_URL . '/' . wppa_expand_id( $thumb['id'] ) . '.' . $thumb['ext'];
	}
	else {
		$result = WPPA_UPLOAD_URL . '/' . $thumb['id'] . '.' . $thumb['ext'];
	}

	if ( $fix_poster_ext ) {
		$result = wppa_fix_poster_ext( $result, $thumb['id'] );
	}

	// Social media do not like querystrings
	if ( ! wppa( 'no_ver' ) ) {
		$result .= '?ver=' . get_option( 'wppa_photo_version', '1' );
	}

	return $result;
}

// Bump Fullsize photo version number
function wppa_bump_photo_rev() {
	wppa_update_option('wppa_photo_version', get_option('wppa_photo_version', '1') + '1');
}

// get path of a full sized image
function wppa_get_photo_path( $id, $fix_poster_ext = true, $system = 'flat' ) {

	$thumb = wppa_cache_thumb( $id );
	if ( ! $thumb ) {
		return false;
	}

	if ( get_option( 'wppa_file_system' ) == 'flat' ) $system = 'flat';	// Have been converted, ignore argument
	if ( get_option( 'wppa_file_system' ) == 'tree' ) $system = 'tree';	// Have been converted, ignore argument

	if ( $system == 'tree' ) {
		$result = WPPA_UPLOAD_PATH . '/' . wppa_expand_id( $thumb['id'], true ) . '.' . $thumb['ext'];
	}
	else {
		$result = WPPA_UPLOAD_PATH . '/' . $thumb['id'] . '.' . $thumb['ext'];
	}
	if ( $fix_poster_ext ) {
		$result = wppa_fix_poster_ext( $result, $thumb['id'] );
	}
	return $result;
}

// Expand id to subdir chain for new file structure
function wppa_expand_id( $xid, $makepath = false ) {

	$result = '';
	$id = $xid;
	$len = strlen( $id );
	while ( $len > '2' ) {
		$result .= substr( $id, '0', '2' ) . '/';
		$id = substr( $id, '2' );
		$len = strlen( $id );
		if ( $makepath ) {
			$path = WPPA_UPLOAD_PATH . '/' . $result;
			if ( ! is_dir( $path ) ) wppa_mktree( $path );
			$path = WPPA_UPLOAD_PATH . '/thumbs/' . $result;
			if ( ! is_dir( $path ) ) wppa_mktree( $path );
		}
	}
	$result .= $id;
	return $result;
}

// Makes the html for the geo support for current theme and adds it to $wppa['geo']
function wppa_do_geo( $id, $location ) {
global $wppa;

	$temp 	= explode( '/', $location );
	$lat 	= $temp['2'];
	$lon 	= $temp['3'];

	$type 	= wppa_opt( 'gpx_implementation' );

	// Switch on implementation type
	switch ( $type ) {
		case 'external-plugin':
			$geo = str_replace( 'w#lon', $lon, str_replace( 'w#lat', $lat, wppa_opt( 'gpx_shortcode' ) ) );
			$geo = str_replace( 'w#ip', $_SERVER['REMOTE_ADDR'], $geo );
			$geo = str_replace( 'w#gmapikey', wppa_opt( 'map_apikey' ), $geo );

			$geo = do_shortcode( $geo );
			$wppa['geo'] .= '<div id="geodiv-' . wppa( 'mocc' ) . '-' . $id . '" style="display:none;">' . $geo . '</div>';
			break;
		case 'wppa-plus-embedded':
			if ( $wppa['geo'] == '' ) { 	// First
				$wppa['geo'] = '
<div id="map-canvas-' . wppa( 'mocc' ).'" style="height:' . wppa_opt( 'map_height' ) . 'px; width:100%; padding:0; margin:0; font-size: 10px;" ></div>
<script type="text/javascript" >
	if ( typeof ( _wppaLat ) == "undefined" ) { var _wppaLat = new Array();	var _wppaLon = new Array(); }
	_wppaLat[' . wppa( 'mocc' ) . '] = new Array(); _wppaLon[' . wppa( 'mocc' ) . '] = new Array();
</script>';
			}	// End first
			$wppa['geo'] .= '
<script type="text/javascript">_wppaLat[' . wppa( 'mocc' ) . '][' . $id . '] = ' . $lat.'; _wppaLon[' . wppa( 'mocc' ) . '][' . $id.'] = ' . $lon . ';</script>';
			break;	// End native
	}
}

// See if an album is in a separate tree
function wppa_is_separate( $id ) {

	if ( $id == '' ) return false;
	if ( ! wppa_is_int( $id ) ) return false;
	if ( $id == '-1' ) return true;
	if ( $id < '1' ) return false;
	$alb = wppa_get_parentalbumid( $id );

	return wppa_is_separate( $alb );
}

// Get the albums parent
function wppa_get_parentalbumid($id) {
static $prev_album_id;

	if ( ! wppa_is_int($id) || $id < '1' ) return '0';

	$album = wppa_cache_album($id);
	if ( $album === false ) {
		wppa_log( 'error', 'Album '.$id.' no longer exists, but is still set as a parent of '.$prev_album_id.'. Please correct this.' );
		return '-9';	// Album does not exist
	}
	$prev_album_id = $id;
	return $album['a_parent'];
}

function wppa_html($str) {
// It is assumed that the raw data contains html.
// If html not allowed, filter specialchars
// To prevent duplicate filtering, first entity_decode
	$result = html_entity_decode($str);
	if ( ! wppa_switch( 'html') && ! current_user_can('wppa_moderate') ) {
		$result = htmlspecialchars($str);
	}
	return $result;
}


// get a photos album id
function wppa_get_album_id_by_photo_id( $id ) {

	if ( ! is_numeric($id) || $id < '1' ) wppa_dbg_msg('Invalid arg wppa_get_album_id_by_photo_id('.$id.')', 'red');
	$thumb = wppa_cache_thumb($id);
	return $thumb['album'];
}

function wppa_get_rating_count_by_id($id) {

	if ( ! is_numeric($id) || $id < '1' ) wppa_dbg_msg('Invalid arg wppa_get_rating_count_by_id('.$id.')', 'red');
	$thumb = wppa_cache_thumb($id);
	return $thumb['rating_count'];
}

function wppa_get_rating_by_id($id, $opt = '') {
global $wpdb;

	if ( ! is_numeric($id) || $id < '1' ) wppa_dbg_msg('Invalid arg wppa_get_rating_by_id('.$id.', '.$opt.')', 'red');
	$thumb = wppa_cache_thumb( $id );
	$rating = $thumb['mean_rating'];
	if ( $rating ) {
		$i = wppa_opt( 'rating_prec' );
		$j = $i + '1';
		$val = sprintf('%'.$j.'.'.$i.'f', $rating);
		if ($opt == 'nolabel') $result = $val;
		else $result = sprintf(__('Rating: %s', 'wp-photo-album-plus'), $val);
	}
	else $result = '';
	return $result;
}

function wppa_get_my_rating_by_id($id, $opt = '') {
global $wpdb;

	if ( ! is_numeric($id) || $id < '1' ) wppa_dbg_msg('Invalid arg wppa_get_my_rating_by_id('.$id.', '.$opt.')', 'red');

	$my_ratings = $wpdb->get_results( $wpdb->prepare( "SELECT `value` FROM `" . WPPA_RATING . "` WHERE `photo` = %d AND `user` = %s", $id, wppa_get_user() ), ARRAY_A );
	if ( $my_ratings ) {
		$rating = 0;
		foreach ( $my_ratings as $r ) {
			$rating += $r['value'];
		}
		$rating /= count( $my_ratings );
	}
	else {
		$rating = '0';
	}
	if ( $rating ) {
		$i = wppa_opt( 'rating_prec' );
		$j = $i + '1';
		$val = sprintf('%'.$j.'.'.$i.'f', $rating);
		if ($opt == 'nolabel') $result = $val;
		else $result = sprintf(__('Rating: %s', 'wp-photo-album-plus'), $val);
	}
	else $result = '0';
	return $result;
}

function wppa_switch( $xkey ) {
global $wppa_opt;

	// Are we initialized?
	if ( empty( $wppa_opt ) ) {
		wppa_initialize_runtime();
	}

	// Old style?
	if ( substr( $xkey, 0, 5 ) == 'wppa_' ) {
		wppa_log( 'Dbg', $xkey . ' used as old style switch', true );
		$key = $xkey;
	}
	else {
		$key = 'wppa_' . $xkey;
	}

	if ( isset( $wppa_opt[$key] ) ) {
		if ( $wppa_opt[$key] == 'yes' ) return true;
		elseif ( $wppa_opt[$key] == 'no' ) return false;
		else wppa_log( 'Dbg', '$wppa_opt['.$key.'] is not a yes/no setting', true );
		return $wppa_opt[$key]; // Return the right value afterall
	}

	wppa_log( 'Dbg', '$wppa_opt['.$key.'] is not a setting', true );

	return false;
}

function wppa_opt( $xkey ) {
global $wppa_opt;

	// Are we initialized?
	if ( empty( $wppa_opt ) ) {
		wppa_initialize_runtime();
	}

	// Old style?
	if ( substr( $xkey, 0, 5 ) == 'wppa_' ) {
		wppa_log( 'Dbg', $xkey . ' used as old style option', true );
		$key = $xkey;
	}
	else {
		$key = 'wppa_' . $xkey;
	}

	if ( isset( $wppa_opt[$key] ) ) {
		if ( $wppa_opt[$key] == 'yes' || $wppa_opt[$key] == 'no' ) {
			wppa_log( 'Dbg', '$wppa_opt['.$key.'] is a yes/no setting, not a value', true );
			return ( $wppa_opt[$key] == 'yes' ); // Return the right value afterall
		}
		return trim( $wppa_opt[$key] );
	}

	wppa_log( 'Dbg', '$wppa_opt['.$key.'] is not a setting', true );

	return false;
}

// Getter / setter of runtime parameter
function wppa( $key, $newval = 'nil' ) {
global $wppa;

	// Array defined?
	if ( empty( $wppa ) ) {
		wppa_reset_occurrance();
	}

	// Invalid key?
	if ( ! isset( $wppa[$key] ) ) {

		// If indesx not exists: fatal error
		if ( ! in_array( $key, array_keys( $wppa ) ) ) {
			wppa_log( 'Err', '$wppa[\'' . $key . '\'] is not defined in reset_occurrance', true );
			return false;
		}

		// Exists but NULL, Not fatal
		else {
			wppa_log( 'Err', '$wppa[\'' . $key . '\'] has value NULL', true );

			// NULL is illegal, replace it by false, to prevent many equal errormessages
			$wppa[$key] = false;
		}
	}

	// Existing key, Get old value
	$oldval = $wppa[$key];

	// New value supplied?
	if ( $newval !== 'nil' ) {
		$wppa[$key] = $newval;
	}

	return $oldval;
}

// Add (concat) value to runtime parameter
function wppa_add( $key, $newval ) {
global $wppa;

	// Array defined?
	if ( empty( $wppa ) ) {
		wppa_reset_occurrance();
	}

	// Valid key?
	if ( isset( $wppa[$key] ) ) {

		// Get old value
		$oldval = $wppa[$key];

		// Add new value
		$wppa[$key] .= $newval;
	}

	// Invalid key
	else {
		wppa_log( 'Err', '$wppa[\''.$key.'\'] is not defined', true );
		return false;
	}

	return $oldval;
}

function wppa_display_root( $id ) {
	$all = __('All albums', 'wp-photo-album-plus' );
	if ( ! $id || $id == '-2' ) return $all;
	$album = wppa_cache_album( $id );
	if ( ! $album ) return '';
	$albums = array();
	$albums[] = $album;
	$albums = wppa_add_paths( $albums );
	return $albums[0]['name'];
}

function wppa_add_paths( $albums ) {

	if ( is_array( $albums ) ) foreach ( array_keys( $albums ) as $index ) {
		$tempid = $albums[$index]['id'];
		$albums[$index]['name'] = __( stripslashes( $albums[$index]['name'] ) );	// Translate name
		while ( $tempid > '0' ) {
			$tempid = wppa_get_parentalbumid($tempid);
			if ( $tempid > '0' ) {
				$albums[$index]['name'] = wppa_get_album_name($tempid).' > '.$albums[$index]['name'];
			}
			elseif ( $tempid == '-1' ) $albums[$index]['name'] = '-s- '.$albums[$index]['name'];
		}
	}
	return $albums;
}

function wppa_add_parents($pages) {
global $wpdb;
static $parents;
static $titles;

	// Pre-fill $parents
	if ( empty( $parents ) ) {
		$temp = $wpdb->get_results( "SELECT `ID`, `post_parent` FROM `" . $wpdb->posts . "`", ARRAY_A );
		if ( ! empty( $temp ) ) {
			foreach( $temp as $item ) {
				$parents[$item['ID']] = $item['post_parent'];
			}
		}
	}

	if ( is_array($pages) ) foreach ( array_keys($pages) as $index ) {
		$tempid = $pages[$index]['ID'];
		$pages[$index]['post_title'] = __(stripslashes($pages[$index]['post_title']));
		while ( $tempid > '0') {
			if ( isset( $parents[$tempid] ) ) {
				$tempid = $parents[$tempid];
			}
			else {
				$t = $wpdb->get_var($wpdb->prepare("SELECT `post_parent` FROM `" . $wpdb->posts . "` WHERE `ID` = %s", $tempid));
				$parents[$tempid] = $t;
				$tempid = $t;
			}
			if ( $tempid > '0' ) {
				if ( ! isset( $titles[$tempid] ) ) {
					$titles[$tempid] = __(stripslashes($wpdb->get_var($wpdb->prepare("SELECT `post_title` FROM `" . $wpdb->posts . "` WHERE `ID` = %s", $tempid))));
				}
				$pages[$index]['post_title'] = $titles[$tempid].' > '.$pages[$index]['post_title'];
			}
			else $tempid = '0';
		}
	}
	return $pages;
}

// Sort an array on a column, keeping the indexes
function wppa_array_sort($array, $on, $order=SORT_ASC) {

    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
            break;
            case SORT_DESC:
                arsort($sortable_array);
            break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

function wppa_get_taglist() {

	$result = WPPA_MULTISITE_GLOBAL ? get_site_option( 'wppa_taglist', 'nil' ) : get_option( 'wppa_taglist', 'nil' );
	if ( $result == 'nil' ) {
		$result = wppa_create_taglist();
	}
	else {
		if ( is_array($result) ) foreach ( array_keys($result) as $tag ) {
			$result[$tag]['ids'] = wppa_index_string_to_array($result[$tag]['ids']);
		}
	}
	return $result;
}

function wppa_clear_taglist() {

	$result = WPPA_MULTISITE_GLOBAL ? update_site_option( 'wppa_taglist', 'nil' ) : update_option( 'wppa_taglist', 'nil' );
	$result = WPPA_MULTISITE_GLOBAL ? get_site_option( 'wppa_taglist', 'nil' ) : get_option( 'wppa_taglist', 'nil' );
	if ( $result != 'nil' ) {
		wppa_log( 'Warning', 'Could not clear taglist' ) ;
	}
}

function wppa_create_taglist() {
global $wpdb;

	// Initialize
	$result 	= false;
	$total 		= '0';
	$done 		= false;
	$skip 		= '0';
	$pagsize 	= '10000';

	// To avoid out of memory, we do all the photos in chunks of $pagsize
	while ( ! $done ) {

		// Get the chunk
		$photos = $wpdb->get_results( 	"SELECT `id`, `tags` " .
										"FROM `" . WPPA_PHOTOS . "` " .
										"WHERE `status` <> 'pending' " .
										"AND `status` <> 'scheduled' " .
										"AND `tags` <> '' " .
										"LIMIT " . $skip . "," . $pagsize,
										ARRAY_A );

		// If photos found, process the tags, if any
		if ( $photos ) foreach ( $photos as $photo ) {
			$tags = explode( ',', $photo['tags'] );

			// Tags found?
			if ( $tags ) foreach ( $tags as $tag ) {
				if ( $tag ) {
					if ( ! isset( $result[$tag] ) ) {	// A new tag
						$result[$tag]['tag'] = $tag;
						$result[$tag]['count'] = '1';
						$result[$tag]['ids'][] = $photo['id'];
					}
					else {								// An existing tag
						$result[$tag]['count']++;
						$result[$tag]['ids'][] = $photo['id'];
					}
				}
				$total++;
			}
		}

		// If no more photos, we are done
		else {
			$done = true;
		}
		$skip += $pagsize;
	}

	// Add the minimum existing tags
	$minimum_tags = wppa_opt( 'minimum_tags' );
	if ( $minimum_tags ) {
		$tags = explode( ',', $minimum_tags );
		foreach ( $tags as $tag ) {
			if ( $tag ) {
				if ( ! isset( $result[$tag] ) ) { 	// A not occurring tag
					$result[$tag]['tag'] = $tag;
					$result[$tag]['count'] = '0';
					$result[$tag]['ids'] = array();
				}
			}
		}
	}

	// If any tags found, calculate fractions
	$tosave = array();
	if ( is_array( $result ) ) {
		foreach ( array_keys( $result ) as $key ) {
			$result[$key]['fraction'] = sprintf( '%4.2f', $result[$key]['count'] / $total );
		}
		$result = wppa_array_sort( $result, 'tag' );
		$tosave = $result;

		// Convert the arrays to compressed enumerations
		foreach ( array_keys( $tosave ) as $key ) {
			$tosave[$key]['ids'] = wppa_index_array_to_string( $tosave[$key]['ids'] );
		}
	}

	// Save the new taglist
	$bret = WPPA_MULTISITE_GLOBAL ? update_site_option( 'wppa_taglist', $tosave ) : update_option( 'wppa_taglist', $tosave );
	if ( ! $bret ) {
		wppa_log( 'Err', 'Unable to save taglist' );
	}

	// And return the result
	return $result;
}

function wppa_get_catlist() {

	$result = WPPA_MULTISITE_GLOBAL ? get_site_option( 'wppa_catlist', 'nil' ) : get_option( 'wppa_catlist', 'nil' );
	if ( $result == 'nil' ) {
		$result = wppa_create_catlist();
	}
	else {
		foreach ( array_keys($result) as $cat ) {
			$result[$cat]['ids'] = wppa_index_string_to_array($result[$cat]['ids']);
		}
	}
	return $result;
}

function wppa_clear_catlist() {

	$result = WPPA_MULTISITE_GLOBAL ? update_site_option( 'wppa_catlist', 'nil' ) : update_option( 'wppa_catlist', 'nil' );
	$result = WPPA_MULTISITE_GLOBAL ? get_site_option( 'wppa_catlist', 'nil' ) : get_option( 'wppa_catlist', 'nil' );
	if ( $result != 'nil' ) {
		wppa_log( 'Warning', 'Could not clear catlist' ) ;
	}
}

function wppa_create_catlist() {
global $wpdb;

	$result = false;
	$total = '0';
	$albums = $wpdb->get_results("SELECT `id`, `cats` FROM `".WPPA_ALBUMS."` WHERE `cats` <> ''", ARRAY_A);
	if ( $albums ) foreach ( $albums as $album ) {
		$cats = explode(',', $album['cats']);
		if ( $cats ) foreach ( $cats as $cat ) {
			if ( $cat ) {
				if ( ! isset($result[$cat]) ) {	// A new cat
					$result[$cat]['cat'] = $cat;
					$result[$cat]['count'] = '1';
					$result[$cat]['ids'][] = $album['id'];
				}
				else {							// An existing cat
					$result[$cat]['count']++;
					$result[$cat]['ids'][] = $album['id'];
				}
			}
			$total++;
		}
	}
	$tosave = array();
	if ( is_array($result) ) {
		foreach ( array_keys($result) as $key ) {
			$result[$key]['fraction'] = sprintf('%4.2f', $result[$key]['count'] / $total);
		}
		$result = wppa_array_sort($result, 'cat');
		$tosave = $result;
		foreach ( array_keys($tosave) as $key ) {
			$tosave[$key]['ids'] = wppa_index_array_to_string($tosave[$key]['ids']);
		}
	}
	$bret = WPPA_MULTISITE_GLOBAL ? update_site_option( 'wppa_catlist', $tosave ) : update_option( 'wppa_catlist', $tosave );
	if ( ! $bret ) {
		wppa_log( 'Err', 'Unable to save catlist' );
	}
	return $result;
}

function wppa_update_option( $option, $value ) {
global $wppa_opt;

	// Update the option
	update_option( $option, $value );

	// Update the local cache
	$wppa_opt[$option] = $value;

	// Delete the cached options
//	delete_option( 'wppa_cached_options' );

	// Remove init.js files, they will be auto re-created
	$files = glob( WPPA_PATH.'/wppa-init.*.js' );
	if ( $files ) {
		foreach ( $files as $file ) {
			@ unlink ( $file );
		}
	}

	// Remove dynamic css files, they will be auto re-created
	if ( is_file ( WPPA_PATH.'/wppa-dynamic.css' ) ) {
		@ unlink ( WPPA_PATH.'/wppa-dynamic.css' );
	}
}

function wppa_album_exists( $id ) {
global $wpdb;
static $existing_albums;

	if ( ! wppa_is_int( $id ) ) {
		return false;
	}

	// If existing albums cache not filled yet, fill it.
	if ( ! $existing_albums ) {
		$existing_albums = $wpdb->get_col( "SELECT `id` FROM `" . WPPA_ALBUMS . "`" );
	}

	return in_array( $id, $existing_albums, true );
}

function wppa_photo_exists( $id ) {
global $wpdb;

	if ( ! wppa_is_int( $id ) ) {
		return false;
	}
	return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `id` = %s", $id ) );
}

function wppa_albumphoto_exists($alb, $photo) {
global $wpdb;
	return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `album` = %s AND `filename` = %s", $alb, $photo));
}

function wppa_dislike_check($photo) {
global $wpdb;

	$count = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_RATING."` WHERE `photo` = %s AND `value` = -1", $photo ));

	if ( wppa_opt( 'dislike_mail_every' ) > '0') {		// Feature enabled?
		if ( $count % wppa_opt( 'dislike_mail_every' ) == '0' ) {	// Mail the admin
			$to        = get_bloginfo('admin_email');
			$subj 	   = __('Notification of inappropriate image', 'wp-photo-album-plus');
			$cont['0'] = sprintf(__('Photo %s has been marked as inappropriate by %s different visitors.', 'wp-photo-album-plus'), $photo, $count);
			$cont['1'] = '<a href="'.get_admin_url().'admin.php?page=wppa_admin_menu&tab=pmod&photo='.$photo.'" >'.__('Manage photo', 'wp-photo-album-plus').'</a>';
			wppa_send_mail($to, $subj, $cont, $photo);
		}
	}

	if ( wppa_opt( 'dislike_set_pending' ) > '0') {		// Feature enabled?
		if ( $count == wppa_opt( 'dislike_set_pending' ) ) {
			$wpdb->query($wpdb->prepare( "UPDATE `".WPPA_PHOTOS."` SET `status` = 'pending' WHERE `id` = %s", $photo ));
			$to        = get_bloginfo('admin_email');
			$subj 	   = __('Notification of inappropriate image', 'wp-photo-album-plus');
			$cont['0'] = sprintf(__('Photo %s has been marked as inappropriate by %s different visitors.', 'wp-photo-album-plus'), $photo, $count);
			$cont['0'] .= "\n".__('The status has been changed to \'pending\'.', 'wp-photo-album-plus');
			$cont['1'] = '<a href="'.get_admin_url().'admin.php?page=wppa_admin_menu&tab=pmod&photo='.$photo.'" >'.__('Manage photo', 'wp-photo-album-plus').'</a>';
			wppa_send_mail($to, $subj, $cont, $photo);
		}
	}

	if ( wppa_opt( 'dislike_delete' ) > '0') {			// Feature enabled?
		if ( $count == wppa_opt( 'dislike_delete' ) ) {
			$to        = get_bloginfo('admin_email');
			$subj 	   = __('Notification of inappropriate image', 'wp-photo-album-plus');
			$cont['0'] = sprintf(__('Photo %s has been marked as inappropriate by %s different visitors.', 'wp-photo-album-plus'), $photo, $count);
			$cont['0'] .= "\n".__('It has been deleted.', 'wp-photo-album-plus');
			$cont['1'] = '';//<a href="'.get_admin_url().'admin.php?page=wppa_admin_menu&tab=pmod&photo='.$photo.'" >'.__('Manage photo').'</a>';
			wppa_send_mail($to, $subj, $cont, $photo);
			wppa_delete_photo($photo);
		}
	}
}


// Get number of dislikes for a given photo id
function wppa_dislike_get( $id ) {
global $wpdb;

	$count = $wpdb->get_var( $wpdb->prepare( 	"SELECT COUNT(*) " .
												"FROM `" . WPPA_RATING . "` " .
												"WHERE `photo` = %s " .
												"AND `value` = -1",
												$id
											)
							);
	return $count;
}

// Get number of pending ratings for a given photo id
function wppa_pendrat_get( $id ) {
global $wpdb;

	$count = $wpdb->get_var( $wpdb->prepare( 	"SELECT COUNT(*) " .
												"FROM `" . WPPA_RATING . "` " .
												"WHERE `photo` = %s AND " .
												"`status` = 'pending'",
												$id
											)
							);
	return $count;
}

// Send the owner of a photo an email telling he has a new approved comment
// $id is comment id.
function wppa_send_comment_approved_email( $id ) {
global $wpdb;

	// Feature enabled?
	if ( ! wppa_switch( 'com_notify_approved' ) ) return;

	// Get comment
	$com = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPPA_COMMENTS . "` WHERE `id` = %d", $id ), ARRAY_A );
	if ( ! $com ) return;

	// Get photo owner
	$owner = wppa_get_photo_item( $com['photo'], 'owner' );
	if ( ! $owner ) return;

	// Get email
	$user = wppa_get_user_by( 'login', $owner );
	if ( ! $user ) return;

	// Custom content?
	if ( wppa_opt( 'com_notify_approved_text' ) ) {

		// The subject
		$subject = wppa_opt( 'com_notify_approved_subj' );

		// The content
		$content = wppa_opt( 'com_notify_approved_text' );
		$content = str_replace( 'w#comment', $com['comment'], $content );
		$content = str_replace( 'w#user', $com['user'], $content );
		$content = wppa_translate_photo_keywords( $com['photo'], $content );

		// Try to send it with extra headers and with html
		$iret = wp_mail( 	$user->user_email,
							$subject,
							$content,
							array( 'Content-Type: text/html; charset=UTF-8' ),
							'' );
		if ( $iret ) return;

		// Failed
		if ( ! wppa_is_cron() ) {
			echo 'Mail sending Failed';
			echo 'Subj='.$subject.', content='.$content;
			wppa_process_failed_mail(	$user->user_email,
										$subject,
										$content,
										array( 'Content-Type: text/html; charset=UTF-8' ),
										'' );
		}

		return;
	}


	// Make email text
	$content =
	'<h3>' .
		__('Your photo has a new approved comment', 'wp-photo-album-plus') .
	'</h3>' .
	'<h3>' .
		__('From:', 'wp-photo-album-plus') . ' ' . $com['user'] .
	'</h3>' .
	'<h3>' .
		__('Comment:', 'wp-photo-album-plus') .
	'</h3>' .
	'<blockquote style="color:#000077; background-color: #dddddd; border:1px solid black; padding: 6px; border-radius 4px;" ><em> '.stripslashes($com['comment']).'</em></blockquote>';

	// Send mail
	wppa_send_mail( $user->user_email, __( 'Approved comment on photo', 'wp-photo-album-plus' ), $content, $com['photo'], 'void' );

}


function wppa_send_mail( $to, $subj, $cont, $photo = '0', $email = '' ) {

	$message_part_1 = '';
	$message_part_2 = '';
	$message_part_3 = '';

	$site = home_url();
	$site = str_replace( 'https://www.', '', $site );
	$site = str_replace( 'http://www.', '', $site );
	$site = str_replace( 'https://', '', $site );
	$site = str_replace( 'http://', '', $site );
	$spos = strpos( $site, '/' );
	if ( $spos  > '2' ) {
		$site = substr( $site, 0, $spos );
	}

	$headers 	= array( 	'From: noreply@' . $site,
							'Content-Type: text/html; charset=UTF-8'
						);

	$message_part_1	.=
'<html>' .
	'<head>' .
		'<title>'.$subj.'</title>' .
	'</head>' .
	'<body>' .
		'<h3>'.$subj.'</h3>' .
		( $photo ? '<p><img src="'.wppa_get_thumb_url($photo).'" '.wppa_get_imgalt($photo).'/></p>' : '' );
		if ( is_array($cont) ) {
			foreach ( $cont as $c ) if ( $c ) {
				$message_part_1 .= '<p>'.$c.'</p>';
			}
		}
		else {
			$message_part_1 .= '<p>'.$cont.'</p>';
		}

		if ( $email != 'void' ) {
			if ( is_user_logged_in() ) {
				global $current_user;
				$current_user = wp_get_current_user();
				$e = $current_user->user_email;
				$eml = sprintf(__('The visitors email address is: <a href="mailto:%s">%s</a>', 'wp-photo-album-plus'), $e, $e);
				$message_part_2 .= '<p>'.$eml.'</p>';
			}
			elseif ( $email ) {
				$e = $email;
				$eml = sprintf(__('The visitor says his email address is: <a href="mailto:%s">%s</a>', 'wp-photo-album-plus'), $e, $e);
				$message_part_2 .= '<p>'.$eml.'</p>';
			}
		}

		$message_part_3 .=
		'<p>' .
			'<small>' .
				sprintf(__('This message is automatically generated at %s. It is useless to respond to it.', 'wp-photo-album-plus'), '<a href="'.home_url().'" >'.home_url().'</a>') .
			'</small>' .
		'</p>' .
	'</body>' .
'</html>';

	$subject = '['.str_replace('&#039;', '', get_bloginfo('name') ).'] '.$subj;

	// Try to send it with extra headers and with html
	$iret = wp_mail( 	$to,
						$subject,
						$message_part_1 . $message_part_2 . $message_part_3,
						$headers,
						'' );
	if ( $iret ) return;

	wppa_log( 'Err', 'Mail sending failed. to=' . $to . ', subject=' . $subject . ', message=' . $message_part_1 . $message_part_2 . $message_part_3 );

	// Failed
	if ( ! wppa_is_cron() ) {
		echo 'Mail sending Failed';
		wppa_process_failed_mail(	$to,
									$subject,
									$message_part_1 . $message_part_2 . $message_part_3,
									$headers,
									'' );
	}

}

function wppa_get_imgalt( $id, $lb = false ) {

	// Get photo data
	$thumb = wppa_cache_thumb( $id );

	// Get raw image alt data
	switch ( wppa_opt( 'alt_type' ) ) {
		case 'fullname':
			$result = wppa_get_photo_name( $id );
			break;
		case 'namenoext':
			$result = wppa_strip_ext( wppa_get_photo_name( $id ) );
			break;
		case 'custom':
			$result = $thumb['alt'];
			break;
		default:
			$result = $id;
			break;
	}

	// Default if empty result
	if ( ! $result ) {
		$result = '0';
	}

	// Format for use in lightbox or direct use html
	if ( $lb ) {
		$result = esc_attr( str_replace( '"', "'", $result ) );
	}
	else {
		$result = ' alt="' . esc_attr( $result ) . '" ';
	}

	return $result;
}

function wppa_get_imgtags( $id ) {

	// Get photo data
	$thumb = wppa_cache_thumb( $id );

	return preg_replace('/,/', '', $thumb['tags']);
}

function wppa_get_imgdesc( $id ) {

	// Get photo data
	$thumb = wppa_cache_thumb( $id );

	return $thumb['description'];
}

function wppa_is_time_up($count = '') {
global $wppa_starttime;

	$timnow = microtime(true);
	$laptim = $timnow - $wppa_starttime;

	$maxwppatim = wppa_opt( 'max_execution_time' );
	$maxinitim = ini_get('max_execution_time');

	if ( $maxwppatim && $maxinitim ) $maxtim = min($maxwppatim, $maxinitim);
	elseif ( $maxwppatim ) $maxtim = $maxwppatim;
	elseif ( $maxinitim ) $maxtim = $maxinitim;
	else return false;

	wppa_dbg_msg('Maxtim = '.$maxtim.', elapsed = '.$laptim, 'red');
	if ( ! $maxtim ) return false;	// No limit or no value
	if ( ( $maxtim - $laptim ) > '5' ) return false;
	if ( $count ) {
		if ( is_admin() ) {
			if ( wppa_switch( 'auto_continue') ) {
				wppa_warning_message(sprintf(__('Time out after processing %s items.', 'wp-photo-album-plus'), $count));
			}
			else {
				wppa_error_message(sprintf(__('Time out after processing %s items. Please restart this operation', 'wp-photo-album-plus'), $count));
			}
		}
		else {
			wppa_alert(sprintf(__('Time out after processing %s items. Please restart this operation', 'wp-photo-album-plus'), $count));
		}
	}
	return true;
}


// Update photo modified timestamp
function wppa_update_modified($photo) {
global $wpdb;
	$wpdb->query($wpdb->prepare("UPDATE `".WPPA_PHOTOS."` SET `modified` = %s WHERE `id` = %s", time(), $photo));
}

function wppa_nl_to_txt($text) {
	return str_replace("\n", "\\n", $text);
}
function wppa_txt_to_nl($text) {
	return str_replace('\n', "\n", $text);
}

// Check query arg on tags
function wppa_vfy_arg($arg, $txt = false) {
	if ( isset($_REQUEST[$arg]) ) {
		if ( $txt ) {	// Text is allowed, but without tags
			$reason = ( defined('WP_DEBUG') && WP_DEBUG ) ? ': '.$arg.' contains tags.' : '';
			if ( $_REQUEST[$arg] != strip_tags($_REQUEST[$arg]) ) wp_die('Security check failue'.$reason);
		}
		else {
			$reason = ( defined('WP_DEBUG') && WP_DEBUG ) ? ': '.$arg.' is not numeric.' : '';
			$value = $_REQUEST[$arg];
			if ( $arg == 'photo-id' && strlen($value) == 12 ) {
				$value = wppa_decrypt_photo( $value );
			}
			if ( ! is_numeric($value) ) wp_die('Security check failue'.$reason);
		}
	}
}

// Strip tags with content
function wppa_strip_tags($text, $key = '') {

	if ($key == 'all') {
		$text = preg_replace(	array	(	'@<a[^>]*?>.*?</a>@siu',				// unescaped <a> tag
											'@&lt;a[^>]*?&gt;.*?&lt;/a&gt;@siu',	// escaped <a> tag
											'@<table[^>]*?>.*?</table>@siu',
											'@<style[^>]*?>.*?</style>@siu',
											'@<div[^>]*?>.*?</div>@siu'
										),
								array	( ' ', ' ', ' ', ' ', ' '
										),
								$text );
		$text = str_replace(array('<br/>', '<br />'), ' ', $text);
		$text = strip_tags($text);
	}
	elseif ( $key == 'script' ) {
		$text = preg_replace('@<script[^>]*?>.*?</script>@siu', ' ', $text );
	}
	elseif ( $key == 'div' ) {
		$text = preg_replace('@<div[^>]*?>.*?</div>@siu', ' ', $text );
	}
	elseif ( $key == 'script&style' || $key == 'style&script' ) {
		$text = preg_replace(	array	(	'@<script[^>]*?>.*?</script>@siu',
											'@<style[^>]*?>.*?</style>@siu'
										),
								array	( ' ', ' '
										),
								$text );
	}
	else {
		$text = preg_replace(	array	(	'@<a[^>]*?>.*?</a>@siu',				// unescaped <a> tag
											'@&lt;a[^>]*?&gt;.*?&lt;/a&gt;@siu'		// escaped <a> tag
										),
								array	( ' ', ' '
										),
								$text );
	}
	return trim($text);
}

// set last album
function wppa_set_last_album( $id = '' ) {

	if ( wppa_is_int( $id ) ) {
		update_option( 'wppa_last_album_used-' . wppa_get_user( 'login' ), $id );
	}
}

// get last album
function wppa_get_last_album() {

	$album = get_option( 'wppa_last_album_used-' . wppa_get_user( 'login' ), '0' );
	if ( ! wppa_album_exists( $album ) ) {
		$album = false;
	}
    return $album;
}

// Combine margin or padding style
function wppa_combine_style($type, $top = '0', $left = '0', $right = '0', $bottom = '0') {
// echo $top.' '.$left.' '.$right.' '.$bottom.'<br />';
	$result = $type.':';			// Either 'margin:' or 'padding:'
	if ( $left == $right ) {
		if ( $top == $bottom ) {
			if ( $top == $left ) {	// All the same: one size fits all
				$result .= $top;
				if ( is_numeric($top) && $top > '0' ) $result .= 'px';
			}
			else {					// Top=Bot and Lft=Rht: two sizes
				$result .= $top;
				if ( is_numeric($top) && $top > '0' ) $result .= 'px '; else $result .= ' ';
				$result .= $left;
				if ( is_numeric($left) && $left > '0' ) $result .= 'px';
			}
		}
		else {						// Top, Lft=Rht, Bot: 3 sizes
			$result .= $top;
			if ( is_numeric($top) && $top > '0' ) $result .= 'px '; else $result .= ' ';
			$result .= $left;
			if ( is_numeric($left) && $left > '0' ) $result .= 'px '; else $result .= ' ';
			$result .= $bottom;
			if ( is_numeric($bottom) && $bottom > '0' ) $result .= 'px';
		}
	}
	else {							// Top, Rht, Bot, Lft: 4 sizes
		$result .= $top;
		if ( is_numeric($top) && $top > '0' ) $result .= 'px '; else $result .= ' ';
		$result .= $right;
		if ( is_numeric($right) && $right > '0' ) $result .= 'px '; else $result .= ' ';
		$result .= $bottom;
		if ( is_numeric($bottom) && $bottom > '0' ) $result .= 'px '; else $result .= ' ';
		$result .= $left;
		if ( is_numeric($left) && $left > '0' ) $result .= 'px';
	}
	$result .= ';';
	return $result;
}

// A temp routine to fix an old bug
function wppa_fix_source_extensions() {
global $wpdb;

	$start_time = time();
	$end = $start_time + '15';
	$count = '0';
	$start = get_option('wppa_sourcefile_fix_start', '0');
	if ( $start == '-1' ) return; // Done!

	$photos = $wpdb->get_results( 	"SELECT `id`, `album`, `name`, `filename`" .
										" FROM `".WPPA_PHOTOS."`" .
										" WHERE `filename` <> ''  AND `filename` <> `name` AND `id` > " . $start .
										" ORDER BY `id`", ARRAY_A
								);
	if ( $photos ) {
		foreach ( $photos as $data ) {
			$faulty_sourcefile_name = wppa_opt( 'source_dir' ).'/album-'.$data['album'].'/'.preg_replace('/\.[^.]*$/', '', $data['filename']);
			if ( is_file($faulty_sourcefile_name) ) {
				$proper_sourcefile_name = wppa_opt( 'source_dir' ).'/album-'.$data['album'].'/'.$data['filename'];
				if ( is_file($proper_sourcefile_name) ) {
					unlink($faulty_sourcefile_name);
				}
				else {
					rename($faulty_sourcefile_name, $proper_sourcefile_name);
				}
				$count++;
			}
			if ( time() > $end ) {
				wppa_ok_message( 'Fixed ' . $count . ' faulty sourcefile names.' .
									' Last was ' . $data['id'] . '.' .
									' Not finished yet. I will continue fixing next time you enter this page. Sorry for the inconvenience.'
								);

				update_option('wppa_sourcefile_fix_start', $data['id']);
				return;
			}
		}
	}
	echo $count.' source file extensions repaired';
	update_option('wppa_sourcefile_fix_start', '-1');
}

// Delete a photo and all its attrs by id
function wppa_delete_photo( $photo ) {
global $wppa_supported_audio_extensions;
global $wppa_supported_video_extensions;
global $wpdb;

	// Sanitize arg
	$photo = strval( intval( $photo ) );
	$photoinfo = $wpdb->get_row($wpdb->prepare('SELECT * FROM `'.WPPA_PHOTOS.'` WHERE `id` = %s', $photo), ARRAY_A);

	// If still in use, refuse deletion
	$in_use = $wpdb->get_row( "SELECT `ID`, `post_title` FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%photo=\"$photo\"%' AND `post_status` = 'publish' LIMIT 1", ARRAY_A );

	if ( is_array( $in_use ) ) {
		if ( defined( 'DOING_AJAX' ) ) {
			echo
			'ER||0||' .
			'<span style="color:#ff0000;" >' .
				__( 'Could not delete photo', 'wp-photo-album-plus' ) .
			'</span>||' .
			__( 'Photo is still in use in post/page', 'wp-photo-album-plus' ) .
				' ' .
			$in_use['post_title'] .
			' (' . $in_use['ID'] . ')';
			wppa_exit();
		}
		else {
			wppa_error_message( __( 'Photo is still in use in post/page', 'wp-photo-album-plus' ) . ' ' . $in_use['post_title'] . ' (' . $in_use['ID'] . ')' );
			return false;
		}
	}

	// Get album
	$album = $photoinfo['album'];

	// Really delete only as cron job
	if ( ! wppa_is_cron() ) {
		if ( $album > '0' ) {
			$newalb = - ( $album + '9' );
			wppa_update_photo( array( 'id' => $photo, 'album' => $newalb, 'modified' => time() ) );
			wppa_mark_treecounts( $album );
			wppa_schedule_cleanup( 'now' );
		}
		return;
	}

	// Restore orig album #
	$album = - ( $album + '9' );

	// Delete multimedia files
	if ( wppa_is_multi( $photo ) ) {
		$mmfile = wppa_strip_ext( wppa_get_photo_path( $photo, false ) );
		$allsup = array_merge( $wppa_supported_audio_extensions, $wppa_supported_video_extensions );
		foreach( $allsup as $mmext ) {
			if ( is_file( $mmfile.'.'.$mmext ) ) {
				@ unlink( $mmfile.'.'.$mmext );
			}
		}
	}

	// Delete sourcefile
	wppa_delete_source( $photoinfo['filename'], $album);

	// Delete fullsize image
	$file = wppa_get_photo_path( $photo );
	if ( is_file( $file ) ) unlink( $file );

	// Delete thumbnail image
	$file = wppa_get_thumb_path( $photo );
	if ( is_file( $file ) ) unlink( $file );

	// Delete index
	wppa_index_remove('photo', $photo);

	// Delete db entries
	$wpdb->query($wpdb->prepare('DELETE FROM `'.WPPA_PHOTOS.'` WHERE `id` = %s LIMIT 1', $photo));
	$wpdb->query($wpdb->prepare('DELETE FROM `'.WPPA_RATING.'` WHERE `photo` = %s', $photo));
	$wpdb->query($wpdb->prepare('DELETE FROM `'.WPPA_COMMENTS.'` WHERE `photo` = %s', $photo));
	$wpdb->query($wpdb->prepare('DELETE FROM `'.WPPA_IPTC.'` WHERE `photo` = %s', $photo));
	$wpdb->query($wpdb->prepare('DELETE FROM `'.WPPA_EXIF.'` WHERE `photo` = %s', $photo));
	wppa_invalidate_treecounts($album);
	wppa_flush_upldr_cache('photoid', $photo);

	// Delete from cloud
	if ( wppa_cdn( 'admin' ) == 'cloudinary' ) {
		wppa_delete_from_cloudinary( $photo );
	}

}

function wppa_microtime($txt = '') {
static $old;

	$new = microtime(true);
	if ( $old ) {
		$delta = $new - $old;
		$old = $new;
		$msg = sprintf('%s took %7.3f s.', $txt, $delta);
		wppa_dbg_msg($msg, 'green', true);
	}
	else $old = $new;
}

function wppa_sanitize_cats($value) {
	return wppa_sanitize_tags($value);
}
function wppa_sanitize_tags($value, $keepsemi = false, $keephash = false ) {

	// Sanitize
	$value = sanitize_text_field( $value );
//	$value = strip_tags( $value );					// Security

	$value = str_replace( 	array( 					// Remove funny chars
									'"',
									'\'',
									'\\',
									'@',
									'?',
									'|',
								 ),
							'',
							$value
						);
	if ( ! $keephash ) {
		$value = str_replace( '#', '', $value );
	}

	$value = stripslashes($value);					// ...

	// Find separator
	$sep = ',';										// Default seperator
	if ( $keepsemi ) {								// ';' allowed
		if ( strpos($value, ';') !== false ) {		// and found at least one ';'
			$value = str_replace(',', ';', $value);	// convert all separators to ';'
			$sep = ';';
		}											// ... a mix is not permitted
	}
	else {
		$value = str_replace(';', ',', $value);		// Convert all seps to default separator ','
	}

	$temp = explode( $sep, $value );
	if ( is_array($temp) ) {

		// Trim
		foreach ( array_keys( $temp ) as $idx ) {
			$temp[$idx] = trim( $temp[$idx] );
		}

		// Capitalize single words within tags
		if ( wppa_switch( 'capitalize_tags' ) ) {
			foreach ( array_keys($temp) as $idx ) {
				if ( strlen( $temp[$idx] ) > '1' ) {
					$words = explode( ' ', $temp[$idx] );
					foreach( array_keys($words) as $i ) {
						$words[$i] = strtoupper(substr($words[$i], 0, 1)).strtolower(substr($words[$i], 1));
					}
					$temp[$idx] = implode(' ', $words);
				}
			}
		}

		// Capitalize exif tags
		foreach ( array_keys( $temp ) as $idx ) {
			if ( substr( $temp[$idx], 0, 2 ) == 'E#' ) {
				$temp[$idx] = strtoupper( $temp[$idx] );
			}
		}

		// Capitalize GPX and HD tags
		foreach ( array_keys( $temp ) as $idx ) {
			if ( in_array( $temp[$idx], array( 'Gpx', 'Hd' ) ) ) {
				$temp[$idx] = strtoupper( $temp[$idx] );
			}
		}

		// Sort
		asort( $temp );

		// Remove dups and recombine
		$value = '';
		$first = true;
		$previdx = '';
		foreach ( array_keys($temp) as $idx ) {
			if ( strlen( $temp[$idx] ) > '1' ) {

				// Remove duplicates
				if ( $temp[$idx] ) {
					if ( $first ) {
						$first = false;
						$value .= $temp[$idx];
						$previdx = $idx;
					}
					elseif ( $temp[$idx] !=  $temp[$previdx] ) {
						$value .= $sep.$temp[$idx];
						$previdx = $idx;
					}
				}
			}
		}
	}

	if ( $sep == ',' && $value != '' ) {
		$value = $sep . $value . $sep;
	}
	return $value;
}

// Does the same as wppa_index_string_to_array() but with format validation and error reporting
function wppa_series_to_array($xtxt) {
	if ( is_array( $xtxt ) ) return false;
	$txt = str_replace(' ', '', $xtxt);					// Remove spaces
	if ( strpos($txt, '.') === false ) return false;	// Not an enum/series, the only legal way to return false
	if ( strpos($txt, '...') !== false ) {
		wppa_stx_err('Max 2 successive dots allowed. '.$txt);
		return false;
	}
	if ( substr($txt, 0, 1) == '.' ) {
		wppa_stx_err('Missing starting number. '.$txt);
		return false;
	}
	if ( substr($txt, -1) == '.' ) {
		wppa_stx_err('Missing ending number. '.$txt);
		return false;
	}
	$t = str_replace(array('.','0','1','2','3','4','5','6','7','8','9'), '',$txt);
	if ( $t ) {
		wppa_stx_err('Illegal character(s): "'.$t.'" found. '.$txt);
		return false;
	}

	// Trim leading '0.'
	if ( substr( $txt, 0, 2 ) == '0.' ) {
		$txt = substr( $txt, 2 );
	}

	$temp = explode('.', $txt);
	$tempcopy = $temp;

	foreach ( array_keys($temp) as $i ) {
		if ( ! $temp[$i] ) { 							// found a '..'
			if ( $temp[$i-'1'] >= $temp[$i+'1'] ) {
				wppa_stx_err('Start > end. '.$txt);
				return false;
			}
			for ( $j=$temp[$i-'1']+'1'; $j<$temp[$i+'1']; $j++ ) {
				$tempcopy[] = $j;
			}
		}
		else {
			if ( ! is_numeric($temp[$i] ) ) {
				wppa_stx_err('A enum or range token must be a number. '.$txt);
				return false;
			}
		}
	}
	$result = $tempcopy;
	foreach ( array_keys($result) as $i ) {
		if ( ! $result[$i] ) unset($result[$i]);
	}
	return $result;
}
function wppa_stx_err($msg) {
	echo 'Syntax error in album specification. '.$msg;
}


function wppa_get_og_desc( $id, $short = false ) {

	if ( $short ) {
		$result = 	strip_shortcodes( wppa_strip_tags( wppa_html( wppa_get_photo_desc( $id ) ), 'all' ) );
		if ( ! $result ) {
			$result = str_replace( '&amp;', __( 'and' , 'wp-photo-album-plus'), get_bloginfo( 'name' ) );
		}
	}
	else {
		$result = 	sprintf( __('See this image on %s', 'wp-photo-album-plus'), str_replace( '&amp;', __( 'and' , 'wp-photo-album-plus'), get_bloginfo( 'name' ) ) ) .
					': ' .
					strip_shortcodes( wppa_strip_tags( wppa_html( wppa_get_photo_desc( $id ) ), 'all' ) );
	}

	$result = 	apply_filters( 'wppa_get_og_desc', $result );

	return $result;
}

// There is no php routine to test if a string var is an integer, like '3': yes, and '3.7' and '3..7': no.
// is_numeric('3.7') returns true
// intval('3..7') == '3..7' returns true
// is_int('3') returns false
// so we make it ourselves
function wppa_is_int( $var ) {
	if ( is_array( $var ) ) {
		return false;
	}
	return ( strval(intval($var)) == strval($var) );
}

// return true if $var only contains digits and points
function wppa_is_enum( $var ) {
	return '' === str_replace( array( '0','1','2','3','4','5','6','7','8','9','.' ), '', $var );
}

function wppa_log( $xtype, $msg, $trace = false, $listuri = false ) {
global $wppa_session;
global $wppa_log_file;

	// Do not log during plugin activation or update
	if ( strpos( $_SERVER['REQUEST_URI'], '/wp-admin/plugins.php' ) !== false ) {
		return;
	}
	if ( strpos( $_SERVER['REQUEST_URI'], '/wp-admin/update-core.php' ) !== false ) {
		return;
	}

	// Sanitize type
	$t = strtolower( substr( $xtype, 0, 1 ) );
	$u = strtolower( substr( $xtype, 1, 1 ) );
	switch ( $t ) {
		case 'a':
			$type = '{span style="color:blue;" }Ajax{/span}';
			break;
		case 'c':
			switch ( $u ) {
				case 'r':
					$type = '{span style="color:blue;" }Cron{/span}';
					if ( ! wppa_switch( 'log_cron' ) ) {
						return;
					}
					break;
				case 'o':
					$type = '{span style="color:cyan;" }Com{/span}';
					if ( ! wppa_switch( 'log_comments' ) ) {
						return;
					}
					break;
			}
			break;
		case 'd':
			$type = '{span style="color:orange;" }Dbg{/span}';
			break;
		case 'e':
			$type = '{span style="color:red;" }Err{/span}';
			break;
		case 'f':
			switch ( $u ) {
				case 's':
					$type = '{span style="color:blue;" }Fso{/span}';
					if ( ! wppa_switch( 'log_fso' ) ) {
						return;
					}
					break;
				case 'i':
					$type = 'Fix';
					break;
			}
			break;
		case 'o':
			$type = 'Obs';
			break;
		case 'u':
			$type = 'Upl';
			break;
		case 'w':
			$type = '{span style="color:yellow;" }War{/span}';
			break;
		default:
			$type = 'Misc';
	}

	// Log debug messages only if WP_DEBUG is defined as true
	if ( $type == 'Dbg' ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
	}

	// See if max size exceeded
	if ( is_file( $wppa_log_file ) ) {
		$filesize = filesize( $wppa_log_file );
		if ( $filesize > 1024000 ) {

			// File > 1000kB, shorten it
			$file = fopen( $wppa_log_file, 'rb' );
			if ( $file ) {
				$buffer = @ fread( $file, $filesize );
				$buffer = substr( $buffer, $filesize - 900*1024 );	// Take ending 900 kB
				fclose( $file );
				$file = fopen( $wppa_log_file, 'wb' );
				@ fwrite( $file, $buffer );
				@ fclose( $file );
			}
		}
	}

	// Open for append
	if ( ! $file = fopen( $wppa_log_file, 'ab' ) ) return;	// Unable to open log file

	// Write log message
	$msg = strip_tags( $msg );

	@ fwrite( $file, '{b}'.$type.'{/b}: on:'.wppa_local_date(get_option('date_format', "F j, Y,").' '.get_option('time_format', "g:i a"), time()).': '.wppa_get_user().': '.$msg."\n" );

	// Log current url and stacktrace 12 levels if trace requested
	if ( $trace || $type == 'Dbg' ) {
		@ fwrite( $file, '{b}url{/b}: '.$_SERVER['REQUEST_URI']."\n" );
	}
	if ( $trace ) {
		ob_start();
		debug_print_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 12 );
		$trace = ob_get_contents();
		ob_end_clean();
		@ fwrite( $file, $trace."\n" );
	}

	// Add uri history
	if ( $listuri ) {
		@ fwrite( $file, 'Uri history:'."\n" );
		if ( is_array( $wppa_session ) ) {
			foreach ( $wppa_session['uris'] as $uri ) {
				@ fwrite( $file, $uri . "\n" );
			}
			@ fwrite( $file, "\n\n" );
		}
	}

	// Done
	@ fclose( $file );
}

function wppa_is_landscape($img_attr) {
	return ($img_attr[0] > $img_attr[1]);
}

function wppa_get_the_id() {

	$id = '0';
	if ( wppa( 'ajax' ) ) {
		if ( wppa_get_get( 'page_id' ) ) $id = wppa_get_get( 'page_id' );
		elseif ( wppa_get_get( 'p' ) ) $id = wppa_get_get( 'p' );
		elseif ( wppa_get_get( 'fromp' ) ) $id = wppa_get_get( 'fromp' );
	}
	else {
		$id = get_the_ID();
	}
	return $id;
}


function wppa_get_artmonkey_size_a( $photo ) {
global $wpdb;

	$data = wppa_cache_thumb( $photo );
	if ( $data ) {
		if ( wppa_switch( 'artmonkey_use_source' ) ) {
			if ( is_file( wppa_get_source_path( $photo ) ) ) {
				$source = wppa_get_source_path( $photo );
			}
			else {
				$source = wppa_get_photo_path( $photo );
			}
		}
		else {
			$source = wppa_get_photo_path( $photo );
		}
		$imgattr = @ getimagesize( $source );
		if ( is_array( $imgattr ) ) {
			$fs = wppa_get_filesize( $source );
			$result = array( 'x' => $imgattr['0'], 'y' => $imgattr['1'], 's' => $fs );
			return $result;
		}
	}
	return false;
}

function wppa_get_filesize( $file ) {

	if ( is_file( $file ) ) {
		$fs = filesize( $file );

		if ( $fs > 1024*1024 ) {
			$fs = sprintf('%4.2f Mb', $fs/(1024*1024));
		}
		else {
			$fs = sprintf('%4.2f Kb', $fs/1024);
		}
		return $fs;
	}

	return false;
}


function wppa_get_the_landing_page( $slug, $title ) {

	$page = wppa_opt( $slug );
	if ( ! $page || ! wppa_page_exists( $page ) ) {
	$page = wppa_create_page( $title );
		wppa_update_option( 'wppa_' . $slug, $page );
		wppa_opt( $slug, $page );
	}
	return $page;
}

function wppa_get_the_auto_page( $photo ) {
global $wpdb;

	if ( ! $photo ) return '0';					// No photo id, no page
	if ( ! wppa_is_int( $photo ) ) return '0';	// $photo not numeric

	$thumb = wppa_cache_thumb( $photo );		// Get photo info

	// Page exists ?
	if ( wppa_page_exists( $thumb['page_id'] ) ) {
		return $thumb['page_id'];
	}

	// Create new page
	$page = wppa_create_page( $thumb['name'], '[wppa type="autopage"][/wppa]' );

	// Store with photo data
	$wpdb->query( $wpdb->prepare( "UPDATE `".WPPA_PHOTOS."` SET `page_id` = ".$page." WHERE `id` = %d", $photo ) );

	// Update cache
	$thumb['page_id'] = $page;

	return $page;
}

function wppa_remove_the_auto_page( $photo ) {

	if ( ! $photo ) return '0';					// No photo id, no page
	if ( ! wppa_is_int( $photo ) ) return '0';	// $photo not numeric

	$thumb = wppa_cache_thumb( $photo );		// Get photo info

	// Page exists ?
	if ( wppa_page_exists( $thumb['page_id'] ) ) {
		wp_delete_post( $thumb['page_id'], true );
		wppa_update_photo( array( 'id' => $photo, 'page_id' => '0' ) );
	}
}

function wppa_create_page( $title, $shortcode = '[wppa type="landing"][/wppa]' ) {

	$my_page = array(
				'post_title'    => $title,
				'post_content'  => $shortcode,
				'post_status'   => 'publish',
				'post_type'	  	=> 'page'
			);

	$page = wp_insert_post( $my_page );
	return $page;
}

// Check if a published page exists
function wppa_page_exists( $id ) {
global $wpdb;
static $pages_exist;

	// Check on valid input
	if ( ! $id ) return false;

	// Already found existing or non existing?
	if ( isset( $pages_exist[$id] ) ) {
		return $pages_exist[$id];
	}

	// Do a query
	$iret = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `" .
											$wpdb->posts . "` " .
											"WHERE `post_type` = 'page' " .
											"AND `post_status` = 'publish' " .
											"AND `ID` = %s", $id ) );

	// Save result
	$pages_exist[$id] = ( $iret > 0 );

	return $pages_exist[$id];
}

function wppa_get_photo_owner( $id ) {

	$thumb = wppa_cache_thumb( $id );
	return $thumb['owner'];
}

function wppa_cdn( $side ) {

	// What did we specify in the settings page?
	$cdn = wppa_opt( 'cdn_service' );

	// Check for fully configured and active
	switch ( $cdn ) {
		case 'cloudinary':
		case 'cloudinarymaintenance':
			if ( wppa_opt( 'cdn_cloud_name' ) && wppa_opt( 'cdn_api_key' ) && wppa_opt( 'cdn_api_secret' ) ) {
				if ( $side == 'admin' ) {		// Admin: always return cloudinary
					$cdn = 'cloudinary';
				}
				elseif ( $side == 'front' ) {	// Front: NOT if in maintenance
					if ( $cdn == 'cloudinarymaintenance' ) {
						$cdn = false;
					}
				}
				else {
					wppa_dbg_msg( 'dbg', 'Wrong arg:'.$side.' in wppa_cdn()', 'red', 'force' );
					$cdn = false;
				}
			}
			else {
				wppa_dbg_msg( 'dbg', 'Incomplete configuration of Cloudinary', 'red', 'force' );
				$cdn = false;	// Incomplete configuration
			}
			break;

		default:
			$cdn = false;

	}

	return $cdn;
}

function wppa_get_source_path( $id ) {
global $blog_id;
global $wppa_supported_photo_extensions;

	// Source files can have uppercase extensions.
	$temp = array();
	foreach( $wppa_supported_photo_extensions as $ext ) {
		$temp[] = strtoupper( $ext );
	}
	$supext = array_merge( $wppa_supported_photo_extensions, $temp );

	$thumb = wppa_cache_thumb( $id );
	$album = $thumb['album'];

	// Trashed?
	if ( $album < '0' ) {
		$album = - ( $album + '9' );
	}

	$multi = is_multisite();
	if ( $multi && ! WPPA_MULTISITE_GLOBAL ) {
		$blog = '/blog-'.$blog_id;
	}
	else {
		$blog = '';
	}
	$source_path = wppa_opt( 'source_dir' ).$blog.'/album-'.$album.'/'.$thumb['filename'];
	if ( wppa_is_multi( $id ) ) {
		$path = wppa_strip_ext( $source_path );
		foreach ( $supext as $ext ) {
			$source = $path . '.' . $ext;
			if ( is_file( $source ) ) {
				return $source;
			}
		}
	}

	return $source_path;
}

// Get url of photo with highest available resolution.
// Not for display ( need not to download fast ) but for external services like Fotomoto
function wppa_get_hires_url( $id ) {

	// video? return the poster url
	if ( wppa_is_video( $id ) || wppa_has_audio( $id ) ) {
		$url = wppa_get_photo_url( $id );
		$temp = explode( '?', $url );
		$url = $temp['0'];
		return $url;
	}

	// Try CDN
	if ( wppa_cdn( 'front' ) && ! wppa_too_old_for_cloud( $id ) ) {
		switch ( wppa_cdn( 'front' ) ) {
			case 'cloudinary':
				$url = wppa_get_cloudinary_url( $id );
				break;
			default:
				$url = '';
		}
		if ( $url ) return $url;
	}

	// Try the orientation corrected source url
	$source_path = wppa_get_o1_source_path( $id );
	if ( is_file( $source_path ) ) {

		// The source file is only http reacheable when it is down from wp-content
		if ( strpos( $source_path, WPPA_CONTENT_PATH ) !== false ) {
			return str_replace( WPPA_CONTENT_PATH, WPPA_CONTENT_URL, $source_path );
		}
	}

	// Try the source url
	$source_path = wppa_get_source_path( $id );
	if ( is_file( $source_path ) ) {

		// The source file is only http reacheable when it is down from ABSPATH
		if ( strpos( $source_path, WPPA_CONTENT_PATH ) !== false ) {
			return str_replace( WPPA_CONTENT_PATH, WPPA_CONTENT_URL, $source_path );
		}
	}

	// The medium res url
	$hires_url = wppa_get_photo_url( $id );
	$temp = explode( '?', $hires_url );
	return $temp['0'];
}
function wppa_get_lores_url( $id ) {
	$lores_url = wppa_get_photo_url( $id );
	$temp = explode( '?', $lores_url );
	$lores_url = $temp['0'];
	return $lores_url;
}
function wppa_get_tnres_url( $id ) {
	$tnres_url = wppa_get_thumb_url( $id );
	$temp = explode( '?', $tnres_url );
	$tnres_url = $temp['0'];
	return $tnres_url;
}

// Get permalink to photo source file
function wppa_get_source_pl( $id ) {

	// Init
	$result = '';

	// If feature is enabled
	if ( wppa_opt( 'pl_dirname' ) ) {
		$source_path = wppa_fix_poster_ext( wppa_get_source_path( $id ), $id );
		if ( is_file( $source_path ) ) {
			$result = 	content_url() . '/' . 						// http://www.mysite.com/wp-content/
						wppa_opt( 'pl_dirname' ) . '/' .			// wppa-pl/
						wppa_get_album_name_for_pl( wppa_get_photo_item( $id, 'album' ) ) .
						'/' . basename( $source_path );					// My-Photo.jpg
		}
	}

	return $result;
}

function wppa_get_source_dir() {
global $blog_id;

	$multi = is_multisite();
//	$multi = true;	// debug
	if ( $multi && ! WPPA_MULTISITE_GLOBAL ) {
		$blog = '/blog-'.$blog_id;
	}
	else {
		$blog = '';
	}
	$source_dir = wppa_opt( 'source_dir' ).$blog;

	return $source_dir;
}

function wppa_get_source_album_dir( $alb ) {
global $blog_id;

	$multi = is_multisite();
//	$multi = true;	// debug
	if ( $multi && ! WPPA_MULTISITE_GLOBAL ) {
		$blog = '/blog-'.$blog_id;
	}
	else {
		$blog = '';
	}
	$source_album_dir = wppa_opt( 'source_dir' ).$blog.'/album-'.$alb;

	return $source_album_dir;
}


function wppa_set_default_name( $id, $filename_raw = '' ) {
global $wpdb;

	if ( ! wppa_is_int( $id ) ) return;
	$thumb = wppa_cache_thumb( $id );

	$method 	= wppa_opt( 'newphoto_name_method' );
	$name 		= $thumb['filename']; 	// The default default
	$filename 	= $thumb['filename'];

	switch ( $method ) {
		case 'none':
			$name = '';
			break;
		case 'filename':
			if ( $filename_raw ) {
				$name = wppa_sanitize_photo_name( $filename_raw );
			}
			break;
		case 'noext':
			if ( $filename_raw ) {
				$name = wppa_sanitize_photo_name( $filename_raw );
			}
			$name = preg_replace('/\.[^.]*$/', '', $name);
			break;
		case '2#005':
			$tag = '2#005';
			$name = $wpdb->get_var( $wpdb->prepare( "SELECT `description` FROM `".WPPA_IPTC."` WHERE `photo` = %s AND `tag` = %s", $id, $tag ) );
			break;
		case '2#120':
			$tag = '2#120';
			$name = $wpdb->get_var( $wpdb->prepare( "SELECT `description` FROM `".WPPA_IPTC."` WHERE `photo` = %s AND `tag` = %s", $id, $tag ) );
			break;
		case 'Photo w#id':
			$name = __( 'Photo w#id', 'wp-photo-album-plus' );
			break;
	}
	if ( ( $name && $name != $filename ) || $method == 'none' ) {	// Update name
		$wpdb->query( $wpdb->prepare( "UPDATE `".WPPA_PHOTOS."` SET `name` = %s WHERE `id` = %s", $name, $id ) );
		wppa_cache_thumb( 'invalidate', $id );	// Invalidate cache
	}
	if ( ! wppa_switch( 'save_iptc') ) { 	// He doesn't want to keep the iptc data, so...
		$wpdb->query($wpdb->prepare( "DELETE FROM `".WPPA_IPTC."` WHERE `photo` = %s", $id ) );
	}

	// In case owner must be set to name.
	wppa_set_owner_to_name( $id );
}

function wppa_set_default_tags( $id ) {
global $wpdb;

	$thumb 	= wppa_cache_thumb( $id );
	$album 	= wppa_cache_album( $thumb['album'] );
	$tags 	= wppa_sanitize_tags( str_replace( array( '\'', '"'), ',', wppa_filter_iptc( wppa_filter_exif( $album['default_tags'], $id ), $id ) ) );

	if ( $tags ) {
		wppa_update_photo( array( 'id' => $id, 'tags' => $tags ) );
		wppa_clear_taglist();
		wppa_cache_thumb( 'invalidate', $id );
	}
}

function wppa_test_for_medal( $id ) {
global $wpdb;

	$thumb = wppa_cache_thumb( $id );
	$status = $thumb['status'];

	if ( wppa_opt( 'medal_bronze_when' ) || wppa_opt( 'medal_silver_when' ) || wppa_opt( 'medal_gold_when' ) ) {
		$max_score = wppa_opt( 'rating_max' );

		$max_ratings = $wpdb->get_var( $wpdb->prepare( 	"SELECT COUNT(*) FROM `".WPPA_RATING."` " .
														"WHERE `photo` = %s AND `value` = %s AND `status` = %s", $id, $max_score, 'publish'
													)
									);

		if ( $max_ratings >= wppa_opt( 'medal_gold_when' ) ) $status = 'gold';
		elseif ( $max_ratings >= wppa_opt( 'medal_silver_when' ) ) $status = 'silver';
		elseif ( $max_ratings >= wppa_opt( 'medal_bronze_when' ) ) $status = 'bronze';
	}

	if ( $status != $thumb['status'] ) {
		$thumb['status'] = $status;	// Update cache
		$wpdb->query( $wpdb->prepare( "UPDATE `".WPPA_PHOTOS."` SET `status` = %s WHERE `id` = %s", $status, $id ) );
	}
}

function wppa_get_the_bestof( $count, $period, $sortby, $what ) {
global $wpdb;

	// Phase 1, find the period we are talking about
	// find $start and $end
	switch ( $period ) {
		case 'lastweek':
			$start 	= wppa_get_timestamp( 'lastweekstart' );
			$end   	= wppa_get_timestamp( 'lastweekend' );
			break;
		case 'thisweek':
			$start 	= wppa_get_timestamp( 'thisweekstart' );
			$end   	= wppa_get_timestamp( 'thisweekend' );
			break;
		case 'lastmonth':
			$start 	= wppa_get_timestamp( 'lastmonthstart' );
			$end 	= wppa_get_timestamp( 'lastmonthend' );
			break;
		case 'thismonth':
			$start 	= wppa_get_timestamp( 'thismonthstart' );
			$end 	= wppa_get_timestamp( 'thismonthend' );
			break;
		case 'lastyear':
			$start 	= wppa_get_timestamp( 'lastyearstart' );
			$end 	= wppa_get_timestamp( 'lastyearend' );
			break;
		case 'thisyear':
			$start 	= wppa_get_timestamp( 'thisyearstart' );
			$end 	= wppa_get_timestamp( 'thisyearend' );
			break;
		default:
			return 'Unimplemented period: '.$period;
	}

	// Phase 2, get the ratings of the period
	// find $ratings, ordered by photo id
	$ratings 	= $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPPA_RATING."` WHERE `timestamp` >= %s AND `timestamp` < %s ORDER BY `photo`", $start, $end ), ARRAY_A );

	// Phase 3, set up an array with data we need
	// There are two methods: photo oriented and owner oriented, depending on

	// Each element reflects a photo ( key = photo id ) and is an array with items: maxratings, meanrating, ratings, totvalue.
	$ratmax	= wppa_opt( 'rating_max' );
	$data 	= array();
	foreach ( $ratings as $rating ) {
		$key = $rating['photo'];
		if ( ! isset( $data[$key] ) ) {
			$data[$key] = array();
			$data[$key]['ratingcount'] 		= '1';
			$data[$key]['maxratingcount'] 	= $rating['value'] == $ratmax ? '1' : '0';
			$data[$key]['totvalue'] 		= $rating['value'];
		}
		else {
			$data[$key]['ratingcount'] 		+= '1';
			$data[$key]['maxratingcount'] 	+= $rating['value'] == $ratmax ? '1' : '0';
			$data[$key]['totvalue'] 		+= $rating['value'];
		}
	}
	foreach ( array_keys( $data ) as $key ) {
		$thumb = wppa_cache_thumb( $key );
		$data[$key]['meanrating'] = $data[$key]['totvalue'] / $data[$key]['ratingcount'];
		$user = wppa_get_user_by( 'login', $thumb['owner'] );
		if ( $user ) {
			$data[$key]['user'] = $user->display_name;
		}
		else { // user deleted
			$data[$key]['user'] = $thumb['owner'];
		}
		$data[$key]['owner'] = $thumb['owner'];
	}

	// Now we split into search for photos and search for owners

	if ( $what == 'photo' ) {

		// Pase 4, sort to the required sequence
		$data = wppa_array_sort( $data, $sortby, SORT_DESC );

	}
	else { 	// $what == 'owner'

		// Phase 4, combine all photos of the same owner
		wppa_array_sort( $data, 'user' );
		$temp = $data;
		$data = array();
		foreach ( array_keys( $temp ) as $key ) {
			if ( ! isset( $data[$temp[$key]['user']] ) ) {
				$data[$temp[$key]['user']]['photos'] 			= '1';
				$data[$temp[$key]['user']]['ratingcount'] 		= $temp[$key]['ratingcount'];
				$data[$temp[$key]['user']]['maxratingcount'] 	= $temp[$key]['maxratingcount'];
				$data[$temp[$key]['user']]['totvalue'] 			= $temp[$key]['totvalue'];
				$data[$temp[$key]['user']]['owner'] 			= $temp[$key]['owner'];
			}
			else {
				$data[$temp[$key]['user']]['photos'] 			+= '1';
				$data[$temp[$key]['user']]['ratingcount'] 		+= $temp[$key]['ratingcount'];
				$data[$temp[$key]['user']]['maxratingcount'] 	+= $temp[$key]['maxratingcount'];
				$data[$temp[$key]['user']]['totvalue'] 			+= $temp[$key]['totvalue'];
			}
		}
		foreach ( array_keys( $data ) as $key ) {
			$data[$key]['meanrating'] = $data[$key]['totvalue'] / $data[$key]['ratingcount'];
		}
		$data = wppa_array_sort( $data, $sortby, SORT_DESC );
	}

	// Phase 5, truncate to the desired length
	$c = '0';
	foreach ( array_keys( $data ) as $key ) {
		$c += '1';
		if ( $c > $count ) unset ( $data[$key] );
	}

	// Phase 6, return the result
	if ( count( $data ) ) {
		return $data;
	}
	else {
		return 	__('There are no ratings between', 'wp-photo-album-plus') .
				'<br />' .
				wppa_local_date( 'F j, Y, H:i s', $start ) .
				' ' . __('and', 'wp-photo-album-plus') .
				'<br />' .
				wppa_local_date( 'F j, Y, H:i s', $end ) .
				'.';
	}
}

// To check on possible duplicate
function wppa_file_is_in_album( $filename, $alb ) {
global $wpdb;

	if ( ! $filename ) return false;	// Copy/move very old photo, before filnametracking
	$photo_id = $wpdb->get_var ( $wpdb->prepare ( 	"SELECT `id` FROM `".WPPA_PHOTOS."` " .
													"WHERE ( `filename` = %s OR `filename` = %s ) AND `album` = %s LIMIT 1",
														wppa_sanitize_file_name( $filename ), $filename, $alb
												)
								);
	return $photo_id;
}

// Retrieve the number of child albums ( if any )
function wppa_has_children( $alb ) {
global $wpdb;
static $childcounts;

	// See if done this alb earlier
	if ( isset( $childcounts[$alb] ) ) {
		$result = $childcounts[$alb];
	}
	else {
		$result = $wpdb->get_var( $wpdb->prepare( 	"SELECT COUNT(*) " .
													"FROM `" . WPPA_ALBUMS . "` " .
													"WHERE `a_parent` = %s", $alb) );

		// Save result
		$childcounts[$alb] = $result;
	}

	return $result;
}

// Get an enumeration of all the (grand)children of some album spec.
// Album spec may be a number or an enumeration
function wppa_alb_to_enum_children( $xalb ) {
	if ( strpos( $xalb, '.' ) !== false ) {
		$albums = explode( '.', $xalb );
	}
	else {
		$albums = array( $xalb );
	}
	$result = '';
	foreach( $albums as $alb ) {
		$result .= _wppa_alb_to_enum_children( $alb );
		$result = trim( $result, '.' ).'.';
	}
	$result = trim( $result, '.' );
//	$result = wppa_compress_enum( $result );
	return $result;
}

function _wppa_alb_to_enum_children( $alb ) {
global $wpdb;
static $child_cache;

	// Done this one before?
	if ( isset( $child_cache[$alb] ) ) {
		return $child_cache[$alb];
	}

	// Get the data
	$result = $alb;
	$children = $wpdb->get_results( $wpdb->prepare( "SELECT `id` FROM `".WPPA_ALBUMS."` WHERE `a_parent` = %s " . wppa_get_album_order( $alb ), $alb ), ARRAY_A );
	if ( $children ) foreach ( $children as $child ) {
		$result .= '.' . _wppa_alb_to_enum_children( $child['id'] );
		$result = trim( $result, '.' );
	}

	// Store in cache
	$child_cache[$alb] = $result;

	// Return requested data
	return $child_cache[$alb];
}

function wppa_compress_enum( $enum ) {
	$result = $enum;
	if ( strpos( $enum, '.' ) !== false ) {
		$result = explode( '.', $enum );
		sort( $result, SORT_NUMERIC );
		$old = '-99';
		foreach ( array_keys( $result ) as $key ) { 	// Remove dups
			if ( $result[$key] == $old ) unset ( $result[$key] );
			else $old = $result[$key];
		}
		$result = wppa_index_array_to_string( $result );
		$result = str_replace( ',', '.', $result );
	}
	$result = trim( $result, '.' );
	return $result;
}

function wppa_expand_enum( $enum ) {
	$result = $enum;
	$result = str_replace( '.', ',', $result );
	$result = str_replace( ',,', '..', $result );
	$result = wppa_index_string_to_array( $result );
	$result = implode( '.', $result );
	return $result;
}

function wppa_mktree( $path ) {
	if ( is_dir( $path ) ) {
		wppa_chmod( $path );
		return true;
	}
	$bret = wppa_mktree( dirname( $path ) );
	wppa_mkdir( $path );
	wppa_chmod( $path );
	return ( is_dir( $path ) );
}

function wppa_mkdir( $path ) {
	if ( ! is_dir( $path ) ) {
		mkdir( $path );
		if ( is_dir( $path ) ) {
			wppa_log( 'Fso', 'Created path: ' . $path );
		}
		else {
			wppa_log( 'Err', 'Could not create: ' . $path );
		}
		wppa_chmod( $path );
	}
}


// Compute avg rating and count and put it in photo data
function wppa_rate_photo( $id ) {
global $wpdb;

	// Likes only?
	if ( wppa_opt( 'rating_display_type' ) == 'likes' ) {

		// Get rating(like)count
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPPA_RATING . "` WHERE `photo` = $id" );

		// Update photo
		$wpdb->query( "UPDATE `" . WPPA_PHOTOS . "` SET `rating_count` = '$count', `mean_rating` = '0' WHERE `id` = $id" );

		// Invalidate cache
		wppa_cache_photo( 'invalidate', $id );
	}
	else {

		// Get all ratings for this photo
		$ratings = $wpdb->get_results( $wpdb->prepare( "SELECT `value` FROM `".WPPA_RATING."` WHERE `photo` = %s AND `status` = %s", $id, 'publish' ), ARRAY_A );

		// Init
		$the_value = '0';
		$the_count = '0';

		// Compute mean value and count
		if ( $ratings ) foreach ( $ratings as $rating ) {
			if ( $rating['value'] == '-1' ) $the_value += wppa_opt( 'dislike_value' );
			else $the_value += $rating['value'];
			$the_count++;
		}
		if ( $the_count ) $the_value /= $the_count;
		if ( wppa_opt( 'rating_max' ) == '1' ) $the_value = '0';
		if ( $the_value == '10' ) $the_value = '9.9999999';	// mean_rating is a text field. for sort order reasons we make 10 into 9.99999

		// Update photo
		$wpdb->query( $wpdb->prepare( "UPDATE `".WPPA_PHOTOS."` SET `mean_rating` = %s, `rating_count` = %s WHERE `id` = $id", $the_value, $the_count ) );

		// Invalidate cache
		wppa_cache_photo( 'invalidate', $id );

		// Set status to a medaltype if appiliccable
		wppa_test_for_medal( $id );
	}
}

function wppa_strip_ext( $file ) {

	$strlen = strlen( $file );
	$dotpos = strrpos( $file, '.' );
	if ( $dotpos > ( $strlen - 6 ) ) {
		$result = substr( $file, 0, $dotpos );
	}
	else {
		$result = $file;
	}

	return $result; // preg_replace('/\.[^.]*$/', '', $file);
}

function wppa_get_ext( $file ) {

	$strlen = strlen( $file );
	$dotpos = strrpos( $file, '.' );
	if ( $dotpos > ( $strlen - 6 ) ) {
		$result = substr( $file, $dotpos + 1 );
	}
	else {
		$result = '';
	}

	return $result; // str_replace( wppa_strip_ext( $file ).'.', '', $file );
}

function wppa_encode_uri_component( $xstr ) {
	$str = $xstr;
	$illegal = array( '?', '&', '#', '/', '"', "'", ' ' );
	foreach ( $illegal as $char ) {
		$str = str_replace( $char, sprintf( '%%%X', ord($char) ), $str );
	}
	return $str;
}

function wppa_decode_uri_component( $xstr ) {
	$str = $xstr;
	$illegal = array( '?', '&', '#', '/', '"', "'", ' ' );
	foreach ( $illegal as $char ) {
		$str = str_replace( sprintf( '%%%X', ord($char) ), $char, $str );
		$str = str_replace( sprintf( '%%%x', ord($char) ), $char, $str );
	}
	return $str;
}

function wppa_force_numeric_else( $value, $default ) {
	if ( ! $value ) return $value;
	if ( ! wppa_is_int( $value ) ) return $default;
	return $value;
}

// Same as wp sanitize_file_name, except that it can be used for a pathname also.
// If a pathname: only the basename of the path is sanitized.
function wppa_sanitize_file_name( $file, $check_length = true ) {
	$temp 	= explode( '/', $file );
	$cnt 	= count( $temp );
	$temp[$cnt - 1] = strip_tags( stripslashes( $temp[$cnt - 1] ) );//sanitize_file_name( $temp[$cnt - 1] );
	$maxlen = wppa_opt( 'max_filename_length' );
	if ( $maxlen && $check_length ) {
		if ( strpos( $temp[$cnt - 1], '.' ) !== false ) {
			$name = wppa_strip_ext( $temp[$cnt - 1] );
			$ext = str_replace( $name.'.', '', $temp[$cnt - 1] );
			if ( strlen( $name ) > $maxlen ) {
				$name = substr( $name, 0, $maxlen );
				$temp[$cnt - 1] = $name.'.'.$ext;
			}
		}
		else {
			if ( strlen( $temp[$cnt - 1] ) > $maxlen ) {
				$temp[$cnt - 1] = substr( $temp[$cnt - 1], 0, $maxlen );
			}
		}
	}
	$file 	= implode( '/', $temp );
	$file 	= trim ( $file );
	return $file;
}

// Create a html safe photo name from a filename. May be a pathname
function wppa_sanitize_photo_name( $file ) {
	$result = htmlspecialchars( strip_tags( stripslashes( basename( $file ) ) ) );
	$maxlen = wppa_opt( 'max_photoname_length' );
	if ( $maxlen && strlen( $result ) > $maxlen ) {
		$result = wppa_strip_ext( $result ); // First remove any possible file-extension
		if ( strlen( $result ) > $maxlen ) {
			$result = substr( $result, 0, $maxlen );	// Truncate
		}
	}
	return $result;
}

// Get meta keywords of a photo
function wppa_get_keywords( $id ) {
static $wppa_void_keywords;

	if ( ! $id ) return '';

	if ( empty ( $wppa_void_keywords ) ) {
		$wppa_void_keywords	= array( 	__('Not Defined', 'wp-photo-album-plus'),
										__('Manual', 'wp-photo-album-plus'),
										__('Program AE', 'wp-photo-album-plus'),
										__('Aperture-priority AE', 'wp-photo-album-plus'),
										__('Shutter speed priority AE', 'wp-photo-album-plus'),
										__('Creative (Slow speed)', 'wp-photo-album-plus'),
										__('Action (High speed)', 'wp-photo-album-plus'),
										__('Portrait', 'wp-photo-album-plus'),
										__('Landscape', 'wp-photo-album-plus'),
										__('Bulb', 'wp-photo-album-plus'),
										__('Average', 'wp-photo-album-plus'),
										__('Center-weighted average', 'wp-photo-album-plus'),
										__('Spot', 'wp-photo-album-plus'),
										__('Multi-spot', 'wp-photo-album-plus'),
										__('Multi-segment', 'wp-photo-album-plus'),
										__('Partial', 'wp-photo-album-plus'),
										__('Other', 'wp-photo-album-plus'),
										__('No Flash', 'wp-photo-album-plus'),
										__('Fired', 'wp-photo-album-plus'),
										__('Fired, Return not detected', 'wp-photo-album-plus'),
										__('Fired, Return detected', 'wp-photo-album-plus'),
										__('On, Did not fire', 'wp-photo-album-plus'),
										__('On, Fired', 'wp-photo-album-plus'),
										__('On, Return not detected', 'wp-photo-album-plus'),
										__('On, Return detected', 'wp-photo-album-plus'),
										__('Off, Did not fire', 'wp-photo-album-plus'),
										__('Off, Did not fire, Return not detected', 'wp-photo-album-plus'),
										__('Auto, Did not fire', 'wp-photo-album-plus'),
										__('Auto, Fired', 'wp-photo-album-plus'),
										__('Auto, Fired, Return not detected', 'wp-photo-album-plus'),
										__('Auto, Fired, Return detected', 'wp-photo-album-plus'),
										__('No flash function', 'wp-photo-album-plus'),
										__('Off, No flash function', 'wp-photo-album-plus'),
										__('Fired, Red-eye reduction', 'wp-photo-album-plus'),
										__('Fired, Red-eye reduction, Return not detected', 'wp-photo-album-plus'),
										__('Fired, Red-eye reduction, Return detected', 'wp-photo-album-plus'),
										__('On, Red-eye reduction', 'wp-photo-album-plus'),
										__('Red-eye reduction, Return not detected', 'wp-photo-album-plus'),
										__('On, Red-eye reduction, Return detected', 'wp-photo-album-plus'),
										__('Off, Red-eye reduction', 'wp-photo-album-plus'),
										__('Auto, Did not fire, Red-eye reduction', 'wp-photo-album-plus'),
										__('Auto, Fired, Red-eye reduction', 'wp-photo-album-plus'),
										__('Auto, Fired, Red-eye reduction, Return not detected', 'wp-photo-album-plus'),
										__('Auto, Fired, Red-eye reduction, Return detected', 'wp-photo-album-plus'),
										'album', 'albums', 'content', 'http',
										'source', 'wp', 'uploads', 'thumbs',
										'wp-content', 'wppa-source',
										'border', 'important', 'label', 'padding',
										'segment', 'shutter', 'style', 'table',
										'times', 'value', 'views', 'wppa-label',
										'wppa-value', 'weighted', 'wppa-pl',
										'datetime', 'exposureprogram', 'focallength', 'isospeedratings', 'meteringmode', 'model', 'photographer',
										str_replace( '/', '', site_url() )
									);

		// make a string
		$temp = implode( ',', $wppa_void_keywords );

		// Downcase
		$temp = strtolower( $temp );

		// Remove spaces and funny chars
		$temp = str_replace( array( ' ', '-', '"', "'", '\\', '>', '<', ',', ':', ';', '!', '?', '=', '_', '[', ']', '(', ')', '{', '}' ), ',', $temp );
		$temp = str_replace( ',,', ',', $temp );
//wppa_log('dbg', $temp);

		// Make array
		$wppa_void_keywords = explode( ',', $temp );

		// Sort array
		sort( $wppa_void_keywords );

		// Remove dups
		$start = 0;
		foreach ( array_keys( $wppa_void_keywords ) as $key ) {
			if ( $key > 0 ) {
				if ( $wppa_void_keywords[$key] == $wppa_void_keywords[$start] ) {
					unset ( $wppa_void_keywords[$key] );
				}
				else {
					$start = $key;
				}
			}
		}
	}

	$text 	= wppa_get_photo_name( $id )  .' ' . wppa_get_photo_desc( $id );
	$text 	= str_replace( array( '/', '-' ), ' ', $text );
	$words 	= wppa_index_raw_to_words( $text );
	foreach ( array_keys( $words ) as $key ) {
		if ( 	wppa_is_int( $words[$key] ) ||
				in_array( $words[$key], $wppa_void_keywords ) ||
				strlen( $words[$key] ) < 5 ) {
			unset ( $words[$key] );
		}
	}
	$result = implode( ', ', $words );
	return $result;
}

function wppa_optimize_image_file( $file ) {
	if ( ! wppa_switch( 'optimize_new' ) ) return;
	if ( function_exists( 'ewww_image_optimizer' ) ) {
		ewww_image_optimizer( $file, 4, false, false, false );
	}
}

function wppa_is_orig ( $path ) {
	$file = basename( $path );
	$file = wppa_strip_ext( $file );
	$temp = explode( '-', $file );
	if ( ! is_array( $temp ) ) return true;
	$temp = $temp[ count( $temp ) -1 ];
	$temp = explode( 'x', $temp );
	if ( ! is_array( $temp ) ) return true;
	if ( count( $temp ) != 2 ) return true;
	if ( ! wppa_is_int( $temp[0] ) ) return true;
	if ( ! wppa_is_int( $temp[1] ) ) return true;
	return false;
}

function wppa_browser_can_html5() {

	if ( ! isset( $_SERVER["HTTP_USER_AGENT"] ) ) return false;

	$is_opera 	= strpos( $_SERVER["HTTP_USER_AGENT"], 'OPR' );
	$is_ie 		= strpos( $_SERVER["HTTP_USER_AGENT"], 'Trident' );
	$is_safari 	= strpos( $_SERVER["HTTP_USER_AGENT"], 'Safari' );
	$is_firefox = strpos( $_SERVER["HTTP_USER_AGENT"], 'Firefox' );

	if ( $is_opera ) 	return true;
	if ( $is_safari ) 	return true;
	if ( $is_firefox ) 	return true;

	if ( $is_ie ) {
		$tri_pos = strpos( $_SERVER["HTTP_USER_AGENT"], 'Trident/' );
		$tri_ver = substr( $_SERVER["HTTP_USER_AGENT"], $tri_pos+8, 3 );
		if ( $tri_ver >= 6.0 ) return true; // IE 10 or later
	}

	return false;
}

function wppa_get_comten_ids( $max_count = 0, $albums = array() ) {
global $wpdb;

	if ( ! $max_count ) {
		$max_count = wppa_opt( 'comten_count' );
	}

	$photo_ids = $wpdb->get_results( $wpdb->prepare( 	"SELECT `photo` FROM `".WPPA_COMMENTS."` " .
														"WHERE `status` = 'approved' " .
														"ORDER BY `timestamp` DESC LIMIT %d", 100 * $max_count ), ARRAY_A );
	$result = array();

	if ( is_array( $photo_ids ) ) {
		foreach( $photo_ids as $ph ) {
			if ( empty( $albums ) || in_array( wppa_get_photo_item( $ph['photo'], 'album' ), $albums ) || ( count( $albums ) == 1 && $albums[0] == '0' ) ) {
				if ( count( $result ) < $max_count ) {
					if ( ! in_array( $ph['photo'], $result ) ) {
						$result[] = $ph['photo'];
					}
				}
			}
		}
	}

	return $result;
}

// Retrieve a get-vareiable, sanitized and post-processed
// Return '1' if set without value, return false when value is 'nil'
function wppa_get_get( $index ) {
static $wppa_get_get_cache;

	// Found this already?
	if ( isset( $wppa_get_get_cache[$index] ) ) return $wppa_get_get_cache[$index];

	// See if set
	if ( isset( $_GET['wppa-'.$index] ) ) {			// New syntax first
		$result = $_GET['wppa-'.$index];
	}
	elseif ( isset( $_GET[$index] ) ) {				// Old syntax
		$result = $_GET[$index];
	}
	else return false;								// Not set

	if ( $result == 'nil' ) return false;			// Nil simulates not set

	if ( ! strlen( $result ) ) $result = '1';		// Set but no value

	// Sanitize
	$result = strip_tags( $result );
	if ( strpos( $result, '<?' ) !== false ) die( 'Security check failure #191' );
	if ( strpos( $result, '?>' ) !== false ) die( 'Security check failure #192' );

	// Post processing needed?
	if ( $index == 'photo' && ( ! wppa_is_int( $result ) ) ) {

		// Encrypted?
		$result = wppa_decrypt_photo( $result );

		// By name?
		$result = wppa_get_photo_id_by_name( $result, wppa_get_album_id_by_name( wppa_get_get( 'album' ) ) );

		if ( ! $result ) return false;				// Non existing photo, treat as not set
	}
	if ( $index == 'album' ) {

		// Encrypted?
		$result = wppa_decrypt_album( $result );

		if ( ! wppa_is_int( $result ) ) {
			$temp = wppa_get_album_id_by_name( $result );
			if ( wppa_is_int( $temp ) && $temp > '0' ) {
				$result = $temp;
			}
			elseif ( ! wppa_series_to_array( $result ) ) {
				$result = false;
			}
		}
	}

	// Save in cache
	$wppa_get_get_cache[$index] = $result;
	return $result;
}

function wppa_get_post( $index, $default = false ) {

	if ( isset( $_POST['wppa-'.$index] ) ) {		// New syntax first
		$result = $_POST['wppa-'.$index];
		if ( strpos( $result, '<?' ) !== false ) die( 'Security check failure #291' );
		if ( strpos( $result, '?>' ) !== false ) die( 'Security check failure #292' );
		if ( $index == 'album' ) $result = wppa_decrypt_album( $result );
		if ( $index == 'photo' ) $result = wppa_decrypt_photo( $result );
		return $result;
	}
	if ( isset( $_POST[$index] ) ) {				// Old syntax
		$result = $_POST[$index];
		if ( strpos( $result, '<?' ) !== false ) die( 'Security check failure #391' );
		if ( strpos( $result, '?>' ) !== false ) die( 'Security check failure #392' );
		if ( $index == 'album' ) $result = wppa_decrypt_album( $result );
		if ( $index == 'photo' ) $result = wppa_decrypt_photo( $result );
		return $result;
	}
	return $default;
}

function wppa_sanitize_searchstring( $str ) {

	$result = $str;
	$result = strip_tags( $result );
	$result = stripslashes( $result );
	$result = str_replace( array( "'", '"', ':', ), '', $result );
	$temp 	= explode( ',', $result );
	foreach ( array_keys( $temp ) as $key ) {
		$temp[$key] = trim( $temp[$key] );
	}
	$result = implode( ',', $temp );

	return $result;
}

// Filter for Plugin CM Tooltip Glossary
function wppa_filter_glossary( $desc ) {
static $wppa_cmt;

	// Do we need this?
	if ( wppa_switch( 'use_CMTooltipGlossary' ) && class_exists( 'CMTooltipGlossaryFrontend' ) ) {

		// Class initialized?
		if ( empty( $wppa_cmt ) ) {
			$wppa_cmt = new CMTooltipGlossaryFrontend;
		}

		// Do we already start with a <p> ?
		$start_p = ( strpos( $desc, '<p' ) === 0 );

		// remove newlines, glossary converts them to <br />
		$desc = str_replace( array( "\n", "\r", "\t" ), '', $desc );
		$desc = $wppa_cmt->cmtt_glossary_parse( $desc, true );

		// Remove <p> and </p> that CMTG added around
		if ( ! $start_p ) {
			if ( substr( $desc, 0, 3 ) == '<p>' ) {
				$desc = substr( $desc, 3 );
			}
			if ( substr( $desc, -4 ) == '</p>' ) {
				$desc = substr( $desc, 0, strlen( $desc ) - 4 );
			}
		}
	}

	return $desc;
}

// Convert file extension to lowercase
function wppa_down_ext( $file ) {
	if ( strpos( $file, '.' ) === false ) return $file;	// no . found
	$dotpos = strrpos( $file, '.' );
	$file = substr( $file, 0, $dotpos ) . strtolower( substr( $file, $dotpos ) );
	return $file;
}

// See of a photo db entry is a multimedia entry
function wppa_is_multi( $id ) {

	if ( ! $id ) return false;			// No id

	$ext = wppa_get_photo_item( $id, 'ext' );
	return ( $ext == 'xxx' );
}

function wppa_fix_poster_ext( $fileorurl, $id ) {

	// Has it extension .xxx ?
	if ( substr( $fileorurl, -4 ) != '.xxx' ) {
		return $fileorurl;
	}

	// Get available ext
	$poster_ext = wppa_get_poster_ext( $id );

	// If found, replace extension to ext of existing file
	if ( $poster_ext ) {
		return str_replace( '.xxx', '.'.$poster_ext, $fileorurl );
	}

	// Not found. If audio, return audiostub file or url
	if ( wppa_has_audio( $id ) ) {

		$audiostub = wppa_opt( 'audiostub' );

		// Url ?
		if ( strpos( $fileorurl, 'http://' ) !== false || strpos( $fileorurl, 'https://' ) !== false ) {
			return WPPA_UPLOAD_URL . '/'. $audiostub;
		}

		// File
		else {
			return WPPA_UPLOAD_PATH . '/' . $audiostub;
		}
	}

	// Not found. Is Video, return as jpg
	return str_replace( '.xxx', '.jpg', $fileorurl );
}

function wppa_get_poster_ext( $id ) {
global $wppa_supported_photo_extensions;

	// Init
	$path 		= wppa_get_photo_path( $id, false );
	$raw_path 	= wppa_strip_ext( $path );

	// Find existing photofiles
	foreach ( $wppa_supported_photo_extensions as $ext ) {
		if ( is_file( $raw_path.'.'.$ext ) ) {
			return $ext;	// Found !
		}
	}

	// Not found.
	return false;
}

// Like wp sanitize_text_field, but also removes chars 0x00..0x07
function wppa_sanitize_text( $txt ) {
	$result = sanitize_text_field( $txt );
	$result = str_replace( array(chr(0), chr(1), chr(2), chr(3),chr(4), chr(5), chr(6), chr(7) ), '', $result );
	$result = trim( $result );
	return $result;
}

function wppa_is_mobile() {
//return true; // debug
	$result = false;
	$detect = new wppa_mobile_detect();
	if ( $detect->isMobile() ) {
		$result = true;
	}
	return $result;
}

// Like wp_nonce_field
// To prevent duplicate id's, we externally add an id number ( e.g. album ) and internally the mocc number.
function wppa_nonce_field( $action = -1, $name = "_wpnonce", $referer = true , $echo = true, $wppa_id = '0' ) {

	$name = esc_attr( $name );
	$nonce_field = 	'<input' .
						' type="hidden"' .
						' id="' . $name . '-' . $wppa_id . '-' . wppa( 'mocc' ) . '"' .
						' name="' . $name . '"' .
						' value="' . wp_create_nonce( $action ) . '"' .
						' />';

	if ( $referer ) {
		$nonce_field .= wp_referer_field( false );
	}

	if ( $echo ) {
		echo $nonce_field;
	}

	return $nonce_field;
}

// Like convert_smilies, but directe rendered to <img> tag to avoid performance bottleneck for emoji's when ajax on firefox
function wppa_convert_smilies( $text ) {
static $smilies;

	// Initialize
	if ( ! is_array( $smilies ) ) {
		$smilies = array(	";-)" 		=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f609.png" />',
							":|" 		=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f610.png" />',
							":x" 		=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f621.png" />',
							":twisted:" => '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f608.png" />',
							":shock:" 	=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f62f.png" />',
							":razz:" 	=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f61b.png" />',
							":oops:" 	=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f633.png" />',
							":o" 		=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f62e.png" />',
							":lol:" 	=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f606.png" />',
							":idea:" 	=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f4a1.png" />',
							":grin:" 	=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f600.png" />',
							":evil:" 	=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f47f.png" />',
							":cry:" 	=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f625.png" />',
							":cool:" 	=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f60e.png" />',
							":arrow:" 	=> '<img class="emoji" draggable="false" alt="?" src="http://s.w.org/images/core/emoji/72x72/27a1.png" />',
							":???:" 	=> '<img class="emoji" draggable="false" alt="??" src="http://s.w.org/images/core/emoji/72x72/1f615.png" />',
							":?:" 		=> '<img class="emoji" draggable="false" alt="?" src="http://s.w.org/images/core/emoji/72x72/2753.png" />',
							":!:" 		=> '<img class="emoji" draggable="false" alt="?" src="http://s.w.org/images/core/emoji/72x72/2757.png" />'
		);
	}

	// Perform
	$result = $text;
	foreach ( array_keys( $smilies ) as $key ) {
		$result = str_replace( $key, $smilies[$key], $result );
	}

	// Convert non-emoji's
	$result = convert_smilies( $result );

	// SSL?
	if ( is_ssl() ) {
		$result = str_replace( 'http://', 'https://', $result );
	}

	// Done
	return $result;
}

function wppa_toggle_alt() {
	if ( wppa( 'alt' ) == 'alt' ) {
		wppa( 'alt', 'even' );
	}
	else {
		wppa( 'alt', 'alt' );
	}
}

function wppa_is_virtual() {

	if ( wppa( 'is_topten' ) ) return true;
	if ( wppa( 'is_lasten' ) ) return true;
	if ( wppa( 'is_featen' ) ) return true;
	if ( wppa( 'is_comten' ) ) return true;
	if ( wppa( 'is_tag' ) ) return true;
	if ( wppa( 'is_related' ) ) return true;
	if ( wppa( 'is_upldr' ) ) return true;
	if ( wppa( 'is_cat' ) ) return true;
	if ( wppa( 'is_supersearch' ) ) return true;
	if ( wppa( 'src' ) ) return true;
	if ( wppa( 'supersearch' ) ) return true;
	if ( wppa( 'searchstring' ) ) return true;
	if ( wppa( 'calendar' ) ) return true;
	if ( wppa_get_get( 'vt' ) ) return true;

	return false;
}

function wppa_too_old_for_cloud( $id ) {

	$thumb = wppa_cache_thumb( $id );

	$is_old = wppa_cdn( 'admin' ) && wppa_opt( 'max_cloud_life' ) && ( time() > ( $thumb['timestamp'] + wppa_opt( 'max_cloud_life' ) ) );

	return $is_old;
}

// Test if we are in a widget
// Returns wppa widget type if in a wppa widget
// Else: return true if in a widget, false if not in a widget
function wppa_in_widget() {

	if ( wppa( 'in_widget' ) ) {
		return wppa( 'in_widget' );
	}
	$stack = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
	if ( is_array( $stack ) ) foreach( $stack as $item ) {
		if ( isset( $item['class'] ) && $item['class'] == 'WP_Widget' ) {
			return true;
		}
	}
	return false;
}

function wppa_bump_mocc() {
	wppa( 'mocc', wppa( 'mocc' ) + 1 );
}

// This is a nice simple function
function wppa_out( $txt ) {
global $wppa;

	$wppa['out'] .= $txt;
	return;
}

function wppa_exit() {
	wppa_session_end();
	exit;
}

function wppa_sanitize_custom_field( $txt ) {

	if ( wppa_switch( 'allow_html_custom' ) ) {
		$result = wppa_strip_tags( $txt, 'script&style' );
	}
	else {
		$result = strip_tags( $txt );
	}
	return $result;
}

// Get the minimum number of photos to display ( photocount treshold if not virtuel )
function wppa_get_mincount() {

	if ( wppa_is_virtual() ) return '0';

	return wppa_opt( 'min_thumbs' );
}

// See if a photo is in our admins choice zip
function  wppa_is_photo_in_zip( $id ) {
global $wpdb;

	// Verify existance of zips dir
	$zipsdir = WPPA_UPLOAD_PATH.'/zips/';
	if ( ! is_dir( $zipsdir ) ) return false;

	// Compose the users zip filename
	$zipfile = $zipsdir.wppa_get_user().'.zip';

	// Check file existance
	if ( ! is_file( $zipfile ) ) {
		return false;
	}

	// Find the photo data
	$data = wppa_cache_thumb( $id );
	$photo_file = wppa_fix_poster_ext( $data['filename'], $id );

	// Open zip
	$wppa_zip = new ZipArchive;
	$wppa_zip->open( $zipfile );
	if ( ! $wppa_zip ) {

		// Failed to open zip
		return false;
	}

	// Look photo up in zip
	for( $i = 0; $i < $wppa_zip->numFiles; $i++ ) {
		$stat = $wppa_zip->statIndex( $i );
		$file_name = $stat['name'];
		if ( $file_name == $photo_file ) {

			// Found
			$wppa_zip->close();
			return true;
		}
	}

	// Not found
	$wppa_zip->close();
	return false;
}

// Convert querystring to get and request vars
function wppa_convert_uri_to_get( $uri ) {

	// Make local copy of argument
	$temp = $uri;

	// See if a ? is in the string
	if ( strpos( $uri, '?' ) !== false ) {

		// Trim up to and including ?
		$temp = substr( $uri, strpos( $uri, '?' ) + 1 );
	}

	// explode uri
	$arr = explode( '&', $temp );

	// If args exist, process them
	if ( !empty( $arr ) ) {
		foreach( $arr as $item ) {
			$arg = explode( '=', $item );
			if ( ! isset( $arg[1] ) ) {
				$arg[1] = null;
			}
			else {
				$arg[1] = urldecode( $arg[1] );
			}
			$_GET[$arg[0]] = $arg[1];
			$_REQUEST[$arg[0]] = $arg[1];
//			wppa_log('dbg',$item);
		}
	}
}

// Set owner to login name if photo name is user display_name
// Return true if owner changed, return 0 if already set, return false if not a username
function wppa_set_owner_to_name( $id ) {
global $wpdb;
static $usercache;

	// Feature enabled?
	if ( wppa_switch( 'owner_to_name' ) ) {

		// Get photo data.
		$p = wppa_cache_thumb( $id );

		// Find user of whose display name equals photoname
		if ( isset( $usercache[$p['name']] ) ) {
			$user = $usercache[$p['name']];
		}
		else {
			$user = $wpdb->get_var( $wpdb->prepare( "SELECT `user_login` FROM `".$wpdb->users."` WHERE `display_name` = %s", $p['name'] ) );
			if ( $user ) {
				$usercache[$p['name']] = $user;
			}
			else {
				$usercache[$p['name']] = false;	// NULL is equal to ! isset() !!!
			}
		}
		if ( $user ) {

			if ( $p['owner'] != $user ) {
				wppa_update_photo( array( 'id' => $id, 'owner' => $user ) );
				wppa_cache_thumb( 'invalidate', $id );
				wppa_log( 'Obs', 'Owner of photo '.$id.' in album '.wppa_get_photo_item( $id, 'album' ).' set to: '.$user );
				return true;
			}
			else {
				return '0';
			}
		}
	}

	return false;
}

// Get my last vote for a certain photo
function wppa_get_my_last_vote( $id ) {
global $wpdb;

	$result = $wpdb->get_var( $wpdb->prepare( 	"SELECT `value` FROM `" . WPPA_RATING . "` " .
												"WHERE `photo` = %s " .
												"AND `user` = %s " .
												"ORDER BY `id` DESC " .
												"LIMIT 1 ",
												$id,
												wppa_get_user()
											)
							);
	return $result;
}

// Add page id to list of pages that need css and js
function wppa_add_wppa_on_page() {
global $wppa_first_id;

	// Feature enabled?
	if ( ! wppa_switch( 'js_css_optional' ) ) {
		return;
	}

	// Init
	$pages 	= wppa_index_string_to_array( get_option( 'wppa_on_pages_list' ) );
	$ID 	= get_the_ID();
	$doit 	= false;

	// Check for the current ID
	if ( $ID ) {
		if ( ! in_array( $ID, $pages ) ) {
			$pages[] = $ID;
			$doit = true;
		}
	}

	// Check for the first encountered ID that may not need wppa. Mark it as it is now the first post on a page, but posts further on the page will going to need it
	if ( $wppa_first_id ) {
		if ( ! in_array( $wppa_first_id, $pages ) ) {
			$pages[] = $wppa_first_id;
			$doit = true;
		}
	}

	if ( $doit ) {
		sort( $pages, SORT_NUMERIC );
		update_option( 'wppa_on_pages_list', wppa_index_array_to_string( $pages ) );
		echo '<script type="text/javascript" >document.location.reload(true);</script>';
	}
}

// See during init if wppa styles and css is needed
function wppa_wppa_on_page() {
global $wppa_first_id;

	// Feature enabled?
	if ( ! wppa_switch( 'js_css_optional' ) ) {
		return true;
	}

	// Init
	$ID = get_the_ID();

	// Remember the first ID
	if ( ! $wppa_first_id ) {
		if ( $ID ) {
			$wppa_first_id = $ID;
		}
	}

	// Look up
	$pages 	= wppa_index_string_to_array( get_option( 'wppa_on_pages_list' ) );
	$result = in_array( $ID, $pages );

	return $result;
}

// Get an svg image html
// @1: string: Name of the .svg file without extension
// @2: string: CSS height or empty, no ; required
// @3: bool: True if for lightbox. Use lightbox colors
// @4: bool: if true: add border
// @5: string: border radius in %: none
// @6: string: border radius in %: light
// @7: string: border radius in %: medium
// @8: string: border radius in %: heavy
function wppa_get_svghtml( $name, $height = false, $lightbox = false, $border = false, $none = '0', $light = '10', $medium = '20', $heavy = '50' ) {

	// Slideonly has no navigation
//	if ( wppa( 'is_slideonly' ) && ! wppa( 'is_slideonlyf' ) ) {
//		return '';
//	}

	// Find the colors
	if ( $lightbox ) {
		$fillcolor 	= wppa_opt( 'ovl_svg_color' );
		$bgcolor 	= wppa_opt( 'ovl_svg_bg_color' );
	}
	else {
		$fillcolor 	= wppa_opt( 'svg_color' );
		$bgcolor 	= wppa_opt( 'svg_bg_color' );
	}

	// Find the border radius
	switch( wppa_opt( 'icon_corner_style' ) ) {
		case 'gif':
		case 'none':
			$bradius = $none;
			break;
		case 'light':
			$bradius = $light;
			break;
		case 'medium':
			$bradius = $medium;
			break;
		case 'heavy':
			$bradius = $heavy;
			break;
	}

	$use_svg	= wppa_use_svg();
	$src 		= $use_svg ? $name . '.svg' : $name . '.png';

	// Compose the html. Native svg html
	if ( $use_svg && in_array( $name, array( 	'Next-Button',
												'Prev-Button',
												'Backward-Button',
												'Forward-Button',
												'Pause-Button',
												'Play-Button',
												'Stop-Button',
												'Eagle-1',
												'Snail',
												'Exit',
												'Full-Screen',
												'Exit-Full-Screen',
												'Content-View',

																) ) ) {

		$result = 	'<svg' .
						' version="1.1"' .
						' xmlns="http://www.w3.org/2000/svg"' .
						' xmlns:xlink="http://www.w3.org/1999/xlink"' .
						' x="0px"' .
						' y="0px"' .
						' viewBox="0 0 30 30"' .
						' style="' .
							'enable-background:new 0 0 30 30;' .
							( $height ? 'height:' . $height . ';' : '' ) .
							'fill:' . $fillcolor . ';' .
							'background-color:' . $bgcolor . ';' .
							'text-decoration:none !important;' .
							'vertical-align:middle;' .
							( $bradius ? 'border-radius:' . $bradius . '%;' : '' ) .
							( $border ? 'border:2px solid ' . $bgcolor . ';box-sizing:border-box;' : '' ) .
							'"' .
						' xml:space="preserve"' .
						' >' .
						'<g>';
		switch ( $name ) {

			case 'Next-Button':
				$result .= 	'<path' .
								' d="M30,0H0V30H30V0z M20,20.5' .
									'c0,0.3-0.2,0.5-0.5,0.5S19,20.8,19,20.5v-4.2l-8.3,4.6c-0.1,0-0.2,0.1-0.2,0.1c-0.1,0-0.2,0-0.3-0.1c-0.2-0.1-0.2-0.3-0.2-0.4v-11' .
									'c0-0.2,0.1-0.4,0.3-0.4c0.2-0.1,0.4-0.1,0.5,0l8.2,5.5V9.5C19,9.2,19.2,9,19.5,9S20,9.2,20,9.5V20.5z"' .
							' />';
				break;
			case 'Prev-Button':
				$result .= 	'<path' .
								' d="M30,0H0V30H30V0z M20,20.5c0,0.2-0.1,0.4-0.3,0.4c-0.1,0-0.2,0.1-0.2,0.1c-0.1,0-0.2,0-0.3-0.1L11,15.4v5.1c0,0.3-0.2,0.5-0.5,0.5S10,20.8,10,20.5v-11' .
								'C10,9.2,10.2,9,10.5,9S11,9.2,11,9.5v4.2l8.3-4.6c0.2-0.1,0.3-0.1,0.5,0S20,9.3,20,9.5V20.5z"' .
							' />';
				break;
			case 'Backward-Button':
				$result .= 	'<path' .
								' d="M30,0H0V30H30V0z M23,20.5' .
									'c0,0.2-0.1,0.3-0.2,0.4c-0.2,0.1-0.3,0.1-0.5,0L16,17.4v3.1c0,0.2-0.1,0.4-0.3,0.4c-0.1,0-0.1,0.1-0.2,0.1c-0.1,0-0.2,0-0.3-0.1' .
									'l-8-6C7.1,14.8,7,14.6,7,14.5c0-0.2,0.1-0.3,0.2-0.4l8-5c0.2-0.1,0.3-0.1,0.5,0C15.9,9.2,16,9.3,16,9.5v3.1l6.3-3.6' .
									'c0.2-0.1,0.3-0.1,0.5,0C22.9,9.2,23,9.3,23,9.5V20.5z"' .
							' />';
				break;
			case 'Forward-Button':
				$result .= 	'<path' .
								' d="M30,0H0V30H30V0z' .
									'M22.8,15.9l-8,5c-0.2,0.1-0.3,0.1-0.5,0c-0.2-0.1-0.3-0.3-0.3-0.4v-3.1l-6.3,3.6C7.7,21,7.6,21,7.5,21c-0.1,0-0.2,0-0.3-0.1' .
									'C7.1,20.8,7,20.7,7,20.5v-11c0-0.2,0.1-0.3,0.2-0.4C7.4,9,7.6,9,7.7,9.1l6.3,3.6V9.5c0-0.2,0.1-0.4,0.3-0.4c0.2-0.1,0.4-0.1,0.5,0' .
									'l8,6c0.1,0.1,0.2,0.3,0.2,0.4C23,15.7,22.9,15.8,22.8,15.9z"' .
							' />';
				break;
			case 'Pause-Button':
				$result .= 	'<path' .
								' d="M30,0H0V30H30V0z M14,20.5' .
									'c0,0.3-0.2,0.5-0.5,0.5h-4C9.2,21,9,20.8,9,20.5v-11C9,9.2,9.2,9,9.5,9h4C13.8,9,14,9.2,14,9.5V20.5z M21,20.5' .
									'c0,0.3-0.2,0.5-0.5,0.5h-4c-0.3,0-0.5-0.2-0.5-0.5v-11C16,9.2,16.2,9,16.5,9h4C20.8,9,21,9.2,21,9.5V20.5z"' .
							' />';
				break;
			case 'Play-Button':
				$result .= 	'<path' .
								' d="M30,0H0V30H30V0z' .
									'M19.8,14.9l-8,5C11.7,20,11.6,20,11.5,20c-0.1,0-0.2,0-0.2-0.1c-0.2-0.1-0.3-0.3-0.3-0.4v-9c0-0.2,0.1-0.3,0.2-0.4' .
									'c0.1-0.1,0.3-0.1,0.5,0l8,4c0.2,0.1,0.3,0.2,0.3,0.4C20,14.7,19.9,14.8,19.8,14.9z"' .
							' />';
				break;
			case 'Stop-Button':
				$result .= 	'<path' .
								' d="M30,0H0V30H30V0z M21,20.5' .
									'c0,0.3-0.2,0.5-0.5,0.5h-11C9.2,21,9,20.8,9,20.5v-11C9,9.2,9.2,9,9.5,9h11C20.8,9,21,9.2,21,9.5V20.5z"' .
							'/>';
				break;
			case 'Eagle-1':
				$result .= 	'<path' .
								' d="M29.9,19.2c-0.1-0.1-0.2-0.2-0.4-0.2c-3.7,0-6.2-0.6-7.6-1.1c-0.1,0-0.1,0.1-0.2,0.1c-1.2,1.2-4,2.6-4.6,2.9' .
									'c-0.1,0-0.1,0.1-0.2,0.1c-0.2,0-0.4-0.1-0.4-0.3c-0.1-0.2,0-0.5,0.2-0.7c0.3-0.2,2.9-1.4,4.1-2.5l0,0c0.1-0.1,0.1-0.1,0.2-0.2' .
									'c0.7-0.7,2.5-0.5,3.3-0.3c0,0,0.1,0,0.1,0c0.2,0.1,0.4,0,0.5-0.2c0,0,0,0,0,0c0,0,0,0,0,0c0.1-0.2,0.1-0.3,0.2-0.5c0,0,0-0.1,0-0.1' .
									'c0-0.1,0.1-0.3,0.1-0.4c0,0,0-0.1,0-0.1c0-0.1,0-0.3,0-0.4c0,0,0-0.1,0-0.1c0-0.1-0.1-0.3-0.2-0.4c0,0,0,0,0-0.1' .
									'c-0.1-0.1-0.1-0.2-0.2-0.2c0,0-0.1-0.1-0.1-0.1c-0.1,0-0.1-0.1-0.2-0.1c0,0-0.1-0.1-0.1-0.1c-0.1,0-0.1-0.1-0.2-0.1' .
									'c-0.1,0-0.1-0.1-0.2-0.1c-0.1,0-0.1-0.1-0.2-0.1c0,0-0.1,0-0.1-0.1c-0.1,0-0.2-0.1-0.2-0.1c0,0-0.1,0-0.1,0c-0.4-0.1-0.7-0.2-1-0.2' .
									'c0-0.1-0.1-0.2-0.1-0.3c-0.1-0.2-0.2-0.3-0.3-0.5c-0.2-0.2-0.4-0.3-0.6-0.4C21,12.1,20.6,12,20,12c-0.3,0-0.6,0-0.8,0.1' .
									'c-0.1,0-0.1,0-0.2,0c-0.2,0-0.5,0.1-0.7,0.1c0,0-0.1,0-0.1,0c-0.2,0.1-0.5,0.1-0.7,0.2c0,0,0,0,0,0c-1.2,0.5-2.2,1.2-3,1.8' .
									'c-0.5,0.3-0.9,0.6-1.2,0.8c-0.2,0.1-0.5,0-0.7-0.2c-0.1-0.3,0-0.5,0.2-0.7c0.2-0.1,0.6-0.4,0.9-0.6c-1.6-0.6-4-2-4-5.4' .
									'c0-4.1,1.9-5.6,3.2-6.6c0.3-0.2,0.6-0.4,0.8-0.7C14,0.7,14,0.5,14,0.3S13.7,0,13.5,0C10.1,0,8.1,2,7,3.5v-1C7,2.3,6.9,2.1,6.7,2' .
									'C6.5,2,6.3,2,6.1,2.1C4.5,3.8,3.9,5.4,3.7,6.8L3.4,6.3C3.4,6.1,3.2,6,3.1,6S2.8,6,2.6,6.1C1.8,7,1.3,8,1.3,9c0,0.5,0.1,1,0.3,1.4' .
									'l-1-0.4c-0.2-0.1-0.3,0-0.5,0.1C0.1,10.2,0,10.3,0,10.5c0,2.7,0.5,4.4,1.4,5.2c0.1,0.1,0.2,0.1,0.3,0.2C1.4,16.4,1,17.4,1,18.5' .
									'c0,1.5,2.6,2.5,4.5,3c-1,0.4-2,1-2,2c0,0.5-1.6,1.2-3.1,1.5c-0.2,0-0.3,0.2-0.4,0.4c-0.1,0.2,0,0.4,0.2,0.5C0.4,26,4.9,30,8.5,30' .
									'C8.8,30,9,29.8,9,29.5c0-3.1,3.5-5,4.5-5.4c0.6,0.3,2,0.9,5,0.9c1.9,0,2.9-0.3,3.2-1l1.6,0.9c0.1,0,0.2,0.1,0.3,0.1' .
									'c3.4,0,4.3-1.1,4.4-1.2c0.1-0.2,0.1-0.5-0.1-0.6l-0.8-0.8c2.1-0.6,2.9-2.6,2.9-2.7C30,19.5,30,19.3,29.9,19.2z M20.5,14' .
									'c0.3,0,0.5,0.2,0.5,0.5S20.8,15,20.5,15S20,14.8,20,14.5S20.2,14,20.5,14z"' .
							' />';
				break;
			case 'Snail':
				$result .= 	'<path' .
								' d="M28.5,16.3L30,9.1c0.1-0.3-0.1-0.5-0.4-0.6c-0.3-0.1-0.5,0.1-0.6,0.4L27.6,16c0,0-0.1,0-0.1,0L27,10c0-0.3-0.3-0.5-0.5-0.5' .
									'C26.2,9.5,26,9.8,26,10l0.5,6.1c-0.4,0.1-0.7,0.2-1.1,0.3l0,0c-1.4,2-4.8,4.1-6.9,4.1c-1.9,0-3.8-0.1-5.2-1.1' .
									'c-0.1-0.1-0.2-0.2-0.2-0.4c0-0.1,0-0.3,0.2-0.4l1.2-1.1c1.5-1.9,1.6-4.7,1.6-5.5c0-1.8-1.2-5.5-5-5.5c-3.7,0-5,2.7-5,5' .
									'c0,2.7,2.1,3,3,3c1.5,0,3-1.3,3-2.5c0-1.1-0.4-1.5-1.5-1.5C9.4,10.5,9,10.9,9,12c0,0.3-0.2,0.5-0.5,0.5S8,12.3,8,12' .
									'c0-1.6,0.9-2.5,2.5-2.5c1.7,0,2.5,0.8,2.5,2.5c0,1.8-1.9,3.5-4,3.5c-1.9,0-4-1.1-4-4c0-3,1.9-6,6-6c4.1,0,6,3.8,6,6.5' .
									'c0,1.1-0.2,4-1.8,6.1l-0.8,0.7c1.2,0.5,2.6,0.6,4.1,0.6c1.8,0,5.2-2.3,6.2-3.9l0,0c0.3-0.7,0.3-1.6,0.3-2.7c0-0.3,0-0.5,0-0.8' .
									'c0-2-3-9.5-12-9.5C4.8,2.5,1,7.9,1,13c0,3,1.3,5.3,3.8,6.5c-0.5,0.4-1.4,1.1-2.6,1.6C0.1,21.8,0,24.9,0,25c0,0.2,0.1,0.4,0.3,0.4' .
									'c0.2,0.1,0.4,0.1,0.5,0c0,0,1.3-0.9,4.1-0.9c1.6,0,2.6,0.6,3.6,1c0.7,0.4,1.3,0.7,2.1,0.7c0.5,0,0.6,0.1,0.8,0.4' .
									'c0.3,0.4,0.6,0.8,1.7,0.8c1,0,1.4-0.3,1.8-0.6c0.3-0.2,0.6-0.4,1.2-0.4c0.6,0,0.9,0.2,1.3,0.4c0.4,0.3,1,0.6,1.9,0.6' .
									'c1.4,0,1.6-1,1.8-1.6c0.1-0.4,0.2-0.8,0.4-1c0.2-0.2,0.4-0.1,1,0.1c0.6,0.2,1.4,0.6,2.1-0.1c0.6-0.6,0.7-1.1,0.8-1.5' .
									'c0.1-0.4,0.1-0.6,0.5-1c0.6-0.5,2-0.1,2.4,0.1c0.2,0.1,0.5,0,0.6-0.2c0-0.1,1.1-1.7,1.1-3.3C30,17.8,29.4,16.8,28.5,16.3z"' .
							' />';
				break;
			case 'Exit':
				$result .= 	'<path d="M30 24.398l-8.406-8.398 8.406-8.398-5.602-5.602-8.398 8.402-8.402-8.402-5.598 5.602 8.398 8.398-8.398 8.398 5.598 5.602 8.402-8.402 8.398 8.402z"></path>';
				break;
			case 'Full-Screen':
				$result .= 	'<path d="M27.414 24.586l-4.586-4.586-2.828 2.828 4.586 4.586-4.586 4.586h12v-12zM12 0h-12v12l4.586-4.586 4.543 4.539 2.828-2.828-4.543-4.539zM12 22.828l-2.828-2.828-4.586 4.586-4.586-4.586v12h12l-4.586-4.586zM32 0h-12l4.586 4.586-4.543 4.539 2.828 2.828 4.543-4.539 4.586 4.586z"></path>';
				break;
			case 'Exit-Full-Screen':
				$result .= 	'<path d="M24.586 27.414l4.586 4.586 2.828-2.828-4.586-4.586 4.586-4.586h-12v12zM0 12h12v-12l-4.586 4.586-4.539-4.543-2.828 2.828 4.539 4.543zM0 29.172l2.828 2.828 4.586-4.586 4.586 4.586v-12h-12l4.586 4.586zM20 12h12l-4.586-4.586 4.547-4.543-2.828-2.828-4.547 4.543-4.586-4.586z"></path>';
				break;
			case 'Content-View':
				$result .= 	'<path' .
								' d="M21.5,25.5h4c0.276,0,0.5-0.224,0.5-0.5s-0.224-0.5-0.5-0.5h-4c-0.276,0-0.5,0.224-0.5,0.5S21.224,25.5,21.5,25.5z' .
									'M21.5,18.5h4c0.276,0,0.5-0.224,0.5-0.5s-0.224-0.5-0.5-0.5h-4c-0.276,0-0.5,0.224-0.5,0.5S21.224,18.5,21.5,18.5z M21.5,23.5h4' .
									'c0.276,0,0.5-0.224,0.5-0.5s-0.224-0.5-0.5-0.5h-4c-0.276,0-0.5,0.224-0.5,0.5S21.224,23.5,21.5,23.5z M21.5,16.5h4' .
									'c0.276,0,0.5-0.224,0.5-0.5s-0.224-0.5-0.5-0.5h-4c-0.276,0-0.5,0.224-0.5,0.5S21.224,16.5,21.5,16.5z M21.5,11.5h4' .
									'c0.276,0,0.5-0.224,0.5-0.5s-0.224-0.5-0.5-0.5h-4c-0.276,0-0.5,0.224-0.5,0.5S21.224,11.5,21.5,11.5z M26.864,0.5H3.136' .
									'C1.407,0.5,0,1.866,0,3.545v22.91C0,28.134,1.407,29.5,3.136,29.5h23.728c1.729,0,3.136-1.366,3.136-3.045V3.545' .
									'C30,1.866,28.593,0.5,26.864,0.5z M9.5,2.5C9.776,2.5,10,2.724,10,3S9.776,3.5,9.5,3.5S9,3.276,9,3S9.224,2.5,9.5,2.5z M6.5,2.5' .
									'C6.776,2.5,7,2.724,7,3S6.776,3.5,6.5,3.5S6,3.276,6,3S6.224,2.5,6.5,2.5z M3.5,2.5C3.776,2.5,4,2.724,4,3S3.776,3.5,3.5,3.5' .
									'S3,3.276,3,3S3.224,2.5,3.5,2.5z M29,26.455c0,1.128-0.958,2.045-2.136,2.045H3.136C1.958,28.5,1,27.583,1,26.455V5.5h28V26.455z' .
									'M21.5,9.5h4C25.776,9.5,26,9.276,26,9s-0.224-0.5-0.5-0.5h-4C21.224,8.5,21,8.724,21,9S21.224,9.5,21.5,9.5z M4.5,25.5h2' .
									'C6.776,25.5,7,25.276,7,25v-2c0-0.276-0.224-0.5-0.5-0.5h-2C4.224,22.5,4,22.724,4,23v2C4,25.276,4.224,25.5,4.5,25.5z M17.5,11.5' .
									'h2c0.276,0,0.5-0.224,0.5-0.5V9c0-0.276-0.224-0.5-0.5-0.5h-2C17.224,8.5,17,8.724,17,9v2C17,11.276,17.224,11.5,17.5,11.5z' .
									'M8.5,25.5h4c0.276,0,0.5-0.224,0.5-0.5s-0.224-0.5-0.5-0.5h-4C8.224,24.5,8,24.724,8,25S8.224,25.5,8.5,25.5z M8.5,18.5h4' .
									'c0.276,0,0.5-0.224,0.5-0.5s-0.224-0.5-0.5-0.5h-4C8.224,17.5,8,17.724,8,18S8.224,18.5,8.5,18.5z M8.5,23.5h4' .
									'c0.276,0,0.5-0.224,0.5-0.5s-0.224-0.5-0.5-0.5h-4C8.224,22.5,8,22.724,8,23S8.224,23.5,8.5,23.5z M4.5,11.5h2' .
									'C6.776,11.5,7,11.276,7,11V9c0-0.276-0.224-0.5-0.5-0.5h-2C4.224,8.5,4,8.724,4,9v2C4,11.276,4.224,11.5,4.5,11.5z M4.5,18.5h2' .
									'C6.776,18.5,7,18.276,7,18v-2c0-0.276-0.224-0.5-0.5-0.5h-2C4.224,15.5,4,15.724,4,16v2C4,18.276,4.224,18.5,4.5,18.5z M17.5,25.5' .
									'h2c0.276,0,0.5-0.224,0.5-0.5v-2c0-0.276-0.224-0.5-0.5-0.5h-2c-0.276,0-0.5,0.224-0.5,0.5v2C17,25.276,17.224,25.5,17.5,25.5z' .
									'M17.5,18.5h2c0.276,0,0.5-0.224,0.5-0.5v-2c0-0.276-0.224-0.5-0.5-0.5h-2c-0.276,0-0.5,0.224-0.5,0.5v2' .
									'C17,18.276,17.224,18.5,17.5,18.5z M8.5,16.5h4c0.276,0,0.5-0.224,0.5-0.5s-0.224-0.5-0.5-0.5h-4C8.224,15.5,8,15.724,8,16' .
									'S8.224,16.5,8.5,16.5z M8.5,9.5h4C12.776,9.5,13,9.276,13,9s-0.224-0.5-0.5-0.5h-4C8.224,8.5,8,8.724,8,9S8.224,9.5,8.5,9.5z' .
									'M8.5,11.5h4c0.276,0,0.5-0.224,0.5-0.5s-0.224-0.5-0.5-0.5h-4C8.224,10.5,8,10.724,8,11S8.224,11.5,8.5,11.5z"' .
							' />';
				break;

		}

		$result .= 		'</g>' .
					'</svg>';

		return $result;
	}

	// Compose html. Non native svg or gif/png
	else {
if ( $use_svg ) wppa_log('dbg','Still used for '.$name,true);
		$result = 	'<img' .
						' src="' . wppa_get_imgdir( $src ) . '"' .
						( $use_svg ? ' class="wppa-svg"' : '' ) .
						' style="' .
							( $height ? 'height:' . $height . ';' : '' ) .
							'fill:' . $fillcolor . ';' .
							'background-color:' . $bgcolor . ';' .
							( $use_svg ? 'display:none;' : '' ) .
							'text-decoration:none !important;' .
							'vertical-align:middle;' .
							( $bradius ? 'border-radius:' . $bradius . '%;' : '' ) .
							( $border ? 'border:2px solid ' . $bgcolor . ';box-sizing:border-box;' : '' ) .

						'"' .
						' alt="' . $name . '"' .
//						' onload="wppaReplaceSvg()"' .
					' />';
	}
	return $result;
}

function wppa_get_mime_type( $id ) {

	$ext = strtolower( wppa_get_photo_item( $id, 'ext' ) );
	if ( $ext == 'xxx' ) {
		$ext = wppa_get_poster_ext( $id );
	}

	switch ( $ext ) {
		case 'jpg':
		case 'jpeg':
			$result = 'image/jpeg';
			break;
		case 'png':
			$result = 'image/png';
			break;
		case 'gif':
			$result = 'image/gif';
			break;
		default:
			$result = '';
	}

	return $result;
}

function wppa_is_ie() {

	$result = false;
	if ( isset ( $_SERVER["HTTP_USER_AGENT"] ) ) {
		if ( strpos( $_SERVER["HTTP_USER_AGENT"], 'Trident' ) !== false ) {
			$result = true;
		}
	}

	return $result;
}

function wppa_is_safari() {

	$result = false;
	if ( isset ( $_SERVER["HTTP_USER_AGENT"] ) ) {
		if ( strpos( $_SERVER["HTTP_USER_AGENT"], 'Safari' ) !== false ) {
			$result = true;
		}
	}

	return $result;
}

function wppa_chmod( $fso ) {

	$fso = rtrim( $fso, '/' );

	$perms = fileperms( $fso ) & 0777;

	if ( is_dir( $fso ) ) {

		// Check file permissions
		if ( 0755 !== ( $perms & 0755 ) ) {

			// If not sufficient, try to change
			@ chmod( $fso, 0755 );
			clearstatcache();

			// If still no luck
			if ( 0755 !== ( fileperms( $fso ) & 0755 ) ) {
				wppa_log( 'Fso', sprintf( 'Unable to set filepermissions on %s from %o to 0755', $fso, $perms ) );
			}
			else {
				wppa_log( 'Fso', sprintf( 'Successfully set filepermissions on %s from %o to 0755', $fso, $perms ) );
			}
		}

		// Verify existance of index.php
		if ( ! is_file( $fso . '/index.php' ) ) {
			@ copy( WPPA_PATH . '/index.php', $fso . '/index.php' );
			if ( is_file( $fso . '/index.php' ) ) {
				wppa_log( 'fso', 'Added: ' . $fso . '/index.php' );
			}
			else {
				wppa_log( 'fso', 'Could not add ' . $fso . '/index.php' );
			}
		}
	}

	if ( is_file( $fso ) ) {

		// Check file permissions
		if ( 0644 !== ( fileperms( $fso ) & 0644 ) ) {

			// If not sufficient, try to change
			@ chmod( $fso, 0644 );
			clearstatcache();

			// If still no luck
			if ( 0644 !== ( fileperms( $fso ) & 0644 ) ) {
				wppa_log( 'Fso', sprintf( 'Unable to set filepermissions on %s from %o to 0644', $fso, $perms ) );
			}
			else {
				wppa_log( 'Fso', sprintf( 'Successfully set filepermissions on %s from %o to 0644', $fso, $perms ) );
			}
		}
	}

}

// Test if a given url is to a photo file
function wppa_is_url_a_photo( $url ) {
	global $wppa_supported_photo_extensions;

	// Init
	$result = true;
	$ext 	= wppa_get_ext( $url );

	// If the url does not have a valid photo extension, its not a photo file
	if ( ! in_array( $ext, $wppa_supported_photo_extensions ) ) {
		return false;
	}

	// Using curl may be protected/limited
	// Use curl to see if the url is found to prevent a php warning
	/* experimental */
	if ( function_exists( 'curl_init' ) && false ) {

		// Create a curl handle to the expected photofile
		$ch = curl_init( $url );

		// Execute
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_FAILONERROR, true );
		curl_exec( $ch );

		// Check if HTTP code > 400 i.e. error 22 occurred
		if( curl_errno( $ch ) == 22 ) {
			$result = false;
		}

		// Close handle
		curl_close($ch);

	}

	// No curl on system, or do not use curl
	else {

		// getimagesize on a non imagefile produces a php warning
		$result = is_array( @ getimagesize( $url ) );
	}

	// Done
	return $result;
}

// Convert array into readable text
function wppa_serialize( $array ) {

	if ( ! is_array( $array ) ) {
		return 'Arg is not an array (wppa_serialize)';
	}
	$result = '';
	foreach( $array as $item ) {
		$result .= $item . ' | ';
	}
	$result = trim( $result, ' |' );
	$result = html_entity_decode( $result, ENT_QUOTES );

	return $result;
}

function wppa_get_like_title_a( $id ) {
global $wpdb;

	$me 	= wppa_get_user();
	$likes 	= wppa_get_photo_item( $id, 'rating_count');
	$mylike = $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPPA_RATING . "` WHERE `photo` = $id AND `user` = '$me'" );

	if ( $mylike ) {
		if ( $likes > 1 ) {
			$text = sprintf( _n( 'You and %d other person like this', 'You and %d other people like this', $likes - 1 ), $likes - 1 );
		}
		else {
			$text = __( 'You are the first one who likes this', 'wp-photo-album-plus' );
		}
		$text .= "\n"
 . __( 'Click again if you do no longer like this', 'wp-photo-album-plus' );
	}
	else {
		if ( $likes ) {
			$text = sprintf( _n( '%d person likes this', '%d people like this', $likes, 'wp-photo-album-plus' ), $likes );
		}
		else {
			$text = __( 'Be the first one to like this', 'wp-photo-album-plus' );
		}
	}
	$result['title']  	= $text;
	$result['mine']  	= $mylike;
	$result['total'] 	= $likes;
	$result['display'] 	= sprintf( _n( '%d like', '%d likes', $likes ), $likes );

	return $result;
}

function wppa_print_tree( $path ) {
	$path = rtrim( $path, '/' );
	echo $path . '<br />';
	$files = glob( $path . '/*' );
	foreach( $files as $file ) {
		echo $file . '<br />';
		if ( is_dir( $file ) ) {
			wppa_print_tree( $file );
		}
	}
}

function wppa_process_failed_mail( $to = '', $subject = '', $message = '', $headers = '', $att = '' ) {

	// Ignore mails that lack essential data
	if ( ! $to || ! $subject || ! $message ) {
		return;
	}

	// Log mail failed
	wppa_log( 'Err', 'Failed mail. To = ' . ( is_array( $to ) ? implode( '|', $to ) : $to ) . ', Subject = ' . $subject . ', Message = ' . $message );

	// Compute mail id
	$id = md5( ( is_array( $to ) ? implode( '|', $to ) : $to ) . $subject . $message );

	// Get stack of failed mails
	$failed_mails = get_option( 'wppa_failed_mails' );

	// If no failed mails yet, create array
	if ( ! is_array( $failed_mails ) ) {
		$failed_mails = array();
	}

	// See if this mail appears in the failed mails list
	$found = false;
	foreach( array_keys( $failed_mails ) as $key ) {
		if ( $id == $key ) {
			$found = true;
		}
	}

	// Found? do nothing
	if ( $found ) {
		return;
	}

	// Not found, add it
	$failed_mails[$id] = array( 'to' 		=> $to,
								'subj' 		=> $subject,
								'message' 	=> $message,
								'headers' 	=> $headers,
								'att' 		=> $att,
								'retry' 	=> wppa_opt( 'retry_mails' ),
								);

	// Store list
	update_option( 'wppa_failed_mails', $failed_mails );

}

// Returns available memory in bytes
function wppa_memry_limit() {

	// get memory limit
	$memory_limit = 0;
	$memory_limini = wppa_convert_bytes( ini_get( 'memory_limit' ) );
	$memory_limcfg = wppa_convert_bytes( get_cfg_var( 'memory_limit' ) );

	// find the smallest not being zero
	if ( $memory_limini && $memory_limcfg ) $memory_limit = min( $memory_limini, $memory_limcfg );
	elseif ( $memory_limini ) $memory_limit = $memory_limini;
	else $memory_limit = $memory_limcfg;

	// No data, return 64MB
	if ( ! $memory_limit ) {
		return 64 * 1024 * 1024;
	}

	return $memory_limit;
}

// Create qr code cache and return its url
function wppa_create_qrcode_cache( $qrsrc ) {

	// Make sure the data portion is url encoded
	$temp = explode( 'data=', $qrsrc );
	$qrsrc = $temp[0] . 'data=' . urlencode( urldecode( $temp[1] ) );

	// Anything to do here?
	if ( ! wppa_switch( 'qr_cache' ) ) {
		return str_replace( 'format=svg', 'format=png', $qrsrc );
	}

	// Make sure we have .../uploads/wppa/qr
	if ( ! is_dir( WPPA_UPLOAD_PATH . '/qr' ) ) {
		mkdir( WPPA_UPLOAD_PATH . '/qr' );
	}

	// In cache already?
	$key = md5( $qrsrc );
	if ( is_file( WPPA_UPLOAD_PATH . '/qr/' . $key . '.svg' ) ) {

		// Bump cache found counter
		update_option( 'wppa_qr_cache_hits', get_option( 'wppa_qr_cache_hits', 0 ) + 1 );
		return WPPA_UPLOAD_URL . '/qr/' . $key . '.svg';
	}

	// Bump cache miss counter
	update_option( 'wppa_qr_cache_miss', get_option( 'wppa_qr_cache_miss', 0 ) + 1 );

	// Catch the qr image
	$curl = curl_init();
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $curl, CURLOPT_URL, $qrsrc );
	$contents = curl_exec( $curl );
	curl_close( $curl );

	// Save the image
	if ( strlen( $contents ) > 1000 ) {
		$file = fopen( WPPA_UPLOAD_PATH . '/qr/' . $key . '.svg', 'w' );
		if ( $file ) {
			fwrite( $file, $contents, strlen( $contents ) );
			fclose( $file );
		}
	}

	if ( is_file( WPPA_UPLOAD_PATH . '/qr/' . $key . '.svg' ) ) {
		return WPPA_UPLOAD_URL . '/qr/' . $key . '.svg';
	}
	else {
		return $qrsrc;
	}
}

function wppa_use_svg( $is_admin = false ) {
	if ( wppa_is_ie() ) {
		return false;
	}
	if ( ! $is_admin && wppa_opt( 'icon_corner_style' ) == 'gif' ) {
		return false;
	}
	return true;
}

function wppa_get_spinner_svg_body_html() {
	$result =
		'<rect x="0" y="0" width="100" height="100" fill="none" class="bk" >' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(0 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="0s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(22.5 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="0.09375s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(45 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="0.1875s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(67.5 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="0.28125s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(90 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="0.375s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(112.5 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="0.46875s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(135 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="0.5625s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(157.5 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="0.65625s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(180 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="0.75s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(202.5 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="0.84375s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(225 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="0.9375s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(247.5 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="1.03125s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(270 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="1.125s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(292.5 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="1.21875s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(315 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="1.3125s" repeatCount="indefinite"/>' .
		'</rect>' .
		'<rect class="wppa-ajaxspin"  x="47" y="40" width="6" height="20" rx="3" ry="3" transform="rotate(337.5 50 50) translate(0 -32)">' .
			'<animate attributeName="opacity" from="1" to="0" dur="1.5s" begin="1.40625s" repeatCount="indefinite"/>' .
		'</rect>';

	return $result;
}

// Can i handle pdf files?
function wppa_can_pdf() {

	if ( wppa_opt( 'image_magick' ) && wppa_switch( 'enable_pdf' ) ) {
		return true;
	}
	return false;
}

// Are we on a windows platform?
function wppa_is_windows() {

	// Windows uses \ instead of /, so if no / in ABSPATH, we are on a windows platform
	return strpos( ABSPATH, '/' ) === false;
}

// If it is a pdf, do preprocessing. We mod &file, hence &$file
function wppa_pdf_preprocess( &$file, $alb, $i = false ) {

	// If pdf not enabled, nothing to do.
	if ( ! wppa_can_pdf() ) return;

	// Is it a pdf?
	if ( $i === false ) {
		$is_pdf = wppa_get_ext( $file['name'] ) == 'pdf';
		$single = true;
	}

	// One file out of a multiple upload
	else {
		$is_pdf = wppa_get_ext( $file['name'][$i] ) == 'pdf';
		$single = false;
	}

	// Only continue if this is a pdf
	if ( ! $is_pdf ) {
		return;
	}

	// Make sure there are no spaces in the filename, otherwise the download link is broken
	if ( $single ) {
		$file['name'] = str_replace( ' ', '_', $file['name'] );
	}
	else {
		$file['name'][$i] = str_replace( ' ', '_', $file['name'][$i] );
	}

	// Copy pdf to source dir,
	$src = wppa_get_source_album_dir( $alb );
	if ( ! is_dir( $src ) ) {
		mkdir( $src );
	}
	$src .=  '/';

	if ( $single ) {
		copy( $file['tmp_name'], $src . $file['name'] );
	}
	else {
		copy( $file['tmp_name'][$i], $src . $file['name'][$i] );
	}

	// Make it a jpg in the source dir,
	if ( $single ) {
		$jpg = wppa_strip_ext( $file['name'] ) . '.jpg';
		if ( wppa_is_windows() ) {

			// On windows the filename[pageno] must be enclosed in "", on unix in ''
			wppa_image_magick( 'convert  -density 300 "' . $src . $file['name'] . '[0]" ' . $src . $jpg, null, $result );
		}
		else {
			wppa_image_magick( "convert  -density 300 '" . $src . $file['name'] . "[0]' " . $src . $jpg, null, $result );
		}
	}
	else {
		$jpg = wppa_strip_ext( $file['name'][$i] ) . '.jpg';
		if ( wppa_is_windows() ) {

			// On windows the filename[pageno] must be enclosed in "", on unix in ''
			wppa_image_magick( 'convert  -density 300 "' . $src . $file['name'][$i] . '[0]" ' . $src . $jpg, null, $result );
		}
		else {
			wppa_image_magick( "convert  -density 300 '" . $src . $file['name'][$i] . "[0]' " . $src . $jpg, null, $result );
		}
	}

	// Copy the jpg image back to $file['name'] and $file['tmp_name']
	if ( $single ) {
		$file['name'] = $jpg;
		copy( $src . $jpg, $file['tmp_name'] );
	}
	else {
		$file['name'][$i] = $jpg;
		copy( $src . $jpg, $file['tmp_name'][$i] );
	}

	// and continue as if it was a jpg, but remember its a .pdf
	wppa( 'is_pdf', true );

	return;
}

// If it is a pdf, do postprocessing
function wppa_pdf_postprocess( $id ) {

	// If pdf...
	if ( wppa( 'is_pdf' ) ) {
		$filename = wppa_get_photo_item( $id, 'filename' );
		$filename = str_replace( '.jpg', '.pdf', $filename );
		wppa_update_photo( array( 'id' => $id, 'filename' => $filename ) );
	}

	// Reset switch
	wppa( 'is_pdf', false );
}

// Has the system 'many' albums?
function wppa_has_many_albums() {
global $wpdb;
static $n_albums;

	// Max specified? If not, return false
	if ( ! wppa_opt( 'photo_admin_max_albums' ) ) {
		return false;
	}

	// Find total number of albums, if not done before
	if ( ! $n_albums ) {
		$n_albums = $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPPA_ALBUMS . "`" );
	}

	// Decide if many
	if ( $n_albums > wppa_opt( 'photo_admin_max_albums' ) ) {
		return true;
	}
	return false;
}

// Return false if user is logged in, upload roles are specified and i do not have one of them
function wppa_check_user_upload_role() {

	// Not logged in, ok
	if ( ! is_user_logged_in() ) {
		return true;
	}

	// No roles specified: ok
	if ( ! wppa_opt( 'user_opload_roles' ) ) {
		return true;
	}

	// Roles specified
	$roles = explode( ',', wppa_opt( 'user_opload_roles' ) );
	foreach ( $roles as $role ) {
		if ( current_user_can( $role ) ) {
			return true;
		}
	}

	// No matching role
	return false;
}

// Like wp_parse_args (args is array only), but it replaces NULL array elements also with the defaults.
function wppa_parse_args( $args, $defaults ) {

	// Remove NULL elements from $args
	$r = (array) $args;

	foreach( array_keys( $r ) as $key ) {

		// This looks funny, but:
		// a NULL element is regarded as being not set,
		// but it would not be overwritten by the default value in the merge
		if ( ! isset( $r[$key] ) ) {
			unset( $r[$key] );
		}
	}

	// Do the merge
	if ( is_array( $defaults ) ) {
		$r = array_merge( $defaults, $r );
	}

	return $r;
}

function wppa_is_divisible( $t, $n ) {
	return ( round( $t / $n ) == ( $t / $n ) );
}