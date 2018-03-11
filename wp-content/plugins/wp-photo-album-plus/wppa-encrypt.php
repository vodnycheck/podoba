<?php
/* wppa-encrypt.php
* Package: wp-photo-album-plus
*
* Contains all ecryption/decryption logic
* Version 6.6.09
*
*/

// Find a unique photo crypt
function wppa_get_unique_photo_crypt() {

	$cr = wppa_get_unique_crypt( WPPA_PHOTOS );

	return $cr;
}

// Find a unique album crypt
function wppa_get_unique_album_crypt() {

	$cr = wppa_get_unique_crypt( WPPA_ALBUMS );
	while ( $cr == get_option( 'wppa_album_crypt_0', '' ) ||
			$cr == get_option( 'wppa_album_crypt_1', '' ) ||
			$cr == get_option( 'wppa_album_crypt_2', '' ) ||
			$cr == get_option( 'wppa_album_crypt_3', '' ) ||
			$cr == get_option( 'wppa_album_crypt_9', '' )
			) {
				$cr = wppa_get_unique_crypt( WPPA_ALBUMS );
			}

	return $cr;
}

// Find a unique crypt
function wppa_get_unique_crypt( $table ) {
global $wpdb;

	$crypt 	= substr( md5( microtime() ), 0, 12 );
	$dup 	= $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `" . $table . "` WHERE `crypt` = %s", $crypt ) );
	while ( $dup ) {
		sleep( 1 );
		$crypt 	= substr( md5( microtime() ), 0, 12 );
		$dup 	= $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `" . $table . "` WHERE `crypt` = %s", $crypt ) );
	}
	return $crypt;
}

// Convert photo id to crypt
function wppa_encrypt_photo( $id ) {

	// Feature enabled?
	if ( ! wppa_switch( 'use_encrypted_links' ) ) {
		return $id;
	}

	// Yes
	if ( strlen( $id ) < 12 ) {
		$crypt = wppa_get_photo_item( $id, 'crypt' );
	}
	else {
		$crypt = $id; 	// Already encrypted
	}

	return $crypt;
}

// Convert album id to crypt
function wppa_encrypt_album( $album ) {

	// Feature enabled?
	if ( ! wppa_switch( 'use_encrypted_links' ) ) {
		return $album;
	}

	// Encrypted album enumeration must always be expanded
	$album = wppa_expand_enum( $album );

	// Decompose possible album enumeration
	$album_ids 		= strpos( $album, '.' ) === false ? array( $album ) : explode( '.', $album );
	$album_crypts 	= array();
	$i 				= 0;

	// Process all tokens
	while ( $i < count( $album_ids ) ) {
		$id = $album_ids[$i];

		// Check for existance of album, otherwise return dummy
		if ( wppa_is_int( $id ) && $id > '0' && ! wppa_album_exists( $id ) ) {
			$id= '999999';
		}

		switch ( $id ) {
			case '-3':
				$crypt = get_option( 'wppa_album_crypt_3', false );
				break;
			case '-2':
				$crypt = get_option( 'wppa_album_crypt_2', false );
				break;
			case '-1':
				$crypt = get_option( 'wppa_album_crypt_1', false );
				break;
			case '':
			case '0':
				$crypt = get_option( 'wppa_album_crypt_0', false );
				break;
			case '999999':
				$crypt = get_option( 'wppa_album_crypt_9', false );
				break;
			default:
				if ( strlen( $id ) < 12 ) {
					$crypt = wppa_get_album_item( $id, 'crypt' );
				}
				else {
					$crypt = $id; 	// Already encrypted
				}
		}
		$album_crypts[$i] = $crypt;
		$i++;
	}

	// Compose result
	$result = implode( '.', $album_crypts );

	return $result;
}

// Convert photo crypt to id
function wppa_decrypt_photo( $photo, $report_error = true ) {
global $wpdb;

	// Feature enabled?
	if ( ! wppa_switch( 'use_encrypted_links' ) ) {
		return $photo;
	}

	// Already decrypted?
	if ( strlen( $photo ) < 12 ) {
		if ( wppa_switch( 'refuse_unencrypted' ) ) {
			wppa_dbg_msg( __( 'Invalid photo identifier:', 'wp-photo-album-plus' ) . ' ' . $photo, 'red', 'force' );
			return false;
		}
		return $photo;
	}

	// Just do it
	$id = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `" . WPPA_PHOTOS . "` WHERE `crypt` = %s", substr( $photo, 0, 12 ) ) );
	if ( ! $id ) {
		if ( $report_error ) {
			wppa_dbg_msg( 'Invalid photo identifier: ' . $photo, 'red', 'force' );
		}
	}

	return $id;
}

// Convert album crypt to id
function wppa_decrypt_album( $album ) {
global $wpdb;

	// Feature enabled?
	if ( ! wppa_switch( 'use_encrypted_links' ) ) {
		return $album;
	}

	// Yes. Decompose possible album enumeration
	$album_crypts	= explode( '.', $album );
	$album_ids 		= array();
	$i 				= 0;

	// Process all tokens
	while ( $i < count( $album_crypts ) ) {
		$crypt = $album_crypts[$i];
		if ( ! $crypt ) {
			$id = '';
		}
		elseif ( $crypt == get_option( 'wppa_album_crypt_9', false ) ) {
			$id = '999999';
		}
		elseif ( $crypt == get_option( 'wppa_album_crypt_0', false ) ) {
			$id = '0';
		}
		elseif ( $crypt == get_option( 'wppa_album_crypt_1', false ) ) {
			$id = '-1';
		}
		elseif ( $crypt == get_option( 'wppa_album_crypt_2', false ) ) {
			$id = '-2';
		}
		elseif ( $crypt == get_option( 'wppa_album_crypt_2', false ) ) {
			$id = '-3';
		}
		else {

			// Already decrypted?
			if ( strlen( $crypt ) < 12 ) {
				$id = $crypt;
				if ( wppa_switch( 'refuse_unencrypted' ) ) {
					wppa_dbg_msg( __('Invalid album identifier:', 'wp-photo-album-plus') . ' ' . $id, 'red' );
					wppa_log( 'dbg', 'Decrypted album foud wppa_decrypt_album(). id=' . $id, true );
					$id = '-9';
				}
				else {
					return $album; 	// Assume everything already decrypted, return original
				}
			}

			// Just do it
			else {
				$id = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `" . WPPA_ALBUMS . "` WHERE `crypt` = %s", substr( $crypt, 0, 12 ) ) );
				if ( ! $id ) {
					wppa_log( 'Dbg', 'Invalid album identifier: ' . $crypt . ' found in: ' . $album . ' (wppa_decrypt_album)' );
					$id = '-9';
				}
			}
		}
		$album_ids[$i] = $id;
		$i++;
	}

	// Compose result
	$result = implode( '.', $album_ids );

	// Remove not found/deleted albums
	$result = str_replace( '..-9', '', $result );
	$result = str_replace( '.-9', '', $result );
	$result = str_replace( '-9..', '', $result );
	$result = str_replace( '-9.', '', $result );

	return $result;
}

// Encrypt a full url
function wppa_encrypt_url( $url ) {

	// Feature enabled?
	if ( ! wppa_switch( 'use_encrypted_links' ) ) {
		return $url;
	}

	// Querystring present?
	if ( strpos( $url, '?' ) === false ) {
		return $url;
	}

	// Has it &amp; 's ?
	if ( strpos( $url, '&amp;' ) === false ) {
		$hasamp = false;
	}
	else {
		$hasamp = true;
	}

	// Disassemble url
	$temp = explode( '?', $url );

	// Has it a querystring?
	if ( count( $temp ) == '1' ) {
		return $url;
	}

	// Disassemble querystring
	$qarray = explode( '&', str_replace( '&amp;', '&', $temp['1'] ) );

	// Search and replace album and photo ids by crypts
	$i = 0;
	while ( $i < count( $qarray ) ) {
		$item = $qarray[$i];
		$t = explode( '=', $item );
		if ( isset( $t['1'] ) ) {
			switch ( $t['0'] ) {
				case 'wppa-album':
				case 'album':
					if ( ! $t['1'] ) $t['1'] = '0';
					$t['1'] = wppa_encrypt_album( $t['1'] );
					if ( $t['1'] === false ) {
						wppa_dbg_msg( 'Error: Illegal album specification: ' . $item . ' (wppa_encrypt_url)', 'red', 'force' );
						exit;
					}
					break;
				case 'wppa-photo':
				case 'photo':
					$t['1'] = wppa_encrypt_photo( $t['1'] );
					if ( $t['1'] === false ) {
						wppa_dbg_msg( 'Error: Illegal photo specification: ' . $item . ' (wppa_encrypt_url)', 'red', 'force' );
						exit;
					}
					break;
			}
		}
		$item = implode( '=', $t );
		$qarray[$i] = $item;
		$i++;
	}

	// Re-assemble url
	$temp['1'] = implode( '&', $qarray );
	$newurl = implode( '?', $temp );
	if ( $hasamp ) {
		$newurl = str_replace( '&', '&amp;', $newurl );
	}

	return $newurl;
}

