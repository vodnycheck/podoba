<?php
/* wppa-index.php
* Package: wp-photo-album-plus
*
* Contains all indexing functions
* Version 6.8.00
*
*
*/

// Add an item to the index
//
// @1: string. Type. Can be 'album' os 'photo'
// @2: int. Id. The id of the album or the photo.
//
// The actual addition of searchable words and ids into the index db table is handled in a cron job.
// If this function is called real-time, it simply notifys cron to scan all albums or photos on missing items.
function wppa_index_add( $type, $id, $force = false ) {
global $wpdb;
global $acount;
global $pcount;

	if ( $type == 'album' ) {

		// Make sure this album will be re-indexed some time if we are not a cron job
		if ( ! wppa_is_cron() && ! $force ) {
			$wpdb->query( "UPDATE `" . WPPA_ALBUMS . "` SET `indexdtm` = '' WHERE `id` = " . strval( intval( $id ) ) );
		}

		// If there is a cron job running adding to the index and this is not that cron job, do nothing, unless force
		if ( get_option( 'wppa_remake_index_albums_user' ) == 'cron-job' && ! wppa_is_cron() && ! $force ) {
			return;
		}

		// If no user runs the remake proc, start it as cron job
		if ( ! get_option( 'wppa_remake_index_albums_user' ) && ! $force ) {
			wppa_schedule_maintenance_proc( 'wppa_remake_index_albums' );
			return;
		}

		// If album is gone, trigger cron to cleanup the index
		if ( ! wppa_album_exists( $id ) ) {
			wppa_schedule_maintenance_proc( 'wppa_cleanup_index' );
			return;
		}

		// Find the raw text, all qTranslate languages
		$words = wppa_index_get_raw_album( $id );

		// Convert to santized array of indexable words
		$words = wppa_index_raw_to_words( $words );

		// Process all the words to see if they must be added to the index
		foreach ( $words as $word ) {

			// Get the row of the index table where the word is registered.
			$indexline = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPPA_INDEX . "` WHERE `slug` = %s", $word ), ARRAY_A );

			// If this line does not exist yet, create it with only one album number as data
			if ( ! $indexline ) {
				wppa_create_index_entry( array( 'slug' => $word, 'albums' => $id ) );
				if ( ! $force ) {
					wppa_log( 'Cron', 'Adding index slug {b}' . $word . '{/b} for album {b}' . $id . '{/b}' );
				}
			}

			// Index line already exitst, process this album id for this word
			else {

				// Convert existing album ids to an array
				$oldalbums = wppa_index_string_to_array( $indexline['albums'] );

				// If not in yet...
				if ( ! in_array( $id, $oldalbums ) ) {

					// Add it
					$oldalbums[] = $id;

					// Covert to string again
					$newalbums = wppa_index_array_to_string( $oldalbums );

					// Update db
					$wpdb->query( $wpdb->prepare( "UPDATE `" . WPPA_INDEX . "` SET `albums` = %s WHERE `id` = %s", $newalbums, $indexline['id'] ) );

				}
			}
		}
		$acount++;
	}

	elseif ( $type == 'photo' ) {

		// Make sure this photo will be re-indexed some time if we are not a cron job
		if ( ! wppa_is_cron() && ! $force ) {
			$wpdb->query( "UPDATE `" . WPPA_PHOTOS . "` SET `indexdtm` = '' WHERE `id` = " . strval( intval( $id ) ) );
		}

		// If there is a cron job running adding to the index and this is not that cron job, do nothing
		if ( get_option( 'wppa_remake_index_photos_user' ) == 'cron-job' && ! wppa_is_cron() && ! $force ) {
			return;
		}

		// If no user runs the remake proc, start it as cron job
		if ( ! get_option( 'wppa_remake_index_photos_user' ) && ! $force ) {
			wppa_schedule_maintenance_proc( 'wppa_remake_index_photos' );
			return;
		}

		// Find the raw text, all qTranslate languages
		$words = wppa_index_get_raw_photo( $id );

		// Convert to santized array of indexable words
		$words = wppa_index_raw_to_words( $words );

		// Process all the words to see if they must be added to the index
		foreach ( $words as $word ) {

			// Get the row of the index table where the word is registered.
			$indexline = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPPA_INDEX . "` WHERE `slug` = %s", $word ), ARRAY_A );

			// If this line does not exist yet, create it with only one album number as data
			if ( ! $indexline ) {
				wppa_create_index_entry( array( 'slug' => $word, 'photos' => $id ) );
				wppa_log( 'Cron', 'Adding index slug {b}' . $word . '{/b} for photo {b}' . $id . '{/b}' );
			}

			// Index line already exitst, process this photo id for this word
			else {

				// Convert existing album ids to an array
				$oldphotos = wppa_index_string_to_array( $indexline['photos'] );

				// If not in yet...
				if ( ! in_array( $id, $oldphotos ) ) {

					// Add it
					$oldphotos[] = $id;

					// Report addition
//					wppa_log( 'Cron', 'Adding photo # {b}'.$id.'{/b} to index slug {b}'.$word.'{/b}');

					// Covert to string again
					$newphotos = wppa_index_array_to_string( $oldphotos );

					// Update db
					$wpdb->query( $wpdb->prepare( "UPDATE `" . WPPA_INDEX . "` SET `photos` = %s WHERE `id` = %s", $newphotos, $indexline['id'] ) );
				}
			}

		}
		$pcount++;
	}

	else {

		// Log error
		wppa_log( 'Error, unimplemented type {b}' . $type . '{/b} in wppa_index_add().' );
	}
}

// Convert raw data string to indexable word array
// Sanitizes any string and clips it into an array of potential slugs in the index db table.
//
// @1: string. Any test string may contain all kind of garbage.
//
function wppa_index_raw_to_words( $xtext, $no_skips = false, $minlen = '3', $no_excl = true ) {

	// Find chars to be replaced by delimiters (spaces)
	$ignore = array( 	'"', "'", '`', '\\', '>', '<', ',', ':', ';', '?', '=', '_',
						'[', ']', '(', ')', '{', '}', '..', '...', '....', "\n", "\r",
						"\t", '.jpg', '.png', '.gif', '&#039', '&amp',
						'w#cc0', 'w#cc1', 'w#cc2', 'w#cc3', 'w#cc4', 'w#cc5', 'w#cc6', 'w#cc7', 'w#cc8', 'w#cc9',
						'w#cd0', 'w#cd1', 'w#cd2', 'w#cd3', 'w#cd4', 'w#cd5', 'w#cd6', 'w#cd7', 'w#cd8', 'w#cd9',
						'#',
					);
	if ( wppa_switch( 'index_ignore_slash' ) ) {
		$ignore[] = '/';
	}
	if ( $no_excl ) {
		$ignore[] = '!';
	}

	// Find words to skip
	$skips = $no_skips ? array() : get_option( 'wppa_index_skips', array() );

	// Find minimum token length
	$minlen = wppa_opt( 'search_min_length' );

	// Init results array
	$result = array();

	// Process text
	if ( $xtext ) {

		// Sanitize
		// Uses downcase only
		$text = strtolower( $xtext );

		// Convert to real chars/symbols
		$text = html_entity_decode( $text );

		// strip style and script tags inclusive content
		$text = wppa_strip_tags( $text, 'script&style' );

		// Make sure <td>word1</td><td>word2</td> will not endup in 'word1word2', but in 'word1' 'word2'
		$text = str_replace( '>', '> ', $text );

		// Now strip remaining tags without stripping the content
		$text = strip_tags( $text );

		// Strip qTranslate language shortcodes: [:*]
		$text = preg_replace( '/\[:..\]|\[:\]/', ' ', $text );

		// Replace ignorable chars and words by delimiters ( $ignore is an array )
		$text = str_replace( $ignore, ' ', $text );

		// Remove accents
		$text = str_replace( array( 'è', 'é', 'ë'), 'e', $text );
		$text = str_replace( array( 'ò', 'ó', 'ö'), 'o', $text );
		$text = str_replace( array( 'à', 'á', 'ä'), 'a', $text );
		$text = str_replace( array( 'ù', 'ú', 'ü'), 'u', $text );
		$text = str_replace( array( 'ì', 'í', 'ï'), 'i', $text );
		$text = str_replace( 'ç', 'c', $text );

		// Trim
		$text = trim( $text );
		$text = trim( $text, " ./-" );

		// Replace multiple space chars by one space char
		while ( strpos( $text, '  ' ) ) {
			$text = str_replace( '  ', ' ', $text );
		}

		// Convert to array
		$words = explode( ' ', $text );

		// Decide for each word if it is in
		foreach ( $words as $word ) {

			// Trim word
			$word = trim( $word );
			$word = trim( $word, " ./-" );

			// If lare enough and not a word to skip, use it: copy to array $result
			if ( strlen( $word ) >= $minlen && ! in_array( $word, $skips ) ) {
				$result[] = $word;
			}

			// If the word contains (a) dashe(s), also process the fractions before/between/after the dash(es)
			if ( strpos( $word, '-' ) !== false ) {

				// Break word into fragments
				$fracts = explode( '-', $word );
				foreach ( $fracts as $fract ) {

					// Trim
					$fract = trim( $fract );
					$fract = trim( $fract, " ./-" );

					// If large enough and not a word to skip, use it: copy to array $result
					if ( strlen( $fract ) >= $minlen && ! in_array( $fract, $skips ) ) {
						$result[] = $fract;
					}
				}
			}
		}
	}

	// Remove numbers optionaly
	if ( wppa_switch( 'search_numbers_void' ) ) {
		foreach ( array_keys( $result ) as $key ) {

			// Strip leading zeroes
			$t = ltrim( $result[$key], '0' );

			// If nothing left (zoroes only) or numeric, discard it
			if ( ! $t || is_numeric( $t ) ) {
				unset( $result[$key] );
			}
		}
	}

	// Remove dups and sort
	$result = array_unique( $result );

	// Done !
	return $result;

}

// Expand compressed string
function wppa_index_string_to_array( $string ) {

	// Anything?
	if ( ! $string ) return array();

	// Any ranges?
	if ( ! strstr($string, '..') ) {
		$result = explode(',', $string);
		foreach( array_keys( $result ) as $key ) {
			$result[$key] = strval($result[$key]);
		}
		return $result;
	}

	// Yes
	$temp = explode(',', $string);
	$result = array();
	foreach ( $temp as $t ) {
		if ( ! strstr($t, '..') ) $result[] = intval($t);
		else {
			$range = explode('..', $t);
			$from = $range['0'];
			$to = $range['1'];
			while ( $from <= $to ) {
				$result[] = strval($from);
				$from++;
			}
		}
	}

	// Remove dups
	$result = array_unique( $result );

//	foreach( array_keys($result) as $key ) {
//		$result[$key] = strval($result[$key]);
//	}

	return $result;
}

// Compress array ranges and convert to string
function wppa_index_array_to_string( $array ) {

	// Remove empty elements
	foreach( array_keys( $array ) as $idx ) {
		if ( ! $array[$idx] ) {
			unset( $array[$idx] );
		}
	}

	// Remove dups and sort
	$array = array_unique( $array, SORT_NUMERIC );

	// Build string
	$result = '';
	$lastitem = '-1';
	$isrange = false;
	foreach ( $array as $item ) {
		if ( $item == $lastitem+'1' ) {
			$isrange = true;
		}
		else {
			if ( $isrange ) {	// Close range
				$result .= '..'.$lastitem.','.$item;
				$isrange = false;
			}
			else {				// Add single item
				$result .= ','.$item;
			}
		}
		$lastitem = $item;
	}
	if ( $isrange ) {	// Don't forget the last if it ends in a range
		$result .= '..'.$lastitem;
	}
	$result = trim($result, ',');
	return $result;
}

// Remove an item from the index Use this function if you do NOT know the current photo data matches the index info
function wppa_index_remove( $type, $id ) {
global $wpdb;

	// If there is a cron job running cleaning the index and this is not that cron job, do nothing
	if ( get_option( 'wppa_cleanup_index_user' ) == 'cron-job' && ! wppa_is_cron() ) {
		return;
	}

	// If no user runs the cleanup proc, start it as cron job
	if ( ! get_option( 'wppa_cleanup_index_user' ) ) {
		wppa_schedule_maintenance_proc( 'wppa_cleanup_index' );
		return;
	}

	$iam_big = ( $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_INDEX."`" ) > '10000' );	// More than 10.000 index entries,
	if ( $iam_big && $id < '100' ) return;	// Need at least 3 digits to match

	if ( $type == 'album' ) {
		if ( $iam_big ) {
			// This is not strictly correct, the may be 24..28 when searching for 26, this will be missed. However this will not lead to problems during search.
			$indexes = $wpdb->get_results( "SELECT * FROM `".WPPA_INDEX."` WHERE `albums` LIKE '".$id."'", ARRAY_A );
		}
		else {
			// There are too many results on large systems, resulting in a 500 error, but it is strictly correct
			$indexes = $wpdb->get_results( "SELECT * FROM `".WPPA_INDEX."` WHERE `albums` <> ''", ARRAY_A );
		}
		if ( $indexes ) foreach ( $indexes as $indexline ) {
			$array = wppa_index_string_to_array($indexline['albums']);
			foreach ( array_keys($array) as $k ) {
				if ( $array[$k] == intval($id) ) {
					unset ( $array[$k] );
					$string = wppa_index_array_to_string($array);
					$wpdb->query( "UPDATE `".WPPA_INDEX."` SET `albums` = '".$string."' WHERE `id` = ".$indexline['id'] );
				}
			}
		}
	}
	elseif ( $type == 'photo' ) {
		if ( $iam_big ) {
			// This is not strictly correct, the may be 24..28 when searching for 26, this will be missed. However this will not lead to problems during search.
			$indexes = $wpdb->get_results( "SELECT * FROM `".WPPA_INDEX."` WHERE `photos` LIKE '%".$id."%'", ARRAY_A );
		}
		else {
			$indexes = $wpdb->get_results( "SELECT * FROM `".WPPA_INDEX."` WHERE `photos` <> ''", ARRAY_A );
			// There are too many results on large systems, resulting in a 500 error, but it is strictly correct
		}
		if ( $indexes ) foreach ( $indexes as $indexline ) {
			$array = wppa_index_string_to_array($indexline['photos']);
			foreach ( array_keys($array) as $k ) {
				if ( $array[$k] == intval($id) ) {
					unset ( $array[$k] );
					$string = wppa_index_array_to_string($array);
					$wpdb->query( "UPDATE `".WPPA_INDEX."` SET `photos` = '".$string."' WHERE `id` = ".$indexline['id'] );
				}
			}
		}
	}
	else wppa_dbg_msg('Error, unimplemented type in wppa_index_remove().', 'red', 'force');

	$wpdb->query( "DELETE FROM `".WPPA_INDEX."` WHERE `albums` = '' AND `photos` = ''" );	// Cleanup empty entries
}

// Re-index an edited item
function wppa_index_update($type, $id) {
	wppa_index_remove($type, $id);
	wppa_index_add($type, $id);
}

// The words in the new photo description should be left out
function wppa_index_compute_skips() {

	$user_skips 	= wppa_opt( 'search_user_void' );
	$system_skips 	= 'w#name,w#filename,w#owner,w#displayname,w#id,w#tags,w#cats,w#timestamp,w#modified,w#views,w#amx,w#amy,w#amfs,w#url,w#hrurl,w#tnurl,w#pl';
	$words 			= wppa_index_raw_to_words( wppa_opt( 'newphoto_description' ) . ',' . $user_skips . ',' . $system_skips, 'noskips' );
	sort( $words );

	$result = array();
	$last = '';
	foreach ( $words as $word ) {	// Remove dups
		if ( $word != $last ) {
			$result[] = $word;
			$last = $word;
		}
	}
	update_option( 'wppa_index_skips', $result );
}

// Find the raw text for indexing album, all qTranslate languages
//
// @1: int: album id
function wppa_index_get_raw_album( $id ) {

	// Get the album data
	$album = wppa_cache_album( $id );

	// Get words from name and description
	$words = wppa_get_album_desc( $id, array( 'translate' => false ) ) . ' ' . wppa_get_album_name( $id, array( 'translate' => false ) );

	// Optionally album categories
	if ( wppa_switch( 'search_cats' ) ) {
		$words .= ' '.$album['cats'];
	}

	// Strip tags, but prevent cluttering
	$words = str_replace( '<', ' <', $words );
	$words = strip_tags( $words );

	// Done!
	return $words;
}

function wppa_index_get_raw_photo( $id ) {
global $wpdb;

	$thumb 	= wppa_cache_thumb( $id );

	$words = wppa_get_photo_desc( $id, array( 'translate' => false ) ) . ' ' . wppa_get_photo_name( $id, array( 'translate' => false ) );

	if ( wppa_switch( 'search_tags' ) ) $words .= ' '.$thumb['tags'];																					// Tags
	if ( wppa_switch( 'search_comments' ) ) {
		$coms = $wpdb->get_results($wpdb->prepare( "SELECT `comment` FROM `" . WPPA_COMMENTS . "` WHERE `photo` = %s AND `status` = 'approved'", $thumb['id'] ), ARRAY_A );
		if ( $coms ) {
			foreach ( $coms as $com ) {
				$words .= ' ' . stripslashes( $com['comment'] );
			}
		}
	}

	// Strip tags, but prevent cluttering
	$words = str_replace( '<', ' <', $words );
	$words = strip_tags( $words );

	// Done!
	return $words;
}