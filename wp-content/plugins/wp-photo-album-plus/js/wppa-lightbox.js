// wppa-lightbox.js
//
// Conatins lightbox modules
// Dependancies: wppa.js and default wp jQuery library
//
var wppaLightboxVersion = '6.7.06';

// Global inits
var wppaNormsBtnOpac 		= 0.75;
var wppaIsVideo 			= false;
var wppaHasAudio 			= false;
var wppaOvlImgs 			= [];
var wppaKbHandlerInstalled 	= false;
var wppaOvlMode 			= '';
var wppaOvlCurIdx 			= 0;
var wppaOvlSvgInverse 		= false;
var wppaOvlFsExitBtnSize 	= '48';

// Global size specs
var wppaSavedContainerWidth = 0;
var wppaSavedContainerHeight;
var wppaSavedMarginLeft;
var wppaSavedMarginTop;
var wppaSavedImageWidth;
var wppaSavedImageHeight;

// Initial initialization
jQuery( document ).ready( function( e ) {
	wppaInitOverlay();
});

// Window resize handler
jQuery( window ).resize( function() {
	jQuery( "#wppa-overlay-bg" ).css({
										height:window.innerHeight,
										width:window.innerWidth,
									});
	wppaOvlResize();
});

// Screen resize handler for mobile devices when they rotate
function wppaDoOnOrientationChange( e ) {

	// Full screen and still in?
	if ( wppaOvlMode != 'normal' && document.getElementById( 'wppa-overlay-img' ) ) {
		setTimeout( 'wppaOvlShow( ' + wppaOvlIdx + ' )', 100 );
		return;
	}
}

// Keyboard handler
function wppaOvlKeyboardHandler( e ) {

//	e.preventDefault();

	var keycode;
	var escapeKey;

	if ( e == null ) { // ie
		keycode = event.keyCode;
		escapeKey = 27;
	} else { // mozilla
		keycode = e.keyCode;
		escapeKey = 27; //e.DOM_VK_ESCAPE;
	}

	var key = String.fromCharCode( keycode ).toLowerCase();

	switch ( keycode ) {
		case escapeKey:
			wppaStopVideo( mocc );
			if ( wppaOvlMode != 'normal' ) {
				wppaOvlNorm( true );
			}
			wppaOvlHide();
			break;
		case 37:
			wppaOvlShowPrev();
			break;
		case 39:
			wppaOvlShowNext();
			break;
	}

	switch ( key ) {
		case 'p':
			wppaOvlShowPrev();
			break;
		case 'n':
			wppaOvlShowNext();
			break;
		case 's':
			wppaOvlStartStop();
			break;
		case 'd':
			jQuery( '#wppa-ovl-legenda-1' ).css( 'visibility', 'hidden' );
			jQuery( '#wppa-ovl-legenda-2' ).css( 'visibility', 'hidden' );
			wppaShowLegenda = 'hidden';
			break;
		case 'f':
			wppaOvlFull();
			break;
		case 'l':
			wppaOvlNorm();
			break;
		case 'q':
		case 'x':
			wppaStopVideo( mocc );
			if ( wppaOvlMode != 'normal' ) {
				wppaOvlNorm( true );
			}
			wppaOvlHide();
			break;

	}

	return false;
}

// Switch to fullscreen mode
function wppaOvlFull( init ) {
wppaConsoleLog( 'wppaOvlFull' );

	// Init
	wppaNormsBtnOpac = 0.75;
	var oldMode = wppaOvlMode;
	if ( ! init ) {
		wppaOvlStepMode();
	}
	var elem = document.getElementById( 'wppa-overlay-ic' );
	if ( ! elem ) return;

	// Got to fullscreen mode. This is browser dependant
	if ( init || oldMode == 'normal' ) {
		if ( elem.requestFullscreen ) {
			elem.requestFullscreen();
		} else if ( elem.mozRequestFullScreen ) {
			elem.mozRequestFullScreen();
		} else if ( elem.webkitRequestFullscreen ) {
			elem.webkitRequestFullscreen();
		}
		setTimeout( function(){wppaOvlShow( wppaOvlIdx )}, 500 );
	}

	// Cancel fullscreen. This is browser dependant
	if ( wppaOvlMode == 'normal' ) {
		if ( document.cancelFullScreen ) {
			document.cancelFullScreen();
		} else if ( document.mozCancelFullScreen ) {
			document.mozCancelFullScreen();
		} else if ( document.webkitCancelFullScreen ) {
			document.webkitCancelFullScreen();
		}
	}

	setTimeout( function(){wppaShowFsButtons(0.75)}, 300 );

	// Remove legenda
	jQuery( '#wppa-ovl-legenda-1' ).html( '' );
}

// Switch to normal screen mode
function wppaOvlNorm( exit ) {
wppaConsoleLog( 'wppaOvlNorm' );

	// Init
	wppaOvlMode = 'normal';
	wppaNormsBtnOpac = 0.75;

	// Cancel fullscreen. This is browser dependant
	if ( document.cancelFullScreen ) {
		document.cancelFullScreen();
	} else if ( document.mozCancelFullScreen ) {
		document.mozCancelFullScreen();
	} else if ( document.webkitCancelFullScreen ) {
		document.webkitCancelFullScreen();
	}

	// If exiting, re-init start mode
	if ( exit ) {
		wppaOvlMode = wppaOvlModeInitial;
		return;
	}

	setTimeout( function(){wppaShowFsButtons(0.75)}, 300 );

	setTimeout( function(){wppaOvlShow(wppaOvlIdx)}, 500 );
}

// Prepare the display of the lightbox overlay.
// arg is either numeric ( index to current lightbox set ) or
// 'this' for a single image or for the first of a set
function wppaOvlShow( arg ) {
wppaConsoleLog( 'wppaOvlShow arg=' + arg );

	// Do the setup right after the invocation of the lightbox
	if ( wppaOvlFirst ) {

		// Prevent Weaver ii from hiding us
		jQuery( '#weaver-final' ).removeClass( 'wvr-hide-bang' );

		// Init background
		jQuery( '#wppa-overlay-bg' ).stop().fadeTo( 3, wppaOvlOpacity );

		// Install keyboard handler
		if ( ! wppaKbHandlerInstalled ) {
			jQuery( document ).on( 'keydown', wppaOvlKeyboardHandler );
			wppaKbHandlerInstalled = true;
		}

		// Adjust background size
		jQuery( '#wppa-overlay-bg' ).css({
											width:window.innerWidth,
											height:window.innerHeight,
										});

		// Jump to fullscreen if initially wanted
		if ( wppaOvlModeInitial != 'normal' ) {
			wppaOvlFull( true );
		}
	}

	// If arg = 'this', setup the array of data
	if ( typeof( arg ) == 'object' ) {

		// Init the set
		wppaOvlUrls 				= [];
		wppaOvlTitles 				= [];
		wppaOvlAlts 				= [];
		wppaOvlVideoHtmls 			= [];
		wppaOvlAudioHtmls 			= [];
		wppaOvlVideoNaturalWidths 	= [];
		wppaOvlVideoNaturalHeights 	= [];
		wppaOvlImgs					= [];
		wppaOvlIdx 					= 0;

		// Do we use rel or data-rel?
		var rel;
		if ( arg.rel ) {
			rel = arg.rel;
		}
		else if ( jQuery( arg ).attr( 'data-rel' ) ) {
			rel = jQuery( arg ).attr( 'data-rel' );
		}
		else {
			rel = false;
		}

		// Are we in a set?
		var temp = rel.split( '[' );

		// We are in a set if temp[1] is defined
		if ( temp[1] ) {
			var setname = temp[1];
			var anchors = jQuery( 'a' );
			var anchor;
			var i, j = 0;

			// Save the set
			for ( i = 0; i < anchors.length; i++ ) {
				anchor = anchors[i];
				if ( jQuery( anchor ).attr( 'data-rel' ) ) {
					temp = jQuery( anchor ).attr( 'data-rel' ).split( "[" );
				}
				else {
					temp = false;
				}
				if ( temp.length > 1 ) {
					if ( temp[0] == 'wppa' && temp[1] == setname ) {	// Same set
						wppaOvlUrls[j] = anchor.href;
						if ( jQuery( anchor ).attr( 'data-lbtitle' ) ) {
							wppaOvlTitles[j] = wppaRepairScriptTags( jQuery( anchor ).attr( 'data-lbtitle' ) );
						}
						else {
							wppaOvlTitles[j] = wppaRepairScriptTags( anchor.title );
						}
						wppaOvlAlts[j] 					= jQuery( anchor ).attr( 'data-alt' ) ? jQuery( anchor ).attr( 'data-alt' ) : '';
						wppaOvlVideoHtmls[j] 			= jQuery( anchor ).attr( 'data-videohtml' ) ? decodeURI( jQuery( anchor ).attr( 'data-videohtml' ) ) : '';
						wppaOvlAudioHtmls[j] 			= jQuery( anchor ).attr( 'data-audiohtml' ) ? decodeURI( jQuery( anchor ).attr( 'data-audiohtml' ) ) : '';
						wppaOvlVideoNaturalWidths[j] 	= jQuery( anchor ).attr( 'data-videonatwidth' ) ? jQuery( anchor ).attr( 'data-videonatwidth' ) : '';
						wppaOvlVideoNaturalHeights[j] 	= jQuery( anchor ).attr( 'data-videonatheight' ) ? jQuery( anchor ).attr( 'data-videonatheight' ) : '';
						if ( anchor.href == arg.href ) {
							wppaOvlIdx = j;									// Current index
						}
						j++;
					}
				}
			}
		}

		// Single image, treat as set with one element
		else {
			wppaOvlUrls[0] = arg.href;
			if ( jQuery( arg ).attr( 'data-lbtitle' ) ) {
				wppaOvlTitles[0] = wppaRepairScriptTags( jQuery( arg ).attr( 'data-lbtitle' ) );
			}
			else {
				wppaOvlTitles[0] = wppaRepairScriptTags( arg.title );
			}
			wppaOvlAlts[0] 					= jQuery( arg ).attr( 'data-alt' ) ? jQuery( arg ).attr( 'data-alt' ) : '';
			wppaOvlVideoHtmls[0] 			= jQuery( arg ).attr( 'data-videohtml' ) ? decodeURI( jQuery( arg ).attr( 'data-videohtml' ) ) : '';
			wppaOvlAudioHtmls[0] 			= jQuery( arg ).attr( 'data-audiohtml' ) ? decodeURI( jQuery( arg ).attr( 'data-audiohtml' ) ) : '';
			wppaOvlVideoNaturalWidths[0] 	= jQuery( arg ).attr( 'data-videonatwidth' ) ? jQuery( arg ).attr( 'data-videonatwidth' ) : '';
			wppaOvlVideoNaturalHeights[0] 	= jQuery( arg ).attr( 'data-videonatheight' ) ? jQuery( arg ).attr( 'data-videonatheight' ) : '';
			wppaOvlIdx = 0;
		}
	}

	// Arg is numeric
	else {
		wppaOvlIdx = arg;
	}

	// Now start the actual function
	setTimeout( function(){ _wppaOvlShow( wppaOvlIdx )}, 100 );

}

// Show the lightbox overlay.
// idx is the numeric index to current lightbox set
function _wppaOvlShow( idx ) {
wppaConsoleLog( '_wppaOvlShow, idx='+idx );

	// Globalize index
	wppaOvlCurIdx = idx;

	// Show spinner
	if ( wppaOvlFirst ) {
		jQuery( "#wppa-ovl-spin" ).fadeIn( 1500 );
	}

	// Find handy switches
	wppaIsVideo 	= wppaOvlVideoHtmls[idx] != '';
	wppaHasAudio 	= wppaOvlAudioHtmls[idx] != '';

	// Preload current image
	// Not an empty url, and do not wait infinitely for a possibly non-existend posterimage
	if ( wppaOvlUrls[idx].length > 0 && ! wppaIsVideo ) {
		wppaOvlImgs[idx] 			= new Image();
		wppaOvlImgs[idx].src 		= wppaOvlUrls[idx];	// Preload
		wppaConsoleLog( 'Preloading ' + ( idx + 1 ) + '/' + wppaOvlUrls.length + ' (current)' );
		if ( ! wppaIsIe && ! wppaOvlImgs[idx].complete ) {
			wppaConsoleLog( 'Retrying preload current image' );
			setTimeout( '_wppaOvlShow(' + idx + ')', 100 );
			return;
		}
	}

	var next;
	var prev;

	// Preload next
	if ( wppaOvlIdx == ( wppaOvlUrls.length - 1 ) ) {
		next = 0;
	}
	else {
		next = wppaOvlIdx + 1;
	}
	// only if not video
	if ( wppaOvlVideoHtmls[next] == '' ) {
		wppaOvlImgs[next] 			= new Image();
		wppaOvlImgs[next].src 		= wppaOvlUrls[next];	// Preload
		wppaConsoleLog( 'Preloading > ' + ( next + 1 ) );
	}

	// Preload previous ( for hitting the prev button )
	// Only when in browsemode
	if ( ! wppaOvlRunning ) {
		if ( wppaOvlIdx == 0 ) {
			prev = wppaOvlUrls.length-1;
		}
		else {
			prev = wppaOvlIdx - 1;
		}
		// only if not video
		if ( wppaOvlVideoHtmls[prev] == '' ) {
			wppaOvlImgs[prev] 			= new Image();
			wppaOvlImgs[prev].src 		= wppaOvlUrls[prev];	// Preload
			wppaConsoleLog( 'Preloading < ' + ( prev + 1 ) );
		}
	}

	// Find photo id and bump its viewcount
	wppaPhotoId = wppaUrlToId( wppaOvlUrls[idx] );
	_bumpViewCount( wppaPhotoId );

	// A single image?
	wppaOvlIsSingle = ( wppaOvlUrls.length == 1  );

	// Fullsize?
	if ( wppaOvlMode != 'normal' ) {
		var html;

		// Fullsize Video
		if ( wppaIsVideo ) {
			html =
			'<div id="wppa-ovl-full-bg" style="position:fixed; width:'+jQuery( window ).width()+'px; height:'+jQuery( window ).height()+'px; left:0px; top:0px; text-align:center;" >'+
				'<video id="wppa-overlay-img" controls preload="metadata"' +
					( wppaOvlVideoStart ? ' autoplay' : '' ) +
					' ontouchstart="wppaTouchStart( event, \'wppa-overlay-img\', -1 );"' +
					' ontouchend="wppaTouchEnd( event );"' +
					' ontouchmove="wppaTouchMove( event );"' +
					' ontouchcancel="wppaTouchCancel( event );"' +
					' onpause="wppaOvlVideoPlaying = false;"' +
					' onplay="wppaOvlVideoPlaying = true;"' +
					' style="border:none; width:'+jQuery( window ).width()+'px; box-shadow:none; position:absolute;"' +
					' alt="'+wppaOvlAlts[idx]+'"' +
					' >'+
						wppaOvlVideoHtmls[idx]+
				'</video>'+
				'<div style="height: 20px; width: 100%; position:absolute; top:0; left:0;" onmouseover="jQuery(\'#wppa-ovl-legenda-2\').css(\'visibility\',\'visible\');" onmouseout="jQuery(\'#wppa-ovl-legenda-2\').css(\'visibility\',\'hidden\');wppaShowLegenda=\'hidden\';" >';
				if ( wppaOvlShowLegenda ) {
					html +=
					'<div id="wppa-ovl-legenda-2" style="position:fixed; left:0; top:0; background-color:'+(wppaOvlTheme == 'black' ? '#272727' : '#a7a7a7')+'; color:'+(wppaOvlTheme == 'black' ? '#a7a7a7' : '#272727')+'; visibility:'+wppaShowLegenda+';" >'+
						'Mode='+wppaOvlMode+'. '+( wppaOvlIsSingle ? wppaOvlFullLegendaSingle : wppaOvlFullLegenda ) +
					'</div>';
				}
				html +=
				'</div>';
			'</div>';
		}

		// Fullsize Photo
		else {
			html =
			'<div id="wppa-ovl-full-bg" style="position:fixed; width:'+jQuery( window ).width()+'px; height:'+jQuery( window ).height()+'px; left:0px; top:0px; text-align:center;" >'+
				'<img id="wppa-overlay-img"'+
					' ontouchstart="wppaTouchStart( event, \'wppa-overlay-img\', -1 );"'+
					' ontouchend="wppaTouchEnd( event );"'+
					' ontouchmove="wppaTouchMove( event );"'+
					' ontouchcancel="wppaTouchCancel( event );"'+
					' src="'+wppaOvlUrls[idx]+'"'+
					' style="border:none; width:'+jQuery( window ).width()+'px; visibility:hidden; box-shadow:none; position:absolute;"'+
					' alt="'+wppaOvlAlts[idx]+'"'+
				' />';
				if ( wppaHasAudio ) {
				html += '<audio' +
							' id="wppa-overlay-audio"' +
							' class="wppa-overlay-audio"' +
							' data-from="wppa"' +
							' preload="metadata"' +
							( ( wppaOvlAudioStart ) ? ' autoplay' : '' ) +
							' onpause="wppaOvlAudioPlaying = false;"' +
							' onplay="wppaOvlAudioPlaying = true;"' +
							' style="' +
								'width:100%;' +
								'position:absolute;' +
								'left:0px;' +
								'bottom:0px;' +
								'padding:0;' +
								'"' +
							' controls' +
							' >' +
							wppaOvlAudioHtmls[idx] +
						'</audio>';
				}
				html +=
				'<div style="height: 20px; width: 100%; position:absolute; top:0; left:0;" onmouseover="jQuery(\'#wppa-ovl-legenda-2\').css(\'visibility\',\'visible\');" onmouseout="jQuery(\'#wppa-ovl-legenda-2\').css(\'visibility\',\'hidden\');wppaShowLegenda=\'hidden\';" >';
				if ( wppaOvlShowLegenda ) {
					html +=
					'<div id="wppa-ovl-legenda-2" style="position:fixed; left:0; top:0; background-color:'+(wppaOvlTheme == 'black' ? '#272727' : '#a7a7a7')+'; color:'+(wppaOvlTheme == 'black' ? '#a7a7a7' : '#272727')+'; visibility:'+wppaShowLegenda+';" >'+
						'Mode='+wppaOvlMode+'. '+( wppaOvlIsSingle ? wppaOvlFullLegendaSingle : wppaOvlFullLegenda )+
					'</div>';
				}
				html +=
				'</div>';
			'</div>';
		}

		// The 'exit' icon
		var dark = wppaIsMobile ? '0.1' : '0.1';
		html += '<div' +
					' id="wppa-exit-btn"' +
					' style="height:'+wppaOvlFsExitBtnSize+'px;z-index:100098;position:fixed;top:0;right:0;opacity:' + wppaNormsBtnOpac + ';"' +
					' onclick="wppaOvlHide()"' +
					' onmouseover="jQuery(this).stop().fadeTo(300,1);"' +
					' ontouchstart="jQuery(this).stop().fadeTo(300,1);"' +
					' onmouseout="jQuery(this).stop().fadeTo(300,' + dark + ');wppaNormsBtnOpac=' + dark + ';"' +
					' ontouchend="jQuery(this).stop().fadeTo(300,' + dark + ');wppaNormsBtnOpac=' + dark + ';"' +
					' >' +
					wppaSvgHtml( 'Exit', wppaOvlFsExitBtnSize+'px', true, true, '0', '0', '0', '0' ) +
				'</div>';


		// The 'back to normal' icon
		html += '<div' +
					' id="wppa-norms-btn"' +
					' style="height:48px;z-index:100098;position:fixed;top:0;right:' + wppaOvlFsExitBtnSize + 'px;opacity:' + wppaNormsBtnOpac + ';"' +
					' onclick="wppaOvlNorm()"' +
					' onmouseover="jQuery(this).stop().fadeTo(300,1);"' +
					' ontouchstart="jQuery(this).stop().fadeTo(300,1);"' +
					' onmouseout="jQuery(this).stop().fadeTo(300,' + dark + ');wppaNormsBtnOpac=' + dark + ';"' +
					' ontouchend="jQuery(this).stop().fadeTo(300,' + dark + ');wppaNormsBtnOpac=' + dark + ';"' +
					' >' +
					wppaSvgHtml( 'Exit-Full-Screen', wppaOvlFsExitBtnSize+'px', true, true, '0', '0', '0', '0' ) +
				'</div>';

		// Replacing the html stops a running video,
		// so we only replace html on a new id, or a photo without audio
		if ( ( ! wppaIsVideo && ! wppaHasAudio ) || wppaOvlFsPhotoId != wppaPhotoId || wppaPhotoId == 0 ) {
			wppaStopVideo( 0 );
			wppaStopAudio();
			jQuery( '#wppa-overlay-ic' ).html( html );
		}

		// Disable right mouse button
		jQuery( '#wppa-overlay-img' ).bind( 'contextmenu', function(e) {
			return false;
		});

		// Replace svg img src to html
//		wppaReplaceSvg();

		wppaOvlIsVideo = wppaIsVideo;
		setTimeout( 'wppaOvlFormatFull()', 10 );
		if ( wppaIsVideo || wppaHasAudio ) {
			setTimeout( 'wppaOvlUpdateFsId()', 2000 );
		}
		else {
			wppaOvlFsPhotoId = 0;
		}
		wppaOvlFirst = false;
		
		// Record we are in
		wppaOvlOpen = true;
		
		return false;
	}

	// NOT fullsize
	else {
		// Initialize
		wppaOvlFsPhotoId = 0; // Reset ovl fullscreen photo id
		wppaPhotoId = 0;
		wppaStopVideo( 0 );
		var txtcol = wppaOvlTheme == 'black' ? '#a7a7a7' : '#272727';	// Normal font
		if ( wppaOvlFontColor ) {
			txtcol = wppaOvlFontColor;
		}
		var showNav = wppaOvlUrls.length > 1;

		// Initial sizing of image container ( contains image, borders and subtext )
		jQuery( '#wppa-overlay-ic' ).css( {
											width:wppaSavedContainerWidth,
											marginLeft:wppaSavedMarginLeft,
											marginTop:wppaSavedMarginTop,
										});

		// Make the html
		var html = '';

		// The img sub image container
		html += '<div id="img-sb-img-cont" style="position:relative;line-height:0;" >';

			// Not Fullsize Video
			if ( wppaIsVideo ) {

				html += '<video' +
							' id="wppa-overlay-img"' +
							' onmouseover="jQuery(\'.wppa-ovl-nav-btn\').stop().fadeTo(200,0.8);"' +
							' onmouseout="jQuery(\'.wppa-ovl-nav-btn\').stop().fadeTo(200,0);"' +
							' preload="metadata"' +
							( wppaOvlVideoStart ? ' autoplay' : '' ) +
							' onpause="wppaOvlVideoPlaying = false;"' +
							' onplay="wppaOvlVideoPlaying = true;"' +
							' ontouchstart="wppaTouchStart( event, \'wppa-overlay-img\', -1 );"' +
							' ontouchend="wppaTouchEnd( event );"' +
							' ontouchmove="wppaTouchMove( event );"' +
							' ontouchcancel="wppaTouchCancel( event );" ' +
							' controls' +
							' style="' +
								'border-width:' + wppaOvlBorderWidth + 'px ' + wppaOvlBorderWidth + 'px 0;' +
								'border-style:solid;' +
								'border-color:'+wppaOvlTheme+';' +
								'width:' + wppaSavedImageWidth + 'px;' +
								'height:' + wppaSavedImageHeight + 'px;' +
								'box-shadow:none;' +
								'box-sizing:content-box;' +
								'position:relative;' +
								'border-top-left-radius:'+wppaOvlRadius+'px;' +
								'border-top-right-radius:'+wppaOvlRadius+'px;' +
								'margin:0;' +
								'padding:0;' +
							'"' +
							' alt="'+wppaOvlAlts[idx]+'"' +
							' >' +
							wppaOvlVideoHtmls[idx] +
						'</video>';

				wppaOvlIsVideo = true;
			}

			// Not fullsize photo
			else {
				html += '<img' +
							' id="wppa-overlay-img"'+
							' onmouseover="jQuery(\'.wppa-ovl-nav-btn\').stop().fadeTo(200,0.8);"' +
							' onmouseout="jQuery(\'.wppa-ovl-nav-btn\').stop().fadeTo(200,0);"' +
							' ontouchstart="wppaTouchStart( event, \'wppa-overlay-img\', -1 );"' +
							' ontouchend="wppaTouchEnd( event );"' +
							' ontouchmove="wppaTouchMove( event );"' +
							' ontouchcancel="wppaTouchCancel( event );"' +
							' src="'+wppaOvlUrls[idx]+'"' +
							' style="' +
								'border-width:' + wppaOvlBorderWidth + 'px ' + wppaOvlBorderWidth + 'px 0;' +
								'border-style:solid;' +
								'border-color:'+wppaOvlTheme+';' +
								'width:' + wppaSavedImageWidth + 'px;' +
								'height:' + wppaSavedImageHeight + 'px;' +
								'box-shadow:none;' +
								'box-sizing:content-box;' +
								'position:relative;' +
								'border-top-left-radius:'+wppaOvlRadius+'px;' +
								'border-top-right-radius:'+wppaOvlRadius+'px;' +
								'margin:0;' +
								'padding:0;' +
								'"' +
							' alt="'+wppaOvlAlts[idx]+'"' +
						' />';

				// Audio on not fullsize
				if ( wppaHasAudio ) {
					html += '<audio' +
								' id="wppa-overlay-audio"' +
								' class="wppa-overlay-audio"' +
								' data-from="wppa"' +
								' preload="metadata"' +
								' onpause="wppaOvlAudioPlaying = false;"' +
								' onplay="wppaOvlAudioPlaying = true;"' +
								' style="' +
									'width:100%;' +
									'position:absolute;' +
									'box-shadow:none;' +
									'left:0;' +
									'bottom:0;' +
									'padding:0 ' + wppaOvlBorderWidth + 'px;' +
									'margin:0;' +
									'background-color:transparent;' +
									'box-sizing:border-box;' +
									'"' +
								' controls' +
								' >' +
								wppaOvlAudioHtmls[idx] +
							'</audio>';
				}
				wppaOvlIsVideo = false;
			}

			// The start/stop button
			// Only if in a set
			// Not on a video to avoid confusion with the start video button
			if ( wppaOvlShowStartStop && ! wppaOvlIsSingle && ! wppaIsVideo ) {
				html += '<div' +
							' id="wppa-ovl-start-stop-btn"' +
							' class="wppa-ovl-nav-btn"' +
							' style="' +
								'z-index:100101;' +
								'position:absolute;' +
								'top:50%;' +
								'margin-top:-24px;' +
								'left:50%;' +
								'margin-left:-24px;' +
								( wppaOvlIdx == -1 ? 'visibility:hidden;' : '' ) +
								'box-shadow:none;' +
								( wppaOvlFirst ? 'opacity:1;' : 'opacity:0;' ) +
								'"' +
							' onclick="wppaOvlStartStop()"' +
							' onmouseover="jQuery(this).stop().fadeTo(200,1);"' +
							' onmouseout="jQuery(this).stop().fadeTo(200,0);"' +
							' ontouchstart="jQuery(this).stop().fadeTo(200,1);"' +
							' onload="jQuery(this).stop().fadeTo(5000,0);"' +
						' >' +
						wppaSvgHtml( ( wppaOvlRunning ? 'Pause-Button' : 'Play-Button' ), '48px', true, true, '0', '20', '50', '50' ) +
						'</div>';
			}

			// Show browse buttons only if we are in a set
			if ( ! wppaOvlIsSingle ) {

				// The prev button
				html += '<div' +
							' id="wppa-ovl-prev-btn"' +
							' class="wppa-ovl-nav-btn"' +
							' style="' +
								'position:absolute;' +
								'z-index:100101;' +
								'width:48px;' +
								'top:50%;' +
								'margin-top:-24px;' +
								'left:1px;' +
								'box-shadow:none;' +
								( wppaOvlFirst ? 'opacity:1;' : 'opacity:0;' ) +
								'"' +
							' onclick="wppaOvlShowPrev()"' +
							' onmouseover="jQuery(this).stop().fadeTo(200,1);"' +
							' onmouseout="jQuery(this).stop().fadeTo(200,0);"' +
							' ontouchstart="jQuery(this).stop().fadeTo(200,1);"' +
							' onload="jQuery(this).stop().fadeTo(5000,0);"' +
							' >' +
							wppaSvgHtml( 'Prev-Button', '48px', true, true ) +
						'</div>';

				// The next button
				html +=	'<div' +
							' id="wppa-ovl-next-btn"' +
							' class="wppa-ovl-nav-btn"' +
							' style="' +
								'position:absolute;' +
								'z-index:100101;' +
								'width:48px;' +
								'top:50%;' +
								'margin-top:-24px;' +
								'right:1px;' +
								'box-shadow:none;' +
								( wppaOvlFirst ? 'opacity:1;' : 'opacity:0;' ) +
								'"' +
							' onclick="wppaOvlShowNext()"' +
							' onmouseover="jQuery(this).stop().fadeTo(200,1);"' +
							' onmouseout="jQuery(this).stop().fadeTo(200,0);"' +
							' ontouchstart="jQuery(this).stop().fadeTo(200,1);"' +
							' onload="jQuery(this).stop().fadeTo(5000,0);"' +
							' >' +
							wppaSvgHtml( 'Next-Button', '48px', true, true ) +
						'</div>';
			}

		// Close the #img-sb-img-cont
		html += '</div>';

		// The subtext container
		var showCounter = ! wppaOvlIsSingle && wppaOvlShowCounter;
		html += '<div id="wppa-overlay-txt-container"' +
					' style="' +
						'position:relative;' +
						'padding:10px;' +
						'background-color:' + wppaOvlTheme + ';' +
						'color:' + txtcol + ';' +
						'text-align:center;' +
						'font-family:' + wppaOvlFontFamily + ';' +
						'font-size:' + wppaOvlFontSize + 'px;' +
						'font-weight:' + wppaOvlFontWeight + ';' +
						'line-height:' + wppaOvlLineHeight + 'px;' +
						'box-shadow:none;' +
						'border-bottom-left-radius:'+wppaOvlRadius+'px;' +
						'border-bottom-right-radius:'+wppaOvlRadius+'px;' +
						'"' +
					' >' +
					'<div' +
						' id="wppa-overlay-txt"' +
						' style="' +
							'text-align:center;' +
							'min-height:36px;' +
							'width:100%;' +
							( wppaOvlTxtHeight == 'auto' ? 'max-height:200px;' : 'max-height:' + wppaOvlTxtHeight + 'px;' ) +
							'overflow:auto;' +
							'box-shadow:none;' +
							'"' +
						' >' +
						( showCounter ? ( wppaOvlIdx + 1 ) + '/' + wppaOvlUrls.length + '<br />' : '' ) +
						wppaOvlTitles[idx] +
					'</div>';
				'</div>';
//alert(html);
		// Insert the html
		jQuery( '#wppa-overlay-ic' ).html( html );

		// Replace svg img src to html
//		wppaReplaceSvg();

		// Restore opacity of fs and exit buttons
//		wppaShowFsButtons();

		// Disable right mouse button
		jQuery( '#wppa-overlay-img' ).bind( 'contextmenu', function(e) {
			return false;
		});

		// Size
		wppaOvlResize();

		// Show fs and exit buttons
		if ( wppaOvlFirst ) {
			wppaShowFsButtons();
		}
		
		// Record we are in
		wppaOvlOpen = true;

		// Done!
		return false;
	}
}

// Adjust display sizes
function wppaOvlSize( speed ) {
wppaConsoleLog( 'wppaOvlSize' );

	var img = document.getElementById( 'wppa-overlay-img' );	// Do NOT jquerify this:
	var txt = document.getElementById( 'wppa-overlay-txt' ); 	// jQuery does not support .naturalHeight etc.

	// Are we still visible?
	if ( ! img || ! txt || jQuery('#wppa-overlay-bg').css('display') == 'none' ) {
		wppaConsoleLog( 'Lb quitted' );
		return;
	}

	// Full screen?
	if ( wppaOvlMode != 'normal' ) {
		wppaOvlFormatFull();
		return;
	}


	var iw 	= jQuery( window ).width();
	var ih 	= jQuery( window ).height();
	var cw, nw, nh;

	if ( wppaOvlIsVideo ) {
		cw = img.clientWidth;//640;
		nw = wppaOvlVideoNaturalWidths[wppaOvlCurIdx];//640;
		nh = wppaOvlVideoNaturalHeights[wppaOvlCurIdx];//480;
	}
	else {
		cw = img.clientWidth;
		nw = img.naturalWidth;
		nh = img.naturalHeight;
	}

	var fakt1;
	var fakt2;
	var fakt;

	// If the width is the limiting factor, adjust the height
	if ( typeof( nw ) == 'undefined' ) {	// ver 4 browser
		nw = img.clientWidth;
		nh = img.clientHeight;
	}
	fakt1 = ( iw - 3 * wppaOvlBorderWidth ) / nw;
	fakt2 = ih / nh;
	if ( fakt1 < fakt2 ) fakt = fakt1;	// very landscape, width is the limit
	else fakt = fakt2;				// Height is the limit
	if ( fakt < 1.0 ) {					// Up or downsize
		nw = parseInt( nw * fakt );
		nh = parseInt( nh * fakt );
	}


	var mh;	// max image height
	var tch = jQuery( '#wppa-overlay-txt' ).height();

	if ( wppaOvlTxtHeight == 'auto' ) {
		if ( tch == 0 ) tch = 20 + 2 * wppaOvlBorderWidth;
		mh = ih - tch - 20 - 2 * wppaOvlBorderWidth;
	}
	else {
		mh = ih - wppaOvlTxtHeight - 20 - 2 * wppaOvlBorderWidth;
	}

	var mw = parseInt( mh * nw / nh );
	var pt = wppaOvlPadTop;
	var lft = parseInt( ( iw-mw )/2 );
	var wid = mw;

	// Image too small?	( never for ver 4 browsers, we do not know the natural dimensions
	if ( nh < mh ) {
		pt = wppaOvlPadTop + ( mh - nh )/2;
		lft = parseInt( ( iw-nw )/2 );
		wid = nw;
	}

	// Save new image width and height
	var done = ( wppaSavedImageWidth - wid < 3 && wid - wppaSavedImageWidth < 3 );

	if ( wid <= 10 ) {
		wid = 240;
		nh = 180;
		nw = 240;
		done = false;
	}

	wid = parseInt(wid);

	wppaSavedImageWidth 		= parseInt( wid );
	wppaSavedImageHeight 		= parseInt( wid * nh / nw );
	wppaSavedMarginLeft 		= - parseInt( ( wid / 2 + wppaOvlBorderWidth ) );
	wppaSavedContainerWidth 	= parseInt( wid + 2 * wppaOvlBorderWidth );
	wppaSavedContainerHeight 	= parseInt( wppaSavedImageHeight + wppaOvlBorderWidth + jQuery( '#wppa-overlay-txt-container' ).height() + 20 ); // padding = 10
	wppaSavedMarginTop 			= - parseInt( wppaSavedContainerHeight / 2 );

	// Go to final size
	jQuery( '#wppa-overlay-img' ).animate( 	{
												width:wppaSavedImageWidth,
												height:wppaSavedImageHeight,
											},
											speed
										);

	jQuery( '#wppa-overlay-ic' ).animate( 	{
												width:wppaSavedContainerWidth,
												marginLeft:wppaSavedMarginLeft,
												marginTop:wppaSavedMarginTop,
											},
											speed
										);


	// Done?
	if ( ! done ) {
		setTimeout( function(){ wppaOvlSize(wppaOvlAnimSpeed) }, speed + 100 );
		wppaConsoleLog( 'Not done '+wppaOvlIdx+' saved='+wppaSavedImageWidth+', wid='+wid+', cw='+cw+', nw='+nw+
							', img complete='+document.getElementById( 'wppa-overlay-img' ).complete );
	}
	else {

		// Remove spinner
		jQuery( '#wppa-ovl-spin' ).stop().fadeOut();
		wppaConsoleLog( 'Done '+wppaOvlIdx );
		wppaOvlFirst = false;
	}
	return true;
}

// Show fullscreen lightbox image
function wppaOvlFormatFull() {
wppaConsoleLog( 'wppaOvlFormatFull '+wppaOvlMode );

	var img;
	var natWidth;
	var natHeight;

	// Find the natural image sizes
	if ( wppaOvlIsVideo ) {
		img 		= document.getElementById( 'wppa-overlay-img' );
		natWidth 	= wppaOvlVideoNaturalWidths[wppaOvlIdx];
		natHeight 	= wppaOvlVideoNaturalHeights[wppaOvlIdx];
	}
	else {
		img 		= document.getElementById( 'wppa-overlay-img' );
		if ( ! wppaIsIe && ( ! img || ! img.complete ) ) {

			// Wait for load complete
			setTimeout( 'wppaOvlFormatFull()', 100 );
			return;
		}
		natWidth 	= img.naturalWidth;
	 	natHeight 	= img.naturalHeight;
	}

	var screenRatio = jQuery( window ).width() / jQuery( window ).height();
	var imageRatio 	= natWidth / natHeight;
	var margLeft 	= 0;
	var margTop 	= 0;
	var imgHeight 	= 0;
	var imgWidth 	= 0;
	var scrollTop 	= 0;
	var scrollLeft 	= 0;
	var Overflow 	= 'hidden';

	switch ( wppaOvlMode ) {
		case 'padded':
			if ( screenRatio > imageRatio ) {	// Picture is more portrait
				margLeft 	= ( jQuery( window ).width() - jQuery( window ).height() * imageRatio ) / 2;
				margTop 	= 0;
				imgHeight 	= jQuery( window ).height();
				imgWidth 	= jQuery( window ).height() * imageRatio;
			}
			else {
				margLeft 	= 0;
				margTop 	= ( jQuery( window ).height() - jQuery( window ).width() / imageRatio ) / 2;
				imgHeight 	= jQuery( window ).width() / imageRatio;
				imgWidth 	= jQuery( window ).width();
			}
			break;
		case 'stretched':
			margLeft 	= 0;
			margTop 	= 0;
			imgHeight 	= jQuery( window ).height();
			imgWidth 	= jQuery( window ).width();
			break;
		case 'clipped':
			if ( screenRatio > imageRatio ) {	// Picture is more portrait
				margLeft 	= 0;
				margTop 	= ( jQuery( window ).height() - jQuery( window ).width() / imageRatio ) / 2;
				imgHeight 	= jQuery( window ).width() / imageRatio;
				imgWidth 	= jQuery( window ).width();
			}
			else {
				margLeft 	= ( jQuery( window ).width() - jQuery( window ).height() * imageRatio ) / 2;
				margTop 	= 0;
				imgHeight 	= jQuery( window ).height();
				imgWidth 	= jQuery( window ).height() * imageRatio;
			}
			break;
		case 'realsize':
			margLeft 	= ( jQuery( window ).width() - natWidth ) / 2;
			if ( margLeft < 0 ) {
				scrollLeft 	= parseInt( - margLeft );
				margLeft 	= 0;
			}
			margTop 	= ( jQuery( window ).height() - natHeight ) / 2;
			if ( margTop < 0 ) {
				scrollTop 	= parseInt( - margTop );
				margTop 	= 0;
			}
			imgHeight 	= natHeight;
			imgWidth 	= natWidth;
			Overflow 	= 'auto';
			break;
	}
	margLeft 	= parseInt( margLeft );
	margTop 	= parseInt( margTop );
	imgHeight 	= parseInt( imgHeight );
	imgWidth 	= parseInt( imgWidth );

	jQuery(img).css({height:imgHeight,width:imgWidth,marginLeft:margLeft,marginTop:margTop,left:0,top:0,maxWidth:10000});
	jQuery(img).css({visibility:'visible'});
	jQuery( '#wppa-ovl-full-bg' ).css({overflow:Overflow});
	jQuery( '#wppa-ovl-full-bg' ).scrollTop( scrollTop );
	jQuery( '#wppa-ovl-full-bg' ).scrollLeft( scrollLeft );
	jQuery( '#wppa-ovl-spin' ).stop().fadeOut();

	return true;	// Done!
}

// This function is called after a timeout to update fullsize photo id.
// Used to determine if a video/audio must restart
function wppaOvlUpdateFsId() {
wppaConsoleLog( 'wppaOvlUpdateFsId' );

	wppaOvlFsPhotoId = wppaPhotoId;
}

// Start audio on the lightbox view
function wppaOvlStartAudio() {
wppaConsoleLog( 'wppaOvlStartAudio' );

	// Due to a bug in jQuery ( jQuery.play() does not exist ), must do myself:
	var elm = document.getElementById( 'wppa-overlay-audio' );
	if ( elm ) {
		if ( typeof( elm.play ) == 'function' ) {
			elm.play();
			wppaConsoleLog('Audio play '+'wppa-overlay-audio');
		}
	}
}

// Step through the ring of fullscreen modes
function wppaOvlStepMode() {
wppaConsoleLog('wppaOvlStepMode from '+wppaOvlMode);

	var modes = new Array( 'normal', 'padded', 'stretched', 'clipped', 'realsize', 'padded' );
	var i = 0;
	while ( i < modes.length ) {
		if ( wppaOvlMode == modes[i] ) {
			wppaOvlMode = modes[i+1];
			wppaOvlShow( wppaOvlIdx );
			return;
		}
		i++;
	}
}

// Start / stop lightbox slideshow
function wppaOvlStartStop() {
wppaConsoleLog('wppaOvlStartStop called. Running='+wppaOvlRunning);

	// Running?
	if ( wppaOvlRunning ) {

		// Stop it
		wppaOvlRunning = false;

		// Swap button image
		jQuery( '#wppa-ovl-start-stop-btn' ).html( wppaSvgHtml( ( wppaOvlRunning ? 'Pause-Button' : 'Play-Button' ), '48px', true, true, '0', '20', '50', '50' ) );

		// If in a set: Determine visibility of browse buttons and make visible if appliccable
		if ( wppaOvlIdx != -1 ) {

			// NOT first, show prev button
			if ( wppaOvlIdx != 0 ) {
				jQuery( '#wppa-ovl-prev-btn' ).css('visibility', 'visible');
			}

			// NOT last, show next button
			if ( wppaOvlIdx != ( wppaOvlUrls.length-1 ) ) {
				jQuery( '#wppa-ovl-next-btn' ).css('visibility', 'visible');
			}
		}
	}

	// Not running
	else {

		// Swap button image
		jQuery( '#wppa-ovl-start-stop-btn' ).html( wppaSvgHtml( ( wppaOvlRunning ? 'Pause-Button' : 'Pause-Button' ), '48px', true, true, '0', '20', '50', '50' ) );

		// Start it
		wppaOvlRunning = true;
		wppaOvlRun();
	}

//	wppaReplaceSvg();
}

// Start lb slideshow
function wppaOvlRun() {
wppaConsoleLog( 'wppaOvlRun, running='+wppaOvlRunning );

	// Already running?
	if ( ! wppaOvlRunning ) return;

	// Wait until playing audio or video ends
	if ( wppaOvlVideoPlaying || wppaOvlAudioPlaying ) {
		setTimeout( 'wppaOvlRun()', 500 );
		return;
	}

	// If the current image is not yet complete, try again after 500 ms
	if ( ! wppaIsVideo ) {
		var elm = document.getElementById( 'wppa-overlay-img' );
		if ( elm ) {
			if ( ! wppaIsIe && ! elm.complete ) {
				wppaConsoleLog( 'Wait during run' );
				setTimeout( 'wppaOvlRun()', 500 );
				return;
			}
		}
	}


	var next;
	if ( wppaOvlIdx >= ( wppaOvlUrls.length-1 ) ) next = 0;
	else next = wppaOvlIdx + 1;

	wppaOvlFsPhotoId = 0;
	wppaPhotoId = 0;

	wppaOvlShow( next );

	setTimeout( 'wppaOvlRun()', wppaOvlSlideSpeed );
}

// One back in the set
function wppaOvlShowPrev() {
wppaConsoleLog( 'wppaOvlShowPrev' );

	wppaOvlFsPhotoId = 0;
	wppaPhotoId = 0;

	if ( wppaOvlIsSingle ) return false;
	if ( wppaOvlIdx < 1 ) {
		wppaOvlIdx = wppaOvlUrls.length;	// Restart at last
	}
	wppaOvlShow( wppaOvlIdx-1 );
	return false;
}

// One further in the set
function wppaOvlShowNext() {
wppaConsoleLog( 'wppaOvlShowNext' );

	wppaOvlFsPhotoId = 0;
	wppaPhotoId = 0;

	if ( wppaOvlIsSingle ) return false;
	if ( wppaOvlIdx >= ( wppaOvlUrls.length-1 ) ) {
		wppaOvlIdx = -1;	// Restart at first
	}
	wppaOvlShow( wppaOvlIdx+1 );
	return false;
}

// Quit lightbox mode
function wppaOvlHide() {
wppaConsoleLog( 'wppaOvlHide' );

	// Stop audio
	wppaStopAudio();

	// Give up fullscreen mode
	if ( wppaOvlMode != 'normal' ) {
		wppaOvlNorm( true );
	}

	// Clear image container
	jQuery( '#wppa-overlay-ic' ).html( '' );

	// Remove background
	jQuery( '#wppa-overlay-bg' ).fadeOut( 300 );
	
	// Remove kb handler
	jQuery( document ).off( 'keydown', wppaOvlKeyboardHandler );
	wppaKbHandlerInstalled = false;

	// Reset switches
	wppaOvlFirst = true;
	wppaOvlRunning = false;
	wppaOvlMode = wppaOvlModeInitial;
	wppaNormsBtnOpac = 0.75;
	jQuery( '#wppa-ovl-spin' ).stop().fadeOut();

	// Remove fs and exit buttons
	jQuery( '#wppa-fulls-btn' ).stop().fadeOut( 300 );
	jQuery( '#wppa-exit-btn' ).stop().fadeOut( 300 );

	// Record we are out
	wppaOvlOpen = false;
}

// Perform onclick action
function wppaOvlOnclick( event ) {
wppaConsoleLog( 'wppaOvlOnClick' );

	switch ( wppaOvlOnclickType ) {
		case 'none':
			break;
		case 'close':
			if ( wppaOvlMode == 'normal' ) {
				wppaOvlHide();
			}
			break;
		case 'browse':
			var x = event.screenX - window.screenX;
			var y = event.clientY;
			if ( y > 48 ) {
				if ( x < jQuery( window ).width() / 2 ) wppaOvlShowPrev();
				else wppaOvlShowNext();
			}
			break;
		default:
			alert( 'Unimplemented action: '+wppaOvlOnclickType );
			break;
	}
	return true;
}

// Initialize <a> tags with onclick and ontouchstart events to lightbox
function wppaInitOverlay() {
wppaConsoleLog( 'wppaInitOverlay' );

	if ( wppaOvlMode == '' ) {
		wppaOvlMode = wppaOvlModeInitial;
	}

	var anchors = jQuery( 'a' );
	var anchor;
	var i;
	var temp = [];

	wppaOvlFsPhotoId = 0; // Reset ovl fullscreen photo id
	wppaPhotoId = 0;
	wppaOvlCurIdx = 0;

	// First time ?
	if ( wppaSavedContainerWidth == 0 ) {
		wppaSavedContainerWidth = 240 + 2 * wppaOvlBorderWidth;
		wppaSavedContainerHeight = 180 + 3 * wppaOvlBorderWidth + 20 + ( wppaOvlTxtHeight == 'auto' ? 50 : wppaOvlTxtHeight );
		wppaSavedMarginLeft = - ( 120 + wppaOvlBorderWidth );
		wppaSavedMarginTop = - ( 90 + wppaOvlBorderWidth + 10 + ( wppaOvlTxtHeight == 'auto' ? 25 : wppaOvlTxtHeight / 2 ) );
		wppaSavedImageWidth = 240;
		wppaSavedImageHeight = 180 + wppaOvlBorderWidth;
	}

	for ( i = 0; i < anchors.length; i++ ) {

		anchor = anchors[i];
		if ( jQuery( anchor ).attr( 'data-rel' ) ) {
			temp = jQuery( anchor ).attr( 'data-rel' ).split( "[" );
		}
		else if ( anchor.rel ) {
			temp = anchor.rel.split( "[" );
		}
		else {
			temp[0] = '';
		}

		if ( temp[0] == 'wppa' ) {

			// found one
			wppaWppaOverlayActivated = true;

			// Install handler
			if ( wppaIsMobile ) {

				// Install ontouch handlers
				jQuery( anchor ).on( 'touchstart', function( event ) {	// tap

					wppaStartTime();

				});
				jQuery( anchor ).on( 'touchend', function( event ) {	// tap

					if ( wppaInTime() ) {
						wppaOvlShow( this );
					}
					event.preventDefault();
				});

			}
			else {

				// Install onclick handler
				jQuery( anchor ).on( 'click', function( event ) {
					wppaOvlShow( this );
					event.preventDefault();
				});
			}

			// Install orientationchange handler if mobile
			if ( wppaIsMobile ) {
				window.addEventListener( 'orientationchange', wppaDoOnOrientationChange);
			}
		}
	}
}

// This module is intented to be used in any onclick definition that opens or closes a part of the photo description.
// this will automaticly adjust the picturesize so that the full description will be visible.
// Example: <a onclick="myproc()" >Show Details</a>
// Change to: <a onclick="myproc(); wppaOvlResize()" >Show Details</a>
// Isn't it simple?
function wppaOvlResize() {
wppaConsoleLog( 'wppaOvlResize' );

	// After resizing, the number of lines may have changed
	setTimeout( 'wppaOvlSize( '+wppaOvlAnimSpeed+' )', 100 );

	if ( wppaOvlAudioStart && ! wppaOvlAudioPlaying ) {
		setTimeout( 'wppaOvlStartAudio()', 1000 );
	}
}

// (re)-Display the fs and exit buttons at the current opacity
function wppaShowFsButtons(opac) {

	if ( typeof(opac) != 'undefined' ) {
		wppaNormsBtnOpac = opac;
	}
	jQuery( '#wppa-exit-btn' ).stop().fadeTo( 3, wppaNormsBtnOpac );
	if ( wppaOvlMode == 'normal' ) {
		jQuery( '#wppa-fulls-btn' ).stop().fadeTo( 3, wppaNormsBtnOpac );
	}
	else {
		jQuery( '#wppa-norms-btn' ).stop().fadeTo( 3, wppaNormsBtnOpac );
	}
}

wppaConsoleLog( 'wppa-lightbox.js version '+wppaLightboxVersion+' loaded.', 'force' );