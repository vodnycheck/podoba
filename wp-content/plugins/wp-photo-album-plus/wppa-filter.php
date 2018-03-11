<?php
/* wppa-filter.php
* Package: wp-photo-album-plus
*
* get the albums via shortcode handler
* Version 6.8.00
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

// Declare action hook actions
add_action('init', 'wppa_do_filter');

function wppa_do_filter() {

	add_filter( 'the_content', 'wppa_add_shortcode_to_post' );
}

// Add a specific shortcode at the end of a post in runtime
// The content filter must be executed ( normal priority 10 )
// before shortcode processing ( normal at priority 11 )
// for this to work
function wppa_add_shortcode_to_post( $post ) {

	$new_post = $post;
	if ( ! wppa( 'ajax' ) && wppa_switch( 'add_shortcode_to_post' ) ) {
		$id = get_the_ID();
		$p = get_post( $id, ARRAY_A );
		if ( $p['post_type'] == 'post' ) $new_post .= wppa_opt( 'shortcode_to_add' );
	}
	return $new_post;
}

// The shortcode handler
function wppa_shortcodes( $xatts, $content = '' ) {
global $wppa;
global $wppa_postid;
global $wppa_api_version;
global $wppa_revno;

	$atts = shortcode_atts( array(
		'type'  	=> 'generic',
		'album' 	=> '',
		'photo' 	=> '',
		'size'		=> '',
		'align'		=> '',
		'taglist'	=> '',
		'cols'		=> '',
		'sub' 		=> '',
		'root' 		=> '',
		'calendar' 	=> '',
		'all' 		=> '',
		'reverse' 	=> '',
		'landing' 	=> '',
		'admin' 	=> '',
		'parent' 	=> '',
		'alt' 		=> '',
		'timeout' 	=> '',
		'button' 	=> '',
	), $xatts );

	// Init
	wppa_reset_occurrance();

	// Find occur
	if ( get_the_ID() != $wppa_postid ) {		// New post
		$wppa['occur'] = '0';					// Init this occurance
		$wppa['fullsize'] = '';					// Reset at each post
		$wppa_postid = get_the_ID();			// Remember the post id
	}

	// Whatever is entered between [wppa ...] and [/wppa]
	$wppa['shortcode_content'] 	= $content;

	// Check for inconsistency
	if ( $atts['alt'] && wppa_switch( 'render_shortcode_always' ) ) {
		wppa_dbg_msg( 'ERROR! Either untick Table IV-A8: Render shortcode always, or remove the alt="'.$atts['alt'].'" attribute from the shortcode on this page/post', 'red', 'force' );
		return '';
	}

	// Find type
	switch ( $atts['type'] ) {
		case 'version':
			return $wppa_api_version;
			break;
		case 'dbversion':
			return $wppa_revno;
			break;
		case 'landing':
			$wppa['is_landing'] = '1';
		case 'generic':
			break;
		case 'cover':
			$wppa['start_album'] = $atts['album'];
			$wppa['is_cover'] = '1';
			$wppa['albums_only'] = true;
			break;
		case 'album':
		case 'content':
			$wppa['start_album'] = $atts['album'];
			break;
		case 'thumbs':
			$wppa['start_album'] = $atts['album'];
			$wppa['photos_only'] = true;
			break;
		case 'covers':
			$wppa['start_album'] = $atts['album'];
			$wppa['albums_only'] = true;
			break;
		case 'slide':
			$wppa['start_album'] = $atts['album'];
			$wppa['is_slide'] = '1';
			$wppa['start_photo'] = $atts['photo'];
			if ( $atts['timeout'] ) {
				$wppa['in_widget_timeout'] = ( $atts['timeout'] == 'random' ? 'random' : strval( abs( intval( $atts['timeout'] ) ) ) );
			}
			if ( $atts['button'] ) {
				$wppa['is_button'] = esc_attr( __( $atts['button'] ) );
			}
			break;
		case 'slideonly':
			$wppa['start_album'] = $atts['album'];
			$wppa['is_slideonly'] = '1';
			$wppa['start_photo'] = $atts['photo'];
			if ( $atts['timeout'] ) {
				$wppa['in_widget_timeout'] = ( $atts['timeout'] == 'random' ? 'random' : strval( abs( intval( $atts['timeout'] ) ) ) );
			}
			break;
		case 'slideonlyf':
			$wppa['start_album'] = $atts['album'];
			$wppa['is_slideonly'] = '1';
			$wppa['is_slideonlyf'] = '1';
			$wppa['film_on'] = '1';
			$wppa['start_photo'] = $atts['photo'];
			if ( $atts['timeout'] ) {
				$wppa['in_widget_timeout'] = ( $atts['timeout'] == 'random' ? 'random' : strval( abs( intval( $atts['timeout'] ) ) ) );
			}
			break;
		case 'slidef':
			$wppa['start_album'] = $atts['album'];
			$wppa['is_slide'] = '1';
			$wppa['film_on'] = '1';
		case 'filmonly':
			$wppa['start_album'] = $atts['album'];
			$wppa['is_slideonly'] = '1';
			$wppa['is_filmonly'] = '1';
			$wppa['film_on'] = '1';
			$wppa['start_photo'] = $atts['photo'];
			break;
		case 'photo':
		case 'sphoto':
			$wppa['single_photo'] = $atts['photo'];
			break;
		case 'mphoto':
			$wppa['single_photo'] = $atts['photo'];
			$wppa['is_mphoto'] = '1';
			break;
		case 'xphoto':
			$wppa['single_photo'] = $atts['photo'];
			$wppa['is_xphoto'] = '1';
			break;
		case 'slphoto':
			$wppa['is_slide'] = '1';
			$wppa['single_photo'] = $atts['photo'];
			$wppa['start_photo'] = $atts['photo'];
			$wppa['is_single'] = '1';
			break;
		case 'autopage':
			$wppa['is_autopage'] = '1';
			break;
		case 'upload':
			if ( $atts['parent'] ) {
				$wppa['start_album'] = wppa_alb_to_enum_children( $atts['parent'] );
			}
			else {
				$wppa['start_album'] = $atts['album'];
			}
			$wppa['is_upload'] = true;
			break;
		case 'multitag':
			$wppa['taglist'] = wppa_sanitize_tags($atts['taglist']);
			$wppa['is_multitagbox'] = true;
			if ( $atts['cols'] ) {
				$cols = explode( ',', $atts['cols'] );
				$col = $cols[0];
				if ( isset( $cols[1] ) && wppa_is_mobile() ) {
					$col = $cols[1];
				}
				if ( ! wppa_is_int( $col ) || $col < '1' ) $col = '2'; // On error use default
				$wppa['tagcols'] = $col;
			}
			break;
		case 'tagcloud':
			$wppa['taglist'] = wppa_sanitize_tags($atts['taglist']);
			$wppa['is_tagcloudbox'] = true;
			break;
		case 'bestof':
			$wppa['bestof'] = true;
			$wppa['bestof_args'] = $xatts;
			break;
		case 'superview':
			$wppa['is_superviewbox'] = true;
			$wppa['start_album'] = $atts['album'];
			break;
		case 'search':
			$wppa['is_searchbox'] = true;
			$wppa['may_sub'] = $atts['sub'];
			if ( $atts['root'] ) {
				if ( substr( $atts['root'], 0, 1 ) == '#' ) {
					$wppa['forceroot'] = strval( intval( substr( $atts['root'], 1 ) ) );
				}
				else {
					$wppa['may_root'] = $atts['root'];
				}
			}
			$wppa['landingpage'] = $atts['landing'];
			break;
		case 'supersearch':
			$wppa['is_supersearch'] = true;
			break;
		case 'calendar':
			if ( ! wppa_switch( 'allow_ajax' ) ) {
				wppa_dbg_msg ( 'Shortcode [wppa type="calendar" ...  requires Ajax acive. See Photo Albums -> Settings Table IV-A1.0', 'red', 'force' );
				return '';
			}
			$wppa['is_calendar'] = true;
			$wppa['calendar'] = 'timestamp';
			if ( in_array( $atts['calendar'], array( 'exifdtm', 'timestamp', 'modified' ) ) ) {
				$wppa['calendar'] = $atts['calendar'];
			}
			if ( $atts['all'] ) {
				$wppa['calendarall'] = true;
			}
			$wppa['reverse'] 		= $atts['reverse'];
			$wppa['start_album'] 	= $atts['album'];
			break;
		case 'stereo':
			$wppa['is_stereobox'] = true;
			break;
		case 'url':
			$wppa['is_url'] = true;
			$wppa['single_photo'] = $atts['photo'];
			break;
		case 'choice':
			$wppa['is_admins_choice'] = true;
			$wppa['admins_choice_users'] = $atts['admin'];
			break;
		case 'acount':
		case 'pcount':
			$a = strval( intval( $atts['album'] ) );
			$p = strval( intval( $atts['parent'] ) );
			$t = $atts['type'];
			if ( $a xor $p ) {
				$alb = $a ? $a : $p;
				$tc = wppa_get_treecounts_a( $alb );

				// Album based count requested
				if ( $a ) {
					if ( $t == 'acount' ) {
						return wppa_get_album_count( $alb, true );
					}
					else {
						return wppa_get_photo_count( $alb, true );
					}
				}

				// Parent based count requested
				else {
					if ( $t == 'acount' ) {
						return $tc['selfalbums'];
					}
					else {
						return $tc['selfphotos'];
					}

				}
			}
			else {
				wppa_dbg_msg('Error in shortcode spec for type="'.$atts['type'].'": either attribute album="" or parent="" should supply a positive integer', 'red', 'force' );
				return;
			}
			break;
		case 'share':
			$result = wppa_get_share_page_html();
			return $result;
			break;

		default:
			wppa_dbg_msg ( 'Invalid type: '.$atts['type'].' in wppa shortcode.', 'red', 'force' );
			return '';
	}

	// Count (internally to wppa_albums)

	// Find size
	if ( $atts['size'] && is_numeric( $atts['size'] ) && $atts['size'] < 1.0 ) {
		$wppa['auto_colwidth'] = true;
		$wppa['fullsize'] = $atts['size'];
	}
	elseif ( substr( $atts['size'], 0, 4 ) == 'auto' ) {
		$wppa['auto_colwidth'] = true;
		$wppa['fullsize'] = '';
		$wppa['max_width'] = substr( $atts['size'], 5 );
	}
	else {
		$wppa['auto_colwidth'] = false;
		$wppa['fullsize'] = $atts['size'];
	}

	// Find align
	$wppa['align'] = $atts['align'];

	// Ready to render ???
	$do_it = false;
	if ( wppa( 'rendering_enabled' ) ) $do_it = true;			// NOT in a head section (in a meta tag or so)
	if ( wppa_in_widget() ) $do_it = true;						// A widget always works
	if ( is_feed() ) $do_it = true;								// A feed has no head section
	if ( wppa_switch( 'render_shortcode_always' ) ) $do_it = true;	// Always

	if ( wppa( 'debug' ) ) {
		if ( $do_it ) $msg = 'Doit is on'; else $msg = 'Doit is off';
		wppa_dbg_msg( $msg );
	}

	// Do it also for url only shortcode
	if ( $do_it || $wppa['is_url'] ) {
		$result =  wppa_albums();						// Get the HTML
	}
	else {
		if ( $atts['alt'] ) {
			if ( wppa_is_int( $atts['alt'] ) && wppa_photo_exists( $atts['alt'] ) ) {
				$result = '<img src="' . wppa_get_photo_url( $atts['alt'] ) . '" alt="Photo ' . $atts['alt'] . '" />';
			}
			elseif ( $atts['alt'] == 'none' ) {
				$result = '';
			}
			else {
				$result = '<span style="color:red; font-weight:bold; ">[WPPA+ Invalid alt attribute in shortcode: ' . $atts['alt'] . ' (fsh)]</span>';
			}
		}
		else {
			$result = '<span style="color:blue; font-weight:bold; ">[WPPA+ Photo display (fsh)]</span>';	// Or an indicator
		}
	}

	// Reset
	$wppa['start_photo'] = '0';	// Start a slideshow here
	$wppa['is_single'] = false;	// Is a one image slideshow

	// Relative urls?
	$result = wppa_make_relative( $result );

	// In widget
	if ( wppa_in_widget() ) {
		if ( ! wppa_switch( 'shortcode_at_priority_widget' ) ) {
			return $result;
		}
	}

	// In Post / Page
	else {
		if ( ! wppa_switch( 'shortcode_at_priority' ) ) {
			return $result;
		}
	}

	// Url always immediately
	if ( $wppa['is_url'] ) {
		return $result;
	}

	// New method to prevent damage of the result by content filters that run on higher priorities than do_shortcode.
	// Previous methods, e.g. increasing the do_shortcode priority sometimes fail due to requirements of other plugins/shortcodes.
	// To prevent this, i first asked an enhancement to add a priority argument to add_shortcode(), but the wp boys simply say
	// 'this is not possible'. Everything is possible, they should say that they are not smart enough to implement it.
	// Since there are plans to set the do_shortcode() priority ( currently 11 ) lower than wpautop() ( 10 ), and there are many serious
	// bugs in wpautop() it is now urgent to create a monkey-proof solution to the problem that others destructify the so preciously created
	// shortcode process output.
	//
	// What we do is:
	// 1. Save the result in memory and return a placeholder for the result.
	// 2. Run a contentfilter on the highest possible priority that replaced the placeholder by the original result.
	//
	// It sounds simple, but it took me a few sleepless nights to figure out.
	// Here it goes:

	// Define storage for the results
	global $wppa_shortcode_results;

	// Create a key to identify the result.
	// Any unique key will do, as long as it is not tampered by any content filter.
	// Hopefully everything keeps an unadded shortcode untouched,
	// therefor we wrap the random key in square brackets
	$key = '[' . md5( rand() ) . ']';

	// Store
	$wppa_shortcode_results[$key] = $result;

	// Return the placeholder ( = the key ) instead of $result
	return $key;

}

// Declare the shortcode handler
add_shortcode( 'wppa', 'wppa_shortcodes' );

// The filter proc to insert the shortcodeoutput into the page content.
function wppa_insert_shortcode_output( $content ) {
global $wppa_shortcode_results;

	if ( is_array( $wppa_shortcode_results ) ) foreach( array_keys( $wppa_shortcode_results ) as $key ) {
		$content = str_replace( $key, $wppa_shortcode_results[$key], $content );
	}

	return $content;
}

// Declare the filter to replace the placeholders by the shortcode process output
// These filters must run after shortcode processing, so normally at a priority > 11
add_action( 'init', 'wppa_add_filters' );

function wppa_add_filters() {
	add_filter( 'the_content', 'wppa_insert_shortcode_output', wppa_opt( 'filter_priority' ) );
	add_filter( 'widget_content', 'wppa_insert_shortcode_output', wppa_opt( 'filter_priority' ) );
	add_filter( 'widget_text', 'wppa_insert_shortcode_output', wppa_opt( 'filter_priority' ) );
}

// The runtime modifiable settings are processed by the wppa_set shortcode
function wppa_set_shortcodes( $xatts, $content = '' ) {
global $wppa;
global $wppa_opt;

	$atts = shortcode_atts( array(
		'name' 		=> '',
		'value' 	=> ''
	), $xatts );

	$allowed = explode( ',', wppa_opt( 'set_shortcodes' ) );

	// Valid item?
	if ( $atts['name'] && ! in_array( $atts['name'], $allowed ) && wppa_opt( 'set_shortcodes' ) != 'all' ) {
		wppa_dbg_msg( $atts['name'] . ' is not a runtime settable configuration entity.', 'red', 'force' );
	}

	// Reset?
	elseif ( ! $atts['name'] ) {
		$wppa_opt = false;
		wppa_initialize_runtime();
		wppa_reset_occurrance();
	}

	// Option?
	elseif ( substr( $atts['name'], 0, 5 ) == 'wppa_' ) {
		if ( isset( $wppa_opt[$atts['name']] ) ) {
			$wppa_opt[$atts['name']] = $atts['value'];
		}
		else {
			wppa_dbg_msg( $atts['name'] . ' is not an option value.', 'red', 'force' );
		}
	}
	else {
		if ( isset( $wppa[$atts['name']] ) ) {
			$wppa[$atts['name']] = $value;
		}
		else {
			wppa_dbg_msg( $atts['name'] . ' is not a runtime value.', 'red', 'force' );
		}
	}
}

// Enable wppa_set shortcode conditionally
if ( get_option( 'wppa_enable_shortcode_wppa_set', 'no' ) == 'yes' ) {
	add_shortcode( 'wppa_set', 'wppa_set_shortcodes' );
}

// Add filter for the use of our lightbox implementation for non wppa+ images
add_filter( 'the_content', 'wppa_lightbox_global' );

function wppa_lightbox_global( $content ) {

	if ( wppa_switch( 'lightbox_global' ) ) {
		if ( wppa_opt( 'lightbox_name' ) == 'wppa' ) {	// Our lightbox
			if ( wppa_switch( 'lightbox_global_set' )  ) { // A set
				$pattern 		= "/<a(.*?)href=('|\")(.*?).(bmp|gif|jpeg|jpg|png)('|\")(.*?)>/i";
				$replacement 	= '<a$1href=$2$3.$4$5 data-rel="wppa[single]" style="'.' cursor:url('.wppa_get_imgdir().wppa_opt( 'magnifier' ).'),pointer;'.'"$6>';
				$content 		= preg_replace($pattern, $replacement, $content);
			}
			else {	// Not a set
				$pattern 		= "/<a(.*?)href=('|\")(.*?).(bmp|gif|jpeg|jpg|png)('|\")(.*?)>/i";
				$replacement 	= '<a$1href=$2$3.$4$5 data-rel="wppa" style="'.' cursor:url('.wppa_get_imgdir().wppa_opt( 'magnifier' ).'),pointer;'.'"$6>';
				$content 		= preg_replace($pattern, $replacement, $content);
			}
		}
	}
	return $content;
}

// Declare the simple photo shortcode handler optionally
add_action( 'init', 'wppa_add_photo_shortcode' );

function wppa_add_photo_shortcode() {
	if ( wppa_switch( 'photo_shortcode_enabled' ) ) {
		add_shortcode( 'photo', 'wppa_photo_shortcodes' );
	}
}

function wppa_photo_shortcodes( $xatts ) {
global $wppa;
global $wppa_postid;
global $wpdb;
static $seed;

	// Init
	wppa_reset_occurrance();

	// Get and validate photo id
	if ( isset( $xatts[0] ) ) {
		$photo = $xatts[0];
		if ( is_numeric( $photo ) && ! wppa_photo_exists( $photo ) ) {
			return sprintf( __( 'Photo %d does not exist', 'wp-photo-album-plus' ), $photo );
		}
	}
	else {
		return __( 'Missing photo id', 'wp-photo-album-plus' );
	}

	// Find occur
	if ( get_the_ID() != $wppa_postid ) {		// New post
		$wppa['occur'] = '0';					// Init this occurance
		$wppa['fullsize'] = '';					// Reset at each post
		$wppa_postid = get_the_ID();			// Remember the post id
	}

	// Random photo?
	if ( $wppa_postid && $photo == 'random' ) {

		if ( ! $seed ) {
			$seed = time();
		}
		$seed = floor( $seed * 0.9 );

		if ( wppa_opt( 'photo_shortcode_random_albums' ) != '-2' ) {
			$albs  = str_replace( '.', ',', wppa_expand_enum( wppa_opt( 'photo_shortcode_random_albums' ) ) );
			$photo = $wpdb->get_var( $wpdb->prepare( 	"SELECT `id` FROM `" . WPPA_PHOTOS . "` " .
														"WHERE `album` IN (" . $albs . ") " .
														"ORDER BY RAND(%d) LIMIT 1", $seed ) );
		}
		else {
			$photo = $wpdb->get_var( $wpdb->prepare( 	"SELECT `id` FROM `" . WPPA_PHOTOS . "` " .
														"ORDER BY RAND(%d) LIMIT 1", $seed ) );
		}
		if ( $photo ) {
			if ( wppa_switch( 'photo_shortcode_random_fixed' ) ) {
				$post_content = $wpdb->get_var( $wpdb->prepare( "SELECT `post_content` FROM `" . $wpdb->posts . "` WHERE `ID` = %d", $wppa_postid ) );
				$post_content = preg_replace( '/\[photo random\]/', '[photo '.$photo.']', $post_content, 1, $done );
				$wpdb->query( $wpdb->prepare( "UPDATE `" . $wpdb->posts . "` SET `post_content` = %s WHERE `ID` = %d", $post_content, $wppa_postid ) );
			}
		}
		else {
			return __( 'No random photo found', 'wp-photo-album-plus' );
		}
	}

	// Get configuration settings
	$type 	= wppa_opt( 'photo_shortcode_type' ); // 'xphoto';
	$size 	= wppa_opt( 'photo_shortcode_size' ); // '350';
	$align 	= wppa_opt( 'photo_shortcode_align' ); //'left';

	switch ( $type ) {
		case 'photo':
		case 'sphoto':
			$wppa['single_photo'] 	= $photo;
			break;
		case 'mphoto':
			$wppa['single_photo'] 	= $photo;
			$wppa['is_mphoto'] 		= '1';
			break;
		case 'xphoto':
			$wppa['single_photo'] 	= $photo;
			$wppa['is_xphoto'] 		= '1';
			break;
		case 'slphoto':
			$wppa['is_slide'] 		= '1';
			$wppa['single_photo'] 	= $photo;
			$wppa['start_photo'] 	= $photo;
			$wppa['is_single'] 		= '1';
			break;
	}

	// Process size
	if ( $size && is_numeric( $size ) && $size < 1.0 ) {
		$wppa['auto_colwidth'] 		= true;
		$wppa['fullsize'] 			= $size;
	}
	elseif ( substr( $size, 0, 4 ) == 'auto' ) {
		$wppa['auto_colwidth'] 		= true;
		$wppa['fullsize'] 			= '';
		$wppa['max_width'] 			= substr( $size, 5 );
	}
	else {
		$wppa['auto_colwidth'] 		= false;
		$wppa['fullsize'] 			= $size;
	}

	// Find align
	$wppa['align'] = $align;

	return wppa_albums();
}