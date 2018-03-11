// wppa-ajax-front.js
//
// Contains frontend ajax modules
// Dependancies: wppa.js and default wp jQuery library
//
var wppaJsAjaxVersion = '6.7.07';

var wppaRenderAdd = false;
var wppaWaitForCounter = 0;

// The new AJAX rendering routine Async
function wppaDoAjaxRender( mocc, ajaxurl, newurl, add, waitfor, addHilite ) {

	if ( parseInt(waitfor) > 0 && waitfor != wppaWaitForCounter ) {
		setTimeout( 'wppaDoAjaxRender( '+mocc+', \''+ajaxurl+'\', \''+newurl+'\', \''+add+'\', '+waitfor+' )', 100 );
		return;
	}

	wppaRenderAdd = add;

	// Fix the url
	if ( wppaLang != '' ) ajaxurl += '&lang='+wppaLang;
	if ( wppaAutoColumnWidth[mocc] ) ajaxurl += '&resp=1';
	if ( addHilite && _wppaCurIdx[mocc] && _wppaId[mocc][_wppaCurIdx[mocc]] ) ajaxurl += '&wppa-hilite=' + _wppaId[mocc][_wppaCurIdx[mocc]];

	// Ajax possible, or no newurl defined ?
	if ( wppaCanAjaxRender || ! newurl ) {

		jQuery.ajax( { 	url: 		ajaxurl,
						async: 		true,
						type: 		'GET',
						timeout: 	60000,
						beforeSend: function( xhr ) {

										// If it is a slideshow: Stop slideshow before pushing it on the stack
										if ( _wppaSSRuns[mocc] ) _wppaStop( mocc );

										// Display the spinner
										jQuery( '#wppa-ajax-spin-'+mocc ).fadeIn();
									},
						success: 	function( result, status, xhr ) {

										if ( wppaRenderAdd ) {
											jQuery( wppaRenderAdd + result ).insertBefore( '#wppa-container-'+mocc+'-end' );
										}

										else {

											// Do not render modal if behind button. When behind button, there is no newurl,
											// so we test on the existence of newurl to see if it is behind button
											if ( wppaRenderModal && newurl ) {

												// Init dialog options
												var opt = {
													modal:		true,
													resizable: 	true,
													width:		wppaGetContainerWidth( mocc ),
													show: 		{
																	effect: 	"fadeIn",
																	duration: 	400
																},
													closeText: 	"",
												};

												// Open modal dialog
												jQuery( '#wppa-modal-container-'+mocc ).html( result ).dialog( opt ).dialog( "open" );

												// Adjust styles
												jQuery( '.ui-dialog' ).css( {
																				boxShadow: 			'0px 0px 5px 5px #aaaaaa',
																				borderRadius: 		wppaBoxRadius+'px',
																				padding: 			'8px',
																				backgroundColor: 	wppaModalBgColor,
																				boxSizing: 			'content-box',
																				zIndex: 			100000,
																			});
												jQuery( '.ui-dialog-titlebar' ).css(
																						{
																							lineHeight: '0px',
																							height: 	'32px',
																						}
																					);
												jQuery( '.ui-button' ).css(
																			{
																				backgroundImage: 	wppaModalQuitImg,
																				padding:			0,
																				position: 			'absolute',
																				right: 				'8px',
																				top: 				'8px',
																				width: 				'16px',
																				height: 			'16px',
																			});
												jQuery( '.ui-button' ).attr( 'title', 'Close' );

												// Stop a possible slideshow
												jQuery( '.ui-button' ).on( 'click', function() { _wppaStop( mocc ); } );
											}

											// Not modal or behind button
											else {
												jQuery( '#wppa-container-'+mocc ).html( result );

												// If behind button: show hide buttton
												jQuery( '#wppa-button-hide-'+mocc ).show();
											}
										}

										// Push the stack
										if ( wppaCanPushState && wppaUpdateAddressLine ) {
											wppaHis++;

											try {
												history.pushState( {page: wppaHis, occur: mocc, type: 'html', html: result}, "", newurl );
												wppaConsoleLog( 'Ajax rendering: History stack pushed', 'force' );

											}
											catch( err ) {
												try {
													history.replaceState( {page: wppaHis, occur: mocc, type: 'html'}, "", newurl );
													wppaConsoleLog( 'Ajax rendering: History stack updated', 'force' );
												}
												catch( err ) {
													wppaConsoleLog( 'Ajax rendering: History stack update failed', 'force' );
												}
											}

											if ( wppaFirstOccur == 0 ) wppaFirstOccur = mocc;
										}

										// If lightbox is on board, refresh the imagelist. It has just changed, you know!
										wppaUpdateLightboxes();

										// Update qrcode
										if ( typeof( wppaQRUpdate ) != 'undefined' ) {
											wppaConsoleLog( 'Ajax render asked qr code for '+newurl, 'force' );
											wppaQRUpdate( newurl );
										}

										// Run Autocol?
										wppaColWidth[mocc] = 0;
										_wppaDoAutocol( mocc );

										// Report if scripts
										var scriptPos = result.indexOf( '<script' );
										var scriptPosLast = result.lastIndexOf( '<script' );
										if ( scriptPos == -1 ) {
											wppaConsoleLog( 'Ajax render did NOT contain a script tag', 'force' );
										}
										else {
											wppaConsoleLog( 'Ajax render did contain a script tag at position '+scriptPos+' last at '+scriptPosLast, 'force' );
										}
									},
						error: 		function( xhr, status, error ) {
										wppaConsoleLog( 'wppaDoAjaxRender failed. Error = ' + error + ', status = ' + status, 'force' );

										// Do it by reload
										document.location.href = newurl;

										// Run Autocol?
										wppaColWidth[mocc] = 0;	// force a recalc and triggers autocol if needed
										_wppaDoAutocol( mocc );
									},
						complete: 	function( xhr, status, newurl ) {
										wppaWaitForCounter++;

										if ( ! wppaRenderModal ) {
											jQuery('html, body').animate({ scrollTop: jQuery("#wppa-container-"+mocc).offset().top - 32 - wppaStickyHeaderHeight }, 1000);
										}

										// Remove spinner
										jQuery( '#wppa-ajax-spin-'+mocc ).stop().fadeOut();

										// Fake resize
										setTimeout(function(){jQuery(window).trigger('resize')}, 250);
									}
					} );
	}

	// Ajax NOT possible
	else {
		document.location.href = newurl;

		// Run Autocol?
		wppaColWidth[mocc] = 0;	// force a recalc and triggers autocol if needed
		_wppaDoAutocol( mocc );
	}
}

// Set photo status to 'publish'
function wppaAjaxApprovePhoto( photo ) {

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=approve' +
								'&photo-id=' + photo,
					async: 		true,
					type: 		'GET',
					timeout: 	60000,
					success: 	function( result, status, xhr ) {
									if ( result == 'OK' ) {
										jQuery( '.wppa-approve-' + photo ).css( 'display', 'none' );
									}
									else {
										alert( result );
									}
								},
					error: 		function( xhr, status, error ) {
									wppaConsoleLog( 'wppaAjaxApprovePhoto failed. Error = ' + error + ', status = ' + status, 'force' );
								},
				} );
}

// Remove photo
function wppaAjaxRemovePhoto( mocc, photo, isslide ) {

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=remove' +
								'&photo-id=' + photo,
					async: 		true,
					type: 		'GET',
					timeout: 	60000,
					success: 	function( result, status, xhr ) {

									// Remove succeeded?
									rtxt = result.split( '||' );
									if ( rtxt[0] == 'OK' ) {

										// Slide?
										if ( isslide ) {
											jQuery( '#wppa-film-'+_wppaCurIdx[mocc]+'-'+mocc ).attr( 'src', '' );
											jQuery( '#wppa-pre-'+_wppaCurIdx[mocc]+'-'+mocc ).attr( 'src', '' );
											jQuery( '#wppa-film-'+_wppaCurIdx[mocc]+'-'+mocc ).attr( 'alt', 'removed' );
											jQuery( '#wppa-pre-'+_wppaCurIdx[mocc]+'-'+mocc ).attr( 'alt', 'removed' );
											wppaNext( mocc );
										}

										// Thumbnail
										else {
											jQuery( '.wppa-approve-'+photo ).css( 'display', 'none' );
											jQuery( '.thumbnail-frame-photo-'+photo ).css( 'display', 'none' );
										}
									}

									// Remove failed
									else {
										if ( rtxt[3] ) {
											alert( rtxt[3] );
											jQuery( '#wppa-delete-'+photo ).css('text-decoration', 'line-through' );
										}
										else {
											alert( result );
										}
									}
								},
					error: 		function( xhr, status, error ) {
									wppaConsoleLog( 'wppaAjaxRemovePhoto failed. Error = ' + error + ', status = ' + status, 'force' );
								}
				} );
}

// Set comment status to 'pblish'
function wppaAjaxApproveComment( comment ) {

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=approve' +
								'&comment-id=' + comment,
					async: 		true,
					type: 		'GET',
					timeout: 	60000,
					success: 	function( result, status, xhr ) {

									// Approve succeeded?
									if ( result == 'OK' ) {
										jQuery( '.wppa-approve-'+comment ).css( 'display', 'none' );
									}

									// Approve failed
									else {
										alert( result );
									}
								},
					error: 		function( xhr, status, error ) {
									wppaConsoleLog( 'wppaAjaxApproveComment failed. Error = ' + error + ', status = ' + status, 'force' );
								}
				} );

}

// Remove comment
function wppaAjaxRemoveComment( comment ) {

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=remove' +
								'&comment-id=' + comment,
					async: 		true,
					type: 		'GET',
					timeout: 	60000,
					success: 	function( result, status, xhr ) {

									// Remove succeeded?
									var rtxt = result.split( '||' );
									if ( rtxt[0] == 'OK' ) {
										jQuery( '.wppa-approve-'+comment ).css( 'display', 'none' );
										jQuery( '.wppa-comment-'+comment ).css( 'display', 'none' );
									}
									else {
										alert( result );
									}
								},
					error: 		function( xhr, status, error ) {
									wppaConsoleLog( 'wppaAjaxRemoveComment failed. Error = ' + error + ', status = ' + status, 'force' );
								},
				} );
}

// Add photo to zip
function wppaAjaxAddPhotoToZip( mocc, id, reload ) {

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
				data: 		'action=wppa' +
							'&wppa-action=addtozip' +
							'&photo-id=' + id,
				async: 		true,
				type: 		'GET',
				timeout: 	60000,
				success: 	function( result, status, xhr ) {

								// Adding succeeded?
								var rtxt = result.split( '||' );
								if ( rtxt[0] == 'OK' ) {

									// For the thumbnails
									jQuery('#admin-choice-'+id+'-'+mocc).html(rtxt[1]);

									// For the slideshow
									jQuery('#admin-choice-'+id+'-'+mocc).val(rtxt[1]);
									jQuery('#admin-choice-'+id+'-'+mocc).prop('disabled', true);
								}
								else {
									alert( result );
								}

								// Reload
								if ( reload ) {
									document.location.reload( true );
								}
							},
				error: 		function( xhr, status, error ) {
								wppaConsoleLog( 'wppaAjaxAddPhotoToZip failed. Error = ' + error + ', status = ' + status, 'force' );
							},
			} );
}

// Remove admins choice zipfile
function wppaAjaxDeleteMyZip() {

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
				data: 		'action=wppa' +
							'&wppa-action=delmyzip',
				async: 		true,
				type: 		'GET',
				timeout: 	60000,
				success: 	function( result, status, xhr ) {

								// Reload
								document.location.reload( true );

							},
				error: 		function( xhr, status, error ) {
								wppaConsoleLog( 'wppaAjaxDeleteMyZip failed. Error = ' + error + ', status = ' + status, 'force' );
							},
			} );
}

// Frontend Edit Photo
function wppaEditPhoto( mocc, xid ) {

	var id 		= String(xid);
	var name 	= 'Edit Photo '+id;
	var desc 	= '';
	var width 	= wppaEditPhotoWidth; //960;
	var height 	= 512;

	if ( screen.availWidth < width ) width = screen.availWidth;

	var wnd;

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=front-edit' +
								'&photo-id=' + id +
								'&moccur=' + mocc,
					async: 		true,
					type: 		'POST',
					timeout: 	60000,
					beforeSend: function( xhr ) {
									if ( wppaUploadEdit == 'classic' ) {

										// Setup window
										wnd = window.open( "", "_blank", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width="+width+", height="+height, true );
										wnd.document.write( '<! DOCTYPE html>' );
										wnd.document.write( '<html>' );
											wnd.document.write( '<head>' );
												// The following is one statement that fixes a bug in opera
													var myHead = 	'<meta name="viewport" content="width='+width+'" >' +
																	'<link rel="stylesheet" id="wppa_style-css"  href="'+wppaWppaUrl+'/wppa-admin-styles.css?ver='+wppaVersion+'" type="text/css" media="all" />'+
																	'<link rel="stylesheet" id="theme_style" href="'+wppaThemeStyles+'" type="text/css" media="all" />'+
																	'<style>body {font-family: sans-serif; font-size: 12px; line-height: 1.4em;}a {color: #21759B;}</style>'+
																	'<script type="text/javascript" src="'+wppaIncludeUrl+'/js/jquery/jquery.js?ver='+wppaVersion+'"></script>'+
																	'<script type="text/javascript" src="'+wppaWppaUrl+'/js/wppa-utils.js?ver='+wppaVersion+'"></script>'+
																	'<script type="text/javascript" src="'+wppaWppaUrl+'/js/wppa-admin-scripts.js?ver='+wppaVersion+'"></script>'+
																	'<title>'+name+'</title>'+
																	'<script type="text/javascript">wppaAjaxUrl="'+wppaAjaxUrl+'";</script>';
												wnd.document.write( myHead );
											wnd.document.write( '</head>' );
											wnd.document.write( '<body>' ); // onunload="window.opener.location.reload()">' );	// This does not work in Opera
									}
								},
					success: 	function( result, status, xhr ) {
									if ( wppaUploadEdit == 'classic' ) {
										wnd.document.write( result );
									}
									if ( wppaUploadEdit == 'new' ) {
										var opt = {
											modal:		true,
											resizable: 	true,
											width:		wppaGetContainerWidth( mocc ),
											show: 		{
															effect: 	"fadeIn",
															duration: 	400
														},
											closeText: 	"",
										};
										jQuery( '#wppa-modal-container-'+mocc ).html( result ).dialog( opt ).dialog( "open" );
										jQuery( '.ui-dialog' ).css( {
																		boxShadow: 			'0px 0px 5px 5px #aaaaaa',
																		borderRadius: 		wppaBoxRadius+'px',
																		padding: 			'8px',
																		backgroundColor: 	wppaModalBgColor,
																		boxSizing: 			'content-box',
																		zIndex: 			100000,
																	});
										jQuery( '.ui-dialog-titlebar' ).css(
																				{
																					lineHeight: '0px',
																					height: 	'24px',
																				}
																			)
										jQuery( '.ui-button' ).css(
																	{
																		backgroundImage: 	wppaModalQuitImg,
																		padding:			0,
																		position: 			'absolute',
																		right: 				'8px',
																		top: 				'8px',
																		width: 				'16px',
																		height: 			'16px',
																	});
										jQuery( '.ui-button' ).attr( 'title', 'Close' );
									}
								},
					error: 		function( xhr, status, error ) {
									if ( wppaUploadEdit == 'classic' ) {
										wnd.document.write( status + ' ' + error );
									}
									wppaConsoleLog( 'wppaEditPhoto failed. Error = ' + error + ', status = ' + status, 'force' );
								},
					complete: 	function( xhr, status, newurl ) {
									if ( wppaUploadEdit == 'classic' ) {
												wnd.document.write( '<script>wppaPhotoStatusChange( "'+id+'" )</script>' );
											wnd.document.write( '</body>' );
										wnd.document.write( '</html>' );
									}
								}
				} );
}

// Preview tags in frontend upload dialog
function wppaPrevTags( tagsSel, tagsEdit, tagsAlbum, tagsPrev ) {

	var sel 		= jQuery( '.'+tagsSel );
	var selArr 		= [];
	var editTag		= '';
	var album 		= jQuery( '#'+tagsAlbum ).val();
	var i 			= 0;
	var j 			= 0;
	var tags 		= '';

	// Get the selected tags
	while ( i < sel.length ) {
		if ( sel[i].selected ) {
			selArr[j] = sel[i].value;
			j++;
		}
		i++;
	}

	// Add edit field if not empty
	editTag = jQuery( '#'+tagsEdit ).val();
	if ( editTag != '' ) {
		selArr[j] = editTag;
	}

	// Prelim result
	tags = selArr.join();

	// Sanitize if edit field is not empty or album known and put result in preview field
	if ( editTag != '' || tagsAlbum != '' ) {

		jQuery.ajax( { 	url: 		wppaAjaxUrl,
						data: 		'action=wppa' +
									'&wppa-action=sanitizetags' +
									'&tags=' + tags +
									'&album=' + album,
						async: 		true,
						type: 		'GET',
						timeout: 	60000,
						beforeSend: function( xhr ) {
										jQuery( '#'+tagsPrev ).html( 'Working...' );
									},
						success: 	function( result, status, xhr ) {
										jQuery( '#'+tagsPrev ).html( wppaTrim( result, ',' ) );
									},
						error: 		function( xhr, status, error ) {
										jQuery( '#'+tagsPrev ).html( '<span style="color:red" >' + error + '</span>' );
										wppaConsoleLog( 'wppaPrevTags failed. Error = ' + error + ', status = ' + status, 'force' );
									},
					} );
	}
}

// Delete album
function wppaAjaxDestroyAlbum( album, nonce ) {

	// Are you sure?
	if ( confirm('Are you sure you want to delete this album?') ) {

		jQuery.ajax( { 	url: 		wppaAjaxUrl,
						data: 		'action=wppa' +
									'&wppa-action=destroyalbum' +
									'&album=' + album +
									'&nonce=' + nonce,
						async: 		true,
						type: 		'GET',
						timeout: 	60000,
						success: 	function( result, status, xhr ) {
										alert( result+'\n'+'Page will be reloaded' );
										document.location.reload( true );
									},
						error: 		function( xhr, status, error ) {
										wppaConsoleLog( 'wppaAjaxDestroyAlbum failed. Error = ' + error + ', status = ' + status, 'force' );
									},
					} );
	}
	return false;
}

// Bump click counter
function _bumpClickCount( photo ) {

	// Feature enabled?
	if ( ! wppaBumpClickCount ) return;

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=bumpclickcount' +
								'&wppa-photo=' + photo +
								'&wppa-nonce=' + jQuery( '#wppa-nonce' ).val(),
					async: 		false,
					type: 		'GET',
					timeout: 	60000,
					success: 	function( result, status, xhr ) {
									wppaConsoleLog( '_bumpClickCount success.' );
								},
					error: 		function( xhr, status, error ) {
									wppaConsoleLog( '_bumpClickCount failed. Error = ' + error + ', status = ' + status, 'force' );
								},
				} );
}

// Bump view counter
function _bumpViewCount( photo ) {

	// Feature enabled?
	if ( ! wppaBumpViewCount ) return;

	// Already bumped?
	if ( wppaPhotoView[photo] ) return;

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=bumpviewcount' +
								'&wppa-photo=' + photo +
								'&wppa-nonce=' + jQuery( '#wppa-nonce' ).val(),
					async: 		true,
					type: 		'GET',
					timeout: 	60000,
					success: 	function( result, status, xhr ) {
									wppaPhotoView[photo] = true;
								},
					error: 		function( xhr, status, error ) {
									wppaConsoleLog( '_bumpViewCount failed. Error = ' + error + ', status = ' + status, 'force' );
								},
				} );
}

// Vote a thumbnail
function wppaVoteThumb( mocc, photo ) {

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=rate' +
								'&wppa-rating=1' +
								'&wppa-rating-id=' + photo +
								'&wppa-occur=' + mocc +
								'&wppa-index=0' +
								'&wppa-nonce=' + jQuery( '#wppa-nonce' ).val(),
					async: 		true,
					type: 		'GET',
					timeout: 	60000,
					success: 	function( result, status, xhr ) {
									jQuery( '#wppa-vote-button-'+mocc+'-'+photo ).val( wppaVotedForMe );
								},
					error: 		function( xhr, status, error ) {
									wppaConsoleLog( 'wppaVoteThumb failed. Error = ' + error + ', status = ' + status, 'force' );
								},
				} );
}

// Rate a photo
function _wppaRateIt( mocc, value ) {
//alert('_wppaRateIt() called with args:'+mocc+' '+value);
	// No value, no vote
	if ( value == 0 ) return;

	// Do not rate a running show
	if ( _wppaSSRuns[mocc] ) return;

	// Init vars
	var photo 		= _wppaId[mocc][_wppaCurIdx[mocc]];
	var oldval  	= _wppaMyr[mocc][_wppaCurIdx[mocc]];
	var waittext  	= _wppaWaitTexts[mocc][_wppaCurIdx[mocc]];

	// If wait text, alert and exit
	if ( waittext.length > 0 ) {
		alert( waittext );
		return;
	}

	// Already rated, and once allowed only?
	if ( oldval != 0 && wppaRatingOnce ) {
//		alert('exit 2');
		return;
	}

	// Disliked aleady?
	if ( oldval < 0 ) return;

	// Set Vote in progress flag
	_wppaVoteInProgress = true;

	// Do the voting
	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=rate' +
								'&wppa-rating=' + value +
								'&wppa-rating-id=' + photo +
								'&wppa-occur=' + mocc +
								'&wppa-index=' + _wppaCurIdx[mocc] +
								'&wppa-nonce=' + jQuery( '#wppa-nonce' ).val(),
					async: 		true,
					type: 		'GET',
					timeout: 	60000,
					beforeSend: function( xhr ) {

									// Set icon
									jQuery( '#wppa-rate-'+mocc+'-'+value ).attr( 'src', wppaImageDirectory+'tick.png' );

									// Fade in fully
									jQuery( '#wppa-rate-'+mocc+'-'+value ).stop().fadeTo( 100, 1.0 );

									// Likes
									jQuery( '#wppa-like-'+mocc ).attr( 'src', wppaImageDirectory+'spinner.gif' );
								},
					success: 	function( result, status, xhr ) {
//alert('_wppaRateIt() result='+result);
									var ArrValues = result.split( "||" );

									// Error from rating algorithm?
									if ( ArrValues[0] == 0 ) {
										if ( ArrValues[1] == 900 ) {		// Recoverable error
											alert( ArrValues[2] );
											_wppaSetRatingDisplay( mocc );	// Restore display
										}
										else {
											alert( 'Error Code='+ArrValues[1]+'\n\n'+ArrValues[2] );
										}
									}

									// No rating error
									else {

										// Is it likes sytem?
										if ( ArrValues[7] && ArrValues[7] == 'likes' ) {
											var likeText = ArrValues[4].split( "|" );

											// Slide
											jQuery( '#wppa-like-'+mocc ).attr( 'title', likeText[0] );
											jQuery( '#wppa-liketext-'+mocc ).html( likeText[1] );
											if ( ArrValues[3] == '1' ) {
												jQuery( '#wppa-like-'+mocc ).attr( 'src', wppaImageDirectory+'thumbdown.png' );
											}
											else { // == '0'
												jQuery( '#wppa-like-'+mocc ).attr( 'src', wppaImageDirectory+'thumbup.png' );
											}
						//alert('arv3='+ArrValues[3]);
											_wppaMyr[ArrValues[0]][ArrValues[2]] = ArrValues[3];
											_wppaAvg[ArrValues[0]][ArrValues[2]] = ArrValues[4];
										}

										// Not likes system
										else {
											// Store new values
											_wppaMyr[ArrValues[0]][ArrValues[2]] = ArrValues[3];
											_wppaAvg[ArrValues[0]][ArrValues[2]] = ArrValues[4];
											_wppaDisc[ArrValues[0]][ArrValues[2]] = ArrValues[5];

											// Update display
											_wppaSetRatingDisplay( mocc );

											// If commenting required and not done so far...
											if ( wppaCommentRequiredAfterVote ) {
												if ( ArrValues[6] == 0 ) {
													alert( ArrValues[7] );
												}
											}
										}

										// Shift to next slide?
										if ( wppaNextOnCallback ) _wppaNextOnCallback( mocc );
									}
								},
					error: 		function( xhr, status, error ) {
									wppaConsoleLog( '_wppaRateIt failed. Error = ' + error + ', status = ' + status, 'force' );
								},
				} );
}

// Rate from lightbox
function _wppaOvlRateIt( id, value, mocc, reloadAfter ) {

	// No value, no vote
	if ( value == 0 ) return;

	// Do the voting
	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=rate' +
								'&wppa-rating=' + value +
								'&wppa-rating-id=' + id +
								'&wppa-occur=1' + // Must be <> 0 to indicate no error
								'&wppa-nonce=' + jQuery( '#wppa-nonce' ).val(),
					async: 		true,
					type: 		'GET',
					timeout: 	60000,
					beforeSend: function( xhr ) {

									// Set icon
									jQuery( '.wppa-rate-'+mocc+'-'+value ).attr( 'src', wppaImageDirectory+'tick.png' );

									// Fade in fully
									jQuery( '.wppa-rate-'+mocc+'-'+value ).stop().fadeTo( 100, 1.0 );

									// Likes
									jQuery( '#wppa-like-'+id+'-'+mocc ).attr( 'src', wppaImageDirectory+'spinner.gif' );
									jQuery( '#wppa-like-0' ).attr( 'src', wppaImageDirectory+'spinner.gif' );
								},
					success: 	function( result, status, xhr ) {
			//			alert(result);
							wppaConsoleLog(result, 'force');

									var ArrValues = result.split( "||" );

									// Error from rating algorithm?
									if ( ArrValues[0] == 0 ) {
										if ( ArrValues[1] == 900 ) {		// Recoverable error
											alert( ArrValues[2] );
										}
										else {
											alert( 'Error Code='+ArrValues[1]+'\n\n'+ArrValues[2] );
										}

										// Set icon
										jQuery( '.wppa-rate-'+mocc+'-'+value ).attr( 'src', wppaImageDirectory+'cross.png' );
									}

									// No rating error
									else {

										// Is it likes sytem?
										if ( ArrValues[7] && ArrValues[7] == 'likes' ) {
											var likeText = ArrValues[4].split( "|" );

											// Lightbox
											jQuery( '#wppa-like-0' ).attr( 'title', likeText[0] );
											jQuery( '#wppa-liketext-0' ).html( likeText[1] );
											if ( ArrValues[3] == '1' ) {
												jQuery( '#wppa-like-0' ).attr( 'src', wppaImageDirectory+'thumbdown.png' );
											}
											else { // == '0'
												jQuery( '#wppa-like-0' ).attr( 'src', wppaImageDirectory+'thumbup.png' );
											}

											// Thumbnail
											jQuery( '#wppa-like-'+id+'-'+mocc ).attr( 'title', likeText[0] );
											jQuery( '#wppa-liketext-'+id+'-'+mocc ).html( likeText[1] );
											if ( ArrValues[3] == '1' ) {
												jQuery( '#wppa-like-'+id+'-'+mocc ).attr( 'src', wppaImageDirectory+'thumbdown.png' );
											}
											else { // == '0'
												jQuery( '#wppa-like-'+id+'-'+mocc ).attr( 'src', wppaImageDirectory+'thumbup.png' );
											}

											return;
										}

// result = $occur.'||'.$photo.'||'.$index.'||'.$myavgrat.'||'.$allavgratcombi.'||'.$distext.'||'.$hascommented.'||'.$message;
// ArrValues[3] = my avg rating
// ArrValues[4] = all avg rating
//
// All avg stars have class 	.wppa-avg-'+mocc+'-'+value
// My stars have class 			.wppa-rate-'+mocc+'-'+value
										_wppaSetRd( mocc, ArrValues[4], '.wppa-avg-' );
										_wppaSetRd( mocc, ArrValues[3], '.wppa-rate-' );

										// Reload?
										if ( reloadAfter ) {
			//								document.location.reload(true);
											return;
										}

										// Shift to next slide?
										if ( wppaNextOnCallback ) wppaOvlShowNext();
									}
								},
					error: 		function( xhr, status, error ) {
									wppaConsoleLog( '_wppaOvlRateIt failed. Error = ' + error + ', status = ' + status, 'force' );
								},
				} );

}

// Download a photo having its original name as filename
function wppaAjaxMakeOrigName( mocc, photo ) {

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=makeorigname' +
								'&photo-id=' + photo +
								'&from=fsname',
					async: 		true,
					type: 		'GET',
					timeout: 	60000,
					beforeSend: function( xhr ) {

								},
					success: 	function( result, status, xhr ) {

									var ArrValues = result.split( "||" );
									if ( ArrValues[1] == '0' ) {	// Ok, no error

										// Publish result
										if ( wppaIsSafari ) {
											if ( wppaArtMonkyLink == 'file' ) wppaWindowReference.location = ArrValues[2];
											if ( wppaArtMonkyLink == 'zip' ) document.location = ArrValues[2];
										}
										else {
											if ( wppaArtMonkyLink == 'file' ) window.open( ArrValues[2] );
											if ( wppaArtMonkyLink == 'zip' ) document.location = ArrValues[2];
										}

									}
									else {

										// Close pre-opened window
										if ( wppaIsSafari && wppaArtMonkyLink == 'file' ) wppaWindowReference.close();

										// Show error
										alert( 'Error: '+ArrValues[1]+'\n\n'+ArrValues[2] );
									}
								},
					error: 		function( xhr, status, error ) {
									wppaConsoleLog( 'wppaAjaxMakeOrigName failed. Error = ' + error + ', status = ' + status, 'force' );
								},
					complete: 	function( xhr, status, newurl ) {

								}
				} );
}

// Download an album
function wppaAjaxDownloadAlbum( mocc, id ) {

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=downloadalbum' +
								'&album-id=' + id,
					async: 		true,
					type: 		'GET',
					timeout: 	60000,
					beforeSend: function( xhr ) {

									// Show spinner
									jQuery( '#dwnspin-'+mocc+'-'+id ).css( 'display', '' );
								},
					success: 	function( result, status, xhr ) {

									// Analyze the result
									var ArrValues = result.split( "||" );
									var url 	= ArrValues[0];
									var erok 	= ArrValues[1];
									var text 	= ArrValues[2];

									if ( ArrValues.length == 3 && text != '' ) alert( 'Attention:\n\n'+text );

									if ( erok == 'OK' ) {
										document.location = url;
									}

									else {	// See if a ( partial ) zipfile has been created
										alert( 'The server could not complete the request.\nPlease try again.' );
									}
								},
					error: 		function( xhr, status, error ) {
									//wppaConsoleLog( 'wppaAjaxDownloadAlbum failed. Error = ' + error + ', status = ' + status, 'force' );
									alert( 'An error occurred:\n'+error+'\nPlease try again' );
								},
					complete: 	function( xhr, status, newurl ) {

									// Hide spinner
									jQuery( '#dwnspin-'+mocc+'-'+id ).css( 'display', 'none' );
								}
				} );
}

// Enter a comment to a photo
function wppaAjaxComment( mocc, id ) {

	// Validate comment else return
	if ( ! _wppaValidateComment( mocc, id ) ) return;

	// Make the Ajax send data
	var data = 	'action=wppa' +
				'&wppa-action=do-comment' +
				'&photo-id=' + id +
				'&comname=' + jQuery( "#wppa-comname-"+mocc ).val() +
				'&comment=' + wppaEncode( jQuery( "#wppa-comment-"+mocc ).val() ) +
				'&wppa-captcha=' + jQuery( "#wppa-captcha-"+mocc ).val() +
				'&wppa-nonce=' + jQuery( "#wppa-nonce-"+mocc ).val() +
				'&moccur=' + mocc;
				if ( typeof ( jQuery( "#wppa-comemail-"+mocc ).val() ) != 'undefined' ) {
					data += '&comemail='+jQuery( "#wppa-comemail-"+mocc ).val();
				}
				if ( typeof ( jQuery( "#wppa-comment-edit-"+mocc ).val() ) != 'undefined' ) {
					data += '&comment-edit='+jQuery( "#wppa-comment-edit-"+mocc ).val();
				}
				if ( typeof ( jQuery( "#wppa-returnurl-"+mocc ).val() ) != 'undefined' ) {
					data += '&returnurl='+encodeURIComponent(jQuery( "#wppa-returnurl-"+mocc ).val());
				}

	// Do the ajax commit
	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		data,//'action=wppa' +
				//				'&wppa-action=',
					async: 		true,
					type: 		'POST',
					timeout: 	60000,
					beforeSend: function( xhr ) {

									// Show spinner
									jQuery( "#wppa-comment-spin-"+mocc ).css( 'display', 'inline' );
								},
					success: 	function( result, status, xhr ) {

									// sanitize
									result = result.replace( /\\/g, '' );

									// Show result
									jQuery( "#wppa-comments-"+mocc ).html( result );

									// if from slideshow, update memory data array
									if ( _wppaCurIdx[mocc] ) {
										_wppaCommentHtml[mocc][_wppaCurIdx[mocc]] = result;
									}

									// Make sure comments are visible
									wppaOpenComments( mocc );
								},
					error: 		function( xhr, status, error ) {
									wppaConsoleLog( 'wppaAjaxComment failed. Error = ' + error + ', status = ' + status, 'force' );
								},
					complete: 	function( xhr, status, newurl ) {

									// Hide spinner
									jQuery( "#wppa-comment-spin-"+mocc ).css( 'display', 'none' );
								}
				} );
}

// New style front-end edit photo
function wppaUpdatePhotoNew(id) {

	var myItems = [ 'name',
					'description',
					'tags',
					'custom_0',
					'custom_1',
					'custom_2',
					'custom_3',
					'custom_4',
					'custom_5',
					'custom_6',
					'custom_7',
					'custom_8',
					'custom_9'
					];

	var myData = 	'action=wppa' +
					'&wppa-action=update-photo-new' +
					'&photo-id=' + id +
					'&wppa-nonce=' + jQuery('#wppa-nonce-'+id).val();

	var i = 0;
	while ( i < myItems.length ) {
		if ( typeof(jQuery('#'+myItems[i] ).val() ) != 'undefined' ) {
			myData += '&' + myItems[i] + '=' + jQuery('#'+myItems[i]).val();
		}
		i++;
	}

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		myData,
					async: 		false,
					type: 		'POST',
					timeout: 	10000,
					beforeSend: function( xhr ) {

								},
					success: 	function( result, status, xhr ) {
									if ( result.length > 0 ) { alert(result); }
								},
					error: 		function( xhr, status, error ) {
									alert(result);

									wppaConsoleLog( 'wppaUpdatePhotoNew failed. Error = ' + error + ', status = ' + status, 'force' );
								},
					complete: 	function( xhr, status, newurl ) {

								}
				} );
}

var wppaLastQrcodeUrl = '';
// Get qrcode and put it as src in elm
function wppaAjaxSetQrCodeSrc( url, elm ) {

	// Been here before with this url?
	if ( wppaLastQrcodeUrl == url ) {
		return;
	}

	// Remember this
	wppaLastQrcodeUrl = url;

	var myData = 	'action=wppa' +
					'&wppa-action=getqrcode' +
					'&wppa-qr-nonce=' + jQuery( '#wppa-qr-nonce' ).val() +
					'&url=' + encodeURIComponent( url );

	jQuery.ajax( {	url: 		wppaAjaxUrl,
					data: 		myData,
					async: 		true,
					type: 		'POST',
					timeout: 	10000,
					success: 	function( result, status, xhr ) {
									document.getElementById( elm ).src = result;
									wppaConsoleLog( 'wppaAjaxSetQrCodeSrc put '+result+' into '+elm );
								},
					error: 		function( xhr, status, error ) {
									wppaConsoleLog( 'wppaAjaxSetQrCodeSrc failed. Error = ' + error + ', status = ' + status, 'force' );
								}
				} );
}

// Log we're in.
wppaConsoleLog( 'wppa-ajax-front.js version '+wppaJsAjaxVersion+' loaded.', 'force' );
