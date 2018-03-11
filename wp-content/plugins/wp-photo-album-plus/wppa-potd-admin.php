<?php
/* wppa-potd-admin.php
* Pachkage: wp-photo-album-plus
*
* admin photo of the day widget
* version 6.7.01
*
*/

function _wppa_sidebar_page_options() {
global $wpdb;
global $wppa_defaults;

	wppa_set_defaults();

	$onch = 'myReload()';

	// Handle spinner js and declare functions
	echo
	'<script type="text/javascript" >' .
		'var didsome=false;' .
		'jQuery(document).ready(function() {' .
				'jQuery(\'#wppa-spinner\').css(\'display\', \'none\');' .
			'});' .
		'function myReload() {' .
			'jQuery(\'#wppa-spinner\').css(\'display\', \'block\');' .
			'_wppaRefreshAfter = true;' .
		'};' .
		'function wppaSetFixed(id) {' .
			'if (jQuery(\'#wppa-widget-photo-\' + id).attr(\'checked\') == \'checked\' ) {' .
				'_wppaRefreshAfter = true;' .
				'wppaAjaxUpdateOptionValue(\'potd_photo\', id);' .
			'}' .
		'};' .
	'</script>';

	// The spinner
	echo
	'<img' .
		' id="wppa-spinner"' .
		' style="position:fixed;top:50%;left:50%;z-index:1000;margin-top:-33px;margin-left:-33px;display:block;"' .
		' src="' . wppa_get_imgdir( 'loader.gif' ) . '"' .
	'/>';

	// Open wrapper
	echo
	'<div class="wrap">';

		// The settings icon
		echo
		'<img src="' . wppa_get_imgdir( 'settings32.png' ) . '" />';

		// The Page title
		echo
		'<h1 style="display:inline;" >' . __( 'Photo of the Day (Widget) Settings', 'wp-photo-album-plus' ) . '</h1>' .
		__( 'Changes are updated immediately. The page will reload if required.', 'wp-photo-album-plus' ) .
		'<br />&nbsp;';

		// The nonce
		wp_nonce_field( 'wppa-nonce', 'wppa-nonce' );

		// The settings table
		echo
		'<table class="widefat wppa-table wppa-setting-table">';

			// The header
			echo
			'<thead style="font-weight: bold; " class="wppa_table_1">' .
				'<tr>' .
					'<td>' . __( '#', 'wp-photo-album-plus' ) . '</td>' .
					'<td>' . __( 'Name', 'wp-photo-album-plus' ) . '</td>' .
					'<td>' . __( 'Description', 'wp-photo-album-plus') . '</td>' .
					'<td>' . __( 'Setting', 'wp-photo-album-plus') . '</td>' .
					'<td>' . __( 'Help', 'wp-photo-album-plus' ) . '</td>' .
				'</tr>' .
			'</thead>';

			// Open the table body
			echo
			'<tbody class="wppa_table" >';

				$name = __('Widget Title:', 'wp-photo-album-plus');
				$desc = __('The title of the widget.', 'wp-photo-album-plus');
				$help = esc_js(__('Enter/modify the title for the widget. This is a default and can be overriden at widget activation.', 'wp-photo-album-plus'));
				$slug = 'wppa_potd_title';
				$html = wppa_input($slug, '85%');
				wppa_setting($slug, '1', $name, $desc, $html, $help);

				$name = __('Widget Photo Width:', 'wp-photo-album-plus');
				$desc = __('Enter the desired display width of the photo in the sidebar.', 'wp-photo-album-plus');
				$help = '';
				$slug = 'wppa_potd_widget_width';
				$html = wppa_input($slug, '40px', '', __('pixels wide', 'wp-photo-album-plus'));
				wppa_setting($slug, '2', $name, $desc, $html, $help);

				$name = __('Horizontal alignment:', 'wp-photo-album-plus');
				$desc = __('Enter the desired display alignment of the photo in the sidebar.', 'wp-photo-album-plus');
				$help = '';
				$slug = 'wppa_potd_align';
				$opts = array(__('--- none ---', 'wp-photo-album-plus'), __('left', 'wp-photo-album-plus'), __('center', 'wp-photo-album-plus'), __('right', 'wp-photo-album-plus'));
				$vals = array('none', 'left', 'center', 'right');
				$html = wppa_select($slug, $opts, $vals);
				wppa_setting($slug, '3', $name, $desc, $html, $help);

				$linktype = wppa_opt( 'potd_linktype' );
				if ( $linktype == 'custom' ) {

					$name = __('Link to:', 'wp-photo-album-plus');
					$desc = __('Enter the url. Do\'nt forget the HTTP://', 'wp-photo-album-plus');
					$help = '';
					$slug = 'wppa_potd_linkurl';
					$html = wppa_input($slug, '85%');
					wppa_setting($slug, '4', $name, $desc, $html, $help);

					$name = __('Link Title:', 'wp-photo-album-plus');
					$desc = __('The balloon text when hovering over the photo.', 'wp-photo-album-plus');
					$help = '';
					$slug = 'wppa_potd_linktitle';
					$html = wppa_input($slug, '85%');
					wppa_setting($slug, '4a', $name, $desc, $html, $help);

				}
				else {
					$name = __('Link to:', 'wp-photo-album-plus');
					$desc = __('Links are set on the <b>Photo Albums -> Settings</b> screen.', 'wp-photo-album-plus');
					$help = '';
					$slug = 'wppa_potd_linkurl';
					$html = '';
					wppa_setting($slug, '4', $name, $desc, $html, $help);
				}

				$name = __('Subtitle:', 'wp-photo-album-plus');
				$desc = __('Select the content of the subtitle.', 'wp-photo-album-plus');
				$help = '';
				$slug = 'wppa_potd_subtitle';
				$opts = array( 	__('--- none ---', 'wp-photo-album-plus'),
								__('Photo Name', 'wp-photo-album-plus'),
								__('Description', 'wp-photo-album-plus'),
								__('Owner', 'wp-photo-album-plus')
							);
				$vals = array( 'none', 'name', 'desc', 'owner' );
				$html = wppa_select($slug, $opts, $vals);
				wppa_setting($slug, '5', $name, $desc, $html, $help);

				$name = __('Counter:', 'wp-photo-album-plus');
				$desc = __('Display a counter of other photos in the album.', 'wp-photo-album-plus');
				$help = '';
				$slug = 'wppa_potd_counter';
				$html = wppa_checkbox($slug);
				wppa_setting($slug, '6', $name, $desc, $html, $help);

				$name = __('Link to:', 'wp-photo-album-plus');
				$desc = __('The counter links to.', 'wp-photo-album-plus');
				$help = '';
				$slug = 'wppa_potd_counter_link';
				$opts = array(__( 'thumbnails', 'wp-photo-album-plus' ), __( 'slideshow', 'wp-photo-album-plus' ), __('single image', 'wp-photo-album-plus'));
				$vals = array( 'thumbs', 'slide', 'single' );
				$html = wppa_select($slug, $opts, $vals);
				wppa_setting($slug, '7', $name, $desc, $html, $help);

				$name = __('Type of album(s) to use:', 'wp-photo-album-plus');
				$desc = __('Select physical or virtual.', 'wp-photo-album-plus');
				$help = '';
				$slug = 'wppa_potd_album_type';
				$opts = array(__('physical albums', 'wp-photo-album-plus'), __('virtual albums', 'wp-photo-album-plus'));
				$vals = array('physical', 'virtual');
				$html = wppa_select($slug, $opts, $vals, $onch);
				wppa_setting($slug, '8', $name, $desc, $html, $help);

				$name = __('Albums to use:', 'wp-photo-album-plus');
				$desc = __('Select the albums to use for the photo of the day.', 'wp-photo-album-plus');
				$help = '';
				$slug = 'wppa_potd_album';
				if ( get_option( 'wppa_potd_album_type' ) == 'physical' ) {
					$html = '<select' .
								' id="wppa_potd_album"' .
								' name="wppa_potd_album"' .
								' style="float:left; max-width: 400px; height: auto !important;"' .
								' multiple="multiple"' .
								' onchange="didsome=true;wppaAjaxUpdateOptionValue(\'potd_album\', this, true)"' .
							//	' onblur="document.location.reload(true);"' .
								' onmouseout="if(didsome)document.location.reload(true);"' .
								' size="10"' .
								' >' .
								wppa_album_select_a( array ( 	'path' 				=> true,
																'optionclass' 		=> 'potd_album',
																'selected' 			=> get_option( 'wppa_potd_album' ),
													) ) .
							'</select>' .
							'<img id="img_potd_album" class="" src="'.wppa_get_imgdir().'star.ico" title="'.__('Setting unmodified', 'wp-photo-album-plus').'" style="padding:0 4px; float:left; height:16px; width:16px;" />';
					wppa_setting($slug, '9', $name, $desc, $html, $help);
				}
				else {
					$desc = __('Select the albums to use for the photo of the day.', 'wp-photo-album-plus');
					$opts = array( 	__( '- all albums -' , 'wp-photo-album-plus' ),
									__( '- all -separate- albums -' , 'wp-photo-album-plus' ),
									__( '- all albums except -separate-' , 'wp-photo-album-plus' ),
									__( '- top rated photos -' , 'wp-photo-album-plus' ),
								);
					$vals =	array( 'all', 'sep', 'all-sep', 'topten' );
					$html = wppa_select($slug, $opts, $vals);
					wppa_setting($slug, '9', $name, $desc, $html, $help);
				}

				if ( get_option( 'wppa_potd_album_type' ) == 'physical' ) {
					$name = __('Include (grand)children:', 'wp-photo-album-plus');
					$desc = __('Include the photos of all sub albums?', 'wp-photo-album-plus');
					$help = '';
					$slug = 'wppa_potd_include_subs';
					$html = wppa_checkbox($slug, $onch);
					wppa_setting($slug, '9a', $name, $desc, $html, $help);

					$name = __('Inverse selection:', 'wp-photo-album-plus');
					$desc = __('Use any album, except the selection made above.', 'wp-photo-album-plus');
					$help = '';
					$slug = 'wppa_potd_inverse';
					$html = wppa_checkbox($slug, $onch);
					wppa_setting($slug, '9b', $name, $desc, $html, $help);
				}

				$name = __('Status filter:', 'wp-photo-album-plus');
				$desc = __('Use only photos with a certain status.', 'wp-photo-album-plus');
				$help = esc_js(__('Select - none - if you want no filtering on status.', 'wp-photo-album-plus'));
				$slug = 'wppa_potd_status_filter';
				$opts = array(	__('- none -', 'wp-photo-album-plus'),
								__( 'Publish' , 'wp-photo-album-plus'),
								__( 'Featured' , 'wp-photo-album-plus'),
								__( 'Gold' , 'wp-photo-album-plus'),
								__( 'Silver' , 'wp-photo-album-plus'),
								__( 'Bronze' , 'wp-photo-album-plus'),
								__( 'Any medal' , 'wp-photo-album-plus'),
							);
				$vals = array(	'none',
								'publish',
								'featured',
								'gold',
								'silver',
								'bronze',
								'anymedal',
							);
				$html = wppa_select($slug, $opts, $vals);
				wppa_setting($slug, '10', $name, $desc, $html, $help);

				$name = __('Display method:', 'wp-photo-album-plus');
				$desc = __('Select the way a photo will be selected.', 'wp-photo-album-plus');
				$help = '';
				$slug = 'wppa_potd_method';
				$opts = array(	__('Fixed photo', 'wp-photo-album-plus'),
								__('Random', 'wp-photo-album-plus'),
								__('Last upload', 'wp-photo-album-plus'),
								__('Change every', 'wp-photo-album-plus'),
							);
				$vals = array( '1', '2', '3', '4', );
				$html = wppa_select($slug, $opts, $vals, $onch);
				wppa_setting($slug, '11', $name, $desc, $html, $help);

				if ( get_option( 'wppa_potd_method' ) == '4' ) { // Change every
					$name = __('Change every period:', 'wp-photo-album-plus');
					$desc = __('The time period a certain photo is used.', 'wp-photo-album-plus');
					$help = '';
					$slug = 'wppa_potd_period';
					$opts = array( 	__('pageview.', 'wp-photo-album-plus'),
									__('hour.', 'wp-photo-album-plus'),
									__('day.', 'wp-photo-album-plus'),
									__('week.', 'wp-photo-album-plus'),
									__('month.', 'wp-photo-album-plus'),
									__('day of week is order#', 'wp-photo-album-plus'),
									__('day of month is order#', 'wp-photo-album-plus'),
									__('day of year is order#', 'wp-photo-album-plus')
							);
					$vals = array( '0', '1', '24', '168', '736', 'day-of-week', 'day-of-month', 'day-of-year' );
					$html = wppa_select($slug, $opts, $vals, $onch);
					wppa_setting($slug, '11a', $name, $desc, $html, $help);

					$wppa_widget_period = get_option( 'wppa_potd_period' );
					if ( substr( $wppa_widget_period, 0, 7 ) == 'day-of-' ) {
						switch( substr( $wppa_widget_period, 7 ) ) {
							case 'week':
								$n_days = '7';
								$date_key = 'w';
								break;
							case 'month':
								$n_days = '31';
								$date_key = 'd';
								break;
							case 'year':
								$n_days = '366';
								$date_key = 'z';
								break;
						}
						while ( get_option( 'wppa_potd_offset', '0' ) > $n_days ) update_option( 'wppa_potd_offset', get_option( 'wppa_potd_offset') - $n_days );
						while ( get_option( 'wppa_potd_offset', '0' ) < '0' ) update_option( 'wppa_potd_offset', get_option( 'wppa_potd_offset') + $n_days );

						$name = __('Day offset:', 'wp-photo-album-plus');
						$desc = __('The difference between daynumber and photo order number.', 'wp-photo-album-plus');
						$help = '';
						$slug = 'wppa_potd_offset';
						$opts = array();
						$day = '0';
						while ( $day < $n_days ) {
							$opts[] = $day;
							$day++;
						}
						$vals = $opts;
						$html = 	'<span style="float:left;" >' .
										sprintf( __('Current day# = %s, offset =', 'wp-photo-album-plus'), wppa_local_date( $date_key ) ) .
									'</span> ' .
									wppa_select($slug, $opts, $vals, $onch);

									$photo_order = wppa_local_date( $date_key ) - get_option( 'wppa_potd_offset', '0' );
									while ( $photo_order < '0' ) {
										$photo_order += $n_days;
									}

						$html .= 	sprintf( __('Todays photo order# = %s.', 'wp-photo-album-plus'), $photo_order );
						wppa_setting($slug, '11b', $name, $desc, $html, $help);

					}
				}

				$name = __('Preview', 'wp-photo-album-plus');
				$desc = __('Current "photo of the day":', 'wp-photo-album-plus');
				$help = '';
				$slug = 'wppa_potd_photo';
				$photo = wppa_get_potd();
				if ( $photo ) {
					$html = '<div style="display:inline-block;width:25%;text-align:center;vertical-align:middle;">' .
								'<img src="' . wppa_get_thumb_url( $photo['id'] ) . '" />' .
							'</div>' .
							'<div style="display:inline-block;width:75%;text-align:center;vertical-align:middle;" >' .
								__('Album', 'wp-photo-album-plus') . ': ' . wppa_get_album_name( $photo['album'] ) .
								'<br />' .
								__('Uploader', 'wp-photo-album-plus') . ': ' . $photo['owner'] .
							'</div>';

				}
				else {
					$html = __('Not found.', 'wp-photo-album-plus');
				}
				wppa_setting($slug, '12', $name, $desc, $html, $help);

				$name = __('Show selection', 'wp-photo-album-plus');
				$desc = __('Show the photos in the current selection.', 'wp-photo-album-plus');
				$help = '';
				$slug = 'wppa_potd_preview';
				$html = wppa_checkbox($slug, $onch);
				wppa_setting($slug, '13', $name, $desc, $html, $help);

			// Cose table body
			echo
			'</tbody>';

			// Table footer
			echo
			'<tfoot style="font-weight: bold;" >' .
				'<tr>' .
					'<td>' . __( '#', 'wp-photo-album-plus' ) . '</td>' .
					'<td>' . __( 'Name', 'wp-photo-album-plus' ) . '</td>' .
					'<td>' . __( 'Description', 'wp-photo-album-plus') . '</td>' .
					'<td>' . __( 'Setting', 'wp-photo-album-plus') . '</td>' .
					'<td>' . __( 'Help', 'wp-photo-album-plus' ) . '</td>' .
				'</tr>' .
			'</tfoot>' .
		'</table>';

		// Diagnostic
//		echo
//		'Diagnostic: wppa_potd_album = ' . get_option( 'wppa_potd_album' ) . ' wppa_potd_photo = ' . get_option( 'wppa_potd_photo' );

		// Status star must be here for js
		echo
		'<img' .
			' id="img_potd_photo"' .
			' src="' . wppa_get_imgdir( 'star.ico' ) . '" style="height:12px;display:none;"' .
		' />';

		// The potd photo pool
		echo
		'<table class="widefat wppa-table wppa-setting-table" >';

			// Table header
			echo
			'<thead>' .
				'<tr>' .
					'<td>' .
						__( 'Photos in the current selection', 'wp-photo-album-plus' ) .
					'</td>' .
				'</tr>' .
			'</thead>';

			// Table body
			if ( wppa_switch( 'potd_preview' ) ) {
				echo
				'<tbody>' .
					'<tr>' .
						'<td>';

							// Get the photos
							$alb 	= wppa_opt( 'potd_album' );
							$opt 	= wppa_is_int( $alb ) ? ' ' . wppa_get_photo_order( $alb ) . ' ' : '';
							$photos = wppa_get_widgetphotos( $alb, $opt );

							// Count them
							$cnt 	= count( $photos );

							// Find current
							$curid 	= wppa_opt( 'potd_photo' );

							// See if we do this
							if ( empty( $photos ) ) {
								_e( 'No photos in the selection', 'wp-photo-album-plus' );
							}
							elseif ( $cnt > '5000' ) {
								echo sprintf( __( 'There are too many photos in the selection to show a preview ( %d )', 'wp-photo-album-plus' ), $cnt );
							}
							else {

								// Yes, display the pool
								foreach ( $photos as $photo ) {
									$id = $photo['id'];

									// Open container div
									echo
									'<div' .
										' class="photoselect"' .
										' style="' .
											'width:180px;' .
											'height:300px;' .
										'" >';

										// Open image container div
										echo
										'<div' .
											' style="' .
												'width:180px;' .
												'height:135px;' .
												'overflow:hidden;' .
												'text-align:center;' .
											'" >';

											// The image if a video
											if ( wppa_is_video( $id ) ) {
												echo wppa_get_video_html( array( 	'id' 		=> $id,
																					'style' 	=> 'width:180px;'
																		));
											}

											// The image if a photo
											else {
												echo '<img' .
														' src=" '. wppa_get_thumb_url( $id ) . '"' .
														' style="' .
															'max-width:180px;' .
															'max-height:135px;' .
															'margin:auto;' .
															'"' .
														' alt="' . esc_attr( wppa_get_photo_name( $id ) ) .'" />';

												// Audio ?
												if ( wppa_has_audio( $id ) ) {
													echo wppa_get_audio_html( array( 	'id' 		=> 	$id,
																						'style' 	=> 	'width:180px;' .
																										'position:relative;' .
																										'bottom:' . ( wppa_get_audio_control_height() + 4 ) .'px;'
																			));
												}
											}

										// Close image container div
										echo
										'</div>';

										// The order# and select radio box
										echo
										'<div style="clear:both;width:100%;margin:3px 0;position:relative;top:5px;" >' .
											'<div style="font-size:9px; line-height:10px;float:left;">(#' . $photo['p_order'] . ')</div>';

											if ( get_option( 'wppa_potd_method' ) == '1' ) { 	// Only if fixed photo
												echo
												'<input' .
													' style="float:right;"' .
													' type="radio"' .
													' name="wppa-widget-photo"' .
													' id="wppa-widget-photo-' . $id . '"' .
													' value="' . $id . '"' .
													( $id == $curid  ? 'checked="checked"' : '' ) .
													' onchange="wppaSetFixed(' . $id . ');"' .
												' />';
											}

										echo
										'</div>';

										// The name/desc boxecho
										echo
										'<div style="clear:both;overflow:hidden;height:150px;position:relative;top:10px;" >' .
											'<div style="font-size:11px; overflow:hidden;">' .
												wppa_get_photo_name( $id ) .
											'</div>' .
											'<div style="font-size:9px; line-height:10px;">' .
												wppa_get_photo_desc( $id ) .
											'</div>' .
										'</div>';

									// Close container
									echo
									'</div>';
								}
								echo
								'<div class="clear"></div>';
							}

						// Close the table
						echo
						'</td>' .
					'</tr>' .
				'</tbody>';
			}
		echo
		'</table>';

	// Close wrap
	echo
	'</div>';
}

// The functions below this line are different from the ones with the same names in the Settings page!!!
function wppa_setting( $slug, $num, $name, $desc, $html, $help) {
global $wppa_status;
global $wppa_defaults;
global $wppa_table;
global $wppa_subtable;
global $no_default;
global $wppa_opt;

	if ( is_array($slug) ) $slugs = $slug;
	else {
		$slugs = false;
		if ( $slug ) $slugs[] = $slug;
	}
	if ( is_array($html) ) $htmls = $html;
	else {
		$htmls = false;
		if ( $html ) $htmls[] = $html;
	}
	if ( strpos($num, ',') !== false ) {
		$nums = explode(',', $num);
		$nums[0] = substr($nums[0], 1);
	}
	else {
		$nums = false;
		if ( $num ) $nums[] = $num;
	}

	// Build the html
	$result = "\n";
	$result .= '<tr id="potd-setting" class="" style="color:#333;">';
	$result .= '<td>'.$num.'</td>';
	$result .= '<td>'.$name.'</td>';
	$result .= '<td><small>'.$desc.'</small></td>';
	if ( $htmls ) foreach ( $htmls as $html ) {
		$result .= '<td>'.$html.'</td>';
	}
	else {
		$result .= '<td></td>';
	}

	if ( $help || ( defined( 'WP_DEBUG') && WP_DEBUG ) ) {
		$is_dflt = true;
		$hlp = esc_js($name).':\n\n'.$help;
		if ( ! $no_default ) {
			if ( $slugs ) {
				$hlp .= '\n\n'.__('The default for this setting is:', 'wp-photo-album-plus');
				if ( count($slugs) == 1) {
					if ( $slugs[0] != '' ) {
						$hlp .= ' '.esc_js(wppa_dflt($slugs[0]));
						if ( $wppa_opt[$slugs[0]] != $wppa_defaults[$slugs[0]] ) {
							$is_dflt = false;
						}
					}
				}
				else foreach ( array_keys($slugs) as $slugidx ) {
					if ( $slugs[$slugidx] != '' && isset($nums[$slugidx]) ) $hlp .= ' '.$nums[$slugidx].'. '.esc_js(wppa_dflt($slugs[$slugidx]));
					if ( $slugs[$slugidx] != '' && $wppa_opt[$slugs[$slugidx]] != $wppa_defaults[$slugs[$slugidx]] ) {
						$is_dflt = false;
					}
				}
			}
		}
		$result .= '<td><input type="button" style="font-size: 11px; height:20px; padding:0; cursor: pointer;" title="'.__('Click for help', 'wp-photo-album-plus').'" onclick="alert('."'".$hlp."'".')" value="&nbsp;' . ( $is_dflt ? '?' : '!' ) . '&nbsp;"></td>';
	}
	else {
		$result .= '<td></td>';//$hlp = __('No help available');
	}

	$result .= '</tr>';

	echo $result;

}


function wppa_dflt($slug) {
global $wppa_defaults;
global $no_default;

	if ( $slug == '' ) return '';
	if ( $no_default ) return '';

	$dflt = isset( $wppa_defaults[$slug] ) ? $wppa_defaults[$slug] : '';

	$dft = $dflt;
	switch ($dflt) {
		case 'yes': 	$dft .= ': '.__('Checked', 'wp-photo-album-plus'); break;
		case 'no': 		$dft .= ': '.__('Unchecked', 'wp-photo-album-plus'); break;
		case 'none': 	$dft .= ': '.__('no link at all.', 'wp-photo-album-plus'); break;
		case 'file': 	$dft .= ': '.__('the plain photo (file).', 'wp-photo-album-plus'); break;
		case 'photo': 	$dft .= ': '.__('the full size photo in a slideshow.', 'wp-photo-album-plus'); break;
		case 'single': 	$dft .= ': '.__('the fullsize photo on its own.', 'wp-photo-album-plus'); break;
		case 'indiv': 	$dft .= ': '.__('the photo specific link.', 'wp-photo-album-plus'); break;
		case 'album': 	$dft .= ': '.__('the content of the album.', 'wp-photo-album-plus'); break;
		case 'widget': 	$dft .= ': '.__('defined at widget activation.', 'wp-photo-album-plus'); break;
		case 'custom': 	$dft .= ': '.__('defined on widget admin page.', 'wp-photo-album-plus'); break;
		case 'same': 	$dft .= ': '.__('same as title.', 'wp-photo-album-plus'); break;
		default:
	}

	return $dft;
}

function wppa_checkbox($xslug, $onchange = '', $class = '') {
global $wppa_defaults;
global $wppa_opt;

	$slug = substr( $xslug, 5 );
	$tit = __('Slug =', 'wp-photo-album-plus').' '.$xslug."\n".__('Values = yes, no', 'wp-photo-album-plus');
	$title = wppa_switch( 'enable_shortcode_wppa_set' ) ? ' title="'.esc_attr( $tit ).'"' : '';
	$html = '<input style="float:left; height: 15px; margin: 0px; padding: 0px;" type="checkbox" id="'.$slug.'"'.$title;
	if ( wppa_switch( $slug ) ) $html .= ' checked="checked"';
	if ($onchange != '') $html .= ' onchange="'.$onchange.';wppaAjaxUpdateOptionCheckBox(\''.$slug.'\', this)"';
	else $html .= ' onchange="wppaAjaxUpdateOptionCheckBox(\''.$slug.'\', this)"';

	if ($class != '') $html .= ' class="'.$class.'"';
	$html .= ' /><img id="img_'.$slug.'" src="'.wppa_get_imgdir().'star.ico" title="'.__('Setting unmodified', 'wp-photo-album-plus').'" style="padding-left:4px; float:left; height:16px; width:16px;"';
	if ($class != '') $html .= ' class="'.$class.'"';
	$html .= ' />';

	if ( substr( $onchange, 0, 10 ) == 'wppaFollow' ) {
		$html .= '<script type="text/javascript" >jQuery(document).ready(function(){'.$onchange.'})</script>';
	}

	return $html;
}

function wppa_input($xslug, $width, $minwidth = '', $text = '', $onchange = '', $placeholder = '') {
global $wppa_opt;

	$slug = substr( $xslug, 5 );
	$tit = __('Slug =', 'wp-photo-album-plus').' '.$xslug;
	$title = wppa_switch( 'enable_shortcode_wppa_set' ) ? ' title="'.esc_attr( $tit ).'"' : '';
	$val = isset ( $wppa_opt[ $xslug ] ) ? esc_attr( $wppa_opt[ $xslug ] ) : get_option( $xslug, '' );
	$html = '<input'.$title.' style="float:left; width: '.$width.'; height:20px;';
	if ($minwidth != '') $html .= ' min-width:'.$minwidth.';';
	$html .= ' font-size: 11px; margin: 0px; padding: 0px;" type="text" id="'.$slug.'"';
	if ($onchange != '') $html .= ' onchange="'.$onchange.';wppaAjaxUpdateOptionValue(\''.$slug.'\', this)"';
	else $html .= ' onchange="wppaAjaxUpdateOptionValue(\''.$slug.'\', this)"';
	if ( $placeholder ) $html .= ' placeholder="'.$placeholder.'"';
	$html .= ' value=" '.$val.'" />';
	$html .= '<img id="img_'.$slug.'" src="'.wppa_get_imgdir().'star.ico" title="'.__('Setting unmodified', 'wp-photo-album-plus').'" style="padding:0 4px; float:left; height:16px; width:16px;" />';
	$html .= '<span style="float:left">'.$text.'</span>';

	return $html;
}

function wppa_select($xslug, $options, $values, $onchange = '', $class = '', $first_disable = false, $postaction = '', $max_width = '220' ) {
global $wppa_opt;

	$slug = substr( $xslug, 5 );

	$tit = __('Slug =', 'wp-photo-album-plus').' '.$xslug."\n".__('Values = ', 'wp-photo-album-plus');
	foreach( $values as $val ) $tit.= $val.', ';
	$tit = trim( $tit, ', ');
	$title = wppa_switch( 'enable_shortcode_wppa_set' ) ? ' title="'.esc_attr( $tit ).'"' : '';

	$html = '<select style="float:left; font-size: 11px; height: 20px; margin: 0px; padding: 0px; max-width:'.$max_width.'px;" id="'.$slug.'"'.$title;
	$html .= ' onchange="'.$onchange.';wppaAjaxUpdateOptionValue(\''.$slug.'\', this);'.$postaction.'"';

	if ($class != '') $html .= ' class="'.$class.'"';
	$html .= '>';

	$val = get_option( $xslug ); // value can be yes or no in Table 0 !! so do not use wppa_opt()
	$idx = 0;
	$cnt = count($options);
	while ($idx < $cnt) {
		$html .= "\n";
		$html .= '<option value="'.$values[$idx].'" ';
		$dis = false;
		if ($idx == 0 && $first_disable) $dis = true;
		$opt = trim($options[$idx], '|');
		if ($opt != $options[$idx]) $dis = true;
		if ($val == $values[$idx]) $html .= ' selected="selected"';
		if ($dis) $html .= ' disabled="disabled"';
		$html .= '>'.$opt.'</option>';
		$idx++;
	}
	$html .= '</select>';
	$html .= '<img id="img_'.$slug.'" class="'.$class.'" src="'.wppa_get_imgdir().'star.ico" title="'.__('Setting unmodified', 'wp-photo-album-plus').'" style="padding:0 4px; float:left; height:16px; width:16px;" />';

	return $html;
}

require_once ('wppa-widget-functions.php');
