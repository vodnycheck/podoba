// wppa-utils.js
//
// conatins common vars and functions
//
var wppaJsUtilsVersion = '6.6.28';
var wppaDebug;

// Trim
// @1 string to be trimmed
// @2 character, string, or array of characters or strings to trim off,
//    default: trim spaces, tabs and newlines
function wppaTrim( str, arg ) {

	var result;

	result = wppaTrimLeft( str, arg );
	result = wppaTrimRight( result, arg );

	return result;
}

// Trim left
// @1 string to be trimmed
// @2 character, string, or array of characters or strings to trim off,
//    default: trim spaces, tabs and newlines
function wppaTrimLeft( str, arg ) {

	var result;
	var strlen;
	var arglen;
	var argcount;
	var i;
	var done;
	var oldStr, newStr;

	switch ( typeof ( arg ) ) {
		case 'string':
			result = str;
			strlen = str.length;
			arglen = arg.length;
			while ( strlen >= arglen && result.substr( 0, arglen ) == arg ) {
				result = result.substr( arglen );
				strlen = result.length;
			}
			break;
		case 'object':
			done = false;
			newStr = str;
			while ( ! done ) {
				i = 0;
				oldStr = newStr;
				while ( i < arg.length ) {
					newStr = wppaTrimLeft( newStr, arg[i] );
					i++;
				}
				done = ( oldStr == newStr );
			}
			result = newStr;
			break;
		default:
			return str.replace( /^\s\s*/, '' );
	}

	return result;
}

// Trim right
// @1 string to be trimmed
// @2 character, string, or array of characters or strings to trim off,
//    default: trim spaces, tabs and newlines
function wppaTrimRight( str, arg ) {

	var result;
	var strlen;
	var arglen;
	var argcount;
	var i;
	var done;
	var oldStr, newStr;

	switch ( typeof ( arg ) ) {
		case 'string':
			result = str;
			strlen = str.length;
			arglen = arg.length;
			while ( strlen >= arglen && result.substr( strlen - arglen ) == arg ) {
				result = result.substr( 0, strlen - arglen );
				strlen = result.length;
			}
			break;
		case 'object':
			done = false;
			newStr = str;
			while ( ! done ) {
				i = 0;
				oldStr = newStr;
				while ( i < arg.length ) {
					newStr = wppaTrimRight( newStr, arg[i] );
					i++;
				}
				done = ( oldStr == newStr );
			}
			result = newStr;
			break;
		default:
			return str.replace( /\s\s*$/, '' );
	}

	return result;
}

// Cookie handling
function wppa_setCookie(c_name,value,exdays) {
var exdate=new Date();
exdate.setDate(exdate.getDate() + exdays);
var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
document.cookie=c_name + "=" + c_value;
}

function wppa_getCookie(c_name) {
var i,x,y,ARRcookies=document.cookie.split(";");
for (i=0;i<ARRcookies.length;i++)
{
  x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
  y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
  x=x.replace(/^\s+|\s+$/g,"");
  if (x==c_name)
    {
    return unescape(y);
    }
  }
  return "";
}

// Change stereotype cookie
function wppaStereoTypeChange( newval ) {
	wppa_setCookie( 'stereotype', newval, 365 );
}

// Change stereoglass cookie
function wppaStereoGlassChange( newval ) {
	wppa_setCookie( 'stereoglass', newval, 365 );
}

// Console logging
function wppaConsoleLog( arg, force ) {

	if ( typeof( console ) != 'undefined' && ( wppaDebug || force == 'force' ) ) {
		var d = new Date();
		var n = d.getTime();
		var t = n % (24*60*60*1000); 				// msec this day
		var h = Math.floor( t / ( 60*60*1000 ) ); 	// Hours this day
		t -= h * 60*60*1000;						// msec this hour
		var m = Math.floor( t / ( 60*1000 ) );		// Minutes this hour
		t -= m * 60*1000;							// msec this minute
		var s = Math.floor( t / 1000 );				// Sec this minute
		t -= s * 1000;								// msec this sec
		console.log( 'At: ' + h + ':' + m + ':' + s + '.' + t + ' message: ' + arg );
	}
}

// Conversion utility
function wppaConvertScriptToShortcode( scriptId, shortcodeId ) {

	var script;
	var workArr;
	var temp;
	var item;
	var value;
	var type;
	var album;
	var photo;
	var size;
	var align;
	var result;

	script = jQuery( '#'+scriptId ).val();
	if ( typeof( script ) != 'string' || script.length == 0 ) {
		jQuery( '#'+shortcodeId ).val( 'No script found' );
		jQuery( '#'+shortcodeId ).css( 'color', 'red' );
		return;
	}

	workarr = script.split( '%%' );
	if ( workarr[1] != 'wppa' || workarr.length < 3 ) {
		jQuery( '#'+shortcodeId ).val( 'No %%wppa%% found' );
		jQuery( '#'+shortcodeId ).css( 'color', 'red' );
		return;
	}

	for ( i=3;i<workarr.length;i+=2 ) {
		temp = workarr[i].split( '=' );
		item = temp[0];
		value = temp[1];
		if ( item && value ) {
			switch( item ) {
				case 'size':
					size = value;
					break;
				case 'align':
					align = value;
					break;
				case 'photo':
				case 'mphoto':
				case 'slphoto':
					type = item;
					photo = value;
					break;
				case 'album':
				case 'cover':
				case 'slide':
				case 'slideonly':
				case 'slideonlyf':
				case 'slidef':
					type = item;
					album = value;
					break;
				default:
					jQuery( '#'+shortcodeId ).val( 'Token "' + workarr[i] + '" not recognized' );
					jQuery( '#'+shortcodeId ).css( 'color', 'red' );
					return;

			}
		}
	}

	result = '[wppa';

	if ( type && type.length > 0 ) {
		result += ' type="' + type + '"';
	}

	if ( album && album.length > 0 ) {
		result += ' album="' + album + '"';
	}

	if ( photo && photo.length > 0 ) {
		result += ' photo="' + photo + '"';
	}

	if ( size && size.length > 0 ) {
		result += ' size="' + size + '"';
	}

	if ( align && align.length > 0 ) {
		result += ' align="' + align + '"';
	}

	result += '][/wppa]';

	jQuery( '#'+shortcodeId ).val( result );
	jQuery( '#'+shortcodeId ).css( 'color', 'green' );

	document.getElementById( shortcodeId ).focus();
    document.getElementById( shortcodeId ).select();

}

// Get an svg image html
// @1: string: Name of the .svg file without extension
// @2: string: CSS height or empty, no ; required
// @3: bool: True if for lightbox. Use lightbox colors
// @4: bool: if true: add border
// @5: radius in % type none
// @6: radius in % type light
// @7: radius in % type medium
// @8: radius in % type heavy
function wppaSvgHtml( image, height, isLightbox, border, none, light, medium, heavy ) {

	var fc; 	// Foreground (fill) color
	var bc; 	// Background color

	if ( ! none ) none = '0';
	if ( ! light ) light = '10';
	if ( ! medium ) medium = '20';
	if ( ! heavy ) heavy = '50';

	// Find Radius
	switch ( wppaSvgCornerStyle ) {
		case 'gif':
		case 'none':
			radius = none;
			break;
		case 'light':
			radius = light;
			break;
		case 'medium':
			radius = medium;
			break;
		case 'heavy':
			radius = heavy;
			break;
	}

	// Init Height
	if ( ! height ) {
		height = '32px';
	}

	// Get Colors
	if ( isLightbox ) {
		fc = wppaOvlSvgFillcolor;
		bc = wppaOvlSvgBgcolor;
	}
	else {
		fc = wppaSvgFillcolor;
		bc = wppaSvgBgcolor;
	}

	var src;
	if ( wppaUseSvg ) {
		src = wppaImageDirectory + image + '.svg';
	}
	else {
		src = wppaImageDirectory + image + '.png';
	}

	// Make the html. Native svg html
	var wppaSvgArray = [ 	'Next-Button',
							'Prev-Button',
							'Backward-Button',
							'Forward-Button',
							'Pause-Button',
							'Play-Button',
							'Stop-Button',
							'Eagle-1',
							'Snail',
							'Exit',
							'Full-Screen',
							'Exit-Full-Screen',
							'Content-View'
						];
	if ( wppaUseSvg && jQuery.inArray( image, wppaSvgArray ) != '-1' ) {

		var result = 	'<svg' +
							' version="1.1"' +
							' xmlns="http://www.w3.org/2000/svg"' +
							' xmlns:xlink="http://www.w3.org/1999/xlink"' +
							' x="0px"' +
							' y="0px"' +
							' viewBox="0 0 30 30"' +
							' style="' +
								'enable-background:new 0 0 30 30;' +
								( height ? 'height:' + height + ';' : '' ) +
								'fill:' + fc + ';' +
								'background-color:' + bc + ';' +
								'text-decoration:none !important;' +
								'vertical-align:middle;' +
								( radius ? 'border-radius:' + radius + '%;' : '' ) +
								( border ? 'border:2px solid ' + bc + ';box-sizing:border-box;' : '' ) +
								'"' +
							' xml:space="preserve"' +
							' >' +
							'<g>';
		switch ( image ) {
			case 'Next-Button':
				result += 	'<path' +
								' d="M30,0H0V30H30V0z M20,20.5' +
									'c0,0.3-0.2,0.5-0.5,0.5S19,20.8,19,20.5v-4.2l-8.3,4.6c-0.1,0-0.2,0.1-0.2,0.1c-0.1,0-0.2,0-0.3-0.1c-0.2-0.1-0.2-0.3-0.2-0.4v-11' +
									'c0-0.2,0.1-0.4,0.3-0.4c0.2-0.1,0.4-0.1,0.5,0l8.2,5.5V9.5C19,9.2,19.2,9,19.5,9S20,9.2,20,9.5V20.5z"' +
							' />';
				break;
			case 'Prev-Button':
				result += 	'<path' +
								' d="M30,0H0V30H30V0z M20,20.5' +
									'c0,0.2-0.1,0.4-0.3,0.4c-0.1,0-0.2,0.1-0.2,0.1c-0.1,0-0.2,0-0.3-0.1L11,15.4v5.1c0,0.3-0.2,0.5-0.5,0.5S10,20.8,10,20.5v-11' +
									'C10,9.2,10.2,9,10.5,9S11,9.2,11,9.5v4.2l8.3-4.6c0.2-0.1,0.3-0.1,0.5,0S20,9.3,20,9.5V20.5z"' +
							' />';
				break;
			case 'Pause-Button':
				result += 	'<path' +
								' d="M30,0H0V30H30V0z M14,20.5' +
									'c0,0.3-0.2,0.5-0.5,0.5h-4C9.2,21,9,20.8,9,20.5v-11C9,9.2,9.2,9,9.5,9h4C13.8,9,14,9.2,14,9.5V20.5z M21,20.5' +
									'c0,0.3-0.2,0.5-0.5,0.5h-4c-0.3,0-0.5-0.2-0.5-0.5v-11C16,9.2,16.2,9,16.5,9h4C20.8,9,21,9.2,21,9.5V20.5z"' +
							' />';
				break;
			case 'Play-Button':
				result += 	'<path' +
								' d="M30,0H0V30H30V0z' +
									'M19.8,14.9l-8,5C11.7,20,11.6,20,11.5,20c-0.1,0-0.2,0-0.2-0.1c-0.2-0.1-0.3-0.3-0.3-0.4v-9c0-0.2,0.1-0.3,0.2-0.4' +
									'c0.1-0.1,0.3-0.1,0.5,0l8,4c0.2,0.1,0.3,0.2,0.3,0.4C20,14.7,19.9,14.8,19.8,14.9z"' +
							' />';
				break;
			case 'Stop-Button':
				result += 	'<path' +
								' d="M30,0H0V30H30V0z M21,20.5' +
									'c0,0.3-0.2,0.5-0.5,0.5h-11C9.2,21,9,20.8,9,20.5v-11C9,9.2,9.2,9,9.5,9h11C20.8,9,21,9.2,21,9.5V20.5z"' +
							'/>';
				break;
			case 'Exit':
				result += 	'<path d="M30 24.398l-8.406-8.398 8.406-8.398-5.602-5.602-8.398 8.402-8.402-8.402-5.598 5.602 8.398 8.398-8.398 8.398 5.598 5.602 8.402-8.402 8.398 8.402z"></path>';
				break;
			case 'Full-Screen':
				result += 	'<path d="M27.414 24.586l-4.586-4.586-2.828 2.828 4.586 4.586-4.586 4.586h12v-12zM12 0h-12v12l4.586-4.586 4.543 4.539 2.828-2.828-4.543-4.539zM12 22.828l-2.828-2.828-4.586 4.586-4.586-4.586v12h12l-4.586-4.586zM32 0h-12l4.586 4.586-4.543 4.539 2.828 2.828 4.543-4.539 4.586 4.586z"></path>';
				break;
			case 'Exit-Full-Screen':
				result += 	'<path d="M24.586 27.414l4.586 4.586 2.828-2.828-4.586-4.586 4.586-4.586h-12v12zM0 12h12v-12l-4.586 4.586-4.539-4.543-2.828 2.828 4.539 4.543zM0 29.172l2.828 2.828 4.586-4.586 4.586 4.586v-12h-12l4.586 4.586zM20 12h12l-4.586-4.586 4.547-4.543-2.828-2.828-4.547 4.543-4.586-4.586z"></path>';
				break;
			default:
				alert( 'Native svg ' + image + ' not implemented' );
		}
		result += 			'</g>' +
						'</svg>';

	}

	// Make the HTML
	else {
		var result = 	'<img' +
							' src="' + src + '"' +
							( wppaUseSvg ? ' class="wppa-svg"' : '' ) +
							' style="' +
								'height:' + height + ';' +
								'fill:' + fc + ';' +
								'background-color:' + bc + ';' +
								( radius ? 'border-radius:' + radius + '%;' : '' ) +
								( border ? 'border:2px solid ' + bc + ';box-sizing:border-box;' : '' ) +
								( wppaUseSvg ? 'display:none;' : '' ) +
								'text-decoration:none !important;' +
								'vertical-align:middle;' +
							'"' +
						' />';
	}

	return result;
}

// Say we're in
wppaConsoleLog( 'wppa-utils.js version '+wppaJsUtilsVersion+' loaded.', 'force' );