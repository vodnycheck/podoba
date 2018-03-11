<?php
/* wppa-slideshow.php
* Package: wp-photo-album-plus
*
* Contains all the slideshow high level functions
* Version 6.7.10
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

function wppa_the_slideshow() {

	wppa_prepare_slideshow_pagination();

	if ( wppa_opt( 'pagelink_pos' ) == 'top' || wppa_opt( 'pagelink_pos' ) == 'both' ) wppa_slide_page_links();

	if ( wppa_switch( 'split_namedesc' ) ) {
		$indexes = explode( ',', wppa_opt( 'slide_order_split' ) );
		$i = '0';
		while ( $i < '12' ) {
			switch ( $indexes[$i] ) {
				case '0':
					wppa_start_stop('optional');				// The 'Slower | start/stop | Faster' bar
					break;
				case '1':
					wppa_slide_frame();						// The photo / slide
					break;
				case '2':
					wppa_slide_name_box('optional');		// Show name in a box.
					break;
				case '3':
					wppa_slide_desc_box('optional');		// Show description in a box.
					break;
				case '4':
					wppa_slide_custom('optional');			// Custom box
					break;
				case '5':
					wppa_slide_rating('optional');			// Rating box
					break;
				case '6':
					wppa_slide_filmstrip('optional');		// Show Filmstrip
					break;
				case '7':
					wppa_browsebar('optional');				// The 'Previous photo | Photo n of m | Next photo' bar
					break;
				case '8':
					wppa_comments('optional');				// The Comments box
					break;
				case '9':
					wppa_iptc('optional');					// The IPTC box
					break;
				case '10':
					wppa_exif('optional');					// The EXIF box
					break;
				case '11':
					wppa_share('optional');					// The Share box
					break;
				default:
					break;
			}
			$i++;
		}
	}
	else {
		$indexes = explode( ',', wppa_opt( 'slide_order' ) );
		$i = '0';
		while ( $i < '11' ) {
			switch ( $indexes[$i] ) {
				case '0':
					wppa_start_stop('optional');				// The 'Slower | start/stop | Faster' bar
					break;
				case '1':
					wppa_slide_frame();						// The photo / slide
					break;
				case '2':
					wppa_slide_name_desc('optional');		// Show name and description in a box.
					break;
				case '3':
					wppa_slide_custom('optional');			// Custom box
					break;
				case '4':
					wppa_slide_rating('optional');			// Rating box
					break;
				case '5':
					wppa_slide_filmstrip('optional');		// Show Filmstrip
					break;
				case '6':
					wppa_browsebar('optional');				// The 'Previous photo | Photo n of m | Next photo' bar
					break;
				case '7':
					wppa_comments('optional');				// The Comments box
					break;
				case '8':
					wppa_iptc('optional');					// The IPTC box
					break;
				case '9':
					wppa_exif('optional');					// The EXIF box
					break;
				case '10':
					wppa_share('optional');					// The Share box
					break;
				default:
					break;
			}
			$i++;
		}
	}
	if ( wppa_opt( 'pagelink_pos' ) == 'bottom' || wppa_opt( 'pagelink_pos' ) == 'both' ) wppa_slide_page_links();
}

function wppa_prepare_slideshow_pagination() {
global $thumbs;
global $thumbs_ids;

	wppa( 'ss_pag', false );

	// Save thumb ids of full selection
	$thumbs_ids = array();
	if ( $thumbs ) foreach ( $thumbs as $t ) {
		$thumbs_ids[] = $t['id'];
	}

	// See if slideonly max is appliccable
	if ( wppa( 'is_slideonly' ) || wppa( 'is_slideonlyf' ) ) {
		if ( wppa_opt( 'slideonly_max' ) ) {
			$pagsiz = wppa_opt( 'slideonly_max' );
		}
		else {
			return;
		}
	}

	// Not slideonly
	else {

		// Page size defined?
		if ( ! wppa_opt( 'slideshow_pagesize' ) ) return;

		// Not in a widget!
		if ( wppa_in_widget() ) return;

		// Fits in one page?
		$pagsiz = wppa_opt( 'slideshow_pagesize' );
		if ( count( $thumbs ) <= $pagsiz ) return;
	}

	// Pagination on and required
	wppa( 'ss_pag', true );
	$nslides = count( $thumbs );
	wppa( 'npages', ceil( $nslides / $pagsiz ) );

	// Assume page = 1
	wppa( 'curpage', '1' );

	// If a page is requested, find it
	$pagreq = wppa_get_get( 'page' );
	if ( is_numeric( $pagreq ) && $pagreq > '0' ) {
		wppa( 'curpage', $pagreq );
	}

	// If a photo requested, find the page where its on
	elseif ( wppa( 'start_photo' ) ) {
		$first = true;
		foreach ( array_keys( $thumbs ) as $key ) {
		if ( $first ) { wppa_dbg_msg('First index = '.$key); $first = false; }
			if ( $thumbs[$key]['id'] == wppa( 'start_photo' ) ) {
				wppa( 'curpage', floor( $key / $pagsiz ) + '1' );
				wppa_dbg_msg('Startphoto is on page #'.wppa( 'curpage' ) );
				if ( wppa_opt( 'slideshow_pagesize' ) == wppa_opt( 'thumb_page_size' ) ) {
					wppa_out( '<script>wppaPageArg="&wppa-page=' . wppa( 'curpage' ) . ';"</script>' );
				}
			}
		}
	}

	// Filmstrip assumes array $thumbs to start at index 0.
	// We shift the req'd part down to the beginning and unset the rest
	$skips = ( wppa( 'curpage' ) - '1' ) * $pagsiz;
	wppa_dbg_msg('Skips = '.$skips);
	foreach ( array_keys( $thumbs ) as $key ) {
		if ( $key < $pagsiz ) {
			if ( isset( $thumbs[$key + $skips]) ) {
				if ( $skips ) $thumbs[$key] = $thumbs[$key + $skips];
			}
			else unset( $thumbs[$key] );	// last page < pagesize
		}
		else unset ( $thumbs[$key] );
	}
	wppa_dbg_msg('Thumbs has '.count($thumbs).' elements.');
}

function wppa_slide_page_links() {
global $thumbs;

	if ( ! wppa( 'ss_pag' ) ) return;	// No pagination
	if ( wppa( 'is_slideonly' ) || wppa( 'is_slideonlyf' ) ) return; // Not on slideonly

	wppa_page_links( wppa( 'npages' ), wppa( 'curpage' ), true );

}

function wppa_get_navigation_type() {
	switch( wppa_opt( 'navigation_type' ) ) {
		case 'icons':
			return 'icons';
			break;
		case 'iconsmobile':
			if ( wppa_is_mobile() ) {
				return 'icons';
			}
			else {
				return 'text';
			}
		case 'text':
			return 'text';
		default:
			return 'icons';
	}
}
function wppa_start_stop( $opt = '' ) {
	if ( wppa_get_navigation_type() == 'icons' ) {
		wppa_start_stop_icons( $opt );
	}
	else {
		wppa_start_stop_text( $opt );
	}
}
function wppa_start_stop_icons( $opt = '' ) {

	if ( is_feed() ) return;	// Not in a feed

	// A single image slideshow needs no navigation
	if ( wppa( 'is_single' ) ) return;
	if ( wppa( 'is_filmonly' ) ) return;

	// we always need the js part for the functionality (through filmstrip etc).
	// so if not wanted: hide it
	$hide = 'display:none; '; // assume hide
	if ( $opt != 'optional' ) $hide = '';														// not optional: show
	if ( wppa_switch( 'show_startstop_navigation' ) && ! wppa( 'is_slideonly' ) ) $hide = '';	// we want it

	if ( wppa_opt( 'start_slide' ) || wppa_in_widget() ) {
		wppa_add_js_page_data( "\n" . '<script type="text/javascript">' );
		wppa_add_js_page_data( "\n" . 'wppaSlideInitRunning['.wppa( 'mocc' ).'] = true;' );
		wppa_add_js_page_data( "\n" . 'wppaMaxOccur = '.wppa( 'mocc' ).';' );
		wppa_add_js_page_data( "\n" . '</script>' );
	}

	if ( ! $hide ) {
		wppa_out( 	'<div' .
						' id="prevnext1-'.wppa( 'mocc' ).'"' .
						' class="wppa-box wppa-nav wppa-nav-text"' .
						' style="text-align:center;'.wppa_wcs('wppa-box').wppa_wcs('wppa-nav').wppa_wcs('wppa-nav-text').$hide.'"' .
						' >' .
						'<span' .
							' id="speed0-'.wppa( 'mocc' ).'"' .
							' class="wppa-nav-text speed0"' .
							' style="'.wppa_wcs('wppa-nav-text').'"' .
							' title="' . __('Slower', 'wp-photo-album-plus') . '"' .
							' onclick="wppaSpeed('.wppa( 'mocc' ).', false); return false;"' .
							' >' .
							wppa_get_svghtml( 'Snail', '1.5em' ) .
						'</span>' .
						' ' .
						'<span' .
							' id="startstop-'.wppa( 'mocc' ).'"' .
							' class="wppa-nav-text startstop"' .
							' style="'.wppa_wcs('wppa-nav-text').'"' .
							' title="' . __( 'Start / stop slideshow', 'wp-photo-album-plus' ) . '"' .
							' onclick="wppaStartStop(' . wppa( 'mocc' ) . ', -1); return false;"' .
							' >' .
							wppa_get_svghtml( 'Play-Button', '1.5em' ) .
						'</span>' .
						' ' .
						'<span' .
							' id="speed1-'.wppa( 'mocc' ).'"' .
							' class="wppa-nav-text speed1"' .
							' style="'.wppa_wcs('wppa-nav-text').'"' .
							' title="' . __('Faster', 'wp-photo-album-plus') . '"' .
							' onclick="wppaSpeed('.wppa( 'mocc' ).', true); return false;">' .
							wppa_get_svghtml( 'Eagle-1', '1.5em' ) .
						'</span>' .
					'</div>' );
	}
}
function wppa_start_stop_text( $opt = '' ) {

	if ( is_feed() ) return;	// Not in a feed

	// A single image slideshow needs no navigation
	if ( wppa( 'is_single' ) ) return;
	if ( wppa( 'is_filmonly' ) ) return;

	// we always need the js part for the functionality (through filmstrip etc).
	// so if not wanted: hide it
	$hide = 'display:none; '; // assume hide
	if ( $opt != 'optional' ) $hide = '';														// not optional: show
	if ( wppa_switch( 'show_startstop_navigation' ) && ! wppa( 'is_slideonly' ) ) $hide = '';	// we want it

	if ( wppa_opt( 'start_slide' ) || wppa_in_widget() ) {
		wppa_add_js_page_data( "\n" . '<script type="text/javascript">' );
		wppa_add_js_page_data( "\n" . 'wppaSlideInitRunning['.wppa( 'mocc' ).'] = true;' );
		wppa_add_js_page_data( "\n" . 'wppaMaxOccur = '.wppa( 'mocc' ).';' );
		wppa_add_js_page_data( "\n" . '</script>' );
	}

	if ( ! $hide ) {
		wppa_out( 	'<div' .
						' id="prevnext1-'.wppa( 'mocc' ).'"' .
						' class="wppa-box wppa-nav wppa-nav-text"' .
						' style="text-align:center;'.wppa_wcs('wppa-box').wppa_wcs('wppa-nav').wppa_wcs('wppa-nav-text').$hide.'"' .
						' >' .
						'<a' .
							' id="speed0-'.wppa( 'mocc' ).'"' .
							' class="wppa-nav-text speed0"' .
							' style="'.wppa_wcs('wppa-nav-text').'"' .
							' onclick="wppaSpeed('.wppa( 'mocc' ).', false); return false;"' .
							' >' .
							__('Slower', 'wp-photo-album-plus') .
						'</a>' .
						' | ' .
						'<a' .
							' id="startstop-'.wppa( 'mocc' ).'"' .
							' class="wppa-nav-text startstop"' .
							' style="'.wppa_wcs('wppa-nav-text').'"' .
							' onclick="wppaStartStop('.wppa( 'mocc' ).', -1); return false;">' .
							__('Start', 'wp-photo-album-plus') .
						'</a>' .
						' | ' .
						'<a' .
							' id="speed1-'.wppa( 'mocc' ).'"' .
							' class="wppa-nav-text speed1"' .
							' style="'.wppa_wcs('wppa-nav-text').'"' .
							' onclick="wppaSpeed('.wppa( 'mocc' ).', true); return false;">' .
							__('Faster', 'wp-photo-album-plus') .
						'</a>' .
					'</div>' );
	}
}

function wppa_slide_frame() {

	if ( is_feed() ) return;
	if ( wppa( 'is_filmonly' ) ) return;

	if ( wppa_switch( 'slide_pause') ) {
		$pause = 	' onmouseover="wppaSlidePause['.wppa( 'mocc' ).'] = \''.__('Paused', 'wp-photo-album-plus').'\'"' .
					' onmouseout="wppaSlidePause['.wppa( 'mocc' ).'] = false"';
	}
	else $pause = '';

	// There are still users who turn off javascript...
	wppa_out( 	'<noscript style="text-align:center; " >' .
					'<span style="color:red; ">' .
						__('To see the full size images, you need to enable javascript in your browser.', 'wp-photo-album-plus') .
					'</span>' .
				'</noscript>' );

	wppa_out( 	'<div' .
					' id="slide_frame-'.wppa( 'mocc' ).'"' .
					$pause .
					' class="slide-frame"' .
					' style="overflow:hidden;' . wppa_get_slide_frame_style() . '"' .
					' >' );

	$auto = wppa( 'auto_colwidth' ) || ( wppa_opt( 'colwidth' ) == 'auto' );

	wppa_out( 	'<div' .
					' id="theslide0-' . wppa( 'mocc' ) . '"' .
					' class="theslide theslide-' . wppa( 'mocc' ) . '"' .
					' style="' .
						( $auto ? 'width:100%;' : 'width:' . wppa( 'slideframewidth' )  . 'px;' ) .
						'margin:auto;' .
					'"' .
					' >' .
				'</div>' .
				'<div' .
					' id="theslide1-' . wppa( 'mocc' ) . '"' .
					' class="theslide theslide-' . wppa( 'mocc' ) . '"' .
					' style="' .
						( $auto ? 'width:100%;' : 'width:' . wppa( 'slideframewidth' )  . 'px;' ) .
						'margin:auto;' .
					'"' .
					' >' .
				'</div>'
			);

			switch( wppa_opt( 'icon_corner_style' ) ) {
				case 'gif':
				case 'none':
					$bradius = '0';
					break;
				case 'light':
					$bradius = '12';
					break;
				case 'medium':
					$bradius = '24';
					break;
				case 'heavy':
					$bradius = '60';
					break;
			}

	if ( wppa_use_svg() ) {
		wppa_out(	'<svg' .
						' id="wppa-slide-spin-' . wppa( 'mocc' ) . '"' .
						' class="wppa-ajax-spin uil-default"' .
						' width="120px"' .
						' height="120px"' .
						' xmlns="http://www.w3.org/2000/svg"' .
						' viewBox="0 0 100 100"' .
						' preserveAspectRatio="xMidYMid"' .
						' style="' .
							'width:120px;' .
							'height:120px;' .
							'position:absolute;' .
							'top:50%;' .
							'margin-top:-60px;' .
							'left:50%;' .
							'margin-left:-60px;' .
							'z-index:100100;' .
							'opacity:1;' .
							'display:block;' .
							'fill:' . wppa_opt( 'svg_color' ) . ';' .
							'background-color:' . wppa_opt( 'svg_bg_color' ) . ';' .
							'box-shadow:none;' .
							'border-radius:' . $bradius .'px;' .
							'"' .
						' >' .
						wppa_get_spinner_svg_body_html() .
					'</svg>'
		);
	}
	else {
		wppa_out( 	'<img' .
						' id="wppa-slide-spin-' . wppa( 'mocc' ) . '"' .
						' alt="spinner"' .
						' style="' .
							'width:120px;' .
							'height:120px;' .
							'position:absolute;' .
							'top:50%;' .
							'margin-top:-60px;' .
							'left:50%;' .
							'margin-left:-60px;' .
							'z-index:100100;' .
							'opacity:1;' .
							'display:block;' .
//							'fill:' . wppa_opt( 'svg_color' ) . ';' .
							'background-color:' . wppa_opt( 'svg_bg_color' ) . ';' .
							'box-shadow:none;' .
							'border-radius:' . $bradius .'px;' .
							'"' .
						' src="' . wppa_get_imgdir() . 'loader.gif"' .
					' />'
				);
	}


	/*'<div' .
					' id="spinner-' . wppa( 'mocc' ) . '"' .
					' class="spinner"' .
					' >' .
				'</div>'
			);
	*/

	if ( ! wppa_page( 'oneofone' ) ) {

		// Big browsing buttons enabled ?
		if ( ( wppa_switch( 'show_bbb' ) && ! wppa_in_widget() ) ||
			 ( wppa_switch( 'show_bbb_widget' ) && wppa_in_widget() ) ) {
			$h = wppa( 'slideframeheight' );
			$h -= wppa_get_audio_control_height();

			wppa_out( 	'<img' .
							' id="bbb-' . wppa( 'mocc' ) . '-l"' .
							' class="bbb-l bbb-' . wppa( 'mocc' ) . '"' .
							' src="' . wppa_get_imgdir() . 'bbbl.png"' .
							' alt="bbbl"' .
							' style="' .
								'background-color:transparent;' .
								'border:none;' .
								'z-index:83;' .
								'position:absolute;' .
								'float:left;' .
								'top:0px;' .
								'width:' . ( wppa( 'slideframewidth' ) * 0.5 ) . 'px;' .
								'height:' . $h . 'px;' .
								'box-shadow:none;' .
								'cursor:default;' .
								'"' .
							' onmouseover="wppaBbb('.wppa( 'mocc' ).',\'l\',\'show\')"' .
							' onmouseout="wppaBbb('.wppa( 'mocc' ).',\'l\',\'hide\')"' .
							' onclick="wppaBbb('.wppa( 'mocc' ).',\'l\',\'click\')"' .
							' />' .
						'<img' .
							' id="bbb-' . wppa( 'mocc' ) . '-r"' .
							' class="bbb-r bbb-' . wppa( 'mocc' ) . '"' .
							' src="' . wppa_get_imgdir() . 'bbbr.png"' .
							' alt="bbbr"' .
							' style="' .
								'background-color:transparent;' .
								'border:none;' .
								'z-index:83;' .
								'position:absolute;' .
								'float:right;' .
								'top:0px;' .
								'width:' . ( wppa( 'slideframewidth' ) * 0.5 ) . 'px;' .
								'height:' . $h . 'px;' .
								'box-shadow:none;' .
								'cursor:default;' .
								'"' .
							' onmouseover="wppaBbb('.wppa( 'mocc' ).',\'r\',\'show\')"' .
							' onmouseout="wppaBbb('.wppa( 'mocc' ).',\'r\',\'hide\')"' .
							' onclick="wppaBbb('.wppa( 'mocc' ).',\'r\',\'click\')"' .
							' />'
					);
		}

		// Ugly browse buttons ?
		if ( ( wppa_switch( 'show_ubb' ) && ! wppa_in_widget() ) ||
			 ( wppa_switch( 'show_ubb_widget' ) && wppa_in_widget() ) ) {

			wppa_out( 	'<div' .
							' id="ubb-'.wppa( 'mocc' ).'-l"' .
							' class="ubb ubb-l ubb-'.wppa( 'mocc' ).'"' .
							' style="' .
								'background-color:transparent;' .
								'border:none;' .
								'z-index:85;' .
								'position:absolute;' .
								'top:50%;' .
								'margin-top:-24px;' .
								'left:0;' .
								'box-shadow:none;' .
								'cursor:pointer;' .
								'width:48px;' .
								'"' .
							' onmouseover="wppaUbb('.wppa( 'mocc' ).',\'l\',\'show\')"' .
							' ontouchstart="wppaUbb('.wppa( 'mocc' ).',\'l\',\'show\')"' .
							' onmouseout="wppaUbb('.wppa( 'mocc' ).',\'l\',\'hide\')"' .
							' ontouchend="wppaUbb('.wppa( 'mocc' ).',\'l\',\'hide\');"' .
							' onclick="wppaUbb('.wppa( 'mocc' ).',\'l\',\'click\')"' .
							' >' .
							wppa_get_svghtml( 'Prev-Button', '48px', false, true ) .
						'</div>' .
						'<div' .
							' id="ubb-'.wppa( 'mocc' ).'-r"' .
							' class="ubb ubb-r ubb-'.wppa( 'mocc' ).'"' .
							' style="' .
								'background-color:transparent;' .
								'border:none;' .
								'z-index:85;' .
								'position:absolute;' .
								'top:50%;' .
								'margin-top:-24px;' .
								'right:0;' .
								'box-shadow:none;' .
								'cursor:pointer;' .
								'width:48px;' .
								'"' .
							' onmouseover="wppaUbb('.wppa( 'mocc' ).',\'r\',\'show\')"' .
							' ontouchstart="wppaUbb('.wppa( 'mocc' ).',\'r\',\'show\')"' .
							' onmouseout="wppaUbb('.wppa( 'mocc' ).',\'r\',\'hide\')"' .
							' onclick="wppaUbb('.wppa( 'mocc' ).',\'r\',\'click\')"' .
							' ontouchend="wppaUbb('.wppa( 'mocc' ).',\'r\',\'click\');wppaUbb('.wppa( 'mocc' ).',\'r\',\'hide\');"' .
							' >' .
							wppa_get_svghtml( 'Next-Button', '48px', false, true ) .
						'</div>'
					);
		}
	}

	wppa_startstop_icons();
	wppa_numberbar();

	wppa_out( '</div>' );
}

function wppa_slide_name_desc( $key = 'optional' ) {

	$do_it = false;
	if ( $key != 'optional' ) $do_it = true;
	if ( wppa( 'is_slideonly' ) ) {
		if ( wppa( 'name_on') ) $do_it = true;
		if ( wppa( 'desc_on') ) $do_it = true;
	}
	else {
		if ( wppa_switch( 'show_full_desc') ) $do_it = true;
		if ( wppa_switch( 'show_full_name') || wppa_switch( 'show_full_owner') ) $do_it = true;
	}
	if ( $do_it ) {
		wppa_out( 	'<div' .
						' id="namedesc-' . wppa( 'mocc' ) . '"' .
						' class="wppa-box wppa-name-desc"' .
						' style="' . wppa_wcs('wppa-box') . wppa_wcs('wppa-name-desc') .'"' .
						' >'
				);

			if ( wppa_switch( 'swap_namedesc') ) {
				wppa_slide_name($key);			// The name of the photo
				wppa_slide_description($key);		// The description of the photo
			}
			else {
				wppa_slide_description($key);		// The description of the photo
				wppa_slide_name($key);			// The name of the photo
			}

		wppa_out( '</div>' );
	}
}

function wppa_slide_name_box( $key = 'optional' ) {

	$do_it = false;
	if ( $key != 'optional' ) $do_it = true;
	if ( wppa( 'is_slideonly' ) ) {
		if ( wppa( 'name_on' ) ) $do_it = true;
	}
	else {
		if ( wppa_switch( 'show_full_name' ) || wppa_switch( 'show_full_owner' ) ) $do_it = true;
	}
	if ( $do_it ) {
		wppa_out( 	'<div' .
						' id="namebox-' . wppa( 'mocc' ) . '"' .
						' class="wppa-box wppa-name-desc"' .
						' style="' . wppa_wcs( 'wppa-box' ) . wppa_wcs( 'wppa-name-desc' ) . '"' .
						' >'
				);
			wppa_slide_name( $key );			// The name of the photo
		wppa_out(  '</div>' );
	}
}

function wppa_slide_desc_box( $key = 'optional' ) {

	$do_it = false;
	if ( $key != 'optional' ) $do_it = true;
	if ( wppa( 'is_slideonly' ) ) {
		if ( wppa( 'desc_on' ) ) $do_it = true;
	}
	else {
		if ( wppa_switch( 'show_full_desc' ) ) $do_it = true;
	}
	if ( $do_it ) {
		wppa_out( 	'<div' .
						' id="descbox-'.wppa( 'mocc' ).'"' .
						' class="wppa-box wppa-name-desc"' .
						' style="' . wppa_wcs( 'wppa-box' ) . wppa_wcs( 'wppa-name-desc' ) . '"' .
						' >' );
			wppa_slide_description( $key );		// The description of the photo
		wppa_out(  '</div>' );
	}
}

function wppa_slide_name($opt = '') {

	if ( wppa( 'is_slideonly' ) ) {
		if ( wppa( 'name_on' ) ) $doit = true;
		else $doit = false;
	}
	else {
		if ( $opt == 'optional' ) {
			if ( wppa_switch( 'show_full_name' ) || wppa_switch( 'show_full_owner' ) ) $doit = true;
			else $doit = false;
		}
		else $doit = true;
	}
	if ( $opt == 'description' ) $doit = false;

	if ( $doit ) {
		wppa_out( 	'<div' .
						' id="imagetitle-'.wppa( 'mocc' ).'"' .
						' class="wppa-fulltitle imagetitle"' .
						' style="'.wppa_wcs('wppa-fulltitle').'padding:3px; width:100%"' .
						' >' .
					'</div>'
				);
	}
}

function wppa_slide_description( $opt = '' ) {

	if ( wppa( 'is_slideonly' ) ) {
		if ( wppa( 'desc_on' ) ) $doit = true;
		else $doit = false;
	}
	else {
		if ( $opt == 'optional' ) {
			if ( wppa_switch( 'show_full_desc' ) ) $doit = true;
			else $doit = false;
		}
		else $doit = true;
	}
	if ( $opt == 'name' ) $doit = false;

	if ( $doit ) {
		wppa_out( 	'<div' .
						' id="imagedesc-'.wppa( 'mocc' ).'"' .
						' class="wppa-fulldesc imagedesc"' .
						' style="' .
							wppa_wcs( 'wppa-fulldesc' ) .
							'padding:3px;' .
							'width:100%;' .
							'text-align:' . wppa_opt( 'fulldesc_align' ) .
							'"' .
						' >' .
					'</div>'
				);
	}
}

function wppa_slide_custom( $opt = '' ) {

	if ( $opt == 'optional' && ! wppa_switch( 'custom_on' ) ) return;
	if ( wppa( 'is_slideonly' ) ) return;	/* Not when slideonly */
	if ( is_feed() ) return;

	$content = __( stripslashes( wppa_opt( 'custom_content' ) ) );

	// w#albdesc
	if ( wppa_is_int( wppa( 'start_album' ) ) && wppa( 'start_album' ) > '0' ) {
		$content = str_replace( 'w#albdesc', wppa_get_album_desc( wppa( 'start_album' ) ), $content );
	}
	else {
		$content = str_replace( 'w#albdesc', '', $content );
	}

	// w#fotomoto
	$f_on_this = false;
	if ( function_exists( 'fotomoto_page_enabled' ) ) {
		$f_on_this = ! wppa( 'in_widget' ) && fotomoto_page_enabled( get_the_ID() );
	}
	if ( wppa_switch( 'fotomoto_on' ) && $f_on_this ) {
		$fontsize = wppa_opt( 'fotomoto_fontsize' );
		if ( $fontsize ) {
			$s = '<style>.FotomotoToolbarClass{font-size:' . wppa_opt( 'fotomoto_fontsize' ) . 'px !important;}</style>';
		}
		else $s = '';
		$content = str_replace( 'w#fotomoto',
								$s .
								'<div' .
									' id="wppa-fotomoto-container-'.wppa( 'mocc' ).'"' .
									' class="wppa-fotomoto-container"' .
									' >' .
								'</div>' .
								'<div' .
									' id="wppa-fotomoto-checkout-'.wppa( 'mocc' ).'"' .
									' class="wppa-fotomoto-checkout FotomotoToolbarClass"' .
									' style="float:right; clear:none;"' .
									' >' .
									'<ul' .
										' class="FotomotoBar"' .
										' style="list-style:none outside none;"' .
										' >' .
										'<li>' .
											'<a' .
											' onclick="FOTOMOTO.API.checkout(); return false;"' .
											' >' .
												__('Checkout', 'wp-photo-album-plus') .
											'</a>' .
										'</li>' .
									'</ul>' .
								'</div>' .
								'<div' .
									' style="clear:both;"' .
									' >' .
								'</div>',
								$content );
	}
	else {
		$content = str_replace( 'w#fotomoto', '', $content );
	}

//	$content = wppa_html( $content ); // removed 6.5.07 because this is nonsense

	wppa_out( 	'<div' .
					' id="wppa-custom-'.wppa( 'mocc' ).'"' .
					' class="wppa-box wppa-custom"' .
					' style="'.wppa_wcs('wppa-box').wppa_wcs('wppa-custom').'"' .
					' >' .
					$content .
				'</div>'
			);
}

function wppa_slide_rating( $opt = '' ) {

	if ( wppa_opt( 'rating_max' ) == '1' ) {
		wppa_slide_rating_vote_only( $opt );
	}
	else {
		wppa_slide_rating_range( $opt );
	}
}

function wppa_slide_rating_vote_only( $opt, $id = '0', $is_lightbox = false ) {

	wppa_out( wppa_get_slide_rating_vote_only( $opt, $id, $is_lightbox ) );
}

function wppa_get_slide_rating_vote_only( $opt, $id = '0', $is_lightbox = false ) {

	if ( ! $is_lightbox ) {
		if ( $opt == 'optional' && ! wppa_switch( 'rating_on' ) ) return;
		if ( wppa( 'is_slideonly' ) ) return '';	/* Not when slideonly */
		if ( is_feed() ) return '';
	}

	$result = '';

	// Open the voting box
	if ( ! $is_lightbox ) {
		$result .=
			'<div' .
				' id="wppa-rating-'.wppa( 'mocc' ).'"' .
				' class="wppa-box wppa-nav wppa-nav-text"' .
				' style="' .
					wppa_wcs( 'wppa-box' ) .
					wppa_wcs( 'wppa-nav' ) .
					wppa_wcs( 'wppa-nav-text' ) .
					'text-align:center;' .
					'"' .
				'>';
	}

	// Likes
	if ( wppa_opt( 'rating_display_type' ) == 'likes' ) {

		// Logged in or don't care
		if ( ! wppa_switch( 'rating_login' ) || is_user_logged_in() ) {
			$fs = '16';
			$pad = '4';
			if ( $id ) {
				$liketitle 	= wppa_get_like_title_a( $id );
				$my 		= $liketitle['mine'];
				$title 		= $liketitle['title'];
				$display 	= $liketitle['display'];
			}
			else {
				$my 		= '';
				$title 		= '';
				$display 	= '';

			}
			$result .=	'<div' .
							' id="wppa-like-imgdiv-'.wppa( 'mocc' ).'"' .
							' style="display:inline"' .
							' >' .

							'<img' .
								( $is_lightbox ? ' id="wppa-like-0"' : ' id="wppa-like-'.wppa( 'mocc' ).'"' ) .
								( $my ? ' src="'.wppa_get_imgdir().'thumbdown.png"' : ' src="'.wppa_get_imgdir().'thumbup.png"' ) .
								( $my ? ' alt="down"' : ' alt="up"' ) .
								' style="height:'.$fs.'px; margin:0 0 -3px 0; padding:0 '.$pad.'px; box-shadow:none; display:inline;"' .
								' class="no-shadow"' .
								( $title ? ' title="' . esc_attr( $title ) . '"' : '' ) .
								' onmouseover="jQuery(this).stop().fadeTo(100, 1.0)"' .
								' onmouseout="jQuery(this).stop().fadeTo(100, wppaStarOpacity)"' .
								( $is_lightbox ? ' onclick="wppaOvlRateIt(\''.wppa_encrypt_photo($id).'\', 1, 0 )"' : ' onclick="wppaRateIt( ' . wppa( 'mocc' ) . ', 1);"' ) .
								' onload="jQuery(this).trigger(\'onmouseout\');"' .
							' />';

			$result .= 	'</div>';

			if ( wppa_switch( 'show_avg_rating' ) ) {
				$result .= 	'<span' .
								( $is_lightbox ? ' id="wppa-liketext-0"' : ' id="wppa-liketext-'.wppa( 'mocc' ).'"' ) .
								' style="cursor:default;"' .
								' >' .
								$display .
							'</span>';
			}

		}
		else {
			if ( wppa_switch( 'login_links' ) ) {
				$result .= sprintf(__( 'You must <a href="%s">login</a> to vote', 'wp-photo-album-plus' ), wppa_opt( 'login_url' ) );
			}
			else {
				$result .= __( 'You must login to vote', 'wp-photo-album-plus' );
			}
		}
	}

	else {

		// Logged in or don't care ?
		if ( ! wppa_switch( 'rating_login' ) || is_user_logged_in() ) {
			$cnt = '0';
			if ( wppa_switch( 'show_avg_rating' ) ) {
				$result .= sprintf( __('Number of votes: <span id="wppa-vote-count-%s" >%s</span>&nbsp;', 'wp-photo-album-plus'), wppa( 'mocc' ), $cnt);
			}
			$result .= 	'<input' .
							' id="wppa-vote-button-'.wppa( 'mocc' ).'"' .
							' class="wppa-vote-button"' .
							' style="margin:0;"' .
							' type="button"' .
							' onclick="wppa'.$ovl.'RateIt('.wppa( 'mocc' ).', 1)"' .
							' value="'.wppa_opt( 'vote_button_text' ) . '"' .
						' />';
		}

		// Must login to vote
		else {
			if ( wppa_switch( 'login_links' ) ) {
				$result .= sprintf( __( 'You must <a href="%s">login</a> to vote' , 'wp-photo-album-plus' ), wppa_opt( 'login_url' ) );
			}
			else {
				$result .= __( 'You must login to vote' , 'wp-photo-album-plus' );
			}
		}
	}

	// Close the voting box
	if ( ! $is_lightbox ) {
		$result .= '</div>';
	}

	return $result;
}

function wppa_slide_rating_range( $opt ) {

	// On a slide: depending on slide visibility settings
	if ( $opt == 'optional' && ! wppa_switch( 'rating_on' ) ) {
		return '';
	}

	// Not on a slideonly
	if ( wppa( 'is_slideonly' ) ) {
		return '';
	}

	$result = wppa_get_rating_range_html();
	wppa_out( $result );
}

function wppa_get_rating_range_html( $id = 0, $is_lightbox = false, $class = '' ) {
global $wpdb;

	// Not on a feed
	if ( is_feed() ) return '';

	// On lightbox: only if in visibility settings set.
	if ( $is_lightbox ) {
		if ( ! wppa_switch( 'ovl_rating' ) ) {
			return '';
		}
	}

	if ( $id ) {
		$wait_text = wppa_get_rating_wait_text( $id, wppa_get_user() );
		if ( $wait_text ) {
			return '<span class="'.$class.'" style="color:red" >'.$wait_text.'</span>';
		}
		if ( wppa_get_photo_item( $id, 'owner' ) == wppa_get_user() && ! wppa_switch( 'allow_owner_votes' ) ) {
			return '<span class="'.$class.'" >' . __( 'Sorry, you can not rate your own photos' , 'wp-photo-album-plus') . '</span>';
		}
		$mylast = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `'.WPPA_RATING.'` WHERE `photo` = %s AND `user` = %s ORDER BY `id` DESC LIMIT 1', $id, wppa_get_user() ), ARRAY_A );
		if ( $mylast && ! wppa_switch( 'rating_change' ) && ! wppa_switch( 'rating_multi' ) ) {
			return '<span class="'.$class.'" >' . __( 'Sorry, you can rate a photo only once', 'wp-photo-album-plus' ) . '</span>';
		}
	}

	// Mphoto, xphoto and lightbox use a different js function than slideshow.
	// In slideshow the id is not known and retrieved from _wppaCurIdx[mocc].
	// There is also a difference in css.
	$idorlb = $id || $is_lightbox;

	// If on xphoto, reload after
	$reload = ( wppa( 'is_xphoto' ) ? 'true' : 'false' );

	$result = '';

	$fs = wppa_opt( 'fontsize_nav' );
	if ( $fs ) $fs += 3; else $fs = '15';	// iconsize = fontsize+3, Default to 15
	$dh = $fs + '6';
	$size = 'font-size:'.($fs-3).'px;';

	// Open the rating box
	$result .= 	'<div' .
					' id="wppa-rating-'.wppa( 'mocc' ).'"' .
					' class="' . ( $idorlb ? $class : 'wppa-box wppa-nav wppa-nav-text' ) . '"' .
					' style="' .
						( $idorlb ? 'padding:4px;' : wppa_wcs( 'wppa-box' ) . wppa_wcs( 'wppa-nav' ) . wppa_wcs( 'wppa-nav-text' ) ) .
						$size .
						' text-align:center;"' .
					'> ';

	// Graphic display ?
	if ( wppa_opt( 'rating_display_type' ) == 'graphic' ) {
		if ( wppa_opt( 'rating_max' ) == '5' ) {
			$r['1'] = __( 'very low', 'wp-photo-album-plus' );
			$r['2'] = __( 'low', 'wp-photo-album-plus' );
			$r['3'] = __( 'average', 'wp-photo-album-plus' );
			$r['4'] = __( 'high', 'wp-photo-album-plus' );
			$r['5'] = __( 'very high', 'wp-photo-album-plus' );
		}
		else for ( $i = '1'; $i <= '10'; $i++ ) $r[$i] = $i;

		$style = 'height:'.$fs.'px; margin:0 0 -3px 0; padding:0; box-shadow:none; display:inline;background-color:transparent;';
		$icon = 'star.ico';

		$avgrat_label = ( wppa_opt( 'initial_colwidth' ) < wppa_opt( 'mini_treshold' ) ? __( 'Avg.', 'wp-photo-album-plus' ) : __( 'Average&nbsp;rating', 'wp-photo-album-plus' ) );
		$myrat_label  = ( wppa_opt( 'initial_colwidth' ) < wppa_opt( 'mini_treshold' ) ? __( 'Mine', 'wp-photo-album-plus' ) :  __( 'My&nbsp;rating', 'wp-photo-album-plus' ) );

		// Display avg rating
		if ( wppa_switch( 'show_avg_rating' ) ) {

			if ( $id ) {
				$avgrat = wppa_get_rating_by_id( $id, 'nolabel' );
				$opac = array();
				$i = '1';
				while ( $i <= wppa_opt( 'rating_max' ) ) {
					if ( $avgrat >= $i ) {
						$opac[$i] = 'opacity:1;';
					}
					else if ( $avgrat <= ( $i - '1') ) {
						$opac[$i] = 'opacity:0.2;';
					}
					else {
						$opac[$i] = 'opacity:'.(0.2 + 0.8 * ($avgrat-$i+'1'));
					}
					$i++;
				}
			}
			$result .= 	'<span'.
							' id="wppa-avg-rat-' . wppa( 'mocc' ) . '"' .
							' class="wppa-rating-label"' .
							' >' .
							$avgrat_label .
						'</span>&nbsp;';

			$i = '1';
			while ( $i <= wppa_opt( 'rating_max' ) ) {
				$result .= 	'<img' .
								' id="wppa-avg-' . wppa( 'mocc' ) . '-' . $i . '"' .
								' class="wppa-rating-star wppa-avg-' . wppa( 'mocc' ) . '-' . $i . ' wppa-avg-'.wppa( 'mocc' ).' no-shadow"' .
								' style="' .
									$style .
									( $id ? $opac[$i] : '' ) .
									'"' .
								' src="' . wppa_get_imgdir() . $icon . '"' .
								' alt=" ' . $i . '"' .
								' title="'.__('Average&nbsp;rating', 'wp-photo-album-plus').': '.$r[$i].'"' .
							' />';
				$i++;
			}
		}

		$result .= 	'<img' .
						' id="wppa-filler-'.wppa( 'mocc' ).'"' .
						' src="'.wppa_get_imgdir().'transp.png"' .
						' alt="f"' .
						' style="width:'.wppa_opt( 'ratspacing').'px; height:15px; box-shadow:none; padding:0; margin:0; border:none;"' .
					' />';

		// Display my rating
		// Logged in or don't care
		if ( ! wppa_switch( 'rating_login' ) || is_user_logged_in() ) {

			// Rating on 2 lines?
			if ( wppa_switch( 'show_avg_mine_2' ) && wppa_switch( 'show_avg_rating' ) ) {
				$result .= '<br />';
			}

			// Text left if no avg rating OR on 2 lines
			if ( ! wppa_switch( 'show_avg_rating' ) || wppa_switch( 'show_avg_mine_2' ) ) {
				$result .= 	'<span' .
								' id="wppa-my-rat-'.wppa( 'mocc' ).'" ' .
								' class="wppa-rating-label"' .
								'>' .
								$myrat_label .
							'</span>&nbsp';
			}

			// Show dislike icon?
			$pad = round( ( wppa_opt( 'ratspacing' ) - $fs ) / 2 );
			if ( $pad < 5 ) $pad = '5';
			if ( wppa_opt( 'dislike_mail_every' ) ) {

				$confirm = 	esc_attr( str_replace( '"', "'", __('Are you sure you want to mark this image as inappropriate?', 'wp-photo-album-plus') ) );
				$result .= 	'<img' .
								' id="wppa-dislike-'.wppa( 'mocc' ).'"' .
								' title="'.__('Click this if you do NOT like this image!', 'wp-photo-album-plus').'"' .
								' src="'.wppa_get_imgdir().'thumbdown.png"' .
								' alt="d"' .
								' style="height:'.$fs.'px; margin:0 0 -3px 0; padding:0 '.$pad.'px; box-shadow:none; display:inline;"' .
								' class="wppa-rating-thumb  no-shadow"' .
								' onmouseover="jQuery(this).stop().fadeTo(100, 1.0)"' .
								' onmouseout="jQuery(this).stop().fadeTo(100, wppaStarOpacity)"' .
								' onclick="';
									if ( $idorlb ) {
										$result .= 'if (confirm(\'' . $confirm . '\')) { wppaOvlRateIt( \'' . wppa_encrypt_photo($id) . '\', -1, ' . ( $id ? wppa('mocc') : '0' ) . ' ); }';
									}
									else {
										$result .= 'if (confirm(\'' . $confirm . '\')) { wppaRateIt( ' . wppa( 'mocc' ) . ', -1); }';
									}
				$result .= 		'"' .
							' />';

				if ( $idorlb ) {
					$mylast = wppa_get_my_last_vote( $id );

					if ( $mylast == '-1' ) {
						$result .= '<script type="text/javascript" >jQuery(\'#wppa-dislike-'.wppa( 'mocc' ).'\').css(\'display\'. \'none\');</script>';
					}
					else {
						$result .= '<script type="text/javascript" >jQuery(\'#wppa-dislike-'.wppa( 'mocc' ).'\').fadeTo(100,'.(wppa_opt('star_opacity')/100).');</script>';
					}
				}

				if ( wppa_switch( 'dislike_show_count' ) ) {
					$result .= 	'<span' .
									' id="wppa-discount-' . wppa( 'mocc' ) . '"' .
									' style="cursor:default"' .
									' title="' . __('Number of people who marked this photo as inappropriate', 'wp-photo-album-plus') . '"' .
									' >' .
								'</span>';
				}
			}

			// Display the my rating stars
			if ( $id ) {
				$myavgrat = wppa_get_my_rating_by_id( $id, 'nolabel' );
				$opac = array();
				$i = '1';
				while ( $i <= wppa_opt( 'rating_max' ) ) {
					if ( $myavgrat >= $i ) {
						$opac[$i] = 'opacity:1;';
					}
					else if ( $myavgrat <= ( $i - '1') ) {
						$opac[$i] = 'opacity:0.2;';
					}
					else {
						$opac[$i] = 'opacity:'.(0.2 + 0.8 * ($myavgrat-$i+'1'));
					}
					$i++;
				}
			}

			$i = '1';
			while ( $i <= wppa_opt( 'rating_max' ) ) {
				$result .= 	'<img' .
								' id="wppa-rate-' . wppa( 'mocc' ) . '-' . $i . '"' .
								' class="wppa-rating-star  wppa-rate-' . wppa( 'mocc' ) . '-' . $i . ' wppa-rate-'.wppa( 'mocc' ).' no-shadow"' .
								' style="' .
									$style .
									( $id ? $opac[$i] : '' ) .
									'"' .
								' src="'.wppa_get_imgdir().$icon.'"' .
								' alt="'.$i.'"' .
								' title="'.__('My&nbsp;rating', 'wp-photo-album-plus').': '.$r[$i].'"' .

								// Follow and leave are different for slideshw and lightbox et al
								( $id ?
								' onmouseover="wppaOvlFollowMe('.wppa( 'mocc' ).', '.$i.', '.$myavgrat.' )"' .
								' onmouseout="wppaOvlLeaveMe('.wppa( 'mocc' ).', '.$i.', '.$myavgrat.' )"' :
								' onmouseover="wppaFollowMe('.wppa( 'mocc' ).', '.$i.')"' .
								' onmouseout="wppaLeaveMe('.wppa( 'mocc' ).', '.$i.')"' ) .

								( $idorlb ? ' onclick="wppaOvlRateIt(\''.wppa_encrypt_photo($id).'\', '.$i.', ' . ( $id ? wppa('mocc') : '0' ) . ', ' . $reload . ' )"' :
										' onclick="wppaRateIt('.wppa( 'mocc' ).', '.$i.')"' ) .
							' />';
				$i++;
			}

			// Text right if avg rating diaplayed AND not on two lines
			if ( wppa_switch( 'show_avg_rating' ) && ! wppa_switch( 'show_avg_mine_2' ) ) {
				$result .= 	'&nbsp;' .
								'<span' .
									' id="wppa-my-rat-'.wppa( 'mocc' ).'" ' .
									' class="wppa-rating-label"' .
									'>' .
									$myrat_label .
								'</span>';
			}
		}
		else {
			if ( wppa_switch( 'login_links' ) ) {
				$result .= sprintf(__( 'You must <a href="%s">login</a> to vote' , 'wp-photo-album-plus'), wppa_opt( 'login_url' ) );
			}
			else {
				$result .= __( 'You must login to vote' , 'wp-photo-album-plus');
			}
		}
	}

	// display_type = numeric?
	elseif ( wppa_opt( 'rating_display_type' ) == 'numeric' ) {

		// Display avg rating
		if ( wppa_switch( 'show_avg_rating' ) ) {
			$result .= 	__('Average&nbsp;rating', 'wp-photo-album-plus').':&nbsp;' .
						'<span id="wppa-numrate-avg-'.wppa( 'mocc' ).'"></span>' .
						' &bull;';
		}

		// Display my rating
		// Logged in or don't care
		if ( ! wppa_switch( 'rating_login' ) || is_user_logged_in() ) {

			// Show dislike icon?
			$pad = round( ( wppa_opt( 'ratspacing' ) - $fs ) / 2 );
			if ( $pad < 5 ) $pad = '5';
			if ( wppa_opt( 'dislike_mail_every') ) {

				$result .=	'<div' .
								' id="wppa-dislike-imgdiv-'.wppa( 'mocc' ).'"' .
								' style="display:inline"' .
								' >';

				$confirm = 	esc_attr( str_replace( '"', "'", __('Are you sure you want to mark this image as inappropriate?', 'wp-photo-album-plus') ) );
				$result .= 		'<img' .
									' id="wppa-dislike-'.wppa( 'mocc' ).'"' .
									' title="'.__('Click this if you do NOT like this image!', 'wp-photo-album-plus').'"' .
									' src="'.wppa_get_imgdir().'thumbdown.png"' .
									' alt="d"' .
									' style="height:'.$fs.'px; margin:0 0 -3px 0; padding:0 '.$pad.'px; box-shadow:none; display:inline;"' .
									' class="no-shadow"' .
									' onmouseover="jQuery(this).stop().fadeTo(100, 1.0)"' .
									' onmouseout="jQuery(this).stop().fadeTo(100, wppaStarOpacity)"' .
									' onclick="';
										if ( $idorlb ) {
											$result .= 'if (confirm(\'' . $confirm . '\')) { wppaOvlRateIt( \'' . wppa_encrypt_photo($id) . '\', -1, ' . ( $id ? wppa('mocc') : '0' ) . ' ); }';
										}
										else {
											$result .= 'if (confirm(\'' . $confirm . '\')) { wppaRateIt( ' . wppa( 'mocc' ) . ', -1); }';
										}
				$result .= 			'"' .
								' />';

				$result .= 		'</div>';

				if ( wppa_switch( 'dislike_show_count') ) {

					$result .= 	'<span' .
									' id="wppa-discount-'.wppa( 'mocc' ).'"' .
									' style="cursor:default"' .
									' title="'.__('Number of people who marked this photo as inappropriate', 'wp-photo-album-plus').'"' .
									' >' .
								'</span>';
				}
			}

			$result .= ' '.__('My rating:', 'wp-photo-album-plus');
			$result .= '<span id="wppa-numrate-mine-' . wppa( 'mocc' ) . '" ></span>';
		}
		else {
			if ( wppa_switch( 'login_links' ) ) {
				$result .= sprintf(__( 'You must <a href="%s">login</a> to vote', 'wp-photo-album-plus' ), wppa_opt( 'login_url' ) );
			}
			else {
				$result .= __( 'You must login to vote', 'wp-photo-album-plus' );
			}
		}
	}

	// Close rating box
	$result .= '</div>';

	return $result;
}

function wppa_slide_filmstrip( $opt = '' ) {

	// A single image slideshow needs no navigation
	if ( wppa( 'is_single' ) ) return;

	$do_it = false;												// Init
	if ( is_feed() ) $do_it = true;								// feed -> do it to indicate that there is a slideshow
	else {														// Not a feed
		if ( $opt != 'optional' ) $do_it = true;				// not optional -> do it
		else {													// optional
			if ( wppa_switch( 'filmstrip') ) {				// optional and option on
				if ( ! wppa( 'is_slideonly' ) ) $do_it = true;	// always except slideonly
			}
			if ( wppa( 'film_on' ) ) $do_it = true;				// explicitly turned on
		}
	}
	if ( ! $do_it ) return;										// Don't do it

	$t = -microtime(true);

	$alb = wppa_get_get( 'album' );

	$thumbs = wppa_get_thumbs();
	if ( ! $thumbs || count( $thumbs ) < 1 ) return;

	$preambule = wppa_get_preambule();

	$width 		= ( wppa_opt( 'film_thumbsize' ) + wppa_opt( 'tn_margin' ) ) * ( count( $thumbs ) + 2 * $preambule );
	$width 		+= wppa_opt( 'tn_margin' ) + 100;
	$topmarg 	= wppa_opt( 'film_thumbsize' ) / 2 - 16;
	$height 	= wppa_opt( 'film_thumbsize' ) + wppa_opt( 'tn_margin' );
	$height1 	= wppa_opt( 'film_thumbsize' );
	$marg 		= '42';	// 32
	$fs 		= '24';
	$fw 		= '42';

	if ( wppa_in_widget() ) {
		$width 		/= 2;
		$topmarg 	/= 2;
		$height 	/= 2;
		$height1 	/= 2;
		$marg 		= '21';
		$fs 		= '12';
		$fw 		= '21';
	}

	$conw = wppa_get_container_width();
	if ( $conw < 1 ) $conw *= 640;
	$w = $conw - ( 2*6 + 2*42 + ( wppa_opt( 'bwidth' ) ? 2*wppa_opt( 'bwidth' ) : 0 ) ); /* 2*padding + 2*arrows + 2*border */
	if ( wppa_in_widget() ) $w = $conw - ( 2*6 + 2*21 + 2*wppa_opt( 'bwidth' ) ); /* 2*padding + 2*arrow + 2*border */
	$IE6 = 'width: '.$w.'px;';
	$pagsiz = round( $w / ( wppa_opt( 'film_thumbsize' ) + wppa_opt( 'tn_margin' ) ) );
	if ( wppa_in_widget() ) $pagsiz = round( $w / ( wppa_opt( 'film_thumbsize' ) / 2 + wppa_opt( 'tn_margin' ) / 2 ) );

	wppa_add_js_page_data( '<script type="text/javascript">' );
	wppa_add_js_page_data( 'wppaFilmPageSize['.wppa( 'mocc' ).'] = '.$pagsiz.';' );
	wppa_add_js_page_data( '</script>' );

	if ( is_feed() ) {
		wppa_out( '<div style="'.wppa_wcs('wppa-box').wppa_wcs('wppa-nav').'">' );
	}
	else {

	wppa_out( 	'<div' .
					' class="wppa-box wppa-nav wppa-filmstrip-box"' .
					' style="text-align:center; '.wppa_wcs('wppa-box').wppa_wcs('wppa-nav').'height:'.$height.'px;"' .
					' >' .
					'<div' .
						' class="wppa-fs-arrow-cont-'.wppa( 'mocc' ).'"' .
						' style="float:left; text-align:left; cursor:pointer; margin-top:'.$topmarg.'px; width: '.$fw.'px; font-size: '.$fs.'px;"' .
						' >' .
						'<span' .
							' class="wppa-first-'.wppa( 'mocc' ).' wppa-arrow"' .
							' style="'.wppa_wcs('wppa-arrow').'"' .
							' id="first-film-arrow-'.wppa( 'mocc' ).'"' .
							' onclick="wppaFirst('.wppa( 'mocc' ).');"' .
							' title="'.__('First', 'wp-photo-album-plus').'"' .
							' >' .
							wppa_get_svghtml( 'Backward-Button', $fs . 'px;', false, false ) .
						'</span>' .
					'</div>' .
					'<div' .
						' class="wppa-fs-arrow-cont-'.wppa( 'mocc' ).'"' .
						' style="float:right; text-align:right; cursor:pointer; margin-top:'.$topmarg.'px; width: '.$fw.'px; font-size: '.$fs.'px;"' .
						' >' .
						'<span' .
							' class="wppa-last-'.wppa( 'mocc' ).' wppa-arrow"' .
							' style="'.wppa_wcs('wppa-arrow').'"' .
							' id="last-film-arrow-'.wppa( 'mocc' ).'"' .
							' onclick="wppaLast('.wppa( 'mocc' ).');"' .
							' title="'.__('Last', 'wp-photo-album-plus').'"' .
							' >' .
							wppa_get_svghtml( 'Forward-Button', $fs . 'px;', false, false ) .
						'</span>' .
					'</div>' .
					'<div' .
						' id="filmwindow-'.wppa( 'mocc' ).'"' .
						' class="filmwindow"' .
						' style="'.$IE6.' position:absolute; display: block; height:'.$height.'px; margin: 0 0 0 '.$marg.'px; overflow:hidden;"' .
						' >' .
						'<div' .
							' id="wppa-filmstrip-'.wppa( 'mocc' ).'"' .
							' style="height:'.$height1.'px; width:'.$width.'px; max-width:'.$width.'px;margin-left: -100px;"' .
							' >'
			);
	}

	$cnt 	= count( $thumbs );
	$start 	= $cnt - $preambule;
	$end 	= $cnt;
	$idx 	= $start;

	// Preambule
	while ( $idx < $end ) {
		$glue 	= $cnt == ( $idx + 1 ) ? true : false;
		$ix 	= $idx;
		while ( $ix < 0 ) {
			$ix += $cnt;
		}
		$thumb = $thumbs[$ix];
		wppa_do_filmthumb( $thumb['id'], $ix, false, $glue );
		$idx++;
	}

	// Real thumbs
	$idx = 0;
	foreach ( $thumbs as $tt ) : $thumb = $tt;
		$glue = $cnt == ( $idx + 1 ) ? true : false;
		wppa_do_filmthumb( $thumb['id'], $idx, true, $glue );
		$idx++;
	endforeach;

	// Postambule
	$start = '0';
	$end = $preambule;
	$idx = $start;
	while ( $idx < $end ) {
		$ix = $idx;
		while ( $ix >= $cnt ) $ix -= $cnt;
		$thumb = $thumbs[$ix];
		wppa_do_filmthumb( $thumb['id'], $ix, false );
		$idx++;
	}

	if ( is_feed() ) {
		wppa_out( '</div>' );
	}
	else {
			wppa_out( '</div>' );
		wppa_out( '</div>' );
	wppa_out( '</div>' );
	}

	$t += microtime(true);
	wppa_dbg_msg( 'Filmstrip took '.$t.' seconds.' );
}

function wppa_startstop_icons() {

	// Do they need us?
	if ( ! wppa_switch( 'show_start_stop_icons' ) ) {
		return;
	}

	// Create and output the html
	wppa_out( 	'<div' .
					' id="wppa-startstop-icon-' . wppa( 'mocc' ) . '"' .
					' alt="start stop"' .
					' style="' .
						'position:absolute;' .
						'left:50%;' .
						'margin-left:-24px;' .
						'top:50%;' .
						'margin-top:-24px;' .
						'z-index:90;' .
						'width:48px;' .
						'opacity:0.8;' .
						'cursor:pointer;' .
						'box-shadow:none;' .
						'"' .
					' onmouseover="jQuery(this).stop().fadeTo(200,0.8);"' .
					' ontouchstart="jQuery(this).stop().fadeTo(200,0.8);"' .
					' onmouseout="jQuery(this).stop().fadeTo(200,0);"' .
					' ontouchend="jQuery(this).stop().fadeTo(200,0);"' .
					' onclick="wppaStartStop( ' . wppa( 'mocc' ) . ', -1 );"' .
					' onload="jQuery(this).fadeTo(1000,0);"' .
					' >' .
					wppa_get_svghtml( 'Play-Button', '48px', false, true, '0', '5', '50', '50' ) .
				'</div>'
			);
}

function wppa_numberbar( $opt = '' ) {

	// A single image slideshow needs no navigation
	if ( wppa( 'is_single' ) ) return;

	if ( is_feed() ) return;

    $do_it = false;
    if ( wppa_switch( 'show_slideshownumbar') && ! wppa( 'is_slideonly' ) ) $do_it = true;
	if ( wppa( 'numbar_on' ) ) $do_it = true;
	if( ! $do_it ) return;

	// get the data
	$thumbs = wppa_get_thumbs();
	if ( empty( $thumbs ) ) return;

	// get the sizes
	$size_given = is_numeric( wppa_opt( 'fontsize_numbar' ) );
	if ( $size_given ) {
		$size = wppa_opt( 'fontsize_numbar' );
		if ( wppa_in_widget() ) $size /= 2;
	}
	else {
		$size = wppa_in_widget() ? '9' : '12';
	}
	if ( $size < '9') $size = '9';
	$size_2  = floor( $size / 2) ;
	$size_4  = floor( $size_2 / 2 );
	$size_32 = floor( $size * 3 / 2 );

	// make the numbar style
	$style = 'position:absolute; bottom:'.$size.'px; right:0; margin-right:'.$size_2.'px; ';

	// start the numbar
	wppa_out( '<div class="wppa-numberbar" style="'.$style.'">' );
		$numid = 0;

		// make the elementstyles
		$style = 	'display:block;' .
					'float:left;' .
					'padding:0 ' .
					$size_4 . 'px;' .
					'margin-right:' . $size_2 . 'px;' .
					'font-weight:' . wppa_opt( 'fontweight_numbar' ) . ';';
		if ( wppa_opt( 'fontfamily_numbar' ) ) {
			$style .= 'font-family:' . wppa_opt( 'fontfamily_numbar' ) .';';
		}
		if ( wppa_opt( 'fontcolor_numbar' ) ) {
			$style .= 'color:' . wppa_opt( 'fontcolor_numbar' ) . ';';
		}
		if ( $size_given ) {
			$style .= 'font-size:' . $size . 'px;line-height:' . $size_32 . 'px;';
		}

		$style_active = $style;

		if ( wppa_opt( 'bgcolor_numbar' ) ) {
			$style .= 'background-color:' . wppa_opt( 'bgcolor_numbar' ) . ';';
		}
		if ( wppa_opt( 'bgcolor_numbar_active' ) ) {
			$style_active .= 'background-color:' . wppa_opt( 'bgcolor_numbar_active' ) . ';';
		}
		if ( wppa_opt( 'bcolor_numbar' ) ) {
			$style .= 'border:1px solid ' . wppa_opt( 'bcolor_numbar' ) . ';';
		}
		if ( wppa_opt( 'bcolor_numbar_active' ) ) {
			$style_active .= 'border:1px solid ' . wppa_opt( 'bcolor_numbar_active' ) . ';';
		}

		// if the number of photos is larger than a certain number, only the active ph displays a number, other are dots
		$count = count( $thumbs );
		$high = wppa_opt( 'numbar_max' );

		// do the numbers
		foreach ( $thumbs as $tt ) {
			$title = sprintf( __( 'Photo %s of %s', 'wp-photo-album-plus' ), $numid + '1', $count );
			wppa_out( 	'<a' .
							' id="wppa-numbar-'.wppa( 'mocc' ).'-'.$numid.'"' .
							' title="'.$title.'"' .
							' ' . ($numid == 0 ? ' class="wppa-numbar-current" ' : '') .
							' style="' . ($numid == 0 ? $style_active : $style) . '"' .
							' onclick="wppaGotoKeepState('.wppa( 'mocc' ).',' . $numid . ');return false;"' .
							' >' .
							( $count > $high ? '.' : $numid + 1 ) .
						'</a>'
					);
			$numid++;
		}
	wppa_out( '</div>' );
}

function wppa_browsebar( $opt = '' ) {
	if ( wppa_get_navigation_type() == 'icons' ) {
		wppa_browsebar_icons( $opt );
	}
	else {
		wppa_browsebar_text( $opt );
	}
}
function wppa_browsebar_icons( $opt = '' ) {

	// A single image slideshow needs no navigation
	if ( wppa( 'is_single' ) ) return;

	if ( is_feed() ) return;

	$do_it = false;
	if ( $opt != 'optional' ) $do_it = true;
	if ( ! wppa( 'is_slideonly' ) && wppa_switch( 'show_browse_navigation' ) ) $do_it = true;
	if ( wppa( 'is_slideonly' ) && wppa( 'browse_on' ) ) $do_it = true;

	if ( $do_it ) {
		wppa_out( 	'<div' .
						' id="prevnext2-' . wppa( 'mocc' ) . '"' .
						' class="wppa-box wppa-nav wppa-nav-text"' .
						' style="text-align:center;' . wppa_wcs( 'wppa-box' ) . wppa_wcs( 'wppa-nav' ) . wppa_wcs( 'wppa-nav-text' ) . '"' .
						' >' .
						'<span' .
							' id="prev-arrow-' . wppa( 'mocc' ) . '"' .
							' class="wppa-prev-' . wppa( 'mocc' ) . ' wppa-nav-text arrow-' . wppa( 'mocc' ) . '"' .
							' style="float:left;text-align:left;cursor:pointer;' . wppa_wcs('wppa-nav-text') . '"' .
							' title="' . __( 'Previous photo', 'wp-photo-album-plus' ) . '"' .
							' onclick="wppaPrev(' . wppa( 'mocc' ) . ')"' .
							' >' .
							wppa_get_svghtml( 'Prev-Button', '1.5em' ) .
						'</span>' .
						'<span' .
							' id="next-arrow-' . wppa( 'mocc' ) . '"' .
							' class="wppa-next-' . wppa( 'mocc' ) . ' wppa-nav-text arrow-' . wppa( 'mocc' ) . '"' .
							' style="float:right;text-align:right;cursor:pointer;' . wppa_wcs( 'wppa-nav-text' ) . '"' .
							' title="' . __( 'Next photo', 'wp-photo-album-plus' ) . '"' .
							' onclick="wppaNext(' . wppa( 'mocc' ) . ')"' .
							' >' .
							wppa_get_svghtml( 'Next-Button', '1.5em' ) .
						'</span>' .
						'<span' .
							' id="counter-'.wppa( 'mocc' ).'"' .
							' class="wppa-nav-text wppa-black"' .
							' style="text-align:center; '.wppa_wcs('wppa-nav-text').'; cursor:pointer;"' .
							' onclick="wppaStartStop('.wppa( 'mocc' ).', -1);"' .
							' title="'.__('Click to start/stop', 'wp-photo-album-plus').'"' .
							' >' .
						'</span>' .
					'</div>'
				);
	}
}
function wppa_browsebar_text( $opt = '' ) {

	// A single image slideshow needs no navigation
	if ( wppa( 'is_single' ) ) return;

	if ( is_feed() ) return;

	$do_it = false;
	if ( $opt != 'optional' ) $do_it = true;
	if ( ! wppa( 'is_slideonly' ) && wppa_switch( 'show_browse_navigation' ) ) $do_it = true;
	if ( wppa( 'is_slideonly' ) && wppa( 'browse_on' ) ) $do_it = true;

	if ( $do_it ) {
		wppa_out( 	'<div' .
						' id="prevnext2-'.wppa( 'mocc' ).'"' .
						' class="wppa-box wppa-nav wppa-nav-text"' .
						' style="text-align: center; '.wppa_wcs('wppa-box').wppa_wcs('wppa-nav').wppa_wcs('wppa-nav-text').'"' .
						' >' .
						'<a' .
							' id="prev-arrow-'.wppa( 'mocc' ).'"' .
							' class="wppa-prev-'.wppa( 'mocc' ).' wppa-nav-text arrow-'.wppa( 'mocc' ).'"' .
							' style="float:left; text-align:left; cursor:pointer; '.wppa_wcs('wppa-nav-text').'" onclick="wppaPrev('.wppa( 'mocc' ).')"' .
							' >' .
						'</a>' .
						'<a' .
							' id="next-arrow-'.wppa( 'mocc' ).'"' .
							' class="wppa-next-'.wppa( 'mocc' ).' wppa-nav-text arrow-'.wppa( 'mocc' ).'"' .
							' style="float:right; text-align:right; cursor:pointer; '.wppa_wcs('wppa-nav-text').'"' .
							' onclick="wppaNext('.wppa( 'mocc' ).')"' .
							' >' .
						'</a>' .
						'<span' .
							' id="counter-'.wppa( 'mocc' ).'"' .
							' class="wppa-nav-text wppa-black"' .
							' style="text-align:center; '.wppa_wcs('wppa-nav-text').'; cursor:pointer;"' .
							' onclick="wppaStartStop('.wppa( 'mocc' ).', -1);"' .
							' title="'.__('Click to start/stop', 'wp-photo-album-plus').'"' .
							' >' .
						'</span>' .
					'</div>'
				);
	}
}

function wppa_comments( $opt = '' ) {

	if ( is_feed() ) {
		if ( wppa_switch( 'show_comments' ) ) {
			wppa_dummy_bar( __( '- - - Comments box activated - - -', 'wp-photo-album-plus' ) );
			return;
		}
	}

	$do_it = false;
	if ( $opt != 'optional' ) $do_it = true;
	if ( ! wppa( 'is_slideonly' ) && wppa_switch( 'show_comments' ) && ! wppa_in_widget() ) $do_it = true;

	if ( $do_it ) {
		wppa_out( 	'<div' .
						' id="wppa-comments-'.wppa( 'mocc' ).'"' .
						' class="wppa-box wppa-comments"' .
						' style="text-align: center; '.wppa_wcs('wppa-box').wppa_wcs('wppa-comments').'"' .
						' >' .
					'</div>'
				);
	}
}

function wppa_iptc( $opt = '' ) {

	if ( is_feed() ) {
		if ( wppa_switch( 'show_iptc' ) ) {
			wppa_dummy_bar( __( '- - - IPTC box activated - - -', 'wp-photo-album-plus' ) );
		}
		return;
	}

	$do_it = false;
	if ( $opt != 'optional' ) $do_it = true;
	if ( ! wppa( 'is_slideonly' ) && wppa_switch( 'show_iptc' ) && ! wppa_in_widget() ) $do_it = true;

	if ( $do_it ) {
		wppa_out( 	'<div' .
						' id="iptc-'.wppa( 'mocc' ).'"' .
						' class="wppa-box wppa-box-text wppa-iptc"' .
						' style="text-align: center; '.wppa_wcs('wppa-box').wppa_wcs('wppa-box-text').wppa_wcs('wppa-iptc').'"' .
						' >' .
					'</div>'
				);
	}
}

function wppa_exif( $opt = '' ) {

	if ( is_feed() ) {
		if ( wppa_switch( 'show_exif' ) ) {
			wppa_dummy_bar( __( '- - - EXIF box activated - - -', 'wp-photo-album-plus' ) );
		}
		return;
	}

	$do_it = false;
	if ( $opt != 'optional' ) $do_it = true;
	if ( ! wppa( 'is_slideonly' ) && wppa_switch( 'show_exif' ) && ! wppa_in_widget() ) $do_it = true;

	if ( $do_it ) {
		wppa_out( 	'<div' .
						' id="exif-'.wppa( 'mocc' ).'"' .
						' class="wppa-box wppa-box-text wppa-exif"' .
						' style="text-align: center; '.wppa_wcs('wppa-box').wppa_wcs('wppa-box-text').wppa_wcs('wppa-exif').'"' .
						' >' .
					'</div>'
				);
	}
}

function wppa_share( $opt = '' ) {

	if ( is_feed() ) {
		return;
	}

	$do_it = false;
	if ( $opt != 'optional' ) $do_it = true;
	if ( ! wppa( 'is_slideonly' ) ) {
		if ( wppa_switch( 'share_on') && ! wppa_in_widget() ) $do_it = true;
		if ( wppa_switch( 'share_on_widget') && wppa_in_widget() ) $do_it = true;
	}

	if ( $do_it ) {
		wppa_out( 	'<div' .
						' id="wppa-share-'.wppa( 'mocc' ).'"' .
						' class="wppa-box wppa-box-text wppa-share"' .
						' style="text-align: center; '.wppa_wcs('wppa-box').wppa_wcs('wppa-box-text').wppa_wcs('wppa-share').'"' .
						' >' .
					'</div>'
				);
	}
}

function wppa_errorbox( $text ) {

	wppa_out( 	'<div' .
					' id="error-'.wppa( 'mocc' ).'"' .
					' class="wppa-box wppa-box-text wppa-nav wppa-errorbox"' .
					' style="text-align: center; '.wppa_wcs('wppa-box').wppa_wcs('wppa-box-text').wppa_wcs('wppa-nav').'"' .
					' >' .
					$text .
				'</div>'
			);
}