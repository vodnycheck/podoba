<?php
/* wppa-statistics.php
* Package: wp-photo-album-plus
*
* Functions for counts etc
* Common use front and admin
* Version 6.7.02
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

// show system statistics
function wppa_statistics() {

	wppa_out( wppa_get_statistics() );
}
function wppa_get_statistics() {

	$count = wppa_get_total_album_count();
	$y_id = wppa_get_youngest_album_id();
	$y_name = __(wppa_get_album_name($y_id), 'wp-photo-album-plus');
	$p_id = wppa_get_parentalbumid($y_id);
	$p_name = __(wppa_get_album_name($p_id), 'wp-photo-album-plus');

	$result = '<div class="wppa-box wppa-nav" style="text-align: center; '.wppa_wcs('wppa-box').wppa_wcs('wppa-nav').'">';
	$result .= sprintf( _n( 'There is %d photo album', 'There are %d photo albums', $count, 'wp-photo-album-plus'), $count );
	$result .= ' '.__('The last album added is', 'wp-photo-album-plus').' ';
	$result .= '<a href="'.wppa_get_permalink().'wppa-album='.$y_id.'&amp;wppa-cover=0&amp;wppa-occur=1">'.$y_name.'</a>';

	if ($p_id > '0') {
		$result .= __(', a subalbum of', 'wp-photo-album-plus').' ';
		$result .= '<a href="'.wppa_get_permalink().'wppa-album='.$p_id.'&amp;wppa-cover=0&amp;wppa-occur=1">'.$p_name.'</a>';
	}

	$result .= '.</div>';

	return $result;
}

// get number of photos in album
function wppa_get_photo_count( $id = '0', $use_treecounts = false ) {
global $wpdb;

	if ( $use_treecounts && $id ) {
		$treecounts = wppa_get_treecounts_a( $id );
		if ( current_user_can('wppa_moderate') ) {
			$count = $treecounts['selfphotos'] + $treecounts['pendselfphotos'] + $treecounts['scheduledselfphotos'];
		}
		else {
			$count = $treecounts['selfphotos'];
		}
	}
	elseif ( ! $id ) {
		if ( current_user_can('wppa_moderate') ) {
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` " );
		}
		else {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE ( ( `status` <> 'pending' AND `status` <> 'scheduled' ) OR `owner` = %s )", wppa_get_user() ) );
		}
	}
	else {
		if ( current_user_can('wppa_moderate') ) {
			$count = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM " . WPPA_PHOTOS . " WHERE album = %s", $id ) );
		}
		else {
			$count = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM " . WPPA_PHOTOS .
				" WHERE `album` = %s AND ( ( `status` <> 'pending' AND `status` <> 'scheduled' ) OR owner = %s )",
				$id, wppa_get_user() ) );
		}
	}

	// Substract private photos if not logged in and album given
	if ( $id && ! is_user_logged_in() ) {
		$count -= $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `album` = %s AND `status` = 'private' ", $id ) );
	}
	return $count;
}

// get number of albums in album
function wppa_get_album_count( $id, $use_treecounts = false ) {
global $wpdb;

	if ( $use_treecounts && $id ) {
		$treecounts = wppa_get_treecounts_a( $id );
		$count = $treecounts['selfalbums'];
	}
	else {
		$count = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM " . WPPA_ALBUMS . " WHERE a_parent=%s", $id ) );
	}
    return $count;
}

// get number of albums in system
function wppa_get_total_album_count() {
global $wpdb;
static $count;

	if ( ! $count ) {
		$count = $wpdb->get_var("SELECT COUNT(*) FROM `".WPPA_ALBUMS."`");
	}

	return $count;
}

// Get the number of albums the user can upload to
// @: array containing album numbers that are in the pool
function wppa_get_uploadable_album_count( $alb = false ) {
global $wpdb;

	// If album array given, prepare partial where clause to limit album ids.
	if ( is_array( $alb ) ) {
		$where = " `id` IN (" . implode( ',', $alb ) . ") ";
		$where = trim( $where, ',' );
	}
	else {
		$where = false;
	}

	// Admin, do not look to owner
	if ( wppa_user_is( 'administrator' ) ) {
		$result = $wpdb->get_var( 	"SELECT COUNT(*) " .
									"FROM `" . WPPA_ALBUMS . "` " .
									( $where ? "WHERE " . $where : "" )
								);
	}

	// Owner or public
	elseif ( wppa_switch( 'upload_owner_only' ) ) {
		$result = $wpdb->get_var( $wpdb->prepare( 	"SELECT COUNT(*) " .
													"FROM `" . WPPA_ALBUMS . "` " .
													"WHERE `owner` = '--- public ---' OR `owner` = %s" .
													( $where ? "AND " . $where : "" ),
													wppa_get_user()
												)
								);
	}

	// No upload owners only
	else {
		$result = $wpdb->get_var( 	"SELECT COUNT(*) " .
									"FROM `" . WPPA_ALBUMS . "` " .
									( $where ? "WHERE " . $where : "" )
								);
	}

	// Done!
	return $result;
}

// get youngest photo id
function wppa_get_youngest_photo_id() {
global $wpdb;

	$result = $wpdb->get_var(
		"SELECT `id` FROM `" . WPPA_PHOTOS .
		"` WHERE `status` <> 'pending' AND `status` <> 'scheduled' ORDER BY `timestamp` DESC, `id` DESC LIMIT 1" );

	return $result;
}

// get n youngest photo ids
function wppa_get_youngest_photo_ids( $n = '3' ) {
global $wpdb;

	if ( ! wppa_is_int( $n ) ) $n = '3';
	$result = $wpdb->get_col(
		"SELECT `id` FROM `" . WPPA_PHOTOS .
		"` WHERE `status` <> 'pending' AND `status` <> 'scheduled' ORDER BY `timestamp` DESC, `id` DESC LIMIT ".$n );

	return $result;
}

// get youngest album id
function wppa_get_youngest_album_id() {
global $wpdb;

	$result = $wpdb->get_var( "SELECT `id` FROM `" . WPPA_ALBUMS . "` ORDER BY `timestamp` DESC, `id` DESC LIMIT 1" );

	return $result;
}

// get youngest album name
function wppa_get_youngest_album_name() {
global $wpdb;

	$result = $wpdb->get_var( "SELECT `name` FROM `" . WPPA_ALBUMS . "` ORDER BY `timestamp` DESC, `id` DESC LIMIT 1" );

	return stripslashes($result);
}

// Bump Clivkcount
function wppa_bump_clickcount( $id ) {
global $wpdb;
global $wppa_session;

	// Feature enabled?
	if ( ! wppa_switch( 'track_clickcounts' ) ) {
		return;
	}

	// Sanitize input
	if ( ! wppa_is_int( $id ) || $id < '1' ) {
		return;
	}

	// Init clicks in session?
	if ( ! isset ( $wppa_session['click'] ) ) {
		$wppa_session['click'] = array();
	}

	// Remember click and update photodata, only if first time
	if ( ! isset( $wppa_session['click'][$id] ) ) {
		$wppa_session['click'][$id] = true;
		$count = $wpdb->get_var( "SELECT `clicks` FROM `" . WPPA_PHOTOS . "` WHERE `id` = $id" );
		$count++;
		$wpdb->query( "UPDATE `" . WPPA_PHOTOS . "` SET `clicks` = $count WHERE `id` = $id" );

		// Invalidate cache
		wppa_cache_photo( 'invalidate', $id );
	}
}

// Bump Viewcount
function wppa_bump_viewcount( $type, $id ) {
global $wpdb;
global $wppa_session;

	if ( ! wppa_switch( 'track_viewcounts') ) return;

	if ( $type != 'album' && $type != 'photo' ) die ( 'Illegal $type in wppa_bump_viewcount: '.$type);
	if ( $type == 'album' ) {
		if ( strlen( $id ) == 12 ) {
			$id = wppa_decrypt_album( $id );
		}
	}
	else {
		if ( strlen( $id ) == 12 ) {
			$id = wppa_decrypt_photo( $id );
		}
	}

	if ( $id < '1' ) return;			// Not a wppa image
	if ( ! wppa_is_int( $id ) ) return;	// Not an integer

	if ( ! isset($wppa_session[$type]) ) {
		$wppa_session[$type] = array();
	}
	if ( ! isset($wppa_session[$type][$id] ) ) {	// This one not done yest
		$wppa_session[$type][$id] = true;			// Mark as viewed
		if ( $type == 'album' ) $table = WPPA_ALBUMS; else $table = WPPA_PHOTOS;

		$count = $wpdb->get_var("SELECT `views` FROM `".$table."` WHERE `id` = ".$id);
		$count++;

		$wpdb->query("UPDATE `".$table."` SET `views` = ".$count." WHERE `id` = ".$id);
		wppa_dbg_msg('Bumped viewcount for '.$type.' '.$id.' to '.$count, 'red');

		// If 'wppa_owner_to_name'
		if ( $type == 'photo' ) {
			wppa_set_owner_to_name( $id );
		}

		// Mark Treecounts need update
		if ( $type == 'photo' ) {
			$alb = wppa_get_photo_item( $id, 'album' );
			wppa_mark_treecounts( $alb );
		}
	}

	wppa_save_session();
}

function wppa_get_upldr_cache() {

	$result = get_option( 'wppa_upldr_cache', array() );

	return $result;
}

function wppa_flush_upldr_cache( $key = '', $id = '' ) {

	$upldrcache	= wppa_get_upldr_cache();

	foreach ( array_keys( $upldrcache ) as $widget_id ) {

		switch ( $key ) {

			case 'widgetid':
				if ( $id == $widget_id ) {
					unset ( $upldrcache[$widget_id] );
				}

			case 'photoid':
				$usr = wppa_get_photo_item( $id, 'owner');
				if ( isset ( $upldrcache[$widget_id][$usr] ) ) {
					unset ( $upldrcache[$widget_id][$usr] );
				}
				break;

			case 'username':
				$usr = $id;
				if ( isset ( $upldrcache[$widget_id][$usr] ) ) {
					unset ( $upldrcache[$widget_id][$usr] );
				}
				break;

			case 'all':
				$upldrcache = array();
				break;

			default:
				wppa_dbg_msg('Missing key in wppa_flush_upldr_cache()', 'red');
				break;
		}
	}
	update_option('wppa_upldr_cache', $upldrcache);
}

function wppa_get_random_photo_id_from_youngest_album() {
global $wpdb;

	$albums = $wpdb->get_col( "SELECT `id` FROM `" . WPPA_ALBUMS . "` ORDER BY `timestamp` DESC" );
	$found 	= false;
	$count 	= count( $albums );
	$idx 	= 0;
	$result = false;

	while ( ! $found && $idx < $count ) {
		$album = $albums[$idx];
		$result = $wpdb->get_var( "SELECT `id` FROM `" . WPPA_PHOTOS ."` WHERE `album` = $album ORDER BY RAND() LIMIT 1" );
		if ( $result ) {
			$found = true;
		}
		$idx++;
	}

	return $result;
}

// Mark treecounts of album $alb as being update required, default: clear all
function wppa_invalidate_treecounts( $alb = '' ) {
global $wpdb;

	// Sanitize arg
	if ( $alb ) {
		$alb = strval( intval( $alb ) );
	}

	// Album id given
	if ( $alb ) {

		// Flush this albums treecounts
		wppa_mark_treecounts( $alb );
	}

	// No album id, flush them all
	else {
		$iret = $wpdb->query( "UPDATE `" . WPPA_ALBUMS . "` SET `treecounts` = ''" );
		if ( ! $iret ) {
			wppa_log( 'Dbg', 'Unable to clear all treecounts' );
		}
	}
}

// Get and verify correctness of treecount values. Fix if needs update
// Essentially the same as wppa_get_treecounts_a(), but updates if needed
function wppa_verify_treecounts_a( $alb ) {
global $wpdb;

	// Sanitize arg
	if ( $alb ) {
		$alb = strval( intval( $alb ) );
	}

	// Anything to do here?
	if ( ! $alb ) {
		return false;
	}

	// Get data
	$treecounts = wppa_get_treecounts_a( $alb );
	if ( ! $treecounts['needupdate'] ) {
		return $treecounts;
	}

	// Get the ids of the child albums
	$child_ids 	= $wpdb->get_col( 	"SELECT `id` " .
									"FROM `" . WPPA_ALBUMS . "` " .
									"WHERE `a_parent` = $alb"
								);


	// Items to compute
	/*
	'needupdate',
	'selfalbums',
	'treealbums',
	'selfphotos',
	'treephotos',
	'pendselfphotos',
	'pendtreephotos',
	'scheduledselfphotos',
	'scheduledtreephotos',
	'selfphotoviews',
	'treephotoviews'
	*/

	// Do the dirty work
	$result = array();

	// Need Update
	$result['needupdate'] 			= '0';

	// Self albums
	$result['selfalbums'] 			= $wpdb->get_var( 	"SELECT COUNT(*) " .
														"FROM `" . WPPA_ALBUMS . "` " .
														"WHERE `a_parent` = $alb "
													);

	// Tree albums
	$result['treealbums'] 			= $result['selfalbums'];
	foreach( $child_ids as $child ) {

		// Recursively get childrens tree album count and add it
		$child_treecounts = wppa_verify_treecounts_a( $child );
		$result['treealbums'] += $child_treecounts['treealbums'];
	}

	// Self photos
	$result['selfphotos'] 			= $wpdb->get_var( 	"SELECT COUNT(*) " .
														"FROM `" . WPPA_PHOTOS . "` " .
														"WHERE `album` = $alb " .
														"AND `status` <> 'pending' " .
														"AND `status` <> 'scheduled'"
													);

	// Tree photos
	$result['treephotos'] 			= $result['selfphotos'];
	foreach( $child_ids as $child ) {

		// Recursively get childrens tree photo count and add it
		$child_treecounts = wppa_verify_treecounts_a( $child );
		$result['treephotos'] += $child_treecounts['treephotos'];
	}

	// Pending self photos
	$result['pendselfphotos'] 		= $wpdb->get_var( 	"SELECT COUNT(*) " .
														"FROM `" . WPPA_PHOTOS . "` " .
														"WHERE `album` = $alb " .
														"AND `status` = 'pending'"
													);

	// Pending tree photos
	$result['pendtreephotos'] 		= $result['pendselfphotos'];
	foreach( $child_ids as $child ) {

		// Recursively get childrens pend tree photo count and add it
		$child_treecounts = wppa_verify_treecounts_a( $child );
		$result['pendtreephotos'] += $child_treecounts['pendtreephotos'];
	}

	// Scheduled self photos
	$result['scheduledselfphotos'] 	= $wpdb->get_var( 	"SELECT COUNT(*) " .
														"FROM `" . WPPA_PHOTOS . "` " .
														"WHERE `album` = $alb " .
														"AND `status` = 'scheduled'"
													);

	// Scheduled tree photos
	$result['scheduledtreephotos'] 	= $result['scheduledselfphotos'];
	foreach( $child_ids as $child ) {

		// Recursively get childrens scheduled tree photo views and add it
		$child_treecounts = wppa_verify_treecounts_a( $child );
		$result['scheduledtreephotos'] += $child_treecounts['scheduledtreephotos'];
	}

	// Self photo views
	$views = $wpdb->get_col( "SELECT `views` FROM `" . WPPA_PHOTOS . "` WHERE `album` = $alb" );
	$result['selfphotoviews'] 		= array_sum( $views );

	// Tree photo views
	$result['treephotoviews'] 		= $result['selfphotoviews'];
	foreach( $child_ids as $child ) {

		// Recursively get childrens pend tree photo views and add it
		$child_treecounts = wppa_verify_treecounts_a( $child );
		$result['treephotoviews'] += $child_treecounts['treephotoviews'];
	}

	// Save result
	wppa_save_treecount_a( $alb, $result );

	// Done
	return $result;

}

// Set treecounts to need update
function wppa_mark_treecounts( $alb ) {

	// Sanitize arg
	if ( $alb ) {
		$alb = strval( intval( $alb ) );
	}

	// Do it
	if ( $alb ) {
		$treecounts = wppa_get_treecounts_a( $alb );
		if ( is_array( $treecounts ) ) {
			$treecounts['needupdate'] = '1';
			wppa_save_treecount_a( $alb, $treecounts );
			$parent = wppa_get_album_item( $alb, 'a_parent' );

			// Bubble up
			if ( $parent > '0' ) {
				wppa_mark_treecounts( $parent );
			}
		}
	}

	// Schedule cron to fix it up
	wppa_schedule_treecount_update();
}

// Save update treecount array
function wppa_save_treecount_a( $alb, $treecounts ) {
global $wpdb;

	// Sanitize arg
	if ( $alb ) {
		$alb = strval( intval( $alb ) );
	}
	if ( is_array( $treecounts ) ) {
		foreach( array_keys( $treecounts ) as $key ) {
			$treecounts[$key] = strval( intval( $treecounts[$key] ) );
		}
	}

	// Do it
	if ( $alb && is_array( $treecounts ) ) {

		$keys 	= array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10' );
		$result = array_combine( $keys, $treecounts );
		$result = serialize( $result );

		// Manually update. If used wppa_update_album, remake index would be triggered
		$iret = $wpdb->query( "UPDATE `" . WPPA_ALBUMS . "` SET `treecounts` = '$result' WHERE `id` = $alb" );
		wppa_cache_album( 'invalidate', $alb );
	}
}

// Get the treecounts for album $alb
function wppa_get_treecounts_a( $alb, $update = false ) {
global $wpdb;

	// Array index defintions
	$needupdate 			= '0';
	$selfalbums 			= '1';
	$treealbums 			= '2';
	$selfphotos 			= '3';
	$treephotos 			= '4';
	$pendselfphotos 		= '5';
	$pendtreephotos 		= '6';
	$scheduledselfphotos 	= '7';
	$scheduledtreephotos 	= '8';
	$selfphotoviews 		= '9';
	$treephotoviews 		= '10';

	// Sanitize arg
	if ( $alb ) {
		$alb = strval( intval( $alb ) );
	}

	// If album id given
	if ( $alb ) {

		// Get db data field
		$treecount_string = wppa_get_album_item( $alb, 'treecounts' );

		// Convert to array
		if ( $treecount_string ) {
			$treecount_array = unserialize( $treecount_string );
		}
		else {
			$treecount_array = array();
		}

		// Fill in missing elements
		$defaults = array( 1,0,0,0,0,0,0,0,0,0,0 );
		$i = 0;
		$n = count( $defaults );
		while ( $i < $n ) {
			if ( ! isset( $treecount_array[$i] ) ) {
				$treecount_array[$i] = $defaults[$i];
			}
			$i++;
		}

		// Convert numeric keys to alphabetic keys
		$keys = array( 	'needupdate',
						'selfalbums',
						'treealbums',
						'selfphotos',
						'treephotos',
						'pendselfphotos',
						'pendtreephotos',
						'scheduledselfphotos',
						'scheduledtreephotos',
						'selfphotoviews',
						'treephotoviews'
						);

		$result = array_combine( $keys, $treecount_array );

		if ( $result['needupdate'] && $update ) {
			return wppa_verify_treecounts_a( $alb );
		}
	}

	// No album given
	else {
		$result = false;
	}

	// Done
	return $result;
}
