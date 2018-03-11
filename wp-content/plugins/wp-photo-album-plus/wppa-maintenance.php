<?php
/* wppa-maintenance.php
* Package: wp-photo-album-plus
*
* Contains (not yet, but in the future maybe) all the maintenance routines
* Version 6.8.00
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

// For cron:
require_once 'wppa-admin-functions.php';

global $wppa_all_maintenance_slugs;
$wppa_all_maintenance_slugs = array( 	'wppa_remake_index_albums',
										'wppa_remove_empty_albums',
										'wppa_remake_index_photos',
										'wppa_apply_default_photoname_all',
										'wppa_apply_new_photodesc_all',
										'wppa_append_to_photodesc',
										'wppa_remove_from_photodesc',
										'wppa_remove_file_extensions',
										'wppa_readd_file_extensions',
										'wppa_all_ext_to_lower',
										'wppa_regen_thumbs',
										'wppa_rerate',
										'wppa_recup',
										'wppa_format_exif',
										'wppa_file_system',
										'wppa_cleanup',
										'wppa_remake',
										'wppa_list_index',
										'wppa_blacklist_user',
										'wppa_un_blacklist_user',
										'wppa_rating_clear',
										'wppa_viewcount_clear',
										'wppa_iptc_clear',
										'wppa_exif_clear',
										'wppa_watermark_all',
										'wppa_create_all_autopages',
										'wppa_delete_all_autopages',
										'wppa_leading_zeros',
										'wppa_add_gpx_tag',
										'wppa_add_hd_tag',
										'wppa_optimize_ewww',
										'wppa_comp_sizes',
										'wppa_edit_tag',
										'wppa_sync_cloud',
										'wppa_sanitize_tags',
										'wppa_sanitize_cats',
										'wppa_custom_album_proc',
										'wppa_custom_photo_proc',
										'wppa_crypt_photos',
										'wppa_crypt_albums',
										'wppa_create_o1_files',
										'wppa_owner_to_name_proc',
										'wppa_move_all_photos',
										'wppa_cleanup_index',
									);

global $wppa_cron_maintenance_slugs;
$wppa_cron_maintenance_slugs = array(	'wppa_remake_index_albums',
										'wppa_remake_index_photos',
										'wppa_regen_thumbs',
										'wppa_rerate',
										'wppa_recup',
										'wppa_format_exif',
										'wppa_cleanup_index',
										'wppa_remake',
										'wppa_rerate',
										'wppa_comp_sizes',
										'wppa_add_gpx_tag',
										'wppa_add_hd_tag',

									);

// Main maintenace module
// Must return a string like: errormesssage||$slug||status||togo
function wppa_do_maintenance_proc( $slug ) {
global $wpdb;
global $wppa_session;
global $wppa_supported_video_extensions;
global $wppa_supported_audio_extensions;
global $wppa_all_maintenance_slugs;
global $wppa_timestamp_start;

	// Are we temp disbled?
	if ( wppa_is_cron() && wppa_switch( 'maint_ignore_cron' ) ) {
		return;
	}


	// Check for multiple maintenance procs
	if ( ! wppa_switch( 'maint_ignore_concurrency_error' ) && ! wppa_is_cron() ) {

		foreach ( array_keys( $wppa_all_maintenance_slugs ) as $key ) {
			if ( $wppa_all_maintenance_slugs[$key] != $slug ) {
				if ( get_option( $wppa_all_maintenance_slugs[$key].'_togo', '0') ) { 	// Process running
					return __('You can run only one maintenance procedure at a time', 'wp-photo-album-plus').'||'.$slug.'||'.__('Error', 'wp-photo-album-plus').'||'.''.'||'.'';
				}
			}
		}
	}

	// Lock this proc
	if ( wppa_is_cron() ) {
		update_option( $slug.'_user', 'cron-job' );
		update_option( $slug.'_lasttimestamp', time() );
	}
	else {
		update_option( $slug.'_user', wppa_get_user() );
	}

	// Extend session
	wppa_extend_session();

	// Initialize
	if ( wppa_is_cron() ) {
		$ini = ini_get( 'max_execution_time' );
		if ( ! $ini ) {
			$ini = 30;
		}
		if ( $wppa_timestamp_start ) {
			$endtime = $wppa_timestamp_start + $ini - 5;
		}
		else {
			$endtime = time() + 5;
		}
	}
	else {
		$endtime = time() + 5;
	}
	$chunksize 	= '1000';
	$lastid 	= strval( intval ( get_option( $slug . '_last', '0' ) ) );
	$errtxt 	= '';
	$id 		= '0';
	$topid 		= '0';
	$reload 	= '';
	$to_delete_from_cloudinary = array();
	$aborted 	= false;

	if ( ! isset( $wppa_session ) ) $wppa_session = array();
	if ( ! isset( $wppa_session[$slug.'_fixed'] ) )   $wppa_session[$slug.'_fixed'] = '0';
	if ( ! isset( $wppa_session[$slug.'_added'] ) )   $wppa_session[$slug.'_added'] = '0';
	if ( ! isset( $wppa_session[$slug.'_deleted'] ) ) $wppa_session[$slug.'_deleted'] = '0';
	if ( ! isset( $wppa_session[$slug.'_skipped'] ) ) $wppa_session[$slug.'_skipped'] = '0';

	if ( $lastid == '0' ) {
		$wppa_session[$slug.'_fixed'] = '0';
		$wppa_session[$slug.'_deleted'] = '0';
		$wppa_session[$slug.'_skipped'] = '0';
	}

	wppa_save_session();

	// Pre-processing needed?
	if ( $lastid == '0' ) {
		if (  wppa_is_cron() ) {
			wppa_log( 'Cron', '{b}' . $slug . '{/b} started. Allowed runtime: ' . ( $endtime - time() ) . 's. Cron id = ' . wppa_is_cron() );
		}
		else {
			wppa_log( 'Obs', 'Maintenance proc {b}' . $slug . '{/b} started. Allowed runtime: ' . ( $endtime - time() ) . 's.' );
		}
		switch ( $slug ) {

			case 'wppa_remake_index_albums':

				// Pre-Clear album index only if not cron
				if ( ! wppa_is_cron() ) {
					$wpdb->query( "UPDATE `" . WPPA_INDEX . "` SET `albums` = ''" );
					$wpdb->query( "UPDATE `" . WPPA_ALBUMS . "` SET `indexdtm` = ''" );
				}
				wppa_index_compute_skips();
				break;

			case 'wppa_remake_index_photos':

				// Pre-Clear photo index only if not cron
				if ( ! wppa_is_cron() ) {
					$wpdb->query( "UPDATE `" . WPPA_INDEX . "` SET `photos` = ''" );
					$wpdb->query( "UPDATE `" . WPPA_PHOTOS . "` SET `indexdtm` = ''" );
				}
				wppa_index_compute_skips();
				break;

			case 'wppa_cleanup_index':
				wppa_index_compute_skips();
				break;

			case 'wppa_recup':

				// Pre-Clear exif and iptc tables only if not cron
				if ( ! wppa_is_cron() ) {
					$wpdb->query( "TRUNCATE TABLE `" . WPPA_IPTC . "`" );
					$wpdb->query( "TRUNCATE TABLE `" . WPPA_EXIF . "`" );
				}
				break;
			case 'wppa_file_system':
				if ( get_option('wppa_file_system') == 'flat' ) update_option( 'wppa_file_system', 'to-tree' );
				if ( get_option('wppa_file_system') == 'tree' ) update_option( 'wppa_file_system', 'to-flat' );
				break;
			case 'wppa_cleanup':
				$orphan_album = get_option( 'wppa_orphan_album', '0' );
				$album_exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM`".WPPA_ALBUMS."` WHERE `id` = %s", $orphan_album ) );
				if ( ! $album_exists ) $orphan_album = false;
				if ( ! $orphan_album ) {
					$orphan_album = wppa_create_album_entry( array( 'name' => __('Orphan photos', 'wp-photo-album-plus'), 'a_parent' => '-1', 'description' => __('This album contains refound lost photos', 'wp-photo-album-plus') ) );
					update_option( 'wppa_orphan_album', $orphan_album );
				}
				break;
			case 'wppa_sync_cloud':
				if ( ! wppa_get_present_at_cloudinary_a() ) {
					// Still Initializing
					$status = 'Initializing';
					if ( ! isset( $wppa_session['fun-count'] ) ) {
						$wppa_session['fun-count'] = 0;
					}
					$wppa_session['fun-count'] = ( $wppa_session['fun-count'] + 1 ) % 3;
					for ( $i=0; $i < $wppa_session['fun-count']; $i++ ) $status .= '.';
					$togo   = 'all';
					$reload = false;
					echo '||'.$slug.'||'.$status.'||'.$togo.'||'.$reload;
					wppa_exit();
				}
				break;
			case 'wppa_crypt_albums':
				update_option( 'wppa_album_crypt_0', wppa_get_unique_album_crypt() );
				update_option( 'wppa_album_crypt_1', wppa_get_unique_album_crypt() );
				update_option( 'wppa_album_crypt_2', wppa_get_unique_album_crypt() );
				update_option( 'wppa_album_crypt_3', wppa_get_unique_album_crypt() );
				update_option( 'wppa_album_crypt_9', wppa_get_unique_album_crypt() );
				break;
			case 'wppa_owner_to_name_proc':
				if ( ! wppa_switch( 'owner_to_name' ) ) {
					echo __( 'Feature must be enabled in Table IV-A28 first', 'wp-photo-album-plus' ).'||'.$slug.'||||||';
					wppa_exit();
				}
				break;
			case 'wppa_move_all_photos':
				$fromalb = get_option( 'wppa_move_all_photos_from' );
				if ( ! wppa_album_exists( $fromalb ) ) {
					echo sprintf(__( 'From album %d does not exist', 'wp-photo-album-plus' ), $fromalb );
					wppa_exit();
				}
				$toalb = get_option( 'wppa_move_all_photos_to' );
				if ( ! wppa_album_exists( $toalb ) ) {
					echo sprintf(__( 'To album %d does not exist', 'wp-photo-album-plus' ), $toalb );
					wppa_exit();
				}
				if ( $fromalb == $toalb ) {
					echo __( 'From and To albums are identical', 'wp-photo-album-plus' );
					wppa_exit();
				}
				break;

		}
		wppa_save_session();
	}

	if ( $lastid != '0' ) {
		if (  wppa_is_cron() ) {
			wppa_log( 'Cron', '{b}' . $slug . '{/b} continued at item # ' . ( $lastid + 1 ) . '. Allowed runtime: ' . ( $endtime - time() ) . 's. Cron id = ' . wppa_is_cron() );
		}
	}

	// Dispatch on albums / photos / single actions

	switch ( $slug ) {

		case 'wppa_remake_index_albums':
		case 'wppa_remove_empty_albums':
		case 'wppa_sanitize_cats':
		case 'wppa_crypt_albums':
		case 'wppa_custom_album_proc':

			// Process albums
			$table 		= WPPA_ALBUMS;

			if ( $slug == 'wppa_remake_index_albums' ) {
				$topid 		= $wpdb->get_var( "SELECT `id` FROM `".WPPA_ALBUMS."` ORDER BY `id` DESC LIMIT 1" );
				$albums 	= $wpdb->get_results( 	"SELECT * FROM `" . WPPA_ALBUMS . "` " .
													"WHERE `id` > " . $lastid . " " .
													"AND `indexdtm` < `modified` " .
													"ORDER BY `id` " .
													"LIMIT 100", ARRAY_A );
			}
			else {
				$topid 		= $wpdb->get_var( "SELECT `id` FROM `".WPPA_ALBUMS."` ORDER BY `id` DESC LIMIT 1" );
				$albums 	= $wpdb->get_results( "SELECT * FROM `".WPPA_ALBUMS."` WHERE `id` > ".$lastid." ORDER BY `id` LIMIT 100", ARRAY_A );
			}

			wppa_cache_album( 'add', $albums );

			if ( $albums ) foreach ( $albums as $album ) {

				$id = $album['id'];

				switch ( $slug ) {

					case 'wppa_remake_index_albums':

						if ( wppa_is_cron() ) {
							wppa_index_add( 'album', $id );
							wppa_log( 'Cron', 'Indexed album {b}' . $id . '{/b}' );
						}
						else {
							wppa_index_add( 'album', $id, 'force' );
						}
						$wpdb->query( "UPDATE `" . WPPA_ALBUMS . "` SET `indexdtm` = '" . time() . "' WHERE `id` = $id" );
						break;

					case 'wppa_remove_empty_albums':
						$p = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `album` = %s", $id ) );
						$a = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_ALBUMS."` WHERE `a_parent` = %s", $id ) );
						if ( ! $a && ! $p ) {
							$wpdb->query( $wpdb->prepare( "DELETE FROM `".WPPA_ALBUMS."` WHERE `id` = %s", $id ) );
							wppa_delete_album_source( $id );
							wppa_invalidate_treecounts( $id );
							wppa_index_remove( 'album', $id );
							wppa_clear_catlist();
						}
						break;

					case 'wppa_sanitize_cats':
						$cats = $album['cats'];
						if ( $cats ) {
							wppa_update_album( array( 'id' => $album['id'], 'cats' => wppa_sanitize_tags( $cats ) ) );
						}
						break;

					case 'wppa_crypt_albums':
						wppa_update_album( array( 'id' => $album['id'], 'crypt' => wppa_get_unique_album_crypt() ) );
						break;

					case 'wppa_custom_album_proc':
						$file = WPPA_UPLOAD_PATH . '/procs/wppa_custom_album_proc.php';
						include $file;
						break;

				}
				// Test for timeout / ready
				$lastid = $id;
				update_option( $slug.'_last', $lastid );
				if ( time() > $endtime ) break; 	// Time out
			}
			else {	// Nothing to do, Done anyway
				$lastid = $topid;
			}
			break;	// End process albums

		case 'wppa_remake_index_photos':
		case 'wppa_apply_default_photoname_all':
		case 'wppa_apply_new_photodesc_all':
		case 'wppa_append_to_photodesc':
		case 'wppa_remove_from_photodesc':
		case 'wppa_remove_file_extensions':
		case 'wppa_readd_file_extensions':
		case 'wppa_all_ext_to_lower':
		case 'wppa_regen_thumbs':
		case 'wppa_rerate':
		case 'wppa_recup':
		case 'wppa_format_exif':
		case 'wppa_file_system':
		case 'wppa_cleanup':
		case 'wppa_remake':
		case 'wppa_watermark_all':
		case 'wppa_create_all_autopages':
		case 'wppa_delete_all_autopages':
		case 'wppa_leading_zeros':
		case 'wppa_add_gpx_tag':
		case 'wppa_add_hd_tag':
		case 'wppa_optimize_ewww':
		case 'wppa_comp_sizes':
		case 'wppa_edit_tag':
		case 'wppa_sync_cloud':
		case 'wppa_sanitize_tags':
		case 'wppa_crypt_photos':
		case 'wppa_custom_photo_proc':
		case 'wppa_create_o1_files':
		case 'wppa_owner_to_name_proc':
		case 'wppa_move_all_photos':

			// Process photos
			$table 		= WPPA_PHOTOS;

			if ( $slug == 'wppa_cleanup' ) {
				$topid 		= get_option( 'wppa_'.WPPA_PHOTOS.'_lastkey', '1' ) * 10;
				$photos 	= array();
				for ( $i = ( $lastid + '1'); $i <= $topid; $i++ ) {
					$photos[]['id'] = $i;
				}
			}
			elseif ( $slug == 'wppa_remake_index_photos' ) {
				$topid 		= $wpdb->get_var( "SELECT `id` FROM `".WPPA_PHOTOS."` ORDER BY `id` DESC LIMIT 1" );
				$photos 	= $wpdb->get_results( 	"SELECT * FROM `" . WPPA_PHOTOS . "` " .
													"WHERE `id` > " . $lastid . " " .
													"AND `indexdtm` < `modified` " .
													"ORDER BY `id` " .
													"LIMIT " . $chunksize, ARRAY_A );
			}
			else {
				$topid 		= $wpdb->get_var( "SELECT `id` FROM `".WPPA_PHOTOS."` ORDER BY `id` DESC LIMIT 1" );
				$photos 	= $wpdb->get_results( "SELECT * FROM `".WPPA_PHOTOS."` WHERE `id` > ".$lastid." ORDER BY `id` LIMIT ".$chunksize, ARRAY_A );
			}

			if ( $slug == 'wppa_edit_tag' ) {
				$edit_tag 	= get_option( 'wppa_tag_to_edit' );
				$new_tag 	= get_option( 'wppa_new_tag_value' );
			}

			if ( ! $photos && $slug == 'wppa_file_system' ) {
				$fs = get_option( 'wppa_file_system' );
				if ( $fs == 'to-tree' ) {
					$to = 'tree';
				}
				elseif ( $fs == 'to-flat' ) {
					$to = 'flat';
				}
				else {
					$to = $fs;
				}
			}

			if ( $photos ) foreach ( $photos as $photo ) {
				$thumb = $photo;	// Make globally known

				$id = $photo['id'];

				switch ( $slug ) {

					case 'wppa_remake_index_photos':

						if ( wppa_is_cron() ) {
							wppa_index_add( 'photo', $id );
							wppa_log( 'Cron', 'Indexed photo {b}' . $id . '{/b}' );
						}
						else {
							wppa_index_add( 'photo', $id, 'force' );
						}
						$wpdb->query( "UPDATE `" . WPPA_PHOTOS . "` SET `indexdtm` = '" . time() . "' WHERE `id` = $id" );
						break;

					case 'wppa_apply_default_photoname_all':
						$filename 	= wppa_get_photo_item( $id, 'filename' );
						wppa_set_default_name( $id, $filename );
						break;

					case 'wppa_apply_new_photodesc_all':
						$value = wppa_opt( 'newphoto_description' );
						$description = trim( $value );
						if ( $description != $photo['description'] ) {	// Modified photo description
							$wpdb->query( $wpdb->prepare( "UPDATE `".WPPA_PHOTOS."` SET `description` = %s WHERE `id` = %s", $description, $id ) );
						}
						break;

					case 'wppa_append_to_photodesc':
						$value = trim( wppa_opt( 'append_text' ) );
						if ( ! $value ) return 'Unexpected error: missing text to append||'.$slug.'||Error||0';
						$description = rtrim( $photo['description'] . ' '. $value );
						if ( $description != $photo['description'] ) {	// Modified photo description
							$wpdb->query( $wpdb->prepare( "UPDATE `".WPPA_PHOTOS."` SET `description` = %s WHERE `id` = %s", $description, $id ) );
						}
						break;

					case 'wppa_remove_from_photodesc':
						$value = trim( wppa_opt( 'remove_text' ) );
						if ( ! $value ) return 'Unexpected error: missing text to remove||'.$slug.'||Error||0';
						$description = rtrim( str_replace( $value, '', $photo['description'] ) );
						if ( $description != $photo['description'] ) {	// Modified photo description
							$wpdb->query( $wpdb->prepare( "UPDATE `".WPPA_PHOTOS."` SET `description` = %s WHERE `id` = %s", $description, $id ) );
						}
						break;

					case 'wppa_remove_file_extensions':
						if ( ! wppa_is_video( $id ) ) {
							$name = str_replace( array( '.jpg', '.png', '.gif', '.JPG', '.PNG', '.GIF' ), '', $photo['name'] );
							if ( $name != $photo['name'] ) {	// Modified photo name
								$wpdb->query( $wpdb->prepare( "UPDATE `".WPPA_PHOTOS."` SET `name` = %s WHERE `id` = %s", $name, $id ) );
							}
						}
						break;

					case 'wppa_readd_file_extensions':
						if ( ! wppa_is_video( $id ) ) {
							$name = str_replace( array( '.jpg', '.png', 'gif', '.JPG', '.PNG', '.GIF' ), '', $photo['name'] );
							if ( $name == $photo['name'] ) { 	// Name had no fileextension
								$wpdb->query( $wpdb->prepare( "UPDATE `".WPPA_PHOTOS."` SET `name` = %s WHERE `id` = %s", $name.'.'.$photo['ext'], $id ) );
							}
						}
						break;

					case 'wppa_all_ext_to_lower':
						$EXT = wppa_get_photo_item( $id, 'ext' );
						$ext = strtolower( $EXT );
						if ( $EXT != $ext ) {
							wppa_update_photo( array( 'id' => $id, 'ext' => $ext ) );
							$fixed_this = true;
						}
						$EXT = strtoupper( $ext );
						$rawpath = wppa_strip_ext( wppa_get_photo_path( $id, false ) );
						$rawthumb = wppa_strip_ext( wppa_get_thumb_path( $id, false ) );
						$fixed_this = false;
						if ( wppa_is_multi( $id ) ) {

						}
						else {
							if ( is_file( $rawpath . '.' . $EXT ) ) {
								if ( is_file( $rawpath . '.' . $ext ) ) {
									unlink( $rawpath . '.' . $EXT );
								}
								else {
									rename( $rawpath . '.' . $EXT, $rawpath . '.' . $ext );
								}
								$fixed_this = true;
							}
							if ( is_file( $rawthumb . '.' . $EXT ) ) {
								if ( is_file( $rawthumb . '.' . $ext ) ) {
									unlink( $rawthumb . '.' . $EXT );
								}
								else {
									rename( $rawthumb . '.' . $EXT, $rawthumb . '.' . $ext );
								}
								$fixed_this = true;
							}
						}
						if ( $fixed_this ) {
							$wppa_session[$slug.'_fixed']++;
						}
						else {
							$wppa_session[$slug.'_skipped']++;
						}
						break;

					case 'wppa_regen_thumbs':
						if ( ! wppa_is_video( $id ) || file_exists( wppa_get_photo_path( $id ) ) ) {
							wppa_create_thumbnail( $id );
						}
						break;

					case 'wppa_rerate':
						wppa_rate_photo( $id );
						break;

					case 'wppa_recup':
						$a_ret = wppa_recuperate( $id );
						if ( $a_ret['iptcfix'] ) $wppa_session[$slug.'_fixed']++;
						if ( $a_ret['exiffix'] ) $wppa_session[$slug.'_fixed']++;
						break;

					case 'wppa_format_exif':
						wppa_fix_exif_format( $id );
						break;

					case 'wppa_file_system':
						$fs = get_option('wppa_file_system');
						if ( $fs == 'to-tree' || $fs == 'to-flat' ) {
							if ( $fs == 'to-tree' ) {
								$from = 'flat';
								$to = 'tree';
							}
							else {
								$from = 'tree';
								$to = 'flat';
							}

							// Media files
							if ( wppa_is_multi( $id ) ) {	// Can NOT use wppa_has_audio() or wppa_is_video(), they use wppa_get_photo_path() without fs switch!!
								$exts 		= array_merge( $wppa_supported_video_extensions, $wppa_supported_audio_extensions );
								$pathfrom 	= wppa_get_photo_path( $id, false, $from );
								$pathto 	= wppa_get_photo_path( $id, false, $to );
							//	wppa_log( 'dbg', 'Trying: '.$pathfrom );
								foreach ( $exts as $ext ) {
									if ( is_file( str_replace( '.xxx', '.'.$ext, $pathfrom ) ) ) {
									//	wppa_log( 'dbg',  str_replace( '.xxx', '.'.$ext, $pathfrom ).' -> '.str_replace( '.xxx', '.'.$ext, $pathto ));
										@ rename ( str_replace( '.xxx', '.'.$ext, $pathfrom ), str_replace( '.xxx', '.'.$ext, $pathto ) );
									}
								}
							}

							// Poster / photo
							if ( file_exists( wppa_get_photo_path( $id, true, $from ) ) ) {
								@ rename ( wppa_get_photo_path( $id, true, $from ), wppa_get_photo_path( $id, true, $to ) );
							}

							// Thumbnail
							if ( file_exists( wppa_get_thumb_path( $id, true, $from ) ) ) {
								@ rename ( wppa_get_thumb_path( $id, true, $from ), wppa_get_thumb_path( $id, true, $to ) );
							}

						}
						break;

					case 'wppa_cleanup':
						$photo_files = glob( WPPA_UPLOAD_PATH.'/'.$id.'.*' );
						// Remove dirs
						if ( $photo_files ) {
							foreach( array_keys( $photo_files ) as $key ) {
								if ( is_dir( $photo_files[$key] ) ) {
									unset( $photo_files[$key] );
								}
							}
						}
						// files left? process
						if ( $photo_files ) foreach( $photo_files as $photo_file ) {
							$basename 	= basename( $photo_file );
							$ext 		= substr( $basename, strpos( $basename, '.' ) + '1');
							if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `id` = %s", $id ) ) ) { // no db entry for this photo
								if ( wppa_is_id_free( WPPA_PHOTOS, $id ) ) {
									if ( wppa_create_photo_entry( array( 'id' => $id, 'album' => $orphan_album, 'ext' => $ext, 'filename' => $basename ) ) ) { 	// Can create entry
										$wppa_session[$slug.'_fixed']++;	// Bump counter
										wppa_log( 'Debug', 'Lost photo file '.$photo_file.' recovered' );
									}
									else {
										wppa_log( 'Debug', 'Unable to recover lost photo file '.$photo_file.' Create photo entry failed' );
									}
								}
								else {
									wppa_log( 'Debug', 'Could not recover lost photo file '.$photo_file.' The id is not free' );
								}
							}
						}
						break;

					case 'wppa_remake':
						$doit = true;
						if ( wppa_switch( 'remake_orientation_only' ) ) {
							$ori = wppa_get_exif_orientation( wppa_get_source_path( $id ) );
							if ( $ori < '2' ) {
								$doit = false;
							}
						}
						if ( wppa_switch( 'remake_missing_only' ) ) {
							if ( is_file( wppa_get_thumb_path( $id ) ) &&
								 is_file( wppa_get_photo_path( $id ) ) ) {
								$doit = false;
							}
						}
						if ( $doit && wppa_remake_files( '', $id ) ) {
							$wppa_session[$slug.'_fixed']++;
						}
						else {
							$wppa_session[$slug.'_skipped']++;
						}
						break;

					case 'wppa_watermark_all':
						if ( ! wppa_is_video( $id ) ) {
							if ( wppa_add_watermark( $id ) ) {
								wppa_create_thumbnail( $id );	// create new thumb
								$wppa_session[$slug.'_fixed']++;
							}
							else {
								$wppa_session[$slug.'_skipped']++;
							}
						}
						else {
							$wppa_session[$slug.'_skipped']++;
						}
						break;

					case 'wppa_create_all_autopages':
						wppa_get_the_auto_page( $id );
						break;

					case 'wppa_delete_all_autopages':
						wppa_remove_the_auto_page( $id );
						break;

					case 'wppa_leading_zeros':
						$name = $photo['name'];
						if ( wppa_is_int( $name ) ) {
							$target_len = wppa_opt( 'zero_numbers' );
							$name = strval( intval( $name ) );
							while ( strlen( $name ) < $target_len ) $name = '0'.$name;
						}
						if ( $name !== $photo['name'] ) {
							$wpdb->query( $wpdb->prepare( "UPDATE `".WPPA_PHOTOS."` SET `name` = %s WHERE `id` = %s", $name, $id ) );
						}
						break;

					case 'wppa_add_gpx_tag':
						$tags 	= wppa_sanitize_tags( $photo['tags'] );
						$temp 	= explode( '/', $photo['location'] );
						if ( ! isset( $temp['2'] ) ) $temp['2'] = false;
						if ( ! isset( $temp['3'] ) ) $temp['3'] = false;
						$lat 	= $temp['2'];
						$lon 	= $temp['3'];
						if ( $lat < 0.01 && $lat > -0.01 &&  $lon < 0.01 && $lon > -0.01 ) {
							$lat = false;
							$lon = false;
						}
						if ( $photo['location'] && $lat && $lon ) {	// Add it
							$tags = wppa_sanitize_tags( $tags . ',GPX' );
							wppa_update_photo( array( 'id' => $photo['id'], 'tags' => $tags ) );
							wppa_index_update( 'photo', $photo['id'] );
							wppa_clear_taglist();
						}
						break;

					case 'wppa_add_hd_tag':
						$tags 	= wppa_sanitize_tags( $photo['tags'] );
						$size 	= wppa_get_artmonkey_size_a( $photo['id'] );
						if ( is_array( $size ) && $size['x'] >= 1920 && $size['y'] >= 1080 ) {
							$tags = wppa_sanitize_tags( $tags . ',HD' );
							wppa_update_photo( array( 'id' => $photo['id'], 'tags' => $tags ) );
							wppa_index_update( 'photo', $photo['id'] );
							wppa_clear_taglist();
						}
						break;

					case 'wppa_optimize_ewww':
						$file = wppa_get_photo_path( $photo['id'] );
						if ( is_file( $file ) ) {
							ewww_image_optimizer( $file, 4, false, false, false );
						}
						$file = wppa_get_thumb_path( $photo['id'] );
						if ( is_file( $file ) ) {
							ewww_image_optimizer( $file, 4, false, false, false );
						}
						break;

					case 'wppa_comp_sizes':
						$tx = 0; $ty = 0; $px = 0; $py = 0;
						$file = wppa_get_photo_path( $photo['id'] );
						if ( is_file( $file ) ) {
							$temp = getimagesize( $file );
							if ( is_array( $temp ) ) {
								$px = $temp[0];
								$py = $temp[1];
							}
						}
						$file = wppa_get_thumb_path( $photo['id'] );
						if ( is_file( $file ) ) {
							$temp = getimagesize( $file );
							if ( is_array( $temp ) ) {
								$tx = $temp[0];
								$ty = $temp[1];
							}
						}
						wppa_update_photo( array( 'id' => $photo['id'], 'thumbx' => $tx, 'thumby' => $ty, 'photox' => $px, 'photoy' => $py ) );
						break;

					case 'wppa_edit_tag':
						$phototags = explode( ',', wppa_get_photo_item( $photo['id'], 'tags' ) );
						if ( in_array( $edit_tag, $phototags ) ) {
							foreach( array_keys( $phototags ) as $key ) {
								if ( $phototags[$key] == $edit_tag ) {
									$phototags[$key] = $new_tag;
								}
							}
							$tags = wppa_sanitize_tags( implode( ',', $phototags ) );
							wppa_update_photo( array( 'id' => $photo['id'], 'tags' => $tags ) );
							$wppa_session[$slug.'_fixed']++;
						}
						else {
							$wppa_session[$slug.'_skipped']++;
						}
						break;

					case 'wppa_sync_cloud':
						$is_old 	 = ( wppa_opt( 'max_cloud_life' ) ) && ( time() > ( $photo['timestamp'] + wppa_opt( 'max_cloud_life' ) ) );
					//	$is_in_cloud = @ getimagesize( wppa_get_cloudinary_url( $photo['id'], 'test_only' ) );
						$is_in_cloud = isset( $wppa_session['cloudinary_ids'][$photo['id']] );
					//	wppa_log('Obs', 'Id='.$photo['id'].', is old='.$is_old.', in cloud='.$is_in_cloud);
						if ( $is_old && $is_in_cloud ) {
							$to_delete_from_cloudinary[] = strval( $photo['id'] );
							if ( count( $to_delete_from_cloudinary ) == 10 ) {
								wppa_delete_from_cloudinary( $to_delete_from_cloudinary );
								$to_delete_from_cloudinary = array();
							}
							$wppa_session[$slug.'_deleted']++;
						}
						if ( ! $is_old && ! $is_in_cloud ) {
							wppa_upload_to_cloudinary( $photo['id'] );
							$wppa_session[$slug.'_added']++;
						}
						if ( $is_old && ! $is_in_cloud ) {
							$wppa_session[$slug.'_skipped']++;
						}
						if ( ! $is_old && $is_in_cloud ) {
							$wppa_session[$slug.'_skipped']++;
						}
						break;

					case 'wppa_sanitize_tags':
						$tags = wppa_sanitize_tags( $photo['tags'] );
						// If raw data exists, update with sanitized data
						if ( $photo['tags'] ) {
							wppa_update_photo( array( 'id' => $photo['id'], 'tags' => $tags ) );
						}
						break;

					case 'wppa_crypt_photos':
						wppa_update_photo( array( 'id' => $photo['id'], 'crypt' => wppa_get_unique_photo_crypt() ) );
						break;

					case 'wppa_create_o1_files':
						wppa_make_o1_source( $photo['id'] );
						break;

					case 'wppa_owner_to_name_proc':
						$iret = wppa_set_owner_to_name( $id );
						if ( $iret === true ) {
							$wppa_session[$slug.'_fixed']++;
						}
						if ( $iret === '0' ) {
							$wppa_session[$slug.'_skipped']++;
						}
						break;

					case 'wppa_move_all_photos':
						$fromalb = get_option( 'wppa_move_all_photos_from' );
						$toalb = get_option( 'wppa_move_all_photos_to' );
						$alb = wppa_get_photo_item( $id, 'album' );
						if ( $alb == $fromalb ) {
							wppa_update_photo( array( 'id' => $id, 'album' => $toalb ) );
							wppa_move_source( wppa_get_photo_item( $id, 'filename' ), $fromalb, $toalb );
							wppa_invalidate_treecounts( $fromalb );
							wppa_invalidate_treecounts( $toalb );
							$wppa_session[$slug.'_fixed']++;
						}
						break;

					case 'wppa_custom_photo_proc':
						$file = WPPA_UPLOAD_PATH . '/procs/wppa_custom_photo_proc.php';
						include $file;
						break;

				}

				// Test for timeout / ready
				$lastid = $id;
				update_option( $slug.'_last', $lastid );
				if ( wppa_is_cron() ) {
					$togo 	= $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `" . WPPA_PHOTOS . "` WHERE `id` > %s ", $lastid ) );
					if ( $togo ) {
						update_option( $slug.'_togo', $togo );
						update_option( $slug.'_status', 'Cron job' );
					}
				}
				if ( time() > $endtime ) break; 	// Time out
			}
			else {	// Nothing to do, Done anyway
				$lastid = $topid;
				wppa_log( wppa_is_cron() ? 'Cron' : 'Obs', 'Maintenance proc {b}' . $slug . '{/b} Done!' );
			}
			break;	// End process photos

		case 'wppa_cleanup_index':
		case 'wppa_something_else_for_index':

			// Process index
			$table 		= WPPA_INDEX;

			$topid 		= $wpdb->get_var( "SELECT `id` FROM `".WPPA_INDEX."` ORDER BY `id` DESC LIMIT 1" );
			$indexes 	= $wpdb->get_results( "SELECT * FROM `".WPPA_INDEX."` WHERE `id` > ".$lastid." ORDER BY `id` LIMIT ".$chunksize, ARRAY_A );

			if ( $indexes ) foreach ( array_keys( $indexes ) as $idx ) {

				switch ( $slug ) {

					case 'wppa_cleanup_index':

						$aborted = false;

						$albums = wppa_index_string_to_array( $indexes[$idx]['albums'] );

						if ( is_array( $albums ) ) foreach( array_keys( $albums ) as $aidx ) {

							if ( time() < ( $endtime + 2 ) ) {
								$alb 	= $albums[$aidx];

								// If album gone, remove it from index
								if ( ! wppa_album_exists( $alb ) ) {
									unset( $albums[$aidx] );
								}

								// Check if keyword appears in album data
								else {
									$words 	= wppa_index_raw_to_words( wppa_index_get_raw_album( $alb ) );
									if ( ! in_array( $indexes[$idx]['slug'], $words ) ) {
						//				wppa_log( 'Cron', 'Removed index entry album {b}' . $albums[$aidx] . '{/b} from keyword {b}' . $indexes[$idx]['slug'] . '{/b}' );

										unset( $albums[$aidx] );
									}
									wppa_cache_album( 'invalidate', $alb );	// Prevent cache overflow
								}
							}
							else break;
						}


						$photos = wppa_index_string_to_array( $indexes[$idx]['photos'] );
						$cp 	= is_array( $photos ) ? count( $photos ) : 0;
						$pidx 	= 0;
						$last 	= get_option( $slug.'_last_photo', 0 );
						delete_option( $slug.'_last_photo' );

						if ( is_array( $photos ) ) foreach( array_keys( $photos ) as $pidx ) {

							if ( $pidx < $last ) continue;	// Skip already done
							if ( $last && $pidx == $last ) {
								wppa_log('Cron', 'Continuing cleanup index at slug = {b}' . $indexes[$idx]['slug'] . '{/b}, element # = {b}' . $last . '{/b}' );
							}

							// For some unknown reason this loop does not return used memory. Hence the memory check
							if ( ( time() < ( $endtime + 2 ) ) && ( memory_get_usage() < ( 0.9 * wppa_memry_limit() ) ) ) {
								$pho 	= $photos[$pidx];

								// If photo gone, remove it from index
								if ( ! wppa_photo_exists( $pho ) ) {
									unset( $photos[$pidx] );
								}

								// Check if keyword appears in photo data
								else {
									$words = wppa_index_raw_to_words( wppa_index_get_raw_photo( $pho ) );
									if ( ! in_array( $indexes[$idx]['slug'], $words ) ) {
					//					wppa_log( 'Cron', 'Removed index entry photo {b}' . $pho . '{/b} from slug {b}' . $indexes[$idx]['slug'] . '{/b}' );
										unset( $photos[$pidx] );
									}
									wppa_cache_thumb( 'invalidate' );	// Prevent cache overflow
								}
							}
							else {
								$aborted = true;
							}
							if ( $aborted ) break;
						}
						if ( $cp && $pidx != ( $cp - 1 ) ) {
							wppa_log( 'Cron', 	'Could not complete scan of index item # {b}' . $indexes[$idx]['id'] . '{/b},' .
												' slug = {b}' . $indexes[$idx]['slug'] . '{/b},' .
												' count = {b}' . $cp . '{/b},' .
												' photo id = {b}' . $pho .'{/b},' .
												' next element # = {b}' . $pidx . '{/b},'
									);
							$aborted = true;
						}

						$lastid = $indexes[$idx]['id'];
						if ( $aborted ) {
							$lastid--;
							update_option( $slug.'_last_photo', $pidx );
						}
						update_option( $slug.'_last', $lastid );
						$albums = wppa_index_array_to_string( $albums );
						$photos = wppa_index_array_to_string( $photos );
						if ( $albums != $indexes[$idx]['albums'] || $photos != $indexes[$idx]['photos'] ) {
							$query = $wpdb->prepare( "UPDATE `".WPPA_INDEX."` SET `albums` = %s, `photos` = %s WHERE `id` = %s", $albums, $photos, $indexes[$idx]['id'] );
							$wpdb->query( $query );
						}
						break;

					case 'wppa_something_else_for_index':
						// Just example to make extensions easy
						// So you know here to out the code
						break;
				}

				// Update status
				if ( wppa_is_cron() ) {
					$togo 	= $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `" . WPPA_INDEX . "` WHERE `id` > %s ", $lastid ) );
					if ( $togo ) {
						update_option( $slug.'_togo', $togo );
						update_option( $slug.'_status', 'Cron job' );
					}
				}

				if ( time() > $endtime ) break;
				if ( memory_get_usage() >= ( 0.9 * wppa_memry_limit() ) ) break;
				if ( $aborted ) break;
			}

			break; 	// End process index

		default:
			$errtxt = 'Unimplemented maintenance slug: '.strip_tags( $slug );
	}

	// either $albums / $photos / $indexes has been exhousted ( for this try ) or time is up

	// Post proc this try:
	switch ( $slug ) {

		case 'wppa_sync_cloud':
			if ( count( $to_delete_from_cloudinary ) > 0 ) {
				wppa_delete_from_cloudinary( $to_delete_from_cloudinary );
			}
			break;

	}

	// Register lastid
	update_option( $slug.'_last', $lastid );

	// Find togo
	if ( $slug == 'wppa_cleanup' ) {
		$togo 	= $topid - $lastid;
	}
	else {
		$togo 	= $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".$table."` WHERE `id` > %s ", $lastid ) );
	}

	// Find status
	if ( ! $errtxt ) {
		$status = $togo ? 'Working' : 'Ready';
	}
	else $status = 'Error';

	// Not done yet?
	if ( $togo ) {

		// If a cron job, reschedule next chunk
		if ( wppa_is_cron() ) {

			update_option( $slug.'_togo', $togo );
			update_option( $slug.'_status', 'Cron job' );

			global $is_reschedule;
			$is_reschedule = true;
			wppa_schedule_maintenance_proc( $slug );
		}
		else {
			update_option( $slug.'_togo', $togo );
			update_option( $slug.'_status', 'Pending' );
		}
	}

	// Really done
	else {

		// Report fixed/skipped/deleted
		if ( $wppa_session[$slug.'_fixed'] ) {
			$status .= ' fixed:'.$wppa_session[$slug.'_fixed'];
			unset ( $wppa_session[$slug.'_fixed'] );
		}
		if ( $wppa_session[$slug.'_added'] ) {
			$status .= ' added:'.$wppa_session[$slug.'_added'];
			unset ( $wppa_session[$slug.'_added'] );
		}
		if ( $wppa_session[$slug.'_deleted'] ) {
			$status .= ' deleted:'.$wppa_session[$slug.'_deleted'];
			unset ( $wppa_session[$slug.'_deleted'] );
		}
		if ( $wppa_session[$slug.'_skipped'] ) {
			$status .= ' skipped:'.$wppa_session[$slug.'_skipped'];
			unset ( $wppa_session[$slug.'_skipped'] );
		}

		// Re-Init options
		delete_option( $slug.'_togo', '' );
		delete_option( $slug.'_status', '' );
		delete_option( $slug.'_last', '0' );
		delete_option( $slug.'_user', '' );
		delete_option( $slug.'_lasttimestamp', '0' );

		// Post-processing needed?
		switch ( $slug ) {
			case 'wppa_remake_index_albums':
			case 'wppa_remake_index_photos':
				wppa_schedule_maintenance_proc( 'wppa_cleanup_index' );
				break;
			case 'wppa_cleanup_index':
				$wpdb->query( "DELETE FROM `".WPPA_INDEX."` WHERE `albums` = '' AND `photos` = ''" );	// Remove empty entries
				delete_option( 'wppa_index_need_remake' );
				break;
			case 'wppa_apply_default_photoname_all':
			case 'wppa_apply_new_photodesc_all':
			case 'wppa_append_to_photodesc':
			case 'wppa_remove_from_photodesc':
				wppa_schedule_maintenance_proc( 'wppa_remake_index_photos' );
//				update_option( 'wppa_remake_index_photos_status', __('Required', 'wp-photo-album-plus') );
				break;
			case 'wppa_regen_thumbs':
				wppa_bump_thumb_rev();
				break;
			case 'wppa_file_system':
				wppa_update_option( 'wppa_file_system', $to );
				$reload = 'reload';
				break;
			case 'wppa_remake':
				wppa_bump_photo_rev();
				wppa_bump_thumb_rev();
				break;
			case 'wppa_edit_tag':
				wppa_clear_taglist();
				if ( wppa_switch( 'search_tags' ) ) {
				wppa_schedule_maintenance_proc( 'wppa_remake_index_photos' );
//					update_option( 'wppa_remake_index_photos_status', __('Required', 'wp-photo-album-plus') );
				}
				$reload = 'reload';
				break;
			case 'wppa_sanitize_tags':
				wppa_clear_taglist();
				break;
			case 'wppa_sanitize_cats':
				wppa_clear_catlist();
				break;
			case 'wppa_sync_cloud':
				unset( $wppa_session['cloudinary_ids'] );
				break;
		}

		if ( wppa_is_cron() ) {
			wppa_log( 'Cron', '{b}' . $slug . '{/b} completed' );
		}
		else {
			wppa_log( 'Obs', 'Maintenance proc {b}' . $slug . '{/b} completed' );
		}

	}

	wppa_save_session();

	if ( wppa_is_cron() ) {
		if ( get_option( $slug . '_ad_inf' ) == 'yes' ) {
			wppa_schedule_maintenance_proc( $slug );
		}
		return;
	}
	else {
		return $errtxt.'||'.$slug.'||'.$status.'||'.$togo.'||'.$reload;
	}

}

function wppa_do_maintenance_popup( $slug ) {
global $wpdb;
global $wppa_log_file;

	// Open wrapper with dedicated styles
	$result =
	'<div' .
		' id="wppa-maintenance-list"' .
		' >' .
		'<style' .
			' tyle="text/css"' .
			' >' .
				'#wppa-maintenance-list h2 {' .
					'margin-top:0;' .
				'}' .
				'#wppa-maintenance-list div {' .
					'background-color:#f1f1f1; border:1px solid #ddd;' .
				'}' .
				'#wppa-maintenance-list td, #wppa-maintenance-list th {' .
					'border-right: 1px solid darkgray;' .
				'}' .
		'</style>';

	switch ( $slug ) {

		// List the search index table
		case 'wppa_list_index':
			$start = get_option( 'wppa_list_index_display_start', '' );
			$total = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_INDEX."`" );
			$indexes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPPA_INDEX."` WHERE `slug` >= %s ORDER BY `slug` LIMIT 1000", $start ), ARRAY_A );

			$result .=
			'<h2>' .
				sprintf( __( 'List of Searcheable words <small>( Max 1000 entries of total %d )</small>', 'wp-photo-album-plus' ), $total ) .
			'</h2>' .
			'<div' .
				' style="float:left; clear:both; width:100%; overflow:auto;"' .
				' >';

			if ( $indexes ) {
				$result .= '
				<table>
					<thead>
						<tr>
							<th><span style="float:left;" >Word</span></th>
							<th style="max-width:400px;" ><span style="float:left;" >Albums</span></th>
							<th><span style="float:left;" >Photos</span></th>
						</tr>
						<tr><td colspan="3"><hr /></td></tr>
					</thead>
					<tbody>';

				foreach ( $indexes as $index ) {
					$result .= '
						<tr>
							<td>'.$index['slug'].'</td>
							<td style="max-width:400px; word-wrap: break-word;" >'.$index['albums'].'</td>
							<td>'.$index['photos'].'</td>
						</tr>';
				}

				$result .= '
					</tbody>
				</table>';
			}
			else {
				$result .= __('There are no index items.', 'wp-photo-album-plus');
			}
			$result .= '
				</div><div style="clear:both;"></div>';

			break;

		case 'wppa_list_errorlog':
			$result .=
			'<h2>' .
				__( 'List of WPPA+ log messages', 'wp-photo-album-plus' ) .
			'</h2>' .
			'<div style="float:left; clear:both; width:100%; overflow:auto; word-wrap:none;" >';

			if ( ! $file = @ fopen( $wppa_log_file, 'r' ) ) {
				$result .= __( 'There are no log messages', 'wp-photo-album-plus' );
			}
			else {
				$size 	= filesize( $wppa_log_file );
				$data 	= fread( $file, $size );
				$data 	= strip_tags( $data );
				$data 	= str_replace( array( '{b}', '{/b}', '{i}', '{/i}', "\n", '{span', '{/span}', '" }' ), array( '<b>', '</b>', '<i>', '</i>', '<br />', '<span', '</span>', '" >' ), $data );
				$result .= $data;
				fclose( $file );
			}

			$result .= '
				</div><div style="clear:both;"></div>
				';
			break;

		case 'wppa_list_rating':
			$total = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_RATING."`" );
			$ratings = $wpdb->get_results( "SELECT * FROM `".WPPA_RATING."` ORDER BY `timestamp` DESC LIMIT 1000", ARRAY_A );
			$result .=
			'<h2>' .
				sprintf( __( 'List of recent ratings <small>( Max 1000 entries of total %d )</small>', 'wp-photo-album-plus' ), $total ) .
			'</h2>' .
			'<div' .
				' style="float:left; clear:both; width:100%; overflow:auto;"' .
				' >';
			if ( $ratings ) {
				$result .= '
				<table>
					<thead>
						<tr>
							<th>Id</th>
							<th>Timestamp</th>
							<th>Date/time</th>
							<th>Status</th>
							<th>User</th>
							<th>Value</th>
							<th>Photo id</th>
							<th></th>
							<th># ratings</th>
							<th>Average</th>
						</tr>
						<tr><td colspan="10"><hr /></td></tr>
					</thead>
					<tbody>';

				foreach ( $ratings as $rating ) {
					$thumb = wppa_cache_thumb( $rating['photo'] );
					$result .= '
						<tr>
							<td>'.$rating['id'].'</td>
							<td>'.$rating['timestamp'].'</td>
							<td>'.( $rating['timestamp'] ? wppa_local_date(get_option('date_format', "F j, Y,").' '.get_option('time_format', "g:i a"), $rating['timestamp']) : 'pre-historic' ).'</td>
							<td>'.$rating['status'].'</td>
							<td>'.$rating['user'].'</td>
							<td>'.$rating['value'].'</td>
							<td>'.$rating['photo'].'</td>
							<td style="width:250px; text-align:center;"><img src="'.wppa_get_thumb_url($rating['photo']).'"
								style="height: 40px;"
								onmouseover="jQuery(this).stop().animate({height:this.naturalHeight}, 200);"
								onmouseout="jQuery(this).stop().animate({height:\'40px\'}, 200);" /></td>
							<td>'.$thumb['rating_count'].'</td>
							<td>'.$thumb['mean_rating'].'</td>
						</tr>';
				}

				$result .= '
					</tbody>
				</table>';
			}
			else {
				$result .= __('There are no ratings', 'wp-photo-album-plus');
			}
			$result .= '
				</div><div style="clear:both;"></div>';
			break;

		case 'wppa_list_session':
			$total = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_SESSION."`" );
			$sessions = $wpdb->get_results( "SELECT * FROM `".WPPA_SESSION."` ORDER BY `id` DESC LIMIT 1000", ARRAY_A );
			$result .=
			'<h2>' .
				sprintf( __( 'List of sessions <small>( Max 1000 entries of total %d )</small>', 'wp-photo-album-plus' ), $total ) .
			'</h2>' .
			'<div' .
				' style="float:left; clear:both; width:100%; overflow:auto;"' .
				' >';
			if ( $sessions ) {
				$result .= '
				<table>
					<thead>
						<tr>
							<th>Id</th>

							<th>IP</th>
							<th>Started</th>
							<th>Count</th>
							<th>Status</th>
							<th>Data</th>
							<th>Uris</th>
						</tr>
						<tr><td colspan="7"><hr /></td></tr>
					</thead>
					<tbody style="overflow:auto;" >';
					foreach ( $sessions as $session ) {
						$data = unserialize( $session['data'] );
						$result .= '
							<tr>
								<td>'.$session['id'].'</td>

								<td>' . ( strlen( $session['ip'] ) > 15 ? substr( $session['ip'], 0, 12 ) . '...' : $session['ip'] ) . '</td>
								<td style="width:150px;" >'.wppa_local_date(get_option('date_format', "F j, Y,").' '.get_option('time_format', "g:i a"), $session['timestamp']).'</td>
								<td>'.$session['count'].'</td>' .
								'<td>'.$session['status'].'</td>' .
								'<td style="border-bottom:1px solid gray;max-width:300px;" >';
									if ( is_array( $data ) ) foreach ( array_keys( $data ) as $key ) {
										if ( $key != 'uris' ) {
											if ( is_array( $data[$key] ) ) {
												$result .= '['.$key.'] => Array('.
												implode( ',', array_keys($data[$key]) ) .
												')<br />';
											}
											else {
												$result .= '['.$key.'] => '.$data[$key].'<br />';
											}
										}
									}
						$result .= '
								</td>
								<td style="border-bottom:1px solid gray;" >';
								if ( is_array( $data['uris'] ) ) {
									foreach ( $data['uris'] as $uri ) {
										$result .= $uri.'<br />';
									}
								}
						$result .= '
								</td>
							</tr>';
					}
				$result .= '
					</tbody>
				</table>';
			}
			else {
				$result .= __( 'There are no active sessions', 'wp-photo-album-plus' );
			}
			$result .= '
				</div><div style="clear:both;"></div>';

			break;

		case 'wppa_list_comments':
			$total = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_COMMENTS."`" );
			$order = wppa_opt( 'list_comments_by' );
			if ( $order == 'timestamp' ) $order .= ' DESC';
			if ( $order == 'name' ) $order = 'user';
			$query = "SELECT * FROM `".WPPA_COMMENTS."` ORDER BY ".$order." LIMIT 1000";
//	$result .= $query.'<br />';
			$comments = $wpdb->get_results( $query, ARRAY_A );
			$result .=
			'<h2>' .
				sprintf( __( 'List of comments <small>( Max 1000 entries of total %d )</small>', 'wp-photo-album-plus' ), $total ) .
			'</h2>' .
			'<div style="float:left; clear:both; width:100%; overflow:auto;" >';
			if ( $comments ) {
				$result .= '
				<table>
					<thead>
						<tr>
							<th>Id</th>
							<th>Timestamp</th>
							<th>Date/time</th>
							<th>Status</th>
							<th>User</th>
							<th>Email</th>
							<th>Photo id</th>
							<th></th>
							<th>Comment</th>
						</tr>
						<tr><td colspan="10"><hr /></td></tr>
					</thead>
					<tbody>';

				foreach ( $comments as $comment ) {
					$thumb = wppa_cache_thumb( $comment['photo'] );
					$result .= '
						<tr>
							<td>'.$comment['id'].'</td>
							<td>'.$comment['timestamp'].'</td>
							<td>'.( $comment['timestamp'] ? wppa_local_date(get_option('date_format', "F j, Y,").' '.get_option('time_format', "g:i a"), $comment['timestamp']) : 'pre-historic' ).'</td>
							<td>'.$comment['status'].'</td>
							<td>'.$comment['user'].'</td>
							<td>'.$comment['email'].'</td>
							<td>'.$comment['photo'].'</td>
							<td style="width:250px; text-align:center;"><img src="'.wppa_get_thumb_url($comment['photo']).'"
								style="height: 40px;"
								onmouseover="jQuery(this).stop().animate({height:this.naturalHeight}, 200);"
								onmouseout="jQuery(this).stop().animate({height:\'40px\'}, 200);" /></td>
							<td>'.$comment['comment'].'</td>
						</tr>';
				}

				$result .= '
					</tbody>
				</table>';
			}
			else {
				$result .= __( 'There are no comments', 'wp-photo-album-plus' );
				$result .= '<br />Query='.$wpdb->prepare( "SELECT * FROM `".WPPA_COMMENTS."` ORDER BY %s DESC LIMIT 1000", wppa_opt( 'list_comments_by' ) );
			}
			$result .= '
				</div><div style="clear:both;"></div>';
			break;

		default:
			$result = 'Error: Unimplemented slug: ' . $slug . ' in wppa_do_maintenance_popup()';
	}

	$result .=
	'</div>';

	return $result;
}

function wppa_recuperate( $id ) {
global $wpdb;

	$thumb = wppa_cache_thumb( $id );
	$iptcfix = false;
	$exiffix = false;
	$file = wppa_get_source_path( $id );
	if ( ! is_file( $file ) ) $file = wppa_get_photo_path( $id, false );

	if ( is_file ( $file ) ) {					// Not a dir
		$attr = getimagesize( $file, $info );
		if ( is_array( $attr ) ) {				// Is a picturefile
			if ( $attr[2] == IMAGETYPE_JPEG ) {	// Is a jpg

				// Save iptc is on?
				if ( wppa_switch( 'save_iptc' ) ) {

					// There is IPTC data
					if ( isset( $info["APP13"] ) ) {

						// If this is a cron prcess, the table is not pre-emptied
						if ( wppa_is_cron() ) {

							// Replace or add data
							wppa_import_iptc( $id, $info );
						}

						// Normal real-time action, no pre-delete required
						else {
							wppa_import_iptc( $id, $info, 'nodelete' );

						}
						$iptcfix = true;
					}
				}

				// Save exif is on?
				if ( wppa_switch( 'save_exif') ) {
					$image_type = exif_imagetype( $file );

					// EXIF supported by server
					if ( $image_type == IMAGETYPE_JPEG ) {

						// Get exif data
						$exif = @ exif_read_data( $file, 'ANY_TAG' );

						// Exif data found
						if ( $exif ) {

							// If this is a cron prcess, the table is not pre-emptied
							if ( wppa_is_cron() ) {

								// Replace or add data
								wppa_import_exif( $id, $file );
							}

							// Normal real-time action, no pre-delete required
							else {
								wppa_import_exif($id, $file, 'nodelete');
							}
							$exiffix = true;
						}
					}
				}
			}
		}
	}
	return array( 'iptcfix' => $iptcfix, 'exiffix' => $exiffix );
}

// Fix erroneous source path in case of migration to an other host
function wppa_fix_source_path() {

	if ( strpos( wppa_opt( 'source_dir' ), ABSPATH ) === 0 ) return; 					// Nothing to do here

	$wp_content = trim( str_replace( home_url(), '', content_url() ), '/' );

	// The source path should be: ( default ) WPPA_ABSPATH.WPPA_UPLOAD.'/wppa-source',
	// Or at least below WPPA_ABSPATH
	if ( strpos( wppa_opt( 'source_dir' ), WPPA_ABSPATH ) === false ) {
		if ( strpos( wppa_opt( 'source_dir' ), $wp_content ) !== false ) {	// Its below wp-content
			$temp = explode( $wp_content, wppa_opt( 'source_dir' ) );
			$temp['0'] = WPPA_ABSPATH;
			wppa_update_option( 'wppa_source_dir', implode( $wp_content, $temp ) );
			wppa_log( 'Fix', 'Sourcepath set to ' . wppa_opt( 'source_dir' ) );
		}
		else { // Give up, set to default
			wppa_update_option( 'wppa_source_dir', WPPA_ABSPATH.WPPA_UPLOAD.'/wppa-source' );
			wppa_log( 'Fix', 'Sourcepath set to default.' );
		}
	}
}

function wppa_log_page() {

	echo
	'<div class="wrap">' .
		wppa_admin_spinner() .
		wp_nonce_field( 'wppa-nonce', 'wppa-nonce', true, false ) .
		'<img id="icon-album" src="' . WPPA_URL . '/img/page_green.png" />' .
		'<h1 style="display:inline" >' . __('WP Photo Album Plus Logfile', 'wp-photo-album-plus') .
			'<input' .
				' class="button-secundary"' .
				' style="float:right; border-radius:3px; font-size: 16px; height: 28px; padding: 0 4px;"' .
				' value="Purge logfile"' .
				' onclick="wppaAjaxUpdateOptionValue(\'errorlog_purge\', 0);jQuery(\'#wppa-maintenance-list\').fadeOut(2000);"' .
				' type="button" >' .
		'</h1>' .
		'<style type="text/css" >h2 { display:none; }</style>' .

		wppa_do_maintenance_popup( 'wppa_list_errorlog' ) .

	'</div>';

}