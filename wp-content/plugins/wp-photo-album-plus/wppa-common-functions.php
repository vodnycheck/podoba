<?php
/* wppa-common-functions.php
*
* Functions used in admin and in themes
* Version 6.8.00
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

// Initialize globals and option settings
function wppa_initialize_runtime( $force = false ) {
global $wppa;
global $wppa_opt;
global $wppa_revno;
global $wppa_api_version;
global $wpdb;
global $wppa_initruntimetime;
global $wppa_defaults;

	$wppa_initruntimetime = - microtime( true );

	if ( $force ) {
		$wppa = false; 					// destroy existing arrays
		$wppa_opt = false;
	}

	if ( is_array( $wppa ) && is_array( $wppa_opt ) && ! $force ) {
		return; 	// Done already
	}

	if ( ! is_array( $wppa ) ) {
		wppa_reset_occurrance();
	}

	// Rebuild options array if required, i.e. when not yet existing or deleted.
	if ( ! is_array( $wppa_opt ) ) {
		wppa_set_defaults();
		$wppa_opt = $wppa_defaults;
		foreach ( array_keys( $wppa_opt ) as $option ) {
			$optval = get_option( $option, 'nil' );
			if ( $optval == 'nil' ) {
				update_option( $option, $wppa_defaults[$option] );
			}
			else {
				$wppa_opt[$option] = $optval;
			}
		}
	}

	// If there is only one grant parent, make sure the granted album is created regardless of if it is used.
	if ( ! wppa_is_cron() && ! wppa( 'ajax' ) ) {
		if ( wppa_is_int( get_option( 'wppa_grant_parent' ) ) ) {
			wppa_grant_albums();
		}
	}

	if ( isset( $_GET['debug'] ) && wppa_switch( 'allow_debug' ) ) {
		$key = $_GET['debug'] ? $_GET['debug'] : E_ALL;
		wppa( 'debug', $key );
	}

	$wppa_initruntimetime += microtime( true );
}

function wppa_reset_occurrance() {
global $wppa;
global $wppa_revno;
global $wppa_api_version;
global $thumbs;

	$thumbs = false;

	wppa_cache_thumb( 'invalidate' );
	wppa_cache_album( 'invalidate' );

	$mocc = isset( $wppa['mocc'] ) ? $wppa['mocc'] : '0';
	$occ  = isset( $wppa['occur'] ) ? $wppa['occur'] : '0';
	$wocc = isset( $wppa['widget_occur'] ) ? $wppa['widget_occur'] : '0';
	$rend = isset( $wppa['rendering_enabled'] ) ? $wppa['rendering_enabled'] : false;
	$debug = isset( $wppa['debug'] ) ? $wppa['debug'] : false;

	$wppa = array (
		'debug' 					=> $debug,
		'revno' 					=> $wppa_revno,				// set in wppa.php
		'api_version' 				=> $wppa_api_version,		// set in wppa.php
		'fullsize' 					=> '',
		'enlarge' 					=> false,
		'occur' 					=> $occ,
		'mocc' 						=> $mocc,
		'widget_occur' 				=> $wocc,
		'in_widget' 				=> false,
		'is_cover' 					=> '0',
		'is_slide' 					=> '0',
		'is_slideonly' 				=> '0',
		'is_slideonlyf'				=> '0',
		'is_filmonly'				=> '0',
		'film_on' 					=> '0',
		'browse_on' 				=> '0',
		'name_on' 					=> '0',
		'desc_on' 					=> '0',
		'numbar_on' 				=> '0',
		'single_photo' 				=> '',
		'is_mphoto' 				=> '0',
		'is_xphoto' 				=> '0',
		'start_album' 				=> '',
		'align' 					=> '',
		'src' 						=> false,
		'portrait_only' 			=> false,
		'in_widget_linkurl' 		=> '',
		'in_widget_linktitle' 		=> '',
		'in_widget_timeout' 		=> '0',
		'ss_widget_valign' 			=> '',
		'album_count' 				=> '0',
		'thumb_count' 				=> '0',
		'out' 						=> '',
		'auto_colwidth' 			=> false,
		'permalink' 				=> '',
		'rendering_enabled' 		=> $rend,
		'tabcount' 					=> '0',
		'comment_id' 				=> '',
		'comment_photo' 			=> '0',
		'comment_user' 				=> '',
		'comment_email' 			=> '',
		'comment_text' 				=> '',
		'no_default' 				=> false,
		'in_widget_frame_height' 	=> '0',
		'in_widget_frame_width'		=> '0',
		'current_album'				=> '0',
		'searchstring'				=> wppa_test_for_search(),
		'searchresults'				=> '',
		'any'						=> false,
		'ajax'						=> false,
		'error'						=> false,
		'iptc'						=> false,
		'exif'						=> false,
		'is_topten'					=> false,
		'topten_count'				=> '0',
		'is_lasten'					=> false,
		'lasten_count'				=> '0',
		'is_featen'					=> false,
		'featen_count'				=> '0',
		'start_photo'				=> '0',
		'is_single'					=> false,
		'is_landing'				=> '0',
		'is_comten'					=> false,
		'comten_count'				=> '0',
		'is_tag'					=> false,
		'photos_only'				=> false,
		'albums_only'				=> false,
		'medals_only' 				=> false,
		'page'						=> '',
		'geo'						=> '',
		'continue'					=> '',
		'is_upload'					=> false,
		'ajax_import_files'			=> false,
		'ajax_import_files_done'	=> false,
		'ajax_import_files_error' 	=> '',
		'last_albums'				=> false,
		'last_albums_parent'		=> '0',
		'is_multitagbox' 			=> false,
		'is_tagcloudbox' 			=> false,
		'taglist' 					=> '',
		'tagcols'					=> '2',
		'is_related'				=> false,
		'related_count'				=> '0',
		'is_owner'					=> '',
		'is_upldr'					=> '',
		'no_esc'					=> false,
		'front_edit'				=> false,
		'is_autopage'				=> false,
		'is_cat'					=> false,
		'bestof' 					=> false,
		'is_subsearch' 				=> false,
		'is_rootsearch' 			=> false,
		'is_superviewbox' 			=> false,
		'is_searchbox'				=> false,
		'may_sub'					=> false,
		'may_root'					=> false,
		'links_no_page' 			=> array( 'none', 'file', 'lightbox', 'lightboxsingle', 'fullpopup' ),
		'shortcode_content' 		=> '',
		'is_remote' 				=> false,
		'is_supersearch' 			=> false,
		'supersearch' 				=> '',
		'is_mobile' 				=> wppa_is_mobile(),
		'rel' 						=> get_option( 'wppa_lightbox_name' ) == 'wppa' ? 'data-rel' : 'rel',
		'lbtitle' 					=> get_option( 'wppa_lightbox_name' ) == 'wppa' ? 'data-lbtitle' : 'title',
		'alt'						=> 'even',
		'is_wppa_tree' 				=> false,
		'is_calendar' 				=> false,
		'calendar' 					=> '',
		'caldate' 					=> '',
		'calendarall' 				=> false,
		'reverse' 					=> false,
		'current_photo' 			=> false,
		'is_stereobox' 				=> false,
		'npages' 					=> '',
		'curpage'					=> '',
		'ss_pag' 					=> false,
		'slideframewidth' 			=> '',
		'slideframeheight' 			=> '',
		'ajax_import_files_error' 	=> '',
		'src_script' 				=> '',
		'is_url' 					=> false,
		'is_inverse' 				=> false,
		'coverphoto_pos' 			=> '',
		'forceroot' 				=> '',
		'landingpage' 				=> '',
		'is_admins_choice' 			=> false,
		'admins_choice_users' 		=> '',
		'for_sm' 					=> false,
		'max_width' 				=> false,
		'no_ver' 					=> false,
		'catbox' 					=> '',
		'is_pdf' 					=> false,
		'is_button' 				=> '',
		'max_slides_in_ss_widget' 	=> '',
		'is_random' 				=> false,

	);
}

function wppa_get_randseed( $type = '' ) {
global $wppa_session;
static $volatile_randseed;
static $randseed_modified;

	// This randseed is for the page only
	if ( $type == 'page' ) {
		if ( $volatile_randseed ) {
			$randseed = $volatile_randseed;
		}
		else {
			$volatile_randseed = time() % 7487;
			$randseed = $volatile_randseed;
		}
	}

	// This randseed survives pageloads up to the duration of the session ( usually 1 hour )
	elseif ( $type == 'session' ) {
		$randseed = $wppa_session['id']; //session_randseed'];
	}

	// If the album spec in the querystring differs from the previous, or there is no album arg in the querystring,
	// the random seed is renewed to improve the random behaviour for non-critical operation
	else {
		if ( isset( $wppa_session['randseed'] ) && ! $randseed_modified ) {
			$old_album = isset( $wppa_session['albumspec'] ) ? $wppa_session['albumspec'] : false;
			$new_album = wppa_get_get( 'album' );
			if ( $new_album === false || ( $old_album && ( $old_album != $new_album ) ) ) {
				unset( $wppa_session['randseed'] );	// Forget randseed
			}
		}

		if ( isset( $wppa_session['randseed'] ) ) {
			$randseed = $wppa_session['randseed'];
		}
		else {
			$randseed = time() % 4721;
			$wppa_session['randseed'] = $randseed;

			// Save old album for later
			$wppa_session['albumspec'] = wppa_get_get( 'album' );

			$randseed_modified = true;
		}
	}

	wppa_save_session();

	return $randseed;
}

function wppa_phpinfo( $key = -1 ) {

	echo '<div id="phpinfo" style="width:600px; margin:auto;" >';

		ob_start();
		if ( wppa_switch( 'allow_debug' ) ) phpinfo( -1 ); else phpinfo( 4 );
		$php = ob_get_clean();
		$php = preg_replace( 	array	( 	'@<!DOCTYPE.*?>@siu',
											'@<html.*?>@siu',
											'@</html.*?>@siu',
											'@<head[^>]*?>.*?</head>@siu',
											'@<body.*?>@siu',
											'@</body.*?>@siu',
											'@cellpadding=".*?"@siu',
											'@border=".*?"@siu',
											'@width=".*?"@siu',
											'@name=".*?"@siu',
											'@<font.*?>@siu',
											'@</font.*?>@siu'
										 ),
										'',
										$php );

		$php = str_replace( 'Features','Features</td><td>', $php );

		echo $php;

	echo '</div>';
}

function wppa_errorlog() {

	// Make name
	$filename = WPPA_CONTENT_PATH.'/wppa-depot/admin/error.log';

	// Logfile present?
	if ( ! is_file( $filename ) ) return;

	// Open file
	$file = @ fopen( $filename, 'r' );

	// If unable to open, quit
	if ( ! $file ) return;
	wppa_dbg_msg( 'Start errorlog' );

		$size 		= filesize( $filename );
		$data 		= fread( $file, $size );

		echo str_replace( "\n", '<br />', $data );

	wppa_dbg_msg( 'dbg', 'End errorlog' );
	fclose( $file );
}

// get the url to the plugins image directory
function wppa_get_imgdir( $file = '', $rel = false ) {

	$result = WPPA_URL . '/img/';
	if ( is_ssl() ) $result = str_replace( 'http://', 'https://', $result );
	$result .= $file;

	$result = wppa_make_relative( $result, $rel );
	return $result;
}

// Protect url against making relative
function wppa_protect_relative( $url ) {

	// Init
	$result = $url;

	// Only if feature enabled
	if ( get_option( 'wppa_relative_urls' ) == 'yes' ) {
		$result = str_replace( 'http', 'h t t p ', $result );
	}

	return $result;
}

// Make url relative
function wppa_make_relative( $url, $rel = '' ) {

	// Init
	$result = $url;

	// Can not use wppa_opt(). $wppa_opt is not initialized when called from wppa_set_defaults
	if ( $rel != 'abs' ) {	// Not if absulute is explicitly requested
		if ( get_option( 'wppa_relative_urls' ) == 'yes' || $rel == 'rel' ) {
			if ( isset( $_ENV['HTTP_HOST'] ) ) {
				if ( is_ssl() ) {
					$result = str_replace( 'https://' . $_ENV['HTTP_HOST'], '', $result );
				}
				else {
					$result = str_replace( 'http://' . $_ENV['HTTP_HOST'], '', $result );
				}
			}
		}
	}

	// Repair protected urls
	$result = str_replace( 'h t t p ', 'http', $result );
	$result = str_replace( 'h+t+t+p+', 'http', $result );

	return $result;
}

function wppa_get_wppa_url() {

	$result = WPPA_URL;
	if ( is_ssl() ) $result = str_replace( 'http://', 'https://', $result );
	return $result;
}

// get album order
function wppa_get_album_order( $parent = '0' ) {
global $wppa;

	// Init
    $result = '';

	// Album given ?
	if ( $parent > '0' ) {
		$album = wppa_cache_album( $parent );
		$order = $album['suba_order_by'];
	}
	else {
		$order = '0';
	}
	if ( ! $order ) $order = wppa_opt( 'list_albums_by' );

	switch ( $order ) {
		case '':
		case '0':
			$result = '';
			break;
		case '1':
			$result = 'ORDER BY a_order';
			break;
		case '-1':
			$result = 'ORDER BY a_order DESC';
			break;
		case '2':
			$result = 'ORDER BY name';
			break;
		case '-2':
			$result = 'ORDER BY name DESC';
			break;
		case '3':
			$result = 'ORDER BY RAND( '.wppa_get_randseed().' )';
			break;
		case '5':
			$result = 'ORDER BY timestamp';
			break;
		case '-5':
			$result = 'ORDER BY timestamp DESC';
			break;
		default:
			wppa_dbg_msg( 'Unimplemented album order: '.$order, 'red' );
	}

	return $result;
}

// get photo order
function wppa_get_photo_order( $id = '0', $no_random = false ) {
global $wpdb;
global $wppa;

	// Random overrule?
	if ( wppa( 'is_random' ) ) {
		$result = " ORDER BY RAND(" . time() % 4711 . ")";
		return $result;
	}

	// Album specified?
	if ( wppa_is_int( $id ) && $id > '0' ) {
		$order = wppa_get_album_item( $id, 'p_order_by' );
	}

	// No album specified
	else {
		$order = '0';
	}

	// No order yet? Use default
    if ( ! $order ) {
		$order = wppa_opt( 'list_photos_by' );
	}

    switch ( $order )
    {
	case '':
	case '0':
		$result = '';
		break;
    case '1':
        $result = 'ORDER BY p_order';
        break;
	case '-1':
		$result = 'ORDER BY p_order DESC';
		break;
    case '2':
        $result = 'ORDER BY name';
        break;
    case '-2':
        $result = 'ORDER BY name DESC';
        break;
    case '3':
		if ( $no_random ) $result = 'ORDER BY name';
        else $result = 'ORDER BY RAND( '.wppa_get_randseed().' )';
        break;
    case '-3':
		if ( $no_random ) $result = 'ORDER BY name DESC';
        else $result = 'ORDER BY RAND( '.wppa_get_randseed().' ) DESC';
        break;
	case '4':
		$result = 'ORDER BY mean_rating';
		break;
	case '-4':
		$result = 'ORDER BY mean_rating DESC';
		break;
	case '5':
		$result = 'ORDER BY timestamp';
		break;
	case '-5':
		$result = 'ORDER BY timestamp DESC';
		break;
	case '6':
		$result = 'ORDER BY rating_count';
		break;
	case '-6':
		$result = 'ORDER BY rating_count DESC';
		break;
	case '7':
		$result = 'ORDER BY exifdtm';
		break;
	case '-7':
		$result = 'ORDER BY exifdtm DESC';
		break;

    default:
        wppa_dbg_msg( 'Unimplemented photo order: '.$order, 'red' );
		$result = '';
    }

    return $result;
}


// See if an album is another albums ancestor
function wppa_is_ancestor( $anc, $xchild ) {

	$child = $xchild;
	if ( is_numeric( $anc ) && is_numeric( $child ) ) {
		$parent = wppa_get_parentalbumid( $child );
		while ( $parent > '0' ) {
			if ( $anc == $parent ) return true;
			$child = $parent;
			$parent = wppa_get_parentalbumid( $child );
		}
	}
	return false;
}



function wppa_get_album_id( $name = '', $parent = false ) {
global $wpdb;

	if ( $name == '' ) return '';
    $name = stripslashes( $name );

    $albs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . WPPA_ALBUMS . "` WHERE `name` = %s", $name ), ARRAY_A );

    if ( empty( $albs ) ) {
		return '';
	}
	else {
		if ( $parent === false ) {
			return $albs['0']['id'];
		}
		else {
			foreach ( $albs as $alb ) {
				if ( $alb['a_parent'] == $parent ) {
					return $alb['id'];
				}
			}
		}
	}
}

// Check if an image is more landscape than the width/height ratio set in Table I item 2 and 3
function wppa_is_wider( $x, $y, $refx = '', $refy = '' ) {

	if ( $refx == '' ) {
		$ratioref = wppa_opt( 'fullsize' ) / wppa_opt( 'maxheight' );
	}
	else {
		$ratioref = $refx/$refy;
	}
	$ratio = $x / $y;
	return ( $ratio > $ratioref );
}

// qtrans hook to see if qtrans is installed
function wppa_qtrans_enabled() {
	return ( function_exists( 'qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) );
}

// Output debug message
function wppa_dbg_msg( $txt = '', $color = 'blue', $force = false, $return = false ) {

	if ( wppa( 'debug' ) || $force || ( is_admin() && WPPA_DEBUG ) || ( WPPA_DEBUG && $color == 'red' ) ) {

		$result = 	'<span style="color:' . $color . ';" >' .
						'<small>' .
							'[WPPA+ dbg msg: ' . $txt . ']' .
							'<br />' .
						'</small>' .
					'</span>';

		if ( $return ) {
			return $result;
		}
		else {
			echo $result;
		}
	}
}

// Append debug value to link
function wppa_dbg_url( $link, $js = '' ) {

	$result = $link;

	if ( wppa( 'debug' ) ) {
		if ( strpos( $result, '?' ) ) {
			if ( $js == 'js' ) $result .= '&';
			else $result .= '&amp;';
		}
		else $result .= '?';
		$result .= 'debug=' . wppa( 'debug' );
	}

	return $result;
}

function wppa_get_time_since( $oldtime ) {

	$newtime = time();
	$diff = $newtime - $oldtime;
	if ( $diff < 60 ) {
		return sprintf( _n( '%d second', '%d seconds', $diff, 'wp-photo-album-plus' ), $diff );
	}
	$diff = floor( $diff / 60 );
	if ( $diff < 60 ) {
		return sprintf( _n( '%d minute', '%d minutes', $diff, 'wp-photo-album-plus' ), $diff );
	}
	$diff = floor( $diff / 60 );
	if ( $diff < 24 ) {
		return sprintf( _n( '%d hour', '%d hours', $diff, 'wp-photo-album-plus' ), $diff );
	}
	$diff = floor( $diff / 24 );
	if ( $diff < 7 ) {
		return sprintf( _n( '%d day', '%d days', $diff, 'wp-photo-album-plus' ), $diff );
	}
	elseif ( $diff < 31 ) {
		$t = floor( $diff / 7 );
		return sprintf( _n( '%d week', '%d weeks', $t, 'wp-photo-album-plus' ), $t );
	}
	$diff = floor( $diff / 30.4375 );
	if ( $diff < 12 ) {
		return sprintf( _n( '%d month', '%d months', $diff, 'wp-photo-album-plus' ), $diff );
	}
	$diff = floor( $diff / 12 );
	return sprintf( _n( '%d year', '%d years', $diff, 'wp-photo-album-plus' ), $diff );

}

// See if an album or any album is accessible for the current user
function wppa_have_access( $alb = '0' ) {
global $wpdb;
global $current_user;

//	if ( !$alb ) $alb = 'any'; //return false;

	// See if there is any album accessible
	if ( ! $alb ) { // == 'any' ) {

		// Administrator has always access OR If all albums are public
		if ( wppa_user_is( 'administrator' ) || ! wppa_switch( 'owner_only' ) ) {
			$albs = $wpdb->get_results( "SELECT `id` FROM `".WPPA_ALBUMS."`" );
			if ( $albs ) return true;
			else return false;	// No albums in system
		}

		// Any --- public --- albums?
		$albs = $wpdb->get_results( "SELECT `id` FROM `".WPPA_ALBUMS."` WHERE `owner` = '--- public ---'" );

		if ( $albs ) return true;

		// Any logged out created albums? ( owner = ip )
		$albs = $wpdb->get_results( "SELECT `owner` FROM `".WPPA_ALBUMS."`", ARRAY_A );
		if ( $albs ) foreach ( $albs as $a ) {
			if ( wppa_is_int( str_replace( '.', '', $a['owner'] ) ) ) return true;
		}

		// Any albums owned by this user?
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$user = $current_user->user_login;
			$any_albs = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_ALBUMS."` WHERE `owner` = %s", $user ) );

			if ( $any_albs ) return true;
			else return false;	// No albums for user accessible
		}
	}

	// See for given album data array or album number
	else {

		// Administrator has always access
		if ( wppa_user_is( 'administrator' ) ) return true;	// Do NOT change this into 'wppa_admin', it will enable access to all albums at backend while owners only

		// If all albums are public
		if ( ! wppa_switch( 'owner_only' ) ) return true;

		// Find the owner
		$owner = '';
		if ( is_array( $alb ) ) {
			$owner = $alb['owner'];
		}
		elseif ( is_numeric( $alb ) ) {
			$owner = $wpdb->get_var( $wpdb->prepare( "SELECT `owner` FROM `".WPPA_ALBUMS."` WHERE `id` = %s", $alb ) );
		}

		// -- public --- ?
		if ( $owner == '--- public ---' ) return true;
		if ( wppa_is_int( str_replace( '.', '', $owner ) ) ) return true;	// Owner is an ip

		// Find the user
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			if ( $current_user->user_login == $owner ) return true;
		}
	}
	return false;
}

// See if this image is the default cover image
function wppa_check_coverimage( $id ) {
	if ( wppa_opt( 'default_coverimage_name' ) ) { 	// Feature enabled
		$name = wppa_strip_ext( wppa_get_photo_item( $id, 'filename' ) );
		$dflt = wppa_strip_ext( wppa_opt( 'default_coverimage_name' ) );
		if ( ! strcasecmp( $name, $dflt ) ) {	// Match
			wppa_update_album( array( 	'id'=> wppa_get_photo_item( $id, 'album' ),
										'main_photo' => $id ) );
		}
	}
}

// Get the max size, rounded up to a multiple of 25 px, of all the possible small images
// in order to create the thumbnail file big enough but not too big.
function wppa_get_minisize() {

	// Init
	$result = '100';

	// Thumbnail used / sizes found for...
	$things = array( 	'thumbsize',
						'thumbsize_alt',
						'topten_size',
						'comten_size',
						'thumbnail_widget_size',
						'lasten_size',
						'album_widget_size',
						'featen_size',
						'popupsize',
						'film_thumbsize',
						 );

	// Find the max
	foreach ( $things as $thing ) {
		$tmp = wppa_opt( $thing );
		if ( is_numeric( $tmp ) && $tmp > $result ) {
			$result = $tmp;
		}
	}

	// Cover image size
	if ( wppa_switch( 'coverphoto_responsive' ) ) {
		$tmp = wppa_opt( 'smallsize_percentage' ) * wppa_opt( 'initial_colwidth' ) / 100;
	}
	else {
		$tmp = wppa_opt( 'smallsize' );
	}
	if ( is_numeric( $tmp ) && $tmp > $result ) {
		$result = $tmp;
	}

	// Optionally correct for 'size=height' for album cover images
	$temp = wppa_opt( 'smallsize' );
	if ( wppa_switch( 'coversize_is_height' ) ) {
		$temp = round( $temp * 4 / 3 );		// assume aspectratio 4:3
	}
	if ( is_numeric( $temp ) && $temp > $result ) $result = $temp;

	// Round up to x * 25, so not a small change results in remake
	$result = ceil( $result / 25 ) * 25;

	// Done
	return $result;
}


function wppa_test_for_search( $at_session_start = false ) {
global $wppa;

	// Assume not
	$str = '';

	if ( isset( $_REQUEST['wppa-searchstring'] ) ) {	// wppa+ search
		$str = $_REQUEST['wppa-searchstring'];
	}
	elseif ( isset( $_REQUEST['searchstring'] ) ) {	// wppa+ search
		$str = $_REQUEST['searchstring'];
	}
	elseif ( isset( $_REQUEST['s'] ) ) {				// wp search
		$str = $_REQUEST['s'];
	}

	// Selection boxes present and with a value?
	$t = '';
	for ( $i = 0; $i < 3; $i++ ) {
		if ( isset( $_REQUEST['wppa-searchselbox-' . $i] ) ) {
			if ( $_REQUEST['wppa-searchselbox-' . $i] ) {
				$t .= ' ' . $_REQUEST['wppa-searchselbox-' . $i];
			}
		}
	}
	if ( $t ) {
		$str = $t . ' ' . $str;
		$str = trim( $str );
	}

	// Sanitize
	$ignore = array( '"', "'", '\\', '>', '<', ':', ';', '?', '=', '_', '[', ']', '(', ')', '{', '}' );
	$str = wppa_decode_uri_component( $str );
	$str = str_replace( $ignore, ' ', $str );
	$str = strip_tags( $str );
	$str = stripslashes( $str );
	$str = trim( $str );
	$inter = chr( 226 ).chr( 136 ).chr( 169 );
	$union = chr( 226 ).chr( 136 ).chr( 170 );
	$str = str_replace ( $inter, ' ', $str );
	$str = str_replace ( $union, ',', $str );
	while ( strpos ( $str, '  ' ) !== false ) $str = str_replace ( '  ', ' ', $str );	// reduce spaces
	while ( strpos ( $str, ',,' ) !== false ) $str = str_replace ( ',,', ',', $str );	// reduce commas
	while ( strpos ( $str, ', ' ) !== false ) $str = str_replace ( ', ', ',', $str );	// trim commas
	while ( strpos ( $str, ' ,' ) !== false ) $str = str_replace ( ' ,', ',', $str );	// trim commas

	// Did we do wppa_initialize_runtime() ?
	if ( is_array( $wppa ) && ! $at_session_start ) {
		$wppa['searchstring'] = $str;
		if ( $wppa['searchstring'] && $wppa['occur'] == wppa_opt( 'search_oc' ) && ! wppa_in_widget() ) $wppa['src'] = true;
		else $wppa['src'] = false;
		if ( isset( $_REQUEST['s'] ) ) {
			$wppa['src'] = true;
			global $wppa_session;
			$wppa_session['use_searchstring'] = $str;
			$wppa_session['display_searchstring'] = $str;
			wppa_save_session();
		}
		$result = $str;
	}
	else {
		$result = $str;
	}

	if ( $wppa['src'] ) {
		switch ( wppa_opt( 'search_display_type' ) ) {
			case 'slide':
				$wppa['is_slide'] = '1';
				break;
			case 'slideonly':
				$wppa['is_slide'] = '1';
				$wppa['is_slideonly'] = '1';
				break;
			case 'albums':
				$wppa['albums_only'] = true;
				break;
			default:
				break;
		}
	}

	if ( $wppa['src'] ) {
		if ( wppa_get_get( 'catbox' ) ) {
			$wppa['catbox'] = wppa_get_get( 'catbox' );
		}
		if ( wppa_get_post( 'catbox' ) ) {
			$wppa['catbox'] = wppa_get_post( 'catbox' );
		}
		if ( isset ( $wppa['catbox'] ) ) {
			$wppa['catbox'] = wppa_sanitize_cats( $wppa['catbox'] );
		}
	}

	return $result;
}


function wppa_table_exists( $xtable ) {
global $wpdb;
static $tables;

	// Some sqls do not show tables, benefit of the doubt: assume table exists
	if ( $tables === false ) return true;

	if ( empty( $tables ) ) {
		$tables = $wpdb->get_results( "SHOW TABLES FROM `".DB_NAME."`", ARRAY_A );
	}

	if ( empty( $tables ) ) {
		$tables = false;
		return true;
	}

	// Normal check
	foreach ( $tables as $table ) {
		if ( is_array( $table ) )	foreach ( $table as $item ) {
			if ( strcasecmp( $item, $xtable ) == 0 ) return true;
		}
	}
	return false;
}



function wppa_clear_cache( $force = false ) {
global $cache_path;

	// If wp-super-cache is on board, clear cache
	if ( function_exists( 'prune_super_cache' ) ) {
		prune_super_cache( $cache_path . 'supercache/', true );
		prune_super_cache( $cache_path, true );
	}

	// W3 Total cache
	if ( function_exists( 'w3tc_pgcache_flush' ) ) {
		w3tc_pgcache_flush();
	}

	// SG_CachePress
	/*
	if ( class_exists( 'SG_CachePress_Supercacher' ) ) {
		$c = new SG_CachePress_Supercacher();
		@ $c->purge_cache();
	}
	*/

	// Quick cache
	if ( isset($GLOBALS['quick_cache']) ) {
		$GLOBALS['quick_cache']->clear_cache();
	}

	// Comet cache
	if ( class_exists( 'comet_cache' ) ) {
		comet_cache::clear();
		wppa_log('dbg', 'comet_cache cleared');
	}

	// At a setup or update operation
	// Manually remove the content of wp-content/cache/
	if ( $force ) {
		if ( is_dir( WPPA_CONTENT_PATH.'/cache/' ) ) {
			wppa_tree_empty( WPPA_CONTENT_PATH.'/cache' );
		}
	}
}

// Removes the content of $dir, ignore errors
function wppa_tree_empty( $dir ) {
	$files = glob( $dir.'/*' );
	if ( is_array( $files ) ) foreach ( $files as $file ) {
		$name = basename( $file );
		if ( $name == '.' || $name == '..' ) {}
		elseif ( is_dir( $file ) ) {
			wppa_tree_empty( $file );
//			@ unlink( $file );
		}
		else @ unlink( $file );
	}
}

function wppa_alert( $msg, $reload = false, $escape = true ) {
global $wppa;

	if ( $escape ) {
		$msg = esc_js( $msg );
	}

	if ( ! $reload && $msg ) {
		wppa_add_js_page_data( '<script type="text/javascript">alert( \'' . $msg . '\' );jQuery( "#wppaer" ).html( "" );</script>' );
	}
	elseif ( $reload == 'home' ) {
		echo 	'<script id="wppaer" type="text/javascript" >' .
					( $msg ? 'alert( \'' . $msg . '\' );' : '' ) .
					'jQuery( "#wppaer" ).html( "" );' .
					'document.location.href="' . home_url() . '";' .
				'</script>';
	}
	else {
		echo 	'<script id="wppaer" type="text/javascript" >' .
					( $msg ? 'alert( \'' . $msg . '\' );' : '' ) .
					'jQuery( "#wppaer" ).html( "" );' .
					'document.location.reload( true );' .
				'</script>';
	}
}

// Return the allowed number to upload in an album. -1 = unlimited
function wppa_allow_uploads( $alb = '0' ) {
global $wpdb;

	if ( ! $alb ) return '-1';//'0';

	$album = wppa_cache_album( $alb );

	$limits = $album['upload_limit']; //$wpdb->get_var( $wpdb->prepare( "SELECT `upload_limit` FROM `".WPPA_ALBUMS."` WHERE `id` = %s", $alb ) );

	$temp = explode( '/', $limits );
	$limit_max  = isset( $temp[0] ) ? $temp[0] : '0';
	$limit_time = isset( $temp[1] ) ? $temp[1] : '0';

	if ( ! $limit_max ) return '-1';		// Unlimited max

	if ( ! $limit_time ) {					// For ever
		$curcount = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `album` = %s", $alb ) );
	}
	else {									// Time criterium in place
		$timnow = time();
		$timthen = $timnow - $limit_time;
		$curcount = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `album` = %s AND `timestamp` > %s", $alb, $timthen ) );
	}

	if ( $curcount >= $limit_max ) $result = '0';	// No more allowed
	else $result = $limit_max - $curcount;

	return $result;
}

// Return the allowed number of uploads for a certain user. -1 = unlimited
function wppa_allow_user_uploads() {
global $wpdb;

	// Get the limits
	$limits = wppa_get_user_upload_limits();

	$temp = explode( '/', $limits );
	$limit_max  = isset( $temp[0] ) ? $temp[0] : '0';
	$limit_time = isset( $temp[1] ) ? $temp[1] : '0';

	if ( ! $limit_max ) return '-1';		// Unlimited max

	$user = wppa_get_user( 'login' );
	if ( ! $limit_time ) {					// For ever
		$curcount = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `owner` = %s", $user ) );
	}
	else {									// Time criterium in place
		$timnow = time();
		$timthen = $timnow - $limit_time;
		$curcount = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `owner` = %s AND `timestamp` > %s", $user, $timthen ) );
	}

	if ( $curcount >= $limit_max ) $result = '0';	// No more allowed
	else $result = $limit_max - $curcount;

	return $result;
}
function wppa_get_user_upload_limits() {
global $wp_roles;

	$limits = '';
	if ( is_user_logged_in() ) {

		// Unlimited if you have wppa_upload capabilities
		if ( current_user_can( 'wppa_upload' ) ) {
			$limits = '0/0';
		}
		else {
			$roles = $wp_roles->roles;
			$roles['loggedout'] = '';
			unset ( $roles['administrator'] );
			foreach ( array_keys( $roles ) as $role ) if ( ! $limits ) {
				if ( current_user_can( $role ) ) {
					$limits = get_option( 'wppa_'.$role.'_upload_limit_count', '0' ).'/'.get_option( 'wppa_'.$role.'_upload_limit_time', '0' );
				}
			}
		}
	}
	else {
		$limits = wppa_opt( 'loggedout_upload_limit_count' ).'/'.wppa_opt( 'loggedout_upload_limit_time' );
	}
	return $limits;
}


function wppa_alfa_id( $id = '0' ) {
	return str_replace( array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '0' ), array( 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j' ), $id );
}

// Thanx to the maker of nextgen, but greatly improved
// Usage: wppa_check_memory_limit() return string telling the max upload size
// @1: if false, return array ( 'maxx', 'maxy', 'maxp' )
// @2: width to test an image,
// @3: height to test an image.
// If both present: return true if fit in memory, false if not.
//
//
function wppa_check_memory_limit( $verbose = true, $x = '0', $y = '0' ) {

// ini_set( 'memory_limit', '18M' );	// testing
	if ( ! function_exists( 'memory_get_usage' ) ) return '';
	if ( is_admin() && ! wppa_switch( 'memcheck_admin' ) ) return '';
	if ( ! is_admin() && ! wppa_switch( 'memcheck_frontend' ) ) return '';

	// get memory limit
	$memory_limit = 0;
	$memory_limini = wppa_convert_bytes( ini_get( 'memory_limit' ) );
	$memory_limcfg = wppa_convert_bytes( get_cfg_var( 'memory_limit' ) );

	// find the smallest not being zero
	if ( $memory_limini && $memory_limcfg ) $memory_limit = min( $memory_limini, $memory_limcfg );
	elseif ( $memory_limini ) $memory_limit = $memory_limini;
	else $memory_limit = $memory_limcfg;

	// No data
	if ( ! $memory_limit ) return '';

	// Calculate the free memory
	$free_memory = $memory_limit - memory_get_usage( true );

	// Calculate number of pixels largest target resized image
	if ( wppa_switch( 'resize_on_upload' ) ) {
		$t = wppa_opt( 'resize_to' );
		if ( $t == '0' ) {
			$to['0'] = wppa_opt( 'fullsize' );
			$to['1'] = wppa_opt( 'maxheight' );
		}
		else {
			$to = explode( 'x', $t );
		}
		$resizedpixels = $to['0'] * $to['1'];
	}
	else {
		$resizedpixels = wppa_get_minisize() * wppa_get_minisize() * 3 / 4;
	}

	// Number of bytes per pixel ( found by trial and error )
	//	$factor = '5.60';	//  5.60 for 17M: 386 x 289 ( 0.1 MP ) thumb only
	//	$factor = '5.10';	//  5.10 for 104M: 4900 x 3675 ( 17.2 MP ) thumb only
	$memlimmb = $memory_limit / ( 1024 * 1024 );
	$factor = '6.00' - '0.58' * ( $memlimmb / 104 );	// 6.00 .. 0.58

	// Calculate max size
	$maxpixels = ( $free_memory / $factor ) - $resizedpixels;

	// If obviously faulty: quit silently
	if ( $maxpixels < 0 ) return '';

	// What are we asked for?
	if ( $x && $y ) { 	// Request for check an image
		if ( $x * $y <= $maxpixels ) $result = true;
		else $result = false;
	}
	else {	// Request for tel me what is the limit
		$maxx = sqrt( $maxpixels / 12 ) * 4;
		$maxy = sqrt( $maxpixels / 12 ) * 3;
		if ( $verbose ) {		// Make it a string
			$result = '<br />'.sprintf(  __( 'Based on your server memory limit you should not upload images larger then <strong>%d x %d (%2.1f MP)</strong>' , 'wp-photo-album-plus'), $maxx, $maxy, $maxpixels / ( 1024 * 1024 ) );
		}
		else {					// Or an array
			$result['maxx'] = $maxx;
			$result['maxy'] = $maxy;
			$result['maxp'] = $maxpixels;
		}
	}
	return $result;
}

/**
 * Convert a shorthand byte value from a PHP configuration directive to an integer value. Negative values return 0.
 * @param    string   $value
 * @return   int
 */
function wppa_convert_bytes( $value ) {
    if ( is_numeric( $value ) ) {
        return max( '0', $value );
    } else {
        $value_length = strlen( $value );
        $qty = substr( $value, 0, $value_length - 1 );
        $unit = strtolower( substr( $value, $value_length - 1 ) );
        switch ( $unit ) {
            case 'k':
                $qty *= 1024;
                break;
            case 'm':
                $qty *= 1048576;
                break;
            case 'g':
                $qty *= 1073741824;
                break;
        }
        return max( '0', $qty );
    }
}

function wppa_dbg_cachecounts( $what ) {
static $counters;

	// Init
	$indexes = array( 'albumhit', 'albummis', 'photohit', 'photomis' );
	foreach( $indexes as $i ) {
		if ( ! isset( $counters[$i] ) ) {
			$counters[$i] = 0;
		}
	}

	if ( wppa( 'debug' ) ) {
		switch( $what ) {
			case 'albumhit':
			case 'albummis':
			case 'photohit':
			case 'photomis':
				$counters[$what]++;
				break;
			case 'print':
				if ( ( $counters['albumhit'] + $counters['albummis'] ) && ( $counters['photohit'] + $counters['photomis'] ) ) {
					wppa_dbg_msg(
						'Cache usage: ' .
						'Album hits: ' . $counters['albumhit'] . ', ' .
						'Album misses: ' . $counters['albummis'] .
						' = ' . sprintf( '%6.2f', ( 100 * $counters['albummis'] / ( $counters['albumhit'] + $counters['albummis'] ) ) ) . '%; ' .
						'Photo hits: ' . $counters['photohit'] . ', ' .
						'Photo misses: ' . $counters['photomis'] .
						' = ' . sprintf( '%6.2f', ( 100 * $counters['photomis'] / ( $counters['photohit'] + $counters['photomis'] ) ) ) . '%; '
					);
					wppa_dbg_msg(
						'2nd level cache entries: ' .
						'albums: ' . wppa_cache_album( 'count' ) . ', ' .
						'photos: ' . wppa_cache_photo( 'count' ) . '. ' .
						'NQ='.get_num_queries()
					);
				}
				else {
					wppa_dbg_msg(
						'Cache usage: ' .
						'Album hits: ' . $counters['albumhit'] . ', ' .
						'Album misses: ' . $counters['albummis'] . ', ' .
						'Photo hits: ' . $counters['photohit'] . ', ' .
						'Photo misses: ' . $counters['photomis'] . '.'
					);
				}
				break;
			default:
				wppa_log( 'err', 'Illegal $what in wppa_dbg_cachecounts(): '.$what );
		}
	}
}



function wppa_format_geo( $lat, $lon ) {

	if ( ! $lat && ! $lon ) return '';	// Both zero: clear

	if ( ! $lat ) $lat = '0.0';
	if ( ! $lon ) $lon = '0.0';

	$geo['latitude_format'] = $lat >= '0.0' ? 'N ' : 'S ';
	$d = floor( $lat );
	$m = floor( ( $lat - $d ) * 60 );
	$s = round( ( ( ( $lat - $d ) * 60 - $m ) * 60 ), 4 );
	$geo['latitude_format'] .= $d.'&deg;'.$m.'&#x27;'.$s.'&#x22;';

	$geo['longitude_format'] = $lon >= '0.0' ? 'E ' : 'W ';
	$d = floor( $lon );
	$m = floor( ( $lon - $d ) * 60 );
	$s = round( ( ( ( $lon - $d ) * 60 - $m ) * 60 ), 4 );
	$geo['longitude_format'] .= $d.'&deg;'.$m.'&#x27;'.$s.'&#x22;';

	$geo['latitude'] = $lat;
	$geo['longitude'] = $lon;

	$result = implode( '/', $geo );
	return $result;
}


function wppa_album_select_a( $args ) {
global $wpdb;

	$args = wp_parse_args( $args, array( 	'exclude' 			=> '',
											'selected' 			=> '',
											'disabled' 			=> '',
											'addpleaseselect' 	=> false,
											'addnone' 			=> false,
											'addall' 			=> false,
											'addgeneric'		=> false,
											'addblank' 			=> false,
											'addselected'		=> false,
											'addseparate' 		=> false,
											'addselbox'			=> false,
											'addowner' 			=> false,
											'disableancestors' 	=> false,
											'checkaccess' 		=> false,
											'checkowner' 		=> false,
											'checkupload' 		=> false,
											'addmultiple' 		=> false,
											'addnumbers' 		=> false,
											'path' 				=> false,
											'root' 				=> false,
											'content'			=> false,
											'sort'				=> false,
											'checkarray' 		=> false,
											'array' 			=> array(),
											'optionclass' 		=> '',
											'tagopen' 			=> '',
											'tagname' 			=> '',
											'tagid' 			=> '',
											'tagonchange' 		=> '',
											'multiple' 			=> false,
											'tagstyle' 			=> '',
											 ) );

	// See if new format is used
	if ( $args['tagopen'] ) {
		$is_newformat = true;
	}
	else {
		$is_newformat = false;
	}

	// Provide default selection if no selected given
	if ( $args['selected'] === '' ) {
        $args['selected'] = wppa_get_last_album();
    }

	// See if selection is valid
	if ( ( $args['selected'] == $args['exclude'] ) ||
		 ( $args['checkupload'] && ! wppa_allow_uploads( $args['selected'] ) ) ||
		 ( $args['disableancestors'] && wppa_is_ancestor( $args['exclude'], $args['selected'] ) )
	   ) {
		$args['selected'] = '0';
	}

	// Calculate roughly the expected number of albums to be in the selection and decide if the number is 'many'
	$is_many = false;
	if ( $args['checkarray'] && ! empty( $args['array'] ) ) {
		$c = count( $args['array'] );
		if ( wppa_opt( 'photo_admin_max_albums' ) && $c > wppa_opt( 'photo_admin_max_albums' ) ) {
			$is_many = true;
		}
	}
	elseif ( wppa_has_many_albums() ) {
		$is_many = true;
	}


	// Process the many case
	if ( $is_many ) {

		// Many newformat
		if ( $is_newformat ) {

			$result =
			'<input' .
				' name="' . $args['tagname'] . '"' .
				( $args['tagid'] ? ' id="'  . $args['tagid'] . '"' : '' ) .
				( $args['multiple'] ? '' : ' type="number"' ) .
				' value="' . $args['selected'] . '"' .
				' onchange="' . $args['tagonchange'] . '"' .
				' style="' . $args['tagstyle'] . '"' .
				' title="' .
					esc_attr( __( 'Enter album number', 'wp-photo-album-plus' ) );

					if ( $args['addnone'] ) 	$result .= esc_attr( "\n" . __( '0 for --- none ---' , 'wp-photo-album-plus' ) );
					if ( $args['addall'] ) 		$result .= esc_attr( "\n" . __( '0 for --- all ---' , 'wp-photo-album-plus' ) );
					if ( $args['addall'] ) 		$result .= esc_attr( "\n" . __( '-2 for --- generic ---' , 'wp-photo-album-plus' ) );
					if ( $args['addowner'] ) 	$result .= esc_attr( "\n" . __( '-3 for --- owner/public ---' , 'wp-photo-album-plus' ) );
					if ( $args['addmultiple'] ) $result .= esc_attr( "\n" . __( '-99 for --- multiple see below ---' , 'wp-photo-album-plus' ) );
					if ( $args['addselbox'] ) 	$result .= esc_attr( "\n" . __( '0 for --- a selection box ---' , 'wp-photo-album-plus' ) );
					if ( $args['addseparate'] ) $result .= esc_attr( "\n" . __( '-1 for --- separate ---' , 'wp-photo-album-plus' ) );

					$result .=
					'"' .
			' />' ;

			return $result;
		}

		// Many old format
		else {
			$result = '';

			$selected = $args['selected'] == '0' ? ' selected="selected"' : '';
			if ( $args['addpleaseselect'] ) $result .=
				'<option value="0" disabled="disabled" '.$selected.' >' .
					__( '- select an album -' , 'wp-photo-album-plus' ) .
				'</option>';

			$selected = $args['selected'] == '0' ? ' selected="selected"' : '';
			if ( $args['addnone'] ) $result .=
				'<option value="0"'.$selected.' >' .
					__( '--- none ---' , 'wp-photo-album-plus' ) .
				'</option>';

			$selected = $args['selected'] == '0' ? ' selected="selected"' : '';
			if ( $args['addall'] ) $result .=
				'<option value="0"'.$selected.' >' .
					__( '--- all ---' , 'wp-photo-album-plus' ) .
				'</option>';

			$selected = $args['selected'] == '-2' ? ' selected="selected"' : '';
			if ( $args['addall'] ) $result .=
				'<option value="-2"'.$selected.' >' .
					__( '--- generic ---' , 'wp-photo-album-plus' ) .
				'</option>';

			$selected = $args['selected'] == '-3' ? ' selected="selected"' : '';
			if ( $args['addowner'] ) $result .=
				'<option value="-3"'.$selected.' >' .
					__( '--- owner/public ---' , 'wp-photo-album-plus' ) .
				'</option>';

			$selected = $args['selected'] == '0' ? ' selected="selected"' : '';
			if ( $args['addblank'] ) $result .=
				'<option value="0"'.$selected.' >' .
				'</option>';

			$selected = $args['selected'] == '-99' ? ' selected="selected"' : '';
			if ( $args['addmultiple'] ) $result .=
				'<option value="-99"'.$selected.' >' .
					__( '--- multiple see below ---' , 'wp-photo-album-plus' ) .
				'</option>';

			$selected = $args['selected'] == '0' ? ' selected="selected"' : '';
			if ( $args['addselbox'] ) $result .=
				'<option value="0"'.$selected.' >' .
					__( '--- a selection box ---' , 'wp-photo-album-plus' ) .
				'</option>';
			$selected = $args['selected'] == '-1' ? ' selected="selected"' : '';
			if ( $args['addseparate'] ) $result .=
				'<option value="-1"' . $selected . '>' .
					__( '--- separate ---' , 'wp-photo-album-plus' ) .
				'</option>';

			return $result;
		}
	}

	// Continue processing Not many albums
	else {

		// Get roughly the albums that might be in the selection
		if ( $args['checkarray'] && ! empty( $args['array'] ) ) {

			// $albums = $args['array'];
			$albums = array();

			$temp = $wpdb->get_results( 	"SELECT `id`, `name` " .
											"FROM `" . WPPA_ALBUMS . "` " .
											"WHERE `id` IN (" . implode( ',', $args['array'] ) . ") " .
											( $args['checkowner'] && wppa_switch( 'upload_owner_only' ) && ! wppa_user_is( 'administrator' ) ? "AND `owner` IN ( '--- public ---', '" . wppa_get_user() . "' ) " : "" ) .
											wppa_get_album_order( $args['root'] ),
											ARRAY_A
										);

			// To keep the preciously created sequence intact when an array is given, copy the data from $temp in the sequence of $args['array']
			foreach( $args['array'] as $id ) {
				foreach( $temp as $item ) {
					if ( $item['id'] == $id ) {
						$albums[] = $item;
					}
				}
			}
		}
		else {
			$albums = $wpdb->get_results( 	"SELECT `id`, `name` " .
											"FROM `" . WPPA_ALBUMS . "` " .
											( $args['checkowner'] && wppa_switch( 'upload_owner_only' ) && ! wppa_user_is( 'administrator' ) ? "WHERE `owner` IN ( '--- public ---', '" . wppa_get_user() . "' ) " : "" ) .
											wppa_get_album_order( $args['root'] ),
											ARRAY_A
										);

			// Must be sorted now...
	//		$args['sort'] = true;
		}

		/* Can not add to cache because only "SELECT * " can be added
		// Add to secondary cache
		if ( $albums ) {
			wppa_cache_album( 'add', $albums );
		}
		*/

		if ( $albums ) {
			// Filter for root
			if ( $args['root'] ) {
				$root = $args['root'];
				switch ( $root ) {	// case '0': all, will be skipped as it returns false in 'if ( $args['root'] )'
					case '-2':	// Generic only
					foreach ( array_keys( $albums ) as $albidx ) {
						if ( wppa_is_separate( $albums[$albidx]['id'] ) ) unset ( $albums[$albidx] );
					}
					break;
					case '-1':	// Separate only
					foreach ( array_keys( $albums ) as $albidx ) {
						if ( ! wppa_is_separate( $albums[$albidx]['id'] ) ) unset ( $albums[$albidx] );
					}
					break;
					default:
					foreach ( array_keys( $albums ) as $albidx ) {
						if ( ! wppa_is_ancestor( $root, $albums[$albidx]['id'] ) ) unset ( $albums[$albidx] );
					}
					break;
				}
			}
			// Filter for must have content
			if ( $args['content'] ) {
				foreach ( array_keys( $albums ) as $albidx ) {
					if ( wppa_get_photo_count( $albums[$albidx]['id'] ) <= wppa_get_mincount() ) unset ( $albums[$albidx] );
				}
			}
			// Add paths
			if ( $args['path'] ) {
				$albums = wppa_add_paths( $albums );
			}
			// Or just translate
			else foreach ( array_keys( $albums ) as $index ) {
				$albums[$index]['name'] = __( stripslashes( $albums[$index]['name'] ) );
			}
			// Sort
			if ( $args['sort'] ) $albums = wppa_array_sort( $albums, 'name' );
		}

		// Output
		$result = '';

		// New format
		if ( $is_newformat ) {
			$result .= $args['tagopen'];
		}

		$selected = $args['selected'] == '0' ? ' selected="selected"' : '';
		if ( $args['addpleaseselect'] ) $result .=
			'<option value="0" disabled="disabled" '.$selected.' >' .
				__( '- select an album -' , 'wp-photo-album-plus' ) .
			'</option>';

		$selected = $args['selected'] == '0' ? ' selected="selected"' : '';
		if ( $args['addnone'] ) $result .=
			'<option value="0"'.$selected.' >' .
				__( '--- none ---' , 'wp-photo-album-plus' ) .
			'</option>';

		$selected = $args['selected'] == '0' ? ' selected="selected"' : '';
		if ( $args['addall'] ) $result .=
			'<option value="0"'.$selected.' >' .
				__( '--- all ---' , 'wp-photo-album-plus' ) .
			'</option>';

		$selected = $args['selected'] == '-2' ? ' selected="selected"' : '';
		if ( $args['addall'] ) $result .=
			'<option value="-2"'.$selected.' >' .
				__( '--- generic ---' , 'wp-photo-album-plus' ) .
			'</option>';

		$selected = $args['selected'] == '-3' ? ' selected="selected"' : '';
		if ( $args['addowner'] ) $result .=
			'<option value="-3"'.$selected.' >' .
				__( '--- owner/public ---' , 'wp-photo-album-plus' ) .
			'</option>';

		$selected = $args['selected'] == '0' ? ' selected="selected"' : '';
		if ( $args['addblank'] ) $result .=
			'<option value="0"'.$selected.' >' .
			'</option>';

		$selected = $args['selected'] == '-99' ? ' selected="selected"' : '';
		if ( $args['addmultiple'] ) $result .=
			'<option value="-99"'.$selected.' >' .
				__( '--- multiple see below ---' , 'wp-photo-album-plus' ) .
			'</option>';

		$selected = $args['selected'] == '0' ? ' selected="selected"' : '';
		if ( $args['addselbox'] ) $result .=
			'<option value="0"'.$selected.' >' .
				__( '--- a selection box ---' , 'wp-photo-album-plus' ) .
			'</option>';

		// In case multiple
		if ( strpos( $args['selected'], ',' ) !== false ) {
			$selarr = explode( ',', $args['selected'] );
		}
		else {
			$selarr = array( $args['selected'] );
		}

		if ( $albums ) foreach ( $albums as $album ) {
			if ( ( $args['disabled'] == $album['id'] ) ||
				 ( $args['exclude'] == $album['id'] ) ||
				 ( $args['checkupload'] && ! wppa_allow_uploads( $album['id'] ) ) ||
				 ( $args['disableancestors'] && wppa_is_ancestor( $args['exclude'], $album['id'] ) )
				 ) $disabled = ' disabled="disabled"'; else $disabled = '';
			if ( in_array( $album['id'], $selarr, true ) && ! $disabled ) $selected = ' selected="selected"'; else $selected = '';

			$ok = true; // Assume this will be in the list
			if ( $args['checkaccess'] && ! wppa_have_access( $album['id'] ) ) {
				$ok = false;
			}
			/* This is in the query now
			if ( $args['checkowner'] && wppa_switch( 'upload_owner_only' ) ) { 							// Need to check
				if ( $album['owner'] != wppa_get_user() && $album['owner']  != '--- public ---' ) { 	// Not 'mine'
					if ( ! wppa_user_is( 'administrator' ) ) {											// No admin
						$ok = false;
					}
				}
			}
			*/
			/* This is in the query now
			if ( $args['checkarray'] ) {
				if ( ! in_array( $album['id'], $args['array'] ) ) {
					$ok = false;
				}
			}
			*/
			if ( $selected && $args['addselected'] ) {
				$ok = true;
			}
			if ( $ok ) {
				if ( $args['addnumbers'] ) $number = ' ( '.$album['id'].' )'; else $number = '';
				$result .= '<option class="' . $args['optionclass']. '" value="' . $album['id'] . '" ' . $selected . $disabled . '>' . $album['name'] . $number . '</option>';
			}
		}

		$selected = $args['selected'] == '-1' ? ' selected="selected"' : '';
		if ( $args['addseparate'] ) $result .=
			'<option value="-1"' . $selected . '>' .
				__( '--- separate ---' , 'wp-photo-album-plus' ) .
			'</option>';

		// New format
		if ( $is_newformat ) {
			$result .= '</select>';
		}

		return $result;
	}
}

function wppa_delete_obsolete_tempfiles() {

	// To prevent filling up diskspace, divide lifetime by 2 and repeat removing obsolete files until count <= 10
	$filecount = 101;
	$lifetime = 3600;
	while ( $filecount > 100 ) {
		$files = glob( WPPA_UPLOAD_PATH.'/temp/*' );
		$filecount = 0;
		if ( $files ) {
			$timnow = time();
			$expired = $timnow - $lifetime;
			foreach ( $files as $file ) {
				if ( is_file( $file ) && basename( $file ) != 'index.php' ) {
					$modified = filemtime( $file );
					if ( $modified < $expired ) @ unlink( $file );
					else $filecount++;
				}
			}
		}
		$lifetime /= 2;
	}
}

function wppa_publish_scheduled() {
global $wpdb;

	$last_check = get_option( 'wppa_last_schedule_check', '0' );
	if ( $last_check < ( time() - 300 ) ) {	// Longer than 5 mins ago

		// Publish scheduled photos
		$to_publish = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPPA_PHOTOS."` WHERE `status` = 'scheduled' AND `scheduledtm` < %s", wppa_get_default_scheduledtm() ), ARRAY_A );
		if ( $to_publish ) foreach( $to_publish as $photo ) {
			wppa_update_photo( array( 'id' => $photo['id'], 'scheduledtm' => '', 'status' => 'publish', 'timestamp' => time() ) );
			wppa_update_album( array( 'id' => $photo['album'], 'modified' => time() ) );	// For New indicator on album
			wppa_invalidate_treecounts( $photo['album'] );
		}

		// Publish scheduled albums ( for future use, currently not implemented )
		$to_publish = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPPA_ALBUMS."` WHERE `scheduledtm` <> '' AND `scheduledtm` < %s", wppa_get_default_scheduledtm() ), ARRAY_A );
		if ( $to_publish ) foreach( $to_publish as $album ) {
			wppa_update_album( array( 'id' => $album['id'], 'scheduledtm' => '' ) );
			wppa_invalidate_treecounts( $album['id'] );
		}

		// Delete photos scheduled for deletion
		$to_delete = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPPA_PHOTOS."` WHERE `scheduledel` <> '' AND `scheduledel` < %s", wppa_get_default_scheduledtm() ), ARRAY_A );
		if ( $to_delete ) foreach( $to_delete as $photo ) {
			wppa_delete_photo( $photo['id'] );
		}

		// Update timestamp of this action
		update_option( 'wppa_last_schedule_check', time() );
	}
}

function wppa_add_js_page_data( $txt ) {
global $wppa_js_page_data_file;
global $wppa;

	if ( is_admin() && ! $wppa['ajax'] ) {
		echo $txt;
		return;
	}

	if ( $wppa_js_page_data_file && ! $wppa['ajax'] ) {
		$handle = fopen( $wppa_js_page_data_file, 'ab' );
	}
	else {
		$handle = false;
	}

	if ( $handle ) {
		$txt = str_replace( '<script type="text/javascript">', '', $txt );
		$txt = str_replace( '</script>', '', $txt );
		$txt = str_replace( "\t", '', $txt );
		$txt = str_replace( "\n", '', $txt );
		$txt = trim( $txt );
		if ( $txt ) fwrite( $handle, "\n".$txt );
		fclose( $handle );
	}
	else {
		$wppa['out'] .= $txt;
	}
}

function wppa_add_credit_points( $amount, $reason = '', $id = '', $value = '', $user = '' ) {

	// Anything to do?
	if ( ! $amount ) {
		return;
	}

	// Initialize
	$bret = false;
	if ( $user ) {
		$usr = wppa_get_user_by( 'login', $user );
	}
	else {
		$usr = wp_get_current_user();
	}
	if ( ! $usr ) {
		wppa_log( 'err', 'Could not add points to user '.$user );
		return false;
	}

	// Cube points
	if ( function_exists( 'cp_alterPoints' )  ) {
		cp_alterPoints( $usr->ID, $amount );
		$bret = true;
	}

	// myCred
	if ( function_exists( 'mycred_add' ) ) {
		$entry = $reason . ( $id ? ', '.__('Photo id =', 'wp-photo-album-plus').' '.$id : '' ) . ( $value ? ', '.__('Value =', 'wp-photo-album-plus').' '.$value : '' );
		$bret = mycred_add( str_replace( ' ', '_', $reason ), $usr->ID, $amount, $entry, '', '', '' );
	}

	return $bret;
}
