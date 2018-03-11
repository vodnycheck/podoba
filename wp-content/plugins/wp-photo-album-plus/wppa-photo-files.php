<?php
/* wppa-photo-files.php
*
* Functions used to create/manipulate photofiles
* Version 6.7.08
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

// Unfortunately there is no php function to rotate or resize an image file while the exif data is preserved.
// The origianal sourcefile is normally saved, to be available for download or hires uses e.g. in lightbox.
// The orientation of photos made by mobile devices is often non-standard ( 1 ), so we need a higres file,
// rotated and/or mirrored to the correct position.
// When the sourefile name is e.g.: .../wp-content/uploads/wppa-source/album-1/MyImage.jpg,
// We create the correct oriented file: .../wp-content/uploads/wppa-source/album-1/MyImage-o1.jpg. ( o1 stands for orientation=1 ).
// Note: wppa_get_source_path() should return the un-oriented file always, while wppa_get_hires_url() must return the -o1 file, if available.
function wppa_make_o1_source( $id ) {

	// Init
	$src_path = wppa_get_source_path( $id );

	// Source available?
	if ( ! is_file( $src_path ) ) return false;

	// Only needed for non-standard orientations
	$orient = wppa_get_exif_orientation( $src_path );
	if ( ! in_array( $orient, array( '2', '3', '4', '5', '6', '7', '8' ) ) ) return false;

	// Only on jpg file type
	$ext = wppa_get_ext( $src_path );
	if ( ! in_array( $ext, array( 'jpg', 'JPG', 'jpeg', 'JPEG' ) ) ) return false;

	// Make destination path
	$dst_path = wppa_get_o1_source_path( $id );

	// ImageMagick
	if ( wppa_opt( 'image_magick' ) ) {
		wppa_image_magick( 'convert "' . $src_path . '" -auto-orient "' . $dst_path . '"' );
	}

	// Classic
	else {

		// Copy source to destination
		copy( $src_path, $dst_path );

		// Correct orientation
		if ( ! wppa_orientate_image_file( $dst_path, $orient ) ) {
			unlink( $dst_path );
			return false;
		}
	}


	// Done
	return true;
}

// Convert source file path to proper oriented source file path
function wppa_get_o1_source_path( $id ) {

	$src_path = wppa_get_source_path( $id );
	if ( $src_path ) {
		$src_path = wppa_strip_ext( $src_path ) . '-o1.' . wppa_get_ext( $src_path );
	}

	return $src_path;
}

// Rotate/mirror a photo display image by id
function wppa_orientate_image( $id, $ori ) {

	// If orientation right, do nothing
	if ( ! $ori || $ori == '1' ) {
		return;
	}

	wppa_orientate_image_file( wppa_get_photo_path( $id ), $ori );
	wppa_bump_photo_rev();
}

// Rotate/mirror an image file by pathname
function wppa_orientate_image_file( $file, $ori ) {

	// Validate args
	if ( ! is_file( $file ) ) {
		wppa_log( 'Err', 'File not found (wppa_orientate_image_file())' );
		return false;
	}
	if ( ! wppa_is_int( $ori ) || $ori < '2' || $ori > '8' ) {
		wppa_log( 'Err', 'Bad arg $ori:'.$ori.' (wppa_orientate_image_file())' );
		return false;
	}

	// Load image
	$source = wppa_imagecreatefromjpeg( $file );
	if ( ! $source ) {
		wppa_log( 'Err', 'Could not create memoryimage from jpg file ' . $file );
		return false;
	}

	// Perform operation
	switch ( $ori ) {
		case '2':
			$orientate = $source;
			imageflip( $orientate, IMG_FLIP_HORIZONTAL );
			break;
		case '3':
			$orientate = imagerotate( $source, 180, 0 );
			break;
		case '4':
			$orientate = $source;
			imageflip( $orientate, IMG_FLIP_VERTICAL );
			break;
		case '5':
			$orientate = imagerotate( $source, 270, 0 );
			imageflip( $orientate, IMG_FLIP_HORIZONTAL );
			break;
		case '6':
			$orientate = imagerotate( $source, 270, 0 );
			break;
		case '7':
			$orientate = imagerotate( $source, 90, 0 );
			imageflip( $orientate, IMG_FLIP_HORIZONTAL );
			break;
		case '8':
			$orientate = imagerotate( $source, 90, 0 );
			break;
	}

	// Output
	imagejpeg( $orientate, $file, wppa_opt( 'jpeg_quality' ) );

	// Accessable
	wppa_chmod( $file );

	// Optimized
	wppa_optimize_image_file( $file );

	// Free the memory
	imagedestroy( $source );
	@ imagedestroy( $orientate );

	// Done
	return true;
}

// Make the display and thumbnails from a given pathname or upload temp image file.
// The id and extension must be supplied.
function wppa_make_the_photo_files( $file, $id, $ext, $do_thumb = true ) {
global $wpdb;
//wppa_log('dbg', 'make called with'.$file.' '.$id.' '.$ext.' '.$do_thumb);
	$thumb = wppa_cache_thumb( $id );

	$src_size = @getimagesize( $file, $info );

	// If the given file is not an image file, log error and exit
	if ( ! $src_size ) {
		if ( is_admin() ) wppa_error_message( sprintf( __( 'ERROR: File %s is not a valid picture file.' , 'wp-photo-album-plus'), $file ) );
		else wppa_alert( sprintf( __( 'ERROR: File %s is not a valid picture file.', 'wp-photo-album-plus'), $file ) );
		return false;
	}

	// Find output path photo file
	$newimage = wppa_get_photo_path( $id );
	if ( $ext ) {
		$newimage = wppa_strip_ext( $newimage ) . '.' . strtolower( $ext );
	}

	// Max sizes
	if ( wppa_opt( 'resize_to' ) == '0' ) {	// from fullsize
		$max_width 	= wppa_opt( 'fullsize' );
		$max_height = wppa_opt( 'maxheight' );
	}
	else {										// from selection
		$screen = explode( 'x', wppa_opt( 'resize_to' ) );
		$max_width 	= $screen[0];
		$max_height = $screen[1];
	}

	// If Resize on upload is checked
	if ( wppa_switch( 'resize_on_upload' ) ) {

		// ImageMagick
		if ( wppa_opt( 'image_magick' ) ) {

			// If jpg, apply jpeg quality
			$q = wppa_opt( 'jpeg_quality' );
			$quality = '';
			if ( wppa_get_ext( $file ) == 'jpg' ) {
				$quality = '-quality ' . $q;
			}

			wppa_image_magick( 'convert "' . $file . '" ' . $quality . ' -resize ' . ( $thumb['stereo'] ? 2 * $max_width : $max_width ) . 'x' . $max_height . ' ' . $newimage );
		}

		// Classic GD
		if ( ! wppa_opt( 'image_magick' ) || ! is_file( $newimage ) ) {

			// Picture sizes
			$src_width 	= $src_size[0];

			// Temp convert to logical width if stereo
			if ( $thumb['stereo'] ) {
				$src_width /= 2;
			}
			$src_height = $src_size[1];

/*			// Max sizes
			if ( wppa_opt( 'resize_to' ) == '0' ) {	// from fullsize
				$max_width 	= wppa_opt( 'fullsize' );
				$max_height = wppa_opt( 'maxheight' );
			}
			else {										// from selection
				$screen = explode( 'x', wppa_opt( 'resize_to' ) );
				$max_width 	= $screen[0];
				$max_height = $screen[1];
			}
*/
			// If orientation needs +/- 90 deg rotation, swap max x and max y
			$ori = wppa_get_exif_orientation( $file );
			if ( $ori >= 5 && $ori <= 8 ) {
				$t = $max_width;
				$max_width = $max_height;
				$max_height = $t;
			}

			// Is source more landscape or more portrait than max window
			if ( $src_width/$src_height > $max_width/$max_height ) {	// focus on width
				$focus = 'W';
				$need_downsize = ( $src_width > $max_width );
			}
			else {														// focus on height
				$focus = 'H';
				$need_downsize = ( $src_height > $max_height );
			}

			// Convert back to physical size
			if ( $thumb['stereo'] ) {
				$src_width *= 2;
			}

			// Downsize required ?
			if ( $need_downsize ) {

				// Find mime type
				$mime = $src_size[2];

				// Create the source image
				switch ( $mime ) {	// mime type
					case 1: // gif
						$temp = @ imagecreatefromgif( $file );
						if ( $temp ) {
							$src = imagecreatetruecolor( $src_width, $src_height );
							imagecopy( $src, $temp, 0, 0, 0, 0, $src_width, $src_height );
							imagedestroy( $temp );
						}
						else $src = false;
						break;
					case 2:	// jpeg
						if ( ! function_exists( 'wppa_imagecreatefromjpeg' ) ) {
							wppa_log( 'Error', 'Function wppa_imagecreatefromjpeg does not exist.' );
						}
						$src = @ wppa_imagecreatefromjpeg( $file );
						break;
					case 3:	// png
						$src = @ imagecreatefrompng( $file );
						break;
				}

				if ( ! $src ) {
					wppa_log( 'Error', 'Image file '.$file.' is corrupt while downsizing photo' );
					return false;
				}

				// Create the ( empty ) destination image
				if ( $focus == 'W') {
					if ( $thumb['stereo'] ) $max_width *= 2;
					$dst_width 	= $max_width;
					$dst_height = round( $max_width * $src_height / $src_width );
				}
				else {
					$dst_height = $max_height;
					$dst_width = round( $max_height * $src_width / $src_height );
				}
				$dst = imagecreatetruecolor( $dst_width, $dst_height );

				// If Png, save transparancy
				if ( $mime == 3 ) {
					imagealphablending( $dst, false );
					imagesavealpha( $dst, true );
				}

				// Do the copy
				imagecopyresampled( $dst, $src, 0, 0, 0, 0, $dst_width, $dst_height, $src_width, $src_height );

				// Remove source image
				imagedestroy( $src );

				// Save the photo
				switch ( $mime ) {	// mime type
					case 1:
						imagegif( $dst, $newimage );
						break;
					case 2:
						imagejpeg( $dst, $newimage, wppa_opt( 'jpeg_quality' ) );
						break;
					case 3:
						imagepng( $dst, $newimage, 6 );
						break;
				}

				// Remove destination image
				imagedestroy( $dst );

			}
			else {	// No downsize needed, picture is small enough
				copy( $file, $newimage );
			}
		}
	}

	// No resize on upload checked
	else {
		copy( $file, $newimage );
	}

	// File successfully created ?
	if ( is_file ( $newimage ) ) {

		// Make sure file is accessible
		wppa_chmod( $newimage );

		// Optimize file
		wppa_optimize_image_file( $newimage );
	}
	else {
		if ( is_admin() ) wppa_error_message( __( 'ERROR: Resized or copied image could not be created.' , 'wp-photo-album-plus') );
		else wppa_alert( __( 'ERROR: Resized or copied image could not be created.', 'wp-photo-album-plus') );
		return false;
	}

	// Process the iptc data
	wppa_import_iptc( $id, $info );

	// Process the exif data
	wppa_import_exif( $id, $file );

	// GPS
	wppa_get_coordinates( $file, $id );

	// Set ( update ) exif date-time if available
	$exdt = wppa_get_exif_datetime( $file );
	if ( $exdt ) {
		wppa_update_photo( array( 'id' => $id, 'exifdtm' => $exdt ) );
	}

	// Check orientation
	wppa_orientate_image( $id, wppa_get_exif_orientation( $file ) );

	// Compute and save sizes
	wppa_get_photox( $id, 'force' );

	// Show progression
	if ( is_admin() && ! wppa( 'ajax' ) ) echo( '.' );

	// Update CDN
	$cdn = wppa_cdn( 'admin' );
	if ( $cdn ) {
		switch ( $cdn ) {
			case 'cloudinary':
				wppa_upload_to_cloudinary( $id );
				break;
			default:
				wppa_dbg_msg( 'Missing upload instructions for '.$cdn, 'red', 'force' );
		}
	}

	// Create stereo images
	wppa_create_stereo_images( $id );

	// Create thumbnail...
	if ( $do_thumb ) {
		wppa_create_thumbnail( $id );
	}

	// Clear (super)cache
	wppa_clear_cache();
	return true;

}

// Create thubnail
function wppa_create_thumbnail( $id, $use_source = true ) {

	if ( $use_source && ! wppa_switch( 'watermark_thumbs' ) ) {

		// Try o1 source
		$file = wppa_get_o1_source_path( $id );

		// Try source path
		if ( ! is_file( $file ) ) {
			$file = wppa_get_source_path( $id );
		}

		// Use photo path
		if ( ! is_file( $file ) ) {
			$file = wppa_get_photo_path( $id );
		}
	}

	// Not source requested
	else {
		$file = wppa_get_photo_path( $id );
	}

	// Max side
	$max_side = wppa_get_minisize();

	// Check file
	if ( ! is_file( $file ) ) return false;		// No file, fail
	$img_attr = getimagesize( $file );
	if ( ! $img_attr ) return false;				// Not an image, fail

	// Retrieve aspect
	$asp_attr = explode( ':', wppa_opt( 'thumb_aspect' ) );

	// Get output path
	$thumbpath = wppa_get_thumb_path( $id );

	// Source size
	$src_size_w = $img_attr[0];
	$src_size_h = $img_attr[1];

	// Temp convert width if stereo
	if ( wppa_get_photo_item( $id, 'stereo' ) ) {
		$src_size_w /= 2;
	}

	// Mime type and thumb type
	$mime = $img_attr[2];
	$type = $asp_attr[2];

	// Source native aspect
	$src_asp = $src_size_h / $src_size_w;

	// Required aspect
	if ( $type == 'none' ) {
		$dst_asp = $src_asp;
	}
	else {
		$dst_asp = $asp_attr[0] / $asp_attr[1];
	}

	// Convert back width if stereo
	if ( wppa_get_photo_item( $id, 'stereo' ) ) {
		$src_size_w *= 2;
	}

	// Image Magick?
	if ( wppa_opt( 'image_magick' ) && $type == 'none' ) {
		wppa_image_magick( 'convert "' . $file . '" -thumbnail ' . $max_side . 'x' . $max_side . ' ' . $thumbpath );
	}

	// Classic GD
	else {
		// Create the source image
		switch ( $mime ) {	// mime type
			case 1: // gif
				$temp = @ imagecreatefromgif( $file );
				if ( $temp ) {
					$src = imagecreatetruecolor( $src_size_w, $src_size_h );
					imagecopy( $src, $temp, 0, 0, 0, 0, $src_size_w, $src_size_h );
					imagedestroy( $temp );
				}
				else $src = false;
				break;
			case 2:	// jpeg
				if ( ! function_exists( 'wppa_imagecreatefromjpeg' ) ) wppa_log( 'Error', 'Function wppa_imagecreatefromjpeg does not exist.' );
				$src = @ wppa_imagecreatefromjpeg( $file );
				break;
			case 3:	// png
				$src = @ imagecreatefrompng( $file );
				break;
		}
		if ( ! $src ) {
			wppa_log( 'Error', 'Image file '.$file.' is corrupt while creating thmbnail' );
			return true;
		}

		// Compute the destination image size
		if ( $dst_asp < 1.0 ) {	// Landscape
			$dst_size_w = $max_side;
			$dst_size_h = round( $max_side * $dst_asp );
		}
		else {					// Portrait
			$dst_size_w = round( $max_side / $dst_asp );
			$dst_size_h = $max_side;
		}

		// Create the ( empty ) destination image
		$dst = imagecreatetruecolor( $dst_size_w, $dst_size_h );
		if ( $mime == 3 ) {	// Png, save transparancy
			imagealphablending( $dst, false );
			imagesavealpha( $dst, true );
		}

		// Fill with the required color
		$c = trim( strtolower( wppa_opt( 'bgcolor_thumbnail' ) ) );
		if ( $c != '#000000' ) {
			$r = hexdec( substr( $c, 1, 2 ) );
			$g = hexdec( substr( $c, 3, 2 ) );
			$b = hexdec( substr( $c, 5, 2 ) );
			$color = imagecolorallocate( $dst, $r, $g, $b );
			if ( $color === false ) {
				wppa_log( 'Err', 'Unable to set background color to: '.$r.', '.$g.', '.$b.' in wppa_create_thumbnail' );
			}
			else {
				imagefilledrectangle( $dst, 0, 0, $dst_size_w, $dst_size_h, $color );
			}
		}

		// Switch on what we have to do
		switch ( $type ) {
			case 'none':	// Use aspect from fullsize image
				$src_x = 0;
				$src_y = 0;
				$src_w = $src_size_w;
				$src_h = $src_size_h;
				$dst_x = 0;
				$dst_y = 0;
				$dst_w = $dst_size_w;
				$dst_h = $dst_size_h;
				break;
			case 'clip':	// Clip image to given aspect ratio
				if ( $src_asp < $dst_asp ) {	// Source image more landscape than destination
					$dst_x = 0;
					$dst_y = 0;
					$dst_w = $dst_size_w;
					$dst_h = $dst_size_h;
					$src_x = round( ( $src_size_w - $src_size_h / $dst_asp ) / 2 );
					$src_y = 0;
					$src_w = round( $src_size_h / $dst_asp );
					$src_h = $src_size_h;
				}
				else {
					$dst_x = 0;
					$dst_y = 0;
					$dst_w = $dst_size_w;
					$dst_h = $dst_size_h;
					$src_x = 0;
					$src_y = round( ( $src_size_h - $src_size_w * $dst_asp ) / 2 );
					$src_w = $src_size_w;
					$src_h = round( $src_size_w * $dst_asp );
				}
				break;
			case 'padd':	// Padd image to given aspect ratio
				if ( $src_asp < $dst_asp ) {	// Source image more landscape than destination
					$dst_x = 0;
					$dst_y = round( ( $dst_size_h - $dst_size_w * $src_asp ) / 2 );
					$dst_w = $dst_size_w;
					$dst_h = round( $dst_size_w * $src_asp );
					$src_x = 0;
					$src_y = 0;
					$src_w = $src_size_w;
					$src_h = $src_size_h;
				}
				else {
					$dst_x = round( ( $dst_size_w - $dst_size_h / $src_asp ) / 2 );
					$dst_y = 0;
					$dst_w = round( $dst_size_h / $src_asp );
					$dst_h = $dst_size_h;
					$src_x = 0;
					$src_y = 0;
					$src_w = $src_size_w;
					$src_h = $src_size_h;
				}
				break;
			default:		// Not implemented
				return false;
		}

		// Copy left half if stereo
		if ( wppa_get_photo_item( $id, 'stereo' ) ) {
			$src_w /= 2;
		}

		// Do the copy
		imagecopyresampled( $dst, $src, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h );

		// Save the thumb
		$thumbpath = wppa_strip_ext( $thumbpath );
		switch ( $mime ) {	// mime type
			case 1:
				$full_thumbpath = $thumbpath . '.gif';
				imagegif( $dst, $full_thumbpath );
				break;
			case 2:
				$full_thumbpath = $thumbpath . '.jpg';
				imagejpeg( $dst, $full_thumbpath, wppa_opt( 'jpeg_quality' ) );
				break;
			case 3:
				$full_thumbpath = $thumbpath . '.png';
				imagepng( $dst, $full_thumbpath, 6 );
				break;
		}
		$thumbpath = $full_thumbpath;

		// Cleanup
		imagedestroy( $src );
		imagedestroy( $dst );
	}

	// Make sure file is accessible
	wppa_chmod( $thumbpath );

	// Optimize
	wppa_optimize_image_file( $thumbpath );

	// Compute and save sizes
	wppa_get_thumbx( $id, 'force' );	// forces recalc x and y

	return true;
}

// To fix a bug in PHP as that photos made with the selfie camera of an android smartphone
// irroneously cause the PHP warning 'is not a valid JPEG file' and cause imagecreatefromjpag crash.
function wppa_imagecreatefromjpeg( $file ) {

	ini_set( 'gd.jpeg_ignore_warning', true );
	$img = imagecreatefromjpeg( $file );
	return $img;
}

// See if ImageMagick command exists
function wppa_is_magick( $command ) {
	if ( ! $command ) {
		return false;
	}
	if ( ! wppa_opt( 'image_magick' ) ) {
		return false;
	}
	return is_file( rtrim( wppa_opt( 'image_magick' ), '/' ) . '/' . $command );
}

// Process ImageMagick command
function wppa_image_magick( $command ) {

	// Image magic enabled?
	if ( ! wppa_opt( 'image_magick' ) ) {
		return '-9';
	}

	// Image Magick root dir
	$path = rtrim( wppa_opt( 'image_magick' ), '/' ) . '/';

	// Try to prepend 'magick' to the command if its not already there.
	// This is for forward compatibility, e.g. when 'magick' exists but 'convert' not.
	if ( wppa_is_magick( 'magick' ) && substr( $command, 0, 6 ) != 'magick' ) {
		$command = 'magick ' . $command;
	}
	$out  = array();
	$err  = 0;
	$run  = exec( $path . $command, $out, $err );

	$logcom = $command;
	$logcom = str_replace( ABSPATH, '...', $logcom );
	$logcom = str_replace( wppa_opt( 'image_magick' ), '...', $logcom );

	if ( $err ) {
		$key = $err ? 'Err' : 'Dbg';
		wppa_log( $key, 'Exec ' . $logcom . ' returned ' . $err ); //, true );
		foreach( $out as $line ) {
			wppa_log( 'OBS', $line );
		}
	}

	return $err;
}