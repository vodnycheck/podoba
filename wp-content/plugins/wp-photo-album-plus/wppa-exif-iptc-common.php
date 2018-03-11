<?php
/* wppa-exif-iptc-common.php
* Package: wp-photo-album-plus
*
* exif and iptc common functions
* version 6.8.00
*
*
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

global $wppa_supported_camara_brands;
$wppa_supported_camara_brands = array( 'CANON', 'NIKON', 'SAMSUNG' );

// Translate iptc tags into  photo dependant data inside a text
function wppa_filter_iptc($desc, $photo) {
global $wpdb;
global $wppa_iptc_labels;
global $wppa_iptc_cache;

	if ( strpos($desc, '2#') === false ) return $desc;	// No tags in desc: Nothing to do

	// Get te labels if not yet present
	if ( ! is_array( $wppa_iptc_labels ) ) {
		$wppa_iptc_labels = $wpdb->get_results( "SELECT * FROM `" . WPPA_IPTC . "` WHERE `photo` = '0' ORDER BY `tag`", ARRAY_A );
	}

	// If in cache, use it
	$iptcdata = false;
	if ( is_array( $wppa_iptc_cache ) ) {
		if ( isset( $wppa_iptc_cache[$photo] ) ) {
			$iptcdata = $wppa_iptc_cache[$photo];
		}
	}

	// Get the photo data
	if ( $iptcdata === false ) {
		$iptcdata = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . WPPA_IPTC . "` WHERE `photo`=%s ORDER BY `tag`", $photo ), ARRAY_A );

		// Save in cache, even when empty
		$wppa_iptc_cache[$photo] = $iptcdata;
	}

	// Init
	$temp = $desc;
	$prevtag = '';
	$combined = '';

	// Process all iptclines of this photo
	if ( ! empty( $iptcdata ) ) {
		foreach ( $iptcdata as $iptcline ) {
			$tag = $iptcline['tag'];
			if ( $prevtag == $tag ) {			// add a next item for this tag
				$combined .= ', '.htmlspecialchars( strip_tags( $iptcline['description'] ) );
			}
			else { 							// first item of this tag
				if ( $combined ) { 			// Process if required
					$temp = str_replace( $prevtag, $combined, $temp );
				}
				$combined = htmlspecialchars( strip_tags( $iptcline['description'] ) );
				$prevtag = $tag;
			}
		}

		// Process last
		$temp = str_replace( $tag, $combined, $temp );
	}

	// Process all labels
	if ( $wppa_iptc_labels ) {
		foreach( $wppa_iptc_labels as $iptclabel ) {
			$tag = $iptclabel['tag'];

			// convert 2#XXX to 2#LXXX to indicate the label
			$t = substr( $tag, 0, 2 ) . 'L' . substr( $tag, 2 );
			$tag = $t;
			$temp = str_replace( $tag, __( $iptclabel['description'] ), $temp );
		}
	}

	// Remove untranslated
	$pos = strpos($temp, '2#');
	while ( $pos !== false ) {
		$tmp = substr($temp, 0, $pos).__('n.a.', 'wp-photo-album-plus').substr($temp, $pos+5);
		$temp = $tmp;
		$pos = strpos($temp, '2#');
	}

	return $temp;
}

// Translate exif tags into  photo dependant data inside a text
function wppa_filter_exif( $desc, $photo ) {
global $wpdb;
global $wppa_exif_labels;
global $wppa_exif_cache;

	if ( strpos($desc, 'E#') === false ) return $desc;	// No tags in desc: Nothing to do

	// Get the labels if not yet present
	if ( ! is_array( $wppa_exif_labels ) ) {
		$wppa_exif_labels = $wpdb->get_results( "SELECT * FROM `" . WPPA_EXIF . "` WHERE `photo` = '0' ORDER BY `tag`", ARRAY_A );
	}

	// If in cache, use it
	$exifdata = false;
	if ( is_array( $wppa_exif_cache ) ) {
		if ( isset( $wppa_exif_cache[$photo] ) ) {
			$exifdata = $wppa_exif_cache[$photo];
		}
	}

	// Get the photo data
	if ( $exifdata === false ) {
		$exifdata = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . WPPA_EXIF . "` WHERE `photo`=%s ORDER BY `tag`", $photo ), ARRAY_A );

		// Save in cache, even when empty
		$wppa_exif_cache[$photo] = $exifdata;
	}

	// Init
	$temp = $desc;
	$prevtag = '';
	$combined = '';

	// Process all exiflines of this photo
	if ( ! empty( $exifdata ) ) {
		foreach ( $exifdata as $exifline ) {

			$tag = $exifline['tag'];
			if ( $prevtag == $tag ) {			// add a next item for this tag
				$combined .= ', ' . wppa_format_exif( $tag, $exifline['description'] );
			}
			else { 							// first item of this tag
				if ( $combined ) { 			// Process if required
					$temp = str_replace( $prevtag, $combined, $temp );
				}
				$combined = wppa_format_exif( $tag, $exifline['description'] );
				$prevtag = $tag;
			}
		}

		// Process last
		$temp = str_replace( $tag, $combined, $temp );
	}

	// Process all labels
	if ( $wppa_exif_labels ) {
		foreach( $wppa_exif_labels as $exiflabel ) {
			$tag = $exiflabel['tag'];

			// convert E#XXX to E#LXXX to indicate the label
			$t = substr( $tag, 0, 2 ) . 'L' . substr( $tag, 2 );
			$tag = $t;

			$temp = str_replace( $tag, __( $exiflabel['description'] ), $temp );
		}
	}

	// Remove untranslated
	$pos = strpos($temp, 'E#');
	while ( $pos !== false ) {
		$tmp = substr( $temp, 0, $pos ) . '<span title="' . esc_attr( __( 'No data', 'wp-photo-album-plus' ) ) . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>' . substr( $temp, $pos+6 );
		$temp = $tmp;
		$pos = strpos($temp, 'E#');
	}

	// Return result
	return $temp;
}

function wppa_format_exif( $tag, $data, $brand = '' ) {
global $wppa_exif_error_output;

	if ( $data !== '' ) {
		switch ( $tag ) {

			case 'E#0001': 	// InteropIndex / CanonCameraSettings (Canon)
				switch( $brand ) {
					case 'CANON':	// CanonCameraSettings (Canon)
						$result = $data;
						return $result;
						break;
					default: 		// InteropIndex
						switch( $data ) {
							case 'R03': $result = __( 'R03 - DCF option file (Adobe RGB)', 'wp-photo-album-plus' ); break;
							case 'R98': $result = __( 'R98 - DCF basic file (sRGB)', 'wp-photo-album-plus' ); break;
							case 'THM': $result = __( 'THM - DCF thumbnail file', 'wp-photo-album-plus' ); break;
							default: $result = __( 'Undefined', 'wp-photo-album-plus' );
						}
						return $result;
				}
				break;

			case 'E#0002': 	// CanonFocalLength / DeviceType
				switch( $brand ) {
					case 'SAMSUNG':	// DeviceType
						switch( $data ) {
							case 0x1000: $result = 'Compact Digital Camera'; break;
							case 0x2000: $result = 'High-end NX Camera'; break;
							case 0x3000: $result = 'HXM Video Camera'; break;
							case 0x12000: $result = 'Cell Phone'; break;
							case 0x300000: $result = 'SMX Video Camera'; break;
							default: $result = $data;
						}
						return $result;
						break;

					default:
						$result = $data;
						return $data;
						break;
				}
				break;

			case 'E#0003': 	// CanonFlashInfo? / SamsungModelID
				switch( $brand ) {
					case 'SAMSUNG':	// SamsungModelID
						switch( $data ) {
							case 0x100101c: $result = 'NX10'; break;
							case 0x1001226: $result = 'HMX-S15BP'; break;
							case 0x1001233: $result = 'HMX-Q10'; break;
							case 0x1001234: $result = 'HMX-H304'; break;
							case 0x100130c: $result = 'NX100'; break;
							case 0x1001327: $result = 'NX11'; break;
							case 0x170104b: $result = 'ES65, ES67 / VLUU ES65, ES67 / SL50'; break;
							case 0x170104e: $result = 'ES70, ES71 / VLUU ES70, ES71 / SL600'; break;
							case 0x1701052: $result = 'ES73 / VLUU ES73 / SL605'; break;
							case 0x1701055: $result = 'ES25, ES27 / VLUU ES25, ES27 / SL45'; break;
							case 0x1701300: $result = 'ES28 / VLUU ES28'; break;
							case 0x1701303: $result = 'ES74,ES75,ES78 / VLUU ES75,ES78'; break;
							case 0x2001046: $result = 'PL150 / VLUU PL150 / TL210 / PL151'; break;
							case 0x2001048: $result = 'PL100 / TL205 / VLUU PL100 / PL101'; break;
							case 0x2001311: $result = 'PL120,PL121 / VLUU PL120,PL121'; break;
							case 0x2001315: $result = 'PL170,PL171 / VLUUPL170,PL171'; break;
							case 0x200131e: $result = 'PL210, PL211 / VLUU PL210, PL211'; break;
							case 0x2701317: $result = 'PL20,PL21 / VLUU PL20,PL21'; break;
							case 0x2a0001b: $result = 'WP10 / VLUU WP10 / AQ100'; break;
							case 0x3000000: $result = 'Various Models (0x3000000)'; break;
							case 0x3a00018: $result = 'Various Models (0x3a00018)'; break;
							case 0x400101f: $result = 'ST1000 / ST1100 / VLUU ST1000 / CL65'; break;
							case 0x4001022: $result = 'ST550 / VLUU ST550 / TL225'; break;
							case 0x4001025: $result = 'Various Models (0x4001025)'; break;
							case 0x400103e: $result = 'VLUU ST5500, ST5500, CL80'; break;
							case 0x4001041: $result = 'VLUU ST5000, ST5000, TL240'; break;
							case 0x4001043: $result = 'ST70 / VLUU ST70 / ST71'; break;
							case 0x400130a: $result = 'Various Models (0x400130a)'; break;
							case 0x400130e: $result = 'ST90,ST91 / VLUU ST90,ST91'; break;
							case 0x4001313: $result = 'VLUU ST95, ST95'; break;
							case 0x4a00015: $result = 'VLUU ST60'; break;
							case 0x4a0135b: $result = 'ST30, ST65 / VLUU ST65 / ST67'; break;
							case 0x5000000: $result = 'Various Models (0x5000000)'; break;
							case 0x5001038: $result = 'Various Models (0x5001038)'; break;
							case 0x500103a: $result = 'WB650 / VLUU WB650 / WB660'; break;
							case 0x500103c: $result = 'WB600 / VLUU WB600 / WB610'; break;
							case 0x500133e: $result = 'WB150 / WB150F / WB152 / WB152F / WB151'; break;
							case 0x5a0000f: $result = 'WB5000 / HZ25W'; break;
							case 0x6001036: $result = 'EX1'; break;
							case 0x700131c: $result = 'VLUU SH100, SH100'; break;
							case 0x27127002: $result = 'SMX-C20N'; break;
							default: $result = $data;
						}
						return $result;
						break;

					default:
						$result = $data;
						return $data;
						break;
				}
				break;

			case 'E#0004': 	// CanonShotInfo / Quality (Nikon)
			case 'E#0005': 	// CanonPanorama / WhiteBalance (Nikon)
			case 'E#0006': 	// CanonImageType / Sharpness (Nikon)
			case 'E#0007': 	// CanonFirmwareVersion / FocusMode (Nikon)
			case 'E#0008': 	// FileNumber (Canon) / FlashSetting (Nikon)
			case 'E#0009': 	// OwnerName (Canon) / FlashType (Nikon)
			case 'E#000A': 	// UnknownD30 (Canon)
			case 'E#000B': 	// WhiteBalanceFineTune (Nikon)
			case 'E#000C': 	// SerialNumber (Canon) / WB_RBLevels (Nikon)
			case 'E#000D': 	// CanonCameraInfo / ProgramShift (Nikon)
			case 'E#000E': 	// CanonFileLength / ExposureDifference (Nikon)
			case 'E#000F': 	// CustomFunctions (Canon) / ISOSelection (Nikon)
				$result = $data;
				return $result;
				break;

			case 'E#0010': 	// CanonModelID (Canon) / DataDump (Nikon)
				switch( $brand ) {
					case 'CANON':
						$data = dechex( $data );
						switch( $data ) {
							case '1010000': $result	= 'PowerShot A30'; break;
							case '1040000': $result	= 'PowerShot S300 / Digital IXUS 300 / IXY Digital 300'; break;
							case '1060000': $result	= 'PowerShot A20'; break;
							case '1080000': $result	= 'PowerShot A10'; break;
							case '1090000': $result	= 'PowerShot S110 / Digital IXUS v / IXY Digital 200'; break;
							case '1100000': $result	= 'PowerShot G2'; break;
							case '1110000': $result	= 'PowerShot S40'; break;
							case '1120000': $result	= 'PowerShot S30'; break;
							case '1130000': $result	= 'PowerShot A40'; break;
							case '1140000': $result	= 'EOS D30'; break;
							case '1150000': $result	= 'PowerShot A100'; break;
							case '1160000': $result	= 'PowerShot S200 / Digital IXUS v2 / IXY Digital 200a'; break;
							case '1170000': $result	= 'PowerShot A200'; break;
							case '1180000': $result	= 'PowerShot S330 / Digital IXUS 330 / IXY Digital 300a'; break;
							case '1190000': $result	= 'PowerShot G3'; break;
							case '1210000': $result	= 'PowerShot S45'; break;
							case '1230000': $result	= 'PowerShot SD100 / Digital IXUS II / IXY Digital 30'; break;
							case '1240000': $result	= 'PowerShot S230 / Digital IXUS v3 / IXY Digital 320'; break;
							case '1250000': $result	= 'PowerShot A70'; break;
							case '1260000': $result	= 'PowerShot A60'; break;
							case '1270000': $result	= 'PowerShot S400 / Digital IXUS 400 / IXY Digital 400'; break;
							case '1290000': $result	= 'PowerShot G5'; break;
							case '1300000': $result	= 'PowerShot A300'; break;
							case '1310000': $result	= 'PowerShot S50'; break;
							case '1340000': $result	= 'PowerShot A80'; break;
							case '1350000': $result	= 'PowerShot SD10 / Digital IXUS i / IXY Digital L'; break;
							case '1360000': $result	= 'PowerShot S1 IS'; break;
							case '1370000': $result	= 'PowerShot Pro1'; break;
							case '1380000': $result	= 'PowerShot S70'; break;
							case '1390000': $result	= 'PowerShot S60'; break;
							case '1400000': $result	= 'PowerShot G6'; break;
							case '1410000': $result	= 'PowerShot S500 / Digital IXUS 500 / IXY Digital 500'; break;
							case '1420000': $result	= 'PowerShot A75'; break;
							case '1440000': $result	= 'PowerShot SD110 / Digital IXUS IIs / IXY Digital 30a'; break;
							case '1450000': $result	= 'PowerShot A400'; break;
							case '1470000': $result	= 'PowerShot A310'; break;
							case '1490000': $result	= 'PowerShot A85'; break;
							case '1520000': $result	= 'PowerShot S410 / Digital IXUS 430 / IXY Digital 450'; break;
							case '1530000': $result	= 'PowerShot A95'; break;
							case '1540000': $result	= 'PowerShot SD300 / Digital IXUS 40 / IXY Digital 50'; break;
							case '1550000': $result	= 'PowerShot SD200 / Digital IXUS 30 / IXY Digital 40'; break;
							case '1560000': $result	= 'PowerShot A520'; break;
							case '1570000': $result	= 'PowerShot A510'; break;
							case '1590000': $result	= 'PowerShot SD20 / Digital IXUS i5 / IXY Digital L2'; break;
							case '1640000': $result	= 'PowerShot S2 IS'; break;
							case '1650000': $result	= 'PowerShot SD430 / Digital IXUS Wireless / IXY Digital Wireless'; break;
							case '1660000': $result	= 'PowerShot SD500 / Digital IXUS 700 / IXY Digital 600'; break;
							case '1668000': $result	= 'EOS D60'; break;
							case '1700000': $result	= 'PowerShot SD30 / Digital IXUS i Zoom / IXY Digital L3'; break;
							case '1740000': $result	= 'PowerShot A430'; break;
							case '1750000': $result	= 'PowerShot A410'; break;
							case '1760000': $result	= 'PowerShot S80'; break;
							case '1780000': $result	= 'PowerShot A620'; break;
							case '1790000': $result	= 'PowerShot A610'; break;
							case '1800000': $result	= 'PowerShot SD630 / Digital IXUS 65 / IXY Digital 80'; break;
							case '1810000': $result	= 'PowerShot SD450 / Digital IXUS 55 / IXY Digital 60'; break;
							case '1820000': $result	= 'PowerShot TX1'; break;
							case '1870000': $result	= 'PowerShot SD400 / Digital IXUS 50 / IXY Digital 55'; break;
							case '1880000': $result	= 'PowerShot A420'; break;
							case '1890000': $result	= 'PowerShot SD900 / Digital IXUS 900 Ti / IXY Digital 1000'; break;
							case '1900000': $result	= 'PowerShot SD550 / Digital IXUS 750 / IXY Digital 700'; break;
							case '1920000': $result	= 'PowerShot A700'; break;
							case '1940000': $result	= 'PowerShot SD700 IS / Digital IXUS 800 IS / IXY Digital 800 IS'; break;
							case '1950000': $result	= 'PowerShot S3 IS'; break;
							case '1960000': $result	= 'PowerShot A540'; break;
							case '1970000': $result	= 'PowerShot SD600 / Digital IXUS 60 / IXY Digital 70'; break;
							case '1980000': $result	= 'PowerShot G7'; break;
							case '1990000': $result	= 'PowerShot A530'; break;
							case '2000000': $result	= 'PowerShot SD800 IS / Digital IXUS 850 IS / IXY Digital 900 IS'; break;
							case '2010000': $result	= 'PowerShot SD40 / Digital IXUS i7 / IXY Digital L4'; break;
							case '2020000': $result	= 'PowerShot A710 IS'; break;
							case '2030000': $result	= 'PowerShot A640'; break;
							case '2040000': $result	= 'PowerShot A630'; break;
							case '2090000': $result	= 'PowerShot S5 IS'; break;
							case '2100000': $result	= 'PowerShot A460'; break;
							case '2120000': $result	= 'PowerShot SD850 IS / Digital IXUS 950 IS / IXY Digital 810 IS'; break;
							case '2130000': $result	= 'PowerShot A570 IS'; break;
							case '2140000': $result	= 'PowerShot A560'; break;
							case '2150000': $result	= 'PowerShot SD750 / Digital IXUS 75 / IXY Digital 90'; break;
							case '2160000': $result	= 'PowerShot SD1000 / Digital IXUS 70 / IXY Digital 10'; break;
							case '2180000': $result	= 'PowerShot A550'; break;
							case '2190000': $result	= 'PowerShot A450'; break;
							case '2230000': $result	= 'PowerShot G9'; break;
							case '2240000': $result	= 'PowerShot A650 IS'; break;
							case '2260000': $result	= 'PowerShot A720 IS'; break;
							case '2290000': $result	= 'PowerShot SX100 IS'; break;
							case '2300000': $result	= 'PowerShot SD950 IS / Digital IXUS 960 IS / IXY Digital 2000 IS'; break;
							case '2310000': $result	= 'PowerShot SD870 IS / Digital IXUS 860 IS / IXY Digital 910 IS'; break;
							case '2320000': $result	= 'PowerShot SD890 IS / Digital IXUS 970 IS / IXY Digital 820 IS'; break;
							case '2360000': $result	= 'PowerShot SD790 IS / Digital IXUS 90 IS / IXY Digital 95 IS'; break;
							case '2370000': $result	= 'PowerShot SD770 IS / Digital IXUS 85 IS / IXY Digital 25 IS'; break;
							case '2380000': $result	= 'PowerShot A590 IS'; break;
							case '2390000': $result	= 'PowerShot A580'; break;
							case '2420000': $result	= 'PowerShot A470'; break;
							case '2430000': $result	= 'PowerShot SD1100 IS / Digital IXUS 80 IS / IXY Digital 20 IS'; break;
							case '2460000': $result	= 'PowerShot SX1 IS'; break;
							case '2470000': $result	= 'PowerShot SX10 IS'; break;
							case '2480000': $result	= 'PowerShot A1000 IS'; break;
							case '2490000': $result	= 'PowerShot G10'; break;
							case '2510000': $result	= 'PowerShot A2000 IS'; break;
							case '2520000': $result	= 'PowerShot SX110 IS'; break;
							case '2530000': $result	= 'PowerShot SD990 IS / Digital IXUS 980 IS / IXY Digital 3000 IS'; break;
							case '2540000': $result	= 'PowerShot SD880 IS / Digital IXUS 870 IS / IXY Digital 920 IS'; break;
							case '2550000': $result	= 'PowerShot E1'; break;
							case '2560000': $result	= 'PowerShot D10'; break;
							case '2570000': $result	= 'PowerShot SD960 IS / Digital IXUS 110 IS / IXY Digital 510 IS'; break;
							case '2580000': $result	= 'PowerShot A2100 IS'; break;
							case '2590000': $result	= 'PowerShot A480'; break;
							case '2600000': $result	= 'PowerShot SX200 IS'; break;
							case '2610000': $result	= 'PowerShot SD970 IS / Digital IXUS 990 IS / IXY Digital 830 IS'; break;
							case '2620000': $result	= 'PowerShot SD780 IS / Digital IXUS 100 IS / IXY Digital 210 IS'; break;
							case '2630000': $result	= 'PowerShot A1100 IS'; break;
							case '2640000': $result	= 'PowerShot SD1200 IS / Digital IXUS 95 IS / IXY Digital 110 IS'; break;
							case '2700000': $result	= 'PowerShot G11'; break;
							case '2710000': $result	= 'PowerShot SX120 IS'; break;
							case '2720000': $result	= 'PowerShot S90'; break;
							case '2750000': $result	= 'PowerShot SX20 IS'; break;
							case '2760000': $result	= 'PowerShot SD980 IS / Digital IXUS 200 IS / IXY Digital 930 IS'; break;
							case '2770000': $result	= 'PowerShot SD940 IS / Digital IXUS 120 IS / IXY Digital 220 IS'; break;
							case '2800000': $result	= 'PowerShot A495'; break;
							case '2810000': $result	= 'PowerShot A490'; break;
							case '2820000': $result	= 'PowerShot A3100/A3150 IS'; break;
							case '2830000': $result	= 'PowerShot A3000 IS'; break;
							case '2840000': $result	= 'PowerShot SD1400 IS / IXUS 130 / IXY 400F'; break;
							case '2850000': $result	= 'PowerShot SD1300 IS / IXUS 105 / IXY 200F'; break;
							case '2860000': $result	= 'PowerShot SD3500 IS / IXUS 210 / IXY 10S'; break;
							case '2870000': $result	= 'PowerShot SX210 IS'; break;
							case '2880000': $result	= 'PowerShot SD4000 IS / IXUS 300 HS / IXY 30S'; break;
							case '2890000': $result	= 'PowerShot SD4500 IS / IXUS 1000 HS / IXY 50S'; break;
							case '2920000': $result	= 'PowerShot G12'; break;
							case '2930000': $result	= 'PowerShot SX30 IS'; break;
							case '2940000': $result	= 'PowerShot SX130 IS'; break;
							case '2950000': $result	= 'PowerShot S95'; break;
							case '2980000': $result	= 'PowerShot A3300 IS'; break;
							case '2990000': $result	= 'PowerShot A3200 IS'; break;
							case '3000000': $result	= 'PowerShot ELPH 500 HS / IXUS 310 HS / IXY 31S'; break;
							case '3010000': $result	= 'PowerShot Pro90 IS'; break;
							case '3010001': $result	= 'PowerShot A800'; break;
							case '3020000': $result	= 'PowerShot ELPH 100 HS / IXUS 115 HS / IXY 210F'; break;
							case '3030000': $result	= 'PowerShot SX230 HS'; break;
							case '3040000': $result	= 'PowerShot ELPH 300 HS / IXUS 220 HS / IXY 410F'; break;
							case '3050000': $result	= 'PowerShot A2200'; break;
							case '3060000': $result	= 'PowerShot A1200'; break;
							case '3070000': $result	= 'PowerShot SX220 HS'; break;
							case '3080000': $result	= 'PowerShot G1 X'; break;
							case '3090000': $result	= 'PowerShot SX150 IS'; break;
							case '3100000': $result	= 'PowerShot ELPH 510 HS / IXUS 1100 HS / IXY 51S'; break;
							case '3110000': $result	= 'PowerShot S100 (new)'; break;
							case '3120000': $result	= 'PowerShot ELPH 310 HS / IXUS 230 HS / IXY 600F'; break;
							case '3130000': $result	= 'PowerShot SX40 HS'; break;
							case '3140000': $result	= 'IXY 32S'; break;
							case '3160000': $result	= 'PowerShot A1300'; break;
							case '3170000': $result	= 'PowerShot A810'; break;
							case '3180000': $result	= 'PowerShot ELPH 320 HS / IXUS 240 HS / IXY 420F'; break;
							case '3190000': $result	= 'PowerShot ELPH 110 HS / IXUS 125 HS / IXY 220F'; break;
							case '3200000': $result	= 'PowerShot D20'; break;
							case '3210000': $result	= 'PowerShot A4000 IS'; break;
							case '3220000': $result	= 'PowerShot SX260 HS'; break;
							case '3230000': $result	= 'PowerShot SX240 HS'; break;
							case '3240000': $result	= 'PowerShot ELPH 530 HS / IXUS 510 HS / IXY 1'; break;
							case '3250000': $result	= 'PowerShot ELPH 520 HS / IXUS 500 HS / IXY 3'; break;
							case '3260000': $result	= 'PowerShot A3400 IS'; break;
							case '3270000': $result	= 'PowerShot A2400 IS'; break;
							case '3280000': $result	= 'PowerShot A2300'; break;
							case '3330000': $result	= 'PowerShot G15'; break;
							case '3340000': $result	= 'PowerShot SX50 HS'; break;
							case '3350000': $result	= 'PowerShot SX160 IS'; break;
							case '3360000': $result	= 'PowerShot S110 (new)'; break;
							case '3370000': $result	= 'PowerShot SX500 IS'; break;
							case '3380000': $result	= 'PowerShot N'; break;
							case '3390000': $result	= 'IXUS 245 HS / IXY 430F'; break;
							case '3400000': $result	= 'PowerShot SX280 HS'; break;
							case '3410000': $result	= 'PowerShot SX270 HS'; break;
							case '3420000': $result	= 'PowerShot A3500 IS'; break;
							case '3430000': $result	= 'PowerShot A2600'; break;
							case '3440000': $result	= 'PowerShot SX275 HS'; break;
							case '3450000': $result	= 'PowerShot A1400'; break;
							case '3460000': $result	= 'PowerShot ELPH 130 IS / IXUS 140 / IXY 110F'; break;
							case '3470000': $result	= 'PowerShot ELPH 115/120 IS / IXUS 132/135 / IXY 90F/100F'; break;
							case '3490000': $result	= 'PowerShot ELPH 330 HS / IXUS 255 HS / IXY 610F'; break;
							case '3510000': $result	= 'PowerShot A2500'; break;
							case '3540000': $result	= 'PowerShot G16'; break;
							case '3550000': $result	= 'PowerShot S120'; break;
							case '3560000': $result	= 'PowerShot SX170 IS'; break;
							case '3580000': $result	= 'PowerShot SX510 HS'; break;
							case '3590000': $result	= 'PowerShot S200 (new)'; break;
							case '3600000': $result	= 'IXY 620F'; break;
							case '3610000': $result	= 'PowerShot N100'; break;
							case '3640000': $result	= 'PowerShot G1 X Mark II'; break;
							case '3650000': $result	= 'PowerShot D30'; break;
							case '3660000': $result	= 'PowerShot SX700 HS'; break;
							case '3670000': $result	= 'PowerShot SX600 HS'; break;
							case '3680000': $result	= 'PowerShot ELPH 140 IS / IXUS 150 / IXY 130'; break;
							case '3690000': $result	= 'PowerShot ELPH 135 / IXUS 145 / IXY 120'; break;
							case '3700000': $result	= 'PowerShot ELPH 340 HS / IXUS 265 HS / IXY 630'; break;
							case '3710000': $result	= 'PowerShot ELPH 150 IS / IXUS 155 / IXY 140'; break;
							case '3740000': $result	= 'EOS M3'; break;
							case '3750000': $result	= 'PowerShot SX60 HS'; break;
							case '3760000': $result	= 'PowerShot SX520 HS'; break;
							case '3770000': $result	= 'PowerShot SX400 IS'; break;
							case '3780000': $result	= 'PowerShot G7 X'; break;
							case '3790000': $result	= 'PowerShot N2'; break;
							case '3800000': $result	= 'PowerShot SX530 HS'; break;
							case '3820000': $result	= 'PowerShot SX710 HS'; break;
							case '3830000': $result	= 'PowerShot SX610 HS'; break;
							case '3840000': $result	= 'EOS M10'; break;
							case '3850000': $result	= 'PowerShot G3 X'; break;
							case '3860000': $result	= 'PowerShot ELPH 165 HS / IXUS 165 / IXY 160'; break;
							case '3870000': $result	= 'PowerShot ELPH 160 / IXUS 160'; break;
							case '3880000': $result	= 'PowerShot ELPH 350 HS / IXUS 275 HS / IXY 640'; break;
							case '3890000': $result	= 'PowerShot ELPH 170 IS / IXUS 170'; break;
							case '3910000': $result	= 'PowerShot SX410 IS'; break;
							case '3930000': $result	= 'PowerShot G9 X'; break;
							case '3940000': $result	= 'EOS M5'; break;
							case '3950000': $result	= 'PowerShot G5 X'; break;
							case '3970000': $result	= 'PowerShot G7 X Mark II'; break;
							case '3980000': $result	= 'EOS M100'; break;
							case '3990000': $result	= 'PowerShot ELPH 360 HS / IXUS 285 HS / IXY 650'; break;
							case '4010000': $result	= 'PowerShot SX540 HS'; break;
							case '4020000': $result	= 'PowerShot SX420 IS'; break;
							case '4030000': $result	= 'PowerShot ELPH 190 IS / IXUS 180 / IXY 190'; break;
							case '4040000': $result	= 'PowerShot G1'; break;
							case '4040001': $result	= 'IXY 180'; break;
							case '4050000': $result	= 'PowerShot SX720 HS'; break;
							case '4060000': $result	= 'PowerShot SX620 HS'; break;
							case '4070000': $result	= 'EOS M6'; break;
							case '4100000': $result	= 'PowerShot G9 X Mark II'; break;
							case '4150000': $result	= 'PowerShot ELPH 185 / IXUS 185 / IXY 200'; break;
							case '4160000': $result	= 'PowerShot SX430 IS'; break;
							case '4170000': $result	= 'PowerShot SX730 HS'; break;
							case '4180000': $result	= 'PowerShot G1 X Mark III'; break;
							case '6040000': $result	= 'PowerShot S100 / Digital IXUS / IXY Digital'; break;
							case '4007d673': $result = 'DC19/DC21/DC22'; break;
							case '4007d674': $result = 'XH A1'; break;
							case '4007d675': $result = 'HV10'; break;
							case '4007d676': $result = 'MD130/MD140/MD150/MD160/ZR850'; break;
							case '4007d777': $result = 'DC50'; break;
							case '4007d778': $result = 'HV20'; break;
							case '4007d779': $result = 'DC211'; break;
							case '4007d77a': $result = 'HG10'; break;
							case '4007d77b': $result = 'HR10'; break;
							case '4007d77d': $result = 'MD255/ZR950'; break;
							case '4007d81c': $result = 'HF11'; break;
							case '4007d878': $result = 'HV30'; break;
							case '4007d87c': $result = 'XH A1S'; break;
							case '4007d87e': $result = 'DC301/DC310/DC311/DC320/DC330'; break;
							case '4007d87f': $result = 'FS100'; break;
							case '4007d880': $result = 'HF10'; break;
							case '4007d882': $result = 'HG20/HG21'; break;
							case '4007d925': $result = 'HF21'; break;
							case '4007d926': $result = 'HF S11'; break;
							case '4007d978': $result = 'HV40'; break;
							case '4007d987': $result = 'DC410/DC411/DC420'; break;
							case '4007d988': $result = 'FS19/FS20/FS21/FS22/FS200'; break;
							case '4007d989': $result = 'HF20/HF200'; break;
							case '4007d98a': $result = 'HF S10/S100'; break;
							case '4007da8e': $result = 'HF R10/R16/R17/R18/R100/R106'; break;
							case '4007da8f': $result = 'HF M30/M31/M36/M300/M306'; break;
							case '4007da90': $result = 'HF S20/S21/S200'; break;
							case '4007da92': $result = 'FS31/FS36/FS37/FS300/FS305/FS306/FS307'; break;
							case '4007dca0': $result = 'EOS C300'; break;
							case '4007dda9': $result = 'HF G25'; break;
							case '4007dfb4': $result = 'XC10'; break;
							case '80000001': $result = 'EOS-1D'; break;
							case '80000167': $result = 'EOS-1DS'; break;
							case '80000168': $result = 'EOS 10D'; break;
							case '80000169': $result = 'EOS-1D Mark III'; break;
							case '80000170': $result = 'EOS Digital Rebel / 300D / Kiss Digital'; break;
							case '80000174': $result = 'EOS-1D Mark II'; break;
							case '80000175': $result = 'EOS 20D'; break;
							case '80000176': $result = 'EOS Digital Rebel XSi / 450D / Kiss X2'; break;
							case '80000188': $result = 'EOS-1Ds Mark II'; break;
							case '80000189': $result = 'EOS Digital Rebel XT / 350D / Kiss Digital N'; break;
							case '80000190': $result = 'EOS 40D'; break;
							case '80000213': $result = 'EOS 5D'; break;
							case '80000215': $result = 'EOS-1Ds Mark III'; break;
							case '80000218': $result = 'EOS 5D Mark II'; break;
							case '80000219': $result = 'WFT-E1'; break;
							case '80000232': $result = 'EOS-1D Mark II N'; break;
							case '80000234': $result = 'EOS 30D'; break;
							case '80000236': $result = 'EOS Digital Rebel XTi / 400D / Kiss Digital X'; break;
							case '80000241': $result = 'WFT-E2'; break;
							case '80000246': $result = 'WFT-E3'; break;
							case '80000250': $result = 'EOS 7D'; break;
							case '80000252': $result = 'EOS Rebel T1i / 500D / Kiss X3'; break;
							case '80000254': $result = 'EOS Rebel XS / 1000D / Kiss F'; break;
							case '80000261': $result = 'EOS 50D'; break;
							case '80000269': $result = 'EOS-1D X'; break;
							case '80000270': $result = 'EOS Rebel T2i / 550D / Kiss X4'; break;
							case '80000271': $result = 'WFT-E4'; break;
							case '80000273': $result = 'WFT-E5'; break;
							case '80000281': $result = 'EOS-1D Mark IV'; break;
							case '80000285': $result = 'EOS 5D Mark III'; break;
							case '80000286': $result = 'EOS Rebel T3i / 600D / Kiss X5'; break;
							case '80000287': $result = 'EOS 60D'; break;
							case '80000288': $result = 'EOS Rebel T3 / 1100D / Kiss X50'; break;
							case '80000289': $result = 'EOS 7D Mark II'; break;
							case '80000297': $result = 'WFT-E2 II'; break;
							case '80000298': $result = 'WFT-E4 II'; break;
							case '80000301': $result = 'EOS Rebel T4i / 650D / Kiss X6i'; break;
							case '80000302': $result = 'EOS 6D'; break;
							case '80000324': $result = 'EOS-1D C'; break;
							case '80000325': $result = 'EOS 70D'; break;
							case '80000326': $result = 'EOS Rebel T5i / 700D / Kiss X7i'; break;
							case '80000327': $result = 'EOS Rebel T5 / 1200D / Kiss X70'; break;
							case '80000328': $result = 'EOS-1D X MARK II'; break;
							case '80000331': $result = 'EOS M'; break;
							case '80000346': $result = 'EOS Rebel SL1 / 100D / Kiss X7'; break;
							case '80000347': $result = 'EOS Rebel T6s / 760D / 8000D'; break;
							case '80000349': $result = 'EOS 5D Mark IV'; break;
							case '80000350': $result = 'EOS 80D'; break;
							case '80000355': $result = 'EOS M2'; break;
							case '80000382': $result = 'EOS 5DS'; break;
							case '80000393': $result = 'EOS Rebel T6i / 750D / Kiss X8i'; break;
							case '80000401': $result = 'EOS 5DS R'; break;
							case '80000404': $result = 'EOS Rebel T6 / 1300D / Kiss X80'; break;
							case '80000405': $result = 'EOS Rebel T7i / 800D / Kiss X9i'; break;
							case '80000406': $result = 'EOS 6D Mark II'; break;
							case '80000408': $result = 'EOS 77D / 9000D'; break;
							case '80000417': $result = 'EOS Rebel SL2 / 200D / Kiss X9'; break;

							default:
								$result = $data;
						}
						return $result;
						break;
					case 'NIKON':
						$result = $data;
						return $result;
						break;
					default:
						$result = $data;
						return $result;
				}
				break;

			case 'E#0011': 	// MovieInfo / OrientationInfo
				switch( $brand ) {
					case 'SAMSUNG': 	// OrientationInfo

						if ( ! wppa_is_valid_rational( $data ) ) {
							return $wppa_exif_error_output;
						}

						$temp = explode( '/', $data );
						$x = $temp[0];
						$y = $temp[1];

						$result = ( $x / $y ) . ' ' . __( 'degrees', 'wp-photo-album-plus' );

						return $result;
						break;
					default:
						$result = $data;
						return $result;
				}
				break;

			case 'E#0012': 	// CanonAFInfo
			case 'E#0013': 	// ThumbnailImageValidArea
				$result = $data;
				return $result;

			case 'E#0015': 	// SerialNumberFormat

				switch( $brand ) {
					case 'CANON':
						if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;
						$data = dechex( $data );
						switch( $data ) {
							case '90000000': $result = __( 'Format 1', 'wp-photo-album-plus' ); break;
							case 'a0000000': $result = __( 'Format 2', 'wp-photo-album-plus' ); break;
							default: $result = __( 'undefined', 'wp-photo-album-plus' );
						}
						return $result;
					default:
						$result = $data;
						return $result;
				}
				break;

			case 'E#001A': 	// SuperMacro
			case 'E#001C': 	// DateStampMode
			case 'E#001D': 	// MyColors
				$result = $data;
				return $result;

			case 'E#001E': 	// FirmwareRevision (Canon) / ColorSpace (Nikon)
				switch( $brand ) {
					case 'CANON':
						$result = $data;
						return $result;
						break;
					case 'NIKON':
						switch( $data ) {
							case 1: $result = 'sRGB'; break;
							case 2: $result = 'Adobe RGB'; break;
							default: $result = $data;
						}
						return $result;
						break;
					default:
						$result = $data;
						return $result;
				}
				break;

			case 'E#0020': 	// SmartAlbumColor ( samsung ) / ImageAuthentication (Nikon)
				switch( $brand ) {
					case 'SAMSUNG':
						switch( $data ) {
							case '0 0': $result = __( 'n.a.', 'wp-photo-album-plus' ); break;
							case 0: $result = __( 'Red', 'wp-photo-album-plus' ); break;
							case 1: $result = __( 'Yellow', 'wp-photo-album-plus' ); break;
							case 2: $result = __( 'Green', 'wp-photo-album-plus' ); break;
							case 3: $result = __( 'Blue', 'wp-photo-album-plus' ); break;
							case 4: $result = __( 'Magenta', 'wp-photo-album-plus' ); break;
							case 5: $result = __( 'Black', 'wp-photo-album-plus' ); break;
							case 6: $result = __( 'White', 'wp-photo-album-plus' ); break;
							case 7: $result = __( 'Various', 'wp-photo-album-plus' ); break;
							default: $result = $data;
						}
						return $result;
						break;
					case 'NIKON':
						switch( $data ) {
							case 0: $result = __( 'Off', 'wp-photo-album-plus' ); break;
							case 1: $result = __( 'On', 'wp-photo-album-plus' ); break;
							default: $result = $data;
						}
						return $result;
					default:
						$result = $data;
						return $result;
				}

			case 'E#0023': 	// Categories
			case 'E#0024': 	// FaceDetect1
			case 'E#0025': 	// FaceDetect2
			case 'E#0026': 	// CanonAFInfo2
			case 'E#0027': 	// ContrastInfo
			case 'E#0028': 	// ImageUniqueID
			case 'E#002F': 	// FaceDetect3
			case 'E#0035': 	// TimeInfo
			case 'E#003C': 	// AFInfo3
			case 'E#0081': 	// RawDataOffset
			case 'E#0083': 	// OriginalDecisionDataOffset
			case 'E#0090': 	// CustomFunctions1D
			case 'E#0091': 	// PersonalFunctions
			case 'E#0092': 	// PersonalFunctionValues
			case 'E#0093': 	// CanonFileInfo
			case 'E#0094': 	// AFPointsInFocus1D
			case 'E#0095': 	// LensModel
			case 'E#0096': 	// SerialInfo
			case 'E#0097': 	// DustRemovalData
			case 'E#0098': 	// CropInfo
			case 'E#0099': 	// CustomFunctions2
			case 'E#009A': 	// AspectInfo
			case 'E#00A0': 	// ProcessingInfo
			case 'E#00A1': 	// ToneCurveTable
			case 'E#00A2': 	// SharpnessTable
			case 'E#00A3': 	// SharpnessFreqTable
			case 'E#00A4': 	// WhiteBalanceTable
			case 'E#00A9': 	// ColorBalance
			case 'E#00AA': 	// MeasuredColor
			case 'E#00AE': 	// ColorTemperature
			case 'E#00B0': 	// CanonFlags
			case 'E#00B1': 	// ModifiedInfo
			case 'E#00B2': 	// ToneCurveMatching
			case 'E#00B3': 	// WhiteBalanceMatching
			case 'E#00B4': 	// ColorSpace
			case 'E#00B6': 	// PreviewImageInfo
			case 'E#00D0': 	// VRDOffset
			case 'E#00E0': 	// SensorInfo

				$result = $data;
				return $result;
				break;

			case 'E#00FE': // SubfileType (called NewSubfileType by the TIFF specification)

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 0x0: $result = __( 'Full-resolution Image', 'wp-photo-album-plus' ); break;
					case 0x1: $result = __( 'Reduced-resolution image', 'wp-photo-album-plus' ); break;
					case 0x2: $result = __( 'Single page of multi-page image', 'wp-photo-album-plus' ); break;
					case 0x3: $result = __( 'Single page of multi-page reduced-resolution image', 'wp-photo-album-plus' ); break;
					case 0x4: $result = __( 'Transparency mask', 'wp-photo-album-plus' ); break;
					case 0x5: $result = __( 'Transparency mask of reduced-resolution image', 'wp-photo-album-plus' ); break;
					case 0x6: $result = __( 'Transparency mask of multi-page image', 'wp-photo-album-plus' ); break;
					case 0x7: $result = __( 'Transparency mask of reduced-resolution multi-page image', 'wp-photo-album-plus' ); break;
					case 0x10001: $result = __( 'Alternate reduced-resolution image', 'wp-photo-album-plus' ); break;
					case 0xffffffff: $result = __( 'invalid', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#00FF': 	// 	OldSubfileType (called SubfileType by the TIFF specification)

				if ( ! wppa_is_valid_integer( $data ) ) {
					return $wppa_exif_error_output;
				}

				switch( $data ) {
					case 1: $result = __( 'Full-resolution image', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Reduced-resolution image', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Single page of multi-page image', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#0100': 	// Image width (pixels), Short or long, 1 item
			case 'E#0101': 	// Image length (pixels), Short or long, 1 item

				if ( ! wppa_is_valid_integer( $data ) ) {
					return $wppa_exif_error_output;
				}

				$result = $data . ' ' . __( 'px.', 'wp-photo-album-plus' );
				return $result;
				break;

			case 'E#0106': // PhotometricInterpretation

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 0: $result = __( 'WhiteIsZero', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'BlackIsZero', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'RGB', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'RGB Palette', 'wp-photo-album-plus' ); break;
					case 4: $result = __( 'Transparency Mask', 'wp-photo-album-plus' ); break;
					case 5: $result = __( 'CMYK', 'wp-photo-album-plus' ); break;
					case 6: $result = __( 'YCbCr', 'wp-photo-album-plus' ); break;
					case 8: $result = __( 'CIELab', 'wp-photo-album-plus' ); break;
					case 9: $result = __( 'ICCLab', 'wp-photo-album-plus' ); break;
					case 10: $result = __( 'ITULab', 'wp-photo-album-plus' ); break;
					case 32803: $result = __( 'Color Filter Array', 'wp-photo-album-plus' ); break;
					case 32844: $result = __( 'Pixar LogL', 'wp-photo-album-plus' ); break;
					case 32845: $result = __( 'Pixar LogLuv', 'wp-photo-album-plus' ); break;
					case 34892: $result = __( 'Linear Raw', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#0107': // Thresholding

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 1: $result = __( 'No dithering or halftoning', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Ordered dither or halftone', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Randomized dither', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#010A': // FillOrder

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 1: $result = __( 'Normal', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Reversed', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#0112': 	// Orientation

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 1: $result = __( 'Horizontal (normal)', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Mirror horizontal', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Rotate 180', 'wp-photo-album-plus' ); break;
					case 4: $result = __( 'Mirror vertical', 'wp-photo-album-plus' ); break;
					case 5: $result = __( 'Mirror horizontal and rotate 270 CW', 'wp-photo-album-plus' ); break;
					case 6: $result = __( 'Rotate 90 CW', 'wp-photo-album-plus' ); break;
					case 7: $result = __( 'Mirror horizontal and rotate 90 CW', 'wp-photo-album-plus' ); break;
					case 8: $result = __( 'Rotate 270 CW', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#011A': 	// XResolution
			case 'E#011B': 	// YResolution

				if ( ! wppa_is_valid_rational( $data ) ) {
					return $wppa_exif_error_output;
				}

				// Format is valid
				$temp = explode( '/', $data );
				$x = $temp[0];
				$y = $temp[1];

				$result = ( $x / $y );
				return $result;
				break;

			case 'E#011C': 	// PlanarConfiguration

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 1: $result = __( 'Chunky', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Planar', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#0122': 	// GrayResponseUnit

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 1: $result = '0.1'; break;
					case 2: $result = '0.001'; break;
					case 3: $result = '0.0001'; break;
					case 4: $result = '1e-05'; break;
					case 5: $result = '1e-06'; break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#0124': 	// T4Options

				$result = '';
				if ( $data & 0x0001 ) $result .= __( '2-Dimensional encoding', 'wp-photo-album-plus' ) . ' ';
				if ( $data & 0x0002 ) $result .= __( 'Uncompressed', 'wp-photo-album-plus' ) . ' ';
				if ( $data & 0x0004 ) $result .= __( 'Fill bits added', 'wp-photo-album-plus' );
				$result = trim( $result );
				if ( ! $result ) $result = __( 'Undefined', 'wp-photo-album-plus' );
				return $result;
				break;

			case 'E#0125': 	// T6Options
				if ( $data & 0x0001 ) $result = __( 'Uncompressed', 'wp-photo-album-plus' );
				else $result = __( 'Undefined', 'wp-photo-album-plus' );
				return $result;
				break;

			case 'E#0128': 	// Resolution unit

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 2:	$result = __( 'inches', 'wp-photo-album-plus' ); break;
					case 3:	$result = __( 'centimeters', 'wp-photo-album-plus' ); break;
					default: $result = __( 'reserved', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#013D': 	// Predictor

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 1: $result = __( 'None', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Horizontal differencing', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#0147': 	// CleanFaxData

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'Clean', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Regenerated', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Unclean', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#014C': 	// InkSet

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 1: $result = __( 'CMYK', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Not CMYK', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#0152': 	// ExtraSamples

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'Unspecified', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Associated Alpha', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Unassociated Alpha', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#0153': 	// SampleFormat

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 1: $result = __( 'Unsigned', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Signed', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Float', 'wp-photo-album-plus' ); break;
					case 4: $result = __( 'Undefined', 'wp-photo-album-plus' ); break;
					case 5: $result = __( 'Complex int', 'wp-photo-album-plus' ); break;
					case 6: $result = __( 'Complex float', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#015A': 	// Indexed

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'Not indexed', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Indexed', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#015F': 	// OPIProxy

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'Higher resolution image does not exist', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Higher resolution image exists', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#0191': 	// ProfileType

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'Unspecified', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Group 3 FAX', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#0192': 	// FaxProfile

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'Unknown', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Minimal B&W lossless, S', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Extended B&W lossless, F', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Lossless JBIG B&W, J', 'wp-photo-album-plus' ); break;
					case 4: $result = __( 'Lossy color and grayscale, C', 'wp-photo-album-plus' ); break;
					case 5: $result = __( 'Lossless color and grayscale, L', 'wp-photo-album-plus' ); break;
					case 6: $result = __( 'Mixed raster content, M', 'wp-photo-album-plus' ); break;
					case 7: $result = __( 'Profile T', 'wp-photo-album-plus' ); break;
					case 255: $result = __( 'Multi Profiles', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#0193': 	// CodingMethods

				$result = '';
				if ( $data & 0x01 ) {
					$result .= __( 'Unspecified compression', 'wp-photo-album-plus' ) . ', ';
				}
				if ( $data & 0x02 ) {
					$result .= __( 'Modified Huffman', 'wp-photo-album-plus' ) . ', ';
				}
				if ( $data & 0x04 ) {
					$result .= __( 'Modified Read', 'wp-photo-album-plus' ) . ', ';
				}
				if ( $data & 0x08 ) {
					$result .= __( 'Modified MR', 'wp-photo-album-plus' ) . ', ';
				}
				if ( $data & 0x10 ) {
					$result .= __( 'JBIG', 'wp-photo-album-plus' ) . ', ';
				}
				if ( $data & 0x20 ) {
					$result .= __( 'Baseline JPEG', 'wp-photo-album-plus' ) . ', ';
				}
				if ( $data & 0x40 ) {
					$result .= __( 'JBIG color', 'wp-photo-album-plus' ) . ', ';
				}
				$result = trim( $result, ', ' );
				if ( ! $result ) $result = __( 'Undefined', 'wp-photo-album-plus' );
				return $result;
				break;

			case 'E#A210': 	// FocalPlaneResolutionUnit

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 2:	$result = __( 'inches', 'wp-photo-album-plus' ); break;
					case 3:	$result = __( 'centimeters', 'wp-photo-album-plus' ); break;
					default: $result = __( 'reserved', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#0212': 	// YCbCrSubSampling

				/*
				'1 1' = YCbCr4:4:4 (1 1)
				'1 2' = YCbCr4:4:0 (1 2)
				'1 4' = YCbCr4:4:1 (1 4)
				'2 1' = YCbCr4:2:2 (2 1)
				'2 2' = YCbCr4:2:0 (2 2)
				'2 4' = YCbCr4:2:1 (2 4)
				'4 1' = YCbCr4:1:1 (4 1)
				'4 2' = YCbCr4:1:0 (4 2)
				*/
				$result = $data;
				return $result;
				break;

			case 'E#0213': 	// YCbCrPositioning

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 1:
						$result = __( 'centered', 'wp-photo-album-plus' );
						break;
					case 2:
						$result = __( 'co-sited', 'wp-photo-album-plus' );
						break;
					default:
						$result = __( 'reserved', 'wp-photo-album-plus' );
				}

				return $result;
				break;

			case 'E#4001': 	// ColorData
			case 'E#4002': 	// CRWParam?
			case 'E#4003': 	// ColorInfo
			case 'E#4005': 	// Flavor?

				$result = $data;
				return $result;
				break;

			case 'E#4008': 	// PictureStyleUserDef
				switch( $brand ) {
					case 'CANON':
						switch( $data ) {
							case 0x0: $result = __( 'None', 'wp-photo-album-plus' ); break;
							case 0x1: $result = __( 'Standard', 'wp-photo-album-plus' ); break;
							case 0x2: $result = __( 'Portrait', 'wp-photo-album-plus' ); break;
							case 0x3: $result = __( 'High Saturation', 'wp-photo-album-plus' ); break;
							case 0x4: $result = __( 'Adobe RGB', 'wp-photo-album-plus' ); break;
							case 0x5: $result = __( 'Low Saturation', 'wp-photo-album-plus' ); break;
							case 0x6: $result = __( 'CM Set 1', 'wp-photo-album-plus' ); break;
							case 0x7: $result = __( 'CM Set 2', 'wp-photo-album-plus' ); break;
							case 0x21: $result = __( 'User Def. 1', 'wp-photo-album-plus' ); break;
							case 0x22: $result = __( 'User Def. 2', 'wp-photo-album-plus' ); break;
							case 0x23: $result = __( 'User Def. 3', 'wp-photo-album-plus' ); break;
							case 0x41: $result = __( 'PC 1', 'wp-photo-album-plus' ); break;
							case 0x42: $result = __( 'PC 2', 'wp-photo-album-plus' ); break;
							case 0x43: $result = __( 'PC 3', 'wp-photo-album-plus' ); break;
							case 0x81: $result = __( 'Standard', 'wp-photo-album-plus' ); break;
							case 0x82: $result = __( 'Portrait', 'wp-photo-album-plus' ); break;
							case 0x83: $result = __( 'Landscape', 'wp-photo-album-plus' ); break;
							case 0x84: $result = __( 'Neutral', 'wp-photo-album-plus' ); break;
							case 0x85: $result = __( 'Faithful', 'wp-photo-album-plus' ); break;
							case 0x86: $result = __( 'Monochrome', 'wp-photo-album-plus' ); break;
							case 0x87: $result = __( 'Auto', 'wp-photo-album-plus' ); break;
							case 0x88: $result = __( 'Fine Detail', 'wp-photo-album-plus' ); break;
							case 0xff: $result = __( 'n/a', 'wp-photo-album-plus' ); break;
							case 0xffff: $result = __( 'n/a', 'wp-photo-album-plus' ); break;
							default: $result = $data;
						}
						break;

					default:
						$result = $data;
				}
				return $result;
				break;

			case 'E#4009': 	// PictureStylePC
			case 'E#4010': 	// CustomPictureStyleFileName
			case 'E#4013': 	// AFMicroAdj
			case 'E#4015': 	// VignettingCorr
			case 'E#4016': 	// VignettingCorr2
			case 'E#4018': 	// LightingOpt
			case 'E#4019': 	// LensInfo
				$result = $data;
				return $result;
				break;


			case 'E#4020': 	// AmbienceInfo
				if ( $brand == 'CANON' ) {
					switch( $data ) {
						case 0: $result = __( 'Standard', 'wp-photo-album-plus' ); break;
						case 1: $result = __( 'Vivid', 'wp-photo-album-plus' ); break;
						case 2: $result = __( 'Warm', 'wp-photo-album-plus' ); break;
						case 3: $result = __( 'Soft', 'wp-photo-album-plus' ); break;
						case 4: $result = __( 'Cool', 'wp-photo-album-plus' ); break;
						case 5: $result = __( 'Intense', 'wp-photo-album-plus' ); break;
						case 6: $result = __( 'Brighter', 'wp-photo-album-plus' ); break;
						case 7: $result = __( 'Darker', 'wp-photo-album-plus' ); break;
						case 8: $result = __( 'Monochrome', 'wp-photo-album-plus' ); break;
						default: $result = $data;
					}
					return $result;
				}
				else {
					$result = $data;
				}
				return $result;

			case 'E#4021': 	// MultiExp
			case 'E#4024': 	// FilterInfo
			case 'E#4025': 	// HDRInfo
			case 'E#4028': 	// AFConfig

				$result = $data;
				return $result;
				break;

			case 'E#7000': 	// SonyRawFileType

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'Sony Uncompressed 14-bit RAW', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Sony Uncompressed 12-bit RAW', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Sony Compressed RAW', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Sony Lossless Compressed RAW', 'wp-photo-album-plus' ); break;
					default: $result = __( 'reserved', 'wp-photo-album-plus' );
				}

				return $result;
				break;

			case 'E#829A': 	// Exposure time

				if ( ! wppa_is_valid_rational( $data ) ) {
					return $wppa_exif_error_output;
				}

				// Format is valid
				$temp = explode( '/', $data );
				$x = $temp[0];
				$y = $temp[1];

				// 1 s.
				if ( $x / $y == 1 ) {
					$result = '1 s.';
					return $result;
				}

				// Normal: 1/nn s.
				if ( $x == 1 ) {
					$result = $data . ' s.';
					return $result;
				}

				// 'nn/1'
				if ( $y == 1 ) {
					$result = $x . ' s.';
					return $result;
				}

				// Simplify nnn/mmm > 1
				if ( ( $x / $y ) > 1 ) {
					$result = sprintf( '%2.1f', $x / $y );
					if ( substr( $result, -2 ) == '.0' ) { 	// Remove trailing '.0'
						$result = substr( $result, 0, strlen( $result ) -2 ) . ' s.';
					}
					else {
						$result .= ' s.';
					}
					return $result;
				}

				// Simplify nnn/mmm < 1
				$v = $y / $x;
				$z = round( $v ) / $v;
				if ( 0.99 < $z && $z < 1.01 ) {
					if ( round( $v ) == '1' ) {
						$result = '1 s.';
					}
					else {
						$result = '1/' . round( $v ) . ' s.';
					}
				}
				else {
					$z = $x / $y;
					$i = 2;
					$n = 0;
					while ( $n < 2 && $i < strlen( $z ) ) {
						if ( substr( $z, $i, 1 ) != '0' ) {
							$n++;
						}
						$i++;
					}
					$result = substr( $z, 0, $i ) . ' s.';
				}
				return $result;
				break;

			case 'E#829D':	// F-Stop

				// Invalid format?
				if ( ! wppa_is_valid_rational( $data ) ) {
					return $wppa_exif_error_output;
				}

				// Format is valid
				$temp = explode( '/', $data );
				$x = $temp[0];
				$y = $temp[1];

				// Bogus data?
				if ( $x / $y > 100 || ( round( 10 * $x / $y ) / 10 ) == 0 ) {
					$result = '<span title="' . esc_attr( __( 'Impossible data', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
					return $result;
				}

				// Valid meaningful data
				$result = 'f/' . ( round( 10 * $x / $y ) / 10 );

				return $result;
				break;

			case 'E#84E3': 	// RasterPadding

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'Byte', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Word', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Long Word', 'wp-photo-album-plus' ); break;
					case 9: $result = __( 'Sector', 'wp-photo-album-plus' ); break;
					case 10: $result = __( 'Long Sector', 'wp-photo-album-plus' ); break;
					default: $result = __( 'reserved', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#84E7': 	// ImageColorIndicator

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'Unspecified Image Color', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Specified Image Color', 'wp-photo-album-plus' ); break;
					default: $result = __( 'reserved', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#84E8': 	// BackgroundColorIndicator

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'Unspecified Background Color', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Specified Background Color', 'wp-photo-album-plus' ); break;
					default: $result = __( 'reserved', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#84EE': 	// HCUsage

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'CT', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Line Art', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Trap', 'wp-photo-album-plus' ); break;
					default: $result = __( 'reserved', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#877F': 	// TIFF_FXExtensions

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				$result = '';
				if ( $data & 0x01 ) $data .= __( 'Resolution/Image Width', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x02 ) $data .= __( 'N Layer Profile M', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x04 ) $data .= __( 'Shared Data', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x08 ) $data .= __( 'B&W JBIG2', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x10 ) $data .= __( 'JBIG2 Profile M', 'wp-photo-album-plus' ) . ',  ';
				$result = trim( $result, ', ' );
				if ( ! $result ) $result = __( 'Undefined', 'wp-photo-album-plus' );
				return $result;
				break;

			case 'E#8780': 	// MultiProfiles

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				$result = '';
				if ( $data & 0x001 ) $data .= __( 'Profile S', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x002 ) $data .= __( 'Profile F', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x004 ) $data .= __( 'Profile J', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x008 ) $data .= __( 'Profile C', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x010 ) $data .= __( 'Profile L', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x020 ) $data .= __( 'Profile M', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x040 ) $data .= __( 'Profile T', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x080 ) $data .= __( 'Resolution/Image Width', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x100 ) $data .= __( 'N Layer Profile M', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x200 ) $data .= __( 'Shared Data', 'wp-photo-album-plus' ) . ',  ';
				if ( $data & 0x400 ) $data .= __( 'JBIG2 Profile M', 'wp-photo-album-plus' ) . ',  ';
				$result = trim( $result, ', ' );
				if ( ! $result ) $result = __( 'Undefined', 'wp-photo-album-plus' );
				return $result;
				break;

			case 'E#8822': 	// Exposure program

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case '0': $result = __('Not Defined', 'wp-photo-album-plus'); break;
					case '1': $result = __('Manual', 'wp-photo-album-plus'); break;
					case '2': $result = __('Program AE', 'wp-photo-album-plus'); break;
					case '3': $result = __('Aperture-priority AE', 'wp-photo-album-plus'); break;
					case '4': $result = __('Shutter speed priority AE', 'wp-photo-album-plus'); break;
					case '5': $result = __('Creative (Slow speed)', 'wp-photo-album-plus'); break;
					case '6': $result = __('Action (High speed)', 'wp-photo-album-plus'); break;
					case '7': $result = __('Portrait', 'wp-photo-album-plus'); break;
					case '8': $result = __('Landscape', 'wp-photo-album-plus'); break;
					case '9': $result = __('Bulb', 'wp-photo-album-plus'); break;
					default:
						$result = '<span title="' . esc_attr( __( 'Unknown', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
				}
				return $result;
				break;

			case 'E#8830': 	// SensitivityType

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case '0': $result = __('Unknown', 'wp-photo-album-plus'); break;
					case '1': $result = __('Standard Output Sensitivity', 'wp-photo-album-plus'); break;
					case '2': $result = __('Recommended Exposure Index', 'wp-photo-album-plus'); break;
					case '3': $result = __('ISO Speed', 'wp-photo-album-plus'); break;
					case '4': $result = __('Standard Output Sensitivity and Recommended Exposure Index', 'wp-photo-album-plus'); break;
					case '5': $result = __('Standard Output Sensitivity and ISO Speed', 'wp-photo-album-plus'); break;
					case '6': $result = __('Recommended Exposure Index and ISO Speed', 'wp-photo-album-plus'); break;
					case '7': $result = __('Standard Output Sensitivity, Recommended Exposure Index and ISO Speed', 'wp-photo-album-plus'); break;
					default:
						$result = '<span title="' . esc_attr( __( 'Unknown', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
				}
				return $result;
				break;

			case 'E#9101': 	// ComponentsConfiguration

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case '0': $result = '-'; break;
					case '1': $result = 'Y'; break;
					case '2': $result = 'Cb'; break;
					case '3': $result = 'Cr'; break;
					case '4': $result = 'R'; break;
					case '5': $result = 'G'; break;
					case '6': $result = 'B'; break;
					default:
						$result = '<span title="' . esc_attr( __( 'Unknown', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
				}
				return $result;
				break;

			case 'E#9102': 	// CompressedBitsPerPixel

				if ( ! wppa_is_valid_rational( $data ) ) return $wppa_exif_error_output;

				$result = wppa_simplify_ratio( $data );
				return $result;
				break;

			case 'E#9201': 	// Shutter speed value

				if ( ! wppa_is_valid_rational( $data, true ) ) {
					return $wppa_exif_error_output;
				}

				// Format is valid
				$temp = explode( '/', $data );
				$x = $temp[0];
				$y = $temp[1];

				$result = round( 10 * $x / $y ) / 10;
				return $result;
				break;

			case 'E#9202': 	// Aperture value

				// Invalid format?
				if ( ! wppa_is_valid_rational( $data ) ) {
					return $wppa_exif_error_output;
				}

				// Format is valid
				$temp = explode( '/', $data );
				$x = $temp[0];
				$y = $temp[1];

				$result = round( 10 * $x / $y ) / 10;

				return $result;
				break;

			case 'E#9204': 	// ExposureBiasValue

				// Invalid format?
				if ( ! wppa_is_valid_rational( $data, true ) ) {
					return $wppa_exif_error_output;
				}

				$t = explode( '/', $data );
				$x = $t[0];
				$y = $t[1];

				$result = sprintf( '%5.2f EV', $x/$y );
				return $result;
				break;

			case 'E#9205': 	// Max aperture value

				// Invalid format?
				if ( ! wppa_is_valid_rational( $data ) ) {
					return $wppa_exif_error_output;
				}

				// Format is valid
				$temp = explode( '/', $data );
				$x = $temp[0];
				$y = $temp[1];

				$result = round( 10 * $x / $y ) / 10;

				return $result;
				break;

			case 'E#9206': 	// Subject distance

				// Invalid format?
				if ( ! wppa_is_valid_rational( $data, true ) ) {
					return $wppa_exif_error_output;
				}

				// Format is valid
				$temp = explode( '/', $data );
				$x = $temp[0];
				$y = $temp[1];

				if ( $x == 0 || $y == 0 || $x/$y > 1000 ) {
					$result = '<span title="' . esc_attr( __( 'Impossible data', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
				}
				else {
					if ( $temp[1] != 0 ) {
						$result = round( 100*$temp[0]/$temp[1] ) / 100;
					}
					if ( $result == -1 ) {
						$result = 'inf';
					}
					else {
						$result .= ' m.';
					}
				}

				return $result;
				break;



			case 'E#9207':	// Metering mode
				switch ( $data ) {
					case '1': $result = __('Average', 'wp-photo-album-plus'); break;
					case '2': $result = __('Center-weighted average', 'wp-photo-album-plus'); break;
					case '3': $result = __('Spot', 'wp-photo-album-plus'); break;
					case '4': $result = __('Multi-spot', 'wp-photo-album-plus'); break;
					case '5': $result = __('Multi-segment', 'wp-photo-album-plus'); break;
					case '6': $result = __('Partial', 'wp-photo-album-plus'); break;
					case '255': $result = __('Other', 'wp-photo-album-plus'); break;
					default:
						$result = '<span title="' . esc_attr( __( 'reserved', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
				}
				return $result;
				break;

			case 'E#9208': 	// LghtSource
				switch ( $data ) {
					case '0': $result = __('unknown', 'wp-photo-album-plus'); break;
					case '1': $result = __('Daylight', 'wp-photo-album-plus'); break;
					case '2': $result = __('Fluorescent', 'wp-photo-album-plus'); break;
					case '3': $result = __('Tungsten (incandescent light)', 'wp-photo-album-plus'); break;
					case '4': $result = __('Flash', 'wp-photo-album-plus'); break;
					case '9': $result = __('Fine weather', 'wp-photo-album-plus'); break;
					case '10': $result = __('Cloudy weather', 'wp-photo-album-plus'); break;
					case '11': $result = __('Shade', 'wp-photo-album-plus'); break;
					case '12': $result = __('Daylight fluorescent (D 5700 – 7100K)', 'wp-photo-album-plus'); break;
					case '13': $result = __('Day white fluorescent (N 4600 – 5400K)', 'wp-photo-album-plus'); break;
					case '14': $result = __('Cool white fluorescent (W 3900 – 4500K)', 'wp-photo-album-plus'); break;
					case '15': $result = __('White fluorescent (WW 3200 – 3700K)', 'wp-photo-album-plus'); break;
					case '17': $result = __('Standard light A', 'wp-photo-album-plus'); break;
					case '18': $result = __('Standard light B', 'wp-photo-album-plus'); break;
					case '19': $result = __('Standard light C', 'wp-photo-album-plus'); break;
					case '20': $result = __('D55', 'wp-photo-album-plus'); break;
					case '21': $result = __('D65', 'wp-photo-album-plus'); break;
					case '22': $result = __('D75', 'wp-photo-album-plus'); break;
					case '23': $result = __('D50', 'wp-photo-album-plus'); break;
					case '24': $result = __('ISO studio tungsten', 'wp-photo-album-plus'); break;
					case '255': $result = __('other light source', 'wp-photo-album-plus'); break;
					default: $result = __('reserved', 'wp-photo-album-plus'); break;
				}
				return $result;
				break;

			case 'E#9209':	// Flash
				switch ( $data ) {
					case '0': $result = __('No Flash', 'wp-photo-album-plus'); break;
					case '1': $result = __('Fired', 'wp-photo-album-plus'); break;
					case '5': $result = __('Fired, Return not detected', 'wp-photo-album-plus'); break;
					case '7': $result = __('Fired, Return detected', 'wp-photo-album-plus'); break;
					case '8': $result = __('On, Did not fire', 'wp-photo-album-plus'); break;
					case '9': $result = __('On, Fired', 'wp-photo-album-plus'); break;
					case '13': $result = __('On, Return not detected', 'wp-photo-album-plus'); break;
					case '15': $result = __('On, Return detected', 'wp-photo-album-plus'); break;
					case '16': $result = __('Off, Did not fire', 'wp-photo-album-plus'); break;
					case '20': $result = __('Off, Did not fire, Return not detected', 'wp-photo-album-plus'); break;
					case '24': $result = __('Auto, Did not fire', 'wp-photo-album-plus'); break;
					case '25': $result = __('Auto, Fired', 'wp-photo-album-plus'); break;
					case '29': $result = __('Auto, Fired, Return not detected', 'wp-photo-album-plus'); break;
					case '31': $result = __('Auto, Fired, Return detected', 'wp-photo-album-plus'); break;
					case '32': $result = __('No flash function', 'wp-photo-album-plus'); break;
					case '48': $result = __('Off, No flash function', 'wp-photo-album-plus'); break;
					case '65': $result = __('Fired, Red-eye reduction', 'wp-photo-album-plus'); break;
					case '69': $result = __('Fired, Red-eye reduction, Return not detected', 'wp-photo-album-plus'); break;
					case '71': $result = __('Fired, Red-eye reduction, Return detected', 'wp-photo-album-plus'); break;
					case '73': $result = __('On, Red-eye reduction', 'wp-photo-album-plus'); break;
					case '77': $result = __('Red-eye reduction, Return not detected', 'wp-photo-album-plus'); break;
					case '79': $result = __('On, Red-eye reduction, Return detected', 'wp-photo-album-plus'); break;
					case '80': $result = __('Off, Red-eye reduction', 'wp-photo-album-plus'); break;
					case '88': $result = __('Auto, Did not fire, Red-eye reduction', 'wp-photo-album-plus'); break;
					case '89': $result = __('Auto, Fired, Red-eye reduction', 'wp-photo-album-plus'); break;
					case '93': $result = __('Auto, Fired, Red-eye reduction, Return not detected', 'wp-photo-album-plus'); break;
					case '95': $result = __('Auto, Fired, Red-eye reduction, Return detected', 'wp-photo-album-plus'); break;
					default:
						$result = '<span title="' . esc_attr( __( 'Unknown', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
				}

				return $result;
				break;

			case 'E#9210': 	// FocalPlaneResolutionUnit

				switch( $data ) {
					case 1: $result = __( 'None', 'wp-photo-album-plus' ); break;
					case 2: $result = 'inches'; break;
					case 3: $result = 'cm'; break;
					case 4: $result = 'mm'; break;
					case 5: $result = '&mu;m'; break;
					default: $result = '<span title="' . esc_attr( __( 'Unknown', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
				}

				return $result;
				break;

			case 'E#920A': // 	Focal length

				if ( ! wppa_is_valid_rational( $data ) ) {
					return $wppa_exif_error_output;
				}

				// Format is valid
				$temp = explode( '/', $data );
				$x = $temp[0];
				$y = $temp[1];

				$z = round( $x / $y );
				if ( $z < 10 ) {
					$result = round( $x * 10 / $y ) / 10 . ' mm.';
				}
				else {
					$result = round( $x / $y ) . ' mm.';
				}
				return $result;
				break;

			case 'E#9212': 	// SecurityClassification

				switch( $data ) {
					case 'C': $result = __( 'Confidential', 'wp-photo-album-plus' ); break;
					case 'R': $result = __( 'Restricted', 'wp-photo-album-plus' ); break;
					case 'S': $result = __( 'Secret', 'wp-photo-album-plus' ); break;
					case 'T': $result = __( 'Top Secret', 'wp-photo-album-plus' ); break;
					case 'U': $result = __( 'Unclassified', 'wp-photo-album-plus' ); break;
					default: $result = '<span title="' . esc_attr( __( 'Unknown', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
				}

				return $result;
				break;

			case 'E#9217': 	// SensingMethod

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 1: $result = __( 'Monochrome area', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'One-chip color area', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Two-chip color area', 'wp-photo-album-plus' ); break;
					case 4: $result = __( 'Three-chip color area', 'wp-photo-album-plus' ); break;
					case 5: $result = __( 'Color sequential area', 'wp-photo-album-plus' ); break;
					case 6: $result = __( 'Monochrome linear', 'wp-photo-album-plus' ); break;
					case 7: $result = __( 'Trilinear', 'wp-photo-album-plus' ); break;
					case 8: $result = __( 'Color sequential linear', 'wp-photo-album-plus' ); break;
					default: $result = '<span title="' . esc_attr( __( 'Unknown', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
				}

				return $result;
				break;

			case 'E#A001': 	// ColorSpace

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 1: $result = __( 'sRGB', 'wp-photo-album-plus' ); break;
					case 0x2: $result = __( 'Adobe RGB', 'wp-photo-album-plus' ); break;
					case 0xfffd: $result = __( 'Wide Gamut RGB', 'wp-photo-album-plus' ); break;
					case 0xfffe: $result = __( 'ICC Profile', 'wp-photo-album-plus' ); break;
					case 0xFFFF: $result = __( 'Uncalibrated', 'wp-photo-album-plus' ); break;
					default: $result = __( 'reserved', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#A002': 	// PixelXDimension
			case 'E#A003': 	// PixelYDimension

				switch( $brand ) {
					case 'SAMSUNG': 	// LensType

						switch ( $data ) {
							case 0: $result = 'Built-in or Manual Lens'; break;
							case 1: $result = 'Samsung NX 30mm F2 Pancake'; break;
							case 2: $result = 'Samsung NX 18-55mm F3.5-5.6 OIS'; break;
							case 3: $result = 'Samsung NX 50-200mm F4-5.6 ED OIS'; break;
							case 4: $result = 'Samsung NX 20-50mm F3.5-5.6 ED'; break;
							case 5: $result = 'Samsung NX 20mm F2.8 Pancake'; break;
							case 6: $result = 'Samsung NX 18-200mm F3.5-6.3 ED OIS'; break;
							case 7: $result = 'Samsung NX 60mm F2.8 Macro ED OIS SSA'; break;
							case 8: $result = 'Samsung NX 16mm F2.4 Pancake'; break;
							case 9: $result = 'Samsung NX 85mm F1.4 ED SSA'; break;
							case 10: $result = 'Samsung NX 45mm F1.8'; break;
							case 11: $result = 'Samsung NX 45mm F1.8 2D/3D'; break;
							case 12: $result = 'Samsung NX 12-24mm F4-5.6 ED'; break;
							case 13: $result = 'Samsung NX 16-50mm F2-2.8 S ED OIS'; break;
							case 14: $result = 'Samsung NX 10mm F3.5 Fisheye'; break;
							case 15: $result = 'Samsung NX 16-50mm F3.5-5.6 Power Zoom ED OIS'; break;
							case 20: $result = 'Samsung NX 50-150mm F2.8 S ED OIS'; break;
							case 21: $result = 'Samsung NX 300mm F2.8 ED OIS'; break;
							default: $result = $data;
						}

						return $result;
						break;

					default:
						if ( ! wppa_is_valid_integer( $data ) ) {
							return $wppa_exif_error_output;
						}

						$result = $data . ' px.';
						return $result;
				}
				break;

			case 'E#A011': 	// ColorSpace (Samsung)

				switch( $brand ) {
					case 'SAMSUNG': 	// ColorSpace

						switch ( $data ) {
							case 0: $result = 'sRGB'; break;
							case 1: $result = 'Adobe RGB'; break;
							default: $result = $data;
						}

						return $result;
						break;

					default:

						$result = $data;
						return $result;
				}
				break;

			case 'E#A012': 	// SmartRange (Samsung)

				switch( $brand ) {
					case 'SAMSUNG': 	// ColorSpace

						switch ( $data ) {
							case 0: $result = __( 'Off', 'wp-photo-album-plus' ); break;
							case 1: $result = __( 'On', 'wp-photo-album-plus' ); break;
							default: $result = $data;
						}

						return $result;
						break;

					default:

						$result = $data;
						return $result;
				}
				break;

			case 'E#A20E':	// FocalPlaneXResolution
			case 'E#A20F':	// FocalPlaneYResolution

				if ( ! wppa_is_valid_rational( $data ) ) {
					return $wppa_exif_error_output;
				}

				// Format is valid
				$temp = explode( '/', $data );
				$x = $temp[0];
				$y = $temp[1];

				$result = round( $x / $y );
				return $result;
				break;

			case 'E#A210': 	// FocalPlaneResolutionUnit

				switch( $data ) {
					case 1: $result = __( 'None', 'wp-photo-album-plus' ); break;
					case 2: $result = 'inches'; break;
					case 3: $result = 'cm'; break;
					case 4: $result = 'mm'; break;
					case 5: $result = '&mu;m'; break;
					default: $result = '<span title="' . esc_attr( __( 'Unknown', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
				}

				return $result;
				break;

			case 'E#A217': 	// SensingMethod

				if ( ! wppa_is_valid_integer( $data ) ) {
					return $wppa_exif_error_output;
				}

				switch ( $data ) {
					case 1:
						$result = __( 'Not defined', 'wp-photo-album-plus' );
						break;
					case 2:
						$result = __( 'One-chip color area sensor', 'wp-photo-album-plus' );
						break;
					case 3:
						$result = __( 'Two-chip color area sensor', 'wp-photo-album-plus' );
						break;
					case 4:
						$result = __( 'Three-chip color area sensor', 'wp-photo-album-plus' );
						break;
					case 5:
						$result = __( 'Color sequential area sensor', 'wp-photo-album-plus' );
						break;
					case 7:
						$result = __( 'Trilinear sensor', 'wp-photo-album-plus' );
						break;
					case 8:
						$result = __( 'Color sequential linear sensor', 'wp-photo-album-plus' );
						break;
					default:
						$result = __( 'reserved', 'wp-photo-album-plus' );
				}

				return $result;
				break;

			case 'E#A300': 	// FileSource

				switch( $data ) {
					case 1: $result = __( 'Film Scanner', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Reflection Print Scanner', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Digital Camera', 'wp-photo-album-plus' ); break;
					case "\x03\x00\x00\x00": $result = __( 'Sigma Digital Camera', 'wp-photo-album-plus' ); break;
					default: $result = __( 'reserved', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#A401': 	// CustomRendered

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'Normal', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Custom', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'HDR', 'wp-photo-album-plus' ); break;
					case 6: $result = __( 'Panorama', 'wp-photo-album-plus' ); break;
					case 8: $result = __( 'Portrait', 'wp-photo-album-plus' ); break;
					default: $result = __( 'reserved', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#A402': 	// ExposureMode

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0:
						$result = __( 'Auto exposure', 'wp-photo-album-plus' );
						break;
					case 1:
						$result = __( 'Manual exposure', 'wp-photo-album-plus' );
						break;
					case 2:
						$result = __( 'Auto bracket', 'wp-photo-album-plus' );
						break;
					default:
						$result = __( 'reserved', 'wp-photo-album-plus' );
				}

				return $result;
				break;

			case 'E#A403': 	// WhiteBalance

				if ( ! wppa_is_valid_integer( $data ) ) {
					return $wppa_exif_error_output;
				}

				switch ( $data ) {
					case 0: $result = __( 'Auto white balance', 'wp-photo-album-plus' );
						break;
					case 1: $result = __( 'Manual white balance', 'wp-photo-album-plus' );
						break;
					default:
						$result = __( 'reserved', 'wp-photo-album-plus' );
				}

				return $result;
				break;

			case 'E#A404': 	// DigitalZoomRatio

				// Invalid format?
				if ( ! wppa_is_valid_rational( $data ) ) {
					return $wppa_exif_error_output;
				}

				$temp = explode( '/', $data );
				$x = $temp[0];
				$y = $temp[1];

				if ( $x == 0 ) {
					$result = __( 'Not used', 'wp-photo-album-plus' );
					return $result;
				}

				$result = wppa_simplify_ratio( $data );
				return $result;
				break;

			case 'E#A405': 	// FocalLengthIn35mmFilm

				if ( ! wppa_is_valid_integer( $data ) ) {
					return $wppa_exif_error_output;
				}

				$result = $data . ' mm.';
				return $result;
				break;

			case 'E#A406': 	// SceneCaptureType

				if ( ! wppa_is_valid_integer( $data ) ) {
					return $wppa_exif_error_output;
				}

				switch( $data ) {
					case 0: $result = __( 'Standard', 'wp-photo-album-plus' );
						break;
					case 1: $result = __( 'Landscape', 'wp-photo-album-plus' );
						break;
					case 2: $result = __( 'Portrait', 'wp-photo-album-plus' );
						break;
					case 3: $result = __( 'Night scene', 'wp-photo-album-plus' );
						break;
					default:
						$result = __( 'reserved', 'wp-photo-album-plus' );
						break;
				}

				return $result;
				break;

			case 'E#A407': 	// GainControl

				if ( ! wppa_is_valid_integer( $data ) ) {
					return $wppa_exif_error_output;
				}

				switch( $data ) {
					case 0: $result = __( 'None', 'wp-photo-album-plus' );
						break;
					case 1: $result = __( 'Low gain up', 'wp-photo-album-plus' );
						break;
					case 2: $result = __( 'High gain up', 'wp-photo-album-plus' );
						break;
					case 3: $result = __( 'Low gain down', 'wp-photo-album-plus' );
						break;
					case 4: $result = __( 'High gain down', 'wp-photo-album-plus' );
						break;
					default:
						$result = __( 'reserved', 'wp-photo-album-plus' );
				}

				return $result;
				break;

			case 'E#A408': 	// Contrast

				if ( ! wppa_is_valid_integer( $data ) ) {
					return $wppa_exif_error_output;
				}

				switch ( $data ) {
					case 0:
						$result = __( 'Normal', 'wp-photo-album-plus' );
						break;
					case 1:
						$result = __( 'Soft', 'wp-photo-album-plus' );
						break;
					case 2:
						$result = __( 'Hard', 'wp-photo-album-plus' );
						break;
					default:
						$result = __( 'reserved', 'wp-photo-album-plus' );
				}

				return $result;
				break;

			case 'E#A409': 	// Saturation

				if ( ! wppa_is_valid_integer( $data ) ) {
					return $wppa_exif_error_output;
				}

				switch ( $data ) {
					case 0:
						$result = __( 'Normal', 'wp-photo-album-plus' );
						break;
					case 1:
						$result = __( 'Low saturation', 'wp-photo-album-plus' );
						break;
					case 2:
						$result = __( 'High saturation', 'wp-photo-album-plus' );
						break;
					default:
						$result = __( 'reserved', 'wp-photo-album-plus' );
				}

				return $result;
				break;

			case 'E#A40A': 	// Sharpness

				if ( ! wppa_is_valid_integer( $data ) ) {
					return $wppa_exif_error_output;
				}

				switch ( $data ) {
					case 0:
						$result = __( 'Normal', 'wp-photo-album-plus' );
						break;
					case 1:
						$result = __( 'Soft', 'wp-photo-album-plus' );
						break;
					case 2:
						$result = __( 'Hard', 'wp-photo-album-plus' );
						break;
					default:
						$result = __( 'reserved', 'wp-photo-album-plus' );
				}

				return $result;
				break;

			case 'E#A40C': 	// SubjectDistanceRange

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0: $result = __( 'unknown', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Macro', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Close view', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Distant view', 'wp-photo-album-plus' ); break;
					default: $result = __( 'reserved', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#BC01': 	// PixelFormat

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch ( $data ) {
					case 0x5: $result = __( 'Black & White', 'wp-photo-album-plus' ); break;
					case 0x8: $result = __( '8-bit Gray', 'wp-photo-album-plus' ); break;
					case 0x9: $result = __( '16-bit BGR555', 'wp-photo-album-plus' ); break;
					case 0xa: $result = __( '16-bit BGR565', 'wp-photo-album-plus' ); break;
					case 0xb: $result = __( '16-bit Gray', 'wp-photo-album-plus' ); break;
					case 0xc: $result = __( '24-bit BGR', 'wp-photo-album-plus' ); break;
					case 0xd: $result = __( '24-bit RGB', 'wp-photo-album-plus' ); break;
					case 0xe: $result = __( '32-bit BGR', 'wp-photo-album-plus' ); break;
					case 0xf: $result = __( '32-bit BGRA', 'wp-photo-album-plus' ); break;
					case 0x10: $result = __( '32-bit PBGRA', 'wp-photo-album-plus' ); break;
					case 0x11: $result = __( '32-bit Gray Float', 'wp-photo-album-plus' ); break;
					case 0x12: $result = __( '48-bit RGB Fixed Point', 'wp-photo-album-plus' ); break;
					case 0x13: $result = __( '32-bit BGR101010', 'wp-photo-album-plus' ); break;
					case 0x15: $result = __( '48-bit RGB', 'wp-photo-album-plus' ); break;
					case 0x16: $result = __( '64-bit RGBA', 'wp-photo-album-plus' ); break;
					case 0x17: $result = __( '64-bit PRGBA', 'wp-photo-album-plus' ); break;
					case 0x18: $result = __( '96-bit RGB Fixed Point', 'wp-photo-album-plus' ); break;
					case 0x19: $result = __( '128-bit RGBA Float', 'wp-photo-album-plus' ); break;
					case 0x1a: $result = __( '128-bit PRGBA Float', 'wp-photo-album-plus' ); break;
					case 0x1b: $result = __( '128-bit RGB Float', 'wp-photo-album-plus' ); break;
					case 0x1c: $result = __( '32-bit CMYK', 'wp-photo-album-plus' ); break;
					case 0x1d: $result = __( '64-bit RGBA Fixed Point', 'wp-photo-album-plus' ); break;
					case 0x1e: $result = __( '128-bit RGBA Fixed Point', 'wp-photo-album-plus' ); break;
					case 0x1f: $result = __( '64-bit CMYK', 'wp-photo-album-plus' ); break;
					case 0x20: $result = __( '24-bit 3 Channels', 'wp-photo-album-plus' ); break;
					case 0x21: $result = __( '32-bit 4 Channels', 'wp-photo-album-plus' ); break;
					case 0x22: $result = __( '40-bit 5 Channels', 'wp-photo-album-plus' ); break;
					case 0x23: $result = __( '48-bit 6 Channels', 'wp-photo-album-plus' ); break;
					case 0x24: $result = __( '56-bit 7 Channels', 'wp-photo-album-plus' ); break;
					case 0x25: $result = __( '64-bit 8 Channels', 'wp-photo-album-plus' ); break;
					case 0x26: $result = __( '48-bit 3 Channels', 'wp-photo-album-plus' ); break;
					case 0x27: $result = __( '64-bit 4 Channels', 'wp-photo-album-plus' ); break;
					case 0x28: $result = __( '80-bit 5 Channels', 'wp-photo-album-plus' ); break;
					case 0x29: $result = __( '96-bit 6 Channels', 'wp-photo-album-plus' ); break;
					case 0x2a: $result = __( '112-bit 7 Channels', 'wp-photo-album-plus' ); break;
					case 0x2b: $result = __( '128-bit 8 Channels', 'wp-photo-album-plus' ); break;
					case 0x2c: $result = __( '40-bit CMYK Alpha', 'wp-photo-album-plus' ); break;
					case 0x2d: $result = __( '80-bit CMYK Alpha', 'wp-photo-album-plus' ); break;
					case 0x2e: $result = __( '32-bit 3 Channels Alpha', 'wp-photo-album-plus' ); break;
					case 0x2f: $result = __( '40-bit 4 Channels Alpha', 'wp-photo-album-plus' ); break;
					case 0x30: $result = __( '48-bit 5 Channels Alpha', 'wp-photo-album-plus' ); break;
					case 0x31: $result = __( '56-bit 6 Channels Alpha', 'wp-photo-album-plus' ); break;
					case 0x32: $result = __( '64-bit 7 Channels Alpha', 'wp-photo-album-plus' ); break;
					case 0x33: $result = __( '72-bit 8 Channels Alpha', 'wp-photo-album-plus' ); break;
					case 0x34: $result = __( '64-bit 3 Channels Alpha', 'wp-photo-album-plus' ); break;
					case 0x35: $result = __( '80-bit 4 Channels Alpha', 'wp-photo-album-plus' ); break;
					case 0x36: $result = __( '96-bit 5 Channels Alpha', 'wp-photo-album-plus' ); break;
					case 0x37: $result = __( '112-bit 6 Channels Alpha', 'wp-photo-album-plus' ); break;
					case 0x38: $result = __( '128-bit 7 Channels Alpha', 'wp-photo-album-plus' ); break;
					case 0x39: $result = __( '144-bit 8 Channels Alpha', 'wp-photo-album-plus' ); break;
					case 0x3a: $result = __( '64-bit RGBA Half', 'wp-photo-album-plus' ); break;
					case 0x3b: $result = __( '48-bit RGB Half', 'wp-photo-album-plus' ); break;
					case 0x3d: $result = __( '32-bit RGBE', 'wp-photo-album-plus' ); break;
					case 0x3e: $result = __( '16-bit Gray Half', 'wp-photo-album-plus' ); break;
					case 0x3f: $result = __( '32-bit Gray Fixed Point', 'wp-photo-album-plus' ); break;
					default: $result = __( 'reserved', 'wp-photo-album-plus' );
				}
				return $result;
				break;

			case 'E#BC02': 	// Transformation

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 0: $result = __( 'Horizontal (normal)', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Mirror vertical', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Mirror horizontal', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Rotate 180', 'wp-photo-album-plus' ); break;
					case 4: $result = __( 'Rotate 90 CW', 'wp-photo-album-plus' ); break;
					case 5: $result = __( 'Mirror horizontal and rotate 90 CW', 'wp-photo-album-plus' ); break;
					case 6: $result = __( 'Mirror horizontal and rotate 270 CW', 'wp-photo-album-plus' ); break;
					case 7: $result = __( 'Rotate 270 CW', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#BC03': 	// Uncompressed

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 0: $result = __( 'No', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Yes', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#BC04': 	// ImageType

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 1: $result = __( 'Preview', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Page', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Preview', 'wp-photo-album-plus' ) . ' ' . __( 'Page', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

/*
0xbc80 	ImageWidth
0xbc81 	ImageHeight
0xbc82 	WidthResolution
0xbc83 	HeightResolution
0xbcc0 	ImageOffset
0xbcc1 	ImageByteCount
0xbcc2 	AlphaOffset
0xbcc3 	AlphaByteCount
*/


			case 'E#BCC4': 	// ImageDataDiscard

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 0: $result = __( 'Full Resolution', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Flexbits Discarded', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'HighPass Frequency Data Discarded', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Highpass and LowPass Frequency Data Discarded', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#BCC5': 	// AlphaDataDiscard

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 0: $result = __( 'Full Resolution', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Flexbits Discarded', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'HighPass Frequency Data Discarded', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Highpass and LowPass Frequency Data Discarded', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;
/*
0xc427 	OceScanjobDesc
0xc428 	OceApplicationSelector
0xc429 	OceIDNumber
0xc42a 	OceImageLogic
0xc44f 	Annotations
0xc4a5 	PrintIM
0xc573 	OriginalFileName
*/
			case 'E#C580': 	// USPTOOriginalContentType

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 0: $result = __( 'Text or Drawing', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Grayscale', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Color', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

/*
0xc5e0 	CR2CFAPattern
1 => '0 1 1 2' = [Red,Green][Green,Blue]
4 => '1 0 2 1' = [Green,Red][Blue,Green]
3 => '1 2 0 1' = [Green,Blue][Red,Green]
2 => '2 1 1 0' = [Blue,Green][Green,Red]


0xc612 	DNGVersion 	int8u[4]! 	IFD0 	(tags 0xc612-0xc7b5 are defined by the DNG specification unless otherwise noted. See https://helpx.adobe.com/photoshop/digital-negative.html for the specification)
0xc613 	DNGBackwardVersion 	int8u[4]! 	IFD0
0xc614 	UniqueCameraModel 	string 	IFD0
0xc615 	LocalizedCameraModel 	string 	IFD0
0xc616 	CFAPlaneColor 	no 	SubIFD
*/

			case 'E#C617': 	// CFALayout

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 1: $result = __( 'Rectangular', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Even columns offset down 1/2 row', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Even columns offset up 1/2 row', 'wp-photo-album-plus' ); break;
					case 4: $result = __( 'Even rows offset right 1/2 column', 'wp-photo-album-plus' ); break;
					case 5: $result = __( 'Even rows offset left 1/2 column', 'wp-photo-album-plus' ); break;
					case 6: $result = __( 'Even rows offset up by 1/2 row, even columns offset left by 1/2 column', 'wp-photo-album-plus' ); break;
					case 7: $result = __( 'Even rows offset up by 1/2 row, even columns offset right by 1/2 column', 'wp-photo-album-plus' ); break;
					case 8: $result = __( 'Even rows offset down by 1/2 row, even columns offset left by 1/2 column', 'wp-photo-album-plus' ); break;
					case 9: $result = __( 'Even rows offset down by 1/2 row, even columns offset right by 1/2 column', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#C6FD': 	// ProfileEmbedPolicy

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 0: $result = __( 'Allow Copying', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Embed if Used', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'Never Embed', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'No Restrictions', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#C71A': 	// PreviewColorSpace

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 0: $result = __( 'Unknown', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'Gray Gamma 2.2', 'wp-photo-album-plus' ); break;
					case 2: $result = __( 'sRGB', 'wp-photo-album-plus' ); break;
					case 3: $result = __( 'Adobe RGB', 'wp-photo-album-plus' ); break;
					case 4: $result = __( 'ProPhoto RGB', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#C7A3': 	// ProfileHueSatMapEncoding
			case 'E#C7A4': 	// ProfileLookTableEncoding

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 0: $result = __( 'Linear', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'sRGB', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			case 'E#C7A6': 	// DefaultBlackRender

				if ( ! wppa_is_valid_integer( $data ) ) return $wppa_exif_error_output;

				switch( $data ) {
					case 0: $result = __( 'Auto', 'wp-photo-album-plus' ); break;
					case 1: $result = __( 'None', 'wp-photo-album-plus' ); break;
					default: $result = __( 'Undefined', 'wp-photo-album-plus' );
				}
				return $result;

			// Unformatted
			default:
				$result = $data;
				return $result;
		}
	}

	// Empty data
	else {
		$result = '<span title="' . esc_attr( __( 'No data', 'wp-photo-album-plus' ) ) . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
	}

	return $result;
}

function wppa_is_valid_rational( $data, $signed = false ) {
global $wppa_exif_error_output;

	// Must contain a '/'
	if ( strpos( $data, '/' ) == false ) {
		$wppa_exif_error_output = '<span title="' . esc_attr( __( 'Missing /', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
		return false;
	}

	// make array
	$t = explode( '/', $data );

	// Divide by zero?
	if ( $t[1] == 0 ) {
		$wppa_exif_error_output = '<span title="' . esc_attr( __( 'Divide by zero', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
		return false;
	}

	// Signed while not permitted?
	if ( ! $signed && ( $t[0] < 0 || $t[1] < 0 ) ) {
		$wppa_exif_error_output = '<span title="' . esc_attr( __( 'Must be positive', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
		return false;
	}

	// May be zero
	if ( $t[0] / $t[1] == 0 ) {
		return true;
	}

	// Unlikely value?
	if ( $t[0] / $t[1] > 100000 || abs( $t[0] / $t[1] ) < 0.00001 ) {
		$wppa_exif_error_output = '<span title="' . esc_attr( __( 'Unlikely value', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
		return false;
	}

	// Ok.
	return true;
}

function wppa_simplify_ratio( $data ) {

	// make array
	$t = explode( '/', $data );
	$x = $t[0];
	$y = $t[1];

	// Is already simplified to the max?
	if ( $x == 1 ) {
		$result = $data;
		return $result;
	}

	// Result is zero?
	if ( $x == 0 ) {
		$result = '0';
		return $result;
	}

	// See if it can be simplified to '1/nn'
	if ( round( $y / $x ) == $y / $x ) {
		$result = '1/' . ( $y / $x );
		return $result;
	}

	/* to be continued */
	$prime = array(2,3,5,7,11,13,17);
	foreach( $prime as $p ) {
		while ( wppa_is_divisible( $x, $p ) && wppa_is_divisible( $y, $p ) ) {
			$x = $x / $p;
			$y = $y / $p;
		}
	}
	$result = $x . '/' . $y;

	$result = $data;
	return $result;
}

function wppa_is_valid_integer( $data ) {
global $wppa_exif_error_output;

	// Must be integer
	if ( ! wppa_is_int( $data ) ) {
		$wppa_exif_error_output = '<span title="' . esc_attr( __( 'Invalid format', 'wp-photo-album-plus' ) ) . ':' . $data . '" style="cursor:pointer;" >' . __( 'n.a.', 'wp-photo-album-plus' ) . '</span>';
		return false;
	}

	// Ok.
	return true;
}

function wppa_iptc_clean_garbage() {
global $wpdb;

	// Remove empty tags
//	$empty = $wpdb->query( "DELETE FROM `" . WPPA_IPTC . "` WHERE `description` = '' OR `description` = ' ' OR `description` = '  '" );
//	if ( $empty ) {
//		wppa_log( 'dbg', $empty . ' empty iptc entries removed.' );
//	}

	// Remove labels that are no longer used
	$labels = $wpdb->get_results( "SELECT DISTINCT `tag` FROM `" . WPPA_IPTC . "` WHERE `photo` = '0'", ARRAY_A );
	if ( ! empty( $labels ) ) {
		foreach( $labels as $label ) {
			$used = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `" . WPPA_IPTC . "` WHERE `tag` = %s AND `photo` <> '0'", $label['tag'] ) );
			if ( $used == 0 ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPPA_IPTC . "` WHERE `tag` = %s AND `photo` = '0'", $label['tag'] ) );
				wppa_log( 'dbg', 'Iptc tag label ' . $label['tag'] . ' removed.' );
			}
		}
	}
}

function wppa_exif_clean_garbage() {
global $wpdb;

	// Remove empty tags
//	$empty = $wpdb->query( "DELETE FROM `" . WPPA_EXIF . "` WHERE `description` = '' OR `description` = ' ' OR `description` = '  '" );
//	wppa_log( 'dbg', $empty . ' empty exif entries removed.' );

	// Remove labels that are no longer used
	$labels = $wpdb->get_results( "SELECT DISTINCT `tag` FROM `" . WPPA_EXIF . "` WHERE `photo` = '0'", ARRAY_A );
	if ( ! empty( $labels ) ) {
		foreach( $labels as $label ) {
			$used = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `" . WPPA_EXIF . "` WHERE `tag` = %s AND `photo` <> '0'", $label['tag'] ) );
			if ( $used == 0 ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPPA_EXIF . "` WHERE `tag` = %s AND `photo` = '0'", $label['tag'] ) );
				wppa_log( 'dbg', 'Exif tag label ' . $label['tag'] . ' removed.' );
			}
		}
	}
}

// (Re-)calculate and store formatted exif entries for photo $photo
function wppa_fix_exif_format( $photo ) {
global $wpdb;

	if ( ! wppa_is_int( $photo ) ) {
		wppa_log( 'Err', 'wppa_fix_exif_format() called with arg: ' . $photo );
		return false;
	}

	$exifs = $wpdb->get_results( "SELECT * FROM `" . WPPA_EXIF . "` WHERE `photo` = $photo", ARRAY_A );

	if ( ! empty( $exifs ) ) {

		$brand = wppa_get_camera_brand( $photo );

		foreach( $exifs as $exif ) {

			$f_description 	= strip_tags( wppa_format_exif( $exif['tag'], $exif['description'], $brand ) );
			$tagbrand 		= ( trim( wppa_exif_tagname( hexdec( substr( $exif['tag'], 2, 4 ) ), $brand, 'brandonly' ), ': ' ) ? $brand : '' );

			// If f_description or thabrand changed: update
			if ( $f_description != $exif['f_description'] || $tagbrand != $exif['brand'] ) {
				$id = $exif['id'];
				$wpdb->query( $wpdb->prepare( "UPDATE `" . WPPA_EXIF . "` SET `f_description` = %s, `brand` = %s WHERE `id` = %s", $f_description, $tagbrand, $id ) );
				$photodata = wppa_cache_photo( $photo );

				// If the format changed and the exif tag is used in the description, the photo must be re-indexed
				if ( strpos( $photodata['description'], $exif['tag'] ) !== false ) {
					$wpdb->query( "UPDATE `" . WPPA_PHOTOS . "` SET `indexdtm` = '' WHERE `id` = $photo" );
					wppa_schedule_maintenance_proc( 'wppa_remake_index_photos' );
				}
			}
		}
	}
}

// Process the iptc data
function wppa_import_iptc( $id, $info, $nodelete = false ) {
global $wpdb;
static $labels;

	$doit = false;
	// Do we need this?
	if ( wppa_switch( 'save_iptc' ) ) $doit = true;
	if ( substr( wppa_opt( 'newphoto_name_method' ), 0, 2 ) == '2#' ) $doit = true;
	if ( ! $doit ) return;

	wppa_dbg_msg( 'wppa_import_iptc called for id='.$id );
	wppa_dbg_msg( 'array is'.( is_array( $info ) ? ' ' : ' NOT ' ).'available' );
	wppa_dbg_msg( 'APP13 is '.( isset( $info['APP13'] ) ? 'set' : 'NOT set' ) );

	// Is iptc data present?
	if ( !isset( $info['APP13'] ) ) return false;	// No iptc data avail
//var_dump( $info );
	// Parse
	$iptc = iptcparse( $info['APP13'] );
	if ( ! is_array( $iptc ) ) return false;		// No data avail

	// There is iptc data for this image.
	// First delete any existing ipts data for this image
	if ( ! $nodelete ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM `".WPPA_IPTC."` WHERE `photo` = %s", $id ) );
	}

	// Find defined labels
	if ( ! is_array( $labels ) ) {
		$result = $wpdb->get_results( "SELECT `tag` FROM `".WPPA_IPTC."` WHERE `photo` = '0' ORDER BY `tag`", ARRAY_N );

		if ( ! is_array( $result ) ) $result = array();
		$labels = array();
		foreach ( $result as $res ) {
			$labels[] = $res['0'];
		}
	}

	foreach ( array_keys( $iptc ) as $s ) {

		// Check for valid item
		if ( $s == '2#000' ) continue; 	// Skip this one
		if ( $s == '1#000' ) continue; 	// Skip this one

		if ( is_array( $iptc[$s] ) ) {
			$c = count ( $iptc[$s] );
			for ( $i=0; $i <$c; $i++ ) {

				// Process item
				wppa_dbg_msg( 'IPTC '.$s.' = '.$iptc[$s][$i] );

				// Check labels first
				if ( ! in_array( $s, $labels ) ) {

					// Add to labels
					$labels[] = $s;

					// Add to db
					$photo 	= '0';
					$tag 	= $s;
					$desc 	= $s.':';
						if ( $s == '2#005' ) $desc = 'Graphic name:';
						if ( $s == '2#010' ) $desc = 'Urgency:';
						if ( $s == '2#015' ) $desc = 'Category:';
						if ( $s == '2#020' ) $desc = 'Supp categories:';
						if ( $s == '2#040' ) $desc = 'Spec instr:';
						if ( $s == '2#055' ) $desc = 'Creation date:';
						if ( $s == '2#080' ) $desc = 'Photographer:';
						if ( $s == '2#085' ) $desc = 'Credit byline title:';
						if ( $s == '2#090' ) $desc = 'City:';
						if ( $s == '2#095' ) $desc = 'State:';
						if ( $s == '2#101' ) $desc = 'Country:';
						if ( $s == '2#103' ) $desc = 'Otr:';
						if ( $s == '2#105' ) $desc = 'Headline:';
						if ( $s == '2#110' ) $desc = 'Source:';
						if ( $s == '2#115' ) $desc = 'Photo source:';
						if ( $s == '2#120' ) $desc = 'Caption:';
					$status = 'display';
						if ( $s == '1#090' ) $status = 'hide';
						if ( $desc == $s.':' ) $status= 'hide';
					//	if ( $s == '2#000' ) $status = 'hide';
					$bret = wppa_create_iptc_entry( array( 'photo' => $photo, 'tag' => $tag, 'description' => $desc, 'status' => $status ) );
					if ( ! $bret ) wppa_log( 'Warning', 'Could not add IPTC tag '.$tag.' for photo '.$photo );
				}

				// Now add poto specific data item
				$photo 	= $id;
				$tag 	= $s;
				$desc 	= $iptc[$s][$i];
				if ( ! seems_utf8( $desc ) ) {
					$desc 	= utf8_encode( $desc );
				}
				$status = 'default';
				$bret = wppa_create_iptc_entry( array( 'photo' => $photo, 'tag' => $tag, 'description' => $desc, 'status' => $status ) );
				if ( ! $bret ) wppa_log( 'Warning', 'Could not add IPTC tag '.$tag.' for photo '.$photo );
			}
		}
	}
}

function wppa_get_exif_datetime( $file ) {

	// Make sure we do not process a -o1.jpg file
	$file = str_replace( '-o1.jpg', '.jpg', $file );
	return wppa_get_exif_item( $file, 'DateTimeOriginal' );
}

function wppa_get_exif_orientation( $file ) {

	return wppa_get_exif_item( $file, 'Orientation' );
}

function wppa_get_exif_item( $file, $item ) {

	// File exists?
	if ( ! is_file( $file ) ) {
		return false;
	}

	// Exif functions present?
	if ( ! function_exists( 'exif_imagetype' ) ) {
		return false;
	}

	// Check filetype
	$image_type = @ exif_imagetype( $file );
	if ( $image_type != IMAGETYPE_JPEG ) {
		return false;
	}

	// Can get exif data?
	if ( ! function_exists( 'exif_read_data' ) ) {
		return false;
	}

	// Get exif data
	$exif = @ exif_read_data( $file, 'EXIF' );
	if ( ! is_array( $exif ) ) {
		return false;
	}

	// Data present
	if ( isset( $exif[$item] ) ) {
		return $exif[$item];
	}

	// Nothing found
	return false;
}


function wppa_import_exif( $id, $file, $nodelete = false ) {
global $wpdb;
static $labels;
static $names;
global $wppa;

	// Do we need this?
	if ( ! wppa_switch( 'save_exif' ) ) return;

	// Make sure we do not process a -o1.jpg file
	$file = str_replace( '-o1.jpg', '.jpg', $file );

	// Check filetype
	if ( ! function_exists( 'exif_imagetype' ) ) return false;

	$image_type = @ exif_imagetype( $file );
	if ( $image_type != IMAGETYPE_JPEG ) return false;	// Not supported image type

	// Get exif data
	if ( ! function_exists( 'exif_read_data' ) ) return false;	// Not supported by the server
	$exif = @ exif_read_data( $file, 'EXIF' );
	if ( ! is_array( $exif ) ) return false;			// No data present

	// There is exif data for this image.
	// First delete any existing exif data for this image
	if ( ! $nodelete ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM `".WPPA_EXIF."` WHERE `photo` = %s", $id ) );
	}

	// Find defined labels
	if ( ! is_array( $labels ) ) {
		$result = $wpdb->get_results( "SELECT * FROM `".WPPA_EXIF."` WHERE `photo` = '0' ORDER BY `tag`", ARRAY_A );

		if ( ! is_array( $result ) ) $result = array();
		$labels = array();
		$names  = array();
		foreach ( $result as $res ) {
			$labels[] = $res['tag'];
			$names[]  = $res['description'];
		}
	}

	// Process items
	foreach ( array_keys( $exif ) as $s ) {

		// Check labels first
		$tag = '';
		if ( in_array( $s, $names ) ) {
			$i = 0;
			while ( $i < count( $labels ) ) {
				if ( $names[$i] == $s ) {
					$tag = $labels[$i];
				}
				$i++;
			}
		}

		if ( $tag == '' ) $tag = wppa_exif_tag( $s );
		if ( $tag == 'E#EA1C' ) $tag = ''; // EA1C is explixitly undefined and will fail to register
		if ( $tag == '' ) continue;

		if ( ! in_array( $tag, $labels ) ) {

			// Add to labels
			$labels[] = $tag;
			$names[]  = $s.':';

			// Add to db
			$photo 	= '0';
			$desc 	= $s.':';
			$status = 'display';
			if ( substr( $s, 0, 12 ) == 'UndefinedTag' ) {
				$status = 'option';
				$desc = wppa_exif_tagname( hexdec( substr( $tag, 2, 4 ) ) );
				if ( substr( $desc, 0, 12 ) != 'UndefinedTag' ) {
					$status = 'display';
				}
			}
			$bret = wppa_create_exif_entry( array( 'photo' => $photo, 'tag' => $tag, 'description' => $desc, 'status' => $status ) );
			if ( ! $bret ) wppa_log( 'War', 'Could not add EXIF tag label '.$tag.' for photo '.$photo );
		}

		// Now add poto specific data item
		// If its an array...
		if ( is_array( $exif[$s] ) ) { // continue;

			$c = count ( $exif[$s] );
			$max = wppa_opt( 'exif_max_array_size' );
			if ( $max != '0' && $c > $max ) {
				wppa_dbg_msg( 'Exif tag '.$tag. ': array truncated form '.$c.' to '.$max.' elements for photo nr '.$id.'.', 'red' );
				$c = $max;
			}
			for ( $i=0; $i <$c; $i++ ) {
				$photo 	= $id;
				$desc 	= $exif[$s][$i];
				$status = 'default';
				$bret = wppa_create_exif_entry( array( 'photo' => $photo, 'tag' => $tag, 'description' => $desc, 'status' => $status ) );
				if ( ! $bret ) wppa_log( 'War', 'Could not add EXIF tag '.$tag.' for photo '.$photo );

			}
		}
		// Its not an array
		else {

			$photo 	= $id;
			$desc 	= $exif[$s];
			$status = 'default';
			$bret = wppa_create_exif_entry( array( 'photo' => $photo, 'tag' => $tag, 'description' => $desc, 'status' => $status ) );
			if ( ! $bret ) {} /* wppa_log( 'Warning 3', 'Could not add EXIF tag '.$tag.' for photo '.$photo.', desc = '.$desc ); */ // Is junk, dont care
		}
	}

	wppa_fix_exif_format( $id );
}

// Convert exif tagname as found by exif_read_data() to E#XXXX, Inverse of exif_tagname();
function wppa_exif_tag( $tagname ) {
static $wppa_inv_exiftags;

	// Setup inverted matrix
	if ( ! is_array( $wppa_inv_exiftags ) ) {
		$key = 0;
		while ( $key < 65536 ) {
			$tag = exif_tagname( $key );
			if ( $tag != '' ) {
				$wppa_inv_exiftags[$tag] = $key;
			}
			$key++;
			if ( ! $key ) break;	// 16 bit server wrap around ( do they still exist??? )
		}
	}

	// Search
	if ( isset( $wppa_inv_exiftags[$tagname] ) ) return sprintf( 'E#%04X',$wppa_inv_exiftags[$tagname] );
	elseif ( strlen( $tagname ) == 19 ) {
		if ( substr( $tagname, 0, 12 ) == 'UndefinedTag' ) return 'E#'.substr( $tagname, -4 );
	}
	else return '';
}

// Wrapper around exif_tagname(), convert 0xXXXX to TagName
function wppa_exif_tagname( $tag, $brand = '', $brandonly = false ) {
global $wpdb;
static $canontags;
static $nikontags;
static $samsungtags;
static $editabletags;
static $commontags;

	// Fill $canontags if not done yet
	if ( empty( $canontags ) ) {
		$canontags = array(
							0x0001 => 'CanonCameraSettings',
							0x0002 => 'CanonFocalLength',
							0x0003 => 'CanonFlashInfo?',
							0x0004 => 'CanonShotInfo',
							0x0005 => 'CanonPanorama',
							0x0006 => 'CanonImageType',
							0x0007 => 'CanonFirmwareVersion',
							0x0008 => 'FileNumber',
							0x0009 => 'OwnerName',
							0x000a => 'UnknownD30',
							0x000c => 'SerialNumber',
							0x000d => 'CanonCameraInfo',
							0x000e => 'CanonFileLength',
							0x000f => 'CustomFunctions',
							0x0010 => 'CanonModelID',
							0x0011 => 'MovieInfo',
							0x0012 => 'CanonAFInfo',
							0x0013 => 'ThumbnailImageValidArea',
							0x0015 => 'SerialNumberFormat',
							0x001a => 'SuperMacro',
							0x001c => 'DateStampMode',
							0x001d => 'MyColors',
							0x001e => 'FirmwareRevision',
							0x0023 => 'Categories',
							0x0024 => 'FaceDetect1',
							0x0025 => 'FaceDetect2',
							0x0026 => 'CanonAFInfo2',
							0x0027 => 'ContrastInfo',
							0x0028 => 'ImageUniqueID',
							0x002f => 'FaceDetect3',
							0x0035 => 'TimeInfo',
							0x003c => 'AFInfo3',
							0x0081 => 'RawDataOffset',
							0x0083 => 'OriginalDecisionDataOffset',
							0x0090 => 'CustomFunctions1D',
							0x0091 => 'PersonalFunctions',
							0x0092 => 'PersonalFunctionValues',
							0x0093 => 'CanonFileInfo',
							0x0094 => 'AFPointsInFocus1D',
							0x0095 => 'LensModel',
							0x0096 => 'SerialInfo',
							0x0097 => 'DustRemovalData',
							0x0098 => 'CropInfo',
							0x0099 => 'CustomFunctions2',
							0x009a => 'AspectInfo',
							0x00a0 => 'ProcessingInfo',
							0x00a1 => 'ToneCurveTable',
							0x00a2 => 'SharpnessTable',
							0x00a3 => 'SharpnessFreqTable',
							0x00a4 => 'WhiteBalanceTable',
							0x00a9 => 'ColorBalance',
							0x00aa => 'MeasuredColor',
							0x00ae => 'ColorTemperature',
							0x00b0 => 'CanonFlags',
							0x00b1 => 'ModifiedInfo',
							0x00b2 => 'ToneCurveMatching',
							0x00b3 => 'WhiteBalanceMatching',
							0x00b4 => 'ColorSpace',
							0x00b6 => 'PreviewImageInfo',
							0x00d0 => 'VRDOffset',
							0x00e0 => 'SensorInfo',
							0x4001 => 'ColorData',
							0x4002 => 'CRWParam?',
							0x4003 => 'ColorInfo',
							0x4005 => 'Flavor?',
							0x4008 => 'PictureStyleUserDef',
							0x4009 => 'PictureStylePC',
							0x4010 => 'CustomPictureStyleFileName',
							0x4013 => 'AFMicroAdj',
							0x4015 => 'VignettingCorr',
							0x4016 => 'VignettingCorr2',
							0x4018 => 'LightingOpt',
							0x4019 => 'LensInfo',
							0x4020 => 'AmbienceInfo',
							0x4021 => 'MultiExp',
							0x4024 => 'FilterInfo',
							0x4025 => 'HDRInfo',
							0x4028 => 'AFConfig',
		);
	}

	// Fill $nikontags if not done yet
	if ( empty( $nikontags ) ) {
		$nikontags = array(
							0x0001 => 'MakerNoteVersion',
							0x0002 => 'ISO',
							0x0003 => 'ColorMode',
							0x0004 => 'Quality',
							0x0005 => 'WhiteBalance',
							0x0006 => 'Sharpness',
							0x0007 => 'FocusMode',
							0x0008 => 'FlashSetting',
							0x0009 => 'FlashType',
							0x000b => 'WhiteBalanceFineTune',
							0x000c => 'WB_RBLevels',
							0x000d => 'ProgramShift',
							0x000e => 'ExposureDifference',
							0x000f => 'ISOSelection',
							0x0010 => 'DataDump',
							0x0011 => 'PreviewIFD',
							0x0012 => 'FlashExposureComp',
							0x0013 => 'ISOSetting',
							0x0014 => 'ColorBalanceA',
							0x0016 => 'ImageBoundary',
							0x0017 => 'ExternalFlashExposureComp',
							0x0018 => 'FlashExposureBracketValue',
							0x0019 => 'ExposureBracketValue',
							0x001a => 'ImageProcessing',
							0x001b => 'CropHiSpeed',
							0x001c => 'ExposureTuning',
							0x001d => 'SerialNumber',
							0x001e => 'ColorSpace',
							0x001f => 'VRInfo',
							0x0020 => 'ImageAuthentication',
							0x0021 => 'FaceDetect',
							0x0022 => 'ActiveD-Lighting',
							0x0023 => 'PictureControlData',
							0x0024 => 'WorldTime',
							0x0025 => 'ISOInfo',
							0x002a => 'VignetteControl',
							0x002b => 'DistortInfo',
							0x002c => 'UnknownInfo',
							0x0032 => 'UnknownInfo2',
							0x0035 => 'HDRInfo',
							0x0039 => 'LocationInfo',
							0x003d => 'BlackLevel',
							0x004f => 'ColorTemperatureAuto',
							0x0080 => 'ImageAdjustment',
							0x0081 => 'ToneComp',
							0x0082 => 'AuxiliaryLens',
							0x0083 => 'LensType',
							0x0084 => 'Lens',
							0x0085 => 'ManualFocusDistance',
							0x0086 => 'DigitalZoom',
							0x0087 => 'FlashMode',
							0x0088 => 'AFInfo',
							0x0089 => 'ShootingMode',
							0x008b => 'LensFStops',
							0x008c => 'ContrastCurve',
							0x008d => 'ColorHue',
							0x008f => 'SceneMode',
							0x0090 => 'LightSource',
							0x0091 => 'ShotInfo',
							0x0092 => 'HueAdjustment',
							0x0093 => 'NEFCompression',
							0x0094 => 'Saturation',
							0x0095 => 'NoiseReduction',
							0x0096 => 'NEFLinearizationTable',
							0x0097 => 'ColorBalance',
							0x0099 => 'RawImageCenter',
							0x009a => 'SensorPixelSize',
							0x009c => 'SceneAssist',
							0x009e => 'RetouchHistory',
							0x00a0 => 'SerialNumber',
							0x00a2 => 'ImageDataSize',
							0x00a5 => 'ImageCount',
							0x00a6 => 'DeletedImageCount',
							0x00a7 => 'ShutterCount',
							0x00a8 => 'FlashInfo',
							0x00a9 => 'ImageOptimization',
							0x00aa => 'Saturation',
							0x00ab => 'VariProgram',
							0x00ac => 'ImageStabilization',
							0x00ad => 'AFResponse',
							0x00b0 => 'MultiExposure',
							0x00b1 => 'HighISONoiseReduction',
							0x00b3 => 'ToningEffect',
							0x00b6 => 'PowerUpTime',
							0x00b7 => 'AFInfo2',
							0x00b8 => 'FileInfo',
							0x00b9 => 'AFTune',
							0x00bb => 'RetouchInfo',
							0x00bd => 'PictureControlData',
							0x00c3 => 'BarometerInfo',
							0x0e00 => 'PrintIM',
							0x0e01 => 'NikonCaptureData',
							0x0e09 => 'NikonCaptureVersion',
							0x0e0e => 'NikonCaptureOffsets',
							0x0e10 => 'NikonScanIFD',
							0x0e13 => 'NikonCaptureEditVersions',
							0x0e1d => 'NikonICCProfile',
							0x0e1e => 'NikonCaptureOutput',
							0x0e22 => 'NEFBitDepth',

		);
	}

	// Fill $samsungtags
	if ( empty( $samsungtags ) ) {
		$samsungtags = array(
							0x0001 => 'MakerNoteVersion',
							0x0002 => 'DeviceType',
							0x0003 => 'SamsungModelID',
							0x0011 => 'OrientationInfo',
							0x0020 => 'SmartAlbumColor',
							0x0021 => 'PictureWizard',
							0x0030 => 'LocalLocationName',
							0x0031 => 'LocationName',
							0x0035 => 'PreviewIFD',
							0x0040 => 'RawDataByteOrder',
							0x0041 => 'WhiteBalanceSetup',
							0x0043 => 'CameraTemperature',
							0x0050 => 'RawDataCFAPattern',
							0x0100 => 'FaceDetect',
							0x0120 => 'FaceRecognition',
							0x0123 => 'FaceName',
							0xa001 => 'FirmwareName',
							0xa003 => 'LensType',
							0xa004 => 'LensFirmware',
							0xa005 => 'InternalLensSerialNumber',
							0xa010 => 'SensorAreas',
							0xa011 => 'ColorSpace',
							0xa012 => 'SmartRange',
							0xa013 => 'ExposureCompensation',
							0xa014 => 'ISO',
							0xa018 => 'ExposureTime',
							0xa019 => 'FNumber',
							0xa01a => 'FocalLengthIn35mmFormat',
							0xa020 => 'EncryptionKey',
							0xa021 => 'WB_RGGBLevelsUncorrected',
							0xa022 => 'WB_RGGBLevelsAuto',
							0xa023 => 'WB_RGGBLevelsIlluminator1',
							0xa024 => 'WB_RGGBLevelsIlluminator2',
							0xa025 => 'HighlightLinearityLimit',
							0xa028 => 'WB_RGGBLevelsBlack',
							0xa030 => 'ColorMatrix',
							0xa031 => 'ColorMatrixSRGB',
							0xa032 => 'ColorMatrixAdobeRGB',
							0xa033 => 'CbCrMatrixDefault',
							0xa034 => 'CbCrMatrix',
							0xa035 => 'CbCrGainDefault',
							0xa036 => 'CbCrGain',
							0xa040 => 'ToneCurveSRGBDefault',
							0xa041 => 'ToneCurveAdobeRGBDefault',
							0xa042 => 'ToneCurveSRGB',
							0xa043 => 'ToneCurveAdobeRGB',
							0xa048 => 'RawData?',
							0xa050 => 'Distortion?',
							0xa051 => 'ChromaticAberration?',
							0xa052 => 'Vignetting?',
							0xa053 => 'VignettingCorrection?',
							0xa054 => 'VignettingSetting?',

		);
	}

	// Fill $editabletags
	if ( empty( $editabletags ) ) {
		$temp = $wpdb->get_results( "SELECT * FROM `" . WPPA_EXIF . "` WHERE `photo` = '0'", ARRAY_A );
		$editabletags = array();
		if ( is_array( $temp ) ) foreach ( $temp as $item ) {
			$editabletags[ hexdec( substr( $item['tag'], 2, 4 ) ) ] = trim( $item['description'], ': ' );
		}
	}

	if ( empty( $commontags ) ) {
		$commontags = array(
							0x0001 => 'InteropIndex',
							0x0002 => 'InteropVersion',
							0x000b => 'ProcessingSoftware',
							0x00fe => 'SubfileType',
							0x00ff => 'OldSubfileType',
							0x0100 => 'ImageWidth',
							0x0101 => 'ImageHeight',
							0x0102 => 'BitsPerSample',
							0x0103 => 'Compression',
							0x0106 => 'PhotometricInterpretation',
							0x0107 => 'Thresholding',
							0x0108 => 'CellWidth',
							0x0109 => 'CellLength',
							0x010a => 'FillOrder',
							0x010d => 'DocumentName',
							0x010e => 'ImageDescription',
							0x010f => 'Make',
							0x0110 => 'Model',
							0x0111 => 'StripOffsets',
							0x0112 => 'Orientation',
							0x0115 => 'SamplesPerPixel',
							0x0116 => 'RowsPerStrip',
							0x0117 => 'StripByteCounts',
							0x0118 => 'MinSampleValue',
							0x0119 => 'MaxSampleValue',
							0x011a => 'XResolution',
							0x011b => 'YResolution',
							0x011c => 'PlanarConfiguration',
							0x011d => 'PageName',
							0x011e => 'XPosition',
							0x011f => 'YPosition',
							0x0120 => 'FreeOffsets',
							0x0121 => 'FreeByteCounts',
							0x0122 => 'GrayResponseUnit',
							0x0123 => 'GrayResponseCurve',
							0x0124 => 'T4Options',
							0x0125 => 'T6Options',
							0x0128 => 'ResolutionUnit',
							0x0129 => 'PageNumber',
							0x012c => 'ColorResponseUnit',
							0x012d => 'TransferFunction',
							0x0131 => 'Software',
							0x0132 => 'ModifyDate',
							0x013b => 'Artist',
							0x013c => 'HostComputer',
							0x013d => 'Predictor',
							0x013e => 'WhitePoint',
							0x013f => 'PrimaryChromaticities',
							0x0140 => 'ColorMap',
							0x0141 => 'HalftoneHints',
							0x0142 => 'TileWidth',
							0x0143 => 'TileLength',
							0x0144 => 'TileOffsets',
							0x0145 => 'TileByteCounts',
							0x0146 => 'BadFaxLines',
							0x0147 => 'CleanFaxData',
							0x0148 => 'ConsecutiveBadFaxLines',
							0x014a => 'SubIFD',
							0x014c => 'InkSet',
							0x014d => 'InkNames',
							0x014e => 'NumberofInks',
							0x0150 => 'DotRange',
							0x0151 => 'TargetPrinter',
							0x0152 => 'ExtraSamples',
							0x0153 => 'SampleFormat',
							0x0154 => 'SMinSampleValue',
							0x0155 => 'SMaxSampleValue',
							0x0156 => 'TransferRange',
							0x0157 => 'ClipPath',
							0x0158 => 'XClipPathUnits',
							0x0159 => 'YClipPathUnits',
							0x015a => 'Indexed',
							0x015b => 'JPEGTables',
							0x015f => 'OPIProxy',
							0x0190 => 'GlobalParametersIFD',
							0x0191 => 'ProfileType',
							0x0192 => 'FaxProfile',
							0x0193 => 'CodingMethods',
							0x0194 => 'VersionYear',
							0x0195 => 'ModeNumber',
							0x01b1 => 'Decode',
							0x01b2 => 'DefaultImageColor',
							0x01b3 => 'T82Options',
							0x01b5 => 'JPEGTables',
							0x0200 => 'JPEGProc',
							0x0201 => 'ThumbnailOffset',
							0x0202 => 'ThumbnailLength',
							0x0203 => 'JPEGRestartInterval',
							0x0205 => 'JPEGLosslessPredictors',
							0x0206 => 'JPEGPointTransforms',
							0x0207 => 'JPEGQTables',
							0x0208 => 'JPEGDCTables',
							0x0209 => 'JPEGACTables',
							0x0211 => 'YCbCrCoefficients',
							0x0212 => 'YCbCrSubSampling',
							0x0213 => 'YCbCrPositioning',
							0x0214 => 'ReferenceBlackWhite',
							0x022f => 'StripRowCounts',
							0x02bc => 'ApplicationNotes',
							0x03e7 => 'USPTOMiscellaneous',
							0x1000 => 'RelatedImageFileFormat',
							0x1001 => 'RelatedImageWidth',
							0x1002 => 'RelatedImageHeight',
							0x4746 => 'Rating',
							0x4747 => 'XP_DIP_XML',
							0x4748 => 'StitchInfo',
							0x4749 => 'RatingPercent',
							0x7000 => 'SonyRawFileType',
							0x7032 => 'VignettingCorrParams',
							0x7035 => 'ChromaticAberrationCorrParams',
							0x7037 => 'DistortionCorrParams',
							0x800d => 'ImageID',
							0x80a3 => 'WangTag1',
							0x80a4 => 'WangAnnotation',
							0x80a5 => 'WangTag3',
							0x80a6 => 'WangTag4',
							0x80b9 => 'ImageReferencePoints',
							0x80ba => 'RegionXformTackPoint',
							0x80bb => 'WarpQuadrilateral',
							0x80bc => 'AffineTransformMat',
							0x80e3 => 'Matteing',
							0x80e4 => 'DataType',
							0x80e5 => 'ImageDepth',
							0x80e6 => 'TileDepth',
							0x8214 => 'ImageFullWidth',
							0x8215 => 'ImageFullHeight',
							0x8216 => 'TextureFormat',
							0x8217 => 'WrapModes',
							0x8218 => 'FovCot',
							0x8219 => 'MatrixWorldToScreen',
							0x821a => 'MatrixWorldToCamera',
							0x827d => 'Model2',
							0x828d => 'CFARepeatPatternDim',
							0x828e => 'CFAPattern2',
							0x828f => 'BatteryLevel',
							0x8290 => 'KodakIFD',
							0x8298 => 'Copyright',
							0x829a => 'ExposureTime',
							0x829d => 'FNumber',
							0x82a5 => 'MDFileTag',
							0x82a6 => 'MDScalePixel',
							0x82a7 => 'MDColorTable',
							0x82a8 => 'MDLabName',
							0x82a9 => 'MDSampleInfo',
							0x82aa => 'MDPrepDate',
							0x82ab => 'MDPrepTime',
							0x82ac => 'MDFileUnits',
							0x830e => 'PixelScale',
							0x8335 => 'AdventScale',
							0x8336 => 'AdventRevision',
							0x835c => 'UIC1Tag',
							0x835d => 'UIC2Tag',
							0x835e => 'UIC3Tag',
							0x835f => 'UIC4Tag',
							0x83bb => 'IPTC-NAA',
							0x847e => 'IntergraphPacketData',
							0x847f => 'IntergraphFlagRegisters',
							0x8480 => 'IntergraphMatrix',
							0x8481 => 'INGRReserved',
							0x8482 => 'ModelTiePoint',
							0x84e0 => 'Site',
							0x84e1 => 'ColorSequence',
							0x84e2 => 'IT8Header',
							0x84e3 => 'RasterPadding',
							0x84e4 => 'BitsPerRunLength',
							0x84e5 => 'BitsPerExtendedRunLength',
							0x84e6 => 'ColorTable',
							0x84e7 => 'ImageColorIndicator',
							0x84e8 => 'BackgroundColorIndicator',
							0x84e9 => 'ImageColorValue',
							0x84ea => 'BackgroundColorValue',
							0x84eb => 'PixelIntensityRange',
							0x84ec => 'TransparencyIndicator',
							0x84ed => 'ColorCharacterization',
							0x84ee => 'HCUsage',
							0x84ef => 'TrapIndicator',
							0x84f0 => 'CMYKEquivalent',
							0x8546 => 'SEMInfo',
							0x8568 => 'AFCP_IPTC',
							0x85b8 => 'PixelMagicJBIGOptions',
							0x85d7 => 'JPLCartoIFD',
							0x85d8 => 'ModelTransform',
							0x8602 => 'WB_GRGBLevels',
							0x8606 => 'LeafData',
							0x8649 => 'PhotoshopSettings',
							0x8769 => 'ExifOffset',
							0x8773 => 'ICC_Profile',
							0x877f => 'TIFF_FXExtensions',
							0x8780 => 'MultiProfiles',
							0x8781 => 'SharedData',
							0x8782 => 'T88Options',
							0x87ac => 'ImageLayer',
							0x87af => 'GeoTiffDirectory',
							0x87b0 => 'GeoTiffDoubleParams',
							0x87b1 => 'GeoTiffAsciiParams',
							0x87be => 'JBIGOptions',
							0x8822 => 'ExposureProgram',
							0x8824 => 'SpectralSensitivity',
							0x8825 => 'GPSInfo',
							0x8827 => 'ISO',
							0x8828 => 'Opto-ElectricConvFactor',
							0x8829 => 'Interlace',
							0x882a => 'TimeZoneOffset',
							0x882b => 'SelfTimerMode',
							0x8830 => 'SensitivityType',
							0x8831 => 'StandardOutputSensitivity',
							0x8832 => 'RecommendedExposureIndex',
							0x8833 => 'ISOSpeed',
							0x8834 => 'ISOSpeedLatitudeyyy',
							0x8835 => 'ISOSpeedLatitudezzz',
							0x885c => 'FaxRecvParams',
							0x885d => 'FaxSubAddress',
							0x885e => 'FaxRecvTime',
							0x8871 => 'FedexEDR',
							0x888a => 'LeafSubIFD',
							0x9000 => 'ExifVersion',
							0x9003 => 'DateTimeOriginal',
							0x9004 => 'CreateDate',
							0x9009 => 'GooglePlusUploadCode',
							0x9010 => 'OffsetTime',
							0x9011 => 'OffsetTimeOriginal',
							0x9012 => 'OffsetTimeDigitized',
							0x9101 => 'ComponentsConfiguration',
							0x9102 => 'CompressedBitsPerPixel',
							0x9201 => 'ShutterSpeedValue',
							0x9202 => 'ApertureValue',
							0x9203 => 'BrightnessValue',
							0x9204 => 'ExposureCompensation',
							0x9205 => 'MaxApertureValue',
							0x9206 => 'SubjectDistance',
							0x9207 => 'MeteringMode',
							0x9208 => 'LightSource',
							0x9209 => 'Flash',
							0x920a => 'FocalLength',
							0x920b => 'FlashEnergy',
							0x920c => 'SpatialFrequencyResponse',
							0x920d => 'Noise',
							0x920e => 'FocalPlaneXResolution',
							0x920f => 'FocalPlaneYResolution',
							0x9210 => 'FocalPlaneResolutionUnit',
							0x9211 => 'ImageNumber',
							0x9212 => 'SecurityClassification',
							0x9213 => 'ImageHistory',
							0x9214 => 'SubjectArea',
							0x9215 => 'ExposureIndex',
							0x9216 => 'TIFF-EPStandardID',
							0x9217 => 'SensingMethod',
							0x923a => 'CIP3DataFile',
							0x923b => 'CIP3Sheet',
							0x923c => 'CIP3Side',
							0x923f => 'StoNits',
							0x927c => 'MakerNote',
							0x9286 => 'UserComment',
							0x9290 => 'SubSecTime',
							0x9291 => 'SubSecTimeOriginal',
							0x9292 => 'SubSecTimeDigitized',
							0x932f => 'MSDocumentText',
							0x9330 => 'MSPropertySetStorage',
							0x9331 => 'MSDocumentTextPosition',
							0x935c => 'ImageSourceData',
							0x9400 => 'AmbientTemperature',
							0x9401 => 'Humidity',
							0x9402 => 'Pressure',
							0x9403 => 'WaterDepth',
							0x9404 => 'Acceleration',
							0x9405 => 'CameraElevationAngle',
							0x9c9b => 'XPTitle',
							0x9c9c => 'XPComment',
							0x9c9d => 'XPAuthor',
							0x9c9e => 'XPKeywords',
							0x9c9f => 'XPSubject',
							0xa000 => 'FlashpixVersion',
							0xa001 => 'ColorSpace',
							0xa002 => 'ExifImageWidth',
							0xa003 => 'ExifImageHeight',
							0xa004 => 'RelatedSoundFile',
							0xa005 => 'InteropOffset',
							0xa010 => 'SamsungRawPointersOffset',
							0xa011 => 'SamsungRawPointersLength',
							0xa101 => 'SamsungRawByteOrder',
							0xa102 => 'SamsungRawUnknown?',
							0xa20b => 'FlashEnergy',
							0xa20c => 'SpatialFrequencyResponse',
							0xa20d => 'Noise',
							0xa20e => 'FocalPlaneXResolution',
							0xa20f => 'FocalPlaneYResolution',
							0xa210 => 'FocalPlaneResolutionUnit',
							0xa211 => 'ImageNumber',
							0xa212 => 'SecurityClassification',
							0xa213 => 'ImageHistory',
							0xa214 => 'SubjectLocation',
							0xa215 => 'ExposureIndex',
							0xa216 => 'TIFF-EPStandardID',
							0xa217 => 'SensingMethod',
							0xa300 => 'FileSource',
							0xa301 => 'SceneType',
							0xa302 => 'CFAPattern',
							0xa401 => 'CustomRendered',
							0xa402 => 'ExposureMode',
							0xa403 => 'WhiteBalance',
							0xa404 => 'DigitalZoomRatio',
							0xa405 => 'FocalLengthIn35mmFormat',
							0xa406 => 'SceneCaptureType',
							0xa407 => 'GainControl',
							0xa408 => 'Contrast',
							0xa409 => 'Saturation',
							0xa40a => 'Sharpness',
							0xa40b => 'DeviceSettingDescription',
							0xa40c => 'SubjectDistanceRange',
							0xa420 => 'ImageUniqueID',
							0xa430 => 'OwnerName',
							0xa431 => 'SerialNumber',
							0xa432 => 'LensInfo',
							0xa433 => 'LensMake',
							0xa434 => 'LensModel',
							0xa435 => 'LensSerialNumber',
							0xa480 => 'GDALMetadata',
							0xa481 => 'GDALNoData',
							0xa500 => 'Gamma',
							0xafc0 => 'ExpandSoftware',
							0xafc1 => 'ExpandLens',
							0xafc2 => 'ExpandFilm',
							0xafc3 => 'ExpandFilterLens',
							0xafc4 => 'ExpandScanner',
							0xafc5 => 'ExpandFlashLamp',
							0xbc01 => 'PixelFormat',
							0xbc03 => 'Uncompressed',
							0xbc04 => 'ImageType',
							0xbc80 => 'ImageWidth',
							0xbc81 => 'ImageHeight',
							0xbc82 => 'WidthResolution',
							0xbc83 => 'HeightResolution',
							0xbcc0 => 'ImageOffset',
							0xbcc1 => 'ImageByteCount',
							0xbcc2 => 'AlphaOffset',
							0xbcc3 => 'AlphaByteCount',
							0xbcc4 => 'ImageDataDiscard',
							0xbcc5 => 'AlphaDataDiscard',
							0xc427 => 'OceScanjobDesc',
							0xc428 => 'OceApplicationSelector',
							0xc429 => 'OceIDNumber',
							0xc42a => 'OceImageLogic',
							0xc44f => 'Annotations',
							0xc4a5 => 'PrintIM',
							0xc573 => 'OriginalFileName',
							0xc580 => 'USPTOOriginalContentType',
							0xc5e0 => 'CR2CFAPattern',
							0xc612 => 'DNGVersion',
							0xc613 => 'DNGBackwardVersion',
							0xc614 => 'UniqueCameraModel',
							0xc615 => 'LocalizedCameraModel',
							0xc616 => 'CFAPlaneColor',
							0xc617 => 'CFALayout',
							0xc618 => 'LinearizationTable',
							0xc619 => 'BlackLevelRepeatDim',
							0xc61a => 'BlackLevel',
							0xc61b => 'BlackLevelDeltaH',
							0xc61c => 'BlackLevelDeltaV',
							0xc61d => 'WhiteLevel',
							0xc61e => 'DefaultScale',
							0xc61f => 'DefaultCropOrigin',
							0xc620 => 'DefaultCropSize',
							0xc621 => 'ColorMatrix1',
							0xc622 => 'ColorMatrix2',
							0xc623 => 'CameraCalibration1',
							0xc624 => 'CameraCalibration2',
							0xc625 => 'ReductionMatrix1',
							0xc626 => 'ReductionMatrix2',
							0xc627 => 'AnalogBalance',
							0xc628 => 'AsShotNeutral',
							0xc629 => 'AsShotWhiteXY',
							0xc62a => 'BaselineExposure',
							0xc62b => 'BaselineNoise',
							0xc62c => 'BaselineSharpness',
							0xc62d => 'BayerGreenSplit',
							0xc62e => 'LinearResponseLimit',
							0xc62f => 'CameraSerialNumber',
							0xc630 => 'DNGLensInfo',
							0xc631 => 'ChromaBlurRadius',
							0xc632 => 'AntiAliasStrength',
							0xc633 => 'ShadowScale',
							0xc640 => 'RawImageSegmentation',
							0xc65a => 'CalibrationIlluminant1',
							0xc65b => 'CalibrationIlluminant2',
							0xc65c => 'BestQualityScale',
							0xc65d => 'RawDataUniqueID',
							0xc660 => 'AliasLayerMetadata',
							0xc68b => 'OriginalRawFileName',
							0xc68c => 'OriginalRawFileData',
							0xc68d => 'ActiveArea',
							0xc68e => 'MaskedAreas',
							0xc68f => 'AsShotICCProfile',
							0xc690 => 'AsShotPreProfileMatrix',
							0xc691 => 'CurrentICCProfile',
							0xc692 => 'CurrentPreProfileMatrix',
							0xc6bf => 'ColorimetricReference',
							0xc6c5 => 'SRawType',
							0xc6d2 => 'PanasonicTitle',
							0xc6d3 => 'PanasonicTitle2',
							0xc6f3 => 'CameraCalibrationSig',
							0xc6f4 => 'ProfileCalibrationSig',
							0xc6f5 => 'ProfileIFD',
							0xc6f6 => 'AsShotProfileName',
							0xc6f7 => 'NoiseReductionApplied',
							0xc6f8 => 'ProfileName',
							0xc6f9 => 'ProfileHueSatMapDims',
							0xc6fa => 'ProfileHueSatMapData1',
							0xc6fb => 'ProfileHueSatMapData2',
							0xc6fc => 'ProfileToneCurve',
							0xc6fd => 'ProfileEmbedPolicy',
							0xc6fe => 'ProfileCopyright',
							0xc714 => 'ForwardMatrix1',
							0xc715 => 'ForwardMatrix2',
							0xc716 => 'PreviewApplicationName',
							0xc717 => 'PreviewApplicationVersion',
							0xc718 => 'PreviewSettingsName',
							0xc719 => 'PreviewSettingsDigest',
							0xc71a => 'PreviewColorSpace',
							0xc71b => 'PreviewDateTime',
							0xc71c => 'RawImageDigest',
							0xc71d => 'OriginalRawFileDigest',
							0xc71e => 'SubTileBlockSize',
							0xc71f => 'RowInterleaveFactor',
							0xc725 => 'ProfileLookTableDims',
							0xc726 => 'ProfileLookTableData',
							0xc740 => 'OpcodeList1',
							0xc741 => 'OpcodeList2',
							0xc74e => 'OpcodeList3',
							0xc761 => 'NoiseProfile',
							0xc763 => 'TimeCodes',
							0xc764 => 'FrameRate',
							0xc772 => 'TStop',
							0xc789 => 'ReelName',
							0xc791 => 'OriginalDefaultFinalSize',
							0xc792 => 'OriginalBestQualitySize',
							0xc793 => 'OriginalDefaultCropSize',
							0xc7a1 => 'CameraLabel',
							0xc7a3 => 'ProfileHueSatMapEncoding',
							0xc7a4 => 'ProfileLookTableEncoding',
							0xc7a5 => 'BaselineExposureOffset',
							0xc7a6 => 'DefaultBlackRender',
							0xc7a7 => 'NewRawImageDigest',
							0xc7a8 => 'RawToPreviewGain',
							0xc7b5 => 'DefaultUserCrop',
							0xea1c => 'Padding',
							0xea1d => 'OffsetSchema',
							0xfde8 => 'OwnerName',
							0xfde9 => 'SerialNumber',
							0xfdea => 'Lens',
							0xfe00 => 'KDC_IFD',
							0xfe4c => 'RawFile',
							0xfe4d => 'Converter',
							0xfe4e => 'WhiteBalance',
							0xfe51 => 'Exposure',
							0xfe52 => 'Shadows',
							0xfe53 => 'Brightness',
							0xfe54 => 'Contrast',
							0xfe55 => 'Saturation',
							0xfe56 => 'Sharpness',
							0xfe57 => 'Smoothness',
							0xfe58 => 'MoireFilter',

		);
	}

	// Init
	$result = '';

	// If brand given, try to find brand dependant tagname
	switch( $brand ) {

		case 'CANON':
			if ( isset( $canontags[$tag] ) ) {
				$result = $canontags[$tag];
			}
			break;

		case 'NIKON':
			if ( isset( $nikontags[$tag] ) ) {
				$result = $nikontags[$tag];
			}
			break;

		case 'SAMSUNG':
			if ( isset( $samsungtags[$tag] ) ) {
				$result = $samsungtags[$tag];
			}

	}

	// If brand only requested, return result, even when blank
	if ( $brandonly ) {
		return $result;
	}

	// Not found? Try editable tags
	if ( ! $result ) {
		if ( isset( $editabletags[$tag] ) ) {
			$result = $editabletags[$tag];
		}
	}

	// Not found? Try common tags
	if ( ! $result ) {
		if ( isset( $commontags[$tag] ) ) {
			$result = $commontags[$tag];
		}
	}

	// Not found? Find generic tag name
	if ( ! $result ) {
		$result = exif_tagname( $tag );
		if ( ! $result ) {
			wppa_log( 'dbg', 'exif_tagname found nothing for ' . sprintf( '0x%04X', $tag ) );
			$result = sprintf( 'UndefinedTag:0x%04X', $tag );
		}
	}

	return $result;
}

// Get gps data from photofile
function wppa_get_coordinates( $picture_path, $photo_id ) {
global $wpdb;

	// Make sure we look at the original, not the -o1 file
	$picture_path = str_replace( '-o1.jpg', '.jpg', $picture_path );

	// Exif on board?
	if ( ! function_exists( 'exif_read_data' ) ) return false;

	// Check filetype
	if ( ! function_exists( 'exif_imagetype' ) ) return false;
	$image_type = @ exif_imagetype( $picture_path );
	if ( $image_type != IMAGETYPE_JPEG ) return false;	// Not supported image type

	// get exif data
	if ( $exif = @ exif_read_data( $picture_path, 0 , false ) ) {

		// any coordinates available?
		if ( !isset ( $exif['GPSLatitude'][0] ) ) return false;	// No GPS data
		if ( !isset ( $exif['GPSLongitude'][0] ) ) return false;	// No GPS data

		// north, east, south, west?
		if ( $exif['GPSLatitudeRef'] == "S" ) {
			$gps['latitude_string'] = -1;
			$gps['latitude_dicrection'] = "S";
		}
		else {
			$gps['latitude_string'] = 1;
			$gps['latitude_dicrection'] = "N";
		}
		if ( $exif['GPSLongitudeRef'] == "W" ) {
			$gps['longitude_string'] = -1;
			$gps['longitude_dicrection'] = "W";
		}
		else {
			$gps['longitude_string'] = 1;
			$gps['longitude_dicrection'] = "E";
		}
		// location
		$gps['latitude_hour'] = $exif["GPSLatitude"][0];
		$gps['latitude_minute'] = $exif["GPSLatitude"][1];
		$gps['latitude_second'] = $exif["GPSLatitude"][2];
		$gps['longitude_hour'] = $exif["GPSLongitude"][0];
		$gps['longitude_minute'] = $exif["GPSLongitude"][1];
		$gps['longitude_second'] = $exif["GPSLongitude"][2];

		// calculating
		foreach( $gps as $key => $value ) {
			$pos = strpos( $value, '/' );
			if ( $pos !== false ) {
				$temp = explode( '/',$value );
				if ( $temp[1] ) $gps[$key] = $temp[0] / $temp[1];
				else $gps[$key] = 0;
			}
		}

		$geo['latitude_format'] = $gps['latitude_dicrection']." ".$gps['latitude_hour']."&deg;".$gps['latitude_minute']."&#x27;".round ( $gps['latitude_second'], 4 ).'&#x22;';
		$geo['longitude_format'] = $gps['longitude_dicrection']." ".$gps['longitude_hour']."&deg;".$gps['longitude_minute']."&#x27;".round ( $gps['longitude_second'], 4 ).'&#x22;';

		$geo['latitude'] = $gps['latitude_string'] * ( $gps['latitude_hour'] + ( $gps['latitude_minute'] / 60 ) + ( $gps['latitude_second'] / 3600 ) );
		$geo['longitude'] = $gps['longitude_string'] * ( $gps['longitude_hour'] + ( $gps['longitude_minute'] / 60 ) + ( $gps['longitude_second'] / 3600 ) );

	}
	else {	// No exif data
		return false;
	}

	// Process result
//	print_r( $geo );	// debug
	$result = implode( '/', $geo );
	$wpdb->query( $wpdb->prepare( "UPDATE `".WPPA_PHOTOS."` SET `location` = %s WHERE `id` = %s", $result, $photo_id ) );
	return $geo;
}

function wppa_get_camera_brand( $id ) {
global $wpdb;

	// Try stored exif data
	$E010F = $wpdb->get_var( $wpdb->prepare( "SELECT `description` FROM `" . WPPA_EXIF . "` WHERE `photo` = %s AND `tag` = 'E#010F' ", $id ) );
	if ( $E010F ) {
		$E010F = strtolower( $E010F );
		if ( strpos( $E010F, 'canon' ) !== false ) {
			return 'CANON';
		}
		if ( strpos( $E010F, 'nikon' ) !== false ) {
			return 'NIKON';
		}
		if ( strpos( $E010F, 'samsung' ) !== false ) {
			return 'SAMSUNG';
		}
	}

	// Try source path
	$src = wppa_get_source_path( $id );
	if ( $src ) {
		$exifs = @ exif_read_data( $src, 'EXIF' );
		if ( $exifs ) {
			if ( isset( $exifs['Make'] ) ) {
				$E010F = strtolower( $exifs['Make'] );
				if ( strpos( $E010F, 'canon' ) !== false ) {
					return 'CANON';
				}
				if ( strpos( $E010F, 'nikon' ) !== false ) {
					return 'NIKON';
				}
				if ( strpos( $E010F, 'samsung' ) !== false ) {
					return 'SAMSUNG';
				}
			}
		}
	}

	// Not found
	return '';

}