<?php
/* wppa-wpdb-insert.php
* Package: wp-photo-album-plus
*
* Contains low-level wpdb routines that add new records
* Version 6.8.00
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

// Session
function wppa_create_session_entry( $args ) {
global $wpdb;

	$args = wp_parse_args( (array) $args, array (
					'session' 			=> wppa_get_session_id(),
					'timestamp' 		=> time(),
					'user'				=> wppa_get_user(),
					'ip'				=> $_SERVER['REMOTE_ADDR'],
					'status' 			=> 'valid',
					'data'				=> false,
					'count' 			=> '1'
					) );

	// WPPA_SESSION is auto increment
	$query = $wpdb->prepare( "INSERT INTO `" . WPPA_SESSION ."` 	(

																	`session`,
																	`timestamp`,
																	`user`,
																	`ip`,
																	`status`,
																	`data`,
																	`count`
																)
														VALUES ( %s, %s, %s, %s, %s, %s, %s )",

																$args['session'],
																$args['timestamp'],
																$args['user'],
																$args['ip'],
																$args['status'],
																$args['data'],
																$args['count']
														);
	$iret = $wpdb->query( $query );

	// Succcessful insert: return record id
	if ( $iret ) {
		$result = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `" . WPPA_SESSION . "` WHERE `session` = %s ORDER BY `id` DESC LIMIT 1", wppa_get_session_id() ) );
		return $result;
	}
	else {
		return false;
	}
}

// Index
function wppa_create_index_entry( $args ) {
global $wpdb;

	$args = wp_parse_args( (array) $args, array (

					'slug' 				=> '',
					'albums' 			=> '',
					'photos' 			=> ''
					) );

	// WPPA_INDEX is now AUTO_INCREMENT

	$query = $wpdb->prepare("INSERT INTO `" . WPPA_INDEX . "` 	(
																	`slug`,
																	`albums`,
																	`photos`
																)
														VALUES ( %s, %s, %s )",

																$args['slug'],
																$args['albums'],
																$args['photos']
														);
	$bret = $wpdb->query($query);

	return $bret;
}

// EXIF
function wppa_create_exif_entry( $args ) {
global $wpdb;

	$args = wp_parse_args( (array) $args, array (

					'photo' 			=> '0',
					'tag' 				=> '',
					'description' 		=> '',
					'f_description' 	=> '',
					'status' 			=> '',
					'brand' 			=> '',
					) );

	// WPPA_EXIF is now AUTO_INCREMENT

	$args['description'] = sanitize_text_field( $args['description'] );
	$args['description'] = str_replace( array(chr(0),chr(1),chr(2),chr(3),chr(4),chr(5),chr(6),chr(7)), '', $args['description'] );

	$query = $wpdb->prepare("INSERT INTO `" . WPPA_EXIF . "` 	(
																	`photo`,
																	`tag`,
																	`description`,
																	`f_description`,
																	`status`,
																	`brand`
																)
														VALUES ( %s, %s, %s, %s, %s, %s )",

																$args['photo'],
																$args['tag'],
																$args['description'],
																$args['f_description'],
																$args['status'],
																$args['brand']
														);
	$bret = $wpdb->query($query);

	return $bret;
}

// IPTC
function wppa_create_iptc_entry( $args ) {
global $wpdb;

	$args = wp_parse_args( (array) $args, array (

					'photo' 			=> '0',
					'tag' 				=> '',
					'description' 		=> '',
					'status' 			=> ''
					) );

	// WPPA_IPTC is now AUTO_INCREMENT

	$args['description'] = sanitize_text_field( $args['description'] );
	$args['description'] = str_replace( array(chr(0),chr(1),chr(2),chr(3),chr(4),chr(5),chr(6),chr(7)), '', $args['description'] );

	$query = $wpdb->prepare("INSERT INTO `" . WPPA_IPTC . "` 	(
																	`photo`,
																	`tag`,
																	`description`,
																	`status`
																)
														VALUES ( %s, %s, %s, %s )",

																$args['photo'],
																$args['tag'],
																$args['description'],
																$args['status']
														);
	$bret = $wpdb->query($query);

	return $bret;
}

// Comments
function wppa_create_comments_entry( $args ) {
global $wpdb;

	$args = wp_parse_args( (array) $args, array (
					'id' 				=> '0',
					'timestamp' 		=> time(),
					'photo' 			=> '0',
					'user' 				=> wppa_get_user(),
					'ip'				=> $_SERVER['REMOTE_ADDR'],
					'email' 			=> '',
					'comment' 			=> '',
					'status' 			=> ''
					) );

	if ( ! wppa_is_id_free( WPPA_COMMENTS, $args['id'] ) ) $args['id'] = wppa_nextkey( WPPA_COMMENTS );

	$query = $wpdb->prepare("INSERT INTO `" . WPPA_COMMENTS . "` 	( 	`id`,
																		`timestamp`,
																		`photo`,
																		`user`,
																		`ip`,
																		`email`,
																		`comment`,
																		`status`
																	)
															VALUES ( %s, %s, %s, %s, %s, %s, %s, %s )",
																$args['id'],
																$args['timestamp'],
																$args['photo'],
																$args['user'],
																$args['ip'],
																$args['email'],
																$args['comment'],
																$args['status']
														);
	$iret = $wpdb->query($query);

	if ( $iret ) {
		if ( wppa_switch( 'search_comments' ) ) {
			wppa_update_photo( $args['photo'] );
		}
		return $args['id'];
	}
	else return false;
}

// Rating
function wppa_create_rating_entry( $args ) {
global $wpdb;

	$args = wp_parse_args( (array) $args, array (
					'id' 				=> '0',
					'timestamp' 		=> time(),
					'photo' 			=> '0',
					'value' 			=> '0',
					'user' 				=> '',
					'status' 			=> 'publish'
					) );

	if ( ! wppa_is_id_free( WPPA_RATING, $args['id'] ) ) $args['id'] = wppa_nextkey( WPPA_RATING );

	$query = $wpdb->prepare("INSERT INTO `" . WPPA_RATING . "` ( 	`id`,
																	`timestamp`,
																	`photo`,
																	`value`,
																	`user`,
																	`status`
																)
														VALUES ( %s, %s, %s, %s, %s, %s )",
																$args['id'],
																$args['timestamp'],
																$args['photo'],
																$args['value'],
																$args['user'],
																$args['status']
														);
	$iret = $wpdb->query($query);

	if ( $iret ) return $args['id'];
	else return false;
}

// Photo
function wppa_create_photo_entry( $args ) {
global $wpdb;

	$args = wp_parse_args( (array) $args, array (
					'id'				=> '0',
					'album' 			=> '0',
					'ext' 				=> 'jpg',
					'name'				=> '',
					'description' 		=> '',
					'p_order' 			=> '0',
					'mean_rating'		=> '',
					'linkurl' 			=> '',
					'linktitle' 		=> '',
					'linktarget' 		=> '_self',
					'owner'				=> ( wppa_opt( 'newphoto_owner' ) ? wppa_opt( 'newphoto_owner' ) : wppa_get_user() ),
					'timestamp'			=> time(),
					'status'			=> 'publish',
					'rating_count'		=> '0',
					'tags' 				=> '',
					'alt' 				=> '',
					'filename' 			=> '',
					'modified' 			=> time(),
					'location' 			=> '',
					'views' 			=> '0',
					'page_id' 			=> '0',
					'exifdtm' 			=> '',
					'videox' 			=> '0',
					'videoy' 			=> '0',
					'scheduledtm' 		=> $args['album'] ? $wpdb->get_var( $wpdb->prepare( "SELECT `scheduledtm` FROM `".WPPA_ALBUMS."` WHERE `id` = %s", $args['album'] ) ) : '',
					'scheduledel' 		=> '',
					'custom'			=> '',
					'crypt' 			=> wppa_get_unique_photo_crypt(),
					'magickstack' 		=> '',
					'indexdtm' 			=> '',
					) );

	if ( $args['scheduledtm'] ) $args['status'] = 'scheduled';

	if ( ! wppa_is_id_free( WPPA_PHOTOS, $args['id'] ) ) $args['id'] = wppa_nextkey( WPPA_PHOTOS );

	$query = $wpdb->prepare( "INSERT INTO `" . WPPA_PHOTOS . "` ( 	`id`,
																	`album`,
																	`ext`,
																	`name`,
																	`description`,
																	`p_order`,
																	`mean_rating`,
																	`linkurl`,
																	`linktitle`,
																	`linktarget`,
																	`owner`,
																	`timestamp`,
																	`status`,
																	`rating_count`,
																	`tags`,
																	`alt`,
																	`filename`,
																	`modified`,
																	`location`,
																	`views`,
																	`page_id`,
																	`exifdtm`,
																	`videox`,
																	`videoy`,
																	`scheduledtm`,
																	`scheduledel`,
																	`custom`,
																	`crypt`,
																	`magickstack`,
																	`indexdtm`
																)
														VALUES ( %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )",
																$args['id'],
																$args['album'],
																$args['ext'],
																trim( $args['name'] ),
																trim( $args['description'] ),
																$args['p_order'],
																$args['mean_rating'],
																$args['linkurl'],
																$args['linktitle'],
																$args['linktarget'],
																$args['owner'],
																$args['timestamp'],
																$args['status'],
																$args['rating_count'],
																$args['tags'],
																$args['alt'],
																wppa_sanitize_file_name( $args['filename'] ),	// Security fix
																$args['modified'],
																$args['location'],
																$args['views'],
																$args['page_id'],
																$args['exifdtm'],
																$args['videox'],
																$args['videoy'],
																$args['scheduledtm'],
																$args['scheduledel'],
																$args['custom'],
																$args['crypt'],
																$args['magickstack'],
																$args['indexdtm']
														);
	$iret = $wpdb->query( $query );

	if ( $iret ) {

		// Update index
		wppa_schedule_maintenance_proc( 'wppa_remake_index_photos' );

		return $args['id'];
	}

	else return false;
}

// Album
function wppa_create_album_entry( $args ) {
global $wpdb;

	$args = wp_parse_args( (array) $args, array (
					'id' 				=> '0',
					'name' 				=> __( 'New Album', 'wp-photo-album-plus' ),
					'description' 		=> '',
					'a_order' 			=> '0',
					'main_photo' 		=> wppa_opt( 'main_photo' ),
					'a_parent' 			=> wppa_opt( 'default_parent' ),
					'p_order_by' 		=> '0',
					'cover_linktype' 	=> wppa_opt( 'default_album_linktype' ),
					'cover_linkpage' 	=> '0',
					'owner' 			=> wppa_get_user(),
					'timestamp' 		=> time(),
					'modified' 			=> time(),
					'upload_limit' 		=> wppa_opt( 'upload_limit_count' ).'/'.wppa_opt( 'upload_limit_time' ),
					'alt_thumbsize' 	=> '0',
					'default_tags' 		=> '',
					'cover_type' 		=> '',
					'suba_order_by' 	=> '',
					'views' 			=> '0',
					'cats'				=> '',
					'scheduledtm' 		=> '',
					'crypt' 			=> wppa_get_unique_album_crypt(),
					'treecounts' 		=> serialize( array( 1,0,0,0,0,0,0,0,0,0,0 ) ),
					'indexdtm' 			=> '',
					) );

	if ( ! wppa_is_id_free( WPPA_ALBUMS, $args['id'] ) ) $args['id'] = wppa_nextkey( WPPA_ALBUMS );

	$query = $wpdb->prepare("INSERT INTO `" . WPPA_ALBUMS . "` ( 	`id`,
																	`name`,
																	`description`,
																	`a_order`,
																	`main_photo`,
																	`a_parent`,
																	`p_order_by`,
																	`cover_linktype`,
																	`cover_linkpage`,
																	`owner`,
																	`timestamp`,
																	`modified`,
																	`upload_limit`,
																	`alt_thumbsize`,
																	`default_tags`,
																	`cover_type`,
																	`suba_order_by`,
																	`views`,
																	`cats`,
																	`scheduledtm`,
																	`crypt`,
																	`treecounts`,
																	`indexdtm`
																	)
														VALUES ( %s, %s, %s, %s, %s, %s, %s, %s, %s, %s ,%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )",
																$args['id'],
																trim( $args['name'] ),
																trim( $args['description'] ),
																$args['a_order'],
																$args['main_photo'],
																$args['a_parent'],
																$args['p_order_by'],
																$args['cover_linktype'],
																$args['cover_linkpage'],
																$args['owner'],
																$args['timestamp'],
																$args['modified'],
																$args['upload_limit'],
																$args['alt_thumbsize'],
																$args['default_tags'],
																$args['cover_type'],
																$args['suba_order_by'],
																$args['views'],
																$args['cats'],
																$args['scheduledtm'],
																$args['crypt'],
																$args['treecounts'],
																$args['indexdtm']
														);
	$iret = $wpdb->query( $query );

	if ( $iret ) {
		wppa_invalidate_treecounts( $args['id'] );

		// Update index
		wppa_schedule_maintenance_proc( 'wppa_remake_index_albums' );

		return $args['id'];
	}

	return false;
}

// Find the next available id in a table
//
// Creating a keyvalue of an auto increment primary key incidently returns the value of MAXINT,
// and thereby making it impossible to add a next record.
// This happens when a time-out occurs during an insert query.
// This is not theoretical, i have seen it happen two times on different installations.
// This routine will find a free positive keyvalue larger than any key used, ignoring the fact that the MAXINT key may be used.
function wppa_nextkey( $table ) {
global $wpdb;

	$name = 'wppa_' . $table . '_lastkey';
	$lastkey = get_option( $name, 'nil' );

	if ( $lastkey == 'nil' ) {	// Init option
		$lastkey = $wpdb->get_var( "SELECT `id` FROM `".$table."` WHERE `id` < '9223372036854775806' ORDER BY `id` DESC LIMIT 1" );
		if ( ! is_numeric( $lastkey ) || $lastkey <= '0' ) {
			$lastkey = '0';
		}
		update_option( $name, $lastkey );
	}
//	wppa_dbg_msg( 'Lastkey in ' . $table . ' = ' . $lastkey );

	$result = $lastkey + '1';
	while ( ! wppa_is_id_free( $table, $result ) ) {
		$result++;
	}
	update_option( $name, $result );
	return $result;
}

// Check whether a given id value is not used
function wppa_is_id_free( $table, $id ) {
global $wpdb;

	if ( ! is_numeric( $id ) ) return false;
	if ( ! wppa_is_int( $id ) ) return false;
	if ( $id <= '0' ) return false;

	if ( ! in_array( $table, array( WPPA_ALBUMS, WPPA_PHOTOS, WPPA_COMMENTS, WPPA_RATING, WPPA_EXIF, WPPA_IPTC, WPPA_INDEX, WPPA_SESSION ) ) ) {
		echo 'Unexpected error in wppa_is_id_free()';
		exit();
	}

	$exists = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table` WHERE `id` = %s", $id ), ARRAY_A );
	if ( $exists ) return false;
	return true;
}

