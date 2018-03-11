<?php
/* wppa-import.php
* Package: wp-photo-album-plus
*
* Contains all the import pages and functions
* Version 6.8.00
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

// import images admin page
function _wppa_page_import() {
global $wppa_revno;
global $wpdb;
global $wppa_supported_photo_extensions;
global $wppa_supported_video_extensions;
global $wppa_supported_audio_extensions;
global $wppa_session;

	if ( wppa( 'ajax' ) ) ob_start();	// Suppress output if ajax operation

	// Init
	$ngg_opts 	= get_option( 'ngg_options', false );
	$user 		= wppa_get_user();

	// Check database
	wppa_check_database( true );

	// Update watermark settings for the user if new values supplied
	if ( wppa_switch( 'watermark_on' ) && ( wppa_switch( 'watermark_user' ) || current_user_can( 'wppa_settings' ) ) ) {

		// File
		if ( isset( $_POST['wppa-watermark-file'] ) ) {

			// Sanitize input
			$watermark_file = $_POST['wppa-watermark-file'];
			if ( stripos( $watermark_file, '.png' ) !== false ) {
				$watermark_file = sanitize_file_name( $watermark_file );
			}
			else {
				if ( ! in_array( $watermark_file, array( '--- none ---', '---name---', '---filename---', '---description---', '---predef---' ) ) ) {
					$watermark_file = 'nil';
				}
			}

			// Update setting
			update_option( 'wppa_watermark_file_'.$user, $watermark_file );
		}

		// Position
		if ( isset( $_POST['wppa-watermark-pos'] ) ) {

			// Sanitize input
			$watermark_pos = $_POST['wppa-watermark-pos'];
			if ( ! in_array( $watermark_pos, array( 'toplft', 'topcen', 'toprht', 'cenlft', 'cencen', 'cenrht', 'botlft', 'botcen', 'botrht' ) ) ) {
				$watermark_pos = 'nil';
			}

			// Update setting
			update_option( 'wppa_watermark_pos_'.$user, $watermark_pos );
		}
	}

	// Update last used albums
	if ( isset( $_POST['wppa-photo-album'] ) ) {
		update_option( 'wppa-photo-album-import-'.wppa_get_user(), strval( intval( $_POST['wppa-photo-album'] ) ) );
	}
	if ( isset( $_POST['wppa-video-album'] ) ) {
		update_option( 'wppa-video-album-import-'.wppa_get_user(), strval( intval( $_POST['wppa-video-album'] ) ) );
	}
	if ( isset( $_POST['wppa-audio-album'] ) ) {
		update_option( 'wppa-audio-album-import-'.wppa_get_user(), strval( intval( $_POST['wppa-audio-album'] ) ) );
	}

	// Verify last albums still exist
	$alb = get_option( 'wppa-photo-album-import-'.wppa_get_user(), '0' );
	if ( $alb ) {
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `" . WPPA_ALBUMS . "` WHERE `id` = %s", $alb ) );
		if ( ! $exists ) update_option( 'wppa-photo-album-import-'.wppa_get_user(), '0' );
	}
	$alb = get_option( 'wppa-video-album-import-'.wppa_get_user(), '0' );
	if ( $alb ) {
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `" . WPPA_ALBUMS . "` WHERE `id` = %s", $alb ) );
		if ( ! $exists ) update_option( 'wppa-video-album-import-'.wppa_get_user(), '0' );
	}
	$alb = get_option( 'wppa-audio-album-import-'.wppa_get_user(), '0' );
	if ( $alb ) {
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `" . WPPA_ALBUMS . "` WHERE `id` = %s", $alb ) );
		if ( ! $exists ) update_option( 'wppa-audio-album-import-'.wppa_get_user(), '0' );
	}

	// Extract zip
	if ( isset( $_GET['zip'] ) ) {
		wppa_extract( $_GET['zip'], true );
	}

	// Set local / remote
	if ( isset( $_POST['wppa-local-remote'] ) && in_array( $_POST['wppa-local-remote'], array( 'local', 'remote' ) ) ) {
		check_admin_referer( '$wppa_nonce', WPPA_NONCE );
		update_option( 'wppa_import_source_type_'.$user, $_POST['wppa-local-remote'] );
	}

	// Set import source dir ( when local )
	if ( isset( $_POST['wppa-import-set-source-dir'] ) && is_dir( $_POST['wppa-source'] ) ) {
		check_admin_referer( '$wppa_nonce', WPPA_NONCE );
		if ( isset( $_POST['wppa-source'] ) ) {
			update_option( 'wppa_import_source_'.$user, $_POST['wppa-source'] );
		}
	}

	// Set import source url ( when remote )
	if ( isset( $_POST['wppa-import-set-source-url'] ) ) {
		check_admin_referer( '$wppa_nonce', WPPA_NONCE );
		if ( isset( $_POST['wppa-source-remote'] ) ) {
			update_option( 'wppa_import_source_url_'.$user, esc_url( $_POST['wppa-source-remote'] ) );
			update_option( 'wppa_import_source_url_found_'.$user, false );
			update_option( 'wppa_import_remote_max_'.$user, strval( intval( $_POST['wppa-import-remote-max'] ) ) );
		}
	}

	// Hit the submit button
	if ( isset( $_POST['wppa-import-submit'] ) ) {
		if ( wppa( 'ajax' ) ) {
			if ( ! wp_verify_nonce( $_POST['wppa-update-check'], '$wppa_nonce' ) ) {
				echo $_POST['wppa-update-check'].' Security check failure';
				wppa_exit();
			}
		}
		else {
			check_admin_referer( '$wppa_nonce', WPPA_NONCE );
		}
        $delp = isset( $_POST['del-after-p'] );
		$delf = isset( $_POST['del-after-f'] );
		$dela = isset( $_POST['del-after-a'] );
		$delz = isset( $_POST['del-after-z'] );
		$delv = isset( $_POST['del-after-v'] );
		$delu = isset( $_POST['del-after-u'] );
		$delc = isset( $_POST['del-after-c'] );

		wppa_import_photos( $delp, $dela, $delz, $delv, $delu, $delc, $delf );
	}

	// Continue dirimport after timeout
	elseif ( isset( $_GET['continue'] ) ) {
		if ( wp_verify_nonce( $_GET['nonce'], 'dirimport' ) ) wppa_import_photos();
	}

	// If we did this by ajax, setup reporting results for it
	if ( wppa( 'ajax' ) ) {
		ob_end_clean();
		if ( wppa( 'ajax_import_files_done' ) ) {
			echo '<span style="color:green" >' . wppa( 'ajax_import_files' ) . ' ' . __( 'Done!', 'wp-photo-album-plus' ) . '</span>';
		}
		elseif ( wppa( 'ajax_import_files_error' ) ) {
			echo '<span style="color:red" >' . wppa( 'ajax_import_files' ) . ' ' . wppa( 'ajax_import_files_error' ) . '</span>';
		}
		else {
			echo '<span style="color:red" >' . wppa( 'ajax_import_files' ) . ' ' . __( 'Failed!', 'wp-photo-album-plus' ) . '</span>';
		}
		wppa_exit();
	}

	// Open the Form
	echo
	'<div class="wrap">' .
		'<h2>' .
			__( 'Import Photos', 'wp-photo-album-plus') .
		'</h2>';

		// See if remote is possible
		$can_remote = ini_get( 'allow_url_fopen' ) && function_exists( 'curl_init' );
		if ( ! $can_remote ) {
			update_option( 'wppa_import_source_type_'.$user, 'local' );
		}

		// Get this users current source type setting ( local/remote )
		$source_type = get_option( 'wppa_import_source_type_'.$user, 'local' );

		// Local. Find data we will going to need
		if ( $source_type == 'local' ) {

			// Get current local dir setting
			$source      = get_option( 'wppa_import_source_'.$user, WPPA_DEPOT_PATH );
			if ( ! $source || ! is_dir( $source ) ) {
				$source = WPPA_DEPOT_PATH;
				update_option( 'wppa_import_source_'.$user, WPPA_DEPOT_PATH );
			}

			// See if the current source is the 'home' directory
			$is_depot 	= ( $source == WPPA_DEPOT_PATH );

			// See if the current source is a subdir of my depot
			$is_sub_depot = ( substr( $source, 0, strlen( WPPA_DEPOT_PATH ) ) == WPPA_DEPOT_PATH );

			// Sanitize system, removes illegal files
			if ( $is_sub_depot ) {
				wppa_sanitize_files();
			}

			// See what's in there
			$files 		= wppa_get_import_files();
			$zipcount 	= wppa_get_zipcount( $files );
			$albumcount = wppa_get_albumcount( $files );
			$photocount = wppa_get_photocount( $files );
			$videocount = wppa_get_video_count( $files );
			$audiocount = wppa_get_audio_count( $files );
			$dircount	= $is_depot ? wppa_get_dircount( $files ) : '0';
			$csvcount 	= $is_depot ? wppa_get_csvcount( $files ) : '0';

			if ( $ngg_opts ) {
				$is_ngg = strpos( $source, $ngg_opts['gallerypath'] ) !== false;	// this is false for the ngg root !!
			}
			else $is_ngg = false;
		}

		// Remote. Find data we will going to need
		if ( $source_type == 'remote' ) {
			wppa( 'is_remote', true );
			$source     	= get_option( 'wppa_import_source_url_' . $user, 'http://' );
			$source_path 	= $source;
			$source_url 	= $source;
			$is_depot 		= false;
			$is_sub_depot 	= false;
			$files 			= wppa_get_import_files();
			$zipcount 		= '0';
			$albumcount 	= '0';
			$photocount 	= $files ? count( $files ) : '0';
			$videocount 	= '0';
			$audiocount 	= '0';
			$dircount		= '0';
			$csvcount 		= '0';
			$is_ngg 		= false;
			$remote_max 	= get_option( 'wppa_import_remote_max_'.$user, '10' );
		}

	// The form
	echo
	'<form' .
		' action="' . wppa_dbg_url( get_admin_url() . 'admin.php?page=wppa_import_photos' ) . '"' .
		' method="post"' .
		' >';

		// Admin and superuser can change import source, other users only if change source not is restricted
		if ( wppa_user_is( 'administrator' ) || ! wppa_switch( 'chgsrc_is_restricted' ) ) {

			// Local / Remote
			echo
			'<div style="border:1px solid gray; padding:4px; margin: 3px 0;" >' .
				wp_nonce_field( '$wppa_nonce', WPPA_NONCE, true, false ) .
				__( 'Select Local or Remote', 'wp-photo-album-plus' ) .
				( $disabled = $can_remote ? '' : 'disabled="disabled"' ) .
				'<select name="wppa-local-remote" >' .
					'<option value="local" ' . ( $source_type == 'local' ? 'selected="selected"' : '' ) . '>' . __( 'Local', 'wp-photo-album-plus') . '</option>' .
					'<option value="remote" ' . $disabled . ( $source_type == 'remote' ? 'selected="selected"' : '' ) . '>' . __( 'Remote' ,'wp-photo-album-plus' ) . '</option>' .
				'</select>';
				if ( $can_remote ) {
					echo
						'<input' .
							' type="submit"' .
							' class="button-secundary"' .
							' name="wppa-import-set-source"' .
							' value="' . __( 'Set Local/Remote' ,'wp-photo-album-plus') . '"' .
						'/>';
				}
				else {
					if ( ! ini_get( 'allow_url_fopen' ) ) {
						_e( 'The server does not allow you to import from remote locations. ( The php directive allow_url_fopen is not set to 1 )', 'wp-photo-album-plus' );
					}
					if ( ! function_exists( 'curl_init' ) ) {
						_e( 'The server does not allow you to import from remote locations. ( The curl functions are not set up )', 'wp-photo-album-plus' );
					}
				}
			echo
			'</div>';

			// Source dir / url
			echo
			'<div style="border:1px solid gray; padding:4px; margin: 3px 0;" >' .
				wp_nonce_field( '$wppa_nonce', WPPA_NONCE, true, false ) .
				__( 'Import photos from:' ,'wp-photo-album-plus');

				// Local: dir
				if ( $source_type == 'local' ) {
					wppa_update_option( 'wppa_import_root', ABSPATH . basename( content_url() ) ); // Provider may have changed disk
					echo
					'<select name="wppa-source" >' .
						wppa_abs_walktree( wppa_opt( 'import_root' ), $source ) .
					'</select>' .
					'<input' .
						' type="submit"' .
						' class="button-secundary"' .
						' name="wppa-import-set-source-dir"' .
						' value="' . __( 'Set source directory', 'wp-photo-album-plus') . '"' .
					' />';
				}

				// Remote: url
				else {
					if ( wppa_is_mobile() ) {
						echo
						'<br />' .
						'<input' .
							' type="text"' .
							' style="width:100%"' .
							' name="wppa-source-remote"' .
							' value="' . $source . '"' .
						' />' .
						'<br />';
					}
					else {
						echo
						'<input' .
							' type="text"' .
							' style="width:50%"' .
							' name="wppa-source-remote"' .
							' value="' . $source . '"' .
						' />';
					}
					echo
					__( 'Max:', 'wp-photo-album-plus' ) .
					'<input' .
						' type="text"' .
						' style="width:50px;"' .
						' name="wppa-import-remote-max"' .
						' value="' . $remote_max . '"' .
					' />' .
					'<input' .
						' type="submit"' .
						' onclick="jQuery( \'#rem-rem\' ).css( \'display\',\'inline\' ); return true;"' .
						' class="button-secundary"' .
						' name="wppa-import-set-source-url"' .
						' value="' . __( 'Find remote photos', 'wp-photo-album-plus' ) . '"' .
					' />' .
					'<span id="rem-rem" style="display:none;" >' .
						__( 'Working, please wait...', 'wp-photo-album-plus') .
					'</span>' .
					'<br />' .
					__( 'You can enter either a web page address like <i>http://mysite.com/mypage/</i> or a full url to an image file like <i>http://mysite.com/wp-content/uploads/wppa/4711.jpg</i>', 'wp-photo-album-plus' );
				}
			echo
			'</div>';
		}
	echo
	'</form>';

	// check if albums exist or will be made before allowing upload
	if ( ! wppa_has_albums() && ! $albumcount && ! $dircount && ! $csvcount ) {
		$url = wppa_dbg_url( get_admin_url() . 'admin.php?page=wppa_admin_menu' );
		echo
		'<p>' .
			__( 'No albums exist. You must', 'wp-photo-album-plus' ) . ' ' .
			'<a href="' . $url . '" >' .
				__( 'create one', 'wp-photo-album-plus' ) . ' ' .
			'</a> ' .
			__( 'before you can import your photos.', 'wp-photo-album-plus' ) .
		'</p>';
		return;
	}

	// Something to import?
	if ( $photocount || $albumcount || $zipcount || $dircount || $videocount || $audiocount || $csvcount ) {

		// Open the form
		echo
		'<form' .
			' action="' . wppa_dbg_url( get_admin_url() . 'admin.php?page=wppa_import_photos' ) . '"' .
			' method="post"' .
			' >' .
			wp_nonce_field( '$wppa_nonce', WPPA_NONCE, true, false );

			// Display the zips
			if ( PHP_VERSION_ID >= 50207 && $zipcount > '0' ) {
				echo
				'<div style="border:1px solid gray; padding:4px; margin: 3px 0;" >' .
					'<p><b>' .
						sprintf( _n( 'There is %d zipfile in the depot', 'There are %d zipfiles in the depot', $zipcount, 'wp-photo-album-plus' ), $zipcount ) .
					'</b></p>' .
					'<table class="form-table wppa-table widefat" style="margin-bottom:0;" >' .
						'<thead>' .
							'<tr>' .
								'<td>' .
									'<input' .
										' type="checkbox"' .
										' id="all-zip"' .
										' checked="checked"' .
										' onchange="checkAll( \'all-zip\', \'.wppa-zip\' )"' .
									' />' .
									'<b>&nbsp;&nbsp;' .
										__( 'Check/uncheck all', 'wp-photo-album-plus' ) .
									'</b>' .
								'</td>';
								if ( $is_sub_depot ) {
									echo
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="del-after-z"' .
											' name="del-after-z"' .
											' checked="checked"' .
										' />' .
										'<b>&nbsp;&nbsp;' .
										__( 'Delete after successful extraction.', 'wp-photo-album-plus' ) .
										'</b>' .
									'</td>';
								}
							echo
							'</tr>' .
						'</thead>' .
					'</table>' .
					'<table' .
						' class="form-table wppa-table widefat"' .
						' style="margin-top:0;"' .
						' >' .
						'<tr>';
							$ct = 0;
							$idx = '0';
							foreach ( $files as $file ) {

								$ext = wppa_get_ext( $file );
								if ( $ext == 'zip' ) {
									echo
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="file-' . $idx . '"' .
											' name="file-' . $idx . '"' .
											' class="wppa-zip"' .
											' checked="checked"' .
										' />&nbsp;&nbsp;' .
										wppa_sanitize_file_name( basename( $file ) ) .
									'</td>';
									if ( $ct == 3 ) {
										echo( '</tr><tr>' );
										$ct = 0;
									}
									else {
										$ct++;
									}
								}
								$idx++;
							}
							echo
						'</tr>' .
					'</table>' .
				'</div>';
			}

			// Dispay the albums ( .amf files )
			if ( $albumcount ) {
				echo
				'<div style="border:1px solid gray; padding:4px; margin: 3px 0;" >' .
					'<p><b>' .
						sprintf( _n( 'There is %d albumdefinition in the depot', 'There are %d albumdefinitions in the depot', $albumcount, 'wp-photo-album-plus' ), $albumcount ) .
					'</b></p>' .
					'<table class="form-table wppa-table widefat" style="margin-bottom:0;" >' .
						'<thead>' .
							'<tr>' .
								'<td>' .
									'<input' .
										' type="checkbox"' .
										' id="all-amf"' .
										' checked="checked"' .
										' onchange="checkAll( \'all-amf\', \'.wppa-amf\' )"' .
									' />' .
									'<b>&nbsp;&nbsp;' .
										__( 'Check/uncheck all', 'wp-photo-album-plus' ) .
									'</b>' .
								'</td>';
								if ( $is_sub_depot ) {
									echo
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="del-after-a"' .
											' name="del-after-a"' .
											' checked="checked"' .
										' />' .
										'<b>&nbsp;&nbsp;' .
											__( 'Remove from depot after successful import, or if the album already exists.', 'wp-photo-album-plus' ) .
										'</b>' .
									'</td>';
								}
							echo
							'</tr>' .
						'</thead>' .
					'</table>' .
					'<table' .
						' class="form-table wppa-table widefat"' .
						' style="margin-top:0;"' .
						' >' .
						'<tr>';
							$ct = 0;
							$idx = '0';
							foreach ( $files as $file ) {
								$ext = wppa_get_ext( $file );
								if ( $ext == 'amf' ) {
									echo
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="file-' . $idx . '"' .
											' name="file-' . $idx . '"' .
											' class="wppa-amf"' .
											' checked="checked"' .
										' />' .
										'&nbsp;&nbsp;' .
										basename( $file ) . '&nbsp;' . stripslashes( wppa_get_meta_name( $file, '( ' ) ) .
									'</td>';
									if ( $ct == 3 ) {
										echo( '</tr><tr>' );
										$ct = 0;
									}
									else {
										$ct++;
									}
								}
								$idx++;
							}
						echo
						'</tr>' .
					'</table>' .
				'</div>';
			}

			// Display the single photos
			if ( $photocount ) {
				echo
				'<div style="border:1px solid gray; padding:4px; margin: 3px 0;" >';

					// Display the number of photos
					'<p><b>';

						// Local
						if ( $source_type == 'local' ) {
							if ( $is_ngg ) {
								printf( _n( 'There is %d photo in the ngg gallery', 'There are %d photos in the ngg gallery', $photocount, 'wp-photo-album-plus' ), $photocount );
							}
							else {
								printf( _n( 'There is %d photo in the depot', 'There are %d photos in the depot', $photocount, 'wp-photo-album-plus' ), $photocount );
							}
						}

						// Remote
						else {
							printf( _n( 'There is %d possible photo found remote', 'There are %d possible photos found remote', $photocount, 'wp-photo-album-plus' ), $photocount );
						}

						// Tell if downsize on
						if ( wppa_switch( 'resize_on_upload' ) ) {
							echo ' ' . __( 'Photos will be downsized during import.', 'wp-photo-album-plus' );
						}

					echo
					'</b></p>';

					// The album selection
					echo
					'<p class="hideifupdate" >' .
						__( 'Default album for import:', 'wp-photo-album-plus') .
							wppa_album_select_a( array( 	'path' 				=> wppa_switch( 'hier_albsel' ),
															'selected' 			=> get_option( 'wppa-photo-album-import-'.wppa_get_user(), '0' ),
															'addpleaseselect' 	=> true,
															'checkowner' 		=> true,
															'checkupload' 		=> true,
															'sort' 				=> true,
															'optionclass' 		=> '',
															'tagopen' 			=> '<select name="wppa-photo-album" id="wppa-photo-album" >',
															'tagname' 			=> 'wppa-photo-album',
															'tagid' 			=> 'wppa-photo-album',
															'tagonchange' 		=> '',
															'multiple' 			=> false,
															'tagstyle' 			=> '',
														) ) .
						__( 'Photos that have (<em>name</em>)[<em>album</em>] will be imported by that <em>name</em> in that <em>album</em>.', 'wp-photo-album-plus') .
					'</p>';

					echo '<p>';

					// Watermark
					if ( wppa_switch( 'watermark_on' ) && ( wppa_switch( 'watermark_user' ) || current_user_can( 'wppa_settings' ) ) ) {
						echo
						__( 'Apply watermark file:', 'wp-photo-album-plus') .
						'<select name="wppa-watermark-file" id="wppa-watermark-file" >' .
							wppa_watermark_file_select( 'user' ) .
						'</select>' .
						__( 'Position:', 'wp-photo-album-plus') .
						'<select name="wppa-watermark-pos" id="wppa-watermark-pos" >' .
							wppa_watermark_pos_select( 'user' ) .
						'</select>';
					}

					// Delay
					$delays = array( '1', '2', '5', '10', '20', '50', '100' );
					echo
					__( 'Delay', 'wp-photo-album-plus' ) .
					'<select id="wppa-delay" >';
					foreach ( $delays as $d ) {
						echo '<option value="' . ( $d * 1000 ) . '" >' . $d . '</option>';
					}
					echo
					'</select> s. ' .
					'<img' .
						' id="wppa-spinner"' .
						' src="' . wppa_get_imgdir( 'spinner.gif' ) . '"' .
						' style="vertical-align:middle;display:none;"' .
					' />';

					echo '</p>';

					// Header of photo list
					echo
					'<table class="form-table wppa-table widefat" style="margin-bottom:0;" >' .
						'<thead>' .
							'<tr>' .
								'<td>' .
									'<input' .
										' type="checkbox"' .
										' id="all-pho"' .
										( $is_sub_depot ? 'checked="checked"' : '' ) .
										' onchange="checkAll( \'all-pho\', \'.wppa-pho\' )"' .
									' />' .
									'<b>' .
										'&nbsp;&nbsp;' .
										__( 'Check/uncheck all', 'wp-photo-album-plus') .
									'</b>' .
								'</td>';

								// Depot specific switches
								if ( $is_sub_depot ) {
									echo
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="del-after-p"' .
											' name="del-after-p"' .
											' checked="checked"' .
										' />' .
										'<b>' .
											'&nbsp;&nbsp;' .
											__( 'Remove from depot after successful import.', 'wp-photo-album-plus' ) .
										'</b>' .
									'</td>' .
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="del-after-f"' .
											' name="del-after-f"' .
										' />' .
										'<b>' .
											'&nbsp;&nbsp;' .
											__( 'Remove from depot after failed import.', 'wp-photo-album-plus' ) .
										'</b>' .
									'</td>';
								}

								// Nextgen import specific switches
								if ( $is_ngg ) {
									echo
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="cre-album"' .
											' name="cre-album"' .
											' checked="checked"' .
											' value="' . esc_attr( basename( $source ) ) .'"' .
										' />' .
										'<b>' .
											'&nbsp;&nbsp;' .
											__( 'Import into album', 'wp-photo-album-plus' ) . ' ' . basename( $source ) .
										'</b>' .
										'<small>' .
											__( 'The album will be created if it does not exist', 'wp-photo-album-plus') .
										'</small>' .
									'</td>' .
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="use-backup"' .
											' name="use-backup"' .
											' checked="checked"' .
										' />' .
										'<b>' .
											'&nbsp;&nbsp;' .
											__( 'Use backup if available', 'wp-photo-album-plus') .
										'</b>' .
									'</td>';
								}

								// Update existing switch
								echo
								'<td>' .
									'<input' .
										' type="checkbox"' .
										' id="wppa-update"' .
										' onchange="impUpd( this, \'#submit\' )"' .
										' name="wppa-update"' .
									' />' .
									'<b>' .
										'&nbsp;&nbsp;' .
										__( 'Update existing photos', 'wp-photo-album-plus') .
									'</b>' .
								'</td>';

								// Void dups switch
								echo '<td>';
								if ( wppa_switch( 'void_dups' ) ) {
									echo
									'<input' .
										' type="hidden"' .
										' id="wppa-nodups"' .
										' name="wppa-nodups"' .
										' value="true"' .
									' />';
								}
								else {
									echo
									'<input' .
										' type="checkbox"' .
										' id="wppa-nodups"' .
										' name="wppa-nodups"' .
										' checked="checked"' .
									' />' .
									'<b>' .
										'&nbsp;&nbsp;' .
										__( 'Do not create duplicates', 'wp-photo-album-plus' ) .
									'</b>';
								}
								echo '</td>';

								// Import preview zoomable switch
								if ( wppa_switch( 'import_preview' ) ) {
									echo
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="wppa-zoom"' .
											' onclick="wppa_setCookie(\'zoompreview\', this.checked, \'365\')"' .
										' />' .
										'<b>' .
											'&nbsp;&nbsp;' .
											__( 'Zoom previews', 'wp-photo-album-plus' ) .
										'</b>' .
										'<script type="text/javascript">if ( wppa_getCookie(\'zoompreview\') == true ) { jQuery(\'#wppa-zoom\').attr(\'checked\', \'checked\') }</script>' .
									'</td>';
								}
							echo
							'</tr>' .
						'</thead>' .
					'</table>';

					// Photo list
					echo
					'<table class="form-table wppa-table widefat" style="margin-top:0;" >' .
						'<tr>';
							$ct = 0;
							$idx = '0';
							if ( is_array( $files ) ) foreach ( $files as $file ) {
								$ext = wppa_get_ext( $file );
								$meta =	wppa_strip_ext( $file ).'.PMF';
								if ( ! is_file( $meta ) ) {
									$meta =	wppa_strip_ext( $file ).'.pmf';
								}
								if ( ! is_file( $meta ) ) {
									$meta = false;
								}
								if ( in_array( strtolower($ext), $wppa_supported_photo_extensions ) ) {
									echo
									'<td id="td-file-' . $idx . '" >' .
										'<input' .
											' type="checkbox"' .
											' id="file-' . $idx . '"' .
											' name="file-' . $idx . '"' .
											' title="' . esc_attr( $file ) . '"' .
											' class="wppa-pho"' .
											( $is_sub_depot ? 'checked="checked"' : '' ) .
										'/ >' .
										'<span' .
											' id="name-file-' . $idx . '"' .
											' >' .
											'&nbsp;&nbsp;';

											if ( wppa( 'is_wppa_tree' ) ) {
												$t = explode( 'uploads/wppa/', $file );
												echo $t[1];
											}
											else {
												echo( wppa_sanitize_file_name( basename( $file ) ) );
											}

											if ( $meta ) {
												echo
												'&nbsp;' .
												stripslashes( wppa_get_meta_name( $meta, '( ' ) ) .
												stripslashes( wppa_get_meta_album( $meta, '[' ) );
											}
										echo
										'</span>';

										if ( wppa_switch( 'import_preview' ) ) {
											if ( wppa( 'is_remote' ) ) {
												if ( strpos( $file, '//res.cloudinary.com/' ) !== false ) {
													$img_url = dirname( $file ) . '/h_144/' . basename( $file );
												}
												else {
													$img_url = $file;
												}
											}
											else {
												$img_url = str_replace( ABSPATH, home_url().'/', $file );
												if ( is_ssl() ) {
													$img_url = str_replace( 'http://', 'https://', $img_url );
												}
											}
											echo
											'<img src="' . $img_url . '"' .
												' alt="N.A."' .
												' style="max-height:48px;"' .
												' onmouseover="if (jQuery(\'#wppa-zoom\').attr(\'checked\')) jQuery(this).css(\'max-height\', \'144px\')"' .
												' onmouseout="if (jQuery(\'#wppa-zoom\').attr(\'checked\')) jQuery(this).css(\'max-height\', \'48px\')"' .
											' />';
										}

									echo
									'</td>';

									if ( $ct == 3 ) {
										echo( '</tr><tr>' );
										$ct = 0;
									}
									else {
										$ct++;
									}
								}
								$idx++;
							}
						echo
						'</tr>' .
					'</table>' .
				'</div>';
			}

			// Display the videos
			if ( $videocount && wppa_switch( 'enable_video' ) ) {
				echo
				'<div style="border:1px solid gray; padding:4px; margin: 3px 0;" >';

					// Display available files
					echo
					'<p><b>' .
						sprintf( _n( 'There is %d video in the depot', 'There are %d videos in the depot', $videocount, 'wp-photo-album-plus' ), $videocount ) .
					'</b></p>';

					// Album to import to
					echo
					'<p class="hideifupdate" >' .
						__( 'Album to import to:', 'wp-photo-album-plus') .
							wppa_album_select_a( array( 	'path' 				=> wppa_switch( 'hier_albsel' ),
															'selected' 			=> get_option( 'wppa-video-album-import-'.wppa_get_user(), '0' ),
															'addpleaseselect'	=> true,
															'checkowner' 		=> true,
															'checkupload' 		=> true,
															'sort'				=> true,
															'optionclass' 		=> '',
															'tagopen' 			=> '<select name="wppa-video-album" id="wppa-video-album" >',
															'tagname' 			=> 'wppa-video-album',
															'tagid' 			=> 'wppa-video-album',
															'tagonchange' 		=> '',
															'multiple' 			=> false,
															'tagstyle' 			=> '',
														) ) .
					'</p>';

					// Header of video list
					echo
					'<table class="form-table wppa-table widefat" style="margin-bottom:0;" >' .
						'<thead>' .
							'<tr>' .
								'<td>' .
									'<input' .
										' type="checkbox"' .
										' id="all-video"' .
										' checked="checked"' .
										' onchange="checkAll( \'all-video\', \'.wppa-video\' )"' .
									' />' .
									'<b>' .
										'&nbsp;&nbsp;' .
										__( 'Check/uncheck all', 'wp-photo-album-plus') .
									'</b>' .
								'</td>';
								if ( $is_sub_depot ) {
									echo
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="del-after-v"' .
											' name="del-after-v"' .
											' checked="checked"' .
										' />' .
										'<b>' .
											'&nbsp;&nbsp;' .
											__( 'Remove from depot after successful import.', 'wp-photo-album-plus' ) .
											' <small>' .
												__( 'Files larger than 64MB will always be removed after successful import.', 'wp-photo-album-plus' ) .
											'</small>' .
										'</b>' .
									'</td>';
								}
							echo
							'</tr>' .
						'</thead>' .
					'</table>';

					// Video list
					echo
					'<table class="form-table wppa-table widefat" style="margin-top:0;" >' .
						'<tr>';
							$ct = 0;
							$idx = '0';
							if ( is_array( $files ) ) foreach ( $files as $file ) {
								$ext = strtolower( substr( strrchr( $file, "." ), 1 ) );
								if ( in_array( strtolower($ext), $wppa_supported_video_extensions ) ) {
									echo
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="file-' . $idx . '"' .
											' name="file-' . $idx . '"' .
											' title="' . $file . '"' .
											' class="wppa-video"' .
											' checked="checked"' .
										' />' .
										'<span' .
											' id="name-file-' . $idx . '"' .
											' >' .
											'&nbsp;&nbsp;' .
											wppa_sanitize_file_name( basename( $file ) ) .
										'</span>' .
									'</td>';
									if ( $ct == 3 ) {
										echo( '</tr><tr>' );
										$ct = 0;
									}
									else {
										$ct++;
									}
								}
								$idx++;
							}
						echo
						'</tr>' .
					'</table>' .
				'</div>';
			}

			// Display the audios
			if ( $audiocount && wppa_switch( 'enable_audio' ) ) {
				echo
				'<div style="border:1px solid gray; padding:4px; margin: 3px 0;" >';

					// Display available files
					echo
					'<p><b>' .
						sprintf( _n( 'There is %d audio in the depot', 'There are %d audios in the depot', $audiocount, 'wp-photo-album-plus' ), $audiocount ) .
					'</b></p>';

					// Album to import to
					echo
					'<p class="hideifupdate" >' .
						__( 'Album to import to:', 'wp-photo-album-plus') .
							wppa_album_select_a( array( 	'path' 				=> wppa_switch( 'hier_albsel' ),
															'selected' 			=> get_option( 'wppa-audio-album-import-'.wppa_get_user(), '0' ),
															'addpleaseselect' 	=> true,
															'checkowner' 		=> true,
															'checkupload' 		=> true,
															'sort' 				=> true,
															'optionclass' 		=> '',
															'tagopen' 			=> '<select name="wppa-audio-album" id="wppa-audio-album" >',
															'tagname' 			=> 'wppa-audio-album',
															'tagid' 			=> 'wppa-audio-album',
															'tagonchange' 		=> '',
															'multiple' 			=> false,
															'tagstyle' 			=> '',
														) ) .
					'</p>';

					// Header of audio list
					echo
					'<table class="form-table wppa-table widefat" style="margin-bottom:0;" >' .
						'<thead>' .
							'<tr>' .
								'<td>' .
									'<input' .
										' type="checkbox"' .
										' id="all-audio"' .
										' checked="checked"' .
										' onchange="checkAll( \'all-audio\', \'.wppa-audio\' )"' .
									' />' .
									'<b>' .
										'&nbsp;&nbsp;' .
										__( 'Check/uncheck all', 'wp-photo-album-plus') .
									'</b>' .
								'</td>';

								// The remove box
								if ( $is_sub_depot ) {
									echo
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="del-after-u"' .
											' name="del-after-u"' .
											' checked="checked"' .
										' />' .
										'<b>' .
											'&nbsp;&nbsp;' .
											__( 'Remove from depot after successful import.', 'wp-photo-album-plus' ) .
										'</b>' .
									'</td>';
								}
							echo
							'</tr>' .
						'</thead>' .
					'</table>';

					// Audio list
					echo
					'<table class="form-table wppa-table widefat" style="margin-top:0;" >' .
						'<tr>';
							$ct = 0;
							$idx = '0';
							if ( is_array( $files ) ) foreach ( $files as $file ) {
								$ext = strtolower( substr( strrchr( $file, "." ), 1 ) );
								if ( in_array( strtolower($ext), $wppa_supported_audio_extensions ) ) {
									echo
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="file-' . $idx . '"' .
											' name="file-' . $idx . '"' .
											' title="' . $file . '"' .
											' class="wppa-audio"' .
											' checked="checked"' .
										' />' .
										'<span' .
											' id="name-file-' . $idx . '"' .
											' >' .
											'&nbsp;&nbsp;' .
											wppa_sanitize_file_name( basename( $file ) ) .
										'</span>' .
									'</td>';
									if ( $ct == 3 ) {
										echo '</tr><tr>';
										$ct = 0;
									}
									else {
										$ct++;
									}
								}
								$idx++;
							}
						echo
						'</tr>' .
					'</table>' .
				'</div>';
			}

			// Display the directories to be imported as albums. Do this in the depot only!!
			if ( $is_depot && $dircount ) {
				echo
				'<div style="border:1px solid gray; padding:4px; margin: 3px 0;" >';

					// Display number of dirs
					echo
					'<p><b>' .
						sprintf( _n( 'There is %d albumdirectory in the depot', 'There are %d albumdirectories in the depot', $dircount, 'wp-photo-album-plus' ), $dircount ) .
					'</b></p>';

					// Header of dirlist
					echo
					'<table class="form-table wppa-table widefat" style="margin-bottom:0;" >' .
						'<thead>' .
							'<tr>' .
								'<td>' .
									'<input' .
										' type="checkbox"' .
										' id="all-dir"' .
										' checked="checked"' .
										' onchange="checkAll( \'all-dir\', \'.wppa-dir\' )"' .
									' />' .
									'<b>' .
										'&nbsp;&nbsp;' .
										__( 'Check/uncheck all', 'wp-photo-album-plus') .
									'</b>' .
								'</td>' .
							'</tr>' .
						'</thead>' .
					'</table>';

					// Dirlist
					echo
					'<table class="form-table wppa-table widefat" style="margin-top:0;" >';
						$ct = 0;
						$idx = '0';
						foreach( $files as $dir ) {
							if ( basename( $dir ) == '.' ) {}
							elseif ( basename( $dir ) == '..' ) {}
							elseif ( is_dir( $dir ) ) {
								echo
								'<tr>' .
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="file-' . $idx . '"' .
											' name="file-' . $idx .'"' .
											' class= "wppa-dir"' .
											' checked="checked"' .
										' />' .
										'&nbsp;&nbsp;' .
										'<b>' .
											wppa_sanitize_file_name( basename( $dir ) ) .
										'</b>';
										$subfiles = glob( $dir.'/*' );
										$subdircount = '0';
										if ( $subfiles ) {
											foreach ( $subfiles as $subfile ) {
												if ( is_dir( $subfile ) && basename( $subfile ) != '.' && basename( $subfile ) != '..' ) {
													$subdircount++;
												}
											}
										}
										$sfcount = empty( $subfiles ) ? '0' : wppa_get_photocount( $subfiles );
										echo ' ' .
										sprintf( _n( 'Contains %d file', 'Contains %d files', $sfcount, 'wp-photo-album-plus' ), $sfcount );
										if ( $subdircount ) {
											echo ' ' .
											sprintf( _n( 'and %d subdirectory', 'and %d subdirectories', $subdircount, 'wp-photo-album-plus' ), $subdircount );
										}
									'</td>' .
								'</tr>';
							}
							$idx++;
						}
					echo
					'</table>' .
				'</div>';
			}

			// Display the csv files
			if ( $is_depot && $csvcount ) {
				echo
				'<div style="border:1px solid gray; padding:4px; margin: 3px 0;" >';

					// Display number of files
					echo
					'<p><b>' .
						sprintf( _n( 'There is %d .csv file in the depot', 'There are %d .csv files in the depot', $csvcount, 'wp-photo-album-plus' ), $csvcount ) .
					'</b></p>';

					// Header of .csv file list
					echo
					'<table class="form-table wppa-table widefat" style="margin-bottom:0;" >' .
						'<thead>' .
							'<tr>' .
								'<td>' .
									'<input' .
										' type="checkbox"' .
										' id="all-csv"' .
										' checked="checked"' .
										' onchange="checkAll( \'all-csv\', \'.wppa-csv\' )"' .
									' />' .
									'<b>' .
										'&nbsp;&nbsp;' .
										__( 'Check/uncheck all', 'wp-photo-album-plus') .
									'</b>' .
								'</td>' .
								'<td>' .
									'<input' .
										' type="checkbox"' .
										' id="del-after-c"' .
										' name="del-after-c"' .
										' checked="checked"' .
										' disabled="disabled"' .
									' />' .
									'<b>' .
									'&nbsp;&nbsp;' .
									__( 'Remove from depot after successful import.', 'wp-photo-album-plus' ) .
									'</b>' .
								'</td>' .
							'</tr>' .
						'</thead>' .
					'</table>';

					// CSV file list
					echo
					'<table class="form-table wppa-table widefat" style="margin-top:0;" >';
						$ct = 0;
						$idx = '0';
						foreach( $files as $csv ) {
							if ( is_file( $csv ) && strtolower( wppa_get_ext( $csv ) ) == 'csv' ) {
								echo
								'<tr>' .
									'<td>' .
										'<input' .
											' type="checkbox"' .
											' id="file-' . $idx . '"' .
											' name="file-' . $idx . '"' .
											' class="wppa-csv"' .
											' checked="checked"' .
										' />' .
										'&nbsp;&nbsp;' .
										'<b>' .
											wppa_sanitize_file_name( basename( $csv ) ) .
											' (' . sprintf( '%5.1f', filesize( $csv ) / 1024 ) . ' kb)' .
										'</b>' .
									'</td>' .
								'</tr>';
							}
							$idx++;
						}
					echo
					'</table>' .
				'</div>';
			}

			// The submit button
			?>
			<p>
				<script type="text/javascript">
					function wppaVfyAlbum() {
						var csvs = jQuery( '.wppa-csv' );
						if ( jQuery( '#wppa-update' ).attr( 'checked' ) != 'checked' ) {
							if ( 	! parseInt( jQuery( '#wppa-photo-album' ).attr( 'value' ) ) &&
									! parseInt( jQuery( '#wppa-video-album' ).attr( 'value' ) ) &&
									! parseInt( jQuery( '#wppa-audio-album' ).attr( 'value' ) ) &&
									csvs.length == 0
								) {
								alert( 'Please select an album first' );
								return false;
							}
						}
						return true;
					}
					function wppaCheckInputVars() {
						var checks = jQuery( ':checked' );
						var nChecks = checks.length;
						var nMax = <?php echo ini_get( 'max_input_vars' ) ?>;
						if ( nMax == 0 ) nMax = 100;
						if ( nChecks > nMax ) {
							alert ( 'There are '+nChecks+' boxes checked or selected, that is more than the maximum allowed number of '+nMax );
							return false;
						}
						var dirs = jQuery( '.wppa-dir' );
						var nDirsChecked = 0;
						if ( dirs.length > 0 ) {
							var i = 0;
							while ( i < dirs.length ) {
								if ( jQuery( dirs[i] ).attr( 'checked' ) == 'checked' ) {
									nDirsChecked++;
								}
								i++;
							}
						}
						var zips = jQuery( '.wppa-zip' );
						var nZipsChecked = 0;
						if ( zips.length > 0 ) {
							var i = 0;
							while ( i < zips.length ) {
								if ( jQuery( zips[i] ).attr( 'checked' ) == 'checked' ) {
									nZipsChecked++;
								}
								i++;
							}
						}
						// If no dirs to import checked, there must be an album selected
						if ( 0 == nDirsChecked && 0 == nZipsChecked && ! wppaVfyAlbum() ) return false;
						return true;
					}
				</script>
				<input type="submit" onclick="return wppaCheckInputVars()" class="button-primary" id="submit" name="wppa-import-submit" value="<?php _e( 'Import', 'wp-photo-album-plus' ); ?>" />
				<script type="text/javascript" >
					var wppaImportRuns = false;
					var wppaTimer;
					function wppaDoAjaxImport() {
						jQuery( '#wppa-spinner' ).css( 'display', 'none' );
//						wppaImportRuns = true;
						var data = '';
						data += 'wppa-update-check='+jQuery( '#wppa-update-check' ).attr( 'value' );
						data += '&wppa-photo-album='+jQuery( '#wppa-photo-album' ).attr( 'value' );
						data += '&wppa-video-album='+jQuery( '#wppa-video-album' ).attr( 'value' );
						data += '&wppa-audio-album='+jQuery( '#wppa-audio-album' ).attr( 'value' );
						data += '&wppa-watermark-file='+jQuery( '#wppa-watermark-file' ).attr( 'value' );
						data += '&wppa-watermark-pos='+jQuery( '#wppa-watermark-pos' ).attr( 'value' );
						if ( jQuery( '#cre-album' ).attr( 'checked' ) ) data += '&cre-album='+jQuery( '#cre-album' ).attr( 'value' );
						if ( jQuery( '#use-backup' ).attr( 'checked' ) ) data += '&use-backup=on'; //+jQuery( '#use-backup' ).attr( 'value' );
						if ( jQuery( '#wppa-update' ).attr( 'checked' ) ) data += '&wppa-update=on'; //+jQuery( '#wppa-update' ).attr( 'value' );
						if ( jQuery( '#wppa-nodups' ).attr( 'checked' ) ) data += '&wppa-nodups=on'; //+jQuery( '#wppa-nudups' ).attr( 'value' );
						if ( jQuery( '#del-after-p' ).attr( 'checked' ) ) data += '&del-after-p=on';
						if ( jQuery( '#del-after-f' ).attr( 'checked' ) ) data += '&del-after-f=on';
						if ( jQuery( '#del-after-v' ).attr( 'checked' ) ) data += '&del-after-v=on';
						if ( jQuery( '#del-after-u' ).attr( 'checked' ) ) data += '&del-after-u=on';
						data += '&wppa-import-submit=ajax';

						var files = jQuery( ':checked' );
						var found = false;
						var i=0;
						var elm;
						var fulldata;
						for ( i=0; i<files.length; i++ ) {
							found = false;	// assume done
							elm = files[i];
							// Is it a file checkbox?
							var temp = elm.id.split( '-' );
							if ( temp[0] != 'file' ) continue;	// no
							fulldata = data+'&import-ajax-file='+elm.title;
							found = true;
							break;
						}
						//	alert( data );
						if ( ! found ) {
							wppaStopAjaxImport();
							return;	// nothing left
						}
						// found one, do it
						var oldhtml=jQuery( '#name-'+elm.id ).html();
						var xmlhttp = wppaGetXmlHttp();
						xmlhttp.onreadystatechange = function() {
							if ( xmlhttp.readyState == 4 ) {
								if ( xmlhttp.status!=404 ) {
									var resp = xmlhttp.responseText;
									//
									if ( resp.length == 0 ) {
										jQuery( '#name-'+elm.id ).html('<span style="color:red" >Timeout</span>' );
										wppaStopAjaxImport();
										return;
									}
									//
									if ( resp.indexOf( 'Server' ) != -1 && resp.indexOf( 'Error' ) != -1 ) {
										resp = '<span style="color:red" >Server error</span>';
									}
									jQuery( '#name-'+elm.id ).html( '&nbsp;&nbsp;<b>'+resp+'</b>' );
									elm.checked = '';
									if ( jQuery( '#del-after-p' ).attr( 'checked' ) ||
										 jQuery( '#del-after-f' ).attr( 'checked' ) ) {
										elm.disabled = 'disabled';
										elm.title = '';
									}
									if ( wppaImportRuns ) {
										wppaTimer = setTimeout( 'wppaDoAjaxImport()', jQuery( '#wppa-delay' ).val() );	// was 100
										jQuery( '#wppa-spinner' ).css( 'display', 'inline' );
									}
								}
								else {
									jQuery( '#name-'+elm.id ).html( '&nbsp;&nbsp;<b>Not found</b>' );
								}
							}
						}
						var url = wppaAjaxUrl+'?action=wppa&wppa-action=import';
						xmlhttp.open( 'POST',url,true );
						xmlhttp.setRequestHeader( "Content-type","application/x-www-form-urlencoded" );
						xmlhttp.send( fulldata );
						jQuery( '#name-'+elm.id ).html( '&nbsp;&nbsp;<b style="color:blue" >' + '<?php _e( 'Working...', 'wp-photo-album-plus' ) ?>' + '</b>' );
						if ( wppaImportRuns ) {
							jQuery( '#wppa-start-ajax' ).css( 'display', 'none' );
							jQuery( '#wppa-stop-ajax' ).css( 'display', 'inline' );
						}
					}
					function wppaStopAjaxImport() {
						wppaImportRuns = false;
						clearTimeout( wppaTimer );
						jQuery( '#wppa-start-ajax' ).css( 'display', 'inline' );
						jQuery( '#wppa-stop-ajax' ).css( 'display', 'none' );
						jQuery( '#wppa-spinner' ).css( 'display', 'none' );
					}
				</script>
				<?php if ( ( $photocount || $videocount || $audiocount ) && ! $albumcount && ! $dircount && ! $zipcount ) { ?>
				<input id="wppa-start-ajax" type="button" onclick="if ( wppaVfyAlbum() ) { wppaImportRuns = true;wppaDoAjaxImport() }" class="button-secundary" value="<?php esc_attr( _e( 'Start Ajax Import', 'wp-photo-album-plus' ) ) ?>" />
				<input id="wppa-stop-ajax" style="display:none;" type="button" onclick="wppaStopAjaxImport()" class="button-secundary" value="<?php esc_attr( _e( 'Stop Ajax Import', 'wp-photo-album-plus' ) ) ?>" />
				<?php } ?>
			</p>
			</form>

		<?php }
		else {
			if ( $source_type == 'local' ) {
				wppa_ok_message( __( 'There are no importable files found in directory:', 'wp-photo-album-plus').' '.$source );
			}
			else {
				wppa_ok_message( __( 'There are no photos found or left to process at url:', 'wp-photo-album-plus').' '.$source_url );
			}
		}
		echo '<br /><b>';
		_e( 'You can import the following file types:', 'wp-photo-album-plus' );
		echo '</b><br />';
		if ( PHP_VERSION_ID >= 50207 ) {
			echo '<br />';
			_e( 'Compressed file types: .zip', 'wp-photo-album-plus' );
		}
		if ( true ) {
			echo '<br />';
			_e( 'Photo file types:', 'wp-photo-album-plus' );
			foreach ( $wppa_supported_photo_extensions as $ext ) {
				echo ' .'.$ext;
			}
		}
		if ( wppa_switch( 'enable_video' ) ) {
			echo '<br />';
			_e( 'Video file types:', 'wp-photo-album-plus' );
			foreach ( $wppa_supported_video_extensions as $ext ) {
				echo ' .'.$ext;
			}
		}
		if ( wppa_switch( 'enable_audio' ) ) {
			echo '<br />';
			_e( 'Audio file types:', 'wp-photo-album-plus' );
			foreach ( $wppa_supported_audio_extensions as $ext ) {
				echo ' .'.$ext;
			}
		}
		echo '<br />';
		_e( 'WPPA+ file types: .amf .pmf', 'wp-photo-album-plus' );
		echo '<br />';
		_e( 'Directories with optional subdirs containig photos', 'wp-photo-album-plus' );
		echo '<br />';
		_e( 'Custom data files of type .csv', 'wp-photo-album-plus' );
		echo '<br /><br />';
		_e( 'Your depot directory is:', 'wp-photo-album-plus' );
		echo '<b> .../' . WPPA_DEPOT . '/</b>';

	if ( wppa( 'continue' ) ) {
		wppa_warning_message( __( 'Trying to continue...', 'wp-photo-album-plus') );
		echo '<script type="text/javascript">document.location=\''.get_admin_url().'admin.php?page=wppa_import_photos&continue&nonce='.wp_create_nonce( 'dirimport' ).'\';</script>';
	}

	echo '<br /><br />';

	wppa_album_admin_footer();

	echo
	'</div><!-- .wrap -->';

}

// get array of files to import
function wppa_get_import_files() {

	// Init
	$user 			= wppa_get_user();
	$source_type 	= get_option( 'wppa_import_source_type_'.$user, 'local' );
	$files			= array();

	// Ajax? one file
	if ( isset ( $_POST['import-ajax-file'] ) ) {
		$files = array( $_POST['import-ajax-file'] );
	}

	// Dispatch on source type local/remote
	elseif ( $source_type == 'local' ) {
		$source 		= get_option( 'wppa_import_source_'.$user, WPPA_DEPOT_PATH );
		$source_path 	= $source;	// Filesystem
		$files 			= glob( $source_path . '/*' );
	}
	else { // remote
		$max_tries 		= get_option( 'wppa_import_remote_max_'.$user, '10' );
		$setting 		= get_option( 'wppa_import_source_url_'.$user, 'http://' );
		$pattern		= '/src=".*?"/';

		// Is it a photofile in a wppa tree filestructure?
		$old_setting = $setting;

		// assume not
		if ( wppa_is_url_a_photo( $setting ) ) {
			wppa( 'is_wppa_tree', false );
			$is_image = true;
		}
		else {
			$setting = wppa_expand_tree_path( $old_setting );

			// though?
			if ( wppa_is_url_a_photo( $setting ) ) {
				wppa( 'is_wppa_tree', true );
				$is_image = true;
			}
			else {
				$is_image = false;
			}
		}

		// Is it a photofile?
		if ( $is_image ) {
			$files = array( $setting );
			$pid = wppa_strip_ext( basename( $old_setting ) );
			if ( is_numeric( $pid ) ) {
				$tries = 1;
				$before = substr( $old_setting, 0, strpos( $old_setting, $pid) );
				while ( $tries < $max_tries ) {
					$tries++;
					$pid++;
					if ( wppa( 'is_wppa_tree' ) ) {
						$files[] = $before . wppa_expand_id($pid) . '.jpg';
					}
					else {
						$files[] = $before . $pid . '.jpg';
					}
				}
			}
		}

		// is it a page url
		else {
			$files = get_option( 'wppa_import_source_url_found_' . $user, false );
			if ( ! $files ) {

				// Init
				$files = array();

				// Get page content
				$curl = curl_init();
				curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $curl, CURLOPT_URL, $setting );
				$contents = curl_exec( $curl );
				curl_close( $curl );

				// Process result
				if ( $contents ) {

					// Preprocess
					$contents = str_replace( '\'', '"', $contents );

					// Find matches
					preg_match_all( $pattern, $contents, $matches, PREG_PATTERN_ORDER );
					if ( is_array( $matches[0] ) ) {

						// Sort
						sort( $matches[0] );

						// Copy to $files, skipping dups
						$val = '';
						$count = 0;
						$sfxs = array( 'jpg', 'jpeg', 'gif', 'png', 'JPG', 'JPEG', 'GIF', 'PNG' );
						foreach ( array_keys( $matches[0] ) as $idx ) {
							if ( $matches[0][$idx] != $val ) {
								$val = $matches[0][$idx];
								// Post process found item
								$match 		= substr( $matches[0][$idx], 5 );
								$matchpos 	= strpos( $contents, $match );
								$match 		= trim( $match, '"' );
								if ( strpos( $match, '?' ) ) $match = substr( $match, 0, strpos( $match, '?' ) );
								$match 		= str_replace( '/uploads/wppa/thumbs/', '/uploads/wppa/', $match );
								$sfx = wppa_get_ext( $match );
								if ( in_array( $sfx, $sfxs ) ) {
									// Save it
									$count++;
									if ( $count <= $max_tries ) {
										$files[] = $match;
									}
								}
							}
						}
					}
				}
				update_option( 'wppa_import_source_url_found_'.$user, $files );
			}
		}
	}

	// Remove non originals
	if ( is_array( $files ) ) foreach ( array_keys( $files ) as $key ) {
		if ( ! wppa_is_orig( $files[$key] ) ) {
			unset ( $files[$key] );
		}
	}

	// Sort to keep synchronicity when doing ajax import
	if ( is_array( $files ) ) sort( $files );

	// Done, return result
	return $files;
}



// Send emails after backend upload
function wppa_backend_upload_mail( $id, $alb, $name ) {

	$owner = wppa_get_user();
	if ( $owner == 'admin' ) return;	// Admin does not send mails to himself

	if ( wppa_switch( 'upload_backend_notify' ) ) {
		$to = get_bloginfo( 'admin_email' );
		$subj = sprintf( __( 'New photo uploaded: %s', 'wp-photo-album-plus'), wppa_sanitize_file_name( $name ) );
		$cont['0'] = sprintf( __( 'User %1$s uploaded photo %2$s into album %3$s', 'wp-photo-album-plus'), $owner, $id, wppa_get_album_name( $alb ) );
		if ( wppa_switch( 'upload_moderate' ) && !current_user_can( 'wppa_admin' ) ) {
			$cont['1'] = __( 'This upload requires moderation', 'wp-photo-album-plus' );
			$cont['2'] = '<a href="'.get_admin_url().'admin.php?page=wppa_admin_menu&tab=pmod&photo='.$id.'" >'.__( 'Moderate manage photo', 'wp-photo-album-plus').'</a>';
		}
		else {
			$cont['1'] = __( 'Details:', 'wp-photo-album-plus' );
			$cont['1'] .= ' <a href="'.get_admin_url().'admin.php?page=wppa_admin_menu&tab=pmod&photo='.$id.'" >'.__( 'Manage photo', 'wp-photo-album-plus').'</a>';
		}
		wppa_send_mail( $to, $subj, $cont, $id );
	}
}

// Do the import photos
function wppa_import_photos( $delp = false, $dela = false, $delz = false, $delv = false, $delu = false, $delc = false, $delf = false ) {
global $wpdb;
global $warning_given;
global $wppa_supported_photo_extensions;
global $wppa_supported_video_extensions;
global $wppa_supported_audio_extensions;

	$warning_given = false;

	// Get this users current source directory setting
	$user 			= wppa_get_user();
	$source_type 	= get_option( 'wppa_import_source_type_'.$user, 'local' );
	if ( $source_type == 'remote' ) wppa( 'is_remote', true );
	$source 		= get_option( 'wppa_import_source_'.$user, WPPA_DEPOT_PATH );

	$depot 			= WPPA_ABSPATH . $source;	// Filesystem
	$depoturl 		= get_bloginfo( 'wpurl' ).'/'.$source;	// url

	// See what's in there
	$files = wppa_get_import_files();

	// First extract zips if our php version is ok
	$idx='0';
	$zcount = 0;
	if ( PHP_VERSION_ID >= 50207 ) {
		foreach( $files as $zipfile ) {
			if ( isset( $_POST['file-'.$idx] ) ) {
				$ext = strtolower( substr( strrchr( $zipfile, "." ), 1 ) );

				if ( $ext == 'zip' ) {
					$err = wppa_extract( $zipfile, $delz );
					if ( $err == '0' ) $zcount++;
				} // if ext = zip
			} // if isset
			$idx++;
		} // foreach
	}

	// Now see if albums must be created
	$idx='0';
	$acount = 0;
	foreach( $files as $album ) {
		if ( isset( $_POST['file-'.$idx] ) ) {
			$ext = strtolower( substr( strrchr( $album, "." ), 1 ) );
			if ( $ext == 'amf' ) {
				$name = '';
				$desc = '';
				$aord = '0';
				$parent = '0';
				$porder = '0';
				$owner = '';
				$handle = fopen( $album, "r" );
				if ( $handle ) {
					$buffer = fgets( $handle, 4096 );
					while ( !feof( $handle ) ) {
						$tag = substr( $buffer, 0, 5 );
						$len = strlen( $buffer ) - 6;	// substract 5 for label and one for eol
						$data = substr( $buffer, 5, $len );
						switch( $tag ) {
							case 'name=':
								$name = $data;
								break;
							case 'desc=':
								$desc = wppa_txt_to_nl( $data );
								break;
							case 'aord=':
								if ( is_numeric( $data ) ) $aord = $data;
								break;
							case 'prnt=':
								if ( $data == __( '--- none ---', 'wp-photo-album-plus') ) $parent = '0';
								elseif ( $data == __( '--- separate ---', 'wp-photo-album-plus') ) $parent = '-1';
								else {
									$prnt = wppa_get_album_id( $data );
									if ( $prnt != '' ) {
										$parent = $prnt;
									}
									else {
										$parent = '0';
										wppa_warning_message( __( 'Unknown parent album:', 'wp-photo-album-plus').' '.$data.' '.__( '--- none --- used.', 'wp-photo-album-plus') );
									}
								}
								break;
							case 'pord=':
								if ( is_numeric( $data ) ) $porder = $data;
								break;
							case 'ownr=':
								$owner = $data;
								break;
						}
						$buffer = fgets( $handle, 4096 );
					} // while !foef
					fclose( $handle );
					if ( wppa_get_album_id( $name ) != '' ) {
						wppa_warning_message( 'Album already exists '.stripslashes( $name ) );
						if ( $dela ) unlink( $album );
					}
					else {
						$id = basename( $album );
						$id = substr( $id, 0, strpos( $id, '.' ) );
						$id = wppa_create_album_entry( array ( 	'id' 			=> $id,
																'name' 			=> stripslashes( $name ),
																'description' 	=> stripslashes( $desc ),
																'a_order' 		=> $aord,
																'a_parent' 		=> $parent,
																'p_order_by' 	=> $porder,
																'owner' 		=> $owner
															 ) );

						if ( $id === false ) {
							wppa_error_message( __( 'Could not create album.', 'wp-photo-album-plus') );
						}
						else {
							//$id = wppa_get_album_id( $name );
							wppa_set_last_album( $id );
							wppa_index_add( 'album', $id );
							wppa_ok_message( __( 'Album #', 'wp-photo-album-plus') . ' ' . $id . ': '.stripslashes( $name ).' ' . __( 'Added.', 'wp-photo-album-plus') );
							if ( $dela ) unlink( $album );
							$acount++;
							wppa_clear_cache();
							wppa_invalidate_treecounts( $id );
						} // album added
					} // album did not exist
				} // if handle ( file open )
			} // if its an album
		} // if isset
		$idx++;
	} // foreach file

	// Now the photos
	$idx 		= '0';
	$pcount 	= '0';
	$totpcount 	= '0';

	// find album id
	if ( isset( $_POST['cre-album'] ) ) {	// use album ngg gallery name for ngg conversion
		$album 	= wppa_get_album_id( strip_tags( $_POST['cre-album'] ) );
		if ( ! $album ) {				// the album does not exist yet, create it
			$name	= strip_tags( $_POST['cre-album'] );
			$desc 	= sprintf( __( 'This album has been converted from ngg gallery %s', 'wp-photo-album-plus'), $name );
			$uplim	= '0/0';	// Unlimited not to destroy the conversion process!!
			$album 	= wppa_create_album_entry( array ( 	'name' 			=> $name,
														'description' 	=> $desc,
														'upload_limit' 	=> $uplim
														 ) );
			if ( $album === false ) {
				wppa_error_message( __( 'Could not create album.', 'wp-photo-album-plus').'<br/>Query = '.$query );
				wp_die( 'Sorry, cannot continue' );
			}
		}
	}
	elseif ( isset( $_POST['wppa-photo-album'] ) ) {
		$album = $_POST['wppa-photo-album'];
	}
	else $album = '0';

	// Report starting process
	wppa_ok_message( __( 'Processing files, please wait...', 'wp-photo-album-plus').' '.__( 'If the line of dots stops growing or your browser reports Ready, your server has given up. In that case: try again', 'wp-photo-album-plus').' <a href="'.wppa_dbg_url( get_admin_url().'admin.php?page=wppa_import_photos' ).'">'.__( 'here.', 'wp-photo-album-plus').'</a>' );

	// Do them all
	foreach ( array_keys( $files ) as $file_idx ) {
		$unsanitized_path_name = $files[$file_idx];
		$file = $files[$file_idx];
		wppa_is_wppa_tree( $file );	// Sets wppa( 'is_wppa_tree' )
		if ( isset( $_POST['use-backup'] ) && is_file( $file.'_backup' ) ) {
			$file = $file.'_backup';
		}
		$file = wppa_sanitize_file_name( $file );
		if ( isset( $_POST['file-'.$idx] ) || wppa( 'ajax' ) ) {
			if ( wppa( 'is_wppa_tree' ) ) {
				if ( wppa( 'ajax' ) ) wppa( 'ajax_import_files', basename( wppa_compress_tree_path( $file ) ) );
			}
			else {
				if ( wppa( 'ajax' ) ) wppa( 'ajax_import_files', basename( $file ) );
			}
			$ext = strtolower( substr( strrchr( $file, "." ), 1 ) );
			$ext = str_replace( '_backup', '', $ext );
			if ( in_array( $ext, $wppa_supported_photo_extensions ) ) {

				// See if a metafile exists
				//$meta = substr( $file, 0, strlen( $file ) - 3 ).'pmf';
				$meta = wppa_strip_ext( $unsanitized_path_name ) . '.PMF';
				if ( ! is_file( $meta ) ) {
					$meta = wppa_strip_ext( $unsanitized_path_name ) . '.pmf';
				}

				// find all data: name, desc, porder form metafile
				if ( is_file( $meta ) ) {
					$alb = wppa_get_album_id( wppa_get_meta_album( $meta ) );
					$name = wppa_get_meta_name( $meta );
					$desc = wppa_txt_to_nl( wppa_get_meta_desc( $meta ) );
					$porder = wppa_get_meta_porder( $meta );
					$linkurl = wppa_get_meta_linkurl( $meta );
					$linktitle = wppa_get_meta_linktitle( $meta );
				}
				else {
					$alb = $album;	// default album
					$name = '';		// default name
					$desc = '';		// default description
					$porder = '0';	// default p_order
					$linkurl = '';
					$linktitle = '';
				}

				// If there is a video or audio with the same name, this is the poster.
				$is_poster = wppa_file_is_in_album( wppa_strip_ext( basename( $file ) ) . '.xxx', $alb );
				if ( $is_poster ) {

					// Delete possible poster sourcefile
					wppa_delete_source( basename( $file ), $alb );

					// Remove possible existing posters, the file-extension may be different as before
					$old_photo = wppa_strip_ext( wppa_get_photo_path( $is_poster, false ) );
					$old_thumb = wppa_strip_ext( wppa_get_thumb_path( $is_poster, false ) );
					foreach ( $wppa_supported_photo_extensions as $pext ) {
						if ( is_file( $old_photo . '.' . $pext ) ) unlink( $old_photo . '.' . $pext );
						if ( is_file( $old_thumb . '.' . $pext ) ) unlink( $old_thumb . '.' . $pext );
					}

					// Clear sizes on db
					wppa_update_photo( array( 	'thumbx' => '0',
												'thumby' => '0',
												'photox' => '0',
												'photoy' => '0'
										));

					// Make new files
					$bret = wppa_make_the_photo_files( $file, $is_poster, strtolower( wppa_get_ext( basename( $file ) ) ) );
					if ( $bret ) { 	// Success
						if ( wppa( 'ajax' ) ) wppa( 'ajax_import_files_done', true );
						wppa_save_source( $file, basename( $file ), $alb );
						wppa_make_o1_source( $is_poster );
						$pcount++;
						$totpcount += $bret;
						if ( $delp ) {
							unlink( $file );
						}
					}
					else { 			// Failed
						if ( ! wppa( 'ajax' ) ) {
							wppa_error_message('Failed to add poster for item '.$is_poster);
						}
						if ( $delf ) {
							unlink( $file );
						}
					}
				}

				// Update the photo ?
				elseif ( isset( $_POST['wppa-update'] ) ) {

					if ( wppa( 'is_wppa_tree' ) ) {
						$tmp = explode( '/wppa/', $file );
						$name = str_replace( '/', '', $tmp[1] );
					}

					$iret = wppa_update_photo_files( $unsanitized_path_name, $name );
					if ( $iret ) {
						if ( wppa( 'ajax' ) ) wppa( 'ajax_import_files_done', true );
						$pcount++;
						$totpcount += $iret;
						if ( $delp ) {
							unlink( $unsanitized_path_name );
						}
					}
					else {
						if ( $delf ) {
							unlink( $unsanitized_path_name );
						}
					}
				}

				// Insert the photo
				else {
					if ( is_numeric( $alb ) && $alb != '0' ) {
						if ( wppa( 'is_wppa_tree' ) ) {
							$tmp = explode( '/wppa/', $file );
							$id = str_replace( '/', '', $tmp[1] );
							$name = $id;
						}
						else {
							$id = basename( $file );
						}
						if ( wppa_switch( 'void_dups' ) && wppa_file_is_in_album( $id, $alb ) ) {
							wppa_warning_message( sprintf( __( 'Photo %s already exists in album %s. (1)', 'wp-photo-album-plus'), $id, $alb ) );
							wppa( 'ajax_import_files_error', __( 'Duplicate', 'wp-photo-album-plus') );
							if ( $delf ) {
								unlink( $file );
							}
						}
						else {
							$id = substr( $id, 0, strpos( $id, '.' ) );
							if ( ! is_numeric( $id ) || ! wppa_is_id_free( WPPA_PHOTOS, $id ) ) $id = 0;
							if ( wppa_insert_photo( $unsanitized_path_name, $alb, stripslashes( $name ), stripslashes( $desc ), $porder, $id, stripslashes( $linkurl ), stripslashes( $linktitle ) ) ) {
								if ( wppa( 'ajax' ) ) {
									wppa( 'ajax_import_files_done', true );
								}
								$pcount++;
								if ( $delp ) {
									unlink( $unsanitized_path_name );
									if ( is_file( $meta ) ) unlink( $meta );
								}

								// If ajax and remote and not a page, update url to successfully imported photo
								if ( wppa( 'ajax' ) && wppa( 'is_remote' ) ) {
									$setting = get_option( 'wppa_import_source_url_'.$user, 'http://' );
									$setting_x = wppa_expand_tree_path( $setting );
									if ( wppa_is_url_a_photo( $setting ) || wppa_is_url_a_photo( $setting_x ) ) {
										update_option( 'wppa_import_source_url_' . wppa_get_user(), wppa_compress_tree_path( $unsanitized_path_name ) );
									}
								}
							}
							else {
								wppa_error_message( __( 'Error inserting photo', 'wp-photo-album-plus') . ' ' . basename( $file ) . '.' );
								if ( $delf ) {
									unlink( $unsanitized_path_name );
								}
							}
						}
					}
					else {
						wppa_error_message( sprintf( __( 'Error inserting photo %s, unknown or non existent album.', 'wp-photo-album-plus'), basename( $file ) ) );
					}
				} // Insert
			}
		}
		$idx++;
		if ( $source_type == 'remote' ) unset( $files[$file_idx] );
		if ( wppa_is_time_up() ) {
			wppa_warning_message( sprintf( __( 'Time out. %s photos imported. Please restart this operation.', 'wp-photo-album-plus'), $pcount ) );
			wppa_set_last_album( $album );
			if ( $source_type == 'remote' ) update_option( 'wppa_import_source_url_found_'.$user, $files );
			return;
		}
	} // foreach $files
	if ( $source_type == 'remote' ) update_option( 'wppa_import_source_url_found_'.$user, $files );

	// Now the dirs to album imports

	$idx 		= '0';
	$dircount 	= '0';
	global $photocount;
	$photocount = '0';
	$iret 		= true;

	foreach ( $files as $file ) {
		if ( basename( $file ) != '.' &&  basename( $file ) != '..' && ( isset( $_POST['file-'.$idx] ) || isset( $_GET['continue'] ) ) ) {
			if ( is_dir( $file ) ) {
				$iret = wppa_import_dir_to_album( $file, '0' );
				if ( wppa_is_time_up() && wppa_switch( 'auto_continue' ) ) {
					wppa( 'continue', 'continue' );
				}
				$dircount++;
			}
		}
		$idx++;
		if ( $iret == false ) break;	// Time out
	}

	// Now the video files
	$videocount = '0';
	$alb = isset( $_POST['wppa-video-album'] ) ? $_POST['wppa-video-album'] : '0';
	if ( wppa( 'ajax' ) && ! $alb ) {
		wppa( 'ajax_import_files_error', __( 'Unknown album', 'wp-photo-album-plus' ) );
	}
	else foreach ( array_keys( $files ) as $idx ) {
		$file = $files[$idx];
		if ( isset( $_POST['file-'.$idx] ) || wppa( 'ajax' ) ) {
			if ( wppa( 'ajax' ) ) wppa( 'ajax_import_files', wppa_sanitize_file_name( basename( $file ) ) );	/* */
			$ext = strtolower( substr( strrchr( $file, "." ), 1 ) );
			if ( in_array( $ext, $wppa_supported_video_extensions ) ) {
				if ( is_numeric( $alb ) && $alb != '0' ) {

					// Do we have this filename with ext xxx in this album?
					$filename = wppa_strip_ext( basename( $file ) ).'.xxx';
					$id = wppa_file_is_in_album( $filename, $alb );

					// Or maybe the poster is already there
					foreach ( $wppa_supported_photo_extensions as $pext ) {
						if ( ! $id ) {
							$id = wppa_file_is_in_album( str_replace( 'xxx', $pext, $filename ), $alb );
						}
					}

					// This filename already exists: is the poster. Fix the filename in the photo info
					if ( $id ) {
						$fname = wppa_get_photo_item( $id, 'filename' );
						$fname = wppa_strip_ext( $fname ) . '.xxx';

						// Fix filename and ext in photo info
						wppa_update_photo( array( 'id' => $id, 'filename' => $fname, 'ext' => 'xxx' ) );
					}

					// Add new entry
					if ( ! $id ) {
						$id = wppa_create_photo_entry( array( 'album' => $alb, 'filename' => $filename, 'ext' => 'xxx', 'name' => wppa_strip_ext( $filename ) ) );
						wppa_invalidate_treecounts( $alb );
					}

					// Add video filetype
					$newpath = wppa_strip_ext( wppa_get_photo_path( $id, false ) ).'.'.$ext;
					$fs = filesize( $file );
					if ( $fs > 1024*1024*64 || $delv ) {	// copy fails for files > 64 Mb

						// Remove old version if already exists
						if ( is_file( $newpath ) ) {
							unlink( $newpath );
						}
						rename( $file, $newpath );
					}
					else {
						copy( $file, $newpath );
					}

					if ( wppa( 'ajax' ) ) {
						wppa( 'ajax_import_files_done', true );
					}

					// Make sure ext is set to xxx after adding video to an existing poster
					wppa_update_photo( array( 'id' => $id, 'ext' => 'xxx' ) );

					// Book keeping
					$videocount++;
				}
				else {
					wppa_error_message( sprintf( __( 'Error inserting video %s, unknown or non existent album.', 'wp-photo-album-plus'), basename( $file ) ) );
				}
			}
		}
	}

	// Now the audio files
	$audiocount = '0';
	$alb = isset( $_POST['wppa-audio-album'] ) ? $_POST['wppa-audio-album'] : '0';
	if ( wppa( 'ajax' ) && ! $alb ) {
		wppa( 'ajax_import_files_error', __( 'Unknown album', 'wp-photo-album-plus' ) );
	}
	else foreach ( array_keys( $files ) as $idx ) {
		$file = $files[$idx];
		if ( isset( $_POST['file-'.$idx] ) || wppa( 'ajax' ) ) {
			if ( wppa( 'ajax' ) ) wppa( 'ajax_import_files', wppa_sanitize_file_name( basename( $file ) ) );
			$ext = strtolower( substr( strrchr( $file, "." ), 1 ) );
			if ( in_array( $ext, $wppa_supported_audio_extensions ) ) {
				if ( is_numeric( $alb ) && $alb != '0' ) {

					// Do we have this filename with ext xxx in this album?
					$filename = wppa_strip_ext( basename( $file ) ).'.xxx';
					$id = wppa_file_is_in_album( $filename, $alb );

					// Or maybe the poster is already there
					foreach ( $wppa_supported_photo_extensions as $pext ) {
						if ( ! $id ) {
							$id = wppa_file_is_in_album( str_replace( 'xxx', $pext, $filename ), $alb );
						}
					}

					// This filename already exists: is the poster. Fix the filename in the photo info
					if ( $id ) {
						$fname = wppa_get_photo_item( $id, 'filename' );
						$fname = wppa_strip_ext( $fname ) . '.xxx';

						// Fix filename and ext in photo info
						wppa_update_photo( array( 'id' => $id, 'filename' => $fname, 'ext' => 'xxx' ) );
					}

					// Add new entry
					if ( ! $id ) {
						$id = wppa_create_photo_entry( array( 'album' => $alb, 'filename' => $filename, 'ext' => 'xxx', 'name' => wppa_strip_ext( $filename ) ) );
						wppa_invalidate_treecounts( $alb );
					}

					// Add audio filetype
					$newpath = wppa_strip_ext( wppa_get_photo_path( $id, false ) ).'.'.$ext;
					copy( $file, $newpath );
					if ( $delu ) unlink( $file );
					if ( wppa( 'ajax' ) ) {
						wppa( 'ajax_import_files_done', true );
					}

					// Make sure ext is set to xxx after adding audio to an existing poster
					wppa_update_photo( array( 'id' => $id, 'ext' => 'xxx' ) );

					// Book keeping
					$audiocount++;
				}
				else {
					wppa_error_message( sprintf( __( 'Error inserting audio %s, unknown or non existent album.', 'wp-photo-album-plus'), basename( $file ) ) );
				}
			}
		}
	}

	// The csv files. NOT with ajax
	$csvcount = wppa_get_csvcount( $files );
	if ( $csvcount ) {
		$csvcount = '0';
		if ( ! wppa( 'ajax' ) ) {
			if ( is_array( $files ) ) {

				// Make sure the feature is on
				if ( ! wppa_switch( 'custom_fields' ) ) {
					wppa_update_option( 'wppa_custom_fields', 'yes' );
					echo '<b>' . __( 'Custom datafields enabled', 'wp-photo-album-plus').'</b><br />';
				}

				// Get the captions we already have
				$cust_labels = array();
				for ( $i = '0'; $i < '10'; $i++ ) {
					$cust_labels[$i] = wppa_opt( 'custom_caption_' . $i );
				}

				// Get the system datafields tha may be filled using .csv import
				$syst_lables = array(
									// id bigint(20) NOT NULL,
					'album',		// bigint(20) NOT NULL,
									// ext tinytext NOT NULL,
					'name',			// text NOT NULL,
					'description',	// longtext NOT NULL,
					'p_order',		// smallint(5) NOT NULL,
									// mean_rating tinytext NOT NULL,
					'linkurl',		// text NOT NULL,
					'linktitle',	// text NOT NULL,
					'linktarget', 	// tinytext NOT NULL,
					'owner', 		// text NOT NULL,
					'timestamp', 	// tinytext NOT NULL,
					'status', 		// tinytext NOT NULL,
									// rating_count bigint(20) NOT NULL default '0',
					'tags',			// text NOT NULL,
					'alt',			// tinytext NOT NULL,
									// filename tinytext NOT NULL,
					'modified',		// tinytext NOT NULL,
					'location',		// tinytext NOT NULL,
					'views',		// bigint(20) NOT NULL default '0',
					'clicks',		// bigint(20) NOT NULL default '0',
									// page_id bigint(20) NOT NULL default '0',
					'exifdtm', 		// tinytext NOT NULL,
									// videox smallint(5) NOT NULL default '0',
									// videoy smallint(5) NOT NULL default '0',
									// thumbx smallint(5) NOT NULL default '0',
									// thumby smallint(5) NOT NULL default '0',
									// photox smallint(5) NOT NULL default '0',
									// photoy smallint(5) NOT NULL default '0',
									// scheduledtm tinytext NOT NULL,
									// custom longtext NOT NULL,
									// stereo smallint NOT NULL default '0',
									// crypt tinytext NOT NULL,
					);

				// Process the files
				$photos_processed_csv 	= '0';
				$photos_skipped_csv 	= '0';
				$is_db_table 			= false;
				$tables 				= array( WPPA_ALBUMS, WPPA_PHOTOS, WPPA_RATING, WPPA_COMMENTS, WPPA_IPTC, WPPA_EXIF, WPPA_INDEX, WPPA_SESSION );

				foreach ( array_keys( $files ) as $idx ) {
					$this_skipped = '0';
					$file = $files[$idx];
					if ( isset( $_POST['file-'.$idx] ) || isset( $_GET['continue'] ) ) {
						$ext = strtolower( wppa_get_ext( $file ) );
						if ( $ext == 'csv' ) {

							// See if it is a db table
							foreach( array_keys( $tables ) as $idx ) {

								$table_name = str_replace( $wpdb->prefix, '', $tables[$idx] );

								if ( strpos( $file, $table_name . '.csv' ) !== false ) {

									$is_db_table = $tables[$idx];

									// Only administrators may do this
									if ( ! current_user_can( 'administrator' ) ) {
										wppa_error_messgae( __( 'Only administrators are allowed to import db table data.', 'wp-photo-album-plus' ) );
										return;
									}
								}
							}

							if ( $is_db_table ) {
								echo '<b>' . __( 'Processing db table', 'wp-photo-album-plus' ) . ' ' . $is_db_table . '</b><br />';
								wppa_log( 'dbg', __( 'Processing db table', 'wp-photo-album-plus' ) . ' ' . $is_db_table );
							}
							else {
								echo '<b>' . __( 'Processing', 'wp-photo-album-plus' ) . ' ' . basename( $file ) . '</b><br />';
								wppa_log( 'dbg', __( 'Processing', 'wp-photo-album-plus' ) . ' ' . basename( $file ) );
							}

							// Copy the file to a temp file
							$tempfile = dirname( $file ) . '/temp.csv';
							copy ( $file, $tempfile );

							// Open file
							$handle = fopen( $tempfile, "rt" );
							if ( ! $handle ) {
								wppa_error_message( __( 'Can not open file. Can not continue. (1)', 'wp-photo-album-plus') );
								return;
							}
							$write_handle = fopen( $file, "wt" );
							if ( ! $write_handle ) {
								wppa_error_message( __( 'Can not open file. Can not continue. (2)', 'wp-photo-album-plus') );
								return;
							}

							// Read header
							$header = fgets( $handle, 4096 );
							if ( ! $header ) {
								wppa_error_message( __( 'Can not read header. Can not continue.', 'wp-photo-album-plus') );
								fclose( $handle );
								return;
							}
							fputs( $write_handle, $header );
							echo __( 'Read header:', 'wp-photo-album-plus') . ' ' . $header . '<br />';

							// Is it a db table?
							if ( $is_db_table ) {

								// Functions for inserting db table data
								$entry_functions = array(	WPPA_ALBUMS 	=> 'wppa_create_album_entry',
															WPPA_PHOTOS 	=> 'wppa_create_photo_entry',
															WPPA_RATING 	=> 'wppa_create_rating_entry',
															WPPA_COMMENTS 	=> 'wppa_create_comments_entry',
															WPPA_IPTC 		=> 'wppa_create_iptc_entry',
															WPPA_EXIF 		=> 'wppa_create_exif_entry',
															WPPA_INDEX 		=> 'wppa_create_index_entry',
														);

								// Interprete and verify header. All fields from .csv MUST be in table fields, else fail
								$csv_fields = str_getcsv( $header );
								$db_fields  = $wpdb->get_results( "DESCRIBE `" . $is_db_table . "`", ARRAY_A );

								foreach( $csv_fields as $csv_field ) {
									$ok = false;
									foreach( $db_fields as $db_field ) {
										if ( $db_field['Field'] === $csv_field ) {
											$ok = true;
										}
									}
									if ( ! $ok ) {
										wppa_error_message( 'Field '.$csv_field.' not found in db table '.$is_db_table.' description' );
										wppa_error_message( __( 'Invalid header. Can not continue.', 'wp-photo-album-plus') );
										fclose( $handle );
										return;
									}
								}

								// Now process the lines
								while ( ! feof( $handle ) ) {
									$dataline = fgets( $handle, 16*4096 );
									if ( $dataline ) {
										$data_arr = str_getcsv( $dataline );

										// Embedded newlines?
										while ( ( count( $csv_fields ) > count( $data_arr ) ) && ! feof( $handle ) ) {

											// Assume continue after embedded linebreak
											$dataline .= "\n" . fgets( $handle, 16*4096 );
											$data_arr = str_getcsv( $dataline );

										}

										reset( $data_arr );
										$id = trim( current( $data_arr ) );
										if ( wppa_is_int( $id ) && $id > '0' ) {

											wppa_dbg_msg( 'Processing id '.$id );

											$existing_data = $wpdb->get_row( "SELECT * FROM `" . $is_db_table . "` WHERE `id` = $id", ARRAY_A );

											// If entry exists:
											// 1. save existing data,
											// 2. remove entry,
											if ( $existing_data ) {
												$data = $existing_data;
												$wpdb->query( "DELETE FROM `" . $is_db_table . "` WHERE `id` = $id" );
											}

											// Entry does not / no longer exist, add csv data to data array
											foreach( array_keys( $csv_fields ) as $key ) {
												if ( isset( $data_arr[$key] ) ) {
													$data[$csv_fields[$key]] = $data_arr[$key];
												}
											}

											// Insert 'new' entry
											if ( isset ( $entry_functions[$is_db_table] ) ) {
												$iret = call_user_func_array( $entry_functions[$is_db_table], array( $data ) );
												if ( $iret ) {
													$photos_processed_csv++;
												}
												else {

													// Write back to original file
													fputs( $write_handle, $dataline );
													$photos_skipped_csv++;
													$this_skipped++;
												}
											}
											else {
												wppa_error_message( 'Table ' . $is_db_table . 'not supported' );
												return;
											}
										}
										else{
											wppa_error_message( 'Id field not positive numeric: '.$id );

											// Write back to original file
											fputs( $write_handle, $dataline );
											$photos_skipped_csv++;
											$this_skipped++;
										}
									}

									// Time up?
									if ( wppa_is_time_up() && wppa_switch( 'auto_continue' ) ) {
										wppa( 'continue', 'continue' );

										// Copy rest of file back to original
										while ( ! feof( $handle ) ) {
											$temp = fgets( $handle, 16*4096 );
											fputs( $write_handle, $temp );
										}
									}
								}
							}

							// Not a db table, a photo cusom data .csv file
							else {

								// Interprete header
								$captions = str_getcsv( $header );
								if ( ! is_array( $captions ) || count( $captions ) < '2' ) {
									wppa_error_message( __( 'Invalid header. Can not continue.', 'wp-photo-album-plus') );
									fclose( $handle );
									return;
								}

								// Verify or add cutom fields
								foreach ( array_keys( $captions ) as $captidx ) {
									if ( $captidx == '0' ) {
										if ( ! in_array( strtolower( trim( $captions['0'] ) ), array( 'name', 'photoname', 'filename' ) ) ) {
											wppa_error_message( __( 'Invalid header. First item must be \'name\', \'photoname\' or \'filename\'', 'wp-photo-album-plus') );
											fclose( $handle );
											return;
										}
									}
									elseif ( in_array( $captions[$captidx], $syst_lables ) ) {
										if ( $captions['0'] != 'filename' ) {
											wppa_error_message( __( 'Invalid header. First item must be \'filename\' when importing system data fields', 'wp-photo-album-plus' ) );
											fclose( $handle );
											return;
										}
									}
									elseif ( ! in_array( $captions[$captidx], $cust_labels ) ) {
										if ( ! in_array( '', $cust_labels ) ) {
											wppa_error_message( __( 'All available custom data fields are in use. There is no space for', 'wp-photo-album-plus') . ' ' . $captions[$captidx] );
											fclose( $handle );
											return;
										}

										// Add a new caption
										$i = '0';
										while ( $cust_labels[$i] ) $i++;
										$cust_labels[$i] = $captions[$captidx];
										wppa_update_option( 'wppa_custom_caption_' . $i, $cust_labels[$i] );
										wppa_update_option( 'wppa_custom_visible_' . $i, 'yes' );
										wppa_log( 'dbg', sprintf( __( 'New caption %s added.', 'wp-photo-album-plus'), $cust_labels[$i] ) );
									}
								}

								// Find the correlation between caption index and custom data index.
								// $custptrs is an array of custom data field numbers
								$custptrs = array();
								for ( $captidx = '1'; $captidx < count( $captions ); $captidx++ ) {
									if ( ! in_array( $captions[$captidx], $syst_lables ) ) {
										for ( $custidx = '0'; $custidx < '10'; $custidx++ ) {
											if ( $captions[$captidx] == $cust_labels[$custidx] ) {
												$custptrs[$custidx] = $captidx;
											}
										}
									}
								}

								// Find the correlation betwwn caption index and system data field names.
								// $systptrs is an array of system data field names. Key is data filed number, value is system field name
								$systptrs = array();
								for ( $captidx = '1'; $captidx < count( $captions ); $captidx++ ) {
									if ( in_array( $captions[$captidx], $syst_lables ) ) {
										$systptrs[$captidx] = $captions[$captidx];
									}
								}

								// Now process the lines
								while ( ! feof( $handle ) ) {
									$dataline = fgets( $handle, 4096 );
									if ( $dataline ) {
										wppa_log( 'dbg', __( 'Read data:', 'wp-photo-album-plus') . ' ' . trim( $dataline ) );
										$data_arr = str_getcsv( $dataline );
										foreach( array_keys( $data_arr ) as $i ) {
											if ( ! seems_utf8( $data_arr[$i] ) ) {
												$data_arr[$i] = utf8_encode( $data_arr[$i] );
											}
										}
										$search = $data_arr[0];
										switch ( strtolower($captions[0]) ) {
											case 'photoname':
												$photos = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPPA_PHOTOS."` WHERE `name` = %s", $data_arr[0] ), ARRAY_A );
												break;
											case 'filename':
												$photos = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPPA_PHOTOS."` WHERE `filename` = %s", $data_arr[0] ), ARRAY_A );
												break;
											case 'name':
												$photos = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPPA_PHOTOS."` WHERE `name` = %s OR `filename` = %s", $data_arr[0], $data_arr[0] ), ARRAY_A );
												break;
										}
										if ( $photos ) {
											foreach( $photos as $photo ) {
												$cust_data = $photo['custom'] ? unserialize( $photo['custom'] ) : array( '', '', '', '', '', '', '', '', '', '' );

												// Update custom fields
												foreach( array_keys( $custptrs ) as $idx ) {
													if ( isset( $data_arr[$custptrs[$idx]] ) ) {
														$cust_data[$idx] = wppa_sanitize_custom_field( $data_arr[$custptrs[$idx]] );
													}
													else {
														$cust_data[$idx] = '';
													}
												}
												wppa_update_photo( array( 'id' => $photo['id'], 'custom' => serialize( $cust_data ) ) );

												// Update system fields
												foreach( array_keys( $systptrs ) as $idx ) {
													$field = $systptrs[$idx];
													$value = stripslashes( $data_arr[$idx] );
													if ( ! seems_utf8( $value ) ) {
														$value = utf8_encode( $value );
													}
													if ( $value ) {
														switch ( $field ) {
															case 'album':
																if ( wppa_is_int( $value ) && wppa_album_exists( $value ) ) {
																	wppa_update_photo( array( 'id' => $photo['id'], $p => $value ) );
																}
																else {
																	wppa_wrong_value( $value, $field, __('Album does not exist', 'wp-photo-album-plus') );
																}
																break;
															case 'name':
																$value = sanitize_text_field( $value );
																wppa_update_photo( array( 'id' => $photo['id'], $field => $value ) );
																break;
															case 'description':
																wppa_update_photo( array( 'id' => $photo['id'], $field => $value ) );
																break;
															case 'linkurl':
																$url = esc_url_raw( $value );
																if ( $url ) {
																	$value = $url;
																	wppa_update_photo( array( 'id' => $photo['id'], $field => $value ) );
																}
																else {
																	wppa_wrong_value( $value, $field );
																}
																break;
															case 'linktitle':
																$value = sanitize_text_field( $value );
																wppa_update_photo( array( 'id' => $photo['id'], $field => $value ) );
																break;
															case 'linktarget':
																if ( $value == '_self' || $value == '_blank' ) {
																	wppa_update_photo( array( 'id' => $photo['id'], $field => $value ) );
																}
																else {
																	wppa_wrong_value( $value, $field );
																}
																break;
															case 'owner':
																$value = sanitize_user( $value );
																wppa_update_photo( array( 'id' => $photo['id'], $field => $value ) );
																break;
															case 'timestamp':
															case 'modified':
																if ( wppa_is_int( $value ) ) {
																	if ( $value > '0' && $value < time() ) {
																		wppa_update_photo( array( 'id' => $photo['id'], $field => $value ) );
																	}
																	else {
																		wppa_wrong_value( $value, $field, __( 'Timestamp out of range', 'wp-photo-album-plus' ) );
																	}
																}
																else {
																	wppa_wrong_value( $value, $field );
																}
																break;
															case 'status':
																if ( in_array( $value, array( 'pending', 'publish', 'featured', 'gold', 'silver', 'bronze', 'scheduled', 'private' ) ) ) {
																	wppa_update_photo( array( 'id' => $photo['id'], $field => $value ) );
																}
																else {
																	wppa_wrong_value( $value, $field );
																}
																break;
															case 'tags':
																$value = wppa_sanitize_tags( $value );
																wppa_update_photo( array( 'id' => $photo['id'], $field => $value ) );
																break;
															case 'alt':
																$value = sanitize_text_field( $value );
																wppa_update_photo( array( 'id' => $photo['id'], $field => $value ) );
																break;
															case 'location':
																break;
															case 'p_order':
															case 'views':
															case 'clicks':
																If ( wppa_is_int( $value ) && $value > 0 ) {
																	wppa_update_photo( array( 'id' => $photo['id'], $field => $value ) );
																}
																else {
																	wppa_wrong_value( $value, $field );
																}
																break;
															case 'exifdtm':
																if ( wppa_is_exif_date( $value ) ) {
																	wppa_update_photo( array( 'id' => $photo['id'], $field => $value ) );
																}
																else {
																	wppa_wrong_value( $value, $field );
																}
																break;
														}
													}
												}
												$photos_processed_csv ++;
											}
											wppa_log( 'dbg', 'Processed: ' . $data_arr[0] );
										}

										// This line could not be processed
										else {
											wppa_log( 'dbg', 'Could not find: ' . $data_arr[0] );

											// Write back to original file
											fputs( $write_handle, $dataline );
											$photos_skipped_csv++;
											$this_skipped++;
										}
										echo '.';
									}


									// Time up?
									if ( wppa_is_time_up() && wppa_switch( 'auto_continue' ) ) {
										wppa( 'continue', 'continue' );

										// Copy rest of file back to original
										while ( ! feof( $handle ) ) {
											$temp = fgets( $handle, 4096 );
											fputs( $write_handle, $temp );
										}
									}
								}
							}

							fclose( $handle );
							fclose( $write_handle );

							$csvcount++;

							// Remove tempfile
							unlink( $tempfile );

							// Remove orig file
							if ( ! $this_skipped && ! wppa_is_time_up() ) {
								unlink( $file );
							}
						}
					}
				}
			}
		}
	}

	wppa_ok_message( __( 'Done processing files.', 'wp-photo-album-plus') );

	if ( $pcount == '0' && $acount == '0' && $zcount == '0' && $dircount == '0' && $photocount == '0' && $videocount == '0' && $audiocount == '0' && $csvcount == '0' ) {
		wppa_warning_message( __( 'No files to import.', 'wp-photo-album-plus') );
	}
	else {
		$msg = '';
		if ( $zcount ) $msg .= $zcount.' '.__( 'Zipfiles extracted.', 'wp-photo-album-plus').' ';
		if ( $acount ) $msg .= $acount.' '.__( 'Albums created.', 'wp-photo-album-plus').' ';
		if ( $dircount ) $msg .= $dircount.' '.__( 'Directory to album imports.', 'wp-photo-album-plus').' ';
		if ( $photocount ) $msg .= ' '.sprintf( __( 'With total %s photos.', 'wp-photo-album-plus'), $photocount ).' ';
		if ( $pcount ) {
			if ( isset( $_POST['wppa-update'] ) ) {
				$msg .= $pcount.' '.__( 'Photos updated', 'wp-photo-album-plus' );
				if ( $totpcount != $pcount ) {
					$msg .= ' '.sprintf( __( 'to %s locations', 'wp-photo-album-plus'), $totpcount );
				}
				$msg .= '.';
			}
			else $msg .= $pcount.' '.__( 'single photos imported.', 'wp-photo-album-plus').' ';
		}
		if ( $videocount ) {
			$msg .= $videocount.' '.__( 'Videos imported.', 'wp-photo-album-plus' );
		}
		if ( $audiocount ) {
			$msg .= $audiocount.' '.__( 'Audios imported.', 'wp-photo-album-plus' );
		}
		if ( $csvcount ) {
			$msg .= $csvcount . ' ' . __( 'CSVs imported,', 'wp-photo-album-plus') . ' ' .
					$photos_processed_csv .' '. __( 'items processed.', 'wp-photo-album-plus' ) . ' ' .
					$photos_skipped_csv . ' ' . __( 'items skipped.', 'wp-photo-album-plus' );
		}
		wppa_ok_message( $msg );
		wppa_set_last_album( $album );
	}
}

function wppa_wrong_value( $value, $field, $extra = '' ) {
	$message = sprintf( __( 'Value %s is not valid for %s.', 'wp-photo-album-plus' ), $value, $field );
	if ( $extra ) {
		$message .= '<br />' . $extra;
	}
	$message .= '<br />' . __( 'This value is ignored.', 'wp-photo-album-plus' );
	wppa_error_message( $message );
}

function wppa_get_zipcount( $files ) {
	$result = 0;
	if ( $files ) {
		foreach ( $files as $file ) {
			$ext = strtolower( substr( strrchr( $file, "." ), 1 ) );
			if ( $ext == 'zip' ) $result++;
		}
	}
	return $result;
}

function wppa_get_albumcount( $files ) {
	$result = 0;
	if ( $files ) {
		foreach ( $files as $file ) {
			$ext = strtolower( substr( strrchr( $file, "." ), 1 ) );
			if ( $ext == 'amf' ) $result++;
		}
	}
	return $result;
}

function wppa_get_photocount( $files ) {
global $wppa_supported_photo_extensions;

	$result = 0;
	if ( $files ) {
		foreach ( $files as $file ) {
			$ext = strtolower( wppa_get_ext( $file ) );
			if ( in_array( $ext, $wppa_supported_photo_extensions ) ) $result++;
		}
	}
	return $result;
}

function wppa_get_video_count( $files ) {
global $wppa_supported_video_extensions;

	$result = 0;
	if ( $files ) {
		foreach ( $files as $file ) {
			$ext = strtolower( wppa_get_ext( $file ) );
			if ( in_array( $ext, $wppa_supported_video_extensions ) ) $result++;
		}
	}
	return $result;
}

function wppa_get_audio_count( $files ) {
global $wppa_supported_audio_extensions;

	$result = 0;
	if ( $files ) {
		foreach ( $files as $file ) {
			$ext = strtolower( wppa_get_ext( $file ) );
			if ( in_array( $ext, $wppa_supported_audio_extensions ) ) $result++;
		}
	}
	return $result;
}


// Find dir is new album candidates
function wppa_get_dircount( $files ) {
	$result = 0;
	if ( $files ) {
		foreach ( $files as $file ) {
			if ( basename( $file ) == '.' ) {}
			elseif ( basename( $file ) == '..' ) {}
			elseif ( is_dir( $file ) ) $result++;
		}
	}
	return $result;
}

// Find .csv file count
function wppa_get_csvcount( $files ) {
	$result = 0;
	if ( $files ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				if ( strtolower( wppa_get_ext( $file ) ) == 'csv' ) $result++;
			}
		}
	}
	return $result;
}

function wppa_get_meta_name( $file, $opt = '' ) {
	return wppa_get_meta_data( $file, 'name', $opt );
}
function wppa_get_meta_album( $file, $opt = '' ) {
	return wppa_get_meta_data( $file, 'albm', $opt );
}
function wppa_get_meta_desc( $file, $opt = '' ) {
	return wppa_get_meta_data( $file, 'desc', $opt );
}
function wppa_get_meta_porder( $file, $opt = '' ) {
	return wppa_get_meta_data( $file, 'pord', $opt );
}
function wppa_get_meta_linkurl( $file, $opt = '' ) {
	return wppa_get_meta_data( $file, 'lnku', $opt );
}
function wppa_get_meta_linktitle( $file, $opt = '' ) {
	return wppa_get_meta_data( $file, 'lnkt', $opt );
}

function wppa_get_meta_data( $file, $item, $opt ) {
	$result = '';
	$opt2 = '';
	if ( $opt == '( ' ) $opt2 = ' )';
	if ( $opt == '{' ) $opt2 = '}';
	if ( $opt == '[' ) $opt2 = ']';
	if ( is_file( $file ) ) {
		$handle = fopen( $file, "r" );
		if ( $handle ) {
			while ( ( $buffer = fgets( $handle, 4096 ) ) !== false ) {
				if ( substr( $buffer, 0, 5 ) == $item.'=' ) {
					if ( $opt == '' ) $result = substr( $buffer, 5, strlen( $buffer )-6 );
					else $result = $opt.__( substr( $buffer, 5, strlen( $buffer )-6 ) ).$opt2;		// Translate for display purposes only
				}
			}
			if ( !feof( $handle ) ) {
				_e( 'Error: unexpected fgets() fail in wppa_get_meta_data().', 'wp-photo-album-plus' );
			}
			fclose( $handle );
		}
	}
	return $result;
}


function wppa_extract( $xpath, $delz ) {
// There are two reasons that we do not allow the directory structure from the zipfile to be restored.
// 1. we may have no create dir access rights.
// 2. we can not reach the pictures as we only glob the users depot and not lower.
// We extract all files to the users depot.
// The illegal files will be deleted there by the wppa_sanitize_files routine,
// so there is no chance a depot/subdir/destroy.php or the like will get a chance to be created.
// dus...

	$err = '0';
	if ( ! class_exists( 'ZipArchive' ) ) {
		$err = '3';
		wppa_error_message( __( 'Class ZipArchive does not exist! Check your php configuration', 'wp-photo-album-plus') );
	}
	else {

		// Start security fix
		$path = wppa_sanitize_file_name( $xpath );
		if ( ! file_exists( $xpath ) ) {
			wppa_error_message( 'Zipfile '.$path.' does not exist.' );
//			unlink( $xpath );
			$err = '4';
			return $err;
		}
		// End security fix

		$ext = strtolower( wppa_get_ext( $xpath ) );
		if ( $ext == 'zip' ) {
			$zip = new ZipArchive;
			if ( $zip->open( $xpath ) === true ) {

				$supported_file_ext = array( 'jpg', 'png', 'gif', 'JPG', 'PNG', 'GIF', 'amf', 'pmf', 'zip', 'csv' );
				$done = '0';
				$skip = '0';
				for( $i = 0; $i < $zip->numFiles; $i++ ){
					$stat = $zip->statIndex( $i );
					$file_ext = @ end( explode( '.', $stat['name'] ) );

					if ( in_array( $file_ext, $supported_file_ext ) ) {
						$zip->extractTo( WPPA_DEPOT_PATH, $stat['name'] );
						$done++;
					}

					// Assuming that entries without a file extension are directries. No warning on directory.
					elseif ( strpos( $stat['name'], '.' ) !== false && strlen( $file_ext ) < 5 ) {
						wppa_warning_message( sprintf( __( 'File %s is of an unsupported filetype and has been ignored during extraction.', 'wp-photo-album-plus'), wppa_sanitize_file_name( $stat['name'] ) ) );
						$skip++;
					}
				}

				$zip->close();
				wppa_ok_message( sprintf( __( 'Zipfile %s processed. %s files extracted, %s files skipped.', 'wp-photo-album-plus'), basename( $path ), $done, $skip ) );
				if ( $delz ) unlink( $xpath );
			} else {
				wppa_error_message( __( 'Failed to extract', 'wp-photo-album-plus').' '.$path );
				$err = '1';
			}
		}
		else $err = '2';
	}
	return $err;
}

function wppa_import_dir_to_album( $file, $parent ) {
global $photocount;
global $wpdb;
global $wppa_session;

	// Session should survive the default hour
	wppa_extend_session();

	// see if album exists
	if ( is_dir( $file ) ) {

		// Check parent
		if ( wppa_switch( 'import_parent_check' ) ) {

			$alb = wppa_get_album_id( basename( $file ), $parent );

			// If parent = 0 ( top-level album ) and album not found,
			// try a 'separate' album ( i.e. parent = -1 ) with this name
			if ( ! $alb && $parent == '0' ) {
				$alb = wppa_get_album_id( basename( $file ), '-1' );
			}
		}

		// All albums have unique names, do'nt worry about parent
		else {
			$alb = wppa_get_album_id( basename( $file ), false );
		}

		if ( ! $alb ) {	// Album must be created
			$name	= basename( $file );
			$uplim	= wppa_opt( 'upload_limit_count' ). '/' . wppa_opt( 'upload_limit_time' );
			$alb = wppa_create_album_entry( array ( 'name' 		=> $name,
													'a_parent' 	=> $parent
													 ) );
			if ( $alb === false ) {
				wppa_error_message( __( 'Could not create album.', 'wp-photo-album-plus').'<br/>Query = '.$query );
				wp_die( 'Sorry, cannot continue' );
			}
			else {
				wppa_set_last_album( $alb );
				wppa_invalidate_treecounts( $alb );
				wppa_index_add( 'album', $alb );
				wppa_create_pl_htaccess();
				wppa_ok_message( __( 'Album #', 'wp-photo-album-plus') . ' ' . $alb . ' ( '.$name.' ) ' . __( 'Added.', 'wp-photo-album-plus') );
				if ( wppa_switch( 'newpag_create' ) && $parent <= '0' ) {

					// Create post object
					$my_post = array(
					  'post_title'    => $name,
					  'post_content'  => str_replace( 'w#album', $alb, wppa_opt( 'newpag_content' ) ),
					  'post_status'   => wppa_opt( 'newpag_status' ),
					  'post_type'	  => wppa_opt( 'newpag_type' )
					 );

					// Insert the post into the database
					$pagid = wp_insert_post( $my_post );
					if ( $pagid ) {
						wppa_ok_message( sprintf( __( 'Page <a href="%s" target="_blank" >%s</a> created.', 'wp-photo-album-plus'), home_url().'?page_id='.$pagid, $name ) );
						$wpdb->query( $wpdb->prepare( "UPDATE `".WPPA_ALBUMS."` SET `cover_linkpage` = %s WHERE `id` = %s", $pagid, $alb ) );
					}
					else {
						wppa_error_message( __( 'Could not create page.', 'wp-photo-album-plus') );
					}
				}
			}
		}

		// Now import the files
		// First escape special regexp chars
		$xfile = str_replace( array( '[', ']', '(', ')', '{', '}', '$', '+' ), array( '\[', '\]', '\(', '\)', '\{', '\}', '\$', '\+' ), $file );
		$photofiles = glob( $xfile.'/*' );
		if ( $photofiles ) foreach ( $photofiles as $photofile ) {
			if ( ! is_dir( $photofile ) ) {

				if ( ! isset( $wppa_session[$photofile] ) || ! wppa_switch( 'keep_import_files' ) ) {

					// If we find a .csv file, move it to our depot and give a warning message
					if ( wppa_get_ext( $photofile ) == 'csv' ) {
						copy( $photofile, WPPA_DEPOT_PATH . '/' . basename( $photofile ) );
						@ unlink( $photofile );
						wppa_warning_message( sprintf( __( '.csv file %s has been moved to your depot.', 'wp-photo-album-plus' ), basename( $photofile ) ) );
					}
					elseif ( wppa_albumphoto_exists( $alb, basename( $photofile ) ) ) {
						if ( ! wppa_switch( 'keep_import_files' ) ) {
							wppa_warning_message( 'Photo '.basename( $photofile ).' already exists in album '.$alb.'. Removed. (2)' );
						}
					}
					else {
						$bret = wppa_insert_photo( $photofile, $alb, basename( $photofile ) );
						$photocount++;
					}
					if ( ! wppa_switch( 'keep_import_files' ) ) {
						@ unlink( $photofile );
					}
					$wppa_session[$photofile] = true;
				}

				if ( wppa_is_time_up( $photocount ) ) return false;
			}
		}

		// Now go deeper, process the subdirs
		$subdirs = glob( $xfile.'/*' );
		if ( $subdirs ) foreach ( $subdirs as $subdir ) {
			if ( is_dir( $subdir ) ) {
				if ( basename( $subdir ) != '.' && basename( $subdir ) != '..' ) {
					$bret = wppa_import_dir_to_album( $subdir, $alb );
					if ( ! $bret ) return false;	// Time out
				}
			}
		}
		@ rmdir( $file );	// Try to remove dir, ignore error
	}
	else {
		wppa_dbg_msg( 'Invalid file in wppa_import_dir_to_album(): '.$file );
		return false;
	}
	return true;
}

function wppa_is_wppa_tree( $file ) {

	$temp = explode( '/uploads/wppa/', $file );
	if ( count( $temp ) === 2 ) {
		$temp[1] = wppa_expand_id( wppa_strip_ext( $temp[1] ) ) . '.' . wppa_get_ext( $temp[1] );
		$newf = implode( '/wppa/', $temp );
		wppa( 'is_wppa_tree', ( $newf != $file ) );
	}
	else {
		wppa( 'is_wppa_tree', false );
	}
	return wppa( 'is_wppa_tree' );
}

function wppa_compress_tree_path( $path ) {

	$result = $path;
	$temp = explode( '/wppa/', $path );
	if ( count( $temp ) == '2' ) {
		$temp[1] = str_replace( '/', '', $temp[1] );
		$result = implode( '/wppa/', $temp );
	}
	return $result;
}

function wppa_expand_tree_path( $path ) {

	$result = $path;
	$temp = explode( '/wppa/', $path );
	if ( count( $temp ) == '2' ) {
		$temp[1] = wppa_expand_id( wppa_strip_ext( $temp[1] ) ) . '.' . wppa_get_ext( $temp[1] );
		$result = implode( '/wppa/', $temp );
	}
	return $result;
}

function wppa_abs_walktree( $root, $source ) {
static $void_dirs;

	$result = '';

	// Init void dirs
	if ( ! $void_dirs ) {
		$void_dirs = array( '.', '..',
							'wp-admin',
							'wp-includes',
							'themes',
							'upgrade',
							'plugins',
							'languages',
							'wppa',
							( wppa_switch( 'allow_import_source') ? '' : 'wppa-source' ),
							);
	}

	// If currently in selected dir, set selected
	$sel = $root == $source ? ' selected="selected"' : '';

	// Set disabled if there are no files inside
	$n_files = count( glob( $root . '/*' ) );
	$n_dirs  = count( glob( $root . '/*', GLOB_ONLYDIR ) );
	$dis     = $n_files == $n_dirs ? ' disabled="disabled"' : '';

	// Check for (sub)depot
	$my_depot = __( '--- My depot --- ' ,'wp-photo-album-plus' );
	$display  = str_replace( WPPA_DEPOT_PATH, $my_depot, $root );
	if ( strpos( $display, $my_depot ) !== false ) {
		$dis = '';
	}

	// Check for ngg gallery dir
	$ngg_opts = get_option( 'ngg_options', false );
	if ( $ngg_opts ) {
		$ngg_gal =  __( '--- Ngg Galleries --- ', 'wp-photo-album-plus' );
		$display = str_replace( rtrim( $ngg_opts['gallerypath'], '/' ), $ngg_gal, $display );
		$pos = strpos( $display, $ngg_gal );
		if ( $pos ) {
			$display = substr( $display, $pos );
		}
	}

	// Remove ABSPATH from display string
	$display = str_replace( ABSPATH, '', $display );

	// Output the selecion if not in the wp dir
	if ( $root.'/' != ABSPATH ) {
		$result .=
			'<option' .
				' value="' . $root . '"' .
				$sel .
				$dis .
				' data-nfiles="' . $n_files . '"' .
				' data-ndirs="' . $n_dirs . '"' .
				' >' .
				$display .
			'</option>';
	}

	// See if subdirs exist
	$dirs = glob( $root . '/*', GLOB_ONLYDIR );

	// Go deeper if not in a list of void disnames
	if ( $dirs ) foreach( $dirs as $path ) {
		$dir = basename( $path );
		if ( ! in_array( $dir, $void_dirs ) ) {
			$newroot = $root . '/' . $dir;
			$result .= wppa_abs_walktree( $newroot, $source );
		}
	}

	return $result;
}