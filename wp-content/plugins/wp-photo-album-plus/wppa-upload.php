<?php
/* wppa-upload.php
* Package: wp-photo-album-plus
*
* Contains all the upload pages and functions
* Version 6.8.00
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

// upload images admin page
function _wppa_page_upload() {
global $target;
global $wppa_revno;
global $upload_album;

	// Maybe it helps...
	set_time_limit( 0 );

    // sanitize system
	$user = wppa_get_user();
	wppa_sanitize_files();

	// Sanitize album input
	if ( isset( $_POST['wppa-album'] ) ) {
		$upload_album = strval( intval( $_POST['wppa-album'] ) );
	}
	else {
		$upload_album = null;
	}

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

	// If from album admin set the last album
	if ( isset( $_REQUEST['wppa-set-album'] ) ) {
		wppa_set_last_album( strval( intval( $_REQUEST['wppa-set-album'] ) ) );
	}

	// Do the upload if requested
	// From BOX A
	if ( isset( $_POST['wppa-upload-multiple'] ) ) {
		check_admin_referer( '$wppa_nonce', WPPA_NONCE );
		wppa_upload_multiple();
		if ( isset( $_POST['wppa-go-edit-multiple'] ) ) {
			if ( current_user_can( 'wppa_admin' ) ) {
				wppa_ok_message( __( 'Connecting to edit album...' , 'wp-photo-album-plus' ) ); ?>
				<script type="text/javascript">
					document.location = '<?php echo( wppa_dbg_url( get_admin_url().'admin.php?page=wppa_admin_menu&tab=edit&edit_id=' . $upload_album . '&wppa_nonce=' . wp_create_nonce( 'wppa_nonce', 'wppa_nonce' ), 'js' ) ) ?>';
				</script>
			<?php }
			elseif ( wppa_opt( 'upload_edit' ) != '-none-' ) {
				wppa_ok_message( __( 'Connecting to edit photos...' , 'wp-photo-album-plus' ) ); ?>
				<script type="text/javascript">document.location = '<?php echo( wppa_dbg_url( get_admin_url().'admin.php?page=wppa_edit_photo', 'js' ) ) ?>';</script>
			<?php }
		}
	}
	// From BOX B
	if ( isset( $_POST['wppa-upload'] ) ) {
		check_admin_referer( '$wppa_nonce', WPPA_NONCE );
		wppa_upload_photos();
		if ( isset( $_POST['wppa-go-edit-single'] ) ) {
			if ( current_user_can( 'wppa_admin' ) ) {
				wppa_ok_message( __( 'Connecting to edit album...' , 'wp-photo-album-plus' ) ); ?>
				<script type="text/javascript">
					document.location = '<?php echo( wppa_dbg_url( get_admin_url().'admin.php?page=wppa_admin_menu&tab=edit&edit_id=' . $upload_album . '&wppa_nonce=' . wp_create_nonce( 'wppa_nonce', 'wppa_nonce' ), 'js' ) ) ?>';
				</script>
			<?php }
			elseif ( wppa_opt( 'upload_edit' ) != '-none-' ) {
				wppa_ok_message( __( 'Connecting to edit photos...' , 'wp-photo-album-plus' ) ); ?>
				<script type="text/javascript">document.location = '<?php echo( wppa_dbg_url( get_admin_url().'admin.php?page=wppa_edit_photo', 'js' ) ) ?>';</script>
			<?php }
		}
	}
	// From BOX C
	if ( isset( $_POST['wppa-upload-zip'] ) ) {
		check_admin_referer( '$wppa_nonce', WPPA_NONCE );
		$err = wppa_upload_zip();
		if ( isset( $_POST['wppa-go-import'] ) && $err == '0' ) {
			wppa_ok_message( __( 'Connecting to your depot...' , 'wp-photo-album-plus' ) );
			update_option( 'wppa_import_source_'.$user, WPPA_DEPOT_PATH ); ?>
			<script type="text/javascript">document.location = '<?php echo( wppa_dbg_url( get_admin_url().'admin.php?page=wppa_import_photos&zip='.$target, 'js' ) ) ?>';</script>
		<?php }
	}

	// sanitize system again
	wppa_sanitize_files();


	// Open the form
	echo
	'<div class="wrap">' .
		'<h2>' . __( 'Upload Photos', 'wp-photo-album-plus' ) . '</h2>';

		// Get some req'd data
		$max_files = ini_get( 'max_file_uploads' );
		$max_files_txt = $max_files;
		if ( $max_files < '1' ) {
			$max_files_txt = __( 'unknown' , 'wp-photo-album-plus' );
			$max_files = '15';
		}
		$max_size = ini_get( 'upload_max_filesize' );
/* debug */
// $max_size = '2G';
/**/
		$max_size_mbytes = substr( $max_size, 0, strlen( $max_size ) - 1 );
		if ( substr( $max_size, -1 ) == 'G' ) { // May upload gigabytes!!
			$max_size_mbytes *= 1024;
		}
		$max_time = ini_get( 'max_input_time' );
		if ( $max_time < '1' ) $max_time = __( 'unknown', 'wp-photo-album-plus' );

		// check if albums exist before allowing upload
		if ( ! wppa_has_albums() ) {

			// User can create
			if ( current_user_can( 'wppa_admin' ) ) {
				$url = wppa_dbg_url( get_admin_url().'admin.php?page=wppa_admin_menu' );
				echo
				'<p>' .
					__( 'No albums exist. You must' , 'wp-photo-album-plus') .
					' <a href="' . $url . '" >' .
						__( 'create one' , 'wp-photo-album-plus') .
					'</a> ' .
					__( 'before you can upload your photos.', 'wp-photo-album-plus' ) .
				'</p>' . '</div>';
				return;
			}

			// User can not create
			else {
				echo
				'<p>' .
					__( 'There are no albums where you are allowed to upload photos to.', 'wp-photo-album-plus' ) .
					'<br />' .
					__( 'Ask your administrator to create at least one album that is accessible for you to upload to, or ask him to give you album admin rights.', 'wp-photo-album-plus' ) .
				'</p>';
				return;
			}
		}

		// Upload One only configured and not administrator or super user?
		if ( wppa_switch( 'upload_one_only' ) && ! wppa_user_is( 'administrator' ) ) {

			// One only
			echo
			'<div style="border:1px solid #ccc; padding:10px; margin-bottom:10px; width: 600px;">' .
				'<h3 style="margin-top:0px;">' . __( 'Upload a single photo' , 'wp-photo-album-plus' ) . '</h3>' .
				'<form enctype="multipart/form-data" action="' . wppa_dbg_url( get_admin_url() . 'admin.php?page=wppa_upload_photos' ) . '" method="post">' .
					wp_nonce_field( '$wppa_nonce', WPPA_NONCE ) .
					'<input id="my_files" type="file" name="my_files[]" />' .
					'<p>' .
						'<label for="wppa-album">' .
							__( 'Album:' , 'wp-photo-album-plus') .
						'</label>' .
							wppa_album_select_a( array( 'path' => wppa_switch( 'hier_albsel' ),
														'addpleaseselect' 	=> true,
														'checkowner' 		=> true,
														'checkupload' 		=> true,
														'sort' 				=> true,
														'tagopen'			=> '<select name="wppa-album" id="wppa-album-s" style="max-width:100%;" >',
														'tagid' 			=> 'wppa-album-s',
														'tagname' 			=> 'wppa-album',
														'tagstyle' 			=> 'max-width:100%;',
														) ) .
					'</p>';
					if ( wppa_switch( 'watermark_on' ) && ( wppa_switch( 'watermark_user' ) || current_user_can( 'wppa_settings' ) ) ) {
						echo
						'<p>' .
							__( 'Apply watermark file:' , 'wp-photo-album-plus' ) .
							'<select name="wppa-watermark-file" id="wppa-watermark-file" >' .
								wppa_watermark_file_select( 'user' ) .
							'</select>' .
							__( 'Position:' , 'wp-photo-album-plus' ) .
							'<select name="wppa-watermark-pos" id="wppa-watermark-pos" >' .
								wppa_watermark_pos_select( 'user' ) .
							'</select>' .
						'</p>';
					}
					echo
					'<input' .
						' type="submit" class="button-primary"' .
						' name="wppa-upload-multiple"' .
						' value="' . __( 'Upload Photo' , 'wp-photo-album-plus') . '"' .
						' onclick="if ( document.getElementById( \'wppa-album-s\' ).value == 0 ) { alert( \'' . __( 'Please select an album' , 'wp-photo-album-plus' ) . '\' ); return false; }"' .
						' />' .
					'<input type="checkbox"' .
						' id="wppa-go-edit-multiple"' .
						' name="wppa-go-edit-multiple"' .
						' style="display:none"' .
						' checked="checked"' .
						' />' .
				'</form>' .
			'</div>';
		}

		// Upload multiple allowed
		else {

			// The information box
			echo
			'<div' .
				' style="' .
					'border:1px solid #ccc;' .
					'padding:10px;' .
					'margin-bottom:10px;' .
					'width:600px;' .
					'background-color:#fffbcc;' .
					'border-color:#e6db55;' .
					'"' .
				' >' .
				sprintf( __( '<b>Notice:</b> your server allows you to upload <b>%s</b> files of maximum total <b>%s</b> bytes and allows <b>%s</b> seconds to complete.' , 'wp-photo-album-plus' ), $max_files_txt, $max_size, $max_time ) .
				' ' .
				__( 'If your request exceeds these limitations, it will fail, probably without an errormessage.' , 'wp-photo-album-plus' ) .
				' ' .
				__( 'Additionally your hosting provider may have set other limitations on uploading files.' , 'wp-photo-album-plus' ) .
				'<br />' .
				wppa_check_memory_limit() .
			'</div>';

			// Box A: Upload Multple photos
			echo
			'<div' .
				' style="' .
					'border:1px solid #ccc;' .
					'padding:10px;' .
					'margin-bottom:10px;' .
					'width: 600px;' .
					'"' .
				' >' .
				'<h3 style="margin-top:0px;">' .
					__( 'Box A:' , 'wp-photo-album-plus' ) . ' ' . __( 'Multiple Photos in one selection' , 'wp-photo-album-plus' ) .
				'</h3>' .
				sprintf( __( 'You can select up to %s photos in one selection and upload them.' , 'wp-photo-album-plus' ), $max_files_txt ) .
				'<br />' .
				'<small style="color:blue" >' .
					__( 'You need a modern browser that supports HTML-5 to select multiple files' , 'wp-photo-album-plus') .
				'</small>' .
				'<form' .
					' enctype="multipart/form-data"' .
					' action="' . wppa_dbg_url( get_admin_url() . 'admin.php?page=wppa_upload_photos' ) . '"' .
					' method="post" >' .
					wp_nonce_field( '$wppa_nonce', WPPA_NONCE, true, false ) .
					'<input' .
						' id="my_files"' .
						' type="file"' .
						' multiple="multiple"' .
						' name="my_files[]"' .
						' onchange="showit()"' .
					' />' .
					'<div id="files_list2" >' .
						'<h3>' .
							__( 'Selected Files:' , 'wp-photo-album-plus' ) .
						'</h3>' .
					'</div>' .
					'<script type="text/javascript">' .
						'function showit() {' .
							'var maxsize = parseInt( \'' . $max_size_mbytes . '\' ) * 1024 * 1024;' .
							'var maxcount = parseInt( \'' . $max_files_txt . '\' );' .
							'var totsize = 0;' .
							'var files = document.getElementById( \'my_files\' ).files;' .
							'var tekst = "<h3>' . __( 'Selected Files:' , 'wp-photo-album-plus' ) . '</h3>";' .
							'tekst += "<table><thead><tr>";' .
								'tekst += "<td>' . __( 'Name' , 'wp-photo-album-plus' ) . '</td>";' .
								'tekst += "<td>' . __( 'Size' , 'wp-photo-album-plus' ) . '</td>";' .
								'tekst += "<td>' . __( 'Type' , 'wp-photo-album-plus' ) . '</td>";' .
								'tekst += "</tr></thead>";' .
								'tekst += "<tbody>";' .
									'tekst += "<tr><td><hr /></td><td><hr /></td><td><hr /></td></tr>";' .
									'for ( var i=0;i<files.length;i++ ) {' .
										'tekst += "<tr>";' .
											'tekst += "<td>" + files[i].name + "</td>";' .
											'tekst += "<td>" + files[i].size + "</td>";' .
											'totsize += files[i].size;' .
											'tekst += "<td>" + files[i].type + "</td>";' .
										'tekst += "</tr>";' .
									'}' .
									'tekst += "<tr><td><hr /></td><td><hr /></td><td><hr /></td></tr>";' .
								'var style1 = "";' .
								'var style2 = "";' .
								'var style3 = "";' .
								'var warn1 = "";' .
								'var warn2 = "";' .
								'var warn3 = "";' .
								'if ( maxcount > 0 && files.length > maxcount ) {' .
									'style1 = "color:red";' .
									'warn1 = "' . __( 'Too many!' , 'wp-photo-album-plus' ) . '";' .
								'}' .
								'if ( maxsize > 0 && totsize > maxsize ) {' .
									'style2 = "color:red";' .
									'warn2 = "' . __( 'Too big!' , 'wp-photo-album-plus') . '";' .
								'}' .
								'if ( warn1 || warn2 ) {' .
									'style3 = "color:green";' .
									'warn3 = "' . __( 'Try again!' , 'wp-photo-album-plus' ) . '";' .
								'}' .
								'tekst += "<tr><td style="+style1+" >' . __( 'Total' , 'wp-photo-album-plus' ) . ': "+files.length+" "+warn1+"</td><td style="+style2+" >"+totsize+" "+warn2+"</td><td style="+style3+" >"+warn3+"</td></tr>";' .
								'tekst += "</tbody>";' .
							'tekst += "</table>";' .
							'jQuery( "#files_list2" ).html( tekst );' .
						'}' .
					'</script>' .
					'<p>' .
						'<label for="wppa-album">' . __( 'Album:' , 'wp-photo-album-plus' ) . '</label>' .
							wppa_album_select_a( array( 'path' => wppa_switch( 'hier_albsel' ),
														'addpleaseselect' => true,
														'checkowner' => true,
														'checkupload' => true,
														'sort' => true,
														'tagopen'			=> '<select name="wppa-album" id="wppa-album-s" style="max-width:100%;" >',
														'tagid' 			=> 'wppa-album-s',
														'tagname' 			=> 'wppa-album',
														'tagstyle' 			=> 'max-width:100%;',
														) ) .
					'</p>';

					// Watermark?
					if ( wppa_switch( 'watermark_on' ) && ( wppa_switch( 'watermark_user' ) || current_user_can( 'wppa_settings' ) ) ) {
						echo
						'<p>' .
							__( 'Apply watermark file:' , 'wp-photo-album-plus' ) .
							'<select name="wppa-watermark-file" id="wppa-watermark-file" >' .
								wppa_watermark_file_select( 'user' ) .
							'</select>' .
							__( 'Position:' , 'wp-photo-album-plus' ) .
							'<select name="wppa-watermark-pos" id="wppa-watermark-pos" >' .
								wppa_watermark_pos_select( 'user' ) .
							'</select>' .
						'</p>';
					}

					// Submit section
					echo
					'<input' .
						' type="submit"' .
						' class="button-primary"' .
						' name="wppa-upload-multiple"' .
						' value="' . __( 'Upload Multiple Photos', 'wp-photo-album-plus' ) . '"' .
						' onclick="if ( document.getElementById( \'wppa-album-s\' ).value == 0 ) { alert( \'' . __( 'Please select an album' , 'wp-photo-album-plus' ) . '\' ); return false; }"' .
					' />' .
					' ';
					if ( current_user_can( 'wppa_admin' ) || wppa_opt( 'upload_edit' ) != 'none' ) {
						echo
						'<input' .
							' type="checkbox"' .
							' id="wppa-go-edit-multiple"' .
							' name="wppa-go-edit-multiple"' .
							' onchange="wppaCookieCheckbox( this, \'wppa-go-edit-multiple\' )"' .
						' />' .
						'<script type="text/javascript" >' .
							'if ( wppa_getCookie( \'wppa-go-edit-multiple\' ) == \'on\' ) document.getElementById( \'wppa-go-edit-multiple\' ).checked = \'checked\';' .
						'</script>';
					}

					if ( current_user_can( 'wppa_admin' ) ) {
						_e( 'After upload: Go to the <b>Edit Album</b> page.', 'wp-photo-album-plus');
					}
					elseif ( wppa_opt( 'upload_edit' ) != 'none' ) {
						_e( 'After upload: Go to the <b>Edit Photos</b> page.', 'wp-photo-album-plus');
					}
				echo
				'</form>' .
			'</div>';
			// End BOX A

			// Box B: Single photos
			echo
			'<div style="border:1px solid #ccc; padding:10px; margin-bottom:10px; width: 600px;" >' .
				'<h3 style="margin-top:0px;" >' .
					__( 'Box B:' , 'wp-photo-album-plus') . ' ' . __( 'Single Photos in multiple selections' , 'wp-photo-album-plus') .
				'</h3>' .
				sprintf( __( 'You can select up to %s photos one by one and upload them at once.' , 'wp-photo-album-plus'), $max_files_txt ) .
				'<form' .
					' enctype="multipart/form-data"' .
					' action="' . wppa_dbg_url( get_admin_url().'admin.php?page=wppa_upload_photos' ) . '"' .
					' method="post" ' .
					' >' .
					wp_nonce_field( '$wppa_nonce', WPPA_NONCE, true, false ) .
					'<input' .
						' id="my_file_element"' .
						' type="file"' .
						' name="file_1"' .
					'/>' .
					'<div id="files_list">' .
						'<h3>' . __( 'Selected Files:' , 'wp-photo-album-plus') . '</h3>' .
					'</div>' .
					'<p>' .
						'<label for="wppa-album">' . __( 'Album:' , 'wp-photo-album-plus') . '</label>' .
							wppa_album_select_a( array( 'path' => wppa_switch( 'hier_albsel' ),
														'addpleaseselect' 	=> true,
														'checkowner' 		=> true,
														'checkupload' 		=> true,
														'sort' 				=> true,
														'tagopen'			=> '<select name="wppa-album" id="wppa-album-m" style="max-width:100%;" >',
														'tagid' 			=> 'wppa-album-m',
														'tagname' 			=> 'wppa-album',
														'tagstyle' 			=> 'max-width:100%;',
														) ) .
					'</p>';

					// Watermark?
					if ( wppa_switch( 'watermark_on' ) && ( wppa_switch( 'watermark_user' ) || current_user_can( 'wppa_settings' ) ) ) {
						echo
						'<p>' .
							__( 'Apply watermark file:' , 'wp-photo-album-plus' ) .
							'<select name="wppa-watermark-file" id="wppa-watermark-file" >' .
								wppa_watermark_file_select( 'user' ) .
							'</select>' .
							__( 'Position:' , 'wp-photo-album-plus' ) .
							'<select name="wppa-watermark-pos" id="wppa-watermark-pos" >' .
								wppa_watermark_pos_select( 'user' ) .
							'</select>' .
						'</p>';
					}

					// Submit section
					echo
					'<input' .
						' type="submit"' .
						' class="button-primary"' .
						' name="wppa-upload"' .
						' value="' . __( 'Upload Single Photos' , 'wp-photo-album-plus') . '"' .
						' onclick="if ( document.getElementById( \'wppa-album-m\' ).value == 0 ) { alert( \'' . __( 'Please select an album' , 'wp-photo-album-plus' ) . '\' ); return false; }"' .
					' />' .
					' ';
					if ( current_user_can( 'wppa_admin' ) || wppa_opt( 'upload_edit' ) != 'none' ) {
						echo
						'<input' .
							' type="checkbox"' .
							' id="wppa-go-edit-single"' .
							' name="wppa-go-edit-single"' .
							' onchange="wppaCookieCheckbox( this, \'wppa-go-edit-single\' )" />' .
						'<script type="text/javascript" >' .
							'if ( wppa_getCookie( \'wppa-go-edit-single\' ) == \'on\' ) document.getElementById( \'wppa-go-edit-single\' ).checked = \'checked\';' .
						'</script>';
					}

					if ( current_user_can( 'wppa_admin' ) ) {
						_e( 'After upload: Go to the <b>Edit Album</b> page.' , 'wp-photo-album-plus');
					}
					elseif ( wppa_opt( 'upload_edit' ) != 'none' ) {
						_e( 'After upload: Go to the <b>Edit Photos</b> page.' , 'wp-photo-album-plus');
					}
				echo
				'</form>' .
				'<script type="text/javascript">' .
//				'<!-- Create an instance of the multiSelector class, pass it the output target and the max number of files -->' .
					'var multi_selector = new MultiSelector( document.getElementById( \'files_list\' ), ' . $max_files . ');' .
//				'<!-- Pass in the file element -->' .
					'multi_selector.addElement( document.getElementById( \'my_file_element\' ) );' .
				'</script>' .
			'</div>';
			// End Box B

			// Box C: Single zips, useless if user can not imort, or when php <50207: no unzip
			if ( current_user_can( 'wppa_import' ) ) {
				if ( PHP_VERSION_ID >= 50207 ) {
					echo
					'<div style="border:1px solid #ccc; padding:10px; width: 600px;" >' .
						'<h3 style="margin-top:0px;" >' .
							__( 'Box C:' , 'wp-photo-album-plus') . ' ' . __( 'Zipped Photos in one selection' , 'wp-photo-album-plus') .
						'</h3>' .
						sprintf( __( 'You can upload one zipfile. It will be placed in your personal wppa-depot: <b>.../%s</b><br/>Once uploaded, use <b>Import Photos</b> to unzip the file and place the photos in any album.' , 'wp-photo-album-plus'), WPPA_DEPOT ) .
						'<form' .
							' enctype="multipart/form-data"' .
							' action="' . wppa_dbg_url( get_admin_url() . 'admin.php?page=wppa_upload_photos' ) . '"' .
							' method="post"' .
							' >' .
							wp_nonce_field( '$wppa_nonce', WPPA_NONCE, true, false ) .
							'<input' .
								' id="my_zipfile_element"' .
								' type="file"' .
								' name="file_zip"' .
							' />' .
							'<br/><br/>' .
							'<input' .
								' type="submit"' .
								' class="button-primary"' .
								' name="wppa-upload-zip"' .
								' value="' . __( 'Upload Zipped Photos' , 'wp-photo-album-plus') . '"' .
							' />' .
							' ' .
							'<input' .
								' type="checkbox"' .
								' id="wppa-go-import"' .
								' name="wppa-go-import"' .
								' onchange="wppaCookieCheckbox( this, \'wppa-go-import\' )"' .
							' />' .
							'<script type="text/javascript" >' .
								'if ( wppa_getCookie( \'wppa-go-import\' ) == \'on\' ) document.getElementById( \'wppa-go-import\' ).checked = \'checked\';' .
							'</script>' .
							__( 'After upload: Go to the <b>Import Photos</b> page.' , 'wp-photo-album-plus') .
						'</form>' .
					'</div>';
				}
				else {
					echo
					'<div style="border:1px solid #ccc; padding:10px; width: 600px;">' .
						'<small>' .
							__( 'Ask your administrator to upgrade php to version 5.2.7 or later. This will enable you to upload zipped photos.' , 'wp-photo-album-plus') .
						'</small>' .
					'</div>';
				}
			}
		}

	echo
	'</div>';
}

// Upload multiple photos
function wppa_upload_multiple() {
global $wpdb;
global $warning_given;
global $upload_album;

	$warning_given = false;
	$uploaded_a_file = false;

	$count = '0';
	foreach ( $_FILES as $file ) {
		if ( is_array( $file['error'] ) ) {
			for ( $i = '0'; $i < count( $file['error'] ); $i++ ) {
				if ( wppa_is_time_up() ) {
					wppa_warning_message( sprintf( __( 'Time out. %s photos uploaded in album nr %s.' , 'wp-photo-album-plus'), $count, $upload_album ) );
					wppa_set_last_album( $upload_album );
					return;
				}
				if ( ! $file['error'][$i] ) {
					wppa_pdf_preprocess( $file, $upload_album, $i );
					$id = wppa_insert_photo( $file['tmp_name'][$i], $upload_album, $file['name'][$i] );
					if ( $id ) {
						$uploaded_a_file = true;
						$count++;
						wppa_pdf_postprocess( $id );
						wppa_backend_upload_mail( $id, $upload_album, $file['name'][$i] );
					}
					else {
						wppa_error_message( __( 'Error inserting photo' , 'wp-photo-album-plus') . ' ' . wppa_sanitize_file_name( basename( $file['name'][$i] ) ) . '.' );
						return;
					}
				}
			}
		}
	}

	if ( $uploaded_a_file ) {
		wppa_update_message( $count.' '.__( 'Photos Uploaded in album nr' , 'wp-photo-album-plus') . ' ' . $upload_album );
		wppa_set_last_album( $upload_album );
    }
}

// Upload single photos
function wppa_upload_photos() {
global $wpdb;
global $warning_given;
global $upload_album;


	$warning_given = false;
	$uploaded_a_file = false;

	$count = '0';
	foreach ( $_FILES as $file ) {
		if ( $file['tmp_name'] != '' ) {
			wppa_pdf_preprocess( $file, $upload_album );
			$id = wppa_insert_photo( $file['tmp_name'], $upload_album, $file['name'] );
			if ( $id ) {
				$uploaded_a_file = true;
				$count++;
				wppa_pdf_postprocess( $id );
				wppa_backend_upload_mail( $id, $upload_album, $file['name'] );
			}
			else {
				wppa_error_message( __( 'Error inserting photo' , 'wp-photo-album-plus') . ' ' . wppa_sanitize_file_name( basename( $file['name'] ) ) . '.' );
				return;
			}
		}
	}

	if ( $uploaded_a_file ) {
		wppa_update_message( $count.' '.__( 'Photos Uploaded in album nr' , 'wp-photo-album-plus') . ' ' . $upload_album );
		wppa_set_last_album( $upload_album );
    }
}

// Send emails after backend upload
function wppa_backend_upload_mail( $id, $alb, $name ) {

	$owner = wppa_get_user();
	if ( $owner == 'admin' ) return;	// Admin does not send mails to himself

	if ( wppa_switch( 'upload_backend_notify' ) ) {
		$to = get_bloginfo( 'admin_email' );
		$subj = sprintf( __( 'New photo uploaded: %s' , 'wp-photo-album-plus'), wppa_sanitize_file_name( $name ) );
		$cont['0'] = sprintf( __( 'User %1$s uploaded photo %2$s into album %3$s' , 'wp-photo-album-plus'), $owner, $id, wppa_get_album_name( $alb ) );
		if ( wppa_switch( 'upload_moderate' ) && !current_user_can( 'wppa_admin' ) ) {
			$cont['1'] = __( 'This upload requires moderation' , 'wp-photo-album-plus');
			$cont['2'] = '<a href="'.get_admin_url().'admin.php?page=wppa_admin_menu&tab=pmod&photo='.$id.'" >'.__( 'Moderate manage photo' , 'wp-photo-album-plus').'</a>';
		}
		else {
			$cont['1'] = __( 'Details:' , 'wp-photo-album-plus');
			$cont['1'] .= ' <a href="'.get_admin_url().'admin.php?page=wppa_admin_menu&tab=pmod&photo='.$id.'" >'.__( 'Manage photo' , 'wp-photo-album-plus').'</a>';
		}
		wppa_send_mail( $to, $subj, $cont, $id );
	}
}

// Upload a zipfile
function wppa_upload_zip() {
global $target;

	$file 	= $_FILES['file_zip'];
	$name 	= wppa_sanitize_file_name( $file['name'] );
	$type 	= $file['type'];
	$error 	= $file['error'];
	$size 	= $file['size'];
	$temp 	= $file['tmp_name'];
	$target = WPPA_DEPOT_PATH.'/'.$name;

	copy( $temp, $target );

	if ( $error == '0' ) wppa_ok_message( __( 'Zipfile' , 'wp-photo-album-plus').' '.$name.' '.__( 'sucessfully uploaded.' , 'wp-photo-album-plus') );
	else wppa_error_message( __( 'Error' , 'wp-photo-album-plus').' '.$error.' '.__( 'during upload.' , 'wp-photo-album-plus') );

	return $error;
}
