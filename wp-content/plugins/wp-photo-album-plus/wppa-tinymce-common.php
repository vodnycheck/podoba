<?php
/* wppa-tinymce-common.php
* Pachkage: wp-photo-album-plus
*
* Version 6.7.11
*
*/

function wppa_make_tinymce_photo_dialog( $front = false ) {
global $wpdb;

	// Prepare albuminfo
	$albums = $wpdb->get_results( 	"SELECT `id`, `name` " .
									"FROM `" . WPPA_ALBUMS . "` " .
									"WHERE `owner` = '" . wppa_get_user() . "' " .
									"OR `owner` = '--- public ---' " .
									"ORDER BY `name` ",
									ARRAY_A );

	// Make the html
	// Open wrapper
	$result =
	'<div id="wppaphoto-form" title="' . esc_attr( __( 'Insert photo', 'wp-photo-album-plus' ) ) . '" >';

		// Front/back end dependant css
		if ( $front ) {
			$result .= '
			<style>
				#wppaphoto-table {
					box-sizing:border-box; width:100% !important;
				}
				.wppa-bar {
					background-color: #7f7;
					width:0%;
					height:18px;
					border-radius: 3px;
					line-height: 18px;
					margin: 0;
				}
				.wppa-percent {
					position:relative;
					display:inline-block;
					top:-19px;
					font-size: 12px;
					line-height: 18px;
					margin: 0;
				}
				.wppa-message {
					clear: both;
				}
				#wppaphoto-table tr, #wppaphoto-table th, #wppaphoto-table td {
					padding: 2px; 0;
				}
				#wppaphoto-table th {
					width:200px;
				}
				#wppaphoto-table input {
					padding:4px;
				}
				#wppaphoto-table th, #wppaphoto-table td {
					border:none;
				}
				#wppaphoto-shortcode-preview-container {
					width:500px !important;
				}
			</style>';
		}
		else {
			$result .= '
			<style>
				#TB_ajaxContent {
					box-sizing:border-box; width:100% !important;
				}
				.wppa-bar {
					background-color: #7f7;
					width:0%;
					height:18px;
					border-radius: 3px;
					line-height: 18px;
					margin: 0;
				}
				.wppa-percent {
					position:relative;
					display:inline-block;
					top:-19px;
					font-size: 12px;
					line-height: 18px;
					margin: 0;
				}
				.wppa-message {
					clear: both;
				}
				#wppaphoto-table tr, #wppaphoto-table th, #wppaphoto-table td {
					padding: 2px; 0;
				}
			</style>';
		}

		// Open table
		$result .=
		'
		<table id="wppaphoto-table" class="form-table" >' .
			'<tbody>' .

				// My photos selection
				'
				<tr id="wppaphoto-myphoto-tr" style="" >' .
					'<th><label for="wppaphoto-myphoto" class="wppaphoto-myphoto" >'.__('My Photo to be used', 'wp-photo-album-plus').':</label></th>'.
					'<td>'.
						'<select id="wppaphoto-myphoto" name="photo" class="wppaphoto-myphoto" onchange="wppaPhotoEvaluate()" >' .
							wppa_get_myphotos_selection_body_for_tinymce() .
						'</select>'.
						'<input' .
							' type="button"' .
							' value="' . esc_attr( __( 'All photos', 'wp-photo-album-plus' ) ) . '"' .
							' onclick="jQuery(\'#wppaphoto-myphoto-tr\').hide();jQuery(\'#wppaphoto-allphoto-tr\').show();wppaMyPhotoSelection=false;jQuery(\'#wppaphoto-photo-preview\').html(\'\');wppaPhotoEvaluate();"' .
						' />' .
						'<br />'.
						'<small style="" class="wppamyphoto-photo" >'.
							__('Specify the photo to be used', 'wp-photo-album-plus').'<br />'.
							__('You can select one of your photos from a maximum of 100 most recently added', 'wp-photo-album-plus').'<br />'.
						'</small>'.
					'</td>'.
				'</tr>' .

				// Photo selection max 100 of all photos
				'
				<tr id="wppaphoto-allphoto-tr" style="display:none;" >'.
					'<th><label for="wppaphoto-allphoto" class="wppaphoto-allphoto" >'.__('The Photo to be used', 'wp-photo-album-plus').':</label></th>'.
					'<td>'.
						'<select id="wppaphoto-allphoto" name="photo" class="wppaphoto-allphoto" onchange="wppaPhotoEvaluate()" >' .
							wppa_get_allphotos_selection_body_for_tinymce() .
						'</select>' .
						'<br />' .
						'<small style="" class="wppaphoto-allphoto" >'.
							__('Specify the photo to be used', 'wp-photo-album-plus').'<br />'.
							__('You can select from a maximum of 100 most recently added photos', 'wp-photo-album-plus').'<br />'.
						'</small>'.
					'</td>'.
				'</tr>'.

				// Photo preview
				'
				<tr id="wppaphoto-photo-preview-tr" style="" >'.
					'<th>' .
						__( 'Preview image', 'wp-photo-album-plus').':' .
					'</th>'.
					'<td id="wppaphoto-photo-preview" style="text-align:center;" >' .
					'</td>' .
				'</tr>';

				// Upload new photo dialog
				if ( count( $albums ) > 0  ) {
					$result .=
					'
					<tr id="wppa-tinymce-upload-tr" >' .
						'<th>' .
							'<a' .
								' style="cursor:pointer;"' .
								' onclick="jQuery(\'#upload-td\').show();jQuery( \'#wppa-user-upload\' ).click();"' .
								' >' .
							__( 'Upload new photo', 'wp-photo-album-plus' ) . ':' .
							'</a>' .
						'</th>'.
						'<td id="upload-td" style="display:none;" >' .

							// Open form
							'<form' .
								' id="wppa-uplform"' .
								' action="' . WPPA_URL . '/wppa-ajax-front.php?action=wppa&amp;wppa-action=do-fe-upload&amp;fromtinymce=1"' .
								' method="post"' .
								' enctype="multipart/form-data"' .
								' >' .
								wppa_nonce_field( 'wppa-check' , 'wppa-nonce', false, false ) .

								// Single album
								( ( count( $albums ) == 1 ) ?

									'<input' .
										' type="hidden"' .
										' id="wppa-upload-album"' .
										' name="wppa-upload-album"' .
										' value="' . $albums[0]['id'] . '"' .
									' />' .

									__( 'Upload to album', 'wp-photo-album-plus' ) . ': <b>' . wppa_get_album_name( $albums[0]['id'] ) . '</b>' :


									// Multiple albums
									__( 'Upload to album', 'wp-photo-album-plus' ) . ':' .
									wppa_album_select_a( array( 	'tagid' 			=> 'wppa-upload-album',
																	'tagname' 			=> 'wppa-upload-album',
																	'tagopen' 			=> '<select' .
																								' id="wppa-upload-album"' .
																								' name="wppa-upload-album"' .
																								' style="max-width:300px;"' .
																								' >' ,
																	'addpleaseselect' 	=> true,
																	'checkupload' 		=> true,
																	'checkowner' 		=> true,

																				) ) ) .

								// The (hidden) functional button
								'
								<input' .
									' type="file"' .
									' style="' .
										'display:none;' .
										'"' .
									' id="wppa-user-upload"' .
									' name="wppa-user-upload"' .
									' onchange="jQuery( \'#wppa-user-upload-submit\' ).css( \'display\', \'block\' );wppaDisplaySelectedFile(\'wppa-user-upload\', \'wppa-user-upload-submit\');"' .
								' />' .

								// The upload submit button
								'
								<input' .
									' type="submit"' .
									' id="wppa-user-upload-submit"' .
									' onclick="if ( document.getElementById( \'wppa-upload-album\' ).value == 0 )' .
											' {alert( \''.esc_js( __( 'Please select an album and try again', 'wp-photo-album-plus' ) ).'\' );return false;}"' .
									' style="display:none;margin: 6px 0;"' .
									' class="wppa-user-upload-submit"' .
									' name="wppa-user-upload-submit"' .
									' value=""' .
								' />' .

								// The progression bar
								'
								<div' .
									' id="progress"' .
									' class="wppa-progress "' .
									' style="clear:both;width:70%;border-color:#777;height:18px;border:1px solid;padding:1px;border-radius:3px;line-height: 18px;text-align: center;"' .
									' >' .
									'<div id="bar" class="wppa-bar" ></div>' .
									'<div id="percent" class="wppa-percent" >0%</div >' .
								'</div>' .
								'<div id="message" class="wppa-message" ></div>' .


							// Form complete
							'</form>' .

							// Upload script
							'<script type="text/javascript" >' .
								'jQuery(function() {

									var options = {
										beforeSend: function() {
											jQuery("#progress").show();
											jQuery("#bar").width("0%");
											jQuery("#message").html("");
											jQuery("#percent").html("");
										},
										uploadProgress: function(event, position, total, percentComplete) {
											jQuery("#bar").width(percentComplete+"%");
											if ( percentComplete < 95 ) {
												jQuery("#percent").html(percentComplete+"%");
											}
											else {
												jQuery("#percent").html(wppaTxtProcessing);
											}
										},
										success: function() {
											jQuery("#bar").width("100%");
											jQuery("#percent").html(wppaTxtDone);
										},
										complete: function(response) {

											var resparr = response.responseText.split( "||" );

											// Non fatal error uploading?
											if ( resparr.length == 1 ) {
												jQuery("#message").html( \'<span style="font-size: 10px;" >\'+resparr[0]+\'</span>\' );
											}
											else {
												jQuery( "#wppaphoto-myphoto" ).html( resparr[2] );
											}
											wppaPhotoEvaluate();

										},
										error: function() {
											jQuery("#message").html( \'<span style="color: red;" >\'+wppaTxtErrUnable+\'</span>\' );
										}
									};
									jQuery("#wppa-uplform").ajaxForm(options);
								});' .
							'</script>' .
						'</td>' .
					'</tr>';
				}

				// Shortcode preview
				$result .=
				'
				<tr>' .
					'<th>' .
						__( 'Shortcode', 'wp-photo-album-plus' ) . ':' .
					'</th>' .
					'<td id="wppaphoto-shortcode-preview-container" >' .
						'<input type="text" id="wppaphoto-shortcode-preview" style="background-color:#ddd; width:500px; height:26px;" value="[photo]" />' .
					'</td>' .
				'</tr>' .

			'</tbody>' .

		'</table>' .

		// Insert shortcode button
		'
		<p class="submit" style="padding:4px;margin:0;" >'.
			'<input type="button" id="wppaphoto-submit" class="button-primary" value="'.__( 'Insert Photo', 'wp-photo-album-plus').'" name="submit" />&nbsp;'.
			'<input type="button" id="wppaphoto-submit-notok" class="button-secundary" value="'.__( 'Insert Photo', 'wp-photo-album-plus').'" onclick="alert(\''.esc_js(__('Please select a photo', 'wp-photo-album-plus')).'\')" />&nbsp;'.
		'</p>' .

		// Initial evaluate
		'<script type="text/javascript" >wppaPhotoEvaluate()</script>' .

	// Close main wrapper
	'
	</div>';

	return $result;
}

// The my photos selection box body
function wppa_get_myphotos_selection_body_for_tinymce( $selected = 0 ) {
global $wpdb;

	// Init
	$result = '';

	// Prepare photoinfo
	$my_photos = $wpdb->get_results( 	"SELECT `id`, `name`, `album`, `ext` " .
										"FROM `" . WPPA_PHOTOS . "` " .
										"WHERE `owner` = '" . wppa_get_user() . "' " .
										"ORDER BY `timestamp` DESC LIMIT 100",
										ARRAY_A );

	if ( $my_photos ) {

		// Please select
		$result .= 	'<option' .
						' class="wppa-photo-select-item-first"' .
						' value=""' .
						' disabled="disabled"' .
						( $selected ? '' : ' selected="selected"' ) .
						' style="color:#700"' .
						' >' .
						'-- ' .	__( 'Please select a photo', 'wp-photo-album-plus' ) . ' --' .
					'</option>';

		// Most recent 100 photos of this owner
		foreach ( $my_photos as $photo ) {

			$name = stripslashes(__($photo['name']));
			if ( strlen($name) > '50') $name = substr($name, '0', '50').'...';

			if ( get_option( 'wppa_file_system' ) == 'flat' ) {
				$val = $photo['id'] . '.' . $photo['ext'];
			}
			else {
				$val = wppa_expand_id( $photo['id'] ) . '.' . $photo['ext'];
			}

			if ( wppa_has_audio( $photo['id'] ) && is_file( WPPA_UPLOAD_PATH . '/' . wppa_fix_poster_ext( $val, $photo['id'] ) ) ) {
				$value = wppa_fix_poster_ext( $val, $photo['id'] );
			}
			else {
				$value = $val;
			}

			$result .= 	'<option' .
							' class="wppa-photo-select-item"' .
							' value="' . $value . '"' .
							' >' .
							$name .
							' (' . wppa_get_album_name( $photo['album'] ) . ')' .
						'</option>';

		}
	}
	else {
		$result .= 	'<option value="0" >' .
						__( 'You have no photos yet', 'wp-photo-album-plus' ) .
					'</option>';
	}

	return $result;
}

// The my photos selection box body
function wppa_get_allphotos_selection_body_for_tinymce() {
global $wpdb;

	// Init
	$result = '';

	// Prepare photoinfo
	$all_photos = $wpdb->get_results( 	"SELECT `id`, `name`, `album`, `ext` " .
										"FROM `" . WPPA_PHOTOS . "` " .
										"ORDER BY `timestamp` DESC LIMIT 100",
										ARRAY_A );

	if ( $all_photos ) {

		// Please select
		$result .= 	'<option' .
						' class="wppa-photo-select-item-first"' .
						' value=""' .
						' disabled="disabled"' .
						' selected="selected"' .
						' style="color:#700"' .
						' >' .
						'-- ' . __( 'Please select a photo', 'wp-photo-album-plus' ) . ' --' .
					'</option>';

		// Most recent 100 photos of all photos
		foreach ( $all_photos as $photo ) {

			$name = stripslashes(__($photo['name']));
			if ( strlen($name) > '50') $name = substr($name, '0', '50').'...';
			if ( get_option( 'wppa_file_system' ) == 'flat' ) {
				$val = $photo['id'] . '.' . $photo['ext'];
			}
			else {
				$val = wppa_expand_id( $photo['id'] ) . '.' . $photo['ext'];
			}

			if ( wppa_has_audio( $photo['id'] ) && is_file( WPPA_UPLOAD_PATH . '/' . wppa_fix_poster_ext( $val, $photo['id'] ) ) ) {
				$value = wppa_fix_poster_ext( $val, $photo['id'] );
			}
			else {
				$value = $val;
			}

			$result .= 	'<option' .
							' class="wppa-photo-select-item"' .
							' value="' . $value . '"' .
							' >' .
							$name .
							' (' . wppa_get_album_name( $photo['album'] ) . ')' .
						'</option>';

		}
	}
	else {
		$result .= 	'<option value="0" >' .
						__( 'There are no photos yet', 'wp-photo-album-plus' ) .
					'</option>';
	}

	return $result;
}