<?php
/* wppa-album-admin-autosave.php
* Package: wp-photo-album-plus
*
* create, edit and delete albums
* Version 6.8.00
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

function _wppa_admin() {
	global $wpdb;
	global $q_config;
	global $wppa_revno;

	if ( get_option('wppa_revision') != $wppa_revno ) wppa_check_database(true);

	echo '
<script type="text/javascript">
	/* <![CDATA[ */
	wppaAjaxUrl = "'.admin_url('admin-ajax.php').'";
	wppaUploadToThisAlbum = "'.__('Upload to this album', 'wp-photo-album-plus').'";
	wppaImageDirectory = "'.wppa_get_imgdir().'";
	/* ]]> */
</script>
';

	// Delete trashed comments
	$query = "DELETE FROM " . WPPA_COMMENTS . " WHERE status='trash'";
	$wpdb->query($query);

	$sel = 'selected="selected"';

	// warn if the uploads directory is no writable
	if (!is_writable(WPPA_UPLOAD_PATH)) {
		wppa_error_message(__('Warning:', 'wp-photo-album-plus') . sprintf(__('The uploads directory does not exist or is not writable by the server. Please make sure that %s is writeable by the server.', 'wp-photo-album-plus'), WPPA_UPLOAD_PATH));
	}

	// Fix orphan albums and deleted target pages
	$albs = $wpdb->get_results("SELECT * FROM `" . WPPA_ALBUMS . "`", ARRAY_A);

	// Now we have them, put them in cache
	wppa_cache_album( 'add', $albs );

	if ( $albs ) {
		foreach ($albs as $alb) {
			if ( $alb['a_parent'] > '0' && wppa_get_parentalbumid($alb['a_parent']) <= '-9' ) {	// Parent died?
				$wpdb->query("UPDATE `".WPPA_ALBUMS."` SET `a_parent` = '-1' WHERE `id` = '".$alb['id']."'");
			}
			if ( $alb['cover_linkpage'] > '0' ) {
				$iret = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `".$wpdb->posts."` WHERE `ID` = %s AND `post_type` = 'page' AND `post_status` = 'publish'", $alb['cover_linkpage']));
				if ( ! $iret ) {	// Page gone?
					$wpdb->query("UPDATE `".WPPA_ALBUMS."` SET `cover_linkpage` = '0' WHERE `id` = '".$alb['id']."'");
				}
			}
		}
	}

	if (isset($_REQUEST['tab'])) {
		// album edit page
		if ($_REQUEST['tab'] == 'edit'){
			if ( isset($_REQUEST['edit_id']) ) {
				$ei = $_REQUEST['edit_id'];
				if ( $ei != 'new' && $ei != 'search' && $ei != 'trash' && $ei != 'single' && ! is_numeric($ei) ) {
					wp_die('Security check failure 1');
				}
				if ( ! wp_verify_nonce( $_REQUEST['wppa_nonce'], 'wppa_nonce' ) ) {
					wp_die('Security check failure 2');
				}
			}

			if ( $_REQUEST['edit_id'] == 'single' ) {
				?>
				<div class="wrap">
					<h2><?php _e( 'Edit Single Photo', 'wp-photo-album-plus' );
						echo ' - <small><i>'.__('Edit photo information', 'wp-photo-album-plus').'</i></small>';
						?>
					</h2>
					<?php
					wppa_album_photos($ei);
					?>
				</div>
				<?php

				return;
			}

			if ( $_REQUEST['edit_id'] == 'search' ) {

				$back_url = get_admin_url().'admin.php?page=wppa_admin_menu';
					if ( isset ( $_REQUEST['wppa-searchstring'] ) ) {
						$back_url .= '&wppa-searchstring='.wppa_sanitize_searchstring( $_REQUEST['wppa-searchstring'] );
					}
					$back_url .= '#wppa-edit-search-tag';
?>
<a name="manage-photos" id="manage-photos" ></a>
				<h2><?php _e('Manage Photos', 'wp-photo-album-plus');
					if ( isset($_REQUEST['bulk']) ) echo ' - <small><i>'.__('Copy / move / delete / edit name / edit description / change status', 'wp-photo-album-plus').'</i></small>';
					elseif ( isset($_REQUEST['quick']) ) echo ' - <small><i>'.__('Edit photo information except copy and move', 'wp-photo-album-plus').'</i></small>';
					else echo ' - <small><i>'.__('Edit photo information', 'wp-photo-album-plus').'</i></small>';
				?></h2>

<a href="<?php echo $back_url ?>"><?php _e('Back to album table', 'wp-photo-album-plus') ?></a><br /><br />

				<?php
					if ( isset($_REQUEST['bulk']) ) wppa_album_photos_bulk($ei);
					else wppa_album_photos($ei);
				?>
				<a href="#manage-photos">
					<div style="position:fixed;right:30px;bottom:30px;background-color:lightblue;" >&nbsp;<?php _e('Top of page', 'wp-photo-album-plus') ?>&nbsp;</div>
				</a>
				<br /><a href="<?php echo $back_url ?>"><?php _e('Back to album table', 'wp-photo-album-plus') ?></a>
<?php
				return;
			}

			if ($_REQUEST['edit_id'] == 'trash' ) {
				?>
				<div class="wrap">
					<h2><?php _e('Manage Trashed Photos', 'wp-photo-album-plus');
						echo ' - <small><i>'.__('Edit photo information', 'wp-photo-album-plus').'</i></small>';
						?>
					</h2>
					<?php
					wppa_album_photos($ei);
					?>
				</div>
				<?php

				return;
			}


			if ($_REQUEST['edit_id'] == 'new') {
				if ( ! wppa_can_create_album() ) wp_die('No rights to create an album');
				$id = wppa_nextkey(WPPA_ALBUMS);
				if (isset($_REQUEST['parent_id'])) {
					$parent = $_REQUEST['parent_id'];
					if ( ! is_numeric($parent) ) {
						wp_die('Security check failure 3');
					}
					$name = wppa_get_album_name($parent).'-#'.$id;
					if ( ! current_user_can('administrator') ) {	// someone creating an album for someone else?
						$parentowner = $wpdb->get_var($wpdb->prepare("SELECT `owner` FROM `".WPPA_ALBUMS."` WHERE `id` = %s", $parent));
						if ( $parentowner !== wppa_get_user() ) wp_die('You are not allowed to create an album for someone else');
					}
				}
				else {
					$parent = wppa_opt( 'default_parent' );
					if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `" . WPPA_ALBUMS . "` WHERE `id` = %s", $parent ) ) ) { // Deafault parent vanished
						wppa_update_option( 'wppa_default_parent', '0' );
						$parent = '0';
					}
					$name = __('New Album', 'wp-photo-album-plus');
					if ( ! wppa_can_create_top_album() ) wp_die('No rights to create a top-level album');
				}
				$id = wppa_create_album_entry( array( 'id' => $id, 'name' => $name, 'a_parent' => $parent ) );
				if ( ! $id ) {
					wppa_error_message( __('Could not create album.', 'wp-photo-album-plus') );
					wp_die('Sorry, cannot continue');
				}
				else {
					$edit_id = $id;
					wppa_set_last_album($edit_id);
					wppa_invalidate_treecounts($edit_id);
					wppa_index_add('album', $id);
					wppa_update_message(__('Album #', 'wp-photo-album-plus') . ' ' . $edit_id . ' ' . __('Added.', 'wp-photo-album-plus'));
					wppa_create_pl_htaccess();
				}
			}
			else {
				$edit_id = $_REQUEST['edit_id'];
			}

			$album_owner = $wpdb->get_var($wpdb->prepare("SELECT `owner` FROM ".WPPA_ALBUMS." WHERE `id` = %s", $edit_id));
			if ( ( $album_owner == '--- public ---' && ! current_user_can('wppa_admin') ) || ! wppa_have_access($edit_id) ) {
				wp_die('You do not have the rights to edit this album');
			}

			// Apply new desc
			if ( isset($_REQUEST['applynewdesc']) ) {
				if ( ! wp_verify_nonce($_REQUEST['wppa_nonce'], 'wppa_nonce') ) wp_die('You do not have the rights to do this');
				$iret = $wpdb->query($wpdb->prepare("UPDATE `".WPPA_PHOTOS."` SET `description` = %s WHERE `album` = %s", wppa_opt( 'newphoto_description' ), $edit_id));
				wppa_ok_message($iret.' descriptions updated.');
			}

			// Remake album
			if ( isset($_REQUEST['remakealbum']) ) {
				if ( ! wp_verify_nonce($_REQUEST['wppa_nonce'], 'wppa_nonce') ) wp_die('You do not have the rights to do this');
				if ( get_option('wppa_remake_start_album_'.$edit_id) ) {	// Continue after time up
					wppa_ok_message('Continuing remake, please wait');
				}
				else {
					update_option('wppa_remake_start_album_'.$edit_id, time());
					wppa_ok_message('Remaking photofiles, please wait');
				}
				$iret = wppa_remake_files($edit_id);
				if ( $iret ) {
					wppa_ok_message('Photo files remade');
					update_option('wppa_remake_start_album_'.$edit_id, '0');
				}
				else {
					wppa_error_message('Remake of photo files did NOT complete');
				}
			}

			// Get the album information
			$albuminfo = $wpdb->get_row($wpdb->prepare('SELECT * FROM `'.WPPA_ALBUMS.'` WHERE `id` = %s', $edit_id), ARRAY_A);

			// We may not use extract(), so we do something like it here manually, hence controlled.
			$id 			= $albuminfo['id'];
			$crypt 			= $albuminfo['crypt'];
			$timestamp 		= $albuminfo['timestamp'];
			$modified 		= $albuminfo['modified'];
			$views 			= $albuminfo['views'];
			$owner 			= $albuminfo['owner'];
			$a_order 		= $albuminfo['a_order'];
			$p_order_by 	= $albuminfo['p_order_by'];
			$a_parent 		= $albuminfo['a_parent'];
			$suba_order_by 	= $albuminfo['suba_order_by'];
			$name 			= stripslashes( $albuminfo['name'] );
			$description 	= stripslashes( $albuminfo['description'] );
			$alt_thumbsize 	= $albuminfo['alt_thumbsize'];
			$cover_type 	= $albuminfo['cover_type'];
			$main_photo 	= $albuminfo['main_photo'];
			$upload_limit 	= $albuminfo['upload_limit'];
			$cats 			= stripslashes( trim( $albuminfo['cats'], ',' ) );
			$default_tags 	= trim( $albuminfo['default_tags'], ',' );
			$cover_linktype = $albuminfo['cover_linktype'];

			$treecounts 	= wppa_get_treecounts_a( $id, true );
			$pviews 		= $treecounts['selfphotoviews'];
			$tpviews 		= $treecounts['treephotoviews'];
			$nsub 			= $treecounts['selfalbums'];

			// Open the photo album admin page
			echo
			'<div class="wrap">';

				// The spinner to indicate busyness
				wppa_admin_spinner();

				// Local js functions placed here as long as there is not yet a possibility to translate texts in js files
		?>
<script>
function wppaTryInheritCats( id ) {

	var query;

	query = '<?php echo esc_js( __( 'Are you sure you want to inherit categories to all (grand)children of this album?', 'wp-photo-album-plus' ) ) ?>';

	if ( confirm( query ) ) {
		wppaAjaxUpdateAlbum( id, 'inherit_cats', Math.random() );
	}
}

function wppaTryAddCats( id ) {

	var query;

	query = '<?php echo esc_js( __( 'Are you sure you want to add the categories to all (grand)children of this album?', 'wp-photo-album-plus' ) ) ?>';

	if ( confirm( query ) ) {
		wppaAjaxUpdateAlbum( id, 'inhadd_cats', Math.random() );
	}
}

function wppaTryApplyDeftags( id ) {

	var query;

	query = '<?php echo esc_js( __( 'Are you sure you want to set the default tags to all photos in this album?', 'wp-photo-album-plus' ) ) ?>';

	if ( confirm( query ) ) {
		wppaAjaxUpdateAlbum( id, 'set_deftags', Math.random(), true );
	}
}

function wppaTryAddDeftags( id ) {

	var query;

	query = '<?php echo esc_js( __( 'Are you sure you want to add the default tags to all photos in this album?', 'wp-photo-album-plus' ) ) ?>';

	if ( confirm( query ) ) {
		wppaAjaxUpdateAlbum( id, 'add_deftags', Math.random(), true );
	}
}

function wppaTryScheduleAll( id ) {

	var query;

	if ( jQuery( '#schedule-box' ).attr( 'checked' ) != 'checked' ) {
		query = '<?php echo esc_js( __( 'Please switch feature on and set date/time to schedule first', 'wp-photo-album-plus' ) ) ?>';
		alert( query );
		return;
	}

	query = '<?php echo esc_js( __( 'Are you sure you want to schedule all photos in this album?', 'wp-photo-album-plus' ) ) ?>';

	if ( confirm( query ) ) {
		wppaAjaxUpdateAlbum( id, 'setallscheduled', Math.random(), true );
	}
}

</script>
		<?php

				// The header
				echo
				'<img src="' . WPPA_URL . '/img/album32.png' . '" alt="Album icon" />' .
				'<h1 style="display:inline;" >' .
					__('Edit Album Information', 'wp-photo-album-plus') .
				'</h1>' .
				'<p class="description">' .
					__('All modifications are instantly updated on the server, except for those that require a button push.', 'wp-photo-album-plus' ) . ' ' .
					__('The <b style="color:#070" >Remark</b> fields keep you informed on the actions taken at the background.', 'wp-photo-album-plus' ) .
				'</p>' .
				'<input' .
					' type="hidden"' .
					' id="album-nonce-' . $id . '"' .
					' value="' . wp_create_nonce( 'wppa_nonce_' . $id ) . '"' .
				' />';

				// The edit albuminfo panel
				echo
				'<div' .
					' id="albumitem-' . $id . '"' .
					' class="wppa-table-wrap"' .
					' style="width:100%;position:relative;"' .
					' >';
{
					// Section 1
					echo
					"\n" . '<!-- Album Section 1 -->' .
					'<table' .
						' class="wppa-table wppa-album-table"' .
						' >' .
						'<tbody>' .
							'<tr>' .
								'<td>';

									// More or less static data
									// Album number
									echo
									__( 'Album number:', 'wp-photo-album-plus' ) . ' ' .
									$id . '. ';

									// Crypt
									echo
									__( 'Crypt:', 'wp-photo-album-plus' ) . ' ' .
									$crypt . '. ';

									// Created
									echo
									__( 'Created:', 'wp-photo-album-plus' ) . ' ' .
									wppa_local_date( '', $timestamp ) . ' ' . __( 'local time' , 'wp-photo-album-plus') . '. ';

									// Modified
									echo
									__( 'Modified:', 'wp-photo-album-plus' ) . ' ';
									if ( $modified > $timestamp ) {
										echo wppa_local_date( '', $modified ) . ' ' . __( 'local time' , 'wp-photo-album-plus' ) . '. ';
									}
									else {
										echo __( 'Not modified', 'wp-photo-album-plus' ) . '. ';
									}

									// Views
									if ( wppa_switch( 'track_viewcounts' ) ) {
										echo
										__( 'Album Views:', 'wp-photo-album-plus' ) . ' ' . $views . ', ';
										echo
										__( 'Photo views:', 'wp-photo-album-plus' ) . ' ' . $pviews . '. ';
										if ( $nsub ) {
											echo
											__( 'Photo views inc sub albums:', 'wp-photo-album-plus' ) . ' ' . $tpviews . '. ';
										}
									}

									// Clicks
									if ( wppa_switch( 'track_clickcounts' ) ) {
										$click_arr = $wpdb->get_col( "SELECT `clicks` FROM `" . WPPA_PHOTOS . "` WHERE `album` = $id" );
										echo
										__( 'Clicks:', 'wp-photo-album-plus' ) . ' ' . array_sum( $click_arr ) . '. ';
									}

									// Newline
									echo '<br />';

									// Owner
									echo
									__( 'Owned by:', 'wp-photo-album-plus' ) . ' ';
									if ( ! wppa_user_is( 'administrator' ) ) {
										if ( $owner == '--- public ---' ) {
											echo __( '--- public ---', 'wp-photo-album-plus' ) . ' ';
										}
										else {
											echo $owner . '. ';
										}
									}
									else {
										$usercount = wppa_get_user_count();
										if ( $usercount > wppa_opt( 'max_users' ) ) {
											echo
											'<input' .
												' type="text"' .
												' value="' . esc_attr( $owner ) . '"' .
												' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'owner\', this )"' .
											' />';
										}
										else {
											echo
											'<select' .
												' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'owner\', this )"' .
												' >';
												wppa_user_select( $owner );
												echo
											'</select>' . ' ';
										}
									}

									// Order # -->
									echo
									__( 'Album order #', 'wp-photo-album-plus' ) . ': ' .
									'<input' .
										' type="text"' .
										' onkeyup="wppaAjaxUpdateAlbum( ' . $id . ', \'a_order\', this )"' .
										' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'a_order\', this )"' .
										' value="' . esc_attr( $a_order ) . '"' .
										' style="width:50px;' .
									'" />' . ' ';
									if ( wppa_opt( 'list_albums_by' ) != '1' && $a_order != '0' ) {
										echo
										'<small class="description" style="color:red" >' .
											__( 'Album order # has only effect if you set the album sort order method to <b>Order #</b> in the Photo Albums -> Settings screen.<br />', 'wp-photo-album-plus' ) .
										'</small>' . ' ';
									}

									// Parent
									echo
									__( 'Parent album:', 'wp-photo-album-plus' ) . ' ';
									if ( wppa_extended_access() ) {
										echo
											wppa_album_select_a( array( 'checkaccess' 		=> true,
																		'exclude' 			=> $id,
																		'selected' 			=> $a_parent,
																		'addselected' 		=> true,
																		'addnone' 			=> true,
																		'addseparate' 		=> true,
																		'disableancestors' 	=> true,
																		'path' 				=> wppa_switch( 'hier_albsel' ),
																		'sort' 				=> true,
																		'tagopen' 			=> '<select' .
																									' id="wppa-parsel"' .
																									' style="max-width:300px;"' .
																									' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'a_parent\', this )"' .
																									' >',
																		'tagid' 			=> 'wppa-parsel',
																		'tagonchange' 		=> 'wppaAjaxUpdateAlbum( ' . $id . ', \'a_parent\', this )',
																		'tagstyle' 			=> 'font-size:13px;height:20px;cursor:pointer;',
																		)
																);
											'</select>';
									}
									else {
										echo
										'<select' .
											' id="wppa-parsel"' .
											' style="max-width:300px;"' .
											' onchange="wppaAjaxUpdateAlbum( '. $id . ', \'a_parent\', this )"' .
											' >' .
											wppa_album_select_a( array( 'checkaccess' => true,
																		'exclude' => $id,
																		'selected' => $a_parent,
																		'addselected' => true,
																		'disableancestors' => true,
																		'path' => wppa_switch( 'hier_albsel' ),
																		'sort' => true,
																		)
																) .
										'</select>';
									}
									echo ' ';

									// P-order-by
									echo
									__( 'Photo order:', 'wp-photo-album-plus' ) . ' ';
									$options = array(	__( '--- default --- See Table IV-C1', 'wp-photo-album-plus' ),
														__( 'Order #', 'wp-photo-album-plus' ),
														__( 'Name', 'wp-photo-album-plus' ),
														__( 'Random', 'wp-photo-album-plus' ),
														__( 'Rating mean value', 'wp-photo-album-plus' ),
														__( 'Number of votes', 'wp-photo-album-plus' ),
														__( 'Timestamp', 'wp-photo-album-plus' ),
														__( 'EXIF Date', 'wp-photo-album-plus' ),
														__( 'Order # desc', 'wp-photo-album-plus' ),
														__( 'Name desc', 'wp-photo-album-plus' ),
														__( 'Rating mean value desc', 'wp-photo-album-plus' ),
														__( 'Number of votes desc', 'wp-photo-album-plus' ),
														__( 'Timestamp desc', 'wp-photo-album-plus' ),
														__( 'EXIF Date desc', 'wp-photo-album-plus' )
														);
									$values = array(	'0',
														'1',
														'2',
														'3',
														'4',
														'6',
														'5',
														'7',
														'-1',
														'-2',
														'-4',
														'-6',
														'-5',
														'-7'
														);
									echo
									'<select' .
										' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'p_order_by\', this )"' .
										' >';
										foreach ( array_keys( $options ) as $key ) {
											$sel = $values[$key] == $p_order_by ? ' selected="selected"' : '';
											echo '<option value="'.$values[$key].'"'.$sel.' >'.$options[$key].'</option>';
										}
									echo
									'</select>' . ' ';

									// Child album order
									echo
									__( 'Sub album sort order:', 'wp-photo-album-plus' ) . ' ' .
									'<select' .
										' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'suba_order_by\', this )"' .
										' >' .
										'<option value="0"' . ( $suba_order_by == '0' ? 'selected="selected"' : '' ) . ' >' . __( '--- default --- See Table IV-D1', 'wp-photo-album-plus' ) . '</option>' .
										'<option value="3"' . ( $suba_order_by == '3' ? 'selected="selected"' : '' ) . ' >' . __( 'Random', 'wp-photo-album-plus' ) . '</option>' .
										'<option value="1"' . ( $suba_order_by == '1' ? 'selected="selected"' : '' ) . ' >' . __( 'Order #', 'wp-photo-album-plus' ) . '</option>' .
										'<option value="-1"' . ( $suba_order_by == '-1' ? 'selected="selected"' : '' ) . ' >' . __( 'Order # reverse', 'wp-photo-album-plus' ) . '</option>' .
										'<option value="2"' . ( $suba_order_by == '2' ? 'selected="selected"' : '' ) . ' >' . __( 'Name', 'wp-photo-album-plus' ) . '</option>' .
										'<option value="-2"' . ( $suba_order_by == '-2' ? 'selected="selected"' : '' ) . ' >' . __( 'Name reverse', 'wp-photo-album-plus' ) . '</option>' .
										'<option value="5"' . ( $suba_order_by == '5' ? 'selected="selected"' : '' ) . ' >' . __( 'Timestamp', 'wp-photo-album-plus' ) . '</option>' .
										'<option value="-5"' . ( $suba_order_by == '-5' ? 'selected="selected"' : '' ) . ' >' . __( 'Timestamp reverse', 'wp-photo-album-plus' ) . '</option>' .
									'</select>' . ' ';

									// Alternative thumbnail size
									if ( ! wppa_switch( 'alt_is_restricted') || current_user_can('administrator') ) {
										echo __( 'Use alt thumbsize:', 'wp-photo-album-plus' ) .
										'<select' .
											' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'alt_thumbsize\', this )"' .
											' >' .
											'<option value="0"' . ( $alt_thumbsize ? '' : ' selected="selected"' ) . ' >' .
												__( 'no', 'wp-photo-album-plus' ) .
											'</option>' .
											'<option value="yes"' . ( $alt_thumbsize ? ' selected="selected"' : '' ) . ' >' .
												__( 'yes', 'wp-photo-album-plus' ) .
											'</option>' .
										'</select>' . ' ';
									}

									// Cover type
									if ( ! wppa_switch( 'covertype_is_restricted' ) || wppa_user_is( 'administrator' ) ) {
										echo
										__( 'Cover Type:', 'wp-photo-album-plus' ) . ' ';
										$sel = ' selected="selected"';
										echo
										'<select' .
											' onchange="wppaAjaxUpdateAlbum( '. $id . ', \'cover_type\', this )"' .
											' >' .
											'<option value=""' . ( $cover_type == '' ? $sel : '' ) . ' >' .
												__( '--- default --- See Table IV-D6', 'wp-photo-album-plus' ) .
											'</option>' .
											'<option value="default"' . ( $cover_type == 'default' ? $sel : '' ) . ' >' .
												__( 'Standard', 'wp-photo-album-plus' ) .
											'</option>' .
											'<option value="longdesc"' . ( $cover_type == 'longdesc' ? $sel : '' ) . ' >' .
												__( 'Long Descriptions', 'wp-photo-album-plus' ) .
											'</option>' .
											'<option value="imagefactory"' . ( $cover_type == 'imagefactory' ? $sel : '' ) . ' >' .
												__( 'Image Factory', 'wp-photo-album-plus' ) .
											'</option>' .
											'<option value="default-mcr"' . ( $cover_type == 'default-mcr' ? $sel : '' ) . ' >' .
												__( 'Standard mcr', 'wp-photo-album-plus' ) .
											'</option>' .
											'<option value="longdesc-mcr"' . ( $cover_type == 'longdesc-mcr' ? $sel : '' ) . ' >' .
												__( 'Long Descriptions mcr', 'wp-photo-album-plus' ) .
											'</option>' .
											'<option value="imagefactory-mcr"' . ( $cover_type == 'imagefactory-mcr' ? $sel : '' ) . ' >' .
												__( 'Image Factory mcr', 'wp-photo-album-plus' ) .
											'</option>' .
										'</select>' . ' ';
									}

									// Cover photo
									echo
									__( 'Cover Photo:', 'wp-photo-album-plus' ) . ' ' .
									wppa_main_photo( $main_photo , $cover_type ) . ' ';

									// Upload limit
									echo
									__( 'Upload limit:', 'wp-photo-album-plus' ) . ' ';
									$lims = explode( '/', $upload_limit );
									if ( ! is_array( $lims ) ) {
										$lims = array( '0', '0' );
									}
									if ( wppa_user_is( 'administrator' ) ) {
										echo
										'<input' .
											' type="text"' .
											' id="upload_limit_count"' .
											' value="' . $lims[0] . '"' .
											' style="width:50px"' .
											' title="' . esc_attr( __( 'Set the upload limit (0 means unlimited).', 'wp-photo-album-plus' ) ) . '"' .
											' onchange="wppaRefreshAfter(); wppaAjaxUpdateAlbum( ' . $id . ', \'upload_limit_count\', this )"' .
										' />';
										$sel = ' selected="selected"';
										echo
										'<select onchange="wppaRefreshAfter(); wppaAjaxUpdateAlbum( ' . $id . ', \'upload_limit_time\', this )" >' .
											'<option value="0"' . ( $lims[1] == '0' ? $sel : '' ) . ' >' . __( 'for ever', 'wp-photo-album-plus' ) . '</option>' .
											'<option value="3600"' . ( $lims[1] == '3600' ? $sel : '' ) . ' >' . __( 'per hour', 'wp-photo-album-plus' ) . '</option>' .
											'<option value="86400"' . ( $lims[1] == '86400' ? $sel : '' ) . ' >' . __( 'per day', 'wp-photo-album-plus' ) . '</option>' .
											'<option value="604800"' . ( $lims[1] == '604800' ? $sel : '' ) . ' >' . __( 'per week', 'wp-photo-album-plus' ) . '</option>' .
											'<option value="2592000"' . ( $lims[1] == '2592000' ? $sel : '' ) . ' >' . __( 'per month', 'wp-photo-album-plus' ) . '</option>' .
											'<option value="31536000"' . ( $lims[1] == '31536000' ? $sel : '' ) . ' >' . __( 'per year', 'wp-photo-album-plus' ) . '</option>' .
										'</select>' . ' ';
									}
									else {
										if ( $lims[0] == '0' ) _e( 'Unlimited', 'wp-photo-album-plus' );
										else {
											echo $lims[0].' ';
											switch ($lims[1]) {
												case '3600': _e( 'per hour', 'wp-photo-album-plus' ); break;
												case '86400': _e( 'per day', 'wp-photo-album-plus' ); break;
												case '604800': _e( 'per week', 'wp-photo-album-plus' ); break;
												case '2592000': _e( 'per month', 'wp-photo-album-plus' ); break;
												case '31536000': _e( 'per year', 'wp-photo-album-plus' ); break;
											}
										}
										echo '. ';
									}

									// Watermark
									if ( wppa_switch( 'watermark_on' ) ) {

										// Newline
										echo '<br />';

										echo
										__( 'Watermark file:', 'wp-photo-album-plus' ) .
										'<select' .
											' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'wmfile\', this )"' .
											' >' .
											wppa_watermark_file_select( 'album', $id ) .
										'</select>' .
										' ' .
										__( 'Watermark pos:', 'wp-photo-album-plus' ) .
										'<select' .
											' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'wmpos\', this )"' .
											' >' .
											wppa_watermark_pos_select( 'album', $id ) .
										'</select>';
									}

									// Status
									echo '<br />' .
									__( 'Remark:', 'wp-photo-album-plus' ) . ' ' .
									'<span' .
										' id="albumstatus-' . $id . '"' .
										' style="font-weight:bold;color:#00AA00;"' .
										' >' .
										sprintf( __( 'Album %s is not modified yet', 'wp-photo-album-plus' ), $id ) .
									'</span>';


									echo
								'</td>' .
							'</tr>' .
						'</tbody>' .
					'</table>';
}
{
					// Section 2
					echo
					"\n" . '<!-- Album Section 2 -->' .
					'<table' .
						' class="wppa-table wppa-album-table"' .
						' >' .
						'<tbody>';

							// Name
							echo
							'<tr>' .
								'<td>' .
									__( 'Name:', 'wp-photo-album-plus' ) .
								'</td>' .
								'<td>' .
									'<input' .
										' type="text"' .
										' style="width:100%;"' .
										' onkeyup="wppaAjaxUpdateAlbum( ' . $id . ', \'name\', this )"' .
										' onchange="wppaAjaxUpdateAlbum( ' . $id .  ', \'name\', this )"' .
										' value="' . esc_attr( $name ) . '"' .
									' />' .
									'<span class="description" >' .
										__( 'Type the name of the album. Do not leave this empty.', 'wp-photo-album-plus' ) .
									'</span>' .
								'</td>' .
								'<td>' .
								'</td>' .
							'</tr>';

							// Description
							echo
							'<tr>' .
								'<td>' .
									__( 'Description:', 'wp-photo-album-plus' ) .
								'</td>';
								if ( wppa_switch( 'use_wp_editor') ) {
									echo
									'<td>';
										wp_editor( 	$description,
													'wppaalbumdesc',
													array( 	'wpautop' 		=> true,
															'media_buttons' => false,
															'textarea_rows' => '6',
															'tinymce' 		=> true
														)
												);
										echo
										'<input' .
											' type="button"' .
											' class="button-secundary"' .
											' value="' . esc_attr( __( 'Update Album description', 'wp-photo-album-plus' ) ) . '"' .
											' onclick="wppaAjaxUpdateAlbum( ' . $id .  ', \'description\', document.getElementById( \'wppaalbumdesc\' ) )"' .
										' />' .
										'<img' .
											' id="wppa-album-spin"' .
											' src="' . wppa_get_imgdir() . 'spinner.' . ( wppa_use_svg() ? 'svg' : 'gif' ) . '"' .
											' alt="Spin"' .
											' style="visibility:hidden"' .
										' />' .
									'</td>';
								}
								else {
									echo
									'<td>' .
										'<textarea' .
											' style="width:100%;height:60px;"' .
											' onkeyup="wppaAjaxUpdateAlbum( ' . $id . ', \'description\', this )"' .
											' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'description\', this )"' .
											' >' .
											$description .
										'</textarea>' .
									'</td>';
								}
								echo
								'<td>' .
								'</td>' .
							'</tr>';

							// Categories
							echo
							'<tr>' .
								'<td>' .
									__( 'Categories:', 'wp-photo-album-plus' ) .
								'</td>' .
								'<td>' .
									'<input' .
										' id="cats"' .
										' type="text"' .
										' style="width:100%;"' .
										' onkeyup="wppaAjaxUpdateAlbum( ' . $id . ', \'cats\', this )"' .
										' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'cats\', this )"' .
										' value="' . esc_attr( $cats ) . '"' .
									' />' .
									'<br />' .
									'<span class="description" >' .
										__( 'Separate categories with commas.', 'wp-photo-album-plus' ) .
									'</span>' .
								'</td>' .
								'<td>' .
									'<select' .
										' onchange="wppaAddCat( this.value, \'cats\' ); wppaAjaxUpdateAlbum( ' . $id . ', \'cats\', document.getElementById( \'cats\' ) )"' .
										' >';
										$catlist = wppa_get_catlist();
										if ( is_array( $catlist ) ) {
											echo '<option value="" >' . __( '- select to add -', 'wp-photo-album-plus' ) . '</option>';
											foreach ( $catlist as $cat ) {
												echo '<option value="' . $cat['cat'] . '" >' . $cat['cat'] . '</option>';
											}
										}
										else {
											echo '<option value="0" >' . __( 'No categories yet', 'wp-photo-album-plus') . '</option>';
										}
									echo
									'</select>' .
								'</td>' .
							'</tr>';

							// Default tags
							echo
							'<tr>' .
								'<td>' .
									__( 'Default photo tags:', 'wp-photo-album-plus' ) .
								'</td>' .
								'<td>' .
									'<input' .
										' type="text"' .
										' id="default_tags"' .
										' value="' . esc_attr( $default_tags ) . '"' .
										' style="width:100%"' .
										' onkeyup="wppaAjaxUpdateAlbum( ' . $id . ', \'default_tags\', this )"' .
										' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'default_tags\', this )"' .
									' />' .
									'<br />' .
									'<span class="description">' .
										__( 'Enter the tags that you want to be assigned to new photos in this album.', 'wp-photo-album-plus' ) .
									'</span>' .
								'</td>' .
								'<td>' .
								'</td>' .
							'</tr>';


							// Custom
							if ( wppa_switch( 'album_custom_fields' ) ) {
								$custom = wppa_get_album_item( $edit_id, 'custom' );
								if ( $custom ) {
									$custom_data = unserialize( $custom );
								}
								else {
									$custom_data = array( '', '', '', '', '', '', '', '', '', '' );
								}
								foreach( array_keys( $custom_data ) as $key ) {
									if ( wppa_opt( 'album_custom_caption_' . $key ) ) {
										echo
										'<tr>' .
											'<td>' .
												apply_filters( 'translate_text', wppa_opt( 'album_custom_caption_' . $key ) ) .
												'<small style="float:right" >' .
													'(w#cc'.$key.')' .
												'</small>:' .
											'</td>' .
											'<td>' .
												'<input' .
													' type="text"' .
													' style="width:100%;"' .
													' id="album_custom_' . $key . '-' . $id . '"' .
													' onkeyup="wppaAjaxUpdateAlbum( ' . $id . ', \'album_custom_' . $key . '\', this );"' .
													' onchange="wppaAjaxUpdateAlbum( ' . $id . ', \'album_custom_' . $key . '\', this );"' .
													' value="' . esc_attr( stripslashes( $custom_data[$key] ) ) . '"' .
												' />' .
											'</td>' .
											'<td>' .
												'<small>' .
													'(w#cd' . $key . ')' .
												'</small>' .
											'</td>' .
										'</tr>';
									}
								}
							}

							// Link type
							echo
							'<tr>' .
								'<td>' .
									__( 'Link type:', 'wp-photo-album-plus' ) .
								'</td>' .
								'<td>';
									$sel = ' selected="selected"';
									$lt = $cover_linktype;
									/* if ( !$linktype ) $linktype = 'content'; /* Default */
									/* if ( $albuminfo['cover_linkpage'] == '-1' ) $linktype = 'none'; /* for backward compatibility */
									echo
									'<select onchange="wppaAjaxUpdateAlbum( '. $id . ', \'cover_linktype\', this )" >' .
										'<option value="content"' . ( $lt == 'content' ? $sel : '' ) . ' >' . __( 'the sub-albums and thumbnails', 'wp-photo-album-plus' ) . '</option>' .
										'<option value="albums"' . ( $lt == 'albums' ? $sel : '' ) . ' >' . __( 'the sub-albums', 'wp-photo-album-plus' ) . '</option>' .
										'<option value="thumbs"' . ( $lt == 'thumbs' ? $sel : '' ) . ' >' . __( 'the thumbnails', 'wp-photo-album-plus' ) . '</option>' .
										'<option value="slide"' . ( $lt == 'slide' ? $sel : '' ) . ' >' . __( 'the album photos as slideshow', 'wp-photo-album-plus' ) . '</option>' .
										'<option value="page"' . ( $lt == 'page' ? $sel : '' ) . ' >' . __( 'the link page with a clean url', 'wp-photo-album-plus' ) . '</option>' .
										'<option value="none"' . ( $lt == 'none' ? $sel : '' ) . ' >' . __( 'no link at all', 'wp-photo-album-plus' ) . '</option>' .
									'</select>' .
									'<br />' .
									'<span class="description">';
										if ( wppa_switch( 'auto_page') ) {
											_e( 'If you select "the link page with a clean url", select an Auto Page of one of the photos in this album.', 'wp-photo-album-plus' );
										}
										else {
											_e( 'If you select "the link page with a clean url", make sure you enter the correct shortcode on the target page.', 'wp-photo-album-plus' );
										}
									echo
									'</span>' .
								'</td>' .
								'<td>' .
								'</td>' .
							'</tr>';

							// Link page
							if ( ! wppa_switch( 'link_is_restricted' ) || wppa_user_is( 'administrator' ) ) {
								echo
								'<tr>' .
									'<td>' .
										__( 'Link to:', 'wp-photo-album-plus' ) .
									'</td>' .
									'<td>';
										$query = "SELECT `ID`, `post_title` FROM `" . $wpdb->posts . "` WHERE `post_type` = 'page' AND `post_status` = 'publish' ORDER BY `post_title` ASC";
										$pages = $wpdb->get_results( $query, ARRAY_A );
										if ( empty( $pages ) ) {
											_e( 'There are no pages (yet) to link to.', 'wp-photo-album-plus' );
										}
										else {
											$linkpage = $albuminfo['cover_linkpage'];
											if ( ! is_numeric( $linkpage ) ) $linkpage = '0';
											echo
											'<select' .
												' onchange="wppaAjaxUpdateAlbum( '. $id . ' , \'cover_linkpage\', this )"' .
												' style="max-width:100%;"' .
												'>' .
												'<option value="0"' . ( $linkpage == '0' ? $sel : '' ) . ' >' . __( '--- the same page or post ---', 'wp-photo-album-plus' ) . '</option>';
												foreach ( $pages as $page ) {
													echo
													'<option value="' . $page['ID'] . '"' . ( $linkpage == $page['ID'] ? $sel : '' ) . ' >' . __( $page['post_title'] ) . '</option>';
												}
											echo
											'</select>' .
											'<br />' .
											'<span class="description" >' .
												__( 'If you want, you can link the title to a WP page instead of the album\'s content. If so, select the page the title links to.', 'wp-photo-album-plus' ) .
											'</span>';
										}
									echo
									'</td>' .
									'<td>' .
									'</td>' .
								'</tr>';
							}

							// Schedule
							echo
							'<tr>' .
								'<td>' .
									__( 'Schedule:', 'wp-photo-album-plus' ) . ' ' .
									'<input' .
										' type="checkbox"' .
										' id="schedule-box"' .
										( $albuminfo['scheduledtm'] ? ' checked="checked"' : '' ) .
										' onchange="wppaChangeScheduleAlbum(' . $id . ', this );"' .
									' />' .
								'</td>' .
								'<td>' .
									'<input type="hidden" value="" id="wppa-dummy" />' .
									'<span class="wppa-datetime-' . $id . '"' . ( $albuminfo['scheduledtm'] ? '' : ' style="display:none;"' ) . ' >' .
										wppa_get_date_time_select_html( 'album', $id, true ) .
									'</span>' .
									'<br />' .
									'<span class="description" >' .
										__( 'If enabled, new photos will have their status set scheduled for publication on the date/time specified here.', 'wp-photo-album-plus' ) .
									'</span>' .
								'</td>' .
								'<td>' .
								'</td>' .
							'</tr>';

						echo
						'</tbody>' .
					'</table>';
}
{
					// Section 3, Actions
					echo
					"\n" . '<!-- Album Section 3 -->' .
					'<table' .
						' class="wppa-table wppa-album-table"' .
						' >' .
						'<tbody>' .
							'<tr>' .
								'<td>';

									// Inherit cats
									echo
									'<input' .
										' type="button"' .
										' title="' . esc_attr( __( 'Apply categories to all (grand)children.', 'wp-photo-album-plus' ) ) . '"' .
										' onclick="wppaTryInheritCats( ' . $id . ' )"' .
										' value="' . esc_attr( __( 'Inherit Cats', 'wp-photo-album-plus' ) ) . '"' .
									' />' .
									'<input' .
										' type="button"' .
										' title="' . esc_attr( __( 'Add categories to all (grand)children.', 'wp-photo-album-plus' ) ) . '"' .
										' onclick="wppaTryAddCats( ' . $id . ' )"' .
										' value="' . esc_attr( __( 'Add Inherit Cats', 'wp-photo-album-plus' ) ) . '"' .
									' />';

									// Apply default tags
									echo
									'<input' .
										' type="button"' .
										' title="' . esc_attr( __( 'Tag all photos in this album with the default tags.', 'wp-photo-album-plus' ) ) . '"' .
										' onclick="wppaTryApplyDeftags( ' . $id . ' )"' .
										' value="' . esc_attr( __( 'Apply default tags', 'wp-photo-album-plus' ) ) . '"' .
									' />' .
									'<input' .
										' type="button"' .
										' title="' . esc_attr( __( 'Add the default tags to all photos in this album.', 'wp-photo-album-plus' ) ) . '"' .
										' onclick="wppaTryAddDeftags( ' . $id . ' )"' .
										' value="' . esc_attr( __( 'Add default tags', 'wp-photo-album-plus' ) ) . '"' .
									' />';

									// Schedule all
									echo
									'<input' .
										' type="button"' .
										' title="' . esc_attr( __( 'Schedule all photos in this album for later publishing.', 'wp-photo-album-plus' ) ) . '"' .
										' onclick="wppaTryScheduleAll( ' . $id . ' )"' .
										' value="' . esc_attr( __( 'Schedule all', 'wp-photo-album-plus' ) ) . '"' .
									' />';

									// Reset Ratings
									if ( wppa_switch( 'rating_on') ) {
										$onc = 'if (confirm(\''.__( 'Are you sure you want to clear the ratings in this album?', 'wp-photo-album-plus' ).'\')) { wppaRefreshAfter(); wppaAjaxUpdateAlbum( ' . $id . ', \'clear_ratings\', 0 ); }';
										echo
										'<input' .
											' type="button"' .
											' onclick="' . $onc . '"' .
											' value="' . esc_attr( __( 'Reset ratings', 'wp-photo-album-plus' ) ) . '"' .
										' />';
									}

									// Apply New photo desc
									if ( wppa_switch( 'apply_newphoto_desc') ) {
										$onc = 'if ( confirm(\'Are you sure you want to set the description of all photos to \n\n'.esc_js(wppa_opt( 'newphoto_description')).'\')) document.location=\''.wppa_ea_url($albuminfo['id'], 'edit').'&applynewdesc\'';
										echo
										'<input' .
											' type="button"' .
											' onclick="' . $onc . '"' .
											' value="' . esc_attr( __( 'Apply new photo desc', 'wp-photo-album-plus' ) ) . '"' .
										' />';
									}

									// Remake all
									if ( wppa_user_is( 'administrator' ) ) {
										$onc = 'if ( confirm(\'Are you sure you want to remake the files for all photos in this album?\')) document.location=\''.wppa_ea_url($albuminfo['id'], 'edit').'&remakealbum\'';
										echo
										'<input' .
											' type="button"' .
											' onclick="' . $onc . '"' .
											' value="' . esc_attr( __( 'Remake all', 'wp-photo-album-plus' ) ) . '"' .
										' />';
									}

									// Create subalbum
									if ( wppa_can_create_album() ) {
										$url = wppa_dbg_url( get_admin_url() . 'admin.php?page=wppa_admin_menu&amp;tab=edit&amp;edit_id=new&amp;parent_id=' . $albuminfo['id'] . '&amp;wppa_nonce=' . wp_create_nonce( 'wppa_nonce' ) );
										if ( wppa_switch( 'confirm_create' ) ) {
											$onc = 'if (confirm(\''.__('Are you sure you want to create a subalbum?', 'wp-photo-album-plus').'\')) document.location=\''.$url.'\';';
										}
										else {
											$onc = 'document.location=\''.$url.'\';';
										}
										echo
										'<input' .
											' type="button"' .
											' onclick="' . $onc . '"' .
											' value="' . esc_attr( __( 'Create child', 'wp-photo-album-plus' ) ) . '"' .
										' />';
									}

									// Create sibling
									if ( $albuminfo['a_parent'] > '0' && wppa_can_create_album() ||
										 $albuminfo['a_parent'] < '1' && wppa_can_create_top_album() ) {
										$url = wppa_dbg_url( get_admin_url() . 'admin.php?page=wppa_admin_menu&amp;tab=edit&amp;edit_id=new&amp;parent_id=' . $albuminfo['a_parent'] . '&amp;wppa_nonce=' . wp_create_nonce( 'wppa_nonce' ) );
										if ( wppa_switch( 'confirm_create' ) ) {
											$onc = 'if (confirm(\''.__('Are you sure you want to create a subalbum?', 'wp-photo-album-plus').'\')) document.location=\''.$url.'\';';
										}
										else {
											$onc = 'document.location=\''.$url.'\';';
										}
										echo
										'<input' .
											' type="button"' .
											' onclick="' . $onc . '"' .
											' value="' . esc_attr( __( 'Create sibling', 'wp-photo-album-plus' ) ) . '"' .
										' />';
									}

									// Edit parent
									if ( $albuminfo['a_parent'] > '0' && wppa_album_exists( $albuminfo['a_parent'] ) && wppa_have_access( $albuminfo['a_parent'] ) ) {
										$url = wppa_dbg_url( get_admin_url() . 'admin.php?page=wppa_admin_menu&amp;tab=edit&amp;edit_id=' . $albuminfo['a_parent'] . '&amp;wppa_nonce=' . wp_create_nonce( 'wppa_nonce' ) );
										$onc = 'document.location=\''.$url.'\';';
										echo
										'<input' .
											' type="button"' .
											' onclick="' . $onc . '"' .
											' value="' . esc_attr( __( 'Edit parent', 'wp-photo-album-plus' ) ) . '"' .
										' />';
									}

									// Goto Upload
									if ( current_user_can( 'wppa_upload' ) ) {
										$a = wppa_allow_uploads( $id );
										if ( $a ) {
											$full = false;
										}
										else {
											$full = true;
										}
										$onc = ( $full ?
													'alert(\''.__('Change the upload limit or remove photos to enable new uploads.', 'wp-photo-album-plus').'\')' :
													'document.location = \''.wppa_dbg_url(get_admin_url()).'/admin.php?page=wppa_upload_photos&wppa-set-album='.$id.'\''
												);
										$val = ( $full ?
													__( 'Album is full', 'wp-photo-album-plus' ) :
													__( 'Upload to this album', 'wp-photo-album-plus' ) .  ( $a > '0' ? ' ' . sprintf( __( '(max %d)', 'wp-photo-album-plus' ), $a ) : '' )
												);
										echo
										'<input' .
											' type="button"' .
											' onclick="' . $onc . '"' .
											' value="' . $val .'"' .
										' />';
									}

									echo
								'</td>' .
							'</tr>' .
						'</tbody>' .
					'</table>';
}


					?>
	</div>

					<?php wppa_album_sequence( $edit_id ) ?>

<a id="manage-photos" ></a>
				<img src="<?php echo WPPA_URL.'/img/camera32.png' ?>" alt="Camera icon" />
				<h1 style="display:inline;" ><?php _e('Manage Photos', 'wp-photo-album-plus');
					if ( isset($_REQUEST['bulk']) ) echo ' - <small><i>'.__('Copy / move / delete / edit name / edit description / change status', 'wp-photo-album-plus').'</i></small>';
					elseif ( isset($_REQUEST['seq']) ) echo ' - <small><i>'.__('Change sequence order by drag and drop', 'wp-photo-album-plus').'</i></small>';
					elseif ( isset($_REQUEST['quick']) ) echo ' - <small><i>'.__('Edit photo information except copy and move', 'wp-photo-album-plus').'</i></small>';
					else echo ' - <small><i>'.__('Edit photo information', 'wp-photo-album-plus').'</i></small>';
				?></h1><div style="clear:both;" >&nbsp;</div>
				<?php
					if ( isset($_REQUEST['bulk']) ) wppa_album_photos_bulk($edit_id);
					elseif ( isset($_REQUEST['seq']) ) wppa_album_photos_sequence($edit_id);
					else wppa_album_photos($edit_id)
				?>
				<a href="#manage-photos">
					<div style="position:fixed;right:30px;bottom:30px;background-color:lightblue;" >&nbsp;<?php _e('Top of page', 'wp-photo-album-plus') ?>&nbsp;</div>
				</a>
			</div>
<?php 	}

		// Comment moderate
		else if ($_REQUEST['tab'] == 'cmod') {
			$photo = $_REQUEST['photo'];
			$alb = wppa_get_album_id_by_photo_id($photo);
			if ( current_user_can('wppa_comments') && wppa_have_access($alb) ) { ?>
				<div class="wrap">
					<img src="<?php echo WPPA_URL.'/img/page_green.png' ?>" />
					<h1 style="display:inline;" ><?php _e('Moderate comment', 'wp-photo-album-plus') ?></h1>
					<div style="clear:both;" >&nbsp;</div>
					<?php wppa_album_photos('', $photo) ?>
				</div>
<?php		}
			else {
				wp_die('You do not have the rights to do this');
			}
		}

		// Photo moderate
		elseif ( $_REQUEST['tab'] == 'pmod' || $_REQUEST['tab'] == 'pedit' ) {
			$photo = $_REQUEST['photo'];
			$alb = wppa_get_album_id_by_photo_id($photo);
			if ( current_user_can('wppa_admin') && wppa_have_access($alb) ) { ?>
				<div class="wrap">
					<img src="<?php echo WPPA_URL.'/img/page_green.png' ?>" />
					<h1 style="display:inline;" ><?php 	if ( $_REQUEST['tab'] == 'pmod' ) _e('Moderate photo', 'wp-photo-album-plus');
								else _e('Edit photo', 'wp-photo-album-plus'); ?>
					</h1><div style="clear:both;" >&nbsp;</div>
					<?php wppa_album_photos('', $photo) ?>
				</div>
<?php		}
			else {
				wp_die('You do not have the rights to do this');
			}
		}

		// album delete confirm page
		else if ($_REQUEST['tab'] == 'del') {

			$album_owner = $wpdb->get_var($wpdb->prepare("SELECT `owner` FROM ".WPPA_ALBUMS." WHERE `id` = %s", $_REQUEST['edit_id']));
			if ( ( $album_owner == '--- public ---' && ! current_user_can('administrator') ) || ! wppa_have_access($_REQUEST['edit_id']) ) {
				wp_die('You do not have the rights to delete this album');
			}
?>
			<div class="wrap">
				<img src="<?php echo WPPA_URL.'/img/albumdel32.png' ?>" />
				<h1 style="display:inline;" ><?php _e('Delete Album', 'wp-photo-album-plus'); ?></h1>

				<p><?php _e('Album:', 'wp-photo-album-plus'); ?> <b><?php echo wppa_get_album_name($_REQUEST['edit_id']); ?>.</b></p>
				<p><?php _e('Are you sure you want to delete this album?', 'wp-photo-album-plus'); ?><br />
					<?php _e('Press Delete to continue, and Cancel to go back.', 'wp-photo-album-plus'); ?>
				</p>
				<form name="wppa-del-form" action="<?php echo(wppa_dbg_url(get_admin_url().'admin.php?page=wppa_admin_menu')) ?>" method="post">
					<?php wp_nonce_field('$wppa_nonce', WPPA_NONCE) ?>
					<p>
						<?php _e('What would you like to do with photos currently in the album?', 'wp-photo-album-plus'); ?><br />
						<input type="radio" name="wppa-del-photos" value="delete" checked="checked" /> <?php _e('Delete', 'wp-photo-album-plus'); ?><br />
						<input type="radio" name="wppa-del-photos" value="move" /> <?php _e('Move to:', 'wp-photo-album-plus'); ?>
						<select name="wppa-move-album">
							<?php echo wppa_album_select_a( array(	'checkaccess' => true,
																	'path' => wppa_switch( 'hier_albsel'),
																	'selected' => '0',
																	'exclude' => $_REQUEST['edit_id'],
																	'addpleaseselect' => true,
																	'sort' => true,
																	) )
							?>
						</select>
					</p>

					<input type="hidden" name="wppa-del-id" value="<?php echo($_REQUEST['edit_id']) ?>" />
					<input type="button" class="button-primary" value="<?php _e('Cancel', 'wp-photo-album-plus'); ?>" onclick="parent.history.back()" />
					<input type="submit" class="button-primary" style="color: red" name="wppa-del-confirm" value="<?php _e('Delete', 'wp-photo-album-plus'); ?>" />
				</form>
			</div>
<?php
		}
	}
	else {	//  'tab' not set. default, album manage page.

		// if add form has been submitted
//		if (isset($_POST['wppa-na-submit'])) {
//			check_admin_referer( '$wppa_nonce', WPPA_NONCE );

//			wppa_add_album();
//		}

		// if album deleted
		if (isset($_POST['wppa-del-confirm'])) {
			check_admin_referer( '$wppa_nonce', WPPA_NONCE );

			$album_owner = $wpdb->get_var($wpdb->prepare("SELECT `owner` FROM ".WPPA_ALBUMS." WHERE `id` = %s", $_POST['wppa-del-id']));
			if ( ( $album_owner == '--- public ---' && ! current_user_can('administrator') ) || ! wppa_have_access($_POST['wppa-del-id']) ) {
				wp_die('You do not have the rights to delete this album');
			}

			if ($_POST['wppa-del-photos'] == 'move') {
				$move = $_POST['wppa-move-album'];
				if ( wppa_have_access($move) ) {
					wppa_del_album($_POST['wppa-del-id'], $move);
				}
				else {
					wppa_error_message(__('Unable to move photos. Album not deleted.', 'wp-photo-album-plus'));
				}
			} else {
				wppa_del_album( $_POST['wppa-del-id'] );
			}
		}

		if ( wppa_extended_access() ) {
			if ( isset($_REQUEST['switchto']) ) update_option('wppa_album_table_'.wppa_get_user(), $_REQUEST['switchto']);
			$style = get_option('wppa_album_table_'.wppa_get_user(), 'flat');
		}
		else $style = 'flat';

		// The Manage Album page
?>
		<div class="wrap">
		<?php wppa_admin_spinner() ?>
			<img src="<?php echo WPPA_URL.'/img/album32.png' ?>" />
			<h1 style="display:inline;" ><?php _e('Manage Albums', 'wp-photo-album-plus'); ?></h1>
			<div style="clear:both;" >&nbsp;</div>
			<?php
			// The Create new album button
			if ( wppa_can_create_top_album() ) {
				$url = wppa_dbg_url(get_admin_url().'admin.php?page=wppa_admin_menu&amp;tab=edit&amp;edit_id=new&amp;wppa_nonce='.wp_create_nonce('wppa_nonce'));
				$vfy = __('Are you sure you want to create a new album?', 'wp-photo-album-plus');
				echo '<form method="post" action="'.get_admin_url().'admin.php?page=wppa_admin_menu&wppa_nonce='.wp_create_nonce('wppa_nonce').'" style="float:left; margin-right:12px;" >';
				echo '<input type="hidden" name="tab" value="edit" />';
				echo '<input type="hidden" name="edit_id" value="new" />';
				$onc = wppa_switch( 'confirm_create' ) ? 'onclick="return confirm(\''.$vfy.'\');"' : '';
				echo '<input type="submit" class="button-primary" '.$onc.' value="'.__('Create New Empty Album', 'wp-photo-album-plus').'" style="height:28px;" />';
				echo '</form>';
			}
			// The switch to button(s)
			if ( wppa_extended_access() ) {
				if ( $style == 'flat' ) { ?>
					<input type="button" class="button-secundary" onclick="document.location='<?php echo wppa_dbg_url(get_admin_url().'admin.php?page=wppa_admin_menu&amp;switchto=collapsible') ?>'" value="<?php _e('Switch to Collapsable table', 'wp-photo-album-plus'); ?>" />
				<?php }
				else /* if ( $style == 'collapsible' ) */ { ?>
					<input type="button" class="button-secundary" onclick="document.location='<?php echo wppa_dbg_url(get_admin_url().'admin.php?page=wppa_admin_menu&amp;switchto=flat') ?>'" value="<?php _e('Switch to Flat table', 'wp-photo-album-plus'); ?>" />
					<input
						type="button"
						class="button-secundary"
						id="wppa-open-all"
						style="display:inline;"
						onclick="	jQuery('#wppa-close-all').css('display','inline');
									jQuery(this).css('display','none');
									jQuery('.wppa-alb-onoff').css('display','');
									jQuery('.alb-arrow-off').css('display','');
									jQuery('.alb-arrow-on').css('display','none');
									"
						value="<?php _e('Open all', 'wp-photo-album-plus'); ?>"
					/>
					<input
						type="button"
						class="button-secundary"
						id="wppa-close-all"
						style="display:none;"
						onclick="	jQuery('#wppa-open-all').css('display','inline');
									jQuery(this).css('display','none');
									jQuery('.wppa-alb-onoff').css('display','none');
									jQuery('.alb-arrow-on').css('display','');
									jQuery('.alb-arrow-off').css('display','none');
									"
						value="<?php _e('Close all', 'wp-photo-album-plus'); ?>"
					/>
				<?php }
			} ?>

			<br />
			<?php // The table of existing albums
				if ( $style == 'flat' ) wppa_admin_albums_flat();
				else wppa_admin_albums_collapsible();
			?>
			<br />

			<?php wppa_album_sequence( '0' ) ?>
		</div>
<?php
	}
}

// The albums table flat
function wppa_admin_albums_flat() {
global $wpdb;

	// Init
	$pagesize 	= wppa_opt( 'album_admin_pagesize' );
	$page 		= '1';
	$skips 		= '0';
	$pages 		= '1';

	// Find out what page to show
	if ( $pagesize ) {
		if ( isset( $_REQUEST['album-page-no'] ) ) {
			$page 	= strval( intval( $_REQUEST['album-page-no'] ) );
			$page 	= max( $page, '1' );
			$skips 	= ( $page - 1 ) * $pagesize;
		}
	}

	// Read all albums, pre-ordered
	$albums = $wpdb->get_results( "SELECT * FROM `" . WPPA_ALBUMS . "` ORDER BY " . get_option( 'wppa_album_order_'.wppa_get_user(), 'id' ) . ( get_option( 'wppa_album_order_' . wppa_get_user() . '_reverse' ) == 'yes' ? " DESC" : "" ) , ARRAY_A );

	// Remove non accessible albums
	$temp = $albums;
	$albums = array();
	foreach ( array_keys( $temp ) as $idx ) {
		if ( wppa_have_access( $temp[$idx]['id'] ) ) {
			$albums[] = $temp[$idx];
		}
	}
	$count = count( $albums );

	// If paging: Make new array with selected albums only
	if ( $pagesize ) {
		$temp 	= $albums;
		$albums = array();
		$i 		= 0;
		foreach( $temp as $item ) {
			if ( $i < $skips ) {}
			elseif ( $i >= ( $skips + $pagesize ) ) {}
			else {
				$albums[] = $item;
			}
			$i++;
		}
	}

	// Find the final ordering method
	$reverse = false;
	if ( isset($_REQUEST['order_by']) ) $order = $_REQUEST['order_by']; else $order = '';
	if ( ! $order ) {
		$order = get_option('wppa_album_order_'.wppa_get_user(), 'id');
		$reverse = (get_option('wppa_album_order_'.wppa_get_user().'_reverse') == 'yes');
	}
	else {
		$old_order = get_option('wppa_album_order_'.wppa_get_user(), 'id');
		$reverse = (get_option('wppa_album_order_'.wppa_get_user().'_reverse') == 'yes');
		if ( $old_order == $order ) {
			$reverse = ! $reverse;
		}
		else $reverse = false;
		update_option('wppa_album_order_'.wppa_get_user(), $order);
		if ( $reverse ) update_option('wppa_album_order_'.wppa_get_user().'_reverse', 'yes');
		else update_option('wppa_album_order_'.wppa_get_user().'_reverse', 'no');
	}

	if ( ! empty($albums) ) {

		// Setup the sequence array
		$seq = false;
		$num = false;
		foreach( $albums as $album ) {
			switch ( $order ) {
				case 'name':
					$seq[] = strtolower(__(stripslashes($album['name'])));
					break;
				case 'description':
					$seq[] = strtolower(__(stripslashes($album['description'])));
					break;
				case 'owner':
					$seq[] = strtolower($album['owner']);
					break;
				case 'a_order':
					$seq[] = $album['a_order'];
					$num = true;
					break;
				case 'a_parent':
					$seq[] = strtolower(wppa_get_album_name($album['a_parent'], array( 'extended' => true )));
					break;
				default:
					$seq[] = $album['id'];
					$num = true;
					break;
			}
		}

		// Sort the seq array
		if ( $num ) asort($seq, SORT_NUMERIC);
		else asort($seq, SORT_REGULAR);

		// Reverse ?
		if ( $reverse ) {
			$t = $seq;
			$c = count($t);
			$tmp = array_keys($t);
			$seq = false;
			for ( $i = $c-1; $i >=0; $i-- ) {
				$seq[$tmp[$i]] = '0';
			}
		}

		$downimg = '<img src="'.wppa_get_imgdir().'down.png" alt="down" style=" height:12px; position:relative; top:2px; " />';
		$upimg   = '<img src="'.wppa_get_imgdir().'up.png" alt="up" style=" height:12px; position:relative; top:2px; " />';

		wppa_album_table_pagination( $page, $count );

		?>

		<table class="wppa-table widefat wppa-setting-table" style="margin-top:12px;" >
			<thead>
			<tr>
				<?php $url = get_admin_url().'admin.php?page=wppa_admin_menu&amp;order_by='; ?>
				<td  style="min-width: 50px;" >
					<a href="<?php echo wppa_dbg_url($url.'id') ?>">
						<?php _e('ID', 'wp-photo-album-plus');
							if ($order == 'id') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<td  style="min-width: 120px;">
					<a href="<?php echo wppa_dbg_url($url.'name') ?>">
						<?php _e('Name', 'wp-photo-album-plus');
							if ($order == 'name') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<td >
					<a href="<?php echo wppa_dbg_url($url.'description') ?>">
						<?php _e('Description', 'wp-photo-album-plus');
							if ($order == 'description') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<?php if (current_user_can('administrator')) { ?>
				<td  style="min-width: 100px;">
					<a href="<?php echo wppa_dbg_url($url.'owner') ?>">
						<?php _e('Owner', 'wp-photo-album-plus');
							if ($order == 'owner') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<?php } ?>
                <td  style="min-width: 100px;" >
					<a href="<?php echo wppa_dbg_url($url.'a_order') ?>">
						<?php _e('Order', 'wp-photo-album-plus');
							if ($order == 'a_order') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
                <td  style="width: 120px;">
					<a href="<?php echo wppa_dbg_url($url.'a_parent') ?>">
						<?php _e('Parent', 'wp-photo-album-plus');
							if ($order == 'a_parent') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<td  title="<?php _e('Albums/Photos/Moderation required/Scheduled', 'wp-photo-album-plus') ?>" >
					<?php _e('A/P/PM/S', 'wp-photo-album-plus'); ?>
				</td>
				<td ><?php _e('Edit', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Quick', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Bulk', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Seq', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Delete', 'wp-photo-album-plus'); ?></td>
				<?php if ( wppa_can_create_album() ) echo '<td >'.__('Create', 'wp-photo-album-plus').'</td>'; ?>
			</tr>
			</thead>
			<tbody>
			<?php $alt = ' class="alternate" '; ?>

			<?php
//				foreach ($albums as $album) if(wppa_have_access($album)) {
				$idx = '0';
				foreach (array_keys($seq) as $s) {
					$album = $albums[$s];
					if (wppa_have_access($album)) {
						$counts = wppa_get_treecounts_a($album['id'], true);
						$pendcount = $counts['pendselfphotos'];
//						$pendcount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE album=%s AND status=%s", $album['id'], 'pending'));
						?>
						<tr <?php echo($alt); if ($pendcount) echo 'style="background-color:#ffdddd"' ?>>
							<td><?php echo($album['id']) ?></td>
							<td><?php echo(esc_attr(__(stripslashes($album['name'])))) ?></td>
							<td><small><?php echo(esc_attr(__(stripslashes($album['description'])))) ?></small></td>
							<?php if (current_user_can('administrator')) { ?>
								<td><?php echo($album['owner']); ?></td>
							<?php } ?>
							<td><?php echo($album['a_order']) ?></td>
							<td><?php echo wppa_get_album_name($album['a_parent'], array( 'extended' => true )) ?></td>
							<?php $url = wppa_dbg_url(get_admin_url().'admin.php?page=wppa_admin_menu&amp;tab=edit&amp;edit_id='.$album['id']); ?>
							<?php $na = $counts['selfalbums']; ?>
							<?php $np = $counts['selfphotos']; ?>
							<?php $nm = $counts['pendselfphotos']; ?>
							<?php $ns = $counts['scheduledselfphotos']; ?>
							<td><?php echo $na.'/'.$np.'/'.$nm.'/'.$ns; ?></td>
							<?php if ( $album['owner'] != '--- public ---' || wppa_user_is('administrator') ) { ?>
								<?php $url = wppa_ea_url($album['id']) ?>
								<td><a href="<?php echo($url) ?>" class="wppaedit"><?php _e('Edit', 'wp-photo-album-plus'); ?></a></td>
								<td><a href="<?php echo($url.'&amp;quick') ?>" class="wppaedit"><?php _e('Quick', 'wp-photo-album-plus'); ?></a></td>
								<td><a href="<?php echo($url.'&amp;bulk#manage-photos') ?>" class="wppaedit"><?php _e('Bulk', 'wp-photo-album-plus'); ?></a></td>
								<td><a href="<?php echo($url.'&amp;seq') ?>" class="wppaedit"><?php _e('Seq', 'wp-photo-album-plus'); ?></a></td>

								<?php $url = wppa_ea_url($album['id'], 'del') ?>
								<td><a href="<?php echo($url) ?>" class="wppadelete"><?php _e('Delete', 'wp-photo-album-plus'); ?></a></td>
								<?php if ( wppa_can_create_album() ) {
									$url = wppa_dbg_url(get_admin_url().'admin.php?page=wppa_admin_menu&amp;tab=edit&amp;edit_id=new&amp;parent_id='.$album['id'].'&amp;wppa_nonce='.wp_create_nonce('wppa_nonce'));
									if ( wppa_switch( 'confirm_create' ) ) {
										$onc = 'if (confirm(\''.__('Are you sure you want to create a subalbum?', 'wp-photo-album-plus').'\')) document.location=\''.$url.'\';';
										echo '<td><a onclick="'.$onc.'" class="wppacreate">'.__('Create', 'wp-photo-album-plus').'</a></td>';
									}
									else {
										echo '<td><a href="'.$url.'" class="wppacreate">'.__('Create', 'wp-photo-album-plus').'</a></td>';
									}
								}
							}
							else { ?>
							<td></td><td></td><?php if ( wppa_can_create_album() ) echo '<td></td' ?>
							<?php } ?>
						</tr>
						<?php if ($alt == '') { $alt = ' class="alternate" '; } else { $alt = '';}
					}
					$idx++;
				}

				wppa_search_edit();
				wppa_trash_edit();

?>
			</tbody>
			<tfoot>
			<tr>
				<?php $url = get_admin_url().'admin.php?page=wppa_admin_menu&amp;order_by='; ?>
				<td >
					<a href="<?php echo wppa_dbg_url($url.'id') ?>">
						<?php _e('ID', 'wp-photo-album-plus');
							if ($order == 'id') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<td  style="width: 120px;">
					<a href="<?php echo wppa_dbg_url($url.'name') ?>">
						<?php _e('Name', 'wp-photo-album-plus');
							if ($order == 'name') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<td >
					<a href="<?php echo wppa_dbg_url($url.'description') ?>">
						<?php _e('Description', 'wp-photo-album-plus');
							if ($order == 'description') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<?php if (current_user_can('administrator')) { ?>
				<td  style="width: 100px;">
					<a href="<?php echo wppa_dbg_url($url.'owner') ?>">
						<?php _e('Owner', 'wp-photo-album-plus');
							if ($order == 'owner') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<?php } ?>
                <td >
					<a href="<?php echo wppa_dbg_url($url.'a_order') ?>">
						<?php _e('Order', 'wp-photo-album-plus');
							if ($order == 'a_order') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
                <td  style="width: 120px;">
					<a href="<?php echo wppa_dbg_url($url.'a_parent') ?>">
						<?php _e('Parent', 'wp-photo-album-plus');
							if ($order == 'a_parent') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<td  title="<?php _e('Albums/Photos/Moderation required/Scheduled', 'wp-photo-album-plus') ?>" >
					<?php _e('A/P/PM/S', 'wp-photo-album-plus'); ?>
				</td>
				<td ><?php _e('Edit', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Quick', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Bulk', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Seq', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Delete', 'wp-photo-album-plus'); ?></td>
				<?php if ( wppa_can_create_album() ) echo '<td >'.__('Create', 'wp-photo-album-plus').'</td>'; ?>
			</tr>
			</tfoot>

		</table>
<?php
	wppa_album_table_pagination( $page, $count );
	wppa_album_admin_footer();

	} else {
?>
	<p><?php _e('No albums yet.', 'wp-photo-album-plus'); ?></p>
<?php
	}
}

// The albums table collapsible
function wppa_admin_albums_collapsible() {
global $wpdb;

	// Init
	$pagesize 	= wppa_opt( 'album_admin_pagesize' );
	$page 		= '1';
	$skips 		= '0';
	$pages 		= '1';

	// Find out what page to show
	if ( $pagesize ) {
		if ( isset( $_REQUEST['album-page-no'] ) ) {
			$page 	= strval( intval( $_REQUEST['album-page-no'] ) );
			$page 	= max( $page, '1' );
			$skips 	= ( $page - 1 ) * $pagesize;
		}
	}

	// Read all albums, pre-ordered
	$albums = $wpdb->get_results( "SELECT * FROM `" . WPPA_ALBUMS . "` ORDER BY " . get_option( 'wppa_album_order_'.wppa_get_user(), 'id' ) . ( get_option( 'wppa_album_order_' . wppa_get_user() . '_reverse' ) == 'yes' ? " DESC" : "" ) , ARRAY_A );

	// Remove non accessible albums
	$temp = $albums;
	$albums = array();
	foreach ( array_keys( $temp ) as $idx ) {
		if ( wppa_have_access( $temp[$idx]['id'] ) ) {
			$albums[] = $temp[$idx];
		}
	}
	$count = count( $albums );

	// If pagination: Make new array with selected albums only
	if ( $pagesize ) {
		$temp 	= $albums;
		$albums = array();
		$i 		= 0;
		foreach( $temp as $item ) {
			if ( $i < $skips ) {}
			elseif ( $i >= ( $skips + $pagesize ) ) {}
			else {
				$albums[] = $item;
			}
			$i++;
		}
	}

	// Make sure all (grand)parents are in
	$done = false;
	while ( ! $done ) {
		$done = true;
		foreach ( $albums as $a ) {
			$parent = $a['a_parent'];
			if ( $parent > '0' ) {
				$found = false;
				foreach ( $albums as $p ) {
					if ( $p['id'] == $parent ) {
						$found = true;
					}
				}
				if ( ! $found ) {
					$done = false;

					// Add missing parent
					$albums[] = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" .WPPA_ALBUMS . "` WHERE `id` = %d", $parent ), ARRAY_A );
				}
			}
		}
	}

	// Find the ordering method
	$reverse = false;
	if ( isset($_REQUEST['order_by']) ) $order = $_REQUEST['order_by']; else $order = '';
	if ( ! $order ) {
		$order = get_option('wppa_album_order_'.wppa_get_user(), 'id');
		$reverse = (get_option('wppa_album_order_'.wppa_get_user().'_reverse') == 'yes');
	}
	else {
		$old_order = get_option('wppa_album_order_'.wppa_get_user(), 'id');
		$reverse = (get_option('wppa_album_order_'.wppa_get_user().'_reverse') == 'yes');
		if ( $old_order == $order ) {
			$reverse = ! $reverse;
		}
		else $reverse = false;
		update_option('wppa_album_order_'.wppa_get_user(), $order);
		if ( $reverse ) update_option('wppa_album_order_'.wppa_get_user().'_reverse', 'yes');
		else update_option('wppa_album_order_'.wppa_get_user().'_reverse', 'no');
	}

	if ( ! empty($albums) ) {

		// Setup the sequence array
		$seq = false;
		$num = false;
		foreach( $albums as $album ) {
			switch ( $order ) {
				case 'name':
					$seq[] = strtolower(__(stripslashes($album['name'])));
					break;
				case 'description':
					$seq[] = strtolower(__(stripslashes($album['description'])));
					break;
				case 'owner':
					$seq[] = strtolower($album['owner']);
					break;
				case 'a_order':
					$seq[] = $album['a_order'];
					$num = true;
					break;
				case 'a_parent':
					$seq[] = strtolower(wppa_get_album_name($album['a_parent']), array( 'extended' => true ));
					break;
				default:
					$seq[] = $album['id'];
					$num = true;
					break;
			}
		}

		// Sort the seq array
		if ( $num ) asort($seq, SORT_NUMERIC);
		else asort($seq, SORT_REGULAR);

		// Reverse ?
		if ( $reverse ) {
			$t = $seq;
			$c = count($t);
			$tmp = array_keys($t);
			$seq = false;
			for ( $i = $c-1; $i >=0; $i-- ) {
				$seq[$tmp[$i]] = '0';
			}
		}

		$downimg = '<img src="'.wppa_get_imgdir().'down.png" alt="down" style=" height:12px; position:relative; top:2px; " />';
		$upimg   = '<img src="'.wppa_get_imgdir().'up.png" alt="up" style=" height:12px; position:relative; top:2px; " />';

		wppa_album_table_pagination( $page, $count );

?>
		<table class="widefat wppa-table wppa-setting-table" style="margin-top:12px;" >
			<thead>
			<tr>
				<td style="min-width:20px;" >
					<img src="<?php echo wppa_get_imgdir().'backarrow.gif' ?>" style="height:16px;" title="<?php _e('Collapse subalbums', 'wp-photo-album-plus') ?>" />
					<img src="<?php echo wppa_get_imgdir().'arrow.gif' ?>" style="height:16px;" title="<?php _e('Expand subalbums', 'wp-photo-album-plus') ?>" />
				</td>
				<?php $url = get_admin_url().'admin.php?page=wppa_admin_menu&amp;order_by='; ?>
				<td  colspan="6" style="min-width: 50px;" >
					<a href="<?php echo wppa_dbg_url($url.'id') ?>">
						<?php _e('ID', 'wp-photo-album-plus');
							if ($order == 'id') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>

				<td  style="min-width: 120px;">
					<a href="<?php echo wppa_dbg_url($url.'name') ?>">
						<?php _e('Name', 'wp-photo-album-plus');
							if ($order == 'name') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<td >
					<a href="<?php echo wppa_dbg_url($url.'description') ?>">
						<?php _e('Description', 'wp-photo-album-plus');
							if ($order == 'description') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<?php if (current_user_can('administrator')) { ?>
				<td  style="min-width: 100px;">
					<a href="<?php echo wppa_dbg_url($url.'owner') ?>">
						<?php _e('Owner', 'wp-photo-album-plus');
							if ($order == 'owner') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<?php } ?>
                <td  style="min-width: 100px;" >
					<a href="<?php echo wppa_dbg_url($url.'a_order') ?>">
						<?php _e('Order', 'wp-photo-album-plus');
							if ($order == 'a_order') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
                <td  style="width: 120px;">
					<a href="<?php echo wppa_dbg_url($url.'a_parent') ?>">
						<?php _e('Parent', 'wp-photo-album-plus');
							if ($order == 'a_parent') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<td  title="<?php _e('Albums/Photos/Moderation required/Scheduled', 'wp-photo-album-plus') ?>" >
					<?php _e('A/P/PM/S', 'wp-photo-album-plus'); ?>
				</td>
				<td ><?php _e('Edit', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Quick', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Bulk', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Seq', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Delete', 'wp-photo-album-plus'); ?></td>
				<?php if ( wppa_can_create_album() ) echo '<td >'.__('Create', 'wp-photo-album-plus').'</td>'; ?>
			</tr>
			</thead>
			<tbody>

			<?php wppa_do_albumlist('0', '0', $albums, $seq); ?>
			<?php if ( $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_ALBUMS."` WHERE `a_parent` = '-1'" ) > 0 ) { ?>
				<tr>
					<td colspan="19" ><em><?php _e('The following albums are ---separate--- and do not show up in the generic album display', 'wp-photo-album-plus'); ?></em></td>
				</tr>
				<?php wppa_do_albumlist('-1', '0', $albums, $seq); ?>
			<?php }

			wppa_search_edit( true );
			wppa_trash_edit( true );

			?>
			</tbody>
			<tfoot>
			<tr>
				<td>
					<img src="<?php echo wppa_get_imgdir().'backarrow.gif' ?>" style="height:16px;" />
					<img src="<?php echo wppa_get_imgdir().'arrow.gif' ?>" style="height:16px;" />
				</td>
				<?php $url = get_admin_url().'admin.php?page=wppa_admin_menu&amp;order_by='; ?>
				<td  colspan="6" >
					<a href="<?php echo wppa_dbg_url($url.'id') ?>">
						<?php _e('ID', 'wp-photo-album-plus');
							if ($order == 'id') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>

				<td  style="width: 120px;">
					<a href="<?php echo wppa_dbg_url($url.'name') ?>">
						<?php _e('Name', 'wp-photo-album-plus');
							if ($order == 'name') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<td >
					<a href="<?php echo wppa_dbg_url($url.'description') ?>">
						<?php _e('Description', 'wp-photo-album-plus');
							if ($order == 'description') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<?php if (current_user_can('administrator')) { ?>
				<td  style="width: 100px;">
					<a href="<?php echo wppa_dbg_url($url.'owner') ?>">
						<?php _e('Owner', 'wp-photo-album-plus');
							if ($order == 'owner') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<?php } ?>
                <td >
					<a href="<?php echo wppa_dbg_url($url.'a_order') ?>">
						<?php _e('Order', 'wp-photo-album-plus');
							if ($order == 'a_order') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
                <td  style="width: 120px;">
					<a href="<?php echo wppa_dbg_url($url.'a_parent') ?>">
						<?php _e('Parent', 'wp-photo-album-plus');
							if ($order == 'a_parent') {
								if ( $reverse ) echo $upimg;
								else echo $downimg;
							}
						?>
					</a>
				</td>
				<td  title="<?php _e('Albums/Photos/Moderation required/Scheduled', 'wp-photo-album-plus') ?>" >
					<?php _e('A/P/PM/S', 'wp-photo-album-plus'); ?>
				</td>
				<td ><?php _e('Edit', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Quick', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Bulk', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Seq', 'wp-photo-album-plus'); ?></td>
				<td ><?php _e('Delete', 'wp-photo-album-plus'); ?></td>
				<?php if ( wppa_can_create_album() ) echo '<td >'.__('Create', 'wp-photo-album-plus').'</td>'; ?>
			</tr>
			</tfoot>

		</table>

		<script type="text/javascript" >
			function checkArrows() {
				elms = jQuery('.alb-arrow-off');
				for(i=0;i<elms.length;i++) {
					elm = elms[i];
					if ( elm.parentNode.parentNode.style.display == 'none' ) elm.style.display = 'none';
				}
				elms = jQuery('.alb-arrow-on');
				for(i=0;i<elms.length;i++) {
					elm = elms[i];
					if ( elm.parentNode.parentNode.style.display == 'none' ) elm.style.display = '';
				}
			}
		</script>

<?php
	wppa_album_table_pagination( $page, $count );
	wppa_album_admin_footer();

	} else {
?>
	<p><?php _e('No albums yet.', 'wp-photo-album-plus'); ?></p>
<?php
	}
}

function wppa_search_edit( $collapsible = false ) {

	$doit = false;
	if ( current_user_can( 'wppa_admin' ) && current_user_can( 'wppa_moderate' ) ) $doit = true;
	if ( wppa_opt( 'upload_edit' ) != '-none-' ) $doit = true;

	if ( ! $doit ) return;

	$result =
	'<tr>' .
		'<td colspan="' . ( $collapsible ? 19 : 13 ) . '" >' .
			'<em>' .
				__( 'Search for photos to edit', 'wp-photo-album-plus' ) .
			'</em>' .
			'<small>' .
				__( 'Enter search words seperated by commas. Photos will meet all search words by their names, descriptions, translated keywords and/or tags.', 'wp-photo-album-plus' ) .
			'</small>' .
		'</td>' .
	'</tr>' .
	'<tr class="alternate" >' .
		( $collapsible ? '<td></td>' : '' ) .
		'<td>' .
			__( 'Any', 'wp-photo-album-plus' ) .
		'</td>' .
		( $collapsible ? '<td></td><td></td><td></td><td></td><td></td>' : '' ) .
		'<td>' .
			__( 'Search for', 'wp-photo-album-plus' ) .
		'</td>' .
		'<td colspan="4" >';
			$value = isset( $_REQUEST['wppa-searchstring'] ) ? wppa_sanitize_searchstring( $_REQUEST['wppa-searchstring'] ) : '';
			$result .=
			'<a id="wppa-edit-search-tag" />' .
			'<input' .
				' type="text"' .
				' id="wppa-edit-search"' .
				' name="wppa-edit-search"' .
				' style="width:100%;padding:2px;color:black;background-color:#ccffcc;"' .
				' value="' . $value . '"' .
			' />' .
		'</td>';
		if ( current_user_can( 'wppa_admin' ) && current_user_can( 'wppa_moderate' ) ) {
			$result .= '<td></td>';
		}
		$result .=
		'<td>' .
			'<a class="wppaedit" onclick="wppaEditSearch(\'' . wppa_ea_url( 'search' ) . '\', \'wppa-edit-search\' )" >' .
				'<b>' . __( 'Edit', 'wp-photo-album-plus' ) . '</b>' .
			'</a>' .
		'</td>' .
		'<td>' .
			'<a class="wppaedit" onclick="wppaEditSearch(\'' . wppa_ea_url( 'search' ) . '&amp;quick' . '\', \'wppa-edit-search\' )" >' .
				'<b>' . __( 'Quick', 'wp-photo-album-plus' ) . '</b>' .
			'</a>' .
		'</td>' .
		'<td>' .
			'<a class="wppaedit" onclick="wppaEditSearch(\'' . wppa_ea_url( 'search' ) . '&amp;bulk' . '\', \'wppa-edit-search\' )" >' .
				'<b>' . __( 'Bulk', 'wp-photo-album-plus' ) . '</b>' .
			'</a>' .
		'</td>' .
		'<td></td><td></td><td></td>' .
	'</tr>';

	echo $result;
}

function wppa_trash_edit( $collapsible = false ) {
global $wpdb;

	$doit = false;
	if ( wppa_user_is( 'administrator' ) ) $doit = true;

	$trashed = $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPPA_PHOTOS . "` WHERE `album` < '0'" );

	if ( ! $trashed ) $doit = false;

	if ( ! $doit ) return;

	$result =
	'<tr>';
		if ( $collapsible ) {
			$result .= '<td></td>';
		}
		$result .=
		'<td>' .
			__( 'Any', 'wp-photo-album-plus' ) .
		'</td>';
		if ( $collapsible ) {
			$result .= '<td></td><td></td><td></td><td></td><td></td>';
		}
		$result .=
		'<td colspan="4" >' .
			sprintf( __( 'There are %s trashed photos that can be rescued', 'wp-photo-album-plus' ), $trashed ) .
		'</td>' .
		'<td>' .
		'</td>' .
		'<td></td>' .
		'<td>' .
			'<a class="wppaedit" onclick="wppaEditTrash( \'' . wppa_ea_url( 'trash' ) . '\' );" >' .
				'<b>' . __( 'Edit', 'wp-photo-album-plus' ) . '</b>' .
			'</a>' .
		'</td>' .
		'<td>' .
			'<a class="wppaedit" onclick="wppaEditTrash( \'' . wppa_ea_url( 'trash' ) . '&amp;quick' . '\' );" >' .
				'<b>' . __( 'Quick', 'wp-photo-album-plus' ) . '</b>' .
			'</a>' .
		'</td>' .
		'<td></td><td></td><td></td><td></td>' .
	'</tr>';

	echo $result;
}

function wppa_album_table_pagination( $page, $count ) {
global $wpdb;

	// Init
	$result = '';
	$pagesize 	= wppa_opt( 'album_admin_pagesize' );

	// Paging on?
	if ( ! $pagesize ) {
		return;
	}

	$npages 	= ceil( $count / $pagesize );

	// Just one page?
	if ( $npages == '1' ) {
		return;
	}

	$link 		= get_admin_url().'admin.php?page=wppa_admin_menu&album-page-no=';

	$result .= '<div style="line-height:1.7em;" >';

	// The links
	if ( $page != '1' ) {
		$result .= '<a href="' . $link . ( $page - '1') . '" >' . __( 'Previous page', 'wp-photo-album-plus' ) . '</a> ';
	}

	$p = '1';
	while ( $p <= $npages ) {
		if ( $p == $page ) {
			$result .= '<span style="padding:0 0,25em;" >' . $page . '</span> ';
		}
		else {
			$result .= 	'<a' .
							' href="' . $link . $p . '"' .
							' style="border:1px solid;padding:0 0.25em;"' .
							' >' .
							$p .
						'</a> ';
		}
		$p++;
	}

	if ( $page != $npages ) {
		$result .= '<a href="' . $link . ( $page + '1') . '" >' . __( 'Next page', 'wp-photo-album-plus' ) . '</a>';
	}

	$result .= '</div>';

	echo $result;
}

function wppa_do_albumlist($parent, $nestinglevel, $albums, $seq) {
global $wpdb;

	$alt = true;

		foreach (array_keys($seq) as $s) {			// Obey the global sequence
			$album = $albums[$s];
			if ( $album['a_parent'] == $parent ) {
				if (wppa_have_access($album)) {
					$counts = wppa_get_treecounts_a($album['id'], true);
					$pendcount = $counts['pendselfphotos'];
					$schedulecount = $counts['scheduledselfphotos'];
					$haschildren = wppa_have_accessible_children( $album );
					{
						$class = '';
						if ( $parent != '0' && $parent != '-1' ) {
							$class .= 'wppa-alb-onoff ';
							$class .= 'wppa-alb-on-'.$parent.' ';
							$par = $parent;
							while ( $par != '0' && $par != '-1' ) {
								$class .= 'wppa-alb-off-'.$par.' ';
								$par = wppa_get_parentalbumid($par);
							}
						}
						if ( $alt ) $class .= ' alternate';
						$style = '';
						if ( $pendcount ) $style .= 'background-color:#ffdddd; ';
					//	if ( $haschildren ) $style .= 'font-weight:bold; ';
						if ( $parent != '0' && $parent != '-1' ) $style .= 'display:none; ';
						$onclickon = 'jQuery(\'.wppa-alb-on-'.$album['id'].'\').css(\'display\',\'\'); jQuery(\'#alb-arrow-on-'.$album['id'].'\').css(\'display\',\'none\'); jQuery(\'#alb-arrow-off-'.$album['id'].'\').css(\'display\',\'\');';
						$onclickoff = 'jQuery(\'.wppa-alb-off-'.$album['id'].'\').css(\'display\',\'none\'); jQuery(\'#alb-arrow-on-'.$album['id'].'\').css(\'display\',\'\'); jQuery(\'#alb-arrow-off-'.$album['id'].'\').css(\'display\',\'none\'); checkArrows();';
						$indent = $nestinglevel;
						if ( $indent > '5' ) $indent = 5;
						?>

						<tr class="<?php echo $class ?>" style="<?php echo $style ?>" >
							<?php
							$i = 0;
							while ( $i < $indent ) {
								echo '<td style="padding:2px;" ></td>';
								$i++;
							}
							?>
							<td style="padding:2px; text-align:center;" ><?php if ( $haschildren ) { ?>
								<img id="alb-arrow-off-<?php echo $album['id'] ?>" class="alb-arrow-off" style="height:16px; display:none;" src="<?php echo wppa_get_imgdir().'backarrow.gif' ?>" onclick="<?php echo $onclickoff ?>" title="<?php _e('Collapse subalbums', 'wp-photo-album-plus') ?>" />
								<img id="alb-arrow-on-<?php echo $album['id'] ?>" class="alb-arrow-on" style="height:16px;" src="<?php echo wppa_get_imgdir().'arrow.gif' ?>" onclick="<?php echo $onclickon ?>" title="<?php _e('Expand subalbums', 'wp-photo-album-plus') ?>" />
							<?php } ?></td>
							<td style="padding:2px;" ><?php echo($album['id']); ?></td>
							<?php
							$i = $indent;
							while ( $i < 5 ) {
								echo '<td style="padding:2px;" ></td>';
								$i++;
							}
							?>
							<td><?php echo(esc_attr(__(stripslashes($album['name'])))) ?></td>
							<td><small><?php echo(esc_attr(__(stripslashes($album['description'])))) ?></small></td>
							<?php if (current_user_can('administrator')) { ?>
								<td><?php echo($album['owner']); ?></td>
							<?php } ?>
							<td><?php echo($album['a_order']) ?></td>
							<td><?php echo wppa_get_album_name($album['a_parent'], array( 'extended' => true )) ?></td>
							<?php $url = wppa_dbg_url(get_admin_url().'admin.php?page=wppa_admin_menu&amp;tab=edit&amp;edit_id='.$album['id']); ?>
							<?php $na = $counts['selfalbums']; ?>
							<?php $np = $counts['selfphotos']; ?>
							<?php $nm = $counts['pendselfphotos']; ?>
							<?php $ns = $counts['scheduledselfphotos']; ?>
							<td><?php echo $na.'/'.$np.'/'.$nm.'/'.$ns; ?></td>
							<?php if ( $album['owner'] != '--- public ---' || wppa_user_is('administrator') ) { ?>
								<?php $url = wppa_ea_url($album['id']) ?>
								<td><a href="<?php echo($url) ?>" class="wppaedit"><?php _e('Edit', 'wp-photo-album-plus'); ?></a></td>
								<td><a href="<?php echo($url.'&amp;quick') ?>" class="wppaedit"><?php _e('Quick', 'wp-photo-album-plus'); ?></a></td>
								<td><a href="<?php echo($url.'&amp;bulk#manage-photos') ?>" class="wppaedit"><?php _e('Bulk', 'wp-photo-album-plus'); ?></a></td>
								<td><a href="<?php echo($url.'&amp;seq') ?>" class="wppaedit"><?php _e('Seq', 'wp-photo-album-plus'); ?></a></td>

								<?php $url = wppa_ea_url($album['id'], 'del') ?>
								<td><a href="<?php echo($url) ?>" class="wppadelete"><?php _e('Delete', 'wp-photo-album-plus'); ?></a></td>
								<?php if ( wppa_can_create_album() ) {
									$url = wppa_dbg_url(get_admin_url().'admin.php?page=wppa_admin_menu&amp;tab=edit&amp;edit_id=new&amp;parent_id='.$album['id'].'&amp;wppa_nonce='.wp_create_nonce('wppa_nonce'));
									if ( wppa_switch( 'confirm_create' ) ) {
										$onc = 'if (confirm(\''.__('Are you sure you want to create a subalbum?', 'wp-photo-album-plus').'\')) document.location=\''.$url.'\';';
										echo '<td><a onclick="'.$onc.'" class="wppacreate">'.__('Create', 'wp-photo-album-plus').'</a></td>';
									}
									else {
										echo '<td><a href="'.$url.'" class="wppacreate">'.__('Create', 'wp-photo-album-plus').'</a></td>';
									}
								}
							}
							else { ?>
							<td></td><td></td><?php if ( wppa_can_create_album() ) echo '<td></td' ?>
							<?php } ?>
						</tr>
						<?php if ($alt == '') { $alt = ' class="alternate" '; } else { $alt = '';}
						if ( $haschildren ) wppa_do_albumlist($album['id'], $nestinglevel+'1', $albums, $seq);
					}
				}
			}
		}

}

function wppa_have_accessible_children( $alb ) {
global $wpdb;

	$albums = $wpdb->get_results( "SELECT * FROM `" . WPPA_ALBUMS . "` WHERE `a_parent` = " . $alb['id'], ARRAY_A );

	if ( ! $albums || ! count($albums) ) return false;
	foreach ( $albums as $album ) {
		if ( wppa_have_access($album) ) return true;
	}
	return false;
}

// delete an album
function wppa_del_album( $id, $move = '-9' ) {
global $wpdb;

	if ( $move && ! wppa_have_access( $move ) ) {
		wppa_error_message(__('Unable to move photos to album %s. Album not deleted.', 'wp-photo-album-plus'));
		return false;
	}

	if ( $move == '-9' ) {
		$move = - ( $id + '9' );
	}

	// Photos in the album
	$photos = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . WPPA_PHOTOS . '` WHERE `album` = %s', $id ), ARRAY_A );

	if ( is_array( $photos ) ) {
		foreach ( $photos as $photo ) {
			$wpdb->query( $wpdb->prepare( 'UPDATE `' . WPPA_PHOTOS . '` SET `album` = %s WHERE `id` = %s', $move, $photo['id'] ) );

			// Move to trash?
			if ( $move > '0' ) {
				wppa_move_source( $photo['filename'], $photo['album'], $move );
			}
			if ( wppa_is_time_up() ) {
				wppa_error_message( 'Time is out. Please redo this operation' );
				wppa_invalidate_treecounts( $move );
				return;
			}

		}
		if ( $move > '0' ) {
			wppa_invalidate_treecounts( $move );
		}
	}

	// First flush treecounts, otherwise we do not know the parent if any
	wppa_invalidate_treecounts( $id );

	// Now delete the album
	$wpdb->query( $wpdb->prepare( 'DELETE FROM `' . WPPA_ALBUMS . '` WHERE `id` = %s LIMIT 1', $id ) );
	wppa_delete_album_source( $id );
	wppa_index_remove( 'album', $id );
	wppa_clear_catlist();

	$msg = __( 'Album Deleted.' , 'wp-photo-album-plus');
	if ( wppa( 'ajax' ) ) {
		echo $msg;
	}
	else {
		wppa_update_message( $msg );
	}
}

// select main photo
function wppa_main_photo($cur = '', $covertype) {
global $wpdb;

    $a_id = $_REQUEST['edit_id'];
	$photos = $wpdb->get_results($wpdb->prepare('SELECT * FROM `'.WPPA_PHOTOS.'` WHERE `album` = %s '.wppa_get_photo_order($a_id).' LIMIT 1000', $a_id), ARRAY_A);

	$output = '';
//	if ( ! empty($photos) ) {
		$output .= '<select name="wppa-main" onchange="wppaAjaxUpdateAlbum('.$a_id.', \'main_photo\', this)" >';
//		$output .= '<option value="">'.__('--- please select ---').'</option>';
		if ( $covertype == 'imagefactory' || ( $covertype == '' && wppa_opt( 'cover_type') == 'imagefactory' ) ) {
			if ( $cur == '0' ) $selected = 'selected="selected"'; else $selected = '';
			$output .= '<option value="0" '.$selected.'>'.sprintf(__('auto select max %s random', 'wp-photo-album-plus'), wppa_opt( 'imgfact_count')).'</option>';
			if ( $cur == '-1' ) $selected = 'selected="selected"'; else $selected = '';
			$output .= '<option value="-1" '.$selected.'>'.sprintf(__('auto select max %s featured', 'wp-photo-album-plus'), wppa_opt( 'imgfact_count')).'</option>';
			if ( $cur == '-2' ) $selected = 'selected="selected"'; else $selected = '';
			$output .= '<option value="-2" '.$selected.'>'.sprintf(__('max %s most recent added', 'wp-photo-album-plus'), wppa_opt( 'imgfact_count')).'</option>';
			if ( $cur == '-3' ) $selected = 'selected="selected"'; else $selected = '';
			$output .= '<option value="-3" '.$selected.'>'.sprintf(__('max %s from (grand)child albums', 'wp-photo-album-plus'), wppa_opt( 'imgfact_count')).'</option>';
			if ( $cur == '-4' ) $selected = 'selected="selected"'; else $selected = '';
			$output .= '<option value="-4" '.$selected.'>'.sprintf(__('max %s most recent from (grand)child albums', 'wp-photo-album-plus'), wppa_opt( 'imgfact_count')).'</option>';
		}
		else {
			if ( $cur == '0' ) $selected = 'selected="selected"'; else $selected = '';
			$output .= '<option value="0" '.$selected.'>'.__('--- random ---', 'wp-photo-album-plus').'</option>';
			if ( $cur == '-1' ) $selected = 'selected="selected"'; else $selected = '';
			$output .= '<option value="-1" '.$selected.'>'.__('--- random featured ---', 'wp-photo-album-plus').'</option>';
			if ( $cur == '-2' ) $selected = 'selected="selected"'; else $selected = '';
			$output .= '<option value="-2" '.$selected.'>'.__('--- most recent added ---', 'wp-photo-album-plus').'</option>';
			if ( $cur == '-3' ) $selected = 'selected="selected"'; else $selected = '';
			$output .= '<option value="-3" '.$selected.'>'.__('--- random from (grand)children ---', 'wp-photo-album-plus').'</option>';
			if ( $cur == '-4' ) $selected = 'selected="selected"'; else $selected = '';
			$output .= '<option value="-4" '.$selected.'>'.__('--- most recent from (grand)children ---', 'wp-photo-album-plus').'</option>';
		}

		if ( ! empty($photos) ) foreach($photos as $photo) {
			if ($cur == $photo['id']) {
				$selected = 'selected="selected"';
			}
			else {
				$selected = '';
			}
			$name = __(stripslashes($photo['name']), 'wp-photo-album-plus');
			if ( strlen($name) > 45 ) $name = substr($name, 0, 45).'...';
			if ( ! $name ) $name = __('Nameless, filename = ', 'wp-photo-album-plus').$photo['filename'];
			$output .= '<option value="'.$photo['id'].'" '.$selected.'>'.$name.'</option>';
		}

		$output .= '</select>';
//	} else {
//		$output = '<p>'.__('No photos yet').'</p>';
//	}
	return $output;
}



// Edit (sub)album sequence
function wppa_album_sequence( $parent ) {
global $wpdb;

	// Get the albums
	$albumorder 	= wppa_get_album_order( $parent );
	$is_descending 	= strpos( $albumorder, 'DESC' ) !== false;
	$albums 		= $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `'.WPPA_ALBUMS.'` WHERE `a_parent` = %s '.$albumorder, $parent ), ARRAY_A );

	// Anything to do here ?
	if ( empty ( $albums ) ) {
		return;
	}

	// Check my access rights
	foreach ( $albums as $album ) {
		if ( ! wppa_have_access( $album['id'] ) ) {
			return;
		}
	}

	// Check album order
	if ( ! strpos( $albumorder, 'a_order' ) ) {
		if ( $parent == '0') {
			echo '<br />';
			_e( 'You can edit top-level album sequence order here when you set the album order to "Order #" or "Order # desc" in Table IV-D1.' );
		}
		else {
			_e( 'You can edit sub-album sequence order here when you set the album order to "Order #" or "Order # desc" in the "Sub album sort order:" selection box above.' );
		}
		echo '<br />';
		return;
	}



	echo
	'<h2>' .
		__( 'Manage album order', 'wp-photo-album-plus' ) .
		' - ' .
		'<small>' .
			'<i>' .
				__( 'Change sequence order by drag and drop, or use the up/down arrows.', 'wp-photo-album-plus' ) .
			'</i>' .
			' ' .
			__( 'Do not leave this page unless the bar is entirely green.', 'wp-photo-album-plus' ) .
		'</small>' .
	'</h2>';

	echo
	'<table>' .
		'<thead>' .
			'<tr>' .
				'<th>' .
					__( 'Color', 'wp-photo-album-plus' ) .
				'</th>' .
				'<th>' .
					__( 'Meaning', 'wp-photo-album-plus' ) .
				'</th>' .
			'</tr>' .
		'</thead>' .
		'<tbody>' .
			'<tr>' .
				'<td>' .
					'<div style="background-color:green;height:12px;" ></div>' .
				'</td>' .
				'<td>' .
					__( 'Up to date', 'wp-photo-album-plus' ) .
				'</td>' .
			'</tr>' .
			'<tr>' .
				'<td>' .
					'<div style="background-color:yellow;height:12px;" ></div>' .
				'</td>' .
				'<td>' .
					__( 'Updating', 'wp-photo-album-plus' ) .
				'</td>' .
			'</tr>' .
			'<tr>' .
				'<td>' .
					'<div style="background-color:orange;height:12px;" ></div>' .
				'</td>' .
				'<td>' .
					__( 'Needs update', 'wp-photo-album-plus' ) .
				'</td>' .
			'</tr>' .
			'<tr>' .
				'<td>' .
					'<div style="background-color:red;height:12px;" ></div>' .
				'</td>' .
				'<td>' .
					__( 'Error', 'wp-photo-album-plus' ) .
				'</td>' .
			'</tr>' .
		'</tbody>' .
	'</table>';



		?>
		<style>
			.sortable-placeholder-albums {
				width: 100%;
				height: 60px;
				margin: 5px;
				border: 1px dotted #cccccc;
				border-radius:3px;
				float: left;
			}
			.ui-state-default-albums {
				position: relative;
				width: 100%;
				height: 60px;
				margin: 5px;
				border: 1px solid #cccccc;
				border-radius:3px;
				float: left;
			}
			.ui-state-default-albums td {
				padding:0;
				line-height:12px;
				text-align:center;
			}
		</style>
		<script>
			jQuery( function() {
				jQuery( "#sortable-albums" ).sortable( {
					cursor: 		"move",
					placeholder: 	"sortable-placeholder-albums",
					stop: 			function( event, ui ) { wppaDoRenumber(); }
				} );
			} );
			var wppaRenumberPending = false;
			var wppaAjaxInProgress 	= 0;

			function wppaDoRenumber() {

				// Busy?
				if ( wppaAjaxInProgress > 0 ) {
					wppaRenumberPending = true;
				}

				// Not busy
				else {
					_wppaDoRenumber();
				}
			}

			function _wppaDoRenumber() {

				// Init
				var ids = jQuery( ".wppa-sort-item-albums" );
				var seq = jQuery( ".wppa-sort-seqn-albums" );
				var descend = <?php if ( $is_descending ) echo 'true'; else echo 'false' ?>;

				// Mark needs update
				var idx = 0;
				while ( idx < ids.length ) {
					var newvalue;
					if ( descend ) newvalue = ids.length - idx;
					else newvalue = idx + 1;
					var oldvalue = seq[idx].value;
					var album = ids[idx].value;
					if ( newvalue != oldvalue ) {
						jQuery( '#wppa-pb-'+idx ).css({backgroundColor:'orange'});
					}
					idx++;
				}

				// Process
				var idx = 0;
				while ( idx < ids.length ) {
					var newvalue;
					if ( descend ) newvalue = ids.length - idx;
					else newvalue = idx + 1;
					var oldvalue = seq[idx].value;
					var album = ids[idx].value;
					if ( newvalue != oldvalue ) {
						wppaDoSeqUpdateAlbum( album, newvalue );
						jQuery( '#wppa-pb-'+idx ).css({backgroundColor:'yellow'});
						wppaLastAlbum = album;
					}
					idx++;
				}
			}

			function wppaDoSeqUpdateAlbum( album, seqno ) {

				var data = 	'action=wppa' +
							'&wppa-action=update-album' +
							'&album-id=' + album +
							'&item=a_order' +
							'&wppa-nonce=' + document.getElementById( 'album-nonce-' + album ).value +
							'&value=' + seqno;
				var xmlhttp = new XMLHttpRequest();

				xmlhttp.onreadystatechange = function() {
					if ( xmlhttp.readyState == 4 && xmlhttp.status != 404 ) {
						var ArrValues = xmlhttp.responseText.split( "||" );
						if ( ArrValues[0] != '' ) {
							alert( 'The server returned unexpected output:\n' + ArrValues[0] );
						}
						switch ( ArrValues[1] ) {
							case '0':	// No error
								var i = seqno - 1;
								var descend = <?php if ( $is_descending ) echo 'true'; else echo 'false' ?>;
								if ( descend ) {
									i = <?php echo count( $albums ) ?> - seqno;
								}
								jQuery( '#wppa-album-seqno-' + album ).html( seqno );
								if ( wppaRenumberPending ) {
									jQuery( '#wppa-pb-'+i ).css({backgroundColor:'orange'});
								}
								else {
									jQuery( '#wppa-pb-'+i ).css({backgroundColor:'green'});
								}
								if ( wppaLastAlbum = album ) {
									wppaRenumberBusy = false;
								}
								break;
							default:	// Any error
								jQuery( '#wppa-album-seqno-' + album ).html( '<span style="color"red" >Err:' + ArrValues[1] + '</span>' );
								break;
						}
						wppaAjaxInProgress--;

						// No longer busy?
						if ( wppaAjaxInProgress == 0 ) {

							if ( wppaRenumberPending ) {

								// Redo
								wppaRenumberPending = false;
								wppaDoRenumber();
							}
						}
					}
				}
				xmlhttp.open( 'POST',wppaAjaxUrl,true );
				xmlhttp.setRequestHeader( "Content-type","application/x-www-form-urlencoded" );
				xmlhttp.send( data );
				wppaAjaxInProgress++;

				jQuery( "#wppa-sort-seqn-albums-" + album ).attr( 'value', seqno );	// set hidden value to new value to prevent duplicate action
				var spinnerhtml = '<img src="' + wppaImageDirectory + 'spinner.' + '<?php echo ( wppa_use_svg() ? 'svg' : 'gif' ) ?>' + '" />';
				jQuery( '#wppa-album-seqno-' + album ).html( spinnerhtml );
			}
		</script>

		<br />

		<div id="wppa-progbar" style="width:100%;height:12px;" >
			<?php
				$c = count( $albums );
				$l = 100 / $c;
				$i = 0;
				while( $i < $c ) {
					echo
					'<div' .
						' id="wppa-pb-' . $i . '"' .
						' style="display:inline;float:left;background-color:green;height:12px;width:' . $l . '%;"' .
						' >' .
					'</div>';
					$i++;
				}
			?>
		</div>

		<br />

		<div class="widefat" style="max-width:600px;" >
			<div id="sortable-albums">
				<?php foreach ( $albums as $album ) {
					$cover_photo_id = wppa_get_coverphoto_id( $album['id'] );
					echo '
					<div' .
						' id="albumitem-' . $album['id'] .'"' .
						' class="ui-state-default-albums"' .
						' style="background-color:#eeeeee;cursor:move;"' .
						' >' .
						'<div' .
							' style="height:100%;width:25%;float:left;text-align:center;overflow:hidden;" >';
							if ( wppa_is_video( $cover_photo_id ) ) {
								echo
								wppa_get_video_html( array( 'id' => $cover_photo_id,
															'height' => '50',
															'margin_top' => '5',
															'margin_bottom' => '5',
															'controls' => false,
															) );
							}
							else {
								echo
								'<img' .
									' class="wppa-cover-image"' .
									' src="' . wppa_get_thumb_url( wppa_get_coverphoto_id( $album['id'] ) ) . '"' .
									' style="max-height:50px; margin: 5px;"' .
								' />';
							}
						echo
						'</div>' .
						'<div style="height:100%;width:40%;float:left;font-size:12px;overflow:hidden;" >' .
							'<b>' . wppa_get_album_name( $album['id'] ) . '</b>' .
							'<br />' .
							wppa_get_album_desc( $album['id'] ) .
						'</div>' .
						'<div style="float:right;width:10%;" >' .
							'<table>' .
								'<tr><td>' .
									'<img' .
										' src="' . wppa_get_imgdir( 'up.png' ) . '"' .
										' title="' . esc_attr( __( 'To top', 'wp-photo-album-plus' ) ) . '"' .
										' style="cursor:pointer;"' .
										' onclick="' .
											'jQuery( \'#albumitem-' . $album['id'] . '\' ).parent().prepend(jQuery( \'#albumitem-' . $album['id'] . '\' ));' .
											'wppaDoRenumber();' .
										'"' .
									' />' .
								'</td></tr>' .
								'<tr><td>' .
									'<img' .
										' src="' . wppa_get_imgdir( 'up.png' ) . '"' .
										' title="' . esc_attr( __( 'One up', 'wp-photo-album-plus' ) ) . '"' .
										' style="cursor:pointer;width:24px;"' .
										' onclick="' .
											'jQuery( \'#albumitem-' . $album['id'] . '\' ).prev().before(jQuery( \'#albumitem-' . $album['id'] . '\' ));' .
											'wppaDoRenumber();' .
										'"' .
									' />' .
								'</td></tr>' .
								'<tr><td>' .
									'<img' .
										' src="' . wppa_get_imgdir( 'down.png' ) . '"' .
										' title="' . esc_attr( __( 'One down', 'wp-photo-album-plus' ) ) . '"' .
										' style="cursor:pointer;width:24px;"' .
										' onclick="' .
											'jQuery( \'#albumitem-' . $album['id'] . '\' ).next().after(jQuery( \'#albumitem-' . $album['id'] . '\' ));' .
											'wppaDoRenumber();' .
										'"' .
									' />' .
								'</td></tr>' .
								'<tr><td>' .
									'<img' .
										' src="' . wppa_get_imgdir( 'down.png' ) . '"' .
										' title="' . esc_attr( __( 'To bottom', 'wp-photo-album-plus' ) ) . '"' .
										' style="cursor:pointer;"' .
										' onclick="' .
											'jQuery( \'#albumitem-' . $album['id'] . '\' ).parent().append(jQuery( \'#albumitem-' . $album['id'] . '\' ));' .
											'wppaDoRenumber();' .
										'"' .
									' />' .
								'</td></tr>' .
							'</table>' .
						'</div>' .
						'<div style="float:right; width:25%;" >' .
							'<span style=""> ' . __( 'Id:' , 'wp-photo-album-plus' ) . ' ' . $album['id'] . '</span>' .
							'<span style=""> - ' . __( 'Ord:' , 'wp-photo-album-plus' ) . '</span>' .
							'<span id="wppa-album-seqno-' . $album['id'] . '" > ' . $album['a_order'] . '</span>' .
							'<br />' .
							'<a href="' . wppa_ea_url( $album['id'] ) . '" style="position:absolute;bottom:0;" >' . __( 'Edit', 'wp-photo-album-plus' ) . '</a>' .
						'</div>' .
						'<input type="hidden" id="album-nonce-' . $album['id'] . '" value="' . wp_create_nonce( 'wppa_nonce_' . $album['id'] ) . '" />' .
						'<input type="hidden" class="wppa-sort-item-albums" value="' . $album['id'] . '" />' .
						'<input type="hidden" class="wppa-sort-seqn-albums" id="wppa-sort-seqn-albums-' . $album['id'] . '" value="' . $album['a_order'] . '" />' .
					'</div>';
				} ?>
			</div>
			<div style="clear:both;"></div>
		</div>
		<?php
}
