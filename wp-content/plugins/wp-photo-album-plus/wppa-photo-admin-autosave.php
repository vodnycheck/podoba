<?php
/* wppa-photo-admin-autosave.php
* Package: wp-photo-album-plus
*
* edit and delete photos
* Version 6.8.00
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

// Edit photo for owners of the photo(s) only
function _wppa_edit_photo() {

	// Check input
	wppa_vfy_arg( 'photo' );

	// Edit one Photo
	if ( isset( $_GET['photo'] ) ) {
		$photo = $_GET['photo'];
		$thumb = wppa_cache_thumb( $photo );
		if ( $thumb['owner'] == wppa_get_user() ) {
			echo
			'<div class="wrap">' .
				'<h2>' . __( 'Edit photo' , 'wp-photo-album-plus') . '</h2>';
				wppa_album_photos( '', $photo );
			echo
			'</div>';
		}
		else {
			wp_die( 'You do not have the rights to do this' );
		}
	}

	// Edit all photos owned by current user
	else {
		echo
		'<div class="wrap">' .
			'<h2>' . __( 'Edit photos' , 'wp-photo-album-plus') . '</h2>';
			wppa_album_photos( '', '', wppa_get_user() );
		echo
		'</div>';
	}
}

// Moderate photos
function _wppa_moderate_photos() {

	// Check input
	wppa_vfy_arg( 'photo' );

	if ( isset( $_GET['photo'] ) ) {
		$photo = $_GET['photo'];
	}
	else $photo = '';

	echo
	'<div class="wrap">' .
		'<h2>' . __( 'Moderate photos' , 'wp-photo-album-plus') . '</h2>';
		if ( wppa_switch( 'moderate_bulk' ) ) {
			wppa_album_photos_bulk( 'moderate' );
		}
		else {
			wppa_album_photos( '', $photo, '', true );
		}
	echo
	'</div>';
}

// The photo edit list. Also used in wppa-album-admin-autosave.php
function wppa_album_photos( $album = '', $photo = '', $owner = '', $moderate = false ) {
global $wpdb;

	// Check input
	wppa_vfy_arg( 'wppa-page' );

	$pagesize 	= wppa_opt( 'photo_admin_pagesize' );
	$page 		= isset ( $_GET['wppa-page'] ) ? $_GET['wppa-page'] : '1';
	$skip 		= ( $page - '1' ) * $pagesize;
	$limit 		= ( $pagesize < '1' ) ? '' : ' LIMIT ' . $skip . ',' . $pagesize;

	// Edit the photos in a specific album
	if ( $album ) {

		// Special album case: search (see last album line in album table)
		if ( $album == 'search' ) {
			$count 	= wppa_get_edit_search_photos( '', 'count_only' );
			$photos = wppa_get_edit_search_photos( $limit );
			$link 	= wppa_dbg_url( 	get_admin_url() . 'admin.php' .
										'?page=wppa_admin_menu' .
										'&tab=edit' .
										'&edit_id=' . $album .
										'&wppa_nonce=' . wp_create_nonce('wppa_nonce') .
										'&wppa-searchstring=' . wppa_sanitize_searchstring( $_REQUEST['wppa-searchstring'] )
									);
		}

		// Edit trashed photos
		elseif ( $album == 'trash' ) {
			$count  = $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPPA_PHOTOS . "` WHERE `album` < '0'" );
			$photos = $wpdb->get_results( "SELECT * FROM `" . WPPA_PHOTOS . "` WHERE `album` < '0' ORDER BY `modified` DESC " . $limit, ARRAY_A );
		//	$count 	= count( $photos );
			$link 	= wppa_dbg_url( 	get_admin_url() . 'admin.php' .
										'?page=wppa_admin_menu' .
										'&tab=edit' .
										'&edit_id=trash' .
										'&wppa_nonce=' . wp_create_nonce('wppa_nonce')
									);
		}

		// A single photo
		elseif ( $album == 'single' ) {
			$p = strval( intval( $_REQUEST['photo'] ) );
			$count 	= $p ? 1 : 0;
			$photos = $wpdb->get_results( "SELECT * FROM `" . WPPA_PHOTOS . "` WHERE `id` = '$p'", ARRAY_A );
			$count 	= count( $photos );
			$link 	= '';
		}

		// A physical album
		else {
			$counts = wppa_get_treecounts_a( $album, true );
			$count 	= $counts['selfphotos'] + $counts['pendselfphotos'] + $counts['scheduledselfphotos'];
			$photos = $wpdb->get_results( $wpdb->prepare( 	"SELECT * " .
															"FROM `" . WPPA_PHOTOS . "` " .
															"WHERE `album` = %s " .
															wppa_get_photo_order( $album, 'norandom' ) .
															$limit,
															$album
														), ARRAY_A
										);
			$link 	= wppa_dbg_url( 	get_admin_url() . 'admin.php' .
										'?page=wppa_admin_menu' .
										'&tab=edit' .
										'&edit_id=' . $album .
										'&wppa_nonce=' . wp_create_nonce('wppa_nonce')
									);
		}
	}

	// Edit a single photo
	elseif ( $photo && ! $moderate ) {
		$count 	= '1';
		$photos = $wpdb->get_results( $wpdb->prepare( 	"SELECT * " .
														"FROM `" . WPPA_PHOTOS . "` " .
														"WHERE `id` = %s",
														$photo
													), ARRAY_A
									);
		$link 	= '';
	}

	// Edit the photos of a specific owner
	elseif ( $owner ) {
		$count 	= $wpdb->get_var( $wpdb->prepare( 	"SELECT COUNT(*) " .
													"FROM `" . WPPA_PHOTOS . "` " .
													"WHERE `owner` = %s",
													$owner
													)
								);
		$photos = $wpdb->get_results( $wpdb->prepare( 	"SELECT * " .
														"FROM `" . WPPA_PHOTOS . "` " .
														"WHERE `owner` = %s " .
														"ORDER BY `timestamp` DESC " .
														$limit,
														$owner
													), ARRAY_A
									);
		$link 	= wppa_dbg_url( get_admin_url() . 'admin.php' . '?page=wppa_edit_photo' . '&wppa_nonce=' . wp_create_nonce('wppa_nonce') );
	}

	// Moderate photos
	elseif ( $moderate ) {

		// Can i moderate?
		if ( ! current_user_can( 'wppa_moderate' ) ) {
			wp_die( __( 'You do not have the rights to do this' , 'wp-photo-album-plus') );
		}

		// Moderate a single photo
		if ( $photo ) {
			$count 	= '1';
			$photos = $wpdb->get_results( $wpdb->prepare( 	"SELECT * " .
															"FROM `" . WPPA_PHOTOS . "` " .
															"WHERE `id` = %s",
															$photo
														), ARRAY_A
										);
			$link 	= '';
		}

		// Are there photos with pending comments?
		else {
			$cmt = $wpdb->get_results( 	"SELECT `photo` " .
										"FROM `" . WPPA_COMMENTS . "` " .
										"WHERE `status` = 'pending' " .
										"OR `status` = 'spam'",
										ARRAY_A
									);

			if ( $cmt ) {
				$orphotois = '';
				foreach ( $cmt as $c ) {
					$orphotois .= "OR `id` = " . $c['photo'] . " ";
				}
			}
			else $orphotois = '';
			$count 	= $wpdb->get_var( 	"SELECT COUNT(*) " .
										"FROM `" . WPPA_PHOTOS . "` " .
										"WHERE `status` = 'pending' " .
										$orphotois
									);
			$photos = $wpdb->get_results( 	"SELECT * " .
											"FROM `" . WPPA_PHOTOS . "` " .
											"WHERE `status` = 'pending' " . $orphotois . " " .
											"ORDER BY `album` DESC, `timestamp` DESC " .
											$limit, ARRAY_A
										);
			$link 	= wppa_dbg_url( get_admin_url() . 'admin.php' . '?page=wppa_moderate_photos' . '&wppa_nonce=' . wp_create_nonce('wppa_nonce') );
		}

		// No photos to moderate
		if ( empty( $photos ) ) {

			// Single photo moderate requested
			if ( $photo ) {
				echo
				'<p>' .
					__( 'This photo is no longer awaiting moderation.' , 'wp-photo-album-plus') .
				'</p>';
			}

			// Multiple photos to moderate requested
			else {
				echo
				'<p>' .
					__( 'There are no photos awaiting moderation at this time.' , 'wp-photo-album-plus') .
				'</p>';
			}

			// If i am admin, i can edit all photos here, sorted by timestamp desc
			if ( wppa_user_is( 'administrator' ) ) {
				echo
				'<h3>' .
					__( 'Manage all photos by timestamp' , 'wp-photo-album-plus') .
				'</h3>';
				$count 	= $wpdb->get_var( 	"SELECT COUNT(*) " .
											"FROM `" . WPPA_PHOTOS . "`"
										);
				$photos = $wpdb->get_results( 	"SELECT * " .
												"FROM `" . WPPA_PHOTOS . "` " .
												"ORDER BY `timestamp` DESC" .
												$limit,
												ARRAY_A
											);
				$link 	= wppa_dbg_url( get_admin_url() . 'admin.php' . '?page=wppa_moderate_photos' . '&wppa_nonce=' . wp_create_nonce('wppa_nonce') );
			}

			// Nothing to do
			else {
				return;
			}
		}
	}

	// If not one of the cases above apply, print error and quit
	else {
		wppa_dbg_msg( 'Missing required argument in wppa_album_photos() 1', 'red', 'force' );
		return;
	}

	// Quick edit skips a few time consuming settings like copy and move to other album
	$quick = isset( $_REQUEST['quick'] );
	if ( $link && $quick ) $link .= '&quick';

	// In case it is a seaerch and edit, show the search statistics
	wppa_show_search_statistics();

	// If no photos selected produce apprpriate message and quit
	if ( empty( $photos ) ) {

		// A specific photo requested
		if ( $photo ) {
			echo
			'<div id="photoitem-' . $photo . '" class="photoitem" style="width:100%; background-color: rgb( 255, 255, 224 ); border-color: rgb( 230, 219, 85 );">' .
				'<span style="color:red">' .
					sprintf( __( 'Photo %s has been removed.' , 'wp-photo-album-plus'), $photo ) .
				'</span>' .
			'</div>';
		}

		// A collection of photos requested
		else {

			// Search
			if ( isset( $_REQUEST['wppa-searchstring'] ) ) {
				echo
				'<h3>' .
					__( 'No photos matching your search criteria.' , 'wp-photo-album-plus') .
				'</h3>';
			}

			// Album
			else {
				echo
				'<h3>' .
					__( 'No photos yet in this album.' , 'wp-photo-album-plus') .
				'</h3>';
			}
		}

		return;
	}

	// There are photos to display for editing
	else {

		// Local js functions placed here as long as there is not yet a possibility to translate texts in js files
		?>
<script>
function wppaTryMove( id, video ) {

	var query;

	if ( ! jQuery( '#target-' + id ).val() ) {
		alert( '<?php echo esc_js( __( 'Please select an album to move to first.', 'wp-photo-album-plus' ) ) ?>' );
		return false;
	}

	if ( video ) {
		query = '<?php echo esc_js( __( 'Are you sure you want to move this video?', 'wp-photo-album-plus' ) ) ?>';
	}
	else {
		query = '<?php echo esc_js( __( 'Are you sure you want to move this photo?', 'wp-photo-album-plus' ) ) ?>';
	}

	if ( confirm( query ) ) {
		wppaAjaxUpdatePhoto( id, 'moveto', document.getElementById( 'target-' + id ) );
	}
}

function wppaTryCopy( id, video ) {

	var query;

	if ( ! jQuery( '#target-' + id ).val() ) {
		alert( '<?php echo esc_js( __( 'Please select an album to copy to first.', 'wp-photo-album-plus' ) ) ?>' );
		return false;
	}

	if ( video ) {
		query = '<?php echo esc_js( __( 'Are you sure you want to copy this video?', 'wp-photo-album-plus' ) ) ?>';
	}
	else {
		query = '<?php echo esc_js( __( 'Are you sure you want to copy this photo?', 'wp-photo-album-plus' ) ) ?>';
	}

	if ( confirm( query ) ) {
		wppaAjaxUpdatePhoto( id, 'copyto', document.getElementById( 'target-' + id ) );
	}
}

function wppaTryDelete( id, video ) {

	var query;

	if ( video ) {
		query = '<?php echo esc_js( __( 'Are you sure you want to delete this video?', 'wp-photo-album-plus' ) ) ?>';
	}
	else {
		query = '<?php echo esc_js( __( 'Are you sure you want to delete this photo?', 'wp-photo-album-plus' ) ) ?>';
	}

	if ( confirm( query ) ) {
		wppaAjaxDeletePhoto( id );
	}
}

function wppaTryUndelete ( id ) {
	wppaAjaxUndeletePhoto( id );
}

function wppaTryRotLeft( id ) {

	var query = '<?php echo esc_js( __( 'Are you sure you want to rotate this photo left?', 'wp-photo-album-plus' ) ) ?>';

	if ( confirm( query ) ) {
		wppaAjaxUpdatePhoto( id, 'rotleft', 0, <?php echo ( wppa( 'front_edit' ) ? 'false' : 'true' ) ?> );
	}
}

function wppaTryRot180( id ) {

	var query = '<?php echo esc_js( __( 'Are you sure you want to rotate this photo 180&deg;?', 'wp-photo-album-plus' ) ) ?>';

	if ( confirm( query ) ) {
		wppaAjaxUpdatePhoto( id, 'rot180', 0, <?php echo ( wppa( 'front_edit' ) ? 'false' : 'true' ) ?> );
	}
}

function wppaTryRotRight( id ) {

	var query = '<?php echo esc_js( __( 'Are you sure you want to rotate this photo right?', 'wp-photo-album-plus' ) ) ?>';

	if ( confirm( query ) ) {
		wppaAjaxUpdatePhoto( id, 'rotright', 0, <?php echo ( wppa( 'front_edit' ) ? 'false' : 'true' ) ?> );
	}
}

function wppaTryFlip( id ) {

	var query = '<?php echo esc_js( __( 'Are you sure you want to flip this photo?', 'wp-photo-album-plus' ) ) ?>';

	if ( confirm( query ) ) {
		wppaAjaxUpdatePhoto( id, 'flip', 0, <?php echo ( wppa( 'front_edit' ) ? 'false' : 'true' ) ?> );
	}
}

function wppaTryFlop( id ) {

	var query = '<?php echo esc_js( __( 'Are you sure you want to flip this photo?', 'wp-photo-album-plus' ) ) ?>';

	if ( confirm( query ) ) {
		wppaAjaxUpdatePhoto( id, 'flop', 0, <?php echo ( wppa( 'front_edit' ) ? 'false' : 'true' ) ?> );
	}
}

function wppaTryWatermark( id ) {

	var wmFile = jQuery( '#wmfsel_' + id ).val();
	if ( wmFile == '--- none ---' ) {
		alert( '<?php echo esc_js( __( 'No watermark selected', 'wp-photo-album-plus' ) ) ?>' );
		return;
	}
	var query = '<?php echo esc_js( __( 'Are you sure? Once applied it can not be removed!', 'wp-photo-album-plus' ) ) ?>';
	query += '\n';
	query += '<?php echo esc_js( __( 'And I do not know if there is already a watermark on this photo', 'wp-photo-album-plus' ) ) ?>';

	if ( confirm( query ) ) {
		wppaAjaxApplyWatermark( id, document.getElementById( 'wmfsel_' + id ).value, document.getElementById( 'wmpsel_' + id ).value );
	}
}

function wppaTryMagick( id, slug ) {

	var query = '<?php echo esc_js( __( 'Are you sure you want to magically process this photo?', 'wp-photo-album-plus' ) ) ?>';

	if ( true || confirm( query ) ) {
		jQuery( '#wppa-admin-spinner' ).css( 'display', 'inline' );
		_wppaAjaxUpdatePhoto( id, slug, 0, false ); //<?php echo ( wppa( 'front_edit' ) ? 'false' : 'true' ) ?> );
	}
}

wppaHor = false;
function wppaToggleHorizon() {
	if ( wppaHor ) {
		jQuery( '#horizon' ).css( 'display', 'none' );
		wppaHor = false;
	}
	else {
		jQuery( '#horizon' ).css( 'display', 'inline' );
		wppaHor = true;
	}
}

function wppaTryScheduledel( id ) {
	wppaPhotoStatusChange( id );
	if ( jQuery( '#scheduledel-' + id ).attr( 'checked' ) != 'checked' ) {
		_wppaAjaxUpdatePhoto( id, 'removescheduledel', 0, true );
	}
}

function wppaToggleExif( id, count ) {

	if ( jQuery( '#wppa-exif-' + id ).css( 'display' ) == 'none' ) {
		jQuery( '#wppa-exif-' + id ).show();
		jQuery( '#wppa-exif-button-' + id ).attr( 'value', '<?php _e( 'Hide', 'wp-photo-album-plus' ) ?> ' + count + ' <?php _e( 'EXIF items', 'wp-photo-album-plus' ) ?>' );
	}
	else {
		jQuery( '#wppa-exif-' + id ).hide();
		jQuery( '#wppa-exif-button-' + id ).attr( 'value', '<?php _e( 'Show', 'wp-photo-album-plus' ) ?> ' + count + ' <?php _e( 'EXIF items', 'wp-photo-album-plus' ) ?>' );
	}
}

</script>
<?php


		$mvt = esc_attr( __( 'Move video', 'wp-photo-album-plus' ) );
		$mpt = esc_attr( __( 'Move photo', 'wp-photo-album-plus' ) );
		$cvt = esc_attr( __( 'Copy video', 'wp-photo-album-plus' ) );
		$cpt = esc_attr( __( 'Copy photo', 'wp-photo-album-plus' ) );


		// Display the pagelinks
//		echo 'page_links called with: '.$page.' '.$pagesize.' '.$count.' '.$link;
		wppa_admin_page_links( $page, $pagesize, $count, $link );

		// Horizon
		echo '<hr id="horizon" style="position:fixed;top:300px;left:0px;border:none;background-color:#777777;z-index:100000;display:none;height:1px;width:100%;" />';

		// Albun name if moderate
		static $modalbum;

		// Display all photos
		foreach ( $photos as $photo ) {

			// We may not use extract(), so we do something like it here manually, hence controlled.
			$id 			= $photo['id'];
			$timestamp 		= $photo['timestamp'];
			$modified 		= $photo['modified'];
			$owner 			= $photo['owner'];
			$crypt 			= $photo['crypt'];
			$album 			= $photo['album'];
			$name 			= stripslashes( $photo['name'] );
			$description 	= stripslashes( $photo['description'] );
			$exifdtm 		= $photo['exifdtm'];
			$views 			= $photo['views'];
			$clicks 		= $photo['clicks'];
			$p_order 		= $photo['p_order'];
			$linktarget 	= $photo['linktarget'];
			$linkurl 		= $photo['linkurl'];
			$linktitle 		= stripslashes( $photo['linktitle'] );
			$alt 			= stripslashes( $photo['alt'] );
			$filename 		= $photo['filename'];
			$videox 		= $photo['videox'];
			$videoy 		= $photo['videoy'];
			$location 		= $photo['location'];
			$status 		= $photo['status'];
			$tags 			= trim( stripslashes( $photo['tags'] ), ',' );
			$stereo 		= $photo['stereo'];
			$magickstack 	= $photo['magickstack'];
			$scheduledel 	= $photo['scheduledel'];

			// See if item is a multimedia item
			$is_multi 		= wppa_is_multi( $id );
			$is_video 		= wppa_is_video( $id );			// returns array of extensions
			$b_is_video 	= empty( $is_video ) ? 0 : 1; 	// boolean
			$has_audio 		= wppa_has_audio( $id );		// returns array of extensions
			$b_has_audio 	= empty( $has_audio ) ? 0 : 1; 	// boolean

			// Various usefull vars
			$owner_editable = wppa_switch( 'photo_owner_change' ) && wppa_user_is( 'administrator' );
			switch ( wppa_get_album_item( $album, 'p_order_by' ) ) {
				case '0':
					$temp = wppa_opt( 'list_photos_by' );
					$sortby_orderno = ( $temp == '-1' || $temp == '1' );
					break;
				case '-1':
				case '1':
					$sortby_orderno = true;
					break;
				default:
					$sortby_orderno = false;
			}
			$wms 	= array( 'toplft' => __( 'top - left' , 'wp-photo-album-plus'), 'topcen' => __( 'top - center' , 'wp-photo-album-plus'), 'toprht' => __( 'top - right' , 'wp-photo-album-plus'),
							 'cenlft' => __( 'center - left' , 'wp-photo-album-plus'), 'cencen' => __( 'center - center' , 'wp-photo-album-plus'), 'cenrht' => __( 'center - right' , 'wp-photo-album-plus'),
							 'botlft' => __( 'bottom - left' , 'wp-photo-album-plus'), 'botcen' => __( 'bottom - center' , 'wp-photo-album-plus'), 'botrht' => __( 'bottom - right' , 'wp-photo-album-plus'), );

			// Album for moderate
			if ( $modalbum != $album ) {
				echo '<h3>' . sprintf( 	__( 'Edit/Moderate photos from album %s by %s', 'wp-photo-album-plus' ),
										'<i>' . wppa_get_album_name( $album ) . '</i>',
										'<i>' . wppa_get_album_item( $album, 'owner' ) . '</i>' ) . '</h3>';
				$modalbum = $album;
			}

			echo 	// Anchor for scroll to
			"\n" . '<a id="photo_' . $id . '" ></a>';

			echo	// The photo data
			'<div' .
				' id="photoitem-' . $id . '"' .
				' class="wppa-table-wrap"' .
				' style="width:100%;position:relative;"' .
				' >';

				echo	// Photo specific nonce field
				'<input' .
					' type="hidden"' .
					' id="photo-nonce-' . $id . '"' .
					' value="' . wp_create_nonce( 'wppa_nonce_' . $id ) . '"' .
				' />';

				echo 	// Section 1
				"\n" . '<!-- Section 1 -->' .
				'<table' .
					' class="wppa-table wppa-photo-table"' .
					' style="width:100%;"' .
					' >' .
					'<tbody>';

						// -- Preview thumbnail ---
						echo
						'<tr>' .
							'<td>';
								// If ImageMagick is enabled...
								// Fake 'for social media' to use the local file here, not cloudinary.
								// Files from cloudinary do not reload, even with ?ver=...
								if ( wppa_can_admin_magick( $id ) ) {
									wppa( 'for_sm', true );
								}

								$src = wppa_get_thumb_url( $id, false );
								$big = wppa_get_photo_url( $id, false );

								if ( wppa_can_admin_magick( $id ) ) {
									wppa( 'for_sm', false );
								}

								if ( $is_video ) {
									reset( $is_video );
									$big = str_replace( 'xxx', current( $is_video ), $big );
									echo
									'<a' .
										' href="' . $big . '"' .
										' target="_blank"' .
										' title="' . esc_attr( __( 'Preview fullsize video' , 'wp-photo-album-plus') ) . '"' .
										' >' .
										wppa_get_video_html( array( 	'id' 		=> $id,
																		'tagid' 	=> 'video-' . $id,
																		'width' 	=> '160',
																		'height' 	=> '160' * wppa_get_videoy( $id ) / wppa_get_videox( $id ),
																		'controls' 	=> false,
																		'use_thumb' => true
																	) ) .
									'</a>';
								}
								else {
									if ( $has_audio ) {
										$src = wppa_get_thumb_url( $id );
										$big = wppa_get_photo_url( $id );
									}
									echo
									'<a' .
										' id="fs-a-' . $id . '"' .
										' href="' . $big . '"' .
										' target="_blank"' .
										' title="' . esc_attr( __( 'Preview fullsize photo', 'wp-photo-album-plus' ) ) . '"' .
										' >' .
										'<img' .
											' id="tnp-' . $id . '"' .
											' src="' . $src . '"' .
											' alt="' . esc_attr( $name ) . '"' .
											' style="max-width: 160px; vertical-align:middle;"' .
										' />' .
									'</a>';
									if ( $has_audio ) {
										$audio = wppa_get_audio_html( array( 	'id' 		=> $id,
																				'tagid' 	=> 'audio-' . $id,
																				'width' 	=> '160',
																				'controls' 	=> true
																			) );
										echo
										'<br />' .
										( $audio ? $audio : '<span style="color:red;">' .
																__( 'Audio disabled' , 'wp-photo-album-plus') .
															'</span>' );
									}
								}
							echo
							'</td>';

							echo
							'<td>' .

								// --- More or less static data ---

								// ID
								'ID = ' . $id . '. ' .

								// Crypt
								__( 'Crypt:', 'wp-photo-album-plus' ) . ' ' . $crypt . '. ' .

								// Filename
								__( 'Filename:', 'wp-photo-album-plus' ) . ' ' . $filename . '. ' .

								// Upload
								__( 'Upload:', 'wp-photo-album-plus' ) . ' ' . wppa_local_date( '', $timestamp ) . ' ' . __( 'local time' , 'wp-photo-album-plus') . '. ' .

								// Owner
								( $owner_editable ? '' : __( 'By:', 'wp-photo-album-plus' ) . ' ' . $owner );
								if ( $owner_editable ) {
									echo
									__( 'Owned by:', 'wp-photo-album-plus' ) .
									'<input' .
										' type="text"' .
										' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'owner\', this )"' .
										' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'owner\', this )"' .
										' value="' . $owner . '"' .
									' />';
								}
								echo
								' ';

								// Album
								$deleted = false;
								if ( $album <= '-9' ) {
									$album = - ( $album + '9' );
									$deleted = true;
								}
								echo
								sprintf( __( 'Album: %d (%s).' , 'wp-photo-album-plus'), $album, wppa_get_album_name( $album ) );

								// Modified
								if ( $deleted ) {
									echo
									'<span style="color:red;" >' .
									__( 'Trashed', 'wp-photo-album-plus' ) .
									'</span>';
								}
								else {
									if ( $modified > $timestamp ) {
										echo
										' ' . __( 'Modified:', 'wp-photo-album-plus' ) . ' ' .
										wppa_local_date( '', $modified ) . ' ' . __( 'local time', 'wp-photo-album-plus' );
									}
									else {
										echo
										' ' . __( 'Not modified', 'wp-photo-album-plus' );
									}
								}
								echo
								'. ' .

								// Exif
								__( 'EXIF Date:', 'wp-photo-album-plus' );
								if ( wppa_user_is( 'administrator' ) ) { // Admin may edit exif date
									echo
									'<input' .
										' type="text"' .
										' style="width:125px;"' .
										' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'exifdtm\', this )"' .
										' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'exifdtm\', this )"' .
										' value="' . $exifdtm . '"' .
									' />';
								}
								else {
									echo $exifdtm . '.';
								}
								echo
								' ';

								// Location
								if ( $photo['location'] || wppa_switch( 'geo_edit' ) ) {
									echo
									__( 'Location:' , 'wp-photo-album-plus') . ' ';
									$loc = $location ? $location : '///';
									$geo = explode( '/', $loc );
									echo $geo['0'].' '.$geo['1'].'. ';
									if ( wppa_switch( 'geo_edit' ) ) {
										echo
										__( 'Lat:', 'wp-photo-album-plus' ) .
										'<input' .
											' type="text"' .
											' style="width:100px;"' .
											' id="lat-' . $id . '"' .
											' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'lat\', this );"' .
											' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'lat\', this );"' .
											' value="' . $geo['2'] . '"' .
										' />' .
										__( 'Lon:', 'wp-photo-album-plus' ) .
										'<input type="text"' .
											' style="width:100px;"' .
											' id="lon-' . $id . '"' .
											' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'lon\', this );"' .
											' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'lon\', this );"' .
											' value="' . $geo['3'] . '"' .
										' />';
									}
								}

								// Changeable p_order
								echo
								__( 'Photo sort order #:', 'wp-photo-album-plus' );
								if ( $sortby_orderno && ( ! wppa_switch( 'porder_restricted' ) || wppa_user_is( 'administrator' ) ) ) {
									echo
									'<input' .
										' type="text"' .
										' id="porder-' . $id . '"' .
										' value="' . $p_order . '"' .
										' style="width:30px;"' .
										' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'p_order\', this )"' .
										' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'p_order\', this )"' .
									' />' .
									' ';
								}
								else {
									echo
									$p_order . '. ';
								}

								// Rating
								$entries = wppa_get_rating_count_by_id( $id );
								if ( $entries ) {

									if ( wppa_opt( 'rating_display_type' ) == 'likes' ) {
										echo __( 'Likes:', 'wp-photo-album-plus' ) . ' ' .
										$entries .
										'. ';
									}
									else {
										echo
										__( 'Rating:', 'wp-photo-album-plus' ) . ' ' .
										__( 'Entries:', 'wp-photo-album-plus' ) . ' ' . $entries .
										', ' .
										__( 'Mean value:', 'wp-photo-album-plus' ) .
										' ' .
										wppa_get_rating_by_id( $id, 'nolabel' ) .
										'. ';
									}
								}
								else {
									echo
									__( 'No ratings for this photo.', 'wp-photo-album-plus' ) . ' ';
								}
								$dislikes = wppa_dislike_get( $id );
								if ( $dislikes ) {
									echo
									'<span style="color:red" >' .
										sprintf( _n( 'Disliked by %d visitor', 'Disliked by %d visitors', $dislikes, 'wp-photo-album-plus' ), $dislikes ) . '. ' .
									'</span>';
								}
								$pending = wppa_pendrat_get( $id );
								if ( $pending ) {
									echo
									'<span style="color:orange" >' .
										sprintf( __( '%d pending votes.', 'wp-photo-album-plus' ), $pending ) . ' ' .
									'</span>';
								}

								// Views
								if ( wppa_switch( 'track_viewcounts' ) ) {
									echo
									__( 'Views' , 'wp-photo-album-plus' ) . ': ' .
									$views .
									'. ';
								}

								// Clicks
								if ( wppa_switch( 'track_clickcounts' ) ) {
									echo
									__( 'Clicks', 'wp-photo-album-plus' ) . ': ' .
									$clicks .
									'. ';
								}

								// Status
								echo '<br />' .
								__( 'Status:' , 'wp-photo-album-plus') . ' ';
								if ( ( current_user_can( 'wppa_admin' ) || current_user_can( 'wppa_moderate' ) ) ) {
									if ( wppa_switch( 'ext_status_restricted' ) && ! wppa_user_is( 'administrator' ) ) {
										$dis = ' disabled="disabled"';
									}
									else {
										$dis = '';
									}
									$sel = ' selected="selected"';
									echo
									'<select' .
										' id="status-' . $id . '"' .
										' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'status\', this ); wppaPhotoStatusChange( ' . $id . ' );"' .
										' >' .
										'<option value="pending"' .	( $status == 'pending' ? $sel : '' ) . ' >' .
											__( 'Pending', 'wp-photo-album-plus' ) .
										'</option>' .
										'<option value="publish"' . ( $status =='publish' ? $sel : '' ) . ' >' .
											__( 'Publish', 'wp-photo-album-plus' ) .
										'</option>' .
										'<option value="featured"' . ( $status == 'featured' ? $sel : '' ) . $dis . ' >' .
											__( 'Featured', 'wp-photo-album-plus' ) .
										'</option>' .
										'<option value="gold"' . ( $status == 'gold' ? $sel : '' ) . $dis . ' >' .
											__( 'Gold', 'wp-photo-album-plus' ) .
										'</option>' .
										'<option value="silver"' . ( $status == 'silver' ? $sel : '' ) . $dis . ' >' .
											__( 'Silver', 'wp-photo-album-plus' ) .
										'</option>' .
										'<option value="bronze"' . ( $status == 'bronze' ? $sel : '' ) . $dis . ' >' .
											__( 'Bronze', 'wp-photo-album-plus' ) .
										'</option>' .
										'<option value="scheduled"' . ( $status == 'scheduled' ? $sel : '' ) . $dis . ' >' .
											__( 'Scheduled', 'wp-photo-album-plus' ) .
										'</option>' .
										'<option value="private"' . ( $status == 'private' ? $sel : '' ) . $dis . ' >' .
											__( 'Private', 'wp-photo-album-plus' ) .
										'</option>' .
									'</select>' .
									wppa_get_date_time_select_html( 'photo', $id, true );
								}
								else {
									echo
									'<input' .
										' type="hidden"' .
										' id="status-' . $id . '"' .
										' value="' . $status . '"' .
									' />';
									if ( $status == 'pending' ) _e( 'Pending', 'wp-photo-album-plus' );
									elseif ( $status == 'publish' ) _e( 'Publish', 'wp-photo-album-plus' );
									elseif ( $status == 'featured' ) _e( 'Featured', 'wp-photo-album-plus' );
									elseif ( $status == 'gold' ) _e( 'Gold', 'wp-photo-album-plus' );
									elseif ( $status == 'silver' ) _e( 'Silver', 'wp-photo-album-plus' );
									elseif ( $status == 'bronze' ) _e( 'Bronze', 'wp-photo-album-plus' );
									elseif ( $status == 'scheduled' ) _e( 'Scheduled', 'wp-photo-album-plus' );
									elseif ( $status == 'private' ) _e( 'Private', 'wp-photo-album-plus' );
									echo
									wppa_get_date_time_select_html( 'photo', $id, false ) .
									'<span id="psdesc-' . $id . '" class="description" style="display:none;" >' .
										__( 'Note: Featured photos should have a descriptive name; a name a search engine will look for!', 'wp-photo-album-plus' ) .
									'</span>';
								}
								echo ' ';

								// Schedule for delete
								$may_change = wppa_user_is( 'administrator' ) || current_user_can( 'wppa_moderate' );
								echo
								__( 'Delete at', 'wp-photo-album-plus' ) .
								' ' .
								'<input' .
									' type="checkbox"' .
									' id="scheduledel-' . $id . '"' .
									( $scheduledel ? ' checked="checked"' : '' ) .
									( $may_change ? '' : ' disabled="disabled"' ) .
									' onchange="wppaTryScheduledel( ' . $id . ' );"' .
								' />' .
								' ' .
								wppa_get_date_time_select_html( 'delphoto', $id, $may_change ) .
								' ';

								// Update status field
								echo
								__( 'Remark:', 'wp-photo-album-plus' ) . ' ' .
								'<span' .
									' id="photostatus-' . $id . '"' .
									' style="font-weight:bold;color:#00AA00;"' .
									' >' .
									( $is_video ? sprintf( __( 'Video %s is not modified yet', 'wp-photo-album-plus' ), $id ) :
												sprintf( __( 'Photo %s is not modified yet', 'wp-photo-album-plus' ), $id ) ) .
								'</span>';

								// New Line
								echo '<br />';

								// --- Available files ---
								echo
								__( 'Available files:', 'wp-photo-album-plus' ) . ' ';

								// Source
								echo
								__( 'Source file:', 'wp-photo-album-plus' ) . ' ';
								$sp = wppa_get_source_path( $id );
								if ( is_file( $sp ) ) {
									$ima = getimagesize( $sp );
									echo
									$ima['0'] . ' x ' . $ima['1'] . ' px, ' .
									wppa_get_filesize( $sp ) . '. ';
								}
								else {
									echo
									__( 'Unavailable', 'wp-photo-album-plus' ) . '. ';
								}

								// Display
								echo
								( $is_video || $has_audio  ? __( 'Poster file:', 'wp-photo-album-plus' ) : __( 'Display file:', 'wp-photo-album-plus' ) ) . ' ';
								$dp = wppa_get_photo_path( $id );
								if ( is_file( $dp ) ) {
									echo
									'<span id="dispfileinfo-' . $id . '" >' .
									floor( wppa_get_photox( $id ) ) . ' x ' . floor( wppa_get_photoy( $id ) ).' px, ' .
									wppa_get_filesize( $dp ) . '.' .
									'</span> ';
								}
								else {
									echo
									'<span style="color:red;" >' .
										__( 'Unavailable', 'wp-photo-album-plus' ) . '. ' .
									'</span>';
								}

								// Thumbnail
								if ( ! $is_video ) {
									echo
									__( 'Thumbnail file:', 'wp-photo-album-plus') . ' ';
									$tp = wppa_get_thumb_path( $id );
									if ( is_file( $tp ) ) {
										echo
										floor( wppa_get_thumbx( $id ) ) . ' x ' . floor( wppa_get_thumby( $id ) ) . ' px, ' .
										wppa_get_filesize( $tp ) . '. ';
									}
									else {
										echo
										'<span style="color:red;" >' .
											__( 'Unavailable', 'wp-photo-album-plus' ) . '. ' .
										'</span>';
									}
								}

								// New line
								echo '<br />';

								// Video
								if ( $b_is_video ) {
									echo
									__( 'Video size:', 'wp-photo-album-plus' ) . ' ' .
									__( 'Width:', 'wp-photo-album-plus' ) .
									'<input' .
										' style="width:50px;margin:0 4px;"' .
										' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'videox\', this )"' .
										' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'videox\', this )"' .
										' value="' . $videox . '"' .
									' />' .
									sprintf( __( 'pix, (0=default:%s)', 'wp-photo-album-plus' ), wppa_opt( 'video_width' ) ) .
									__( 'Height:', 'wp-photo-album-plus' ) .
									'<input' .
										' style="width:50px;margin:0 4px;"' .
										' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'videoy\', this )"' .
										' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'videoy\', this )"' .
										' value="' . $videoy . '"' .
									' />' .
									sprintf( __( 'pix, (0=default:%s)', 'wp-photo-album-plus' ), wppa_opt( 'video_height' ) ) .
									' ' .
									__( 'Formats:', 'wp-photo-album-plus' ) . ' ';
									$c = 0;
									foreach ( $is_video as $fmt ) {
										echo
										$fmt . ' ' .
										__( 'Filesize:', 'wp-photo-album-plus' ) . ' ' .
										wppa_get_filesize( str_replace( 'xxx', $fmt, wppa_get_photo_path( $id, false ) ) );
										$c++;
										if ( is_array( $is_video ) && $c == count( $is_video ) ) {
											echo '. ';
										}
										else {
											echo ', ';
										}
									}
								}

								// Audio
								if ( $b_has_audio ) {
									echo
									__( 'Formats:', 'wp-photo-album-plus' ) . ' ';
									$c = 0;
									foreach ( $has_audio as $fmt ) {
										echo
										$fmt . ' ' .
										__( 'Filesize:', 'wp-photo-album-plus' ) . ' ' .
										wppa_get_filesize( str_replace( 'xxx', $fmt, wppa_get_photo_path( $id, false ) ) );
										$c++;
										if ( is_array( $is_video ) && $c == count( $is_video ) ) {
											echo '. ';
										}
										else {
											echo ', ';
										}
									}
								}

							echo
							'</td>' .
						'</tr>' .
					'</tbody>' .
				'</table>';

				echo	// Section 2
				"\n" . '<!-- Section 2 -->';

				if ( ( wppa_switch( 'enable_stereo' ) && ! $is_multi ) || ( is_file( wppa_get_photo_path( $id ) ) && wppa_switch( 'watermark_on' ) ) ) {
					echo
					'<table' .
						' class="wppa-table wppa-photo-table"' .
						' style="width:100%;"' .
						' >' .
						'<tbody>' .
							'<tr>' .
								'<td>';

									// Stereo
									if ( wppa_switch( 'enable_stereo' ) && ! $is_multi ) {
										echo
										__( 'Stereophoto:', 'wp-photo-album-plus' ) . ' ' .
										'<select' .
											' id="stereo-' . $id . '"' .
											' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'stereo\', this )"' .
											' >' .
											'<option value="0"' . ( $stereo == '0' ? ' selected="selected"' : '' ) . ' >' .
												__( 'no stereo image or ready anaglyph', 'wp-photo-album-plus' ) .
											'</option>' .
											'<option value="1"' . ( $stereo == '1' ? ' selected="selected"' : '' ) . ' >' .
												__( 'Left - right stereo image', 'wp-photo-album-plus' ) .
											'</option>' .
											'<option value="-1"' . ( $stereo == '-1' ? ' selected="selected"' : '' ) . ' >' .
												__( 'Right - left stereo image', 'wp-photo-album-plus' ) .
											'</option>' .
										'</select>' .
										' ';
										__( 'Images:', 'wp-photo-album-plus' ) . ' ';
										$files = glob( WPPA_UPLOAD_PATH . '/stereo/' . $id . '-*.*' );
										$c = 0;
										if ( ! empty( $files ) ) {
											sort( $files );
											foreach ( $files as $file ) {
												echo
												'<a href="' . str_replace( WPPA_UPLOAD_PATH, WPPA_UPLOAD_URL, $file ) . '" target="_blank" >' .
													basename( $file ) .
												'</a>';
												$c++;
												if ( $c == count( $files ) ) {
													echo '. ';
												}
												else {
													echo ', ';
												}
											}
										}
									}

									// Watermark
									if ( wppa_switch( 'watermark_on' ) ) {

										// Get the current watermark file settings
										$temp 	= wppa_get_water_file_and_pos( $id );
										$wmfile = isset( $temp['file'] ) ? $temp['file'] : '';
										$wmpos 	= isset( $temp['pos'] ) && isset ( $wms[$temp['pos']] ) ? $wms[$temp['pos']] : '';

										$user = wppa_get_user();
										if ( wppa_switch( 'watermark_user' ) || current_user_can( 'wppa_settings' ) ) {
											echo
											__( 'Watermark:', 'wp-photo-album-plus') . ' ';
											echo
											'<select' .
												' id="wmfsel_' . $id . '"' .
												' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'wppa_watermark_file_' . $user . '\', this );"' .
												' >' .
												wppa_watermark_file_select( 'user', $album ) .
											'</select>' .
											__( 'Pos:', 'wp-photo-album-plus' ) . ' ' .
											'<select' .
												' id="wmpsel_' . $id . '"' .
												' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'wppa_watermark_pos_' . $user . '\', this );"' .
												' >' .
												wppa_watermark_pos_select( 'user', $album ) .
											'</select>' .
											'<input' .
												' type="button"' .
												' class="button-secundary"' .
												' value="' . esc_attr( __( 'Apply watermark', 'wp-photo-album-plus' ) ) . '"' .
												' onclick="wppaTryWatermark( ' . $id . ' )"' .
											' />' .
											' ' .
											'<img' .
												' id="wppa-water-spin-' . $id . '"' .
												' src="' . wppa_get_imgdir() . 'spinner.' . ( wppa_use_svg() ? 'svg' : 'gif' ) . '"' .
												' alt="Spin"' .
												' style="visibility:hidden"' .
											' />';
										}
										elseif ( basename( $wmfile ) != '--- none ---' ) {
											echo
											__( 'Watermark:', 'wp-photo-album-plus') . ' ';
											echo
											__( 'File:', 'wp-photo-album-plus' ) . ' ' . basename( $wmfile ) . ' ' .
											__( 'Pos:', 'wp-photo-album-plus') . ' ' . $wmpos;
										}
										echo ' ';
									}

								echo
								'</td>' .
							'</tr>' .
						'</tbody>' .
					'</table>';
				}


				echo	// Section 3
				"\n" . '<!-- Section 3 -->' .

				'<table' .
					' class="wppa-table wppa-photo-table"' .
					' style="width:100%;"' .
					' >' .
					'<tbody>' .
						'<tr>' .
							'<td>';

								// --- Actions ---

								// Rotate
								if ( ! $b_is_video ) {
									if ( ! wppa_can_admin_magick( $id ) ) {
										echo
										'<input' .
											' type="button"' .
											' onclick="wppaTryRotLeft( ' . $id . ' )"' .
											' value="' . esc_attr( __( 'Rotate left', 'wp-photo-album-plus') ) . '"' .
										' />' .
										' ' .
										'<input' .
											' type="button"' .
											' onclick="wppaTryRot180( ' . $id . ' )"' .
											' value="' . esc_attr( __( 'Rotate 180&deg;', 'wp-photo-album-plus') ) . '"' .
										' />' .
										' ' .
										'<input' .
											' type="button"' .
											' onclick="wppaTryRotRight( ' . $id . ' )"' .
											' value="' . esc_attr( __( 'Rotate right', 'wp-photo-album-plus') ) . '"' .
										' />' .
										' ' .
										'<input' .
											' type="button"' .
											' onclick="wppaTryFlip( ' . $id . ' )"' .
											' value="' . esc_attr( __( 'Flip', 'wp-photo-album-plus') ) . '&thinsp;&#8212;"' .
										' />' .
										' ' .
										'<input' .
											' type="button"' .
											' onclick="wppaTryFlop( ' . $id . ' )"' .
											' value="' . esc_attr( __( 'Flip', 'wp-photo-album-plus') ) . ' |"' .
										' />' .
										' ';
									}
								}

								// Remake displayfiles
								if ( ! $is_video ) {
									echo
									'<input' .
										' type="button"' .
										' title="' . esc_attr( __( 'Remake display file and thumbnail file', 'wp-photo-album-plus' ) ) . '"' .
										' onclick="wppaAjaxUpdatePhoto( ' . $id . ', \'remake\', this, ' . ( wppa( 'front_edit' ) ? 'false' : 'true' ) . ' )"' .
										' value="' . esc_attr( __( 'Remake files', 'wp-photo-album-plus' ) ) . '"' .
									' />' .
									' ';
								}

								// Remake thumbnail
								if ( ! $is_video ) {
									echo
									'<input' .
										' type="button"' .
										' title=' . esc_attr( __( 'Remake thumbnail file', 'wp-photo-album-plus' ) ) . '"' .
										' onclick="wppaAjaxUpdatePhoto( ' . $id . ', \'remakethumb\', this, ' . ( wppa( 'front_edit' ) ? 'false' : 'true' ) . ' )"' .
										' value="' . esc_attr( __( 'Remake thumbnail file', 'wp-photo-album-plus' ) ) . '"' .
									' />' .
									' ';
								}

								// Move/copy
								if ( ! $quick ) {

									$max = wppa_opt( 'photo_admin_max_albums' );
									if ( ! $max || wppa_get_total_album_count() < $max ) {

										// If not done yet, get the album options html with the current album excluded
										if ( ! isset( $album_select[$album] ) ) {
											$album_select[$album] = wppa_album_select_a( array( 	'checkaccess' => true,
																									'path' => wppa_switch( 'hier_albsel' ),
																									'exclude' => $album,
																									'selected' => '0',
																									'addpleaseselect' => true,
																									'sort' => true,
																								)
																						);
										}

										echo
										__( 'Target album for copy/move:', 'wp-photo-album-plus' ) .
										'<select' .
											' id="target-' . $id . '"' .
											' style="max-width:500px;"' .
											' >' .
											$album_select[$album] .
										'</select>';
									}
									else {
										echo
										__( 'Target album for copy/move:', 'wp-photo-album-plus' ) .
										'<input' .
											' id="target-' . $id . '"' .
											' type="number"' .
											' style="height:20px;"' .
											' placeholder="' . __( 'Album id', 'wp-photo-album-plus' ) . '"' .
										' />';
									}
									echo
									' ';

									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMove( ' . $id . ', ' . $b_is_video . ' )"' .
										' value="' . ( $b_is_video ? $mvt : $mpt ) . '"' .
									' />' .
									' ' .
									'<input' .
										' type="button"' .
										' onclick="wppaTryCopy( ' . $id . ', ' . $b_is_video . ' )"' .
										' value="' . ( $b_is_video ? $cvt : $cpt ) . '"' .
									' />' .
									' ';
								}

								// Delete
								if ( ! wppa( 'front_edit' ) ) {
									echo
									'<input' .
										' type="button"' .
										' style="color:red;"' .
										' onclick="wppaTry' . ( $deleted ? 'Undelete' : 'Delete' ) . '( ' . $id . ', ' . $b_is_video . ' )"' .
										( $deleted ?
										' value="' . ( $b_is_video ? esc_attr( __( 'Undelete video', 'wp-photo-album-plus' ) ) : esc_attr( __( 'Undelete photo', 'wp-photo-album-plus' ) ) ) .'"' :
										' value="' . ( $b_is_video ? esc_attr( __( 'Delete video', 'wp-photo-album-plus' ) ) : esc_attr( __( 'Delete photo', 'wp-photo-album-plus' ) ) ) . '"' ) .
									' />' .
									' ';
								}

								// Re-upload
								if ( wppa_user_is( 'administrator' ) || ! wppa_switch( 'reup_is_restricted' ) ) {
									echo
									'<input' .
										' type="button"' .
										' onclick="jQuery( \'#re-up-' . $id . '\' ).css( \'display\', \'inline-block\' )"' .
										' value="' . esc_attr( __( 'Re-upload file', 'wp-photo-album-plus' ) ) . '"' .
									' />' .

									'<div id="re-up-' . $id . '" style="display:none" >' .
										'<form' .
											' id="wppa-re-up-form-' . $id . '"' .
											' onsubmit="wppaReUpload( event, ' . $id . ', \'' . $filename . '\' )"' .
											' >' .
											'<input' .
												' type="file"' .
												' id="wppa-re-up-file-' . $id . '"' .
											' />' .
											'<input' .
												' type="submit"' .
												' id="wppa-re-up-butn-' . $id . '"' .
												' value="' . esc_attr( __( 'Upload', 'wp-photo-album-plus' ) ) . '"' .
											' />' .
										'</form>' .
									'</div>';
								}

								// Refresh
								/*
								if ( ! wppa( 'front_edit' ) ) {
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaReload( \'#photo_' . $id . '\')"' .
										' value="' . esc_attr( __( 'Refresh page', 'wp-photo-album-plus' ) ) . '"' .
									' />';
								}
								*/



							echo
							'</td>' .
						'</tr>' .
					'</tbody>' .
				'</table>';

				// Section 3a ImageMagick editing commands
				if ( wppa_can_admin_magick( $id ) && ! $quick ) {

					echo
					'<table' .
						' class="wppa-table wppa-photo-table"' .
						' style="width:100%;"' .
						' >' .
						'<tbody>' .
							'<tr>' .
								'<td>' .
									__( '<b>ImageMagick</b> commands. The operations are executed upon the display file.', 'wp-photo-album-plus' ) . ' ' .
									__( 'A new thumbnail image will be created from the display file.', 'wp-photo-album-plus' ) .
								'</td>' .
							'</tr>' .
							'<tr>' .
								'<td>';

									// --- Actions ---

									// Rotate left
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'magickrotleft\' )"' .
										' value="' . esc_attr( __( 'Rotate left', 'wp-photo-album-plus') ) . '"' .
									' />' .
									' ';

									// Rotat 180
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'magickrot180\' )"' .
										' value="' . esc_attr( __( 'Rotate 180&deg;', 'wp-photo-album-plus') ) . '"' .
									' />' .
									' ';

									// Rotate right
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'magickrotright\' )"' .
										' value="' . esc_attr( __( 'Rotate right', 'wp-photo-album-plus') ) . '"' .
									' />' .
									' ';

									// Flip
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'magickflip\' )"' .
										' value="' . esc_attr( __( 'Flip', 'wp-photo-album-plus') ) . '&thinsp;&#8212;"' .
										' title="-flip"' .
									' />' .
									' ';

									// Flop
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'magickflop\' )"' .
										' value="' . esc_attr( __( 'Flop', 'wp-photo-album-plus') ) . ' |"' .
										' title="-flop"' .
									' />' .
									' ';

									// Enhance
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'enhance\' )"' .
										' value="' . esc_attr( __( 'Enhance', 'wp-photo-album-plus') ) . '"' .
										' title="-enhance"' .
									' />' .
									' ';

									// Sharpen
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'sharpen\' )"' .
										' value="' . esc_attr( __( 'Sharpen', 'wp-photo-album-plus' ) ) . '"' .
										' title="-sharpen 0x1"' .
									' />' .
									' ';

									// Blur
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'blur\' )"' .
										' value="' . esc_attr( __( 'Blur', 'wp-photo-album-plus' ) ) . '"' .
										' title="-blur 0x1"' .
									' />' .
									' ';

									// Auto gamma
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'auto-gamma\' )"' .
										' value="' . esc_attr( __( 'Auto Gamma', 'wp-photo-album-plus' ) ) . '"' .
										' title="-auto-gamma"' .
									' />' .
									' ';

									// Auto level
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'auto-level\' )"' .
										' value="' . esc_attr( __( 'Auto Level', 'wp-photo-album-plus' ) ) . '"' .
										' title="-auto-level"' .
									' />' .
									' ';

									// Contrast+
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'contrast-p\' )"' .
										' value="' . esc_attr( __( 'Contrast+', 'wp-photo-album-plus' ) ) . '"' .
										' title="-brightness-contrast 0x5"' .
									' />' .
									' ';

									// Contrast-
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'contrast-m\' )"' .
										' value="' . esc_attr( __( 'Contrast-', 'wp-photo-album-plus' ) ) . '"' .
										' title="-brightness-contrast 0x-5"' .
									' />' .
									' ';

									// Brightness+
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'brightness-p\' )"' .
										' value="' . esc_attr( __( 'Brightness+', 'wp-photo-album-plus' ) ) . '"' .
										' title="-brightness-contrast 5"' .
									' />' .
									' ';

									// Brightness-
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'brightness-m\' )"' .
										' value="' . esc_attr( __( 'Brightness-', 'wp-photo-album-plus' ) ) . '"' .
										' title="-brightness-contrast -5"' .
									' />' .
									' ';

									// Despeckle
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'despeckle\' )"' .
										' value="' . esc_attr( __( 'Despeckle', 'wp-photo-album-plus' ) ) . '"' .
										' title="-despeckle"' .
									' />' .
									' ';

									// Lenear gray
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'lineargray\' )"' .
										' value="' . esc_attr( __( 'Linear gray', 'wp-photo-album-plus' ) ) . '"' .
										' title="-colorspace gray"' .
									' />' .
									' ';

									// Non-linear gray
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'nonlineargray\' )"' .
										' value="' . esc_attr( __( 'Non-linear gray', 'wp-photo-album-plus' ) ) . '"' .
										' title="-grayscale Rec709Luma"' .
									' />' .
									' ';

									// Charcoal
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'charcoal\' )"' .
										' value="' . esc_attr( __( 'Charcoal', 'wp-photo-album-plus' ) ) . '"' .
										' title="-charcoal"' .
									' />' .
									' ';

									// Paint
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'paint\' )"' .
										' value="' . esc_attr( __( 'Paint', 'wp-photo-album-plus' ) ) . '"' .
										' title="-paint"' .
									' />' .
									' ';

									// Sepia
									echo
									'<input' .
										' type="button"' .
										' onclick="wppaTryMagick( ' . $id . ', \'sepia\' )"' .
										' value="' . esc_attr( __( 'Sepia', 'wp-photo-album-plus' ) ) . '"' .
										' title="-sepia-tone 80%"' .
									' />' .
									' ';
									echo
								'</td>' .
							'</tr>' .
							'<tr>' .
								'<td>' .
									__( '<b>ImageMagick</b> command stack', 'wp-photo-album-plus' ) .
									': ' .
									'<span' .
										' id="imstack-' . $id . '"' .
										' style="color:blue;"' .
										' >' .
										$magickstack .
									'</span>' .
									' ' .
									'<input' .
										' type="button"' .
										' id="imstackbutton-' . $id . '"' .
										' onclick="wppaTryMagick( ' . $id . ', \'magickundo\' )"' .
										' value="' . esc_attr( __( 'Undo', 'wp-photo-album-plus' ) ) . '"' .
										' title="' . esc_attr( __( 'Undo last Magick command', 'wp-photo-album-plus' ) ) . '"' .
										' style="' . ( $magickstack ? '' : 'display:none;' ) . '"' .
									' />' .
								'</td>' .
							'</tr>';

							// Fake 'for social media' to use the local file here, not cloudinary. Files from cloudinary do not reload, even with ?ver=...
							wppa( 'for_sm', true );
							echo
							'<tr>' .
								'<td>' .
									'<img' .
										' id="fs-img-' . $id . '"' .
										' src="' . wppa_get_photo_url( $id ) . '"' .
										' style="float:left;max-width:90%;" ' .
									' />' .
									'<div' .
										' style="display:inline-block;vertical-align:middle;margin-left:4px;margin-top:' . ( min( 600, wppa_get_photoy( $id ) ) / 2 - 30 ) . 'px;"' .
										' >' .
										'<input' .
											' type="button"' .
											' onclick="wppaTryMagick( ' . $id . ', \'skyleft\' );"' .
											' value="' . esc_attr( 'Up', 'wp-photo-album-plus' ) . '"' .
											' title="' . esc_attr( 'Turn horizon up by 0.5&deg;', 'wp-photo-album-plus' ) . '"' .
										' />' .
										'<br />' .
										'<input' .
											' type="button"' .
											' onclick="wppaToggleHorizon()"' .
											' value="' . esc_attr( 'Hor', 'wp-photo-album-plus' ) . '"' .
											' title="' . esc_attr( 'Toggle horizon reference line on/off', 'wp-photo-album-plus' ) . '"' .
										' />' .
										'<br />' .
										'<input' .
											' type="button"' .
											' onclick="wppaTryMagick( ' . $id . ', \'skyright\' );"' .
											' value="' . esc_attr( 'Down', 'wp-photo-album-plus' ) . '"' .
											' title="' . esc_attr( 'Turn horizon down by 0.5&deg;', 'wp-photo-album-plus' ) . '"' .
										' />' .
									'</div>' .
								'</td>' .
							'</tr>';

						echo
						'</tbody>' .
					'</table>';
				}

				// Reset switch
				wppa( 'for_sm', false );

				echo	// Section 4
				"\n" . '<!-- Section 4 -->' .
				'<table' .
					' class="wppa-table wppa-photo-table"' .
					' style="width:100%;"' .
					' >' .
					'<tbody>';

						// Name
						echo
						'<tr>' .
							'<td>' .
								__( 'Photoname:' , 'wp-photo-album-plus') .
							'</td>' .
							'<td>' .
								'<input' .
									' type="text"' .
									' style="width:100%;"' .
									' id="pname-' . $id . '"' .
									' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'name\', this );"' .
									' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'name\', this );"' .
									' value="' . esc_attr( stripslashes( $name ) ) . '"' .
								' />' .
							'</td>' .
							'<td>' .
							'</td>' .
						'</tr>';

						// Description
						if ( ! wppa_switch( 'desc_is_restricted' ) || wppa_user_is( 'administrator' ) ) {
							echo
							'<tr>' .
								'<td>' .
									__( 'Description:', 'wp-photo-album-plus' ) .
								'</td>';
								if ( wppa_switch( 'use_wp_editor' ) ) {
									$alfaid = wppa_alfa_id( $id );
									echo
									'<td>';
										wp_editor( 	$description,
													'wppaphotodesc'.$alfaid,
													array( 	'wpautop' 		=> true,
															'media_buttons' => false,
															'textarea_rows' => '6',
															'tinymce' 		=> true
															)
												);
									echo
									'</td>' .
									'<td>' .
										'<input' .
											' type="button"' .
											' class="button-secundary"' .
											' value="' . esc_attr( __( 'Update Photo description', 'wp-photo-album-plus' ) ) . '"' .
											' onclick="wppaAjaxUpdatePhoto( ' . $id . ', \'description\', document.getElementById( \'wppaphotodesc' . $alfaid . '\' ), false, \'' . $alfaid . '\' )"' .
										' />' .
										'<img' .
											' id="wppa-photo-spin-' . $id . '"' .
											' src="' . wppa_get_imgdir() . 'spinner.' . ( wppa_use_svg() ? 'svg' : 'gif' ) . '"' .
											' style="visibility:hidden"' .
										' />' .
									'</td>';
								}
								else {
									echo
									'<td>' .
										'<textarea' .
											' style="width:100%;height:60px;"' .
											' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'description\', this )"' .
											' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'description\', this )"' .
											' >' .
											$description .
										'</textarea>' .
									'</td>' .
									'<td>' .
									'</td>';
								}
							echo
							'</tr>';
						}
						else {
							echo
							'<tr>' .
								'<td>' .
									__( 'Description:', 'wp-photo-album-plus') .
								'</td>' .
								'<td>' .
									$description .
								'</td>' .
								'<td>' .
								'</td>' .
							'</tr>';
						}

						// Tags
						$allowed = ! wppa_switch( 'newtags_is_restricted' ) || wppa_user_is( 'administrator' );
						echo
						'<tr>' .
							'<td>' .
								__( 'Tags:', 'wp-photo-album-plus' ) .
							'</td>';

							echo
							'<td>' .
								'<input' .
									' id="tags-' . $id . '"' .
									' type="text"' .
									' style="width:100%;"' .
									' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'tags\', this )"' .
									' value="' . $tags . '"' .
									( $allowed ? '' : ' readonly="readonly"' ) .
								' />' .
								( $allowed ?
								'<br />' .
								'<span class="description" >' .
									__( 'Separate tags with commas.', 'wp-photo-album-plus') .
								'</span>' : '' ) .
							'</td>';

							echo
							'<td>' .
								'<select' .
									' onchange="wppaAddTag( this.value, \'tags-' . $id . '\' ); wppaAjaxUpdatePhoto( ' . $id . ', \'tags\', document.getElementById( \'tags-' . $id . '\' ) )"' .
									' >';
									$taglist = wppa_get_taglist();
									if ( is_array( $taglist ) ) {
										echo '<option value="" >' . __( '- select -', 'wp-photo-album-plus' ) . '</option>';
										foreach ( $taglist as $tag ) {
											echo '<option value="' . $tag['tag'] . '" >' . $tag['tag'] . '</option>';
										}
										if ( ! $allowed ) {
											echo '<option value="-clear-" >' . __( '- clear -', 'wp-photo-album-plus' ) . '</option>';
										}
									}
									else {
										echo '<option value="0" >' . __( 'No tags yet', 'wp-photo-album-plus' ) . '</option>';
									}
								echo
								'</select>' .
								'<br />' .
								'<span class="description" >' .
									__( 'Select to add', 'wp-photo-album-plus' ) .
								'</span>' .
							'</td>';
						'</tr>';

						// Custom
						if ( wppa_switch( 'custom_fields' ) ) {
							$custom = wppa_get_photo_item( $photo['id'], 'custom' );
							if ( $custom ) {
								$custom_data = unserialize( $custom );
							}
							else {
								$custom_data = array( '', '', '', '', '', '', '', '', '', '' );
							}
							foreach( array_keys( $custom_data ) as $key ) {
								if ( wppa_opt( 'custom_caption_' . $key ) ) {
									echo
									'<tr>' .
										'<td>' .
											apply_filters( 'translate_text', wppa_opt( 'custom_caption_' . $key ) ) .
											'<small style="float:right" >' .
												'(w#cc'.$key.')' .
											'</small>:' .
										'</td>' .
										'<td>' .
											'<input' .
												' type="text"' .
												' style="width:100%;"' .
												' id="custom_' . $key . '-' . $id . '"' .
												' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'custom_' . $key . '\', this );"' .
												' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'custom_' . $key . '\', this );"' .
												' value="' . esc_attr( stripslashes( $custom_data[$key] ) ) . '"' .
											'/>' .
										'</td>' .
										'<td>' .
											'<small>(w#cd'.$key.')</small>' .
										'</td> ' .
									'</tr>';
								}
							}
						}

						// -- Auto Page --
						if ( wppa_switch( 'auto_page' ) && ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) ) {
							$appl = get_permalink( wppa_get_the_auto_page( $id ) );
							echo
							'<tr>' .
								'<td>' .
									__( 'Autopage Permalink:', 'wp-photo-album-plus' ) .
								'</td>' .
								'<td>' .
									'<a href="' . $appl . '" target="_blank" >' .
										$appl .
									'</a>' .
								'</td>' .
								'<td>' .
								'</td>' .
							'</tr>';
						}

						// -- Link url --
						if ( ! wppa_switch( 'link_is_restricted' ) || wppa_user_is( 'administrator' ) ) {
							echo
							'<tr>' .
								'<td>' .
									__( 'Photo specific link url:', 'wp-photo-album-plus' ) .
								'</td>' .
								'<td>' .
									'<input' .
										' type="text"' .
										' id="pislink-' . $id . '"' .
										' style="width:100%;"' .
										' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'linkurl\', this )"' .
										' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'linkurl\', this )"' .
										' value="' . esc_attr( $linkurl ) . '"' .
									' />' .
								'</td>' .
								'<td>' .
									'<select' .
										' id="pistarget-' . $id . '"' .
										' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'linktarget\', this )"' .
										' >' .
										'<option' .
											' value="_self"' .
											( $linktarget == '_self' ? ' selected="selected"' : '' ) .
											' >' .
											__( 'Same tab', 'wp-photo-album-plus' ) .
										'</option>' .
										'<option' .
											' value="_blank"' .
											( $linktarget == '_blank' ? ' selected="selected"' : '' ) .
											' >' .
											__( 'New tab', 'wp-photo-album-plus' ) .
										'</option>' .
									'</select>' .
									'<input' .
										' type="button"' .
										' onclick="window.open( jQuery( \'#pislink-' . $id . '\' ).val(), jQuery( \'#pistarget-' . $id . '\' ).val() );"' .
										' value="' . __( 'Tryit!', 'wp-photo-album-plus' ) . '"' .
									' />' .
								'</td>' .
							'</tr>';

							// -- Link title --
							echo
							'<tr>' .
								'<td>' .
									__( 'Photo specific link title:', 'wp-photo-album-plus' ) .
								'</td>' .
								'<td>' .
									'<input' .
										' type="text"' .
										' style="width:100%;"' .
										' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'linktitle\', this )"' .
										' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'linktitle\', this )"' .
										' value="' . esc_attr( $linktitle ) . '"' .
									' />';
									if ( current_user_can( 'wppa_settings' ) ) {
										echo
										'<br />' .
										'<span class="description" >' .
											__( 'If you want this link to be used, check \'PS Overrule\' checkbox in table VI.' , 'wp-photo-album-plus') .
										'</span>';
									}
								echo
								'</td>' .
								'<td>' .
								'</td>' .
							'</tr>';
						}

						// -- Custom ALT field --
						if ( wppa_opt( 'alt_type' ) == 'custom' ) {
							echo
							'<tr>' .
								'<td>' .
									__( 'HTML Alt attribute:' , 'wp-photo-album-plus') .
								'</td>' .
								'<td>' .
									'<input' .
										' type="text"' .
										' style="width:100%;"' .
										' onkeyup="wppaAjaxUpdatePhoto( ' . $id . ', \'alt\', this )"' .
										' onchange="wppaAjaxUpdatePhoto( ' . $id . ', \'alt\', this )"' .
										' value="' . esc_attr( $alt ) . '"' .
									' />' .
								'</td>' .
								'<td>' .
								'</td>' .
							'</tr>';
						}

						// If Quick, skip the following items for speed and space
						if ( ! $quick ) {

							// Shortcode
							if ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) {
								echo
								'<tr>' .
									'<td>' .
										__( 'Single image shortcode', 'wp-photo-album-plus' ) . ':' .
									'</td>' .
									'<td>' .
										'[wppa type="photo" photo="' . $id .'"][/wppa]'.
									'</td>' .
									'<td>' .
										'<small>' .
											sprintf( 	__( 'See %s The documentation %s for more shortcode options.', 'wp-photo-album-plus' ),
														'<a href="http://wppa.nl/shortcode-reference/" target="_blank" >',
														'</a>'
													) .
										'</small>' .
									'</td>' .
								'</tr>';
							}

							// Source permalink
							if ( is_file( wppa_get_source_path( $id ) ) ) {
								$spl = wppa_get_source_pl( $id );
								echo
								'<tr>' .
									'<td>' .
										__( 'Permalink', 'wp-photo-album-plus' ) . ':' .
									'</td>' .
									'<td>' .
										'<a href="' . $spl . '" target="_blank" >' . $spl . '</a>' .
									'</td>' .
									'<td>' .
									'</td>' .
								'</tr>';
							}

							// High resolution url
							$hru = wppa_get_hires_url( $id );
							echo
							'<tr>' .
								'<td>' .
									__( 'Hi resolution url', 'wp-photo-album-plus') . ':' .
								'</td>' .
								'<td>' .
									'<a href="' . $hru . '" target="_blank" >' . $hru . '</a>' .
								'</td>' .
								'<td>' .
								'</td>' .
							'</tr>';

							// Display file
							if ( is_file( wppa_get_photo_path( $id ) ) ) {
								$lru = wppa_fix_poster_ext( wppa_get_lores_url( $id ), $id );
								echo
								'<tr>' .
									'<td>' .
										__( 'Display file url', 'wp-photo-album-plus') . ':' .
									'</td>' .
									'<td>' .
										'<a href="' . $lru . '" target="_blank" >' . $lru . '</a>' .
									'</td>' .
									'<td>' .
									'</td>' .
								'</tr>';
							}

							// Thumbnail
							if ( is_file( wppa_get_thumb_path( $id ) ) ) {
								$tnu = wppa_fix_poster_ext( wppa_get_tnres_url( $id ), $id );
								echo
								'<tr>' .
									'<td>' .
										__( 'Thumbnail file url', 'wp-photo-album-plus' ) . ':' .
									'</td>' .
									'<td>' .
										'<a href="' . $tnu . '" target="_blank" >' . $tnu . '</a>' .
									'</td>' .
									'<td>' .
									'</td>' .
								'</tr>';
							}
						}
					echo
					'</tbody>' .
				'</table>';

				echo 	// Section 5
				"\n" . '<!-- Section 5 -->';

				// Exif
				if ( ! $quick ) {
					$exifs = $wpdb->get_results( $wpdb->prepare( 	"SELECT * FROM `" . WPPA_EXIF . "` " .
																	"WHERE `photo` = %s " .
																	"ORDER BY `tag`, `id` ", $id ), ARRAY_A );
					if ( ! empty( $exifs ) ) {
						$brand = wppa_get_camera_brand( $id );
						echo
						'<table><tbody><tr><td><input' .
							' type="button"' .
							' id="wppa-exif-button-' . $id . '"' .
							' class="button-secundary"' .
							' value="' . esc_attr( sprintf( __( 'Show %d EXIF items', 'wp-photo-album-plus' ), count( $exifs ) ) ) . '"' .
							' onclick="wppaToggleExif( ' . $id . ', ' . count( $exifs ) . ' );"' .
						' /></td></tr></tbody></table>' .
						'<table' .
							' id="wppa-exif-' . $id . '"' .
							' class="wppa-table wppa-photo-table"' .
							' style="clear:both;width:100%;display:none;"' .
							' >' .
							'<thead>' .
								'<tr style="font-weight:bold;" >' .
									'<td style="padding:0 4px;" >Exif tag</td>' .
									'<td style="padding:0 4px;" >Brand</td>' .
									'<td style="padding:0 4px;" >Description</td>' .
									'<td style="padding:0 4px;max-width:30%;" >Raw value</td>' .
									'<td style="padding:0 4px;max-width:30%;" >Formatted value</td>' .
								'</tr>' .
							'</thead>' .
							'<tbody>';

								foreach ( $exifs as $exif ) {
									echo '
									<tr id="exif-tr-' . $exif['id'] . '" >
										<td style="padding:0 4px;" >'.$exif['tag'].'</td>';

											if ( $brand && $exif['brand'] ) { 	 // * wppa_exif_tagname( hexdec( substr( $exif['tag'], 2, 4 ) ), $brand, 'brandonly' ) ) {
												echo '
												<td style="padding:0 4px;" >' . $brand . '</td>
												<td style="padding:0 4px;" >' . wppa_exif_tagname( hexdec( substr( $exif['tag'], 2, 4 ) ), $brand, 'brandonly' ) . ':</td>';
											}
											else {
												echo '
												<td style="padding:0 4px;" ></td>
												<td style="padding:0 4px;" >' . wppa_exif_tagname( hexdec( substr( $exif['tag'], 2, 4 ) ) ) . ':</td>';
											}

										echo '
										<td style="padding:0 4px;" >'.$exif['description'].'</td>
										<td style="padding:0 4px;" >' .
											( $exif['f_description'] == __( 'n.a.', 'wp-photo-album-plus' ) ? wppa_format_exif( $exif['tag'], $exif['description'] ) : $exif['f_description'] ) .
										'</td>
									</tr>';

								}

							echo
							'</tbody>' .
						'</table>';


					}
				}

				echo	// Section 6
				"\n" . '<!-- Section 6 -->';

				// Comments
				if ( ! $quick ) {
					$comments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . WPPA_COMMENTS."` " .
																	"WHERE `photo` = %s " .
																	"ORDER BY `timestamp` DESC ", $id ), ARRAY_A );
					if ( ! empty( $comments ) ) {
						echo
						'<table' .
							' class="wppa-table wppa-photo-table"' .
							' style="width:100%;"' .
							' >' .
							'<thead>' .
								'<tr style="font-weight:bold;" >' .
									'<td style="padding:0 4px;" >#</td>' .
									'<td style="padding:0 4px;" >User</td>' .
									'<td style="padding:0 4px;" >Time since</td>' .
									'<td style="padding:0 4px;" >Status</td>' .
									'<td style="padding:0 4px;" >Comment</td>' .
								'</tr>' .
							'</thead>' .
							'<tbody>';

								foreach ( $comments as $comment ) {
									echo '
									<tr id="com-tr-' . $comment['id'] . '" >
										<td style="padding:0 4px;" >'.$comment['id'].'</td>
										<td style="padding:0 4px;" >'.$comment['user'].'</td>
										<td style="padding:0 4px;" >'.wppa_get_time_since( $comment['timestamp'] ).'</td>';
										if ( current_user_can( 'wppa_comments' ) || current_user_can( 'wppa_moderate' ) || ( wppa_get_user() == $photo['owner'] && wppa_switch( 'owner_moderate_comment' ) ) ) {
											$p = ( $comment['status'] == 'pending' ) ? 'selected="selected" ' : '';
											$a = ( $comment['status'] == 'approved' ) ? 'selected="selected" ' : '';
											$s = ( $comment['status'] == 'spam' ) ? 'selected="selected" ' : '';
											$t = ( $comment['status'] == 'trash' ) ? 'selected="selected" ' : '';
											echo
											'<td style="padding:0 4px;" >' .
												'<select' .
													' id="com-stat-' . $comment['id'] . '"' .
													' style=""' .
													' onchange="wppaAjaxUpdateCommentStatus( '.$id.', '.$comment['id'].', this.value );wppaSetComBgCol(' . $comment['id'] . ');"' .
													' >' .
													'<option value="pending" '.$p.'>'.__( 'Pending' , 'wp-photo-album-plus').'</option>' .
													'<option value="approved" '.$a.'>'.__( 'Approved' , 'wp-photo-album-plus').'</option>' .
													'<option value="spam" '.$s.'>'.__( 'Spam' , 'wp-photo-album-plus').'</option>' .
													'<option value="trash" '.$t.'>'.__( 'Trash' , 'wp-photo-album-plus').'</option>' .
												'</select >' .
											'</td>';
										}
										else {
											echo '<td style="padding:0 4px;" >';
												if ( $comment['status'] == 'pending' ) _e( 'Pending' , 'wp-photo-album-plus');
												elseif ( $comment['status'] == 'approved' ) _e( 'Approved' , 'wp-photo-album-plus');
												elseif ( $comment['status'] == 'spam' ) _e( 'Spam' , 'wp-photo-album-plus');
												elseif ( $comment['status'] == 'trash' ) _e( 'Trash' , 'wp-photo-album-plus');
											echo '</td>';
										}
										echo '<td style="padding:0 4px;" >'.$comment['comment'].'</td>
									</tr>' .
									'<script>wppaSetComBgCol(' . $comment['id'] . ')</script>';
								}

							echo
							'</tbody>' .
						'</table>';
					}
				}

				echo
				'<script>wppaPhotoStatusChange( ' . $id . ' )</script>' .
				'<div style="clear:both;"></div>' .
			'</div>' .
			'<div style="clear:both;margin-top:7px;"></div>';

		} /* foreach photo */

		wppa_admin_page_links( $page, $pagesize, $count, $link );

	} /* photos not empty */
} /* function */

function wppa_album_photos_bulk( $album ) {
	global $wpdb;

	// Check input
	wppa_vfy_arg( 'wppa-page' );

	if ( $album == 'moderate' ) {
		// Can i moderate?
		if ( ! current_user_can( 'wppa_moderate' ) ) {
			wp_die( __( 'You do not have the rights to do this' , 'wp-photo-album-plus') );
		}
	}

	// Init
	$count = '0';
	$abort = false;

	if ( isset ( $_POST['wppa-bulk-action'] ) ) {
		check_admin_referer( 'wppa-bulk', 'wppa-bulk' );
		if ( isset ( $_POST['wppa-bulk-photo'] ) ) {
			$ids 		= $_POST['wppa-bulk-photo'];
			$newalb 	= isset ( $_POST['wppa-bulk-album'] ) ? $_POST['wppa-bulk-album'] : '0';
			$status 	= isset ( $_POST['wppa-bulk-status'] ) ? $_POST['wppa-bulk-status'] : '';
			$owner 		= isset ( $_POST['wppa-bulk-owner'] ) ? $_POST['wppa-bulk-owner'] : '';
			$totcount 	= count( $ids );
			if ( ! is_numeric( $newalb ) ) wp_die( 'Security check failure 1' );
			if ( is_array( $ids ) ) {
				foreach ( array_keys( $ids ) as $id ) {
					$skip = false;
					switch ( $_POST['wppa-bulk-action'] ) {
						case 'wppa-bulk-delete':
							wppa_delete_photo( $id );
							break;
						case 'wppa-bulk-move-to':
							if ( $newalb ) {
								$photo = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM '.WPPA_PHOTOS.' WHERE `id` = %s', $id ), ARRAY_A );
								if ( wppa_switch( 'void_dups' ) ) {	// Check for already exists
									$exists = $wpdb->get_var ( $wpdb->prepare ( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `filename` = %s AND `album` = %s", $photo['filename'], $newalb ) );
									if ( $exists ) {	// Already exists
										wppa_error_message ( sprintf ( __( 'A photo with filename %s already exists in album %s.' , 'wp-photo-album-plus'), $photo['filename'], $newalb ) );
										$skip = true;
									}
								}
								if ( $skip ) continue;
								wppa_invalidate_treecounts( $photo['album'] );		// Current album
								wppa_invalidate_treecounts( $newalb );				// New album
								$wpdb->query( $wpdb->prepare( 'UPDATE `'.WPPA_PHOTOS.'` SET `album` = %s WHERE `id` = %s', $newalb, $id ) );
								wppa_move_source( $photo['filename'], $photo['album'], $newalb );
							}
							else wppa_error_message( 'Unexpected error #4 in wppa_album_photos_bulk().' );
							break;
						case 'wppa-bulk-copy-to':
							if ( $newalb ) {
								$photo = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM '.WPPA_PHOTOS.' WHERE `id` = %s', $id ), ARRAY_A );
								if ( wppa_switch( 'void_dups' ) ) {	// Check for already exists
									$exists = $wpdb->get_var ( $wpdb->prepare ( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `filename` = %s AND `album` = %s", $photo['filename'], $newalb ) );
									if ( $exists ) {	// Already exists
										wppa_error_message ( sprintf ( __( $exists.'A photo with filename %s already exists in album %s.' , 'wp-photo-album-plus'), $photo['filename'], $newalb ) );
										$skip = true;
									}
								}
								if ( $skip ) continue;
								wppa_copy_photo( $id, $newalb );
								wppa_invalidate_treecounts( $newalb );
							}
							else wppa_error_message( 'Unexpected error #3 in wppa_album_photos_bulk().' );
							break;
						case 'wppa-bulk-status':
							if ( ! in_array( $status, array( 'publish', 'pending', 'featured', 'scheduled', 'gold', 'silver', 'bronze', 'private' ) ) ) {
								wppa_log( 'error', 'Unknown status '.strip_tags( $status ).' found in wppa-photo-admin-autosave.php -> wppa_album_photos_bulk()' );
								$status = 'publish';
							}
							if ( current_user_can( 'wppa_admin' ) || current_user_can( 'wppa_moderate' ) ) {
								if ( $status == 'publish' || $status == 'pending' || wppa_user_is( 'administrator' ) || ! wppa_switch( 'ext_status_restricted' ) ) {
									$wpdb->query( "UPDATE `".WPPA_PHOTOS."` SET `status` = '".$status."' WHERE `id` = ".$id );
									wppa_invalidate_treecounts( wppa_get_photo_item( $id, 'album' ) );
								}
								else wp_die( 'Security check failure 2' );
							}
							else wp_die( 'Security check failure 3' );
							break;
						case 'wppa-bulk-owner':
							if ( wppa_user_is( 'administrator' ) && wppa_switch( 'photo_owner_change' ) ) {
								if ( $owner ) {
									$owner = sanitize_user( $owner );
									$exists = $wpdb->get_var( "SELECT COUNT(*) FROM `".$wpdb->users."` WHERE `user_login` = '".$owner."'" );
									if ( $exists ) {
										$wpdb->query( "UPDATE `".WPPA_PHOTOS."` SET `owner` = '".$owner."' WHERE `id` = ".$id );
									}
									else {
										wppa_error_message( 'A user with login name '.$owner.' does not exist.' );
										$skip = true;
									}
								}
								else wp_die( 'Missing required arg in bulk change owner' );
							}
							else wp_die( 'Security check failure 4' );
							break;
						default:
							wppa_error_message( 'Unimplemented bulk action requested in wppa_album_photos_bulk().' );
							break;
					}
					if ( ! $skip ) $count++;
					if ( wppa_is_time_up() ) {
						wppa_error_message( sprintf( __( 'Time is out after processing %d out of %d items.' , 'wp-photo-album-plus'), $count, $totcount ) );
						$abort = true;
					}
					if ( $abort ) break;
				}
			}
			else wppa_error_message( 'Unexpected error #2 in wppa_album_photos_bulk().' );
		}
		else wppa_error_message( 'Unexpected error #1 in wppa_album_photos_bulk().' );

		if ( $count && ! $abort ) {
			switch ( $_POST['wppa-bulk-action'] ) {
				case 'wppa-bulk-delete':
					$message = sprintf( __( '%d photos deleted.' , 'wp-photo-album-plus'), $count );
					break;
				case 'wppa-bulk-move-to':
					$message = sprintf( __( '%1$s photos moved to album %2$s.' , 'wp-photo-album-plus'), $count, $newalb.': '.wppa_get_album_name( $newalb ) );
					break;
				case 'wppa-bulk-copy-to':
					$message = sprintf( __( '%1$s photos copied to album %2$s.' , 'wp-photo-album-plus'), $count, $newalb.': '.wppa_get_album_name( $newalb ) );
					break;
				case 'wppa-bulk-status':
					$message = sprintf( __( 'Changed status to %1$s on %2$s photos.' , 'wp-photo-album-plus'), $status, $count );
					break;
				case 'wppa-bulk-owner':
					$message = sprintf( __( 'Changed owner to %1$s on %2$s photos.' , 'wp-photo-album-plus'), $owner, $count );
					break;
				default:
					$message = sprintf( __( '%d photos processed.' , 'wp-photo-album-plus'), $count );
					break;
			}
			wppa_ok_message( $message );
		}
	}

	$pagesize 			= wppa_opt( 'photo_admin_pagesize' );
	$next_after 		= isset ( $_REQUEST['next-after'] ) ? strval( intval( $_REQUEST['next-after'] ) ) : '0';
	$page 				= ( isset( $_GET['wppa-page'] ) ? max( strval( intval( $_GET['wppa-page'] ) ), '1' ) : '1' ) + ( isset( $_POST['next-after'] ) ? $_POST['next-after'] : '0' );
	$skip 				= ( $page > '0' ? ( $page - '1' ) * $pagesize : '0' );
	$limit 				= ( $pagesize < '1' ) ? '' : ' LIMIT '.$skip.','.$pagesize;
//	$no_confirm_delete 	= wppa_getCookie(); //( isset( $_REQUEST['no-confirm-delete'] ) ? true : false );
//	$no_confirm_move 	= wppa_getCookie(); //( isset( $_REQUEST['no-confirm-move'] ) ? true : false );
/*
echo 'Post=';
print_r($_POST);
echo '<br />';
print_r($_GET);
echo '<br />';
echo 'Page='.$page;
*/
	if ( $album ) {
		if ( $album == 'moderate' ) {
			$photos	= $wpdb->get_results( "SELECT * FROM `" . WPPA_PHOTOS . "` WHERE `status` = 'pending' ORDER BY `album` DESC, `timestamp` DESC " . $limit, ARRAY_A );
			$count 	= count( $photos );
			$link 	= wppa_dbg_url( get_admin_url().'admin.php?page=wppa_moderate_photos' );
		}
		elseif ( $album == 'search' ) {
			$count 	= wppa_get_edit_search_photos( '', 'count_only' );
			$photos = wppa_get_edit_search_photos( $limit );
			$link 	= wppa_dbg_url( get_admin_url().'admin.php?page=wppa_admin_menu&tab=edit&edit_id='.$album.'&wppa-searchstring='.wppa_sanitize_searchstring($_REQUEST['wppa-searchstring']).'&bulk'.'&wppa_nonce=' . wp_create_nonce('wppa_nonce') );
			wppa_show_search_statistics();
		}
		else {
			$counts = wppa_get_treecounts_a( $album, true );
			$count = $counts['selfphotos'] + $counts['pendselfphotos'] + $counts['scheduledselfphotos'];
			$photos = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `'.WPPA_PHOTOS.'` WHERE `album` = %s '.wppa_get_photo_order( $album, 'norandom' ).$limit, $album ), ARRAY_A );
			$link = wppa_dbg_url( get_admin_url().'admin.php?page=wppa_admin_menu&tab=edit&edit_id='.$album.'&bulk'.'&wppa_nonce=' . wp_create_nonce('wppa_nonce') );
		}

		if ( $photos ) {
			$plink = $link . '&next-after=' . $next_after;
			wppa_admin_page_links( $page, $pagesize, $count, $plink, '#manage-photos' );
			?>
			<script type="text/javascript" >
				function wppaBulkActionChange( elm, id ) {
					wppa_setCookie( 'wppa_bulk_action',elm.value,365 );
					if ( elm.value == 'wppa-bulk-move-to' || elm.value == 'wppa-bulk-copy-to' ) jQuery( '#wppa-bulk-album' ).css( 'display', 'inline' );
					else jQuery( '#wppa-bulk-album' ).css( 'display', 'none' );
					if ( elm.value == 'wppa-bulk-status' ) jQuery( '#wppa-bulk-status' ).css( 'display', 'inline' );
					else jQuery( '#wppa-bulk-status' ).css( 'display', 'none' );
					if ( elm.value == 'wppa-bulk-owner' ) jQuery( '#wppa-bulk-owner' ).css( 'display', 'inline' );
					else jQuery( '#wppa-bulk-owner' ).css( 'display', 'none' );
				}
				function wppaBulkDoitOnClick() {
					var photos = jQuery( '.wppa-bulk-photo' );
					var count=0;
					for ( i=0; i< photos.length; i++ ) {
						var photo = photos[i];
						if ( photo.checked ) count++;
					}
					if ( count == 0 ) {
						alert( 'No photos selected' );
						return false;
					}
					var action = document.getElementById( 'wppa-bulk-action' ).value;
					switch ( action ) {
						case '':
							alert( 'No action selected' );
							return false;
							break;
						case 'wppa-bulk-delete':
							break;
						case 'wppa-bulk-move-to':
						case 'wppa-bulk-copy-to':
							var album = document.getElementById( 'wppa-bulk-album' ).value;
							if ( album == 0 ) {
								alert( 'No album selected' );
								return false;
							}
							break;
						case 'wppa-bulk-status':
							var status = document.getElementById( 'wppa-bulk-status' ).value;
							if ( status == 0 ) {
								alert( 'No status selected' );
								return false;
							}
							break;
						case 'wppa-bulk-owner':
							var owner = documnet.getElementById( 'wppa-bulk-owner' ).value;
							if ( owner == 0 ) {
								alert( 'No new owner selected' );
								return false;
							}
							break;
						default:
							alert( 'Unimplemented action requested: '+action );
							return false;
							break;

					}
					return true;
				}
				function wppaSetThumbsize( elm ) {
					var thumbsize = elm.value;
					wppa_setCookie( 'wppa_bulk_thumbsize',thumbsize,365 );
					jQuery( '.wppa-bulk-thumb' ).css( 'max-width', thumbsize+'px' );
					jQuery( '.wppa-bulk-thumb' ).css( 'max-height', ( thumbsize/2 )+'px' );
					jQuery( '.wppa-bulk-dec' ).css( 'height', ( thumbsize/2 )+'px' );
				}
				jQuery( document ).ready( function() {
					var action = wppa_getCookie( 'wppa_bulk_action' );
					document.getElementById( 'wppa-bulk-action' ).value = action;
					if ( action == 'wppa-bulk-move-to' || action == 'wppa-bulk-copy-to' ) {
						jQuery( '#wppa-bulk-album' ).css( 'display','inline' );
						document.getElementById( 'wppa-bulk-album' ).value = wppa_getCookie( 'wppa_bulk_album' );
					}
					if ( action == 'wppa-bulk-status' ) {
						jQuery( '#wppa-bulk-status' ).css( 'display','inline' );
						document.getElementById( 'wppa-bulk-status' ).value = wppa_getCookie( 'wppa_bulk_status' );
					}
					if ( action == 'wppa-bulk-owner' ) {
						jQuery( '#wppa-bulk-owner' ).css( 'display','inline' );
						document.getElementById( 'wppa-bulk-owner' ).value = wppa_getCookie( 'wppa_bulk_owner' );
					}
				} );
function wppaTryMove( id, video ) {

	var query;

	if ( ! jQuery( '#target-' + id ).val() ) {
		alert( '<?php echo esc_js( __( 'Please select an album to move to first.', 'wp-photo-album-plus' ) ) ?>' );
		return false;
	}

	if ( video ) {
		query = '<?php echo esc_js( __( 'Are you sure you want to move this video?', 'wp-photo-album-plus' ) ) ?>';
	}
	else {
		query = '<?php echo esc_js( __( 'Are you sure you want to move this photo?', 'wp-photo-album-plus' ) ) ?>';
	}

	if ( jQuery('#confirm-move').attr('checked') != 'checked' || confirm( query ) ) {
		jQuery( '#moving-' + id ).html( '<?php _e( 'Moving...', 'wp-photo-album-plus' ) ?>' );
		_wppaAjaxUpdatePhoto( id, 'moveto', jQuery( '#target-' + id ).val(), false, '<td colspan="8" >', '</td>' );
	}
}

function wppaToggleConfirmDelete( elm ) {
	var status = jQuery( elm ).attr( 'checked' );
	if ( status == 'checked' ) {
		wppa_setCookie( 'wppaConfirmDelete', 'checked', 365 );
	}
	else {
		wppa_setCookie( 'wppaConfirmDelete', 'unchecked', 365 );
	}
}
function wppaToggleConfirmMove( elm ) {
	var status = jQuery( elm ).attr( 'checked' );
	if ( status == 'checked' ) {
		wppa_setCookie( 'wppaConfirmMove', 'checked', 365 );
	}
	else {
		wppa_setCookie( 'wppaConfirmMove', 'unchecked', 365 );
	}
}
function wppaSetConfirmDelete( id ) {
	var status = wppa_getCookie( 'wppaConfirmDelete' );
	if ( status == 'checked' ) {
		jQuery( '#' + id ).attr( 'checked', 'checked' );
	}
	else {
		jQuery( '#' + id ).removeAttr( 'checked' );
	}
}
function wppaSetConfirmMove( id ) {
	var status = wppa_getCookie( 'wppaConfirmMove' );
	if ( status == 'checked' ) {
		jQuery( '#' + id ).attr( 'checked', 'checked' );
	}
	else {
		jQuery( '#' + id ).removeAttr( 'checked' );
	}
}

			</script>
<?php /**/ ?>
			<form action="<?php echo $link.'&wppa-page='.$page.'#manage-photos' ?>" method="post" >
				<?php wp_nonce_field( 'wppa-bulk','wppa-bulk' ) ?>
				<h3>
				<span style="font-weight:bold;" ><?php _e( 'Bulk action:' , 'wp-photo-album-plus') ?></span>
				<select id="wppa-bulk-action" name="wppa-bulk-action" onchange="wppaBulkActionChange( this, 'bulk-album' )" >
					<option value="" ></option>
					<option value="wppa-bulk-delete" ><?php _e( 'Delete' , 'wp-photo-album-plus') ?></option>
					<option value="wppa-bulk-move-to" ><?php _e( 'Move to' , 'wp-photo-album-plus') ?></option>
					<option value="wppa-bulk-copy-to" ><?php _e( 'Copy to' , 'wp-photo-album-plus') ?></option>
					<?php if ( current_user_can( 'wppa_admin' ) || current_user_can( 'wppa_moderate' ) ) { ?>
						<option value="wppa-bulk-status" ><?php _e( 'Set status to' , 'wp-photo-album-plus') ?></option>
					<?php } ?>
					<?php if ( wppa_user_is( 'administrator' ) && wppa_switch( 'photo_owner_change' ) ) { ?>
						<option value="wppa-bulk-owner" ><?php _e( 'Set owner to' , 'wp-photo-album-plus') ?></option>
					<?php } ?>
				</select>
				<?php
		//		<select name="wppa-bulk-album" id="wppa-bulk-album" style="display:none;" onchange="wppa_setCookie( 'wppa_bulk_album',this.value,365 );" >
				echo wppa_album_select_a( array( 	'checkaccess' 		=> true,
													'path' 				=> wppa_switch( 'hier_albsel' ),
													'exclude' 			=> $album,
													'selected' 			=> '0',
													'addpleaseselect' 	=> true,
													'sort' 				=> true,
													'tagopen' 			=> '<select' .
																				' name="wppa-bulk-album"' .
																				' id="wppa-bulk-album"' .
																				' style="display:none;"' .
																				' onchange="wppa_setCookie( \'wppa_bulk_album\',this.value,365 );"' .
																				' >',
													'tagname' 			=> 'wppa-bulk-album',
													'tagid' 			=> 'wppa-bulk-album',
													'tagonchange' 		=> 'wppa_setCookie( \'wppa_bulk_album\',this.value,365 );',
													'tagstyle' 			=> 'display:none;cursor:pointer;',
													) );
				?>
				<select name="wppa-bulk-status" id="wppa-bulk-status" style="display:none;" onchange="wppa_setCookie( 'wppa_bulk_status',this.value,365 );" >
					<option value="" ><?php _e( '- select a status -' , 'wp-photo-album-plus') ?></option>
					<option value="pending" ><?php _e( 'Pending' , 'wp-photo-album-plus') ?></option>
					<option value="publish" ><?php _e( 'Publish' , 'wp-photo-album-plus') ?></option>
					<?php if ( wppa_switch( 'ext_status_restricted' ) && ! wppa_user_is( 'administrator' ) ) $dis = ' disabled'; else $dis = ''; ?>
					<option value="featured"<?php echo $dis?> ><?php _e( 'Featured' , 'wp-photo-album-plus') ?></option>
					<option value="gold" <?php echo $dis?> ><?php _e( 'Gold' , 'wp-photo-album-plus') ?></option>
					<option value="silver" <?php echo $dis?> ><?php _e( 'Silver' , 'wp-photo-album-plus') ?></option>
					<option value="bronze" <?php echo $dis?> ><?php _e( 'Bronze' , 'wp-photo-album-plus') ?></option>
					<option value="scheduled" <?php echo $dis?> ><?php _e( 'Scheduled' , 'wp-photo-album-plus') ?></option>
					<option value="private" <?php echo $dis ?> ><?php _e(  'Private' , 'wp-photo-album-plus') ?></option>
				</select>
				<!-- Owner -->
				<?php 	$users = wppa_get_users();
						if ( count( $users ) ) { ?>
				<select name="wppa-bulk-owner" id="wppa-bulk-owner" style="display:none;" onchange="wppa_setCookie( 'wppa_bulk_owner',this.value,365 );">
					<option value="" ><?php _e( '- select an owner -' , 'wp-photo-album-plus') ?></option>
					<?php

						foreach ( $users as $user ) {
							echo '<option value="'.$user['user_login'].'" >'.$user['display_name'].' ('.$user['user_login'].')</option>';
						}
					?>
				</select>
				<?php } else { ?>
				<input name="wppa-bulk-owner" id="wppa-bulk-owner" style="display:none;" onchange="wppa_setCookie( 'wppa_bulk_owner',this.value,365 );" />
				<?php } ?>
				<!-- Submit -->
				<input type="submit" onclick="return wppaBulkDoitOnClick()" class="button-primary" value="<?php _e( 'Doit!' , 'wp-photo-album-plus') ?>" />
				<?php
					if ( wppa_is_mobile() ) {
						echo '<br />';
					}
				?>
				<?php $nextafterselhtml =
					'<select name="next-after" >' .
						'<option value="-1" ' . ( $next_after == '-1' ? 'selected="selected"' : '' ) . ' >' . __( 'the previous page', 'wp-photo-album-plus' ) . '</option>' .
						'<option value="0" ' . ( $next_after == '0' ? 'selected="selected"' : '' ) . ' >' . __( 'the same page', 'wp-photo-album-plus' ) . '</option>' .
						'<option value="1" ' . ( $next_after == '1' ? 'selected="selected"' : '' ) . ' >' . __( 'the next page', 'wp-photo-album-plus' ) . '</option>' .
					'</select>';
					echo sprintf( __( 'Go to %s after Doit!.', 'wp-photo-album-plus'), $nextafterselhtml );
					if ( wppa_is_mobile() ) {
						echo '<br />';
					}
				?>

				<input
					type="checkbox"
					id="confirm-delete"
					name="confirm-delete"
					checked="checked"
					onchange="wppaToggleConfirmDelete( this );"
				/>
				<?php _e('Confirm delete', 'wp-photo-album-plus') ?>

				<input
					type="checkbox"
					id="confirm-move"
					name="confirm-move"
					checked="checked"
					onchange="wppaToggleConfirmMove(this);"
				/>
				<?php _e('Confirm move', 'wp-photo-album-plus') ?>

				<?php echo '<small style="float:right;" > (' . count( $photos ) . ')</small>'; ?>
				<script>
					jQuery( document ).ready( function() {
						wppaSetConfirmDelete( 'confirm-delete' );
						wppaSetConfirmMove( 'confirm-move' );
					});
				</script>
				</h3>
				<?php $edit_link = wppa_ea_url( 'single', $tab = 'edit' ) ?>
				<table class="widefat" >
					<thead style="font-weight:bold;" >
						<td><input type="checkbox" class="wppa-bulk-photo" onchange="jQuery( '.wppa-bulk-photo' ).attr( 'checked', this.checked );" /></td>
						<td><?php _e( 'ID' , 'wp-photo-album-plus') ?></td>
						<td><?php _e( 'Preview' , 'wp-photo-album-plus') ?></td>
						<td><?php _e( 'Name' , 'wp-photo-album-plus') ?></td>
						<td><?php _e( 'Description' , 'wp-photo-album-plus') ?></td>
						<td><?php _e( 'Status' , 'wp-photo-album-plus') ?></td>
						<td><?php _e( 'Owner' , 'wp-photo-album-plus') ?></td>
						<td><?php _e( 'Remark' , 'wp-photo-album-plus') ?></td>
					</thead>
					<tbody>
						<?php foreach ( $photos as $photo ) { ?>
						<?php $id = $photo['id']; ?>

						<?php // Album for moderate
						static $modalbum;
						if ( $album == 'moderate' ) {
							if ( $modalbum != $photo['album'] ) {
								echo '<tr><td colspan="8" style="background-color:lightgreen;" ><h3 style="margin:0;" >' . sprintf( 	__( 'Moderate photos from album %s by %s', 'wp-photo-album-plus' ),
																			'<i>' . wppa_get_album_name( $photo['album'] ) . '</i>',
																			'<i>' . wppa_get_album_item( $photo['album'], 'owner' ) . '</i>' ) . '</h3></td></tr>';
								$modalbum = $photo['album'];
							}
						}
						?>

						<?php $maxsize = wppa_get_minisize(); ?>
						<tr id="photoitem-<?php echo $photo['id'] ?>" >
							<!-- Checkbox -->
							<td>
								<input type="hidden" id="photo-nonce-<?php echo $photo['id'] ?>" value="<?php echo wp_create_nonce( 'wppa_nonce_'.$photo['id'] );  ?>" />
								<input type="checkbox" name="wppa-bulk-photo[<?php echo $photo['id'] ?>]" class="wppa-bulk-photo" />
							</td>
							<!-- ID and delete link -->
							<td><?php
								echo
								'<a' .
									' href="' . $edit_link . '&photo=' . $photo['id'] . '"' .
									' target="_blank"' .
									' >' .
									$photo['id'] .
								'</a>' .
								'<br />' .
								'<a' .
									' id="wppa-delete-' . $photo['id'] . '"' .
									' onclick="if ( jQuery(\'#confirm-delete\').attr(\'checked\') != \'checked\' ||
													confirm( \'' . esc_js( __( 'Are you sure you want to delete this photo?', 'wp-photo-album-plus' ) ) . '\' ) ) {
										jQuery(this).html( \'' . esc_js( __('Deleting...', 'wp-photo-album-plus') ) . '\' );
										wppaAjaxDeletePhoto( \'' . $photo['id'] . '\', \'<td colspan=8 >\', \'</td>\' ) }"' .
									' style="color:red;font-weight:bold;cursor:pointer;"' .
									' >' .
									__( 'Delete', 'wp-photo-album-plus' ) .
								'</a>';
								?>
							</td>
							<!-- Preview -->
							<td style="min-width:240px; text-align:center;" >
							<?php if ( wppa_is_video( $photo['id'] ) ) { ?>
								<a href="<?php echo str_replace( 'xxx', 'mp4', wppa_get_photo_url( $photo['id'] ) ) ?>" target="_blank" title="Click to see fullsize" >
									<?php // Animating size changes of a video tag is not a good idea. It will rapidly screw up browser cache and cpu ?>
									<?php echo wppa_get_video_html( array(
													'id'			=> $id,
												//	'width'			=> $imgwidth,
													'height' 		=> '60',
													'controls' 		=> false,
												//	'margin_top' 	=> '0',
												//	'margin_bottom' => '0',
													'tagid' 		=> 'pa-id-'.$id,
												//	'cursor' 		=> 'cursor:pointer;',
													'events' 		=> ' onmouseover="jQuery( this ).css( \'height\', \'160\' )" onmouseout="jQuery( this ).css( \'height\', \'60\' )"',
												//	'title' 		=> $title,
													'preload' 		=> 'metadata',
												//	'onclick' 		=> $onclick,
												//	'lb' 			=> false,
												//	'class' 		=> '',
												//	'style' 		=> $imgstyle,
													'use_thumb' 	=> true
													));


									?>
					<!--				<video preload="metadata" style="height:60px;" onmouseover="jQuery( this ).css( 'height', '160' )" onmouseout="jQuery( this ).css( 'height', '60' )" >
										<?php // echo wppa_get_video_body( $photo['id'] ) ?>
									</video>	-->
								</a>
							<?php }
							else {
								echo
									'<a' .
										' href="' . wppa_get_photo_url( $photo['id'] ) . '"' .
										' target="_blank"' .
										' title="Click to see fullsize"' .
										' >' .
										'<img' .
											' class="wppa-bulk-thumb"' .
											' src="' . wppa_get_thumb_url( $photo['id'] ) . '"' .
											' style="max-width:' . $maxsize . 'max-height:' . $maxsize . 'px;"' .
									//		' onmouseover="jQuery( this ).stop().animate( {height:120}, 100 )"' .
									//		' onmouseout="jQuery( this ).stop().animate( {height:60}, 100 )"' .
										' />' .
									'</a>';
							}
							?>
							</td>
							<!-- Name, size, move -->
							<!-- Name -->
							<td style="width:25%;" >
								<input type="text" style="width:300px;" id="pname-<?php echo $photo['id'] ?>" onchange="wppaAjaxUpdatePhoto( <?php echo $photo['id'] ?>, 'name', this );" value="<?php echo esc_attr( stripslashes( $photo['name'] ) ) ?>" />
								<!-- Size -->
								<?php
								if ( wppa_is_video( $photo['id'] ) ) {
									echo '<br />'.wppa_get_videox( $photo['id'] ).' x '.wppa_get_videoy( $photo['id'] ).' px.';
								}
								else {
									$sp = wppa_get_source_path( $photo['id'] );
									if ( is_file( $sp ) ) {
										$ima = getimagesize( $sp );
										if ( is_array( $ima ) ) {
											echo '<br />'.$ima['0'].' x '.$ima['1'].' px.';
										}
									}
								}
								?>
								<!-- Move -->
								<?php
									$max = wppa_opt( 'photo_admin_max_albums' );
									if ( ! $max || wppa_get_total_album_count() < $max ) {

										// If not done yet, get the album options html with the current album excluded
										if ( ! isset( $album_select[$album] ) ) {
											$album_select[$album] = wppa_album_select_a( array( 	'checkaccess' 		=> true,
																									'path' 				=> wppa_switch( 'hier_albsel' ),
																									'exclude' 			=> $album,
																									'selected' 			=> '0',
																									'addpleaseselect' 	=> true,
																									'sort' 				=> true,
																								)
																						);
										}

										echo
										'<br />' . __( 'Target album for move to:', 'wp-photo-album-plus' ) . '<br />' .
										'<select' .
											' id="target-' . $id . '"' .
											' onchange="wppaTryMove(' . $id . ', ' . ( wppa_is_video( $id ) ? 'true' : 'false' ) . ');"' .
											' style="max-width:300px;"' .
											' >' .
											$album_select[$album] .
										'</select>' .
										'<span id="moving-' . $id . '" style="color:red;font-weight:bold;" ></span>';
									}

								?>
							</td>
							<!-- Description -->
							<td style="width:25%;" >
								<textarea class="wppa-bulk-dec" style="height:50px; width:100%" onchange="wppaAjaxUpdatePhoto( <?php echo $photo['id'] ?>, 'description', this )" ><?php echo( stripslashes( $photo['description'] ) ) ?></textarea>
							</td>
							<!-- Status -->
							<td>
							<?php if ( current_user_can( 'wppa_admin' ) || current_user_can( 'wppa_moderate' ) )  { ?>
								<select id="status-<?php echo $photo['id'] ?>" onchange="wppaAjaxUpdatePhoto( <?php echo $photo['id'] ?>, 'status', this ); wppaPhotoStatusChange( <?php echo $photo['id'] ?> ); ">
									<option value="pending" <?php if ( $photo['status']=='pending' ) echo 'selected="selected"'?> ><?php _e( 'Pending' , 'wp-photo-album-plus') ?></option>
									<option value="publish" <?php if ( $photo['status']=='publish' ) echo 'selected="selected"'?> ><?php _e( 'Publish' , 'wp-photo-album-plus') ?></option>
									<?php if ( wppa_switch( 'ext_status_restricted' ) && ! wppa_user_is( 'administrator' ) ) $dis = ' disabled'; else $dis = ''; ?>
									<option value="featured" <?php if ( $photo['status']=='featured' ) echo 'selected="selected"'; echo $dis?> ><?php _e( 'Featured' , 'wp-photo-album-plus') ?></option>
									<option value="gold" <?php if ( $photo['status'] == 'gold' ) echo 'selected="selected"'; echo $dis?> ><?php _e( 'Gold' , 'wp-photo-album-plus') ?></option>
									<option value="silver" <?php if ( $photo['status'] == 'silver' ) echo 'selected="selected"'; echo $dis?> ><?php _e( 'Silver' , 'wp-photo-album-plus') ?></option>
									<option value="bronze" <?php if ( $photo['status'] == 'bronze' ) echo 'selected="selected"'; echo $dis?> ><?php _e( 'Bronze' , 'wp-photo-album-plus') ?></option>
									<option value="scheduled" <?php if ( $photo['status'] == 'scheduled' ) echo 'selected="selected"'; echo $dis?> ><?php _e( 'Scheduled' , 'wp-photo-album-plus') ?></option>
									<option value="private" <?php if ( $photo['status'] == 'private' ) echo 'selected="selected"'; echo $dis ?> ><?php _e( 'Private' , 'wp-photo-album-plus') ?></option>
								</select>
							<?php }
								else {
									if ( $photo['status'] == 'pending' ) _e( 'Pending' , 'wp-photo-album-plus');
									elseif ( $photo['status'] == 'publish' ) _e( 'Publish' , 'wp-photo-album-plus');
									elseif ( $photo['status'] == 'featured' ) e( 'Featured' );
									elseif ( $photo['status'] == 'gold' ) _e( 'Gold' , 'wp-photo-album-plus');
									elseif ( $photo['status'] == 'silver' ) _e( 'Silver' , 'wp-photo-album-plus');
									elseif ( $photo['status'] == 'bronze' ) _e( 'Bronze' , 'wp-photo-album-plus');
									elseif ( $photo['status'] == 'scheduled' ) _e( 'Scheduled' , 'wp-photo-album-plus');
									elseif ( $photo['status'] == 'private' ) _e( 'Private' , 'wp-photo-album-plus');
								} ?>
							</td>
							<!-- Owner -->
							<td>
								<?php echo $photo['owner'] ?>
							</td>
							<!-- Remark -->
							<td id="photostatus-<?php echo $photo['id'] ?>" style="width:25%;" >
								<?php _e( 'Not modified' , 'wp-photo-album-plus') ?>
								<script type="text/javascript">wppaPhotoStatusChange( <?php echo $photo['id'] ?> )</script>
							</td>
						</tr>
						<?php } ?>
					</tbody>
					<tfoot style="font-weight:bold;" >
						<td><input type="checkbox" class="wppa-bulk-photo" onchange="jQuery( '.wppa-bulk-photo' ).attr( 'checked', this.checked );" /></td>
						<td><?php _e( 'ID' , 'wp-photo-album-plus') ?></td>
						<td><?php _e( 'Preview' , 'wp-photo-album-plus') ?></td>
						<td><?php _e( 'Name' , 'wp-photo-album-plus') ?></td>
						<td><?php _e( 'Description' , 'wp-photo-album-plus') ?></td>
						<td><?php _e( 'Status' , 'wp-photo-album-plus') ?></td>
						<td><?php _e( 'Owner' , 'wp-photo-album-plus') ?></td>
						<td><?php _e( 'Remark' , 'wp-photo-album-plus') ?></td>
					</tfoot>
				</table>
			</form>
			<?php
			wppa_admin_page_links( $page, $pagesize, $count, $plink, '#manage-photos' );
		}
		else {
			if ( $page == '1' ) {
				if ( isset( $_REQUEST['wppa-searchstring'] ) ) {
					echo '<h3>'.__( 'No photos matching your search criteria.' , 'wp-photo-album-plus').'</h3>';
				}
				elseif ( $album == 'moderate' ) {
					echo '<h3>'.__( 'No photos to moderate', 'wp-photo-album-plus' ) . '</h3>';
				}
				else {
					echo '<h3>'.__( 'No photos yet in this album.' , 'wp-photo-album-plus' ).'</h3>';
				}
			}
			else {
				$page_1 = $page - '1';
				echo sprintf( __( 'Page %d is empty, try <a href="%s" >page %d</a>.' , 'wp-photo-album-plus'), $page, $link.'&wppa-page='.$page_1.'#manage-photos', $page_1 );
			}
		}
	}
	else {
		wppa_dbg_msg( 'Missing required argument in wppa_album_photos() 2', 'red', 'force' );
	}
}

function wppa_album_photos_sequence( $album ) {
global $wpdb;

	if ( $album ) {
		$photoorder 	= wppa_get_photo_order( $album, 'norandom' );
		$is_descending 	= strpos( $photoorder, 'DESC' ) !== false;
		$is_p_order 	= strpos( $photoorder, 'p_order' ) !== false;
		$photos 		= $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `'.WPPA_PHOTOS.'` WHERE `album` = %s '.$photoorder, $album ), ARRAY_A );
		$link 			= wppa_dbg_url( get_admin_url().'admin.php?page=wppa_admin_menu&tab=edit&edit_id='.$album.'&bulk'.'&wppa_nonce=' . wp_create_nonce('wppa_nonce') );
		$size 			= '180';

		if ( $photos ) {
			?>
			<style>
				.sortable-placeholder {
					width: <?php echo $size ?>px;
					height: <?php echo $size ?>px;
					margin: 5px;
					border: 1px solid #cccccc;
					border-radius:3px;
					float: left;
				}
				.ui-state-default {
					position: relative;
					width: <?php echo $size ?>px;
					height: <?php echo $size ?>px;
					margin: 5px;
					border-radius:3px;
					float: left;
				}
				.wppa-publish {
					border: 1px solid;
					background-color: rgb( 255, 255, 224 );
					border-color: rgb( 230, 219, 85 );
				}
				.wppa-featured {
					border: 1px solid;
					background-color: rgb( 224, 255, 224 );
					border-color: rgb( 85, 238, 85 );
				}
				.wppa-pending, .wppa-scheduled, .wppa-private {
					border: 1px solid;
					background-color: rgb( 255, 235, 232 );
					border-color: rgb( 204, 0, 0 );
				}
				.wppa-bronze {
					border: 1px solid;
					background-color: rgb( 221, 221, 187 );
					border-color: rgb( 204, 204, 170 );
				}
				.wppa-silver {
					border: 1px solid;
					background-color: rgb( 255, 255, 255 );
					border-color: rgb( 238, 238, 238 );
				}
				.wppa-gold {
					border: 1px solid;
					background-color: rgb( 238, 238, 204 );
					border-color: rgb( 221, 221, 187 );
				}
			</style>
			<script>
				jQuery( function() {
					jQuery( "#sortable" ).sortable( {
						cursor: "move",
						placeholder: "sortable-placeholder",
						stop: function( event, ui ) {
							var ids = jQuery( ".wppa-sort-item" );
							var seq = jQuery( ".wppa-sort-seqn" );
							var idx = 0;
							var descend = <?php if ( $is_descending ) echo 'true'; else echo 'false' ?>;
							while ( idx < ids.length ) {
								var newvalue;
								if ( descend ) newvalue = ids.length - idx;
								else newvalue = idx + 1;
								var oldvalue = seq[idx].value;
								var photo = ids[idx].value;
								if ( newvalue != oldvalue ) {
									wppaDoSeqUpdate( photo, newvalue );
								}
								idx++;
							}
						}
					} );
				} );
				function wppaDoSeqUpdate( photo, seqno ) {
					var data = 'action=wppa&wppa-action=update-photo&photo-id='+photo+'&item=p_order&wppa-nonce='+document.getElementById( 'photo-nonce-'+photo ).value+'&value='+seqno;
					var xmlhttp = new XMLHttpRequest();

					xmlhttp.onreadystatechange = function() {
						if ( xmlhttp.readyState == 4 && xmlhttp.status != 404 ) {
							var ArrValues = xmlhttp.responseText.split( "||" );
							if ( ArrValues[0] != '' ) {
								alert( 'The server returned unexpected output:\n'+ArrValues[0] );
							}
							switch ( ArrValues[1] ) {
								case '0':	// No error
									jQuery( '#wppa-seqno-'+photo ).html( seqno );
									break;
								case '99':	// Photo is gone
									jQuery( '#wppa-seqno-'+photo ).html( '<span style="color"red" >deleted</span>' );
									break;
								default:	// Any error
									jQuery( '#wppa-seqno-'+photo ).html( '<span style="color"red" >Err:'+ArrValues[1]+'</span>' );
									break;
							}
						}
					}
					xmlhttp.open( 'POST',wppaAjaxUrl,true );
					xmlhttp.setRequestHeader( "Content-type","application/x-www-form-urlencoded" );
					xmlhttp.send( data );
					jQuery( "#wppa-sort-seqn-"+photo ).attr( 'value', seqno );	// set hidden value to new value to prevent duplicate action
					var spinnerhtml = '<img src="'+wppaImageDirectory+'spinner.'+'<?php echo ( wppa_use_svg() ? 'svg' : 'gif' ) ?>'+'" />';
					jQuery( '#wppa-seqno-'+photo ).html( spinnerhtml );
				}
			</script>
			<?php if ( ! $is_p_order ) wppa_warning_message( __( 'Setting photo sequence order has only effect if the photo order method is set to <b>Order#</b>' , 'wp-photo-album-plus') ) ?>
			<div class="widefat" style="border-color:#cccccc" >
				<div id="sortable">
					<?php foreach ( $photos as $photo ) {
						if ( wppa_is_video( $photo['id'] ) ) {
							$imgs['0'] = wppa_get_videox( $photo['id'] );
							$imgs['1'] = wppa_get_videoy( $photo['id'] );
						}
						else {
							$imgs['0'] = wppa_get_thumbx( $photo['id'] );
							$imgs['1'] = wppa_get_thumby( $photo['id'] );
						}
						if ( ! $imgs['0'] ) {	// missing thuimbnail, prevent division by zero
							$imgs['0'] = 200;
							$imgs['1'] = 150;
						}
						$mw = $size - '20';
						$mh = $mw * '3' / '4';
						if ( $imgs[1]/$imgs[0] > $mh/$mw ) {	// more portrait than 200x150, y is limit
							$mt = '15';
						}
						else {	// x is limit
							$mt = ( $mh - ( $imgs[1]/$imgs[0] * $mw ) ) / '2' + '15';
						}
					?>
					<div id="photoitem-<?php echo $photo['id'] ?>" class="ui-state-default wppa-<?php echo $photo['status'] ?>" style="background-image:none; text-align:center; cursor:move;" >
					<?php if ( wppa_is_video( $photo['id'] ) ) { ?>
					<?php $id = $photo['id'] ?>
					<?php $imgstyle = 'max-width:'.$mw.'px; max-height:'.$mh.'px; margin-top:'.$mt.'px;' ?>
					<?php echo wppa_get_video_html( array(
													'id'			=> $id,
												//	'width'			=> $imgwidth,
												//	'height' 		=> '60',
													'controls' 		=> false,
												//	'margin_top' 	=> '0',
												//	'margin_bottom' => '0',
													'tagid' 		=> 'pa-id-'.$id,
												//	'cursor' 		=> 'cursor:pointer;',
												//	'events' 		=> ' onmouseover="jQuery( this ).css( \'height\', \'160\' )" onmouseout="jQuery( this ).css( \'height\', \'60\' )"',
												//	'title' 		=> $title,
													'preload' 		=> 'metadata',
												//	'onclick' 		=> $onclick,
												//	'lb' 			=> false,
													'class' 		=> 'wppa-bulk-thumb',
													'style' 		=> $imgstyle,
													'use_thumb' 	=> true
													));
						?>
	<!--					<video preload="metadata" class="wppa-bulk-thumb" style="max-width:<?php echo $mw ?>px; max-height:<?php echo $mh ?>px; margin-top: <?php echo $mt ?>px;" >
						 // echo //wppa_get_video_body( $photo['id'] ) ?>
						</video>
	-->
					<?php }
					else { ?>
						<img class="wppa-bulk-thumb" src="<?php echo wppa_get_thumb_url( $photo['id'] ) ?>" style="max-width:<?php echo $mw ?>px; max-height:<?php echo $mh ?>px; margin-top: <?php echo $mt ?>px;" />
					<?php } ?>
						<div style="font-size:9px; position:absolute; bottom:24px; text-align:center; width:<?php echo $size ?>px;" ><?php echo wppa_get_photo_name( $photo['id'] ) ?></div>
						<div style="text-align: center; width: <?php echo $size ?>px; position:absolute; bottom:8px;" >
							<span style="margin-left:15px;float:left"><?php echo __( 'Id: ' , 'wp-photo-album-plus').$photo['id']?></span>
							<?php if ( wppa_is_video( $photo['id'] ) )_e('Video', 'wp-photo-album-plus'); ?>
							<?php if ( wppa_has_audio( $photo['id'] ) ) _e('Audio', 'wp-photo-album-plus'); ?>
							<span style="float:right; margin-right:15px;"><?php echo __( 'Ord: ' , 'wp-photo-album-plus').'<span id="wppa-seqno-'.$photo['id'].'" >'.$photo['p_order'] ?></span>
						</div>
						<input type="hidden" id="photo-nonce-<?php echo $photo['id'] ?>" value="<?php echo wp_create_nonce( 'wppa_nonce_'.$photo['id'] );  ?>" />
						<input type="hidden" class="wppa-sort-item" value="<?php echo $photo['id'] ?>" />
						<input type="hidden" class="wppa-sort-seqn" id="wppa-sort-seqn-<?php echo $photo['id'] ?>" value="<?php echo $photo['p_order'] ?>" />
					</div>
					<?php } ?>
				</div>
				<div style="clear:both;"></div>
			</div>
			<?php
		}
		else {
			echo '<h3>'.__( 'The album is empty.' , 'wp-photo-album-plus').'</h3>';
		}
	}
	else {
		wppa_dbg_msg( 'Missing required argument in wppa_album_photos() 3', 'red', 'force' );
	}
}

function wppa_get_edit_search_photos( $limit = '', $count_only = false ) {
global $wpdb;
global $wppa_search_stats;

	$doit = false;

	if ( current_user_can( 'wppa_admin' ) && current_user_can( 'wppa_moderate' ) ) $doit = true;
	if ( wppa_opt( 'upload_edit' ) != '-none-' ) $doit = true;
	if ( ! $doit ) {	// Should never get here. Only when url is manipulted manually.
		die('Security check failure #309');
	}

	$words = explode( ',', wppa_sanitize_searchstring( $_REQUEST['wppa-searchstring'] ) );

	$wppa_search_stats = array();

	$first = true;
	$photo_array = array();

	// See if only ids given
	if ( wppa_user_is( 'administrator' ) ) {
		foreach ( $words as $word ) {
			if ( wppa_is_int( $word ) ) {
				$photo_array[] = $word;
			}
		}
		asort( $photo_array );
	}

	// Nothing? Process normal serch
	if ( ! count( $photo_array ) ) {

		foreach( $words as $word ) {

			// Find lines in index db table
			if ( wppa_switch( 'wild_front' ) ) {
				$pidxs = $wpdb->get_results( "SELECT `slug`, `photos` FROM `".WPPA_INDEX."` WHERE `slug` LIKE '%".$word."%'", ARRAY_A );
			}
			else {
				$pidxs = $wpdb->get_results( "SELECT `slug`, `photos` FROM `".WPPA_INDEX."` WHERE `slug` LIKE '".$word."%'", ARRAY_A );
			}

			$photos = '';

			foreach ( $pidxs as $pi ) {
				$photos .= $pi['photos'].',';
			}

			if ( $first ) {
				$photo_array 	= wppa_index_string_to_array( trim( $photos, ',' ) );
				$count 			= empty( $photo_array ) ? '0' : count( $photo_array );
				$list 			= implode( ',', $photo_array );
				if ( ! $list ) {
					$list = '0';
				}

				if ( current_user_can( 'wppa_admin' ) && current_user_can( 'wppa_moderate' ) ) {
					$real_count = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `id` IN (".$list.") " );
					if ( $count != $real_count ) {
						update_option( 'wppa_remake_index_photos_status', __('Required', 'wp-photo-album-plus') );
					}
				}
				else { // Not admin, can edit own photos only
					$real_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `id` IN (".$list.") AND `owner` = %s", wppa_get_user() ) );
				}

				$wppa_search_stats[] 	= array( 'word' => $word, 'count' => $real_count );
				$first = false;
			}
			else {
				$temp_array 	= wppa_index_string_to_array( trim( $photos, ',' ) );
				$count 			= empty( $temp_array ) ? '0' : count( $temp_array );
				$list 			= implode( ',', $temp_array );

				if ( current_user_can( 'wppa_admin' ) && current_user_can( 'wppa_moderate' ) ) {
					$real_count = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `id` IN (".$list.") " );
					if ( $count != $real_count ) {
						update_option( 'wppa_remake_index_photos_status', __('Required', 'wp-photo-album-plus') );
					}
				}
				else { // Not admin, can edit own photos only
					$real_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `id` IN (".$list.") AND `owner` = %s", wppa_get_user() ) );
				}

				$wppa_search_stats[] 	= array( 'word' => $word, 'count' => $real_count );
				$photo_array 			= array_intersect( $photo_array, $temp_array );
			}
		}
	}

	if ( ! empty( $photo_array ) ) {

		$list = implode( ',', $photo_array );

//		if ( wppa_user_is( 'administrator' ) ) {
		if ( current_user_can( 'wppa_admin' ) && current_user_can( 'wppa_moderate' ) ) {
			$totcount = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `id` IN (".$list.") " );
		}
		else { // Not admin, can edit own photos only
			$totcount = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `id` IN (".$list.") AND `owner` = %s" , wppa_get_user() ) );
		}

		$wppa_search_stats[] = array( 'word' => __( 'Combined', 'wp-photo-album-plus'), 'count' => $totcount );

//		if ( wppa_user_is( 'administrator' ) ) {
		if ( current_user_can( 'wppa_admin' ) && current_user_can( 'wppa_moderate' ) ) {
			$photos = $wpdb->get_results( "SELECT * FROM `".WPPA_PHOTOS."` WHERE `id` IN (".$list.") " . wppa_get_photo_order( '0', 'norandom' ).$limit, ARRAY_A );
		}
		else { // Not admin, can edit own photos only
			$photos = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPPA_PHOTOS."` WHERE `id` IN (".$list.") AND `owner` = %s" . wppa_get_photo_order( '0', 'norandom' ).$limit, wppa_get_user() ), ARRAY_A );
		}
	}
	else {
		$photos = false;
	}


	if ( $count_only ) {
		if ( is_array( $photos ) ) {
			return count( $photos );
		}
		else {
			return '0';
		}
	}
	else {
		return $photos;
	}
}

function wppa_show_search_statistics() {
global $wppa_search_stats;

	if ( isset( $_REQUEST['wppa-searchstring'] ) ) {
		echo '
		<table>
			<thead>
				<tr>
					<td><b>' .
						__('Word', 'wp-photo-album-plus') . '
					</b></td>
					<td><b>' .
						__('Count', 'wp-photo-album-plus') . '
					</b></td>
				</tr>
				<tr>
					<td><hr /></td>
					<td><hr /></td>
				</tr>
			</thead>
			<tbody>';
			$count = empty( $wppa_search_stats ) ? '0' : count( $wppa_search_stats );
			$c = '0';
			$s = '';
			foreach( $wppa_search_stats as $search_item ) {
				$c++;
				if ( $c == $count ) {
					echo '<tr><td><hr /></td><td><hr /></td></tr>';
					$s = 'style="font-weight:bold;"';
				}
				echo '
				<tr>
					<td '.$s.'>' .
						$search_item['word'] . '
					</td>
					<td '.$s.'>' .
						$search_item['count'] . '
					</td>
				</tr>';
			}
		echo '
		</table>';
	}
}

// New style fron-end edit photo
function wppa_fe_edit_new_style( $photo ) {

	$items 	= array( 	'name',
						'description',
						'tags',
						'custom_0',
						'custom_1',
						'custom_2',
						'custom_3',
						'custom_4',
						'custom_5',
						'custom_6',
						'custom_7',
						'custom_8',
						'custom_9',
						);
	$titles = array( 	__( 'Name', 'wp-photo-album-plus' ),
						__( 'Description', 'wp-photo-album-plus' ),
						__( 'Tags', 'wp-photo-album-plus' ),
						apply_filters( 'translate_text', wppa_opt( 'custom_caption_0' ) ),
						apply_filters( 'translate_text', wppa_opt( 'custom_caption_1' ) ),
						apply_filters( 'translate_text', wppa_opt( 'custom_caption_2' ) ),
						apply_filters( 'translate_text', wppa_opt( 'custom_caption_3' ) ),
						apply_filters( 'translate_text', wppa_opt( 'custom_caption_4' ) ),
						apply_filters( 'translate_text', wppa_opt( 'custom_caption_5' ) ),
						apply_filters( 'translate_text', wppa_opt( 'custom_caption_6' ) ),
						apply_filters( 'translate_text', wppa_opt( 'custom_caption_7' ) ),
						apply_filters( 'translate_text', wppa_opt( 'custom_caption_8' ) ),
						apply_filters( 'translate_text', wppa_opt( 'custom_caption_9' ) ),
						);
	$types 	= array( 	'text',
						'textarea',
						'text',
						'text',
						'text',
						'text',
						'text',
						'text',
						'text',
						'text',
						'text',
						'text',
						'text',
						);
	$doit 	= array(	wppa_switch( 'fe_edit_name' ),
						wppa_switch( 'fe_edit_desc' ),
						wppa_switch( 'fe_edit_tags' ),
						wppa_switch( 'custom_edit_0' ),
						wppa_switch( 'custom_edit_1' ),
						wppa_switch( 'custom_edit_2' ),
						wppa_switch( 'custom_edit_3' ),
						wppa_switch( 'custom_edit_4' ),
						wppa_switch( 'custom_edit_5' ),
						wppa_switch( 'custom_edit_6' ),
						wppa_switch( 'custom_edit_7' ),
						wppa_switch( 'custom_edit_8' ),
						wppa_switch( 'custom_edit_9' ),
						);

	// Open page
	echo
		'<div' .
			' style="width:100%;margin-top:8px;padding:8px;display:block;box-sizing:border-box;background-color:#fff;"' .
//			' class="site-main"' .
//			' role="main"' .
			' >' .
			'<h3>' .
			'<img' .
				' style="height:50px;"' .
				' src="' . wppa_get_thumb_url( $photo ) . '"' .
				' alt="' . $photo . '"' .
			' />' .
			'&nbsp;&nbsp;' .
			wppa_opt( 'fe_edit_caption' ) . '</h3>';

	// Open form
	echo
		'<form' .
			' >' .
			'<input' .
				' type="hidden"' .
				' id="wppa-nonce-' . $photo . '"' .
				' name="wppa-nonce"' .
				' value="' . wp_create_nonce( 'wppa-nonce-' . $photo ) . '"' .
				' />';

	// Get custom data
	$custom = wppa_get_photo_item( $photo, 'custom' );
	if ( $custom ) {
		$custom_data = unserialize( $custom );
	}
	else {
		$custom_data = array( '', '', '', '', '', '', '', '', '', '' );
	}

	// Items
	foreach ( array_keys( $items ) as $idx ) {
		if ( $titles[$idx] && $doit[$idx] ) {
			echo
				'<h6>' . $titles[$idx] . '</h6>';

				if ( wppa_is_int( substr( $items[$idx], -1 ) ) ) {
					$value = stripslashes( $custom_data[substr( $items[$idx], -1 )] );
				}
				else {
					$value = wppa_get_photo_item( $photo, $items[$idx] );
					if ( $items[$idx] == 'tags' ) {
						$value = trim( $value, ',' );
					}
				}
				if ( $types[$idx] == 'text' ) {
					echo
						'<input' .
							' type="text"' .
							' style="width:100%;"' .
							' id="' . $items[$idx] . '"' .
							' name="' . $items[$idx] . '"' .
							' value="' . esc_attr( $value ) . '"' .
						' />';
				}
				if ( $types[$idx] == 'textarea' ) {
					echo
						'<textarea' .
							' style="width:100%;min-width:100%;max-width:100%;"' .
							' id="' . $items[$idx] . '"' .
							' name="' . $items[$idx] . '"' .
							' >' .
							esc_textarea( stripslashes( $value ) ) .
						'</textarea>';
				}
		}
	}

	// Submit
	echo
		'<input' .
			' type="button"' .
			' style="margin-top:8px;margin-right:8px;"' .
			' value="' . esc_attr( __( 'Send', 'wp-photo-album-plus' ) ) . '"' .
			' onclick="wppaUpdatePhotoNew(' . $photo . ');document.location.reload(true);"' .
			' />';

	// Cancel
	echo
		'<input' .
			' type="button"' .
			' style="margin-top:8px;"' .
			' value="' . esc_attr( __( 'Cancel', 'wp-photo-album-plus' ) ) . '"' .
			' onclick="jQuery( \'#wppa-modal-container-' . strval( intval( $_REQUEST['moccur'] ) ) . '\').dialog(\'close\')"' .
			' />';

	// Close form
	echo
		'</form>';

	// Close page
	echo
		'</div>';

}

// See if this photo needs the ImageMagick features
function wppa_can_admin_magick( $id ) {

	// Is ImageMagick on board?
	if ( ! wppa_opt( 'image_magick' ) ) {
		return false;
	}

	// Is it a video?
	if ( wppa_is_video( $id ) ) {
		return false;
	}

	return true;
}
