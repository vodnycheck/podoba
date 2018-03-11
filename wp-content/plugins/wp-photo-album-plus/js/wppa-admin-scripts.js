/* admin-scripts.js */
/* Package: wp-photo-album-plus
/*
/* Version 6.8.00
/* Various js routines used in admin pages
*/

var wppa_moveup_url = '#';
var wppa_import = 'Import';
var wppa_update = 'Update';
var wppaImageDirectory;
var wppaAjaxUrl;
var wppaUploadToThisAlbum = 'Upload to this album';

/* Check if jQuery library revision is high enough, othewise give a message and uncheck checkbox elm */
function checkjQueryRev(msg, elm, rev){
	var version = parseFloat(jQuery.fn.jquery);
	if (elm.checked) {
		if (version < rev) {
			alert (msg+'\nThe version of your jQuery library: '+version+' is too low for this feature. It requires version '+rev);
			elm.checked = '';
		}
	}
}

function wppaReUpload( event, photo, expectedName ) {

	var form = document.getElementById('wppa-re-up-form-'+photo);
	var fileSelect = document.getElementById('wppa-re-up-file-'+photo);
	var button = document.getElementById('wppa-re-up-butn-'+photo);

	// Remove default action
	event.preventDefault();

	// Get the selected file from the input.
	var file = fileSelect.files[0];

	// Check the file type.
	if ( !file.type.match( 'image.*' ) ) {
		alert( 'File is not an image file!' );
		return;
	}

	// Check the file name
	if ( expectedName.length == 0 ) {
		alert( 'Filename will be set to '+file.name );
	}
	else if ( file.name != expectedName ) {
		if ( ! confirm( 'Filename is different.\nIf you continue, the filename will not be updated!.\n\nContinue?' ) ) {
			jQuery( '#re-up-'+photo ).css( 'display', 'none' );
			return;
		}
	}

	// Update button text
	button.value = 'Uploading...';
	button.style.color = 'black';

	// Create a new FormData object.
	var formData = new FormData();

	// Add the file to the request.
	formData.append('photo', file, file.name);

	// Set up the request.
	var xhr = new XMLHttpRequest();

	// Open the connection.
	var queryString = 	'?action=wppa' +
						'&wppa-action=update-photo' +
						'&photo-id=' + photo +
						'&item=file' +
						'&wppa-nonce=' + document.getElementById('photo-nonce-'+photo).value;

	xhr.open( 'POST', wppaAjaxUrl + queryString, true );

	// Set up a handler for when the request finishes.
	xhr.onload = function () {

		if ( xhr.status === 200 ) {

			var str = wppaTrim( xhr.responseText );
			var ArrValues = str.split( "||" );

				if ( ArrValues[0] != '' ) {
					alert( 'The server returned unexpected output:\n' + ArrValues[0] );
				}
				switch ( ArrValues[1] ) {
					case '0':		// No error
						jQuery('#photostatus-'+photo).html(ArrValues[2]);
						button.value = 'Upload';
						jQuery( '#re-up-'+photo ).css( 'display', 'none' );
						break;
					case '99':	// Photo is gone
						document.getElementById('photoitem-'+photo).innerHTML = '<span style="color:red">'+ArrValues[2]+'</span>';
						break;
					default:	// Any error
						document.getElementById('photostatus-'+photo).innerHTML = '<span style="color:red">'+ArrValues[2]+' ('+ArrValues[1]+')</span>';
						button.value = 'Error occured';
						button.style.color = 'red';
						break;
				}
		}
		else {
			alert('An error occurred!');
		}
	};

	// Send the Data.
	xhr.send( formData );
}

/* This functions does the init after loading settings page. do not put this code in the document.ready function!!! */
function wppaInitSettings() {
	wppaCheckBreadcrumb();
	wppaCheckFullHalign();
	wppaCheckUseThumbOpacity();
	wppaCheckUseCoverOpacity();
	wppaCheckThumbType();
	wppaCheckThumbLink();
	wppaCheckTopTenLink();
	wppaCheckFeaTenLink();
	wppaCheckLasTenLink();
	wppaCheckThumbnailWLink();
	wppaCheckCommentLink();
	wppaCheckXphotoLink();
	wppaCheckMphotoLink();
	wppaCheckSphotoLink();
	wppaCheckSlidePhotoLink();
	wppaCheckSlideOnlyLink();
	wppaCheckAlbumWidgetLink();
	wppaCheckSlideLink();
	wppaCheckCoverImg();
	wppaCheckPotdLink();
	wppaCheckTagLink()
	wppaCheckRating();
	wppaCheckComments();
	wppaCheckCustom();
	wppaCheckResize();
	wppaCheckNumbar();
	wppaCheckWatermark();
	wppaCheckPopup();
	wppaCheckGravatar();
	wppaCheckUserUpload();
	wppaCheckAjax();
	wppaCheckFotomoto();
	wppaCheckLinkPageErr('sphoto');
	wppaCheckLinkPageErr('mphoto');
	wppaCheckLinkPageErr('xphoto');
	wppaCheckLinkPageErr('topten_widget');
	wppaCheckLinkPageErr('slideonly_widget');
	wppaCheckLinkPageErr('potd');
	wppaCheckLinkPageErr('comment_widget');
	wppaCheckLinkPageErr('thumbnail_widget');
	wppaCheckLinkPageErr('lasten_widget');
	wppaCheckLinkPageErr('album_widget');
	wppaCheckLinkPageErr('tagcloud');
	wppaCheckLinkPageErr('multitag');
	wppaCheckLinkPageErr('super_view');
	wppaCheckSplitNamedesc();
	wppaCheckShares();
//	wppaCheckKeepSource();
	wppaCheckCoverType();
	wppaCheckNewpag();
//	wppaCheckIndexSearch();
	wppaCheckCDN();
	wppaCheckAutoPage();
	wppaCheckGps();
	wppaCheckFontPreview();
	wppaCheckCheck( 'enable_video', 'wppa-video' );
	wppaCheckCheck( 'custom_fields', 'custfields' );
	wppaCheckCheck( 'album_custom_fields', 'albumcustfields' );
	wppaCheckCheck( 'new_mod_label_is_text', 'nmtxt' );
	wppaCheckCheck( 'coverphoto_responsive', 'cvpr' );
	wppaCheckSmWidgetLink();

	var tab = new Array('O','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');
	var sub = new Array('A','B','C','D','E','F','G','H','I','J','K','L');

	for (table=1; table<13; table++) {
		var cookie = wppa_getCookie('table_'+table);
		if (cookie == 'on') {
			wppaShowTable(table);	// Refreshes cookie, so it 'never' forgets
		}
		else {
			wppaHideTable(table);	// Refreshes cookie, so it 'never' forgets
		}
		for (subtab=0; subtab<12; subtab++) {
			cookie = wppa_getCookie('table_'+tab[table-1]+'-'+sub[subtab]);
			if (cookie == 'on') {
				wppaToggleSubTable(tab[table-1],sub[subtab]);
			}
		}
		wppaToggleSubTable(tab[table-1],'Z');
	}
}

// Quick sel on settings page will be released at version 5.5.0
function wppaQuickSel() {
	var tab = new Array('O','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');
	var sub = new Array('A','B','C','D','E','F','G','H','I','J','K','Z');
	var tag;
	var _cls;

	// Open Tables and subtables
	for ( table = 1; table < 13; table++ ) {
		if ( table < 13 ) {
			wppaShowTable(table);	// was Show Refreshes cookie, so it 'never' forgets
		}
		else {
			wppaHideTable(table);	// Refreshes cookie, so it 'never' forgets
		}
		wppa_tablecookieoff(table);
		for (subtab=0; subtab<12; subtab++) {
			cookie = wppa_getCookie('table_'+tab[table-1]+'-'+sub[subtab]);
			if (cookie == 'on') {
				wppaToggleSubTable(tab[table-1],sub[subtab]);
			}
			var selection = jQuery('.wppa-'+tab[table-1]+'-'+sub[subtab]);
			if ( selection.length > 0 ) {
				selection.removeClass('wppa-none');
				// For compatibility we fake all subtables are closed, because we close almost everything later on
				wppaSubTabOn[tab[table-1]+'-'+sub[subtab]] = false;//true;
				wppa_tablecookieoff(tab[table-1]+'-'+sub[subtab]);
			}
		}
//		wppaToggleSubTable('X','Z');
//		wppaToggleSubTable('XI','Z');
//		wppaToggleSubTable('VII','A');
	}

//	jQuery( '.subtableheader' ).css('display')=='none');

	// Find tags
	tag1 = jQuery("#wppa-quick-selbox-1").val();
	tag2 = jQuery("#wppa-quick-selbox-2").val();

	// Both empty? close all (sub)tables
	if ( tag1 == '-' && tag2 == '-' ) {
		jQuery( '._wppatag-' ).addClass( 'wppa-none' );
		for ( table = 1; table < 13; table++ ) {
			wppaHideTable( table );
		}
	}
	// Hide not wanted items
	else {
		if ( tag1 != '-' ) {
			jQuery( '._wppatag-'+tag1 ).addClass('wppa-none');
		}
		if ( tag2 != '-' ) {
			jQuery( '._wppatag-'+tag2 ).addClass('wppa-none');
		}
	}
}

function wppaToggleTable(table) {
	if (jQuery('#wppa_table_'+table).css('display')=='none') {
		jQuery('#wppa_table_'+table).css('display', 'inline');
		wppa_tablecookieon(table);
	}
	else {
		jQuery('#wppa_table_'+table).css('display', 'none');
		wppa_tablecookieoff(table);
	}

}

var wppaSubTabOn = new Array();

function wppaToggleSubTable(table,subtable) {
	if (wppaSubTabOn[table+'-'+subtable]) {
		jQuery('.wppa-'+table+'-'+subtable).addClass('wppa-none');
		wppaSubTabOn[table+'-'+subtable] = false;
		wppa_tablecookieoff(table+'-'+subtable);
	}
	else {
		jQuery('.wppa-'+table+'-'+subtable).removeClass('wppa-none');
		wppaSubTabOn[table+'-'+subtable] = true;
		wppa_tablecookieon(table+'-'+subtable);
	}
//alert("table+'-'+subtable = "+table+'-'+subtable+" wppaSubTabOn[table+'-'+subtable] = "+wppaSubTabOn[table+'-'+subtable]);
}

function wppaHideTable(table) {
	jQuery('#wppa_table_'+table).css('display', 'none');
	jQuery('#wppa_tableHide-'+table).css('display', 'none');
	jQuery('#wppa_tableShow-'+table).css('display', 'inline');
	wppa_tablecookieoff(table);
}

function wppaShowTable(table) {
	jQuery('#wppa_table_'+table).css('display', 'block');
	jQuery('#wppa_tableHide-'+table).css('display', 'inline');
	jQuery('#wppa_tableShow-'+table).css('display', 'none');
	wppa_tablecookieon(table);
}

var _wppaRefreshAfter = false;
function wppaRefreshAfter() {
	_wppaRefreshAfter = true;
}

function wppaFollow( id, clas ) {

	if ( jQuery('#'+id).prop('checked') ) {
		jQuery('.'+clas).css('display', '');
	}
	else {
		jQuery('.'+clas).css('display', 'none');
	}
}

function wppaCheckCheck( slug, clas ) {
//wppaConsoleLog( 'CheckCheck slug = '+slug, 'force' );

	var on = document.getElementById( slug ).checked;
	if ( on ) {
		jQuery( '.'+clas ).css( 'display', '' );
		jQuery( '.-'+clas ).css( 'display', 'none' );
	}
	else {
		jQuery( '.'+clas ).css( 'display', 'none' );
		jQuery( '.-'+clas ).css( 'display', '' );
	}
}

// Check for concurrent lightbox and video.
// This is not possible because the controls can not be reached.
function wppaCheckSlideVideoControls() {

	var link = document.getElementById( 'slideshow_linktype' ).value;
	if ( link == 'none' ) {
		return;
	}

//	var on = document.getElementById( 'start_slide_video' ).checked;
//	if ( ! on ) {
//		return;
//	}

	alert('Warning! '+
			"\n"+
			'You can not have video controls on a videoslide when there is a link on the slide.'+
			"\n"+
			'The videoslide will not show controls and will also not autoplay');
}

function wppaCheckFotomoto() {
	var on = document.getElementById("fotomoto_on").checked;
	if ( on ) {
		jQuery(".wppa_fotomoto").css('display', '');
	}
	else {
		jQuery(".wppa_fotomoto").css('display', 'none');
	}
}

function wppaCheckFontPreview() {
	var font = document.getElementById('textual_watermark_font').value;
	var type = document.getElementById('textual_watermark_type').value;
	var fsrc = wppaFontDirectory+'wmf'+font+'-'+type+'.png';
	var tsrc = wppaFontDirectory+'wmf'+type+'-'+font+'.png';
	jQuery('#wm-font-preview').attr('src', fsrc);
	jQuery('#wm-type-preview').attr('src', tsrc);
}

/* Adjust visibility of selection radiobutton if fixed photo is chosen or not */
/* Also: hide/show order# stuff */
function wppaCheckWidgetMethod() {
	var ph;
	var i;
	if (document.getElementById('wppa-wm').value=='4') {
		document.getElementById('wppa-wp').style.visibility='visible';
		var per = jQuery('#wppa-wp').val();

		if ( per == 'day-of-week' || per == 'day-of-month' || per == 'day-of-year' ) {
			jQuery('.wppa-order').css('visibility', '');
		}
		else {
			jQuery('.wppa-order').css('visibility', 'hidden');
		}

	}
	else {
		document.getElementById('wppa-wp').style.visibility='hidden';
		jQuery('.wppa-order').css('visibility', 'hidden');


	}
	if (document.getElementById('wppa-wm').value=='1') {
		ph=document.getElementsByName('wppa-widget-photo');
		i=0;
		while (i<ph.length) {
			ph[i].style.visibility='visible';
			i++;
		}
	}
	else {
		ph=document.getElementsByName('wppa-widget-photo');
		i=0;
		while (i<ph.length) {
			ph[i].style.visibility='hidden';
			i++;
		}
	}
}

/* Enables or disables the setting of full size horizontal alignment. Only when fullsize is unequal to column width */
/* also no hor align if vertical align is ---default-- */
/* Also show/hide initial colwidth for resp themem ( Table I-A1.1 ) */
function wppaCheckFullHalign() {
	var fs = document.getElementById('fullsize').value;
	var cs = document.getElementById('colwidth').value;
	var va = document.getElementById('fullvalign').value;
	if ((fs != cs) && (va != 'default')) {
		jQuery('.wppa_ha').css('display', '');
	}
	else {
		jQuery('.wppa_ha').css('display', 'none');
	}
	if ( cs == 'auto' ) {
		jQuery('.wppa_init_resp_width').css('display', '');
	}
	else {
		jQuery('.wppa_init_resp_width').css('display', 'none');
	}
}

/* Check for CDN type */
function wppaCheckCDN() {
	var cdn = document.getElementById('cdn_service').value;
	if ( cdn == 'cloudinary' || cdn == 'cloudinarymaintenance' ) jQuery('.cloudinary').css('display', '');
	else jQuery('.cloudinary').css('display', 'none');
}

/* Check GPX Implementation */
function wppaCheckGps() {
	var gpx = document.getElementById('gpx_implementation').value;
	if ( gpx == 'wppa-plus-embedded' ) {
		jQuery('.wppa_gpx_native').css('display', '');
		jQuery('.wppa_gpx_plugin').css('display', 'none');
	}
	else {
		jQuery('.wppa_gpx_native').css('display', 'none');
		jQuery('.wppa_gpx_plugin').css('display', '');
	}
}

/* Enables or disables popup thumbnail settings according to availability */
function wppaCheckThumbType() {
	var ttype = document.getElementById('thumbtype').value;
	if (ttype == 'default') {
		jQuery('.tt_normal').css('display', '');
		jQuery('.tt_ascovers').css('display', 'none');
		jQuery('.tt_always').css('display', '');
		wppaCheckUseThumbOpacity();
	}
	if (ttype == 'ascovers'||ttype == 'ascovers-mcr') {
		jQuery('.tt_normal').css('display', 'none');
		jQuery('.tt_ascovers').css('display', '');
		jQuery('.tt_always').css('display', '');
	}
	if (ttype == 'masonry') {
		jQuery('.tt_normal').css('display', 'none');
		jQuery('.tt_ascovers').css('display', 'none');
		jQuery('.tt_always').css('display', '');
		jQuery('.tt_masonry').css('display', '');
	}
}

function wppaCheckAutoPage() {
	var auto = document.getElementById('auto_page').checked;
	if ( auto ) jQuery('.autopage').css('display', '');
	else jQuery('.autopage').css('display', 'none');
}

/* Enables or disables thumb opacity dependant on whether feature is selected */
function wppaCheckUseThumbOpacity() {
	var topac = document.getElementById('use_thumb_opacity').checked;
	if (topac) {
		jQuery('.thumb_opacity').css('color', '#333');
		jQuery('.thumb_opacity_html').css('visibility', 'visible');
	}
	else {
		jQuery('.thumb_opacity').css('color', '#999');
		jQuery('.thumb_opacity_html').css('visibility', 'hidden');
	}
}

/* Enables or disables coverphoto opacity dependant on whether feature is selected */
function wppaCheckUseCoverOpacity() {
	var copac = document.getElementById('use_cover_opacity').checked;
	if (copac) {
		jQuery('.cover_opacity').css('color', '#333');
		jQuery('.cover_opacity_html').css('visibility', 'visible');
	}
	else {
		jQuery('.cover_opacity').css('color', '#999');
		jQuery('.cover_opacity_html').css('visibility', 'hidden');
	}
}

/* Enables or disables secundairy breadcrumb settings */
function wppaCheckBreadcrumb() {
	var Bca = document.getElementById('show_bread_posts').checked;
	var Bcb = document.getElementById('show_bread_pages').checked;
	var Bc = Bca || Bcb;
	if (Bc) {
		jQuery('.wppa_bc').css('display', '');
		jQuery('.wppa_bc_html').css('display', '');
		var BcVal = document.getElementById('bc_separator').value;
		if (BcVal == 'txt') {
			jQuery('.wppa_bc_txt').css('display', '');
			jQuery('.wppa_bc_url').css('display', 'none');

			jQuery('.wppa_bc_txt_html').css('display', '');
			jQuery('.wppa_bc_url_html').css('display', 'none');
		}
		else {
			if (BcVal == 'url') {
				jQuery('.wppa_bc_txt').css('display', 'none');
				jQuery('.wppa_bc_url').css('display', '');

				jQuery('.wppa_bc_txt_html').css('display', 'none');
				jQuery('.wppa_bc_url_html').css('display', '');
			}
			else {
				jQuery('.wppa_bc_txt').css('display', 'none');
				jQuery('.wppa_bc_url').css('display', 'none');
			}
		}
	}
	else {
		jQuery('.wppa_bc').css('display', 'none');
		jQuery('.wppa_bc_txt').css('display', 'none');
		jQuery('.wppa_bc_url').css('display', 'none');
	}
}

/* Enables or disables rating system settings */
function wppaCheckRating() {
	var Rt = document.getElementById('rating_on').checked;
	if (Rt) {
		jQuery('.wppa_rating').css('color', '#333');
		jQuery('.wppa_rating_html').css('visibility', 'visible');
		jQuery('.wppa_rating_').css('display', '');
	}
	else {
		jQuery('.wppa_rating').css('color', '#999');
		jQuery('.wppa_rating_html').css('visibility', 'hidden');
		jQuery('.wppa_rating_').css('display', 'none');
	}
}

function wppaCheckComments() {
	var Cm = document.getElementById('show_comments').checked;
	if (Cm) {
		jQuery('.wppa_comment').css('color', '#333');
		jQuery('.wppa_comment_html').css('visibility', 'visible');
		jQuery('.wppa_comment_').css('display', '');
	}
	else {
		jQuery('.wppa_comment').css('color', '#999');
		jQuery('.wppa_comment_html').css('visibility', 'hidden');
		jQuery('.wppa_comment_').css('display', 'none');
	}

}

function wppaCheckAjax() {
	var Aa = document.getElementById('allow_ajax').checked;
	if (Aa) {
		jQuery('.wppa_allow_ajax_').css('display', '');
	}
	else {
		jQuery('.wppa_allow_ajax_').css('display', 'none');
	}
}

function wppaCheckShares() {
	var Sh = document.getElementById('share_on').checked || document.getElementById('share_on_widget').checked || document.getElementById('share_on_lightbox').checked || document.getElementById('share_on_thumbs').checked || document.getElementById('share_on_mphoto').checked;
	if (Sh) jQuery('.wppa_share').css('display', '');
	else jQuery('.wppa_share').css('display', 'none');
}
/*
function wppaCheckKeepSource() {
	var Ks = document.getElementById('keep_source').checked;
	if ( Ks ) jQuery('.wppa_keep_source').css('display', '');
	else jQuery('.wppa_keep_source').css('display', 'none');
}
*/

function wppaCheckCoverType() {
	var Type = document.getElementById('cover_type').value;
	var Pos = document.getElementById('coverphoto_pos').value;

	if ( Type == 'imagefactory' || Type == 'imagefactory-mcr' ) {
		jQuery('.wppa_imgfact_').css('display', '');
/*		if ( Pos == 'left' || Pos == 'right' )
			alert('To avoid layout problems: please set Cover photo position ( Table IV-D3 ) to \'top\' or \'bottom\'!');
*/	}
	else jQuery('.wppa_imgfact_').css('display', 'none');

	if ( Type == 'longdesc' ) {
/*		if ( Pos == 'top' || Pos == 'bottom' )
			alert('To avoid layout problems: please set Cover photo position ( Table IV-D3 ) to \'left\' or \'right\'!');
*/	}
}

function wppaCheckNewpag() {
	var Np = document.getElementById('newpag_create').checked;
	if ( Np ) jQuery('.wppa_newpag').css('display', '');
	else jQuery('.wppa_newpag').css('display', 'none');
}

function wppaCheckCustom() {
	var Cm = document.getElementById('custom_on').checked;
	if (Cm) {
		jQuery('.wppa_custom').css('color', '#333');
		jQuery('.wppa_custom_html').css('visibility', 'visible');
		jQuery('.wppa_custom_').css('display', '');
	}
	else {
		jQuery('.wppa_custom').css('color', '#999');
		jQuery('.wppa_custom_html').css('visibility', 'hidden');
		jQuery('.wppa_custom_').css('display', 'none');
	}
}

function wppaCheckWidgetLink() {
	if (document.getElementById('wlp').value == '-1') {
		jQuery('.wppa_wlu').css('display', '');
		jQuery('.wppa_wlt').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_wlu').css('display', 'none');
		jQuery('.wppa_wlt').css('visibility', 'visible');
	}
}

function wppaCheckSmWidgetLink() {
	if (document.getElementById('widget_sm_linktype').value == 'home') {
		jQuery('.wppa_smrp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_smrp').css('visibility', '');
	}
}

function wppaCheckThumbLink() {
	var lvalue = document.getElementById('thumb_linktype').value;
	if (lvalue == 'none' || lvalue == 'file' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_tlp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_tlp').css('visibility', 'visible');
	}
	if (lvalue == 'none' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_tlb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_tlb').css('visibility', 'visible');
	}
}

function wppaCheckTopTenLink() {
	var lvalue = document.getElementById('topten_widget_linktype').value;
	if (lvalue == 'none' || lvalue == 'file' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_ttlp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_ttlp').css('visibility', 'visible');
	}
	if (lvalue == 'none' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_ttlb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_ttlb').css('visibility', 'visible');
	}
}

function wppaCheckFeaTenLink() {
	var lvalue = document.getElementById('featen_widget_linktype').value;
	if (lvalue == 'none' || lvalue == 'file' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_ftlp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_ftlp').css('visibility', 'visible');
	}
	if (lvalue == 'none' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_ftlb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_ftlb').css('visibility', 'visible');
	}
}

function wppaCheckLasTenLink() {
	var lvalue = document.getElementById('lasten_widget_linktype').value;
	if (lvalue == 'none' || lvalue == 'file' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_ltlp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_ltlp').css('visibility', 'visible');
	}
	if (lvalue == 'none' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_ltlb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_ltlb').css('visibility', 'visible');
	}
}

function wppaCheckThumbnailWLink() {
	var lvalue = document.getElementById('thumbnail_widget_linktype').value;
	if (lvalue == 'none' || lvalue == 'file' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_tnlp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_tnlp').css('visibility', 'visible');
	}
	if (lvalue == 'none' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_tnlb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_tnlb').css('visibility', 'visible');
	}
}

function wppaCheckCommentLink() {
	var lvalue = document.getElementById('comment_widget_linktype').value;
	if (lvalue == 'none' || lvalue == 'file' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_cmlp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_cmlp').css('visibility', 'visible');
	}
	if (lvalue == 'none' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_cmlb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_cmlb').css('visibility', 'visible');
	}
}

function wppaCheckSlideOnlyLink() {
	var lvalue = document.getElementById('slideonly_widget_linktype').value;
	if (lvalue == 'none' || lvalue == 'file' || lvalue == 'widget' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_solp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_solp').css('visibility', 'visible');
	}
	if (lvalue == 'none' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_solb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_solb').css('visibility', 'visible');
	}
}

function wppaCheckAlbumWidgetLink() {
	var lvalue = document.getElementById('album_widget_linktype').value;
	if (lvalue == 'lightbox') {
		jQuery('.wppa_awlp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_awlp').css('visibility', 'visible');
	}
	if (lvalue == 'lightbox') {
		jQuery('.wppa_awlb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_awlb').css('visibility', 'visible');
	}
}

function wppaCheckSlideLink() {
	var lvalue = document.getElementById('slideshow_linktype').value;
		if (lvalue == 'none' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_sslb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_sslb').css('visibility', 'visible');
	}
}

function wppaCheckCoverImg() {
	var lvalue = document.getElementById('coverimg_linktype').value;
		if (lvalue == 'none' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_covimgbl').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_covimgbl').css('visibility', 'visible');
	}
}

function wppaCheckPotdLink() {
	var lvalue = document.getElementById('potd_linktype').value;
	if (lvalue == 'none' || lvalue == 'lightbox' || lvalue == 'file' || lvalue == 'custom') {
		jQuery('.wppa_potdlp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_potdlp').css('visibility', 'visible');
	}
	if (lvalue == 'none' || lvalue == 'lightbox' || lvalue == 'fullpopup') {
		jQuery('.wppa_potdlb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_potdlb').css('visibility', 'visible');
	}
}

function wppaCheckTagLink() {
	var lvalue = document.getElementById('tagcloud_linktype').value;
	/* */
}

function wppaCheckMTagLink() {
	var lvalue = document.getElementById('multitag_linktype').value;
	/* */
}

function wppaCheckXphotoLink() {
	var lvalue = document.getElementById('xphoto_linktype').value;
	if (lvalue == 'none' || lvalue == 'file' || lvalue == 'lightbox' ) {
		jQuery('.wppa_xlp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_xlp').css('visibility', 'visible');
	}
	if (lvalue == 'none' || lvalue == 'lightbox' ) {
		jQuery('.wppa_xlb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_xlb').css('visibility', 'visible');
	}
}

function wppaCheckMphotoLink() {
	var lvalue = document.getElementById('mphoto_linktype').value;
	if (lvalue == 'none' || lvalue == 'file' || lvalue == 'lightbox' ) {
		jQuery('.wppa_mlp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_mlp').css('visibility', 'visible');
	}
	if (lvalue == 'none' || lvalue == 'lightbox' ) {
		jQuery('.wppa_mlb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_mlb').css('visibility', 'visible');
	}
}

function wppaCheckSphotoLink() {
	var lvalue = document.getElementById('sphoto_linktype').value;
	if (lvalue == 'none' || lvalue == 'file' || lvalue == 'lightbox' ) {
		jQuery('.wppa_slp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_slp').css('visibility', 'visible');
	}
	if (lvalue == 'none' || lvalue == 'lightbox' ) {
		jQuery('.wppa_slb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_slb').css('visibility', 'visible');
	}
}

function wppaCheckSlidePhotoLink() {
	var lvalue = document.getElementById('slideshow_linktype').value;
	if (lvalue == 'none' || lvalue == 'file' || lvalue == 'lightbox' || lvalue == 'lightboxsingle' || lvalue == 'fullpopup' ) {
		jQuery('.wppa_sslp').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_sslp').css('visibility', 'visible');
	}
	if (lvalue == 'none' || lvalue == 'lightbox' || lvalue == 'lightboxsingle' || lvalue == 'fullpopup' ) {
		jQuery('.wppa_sslb').css('visibility', 'hidden');
	}
	else {
		jQuery('.wppa_sslb').css('visibility', 'visible');
	}
}

function wppaCheckResize() {
	var Rs = document.getElementById('resize_on_upload').checked;
	if (Rs) {
		jQuery('.re_up').css('display', '');
	}
	else {
		jQuery('.re_up').css('display', 'none');
	}
}

function wppaCheckNumbar() {
	var Nb = document.getElementById('show_slideshownumbar').checked;
	if (Nb) {
		jQuery('.wppa_numbar').css('display', '');
	}
	else {
		jQuery('.wppa_numbar').css('display', 'none');
	}
}

function wppaCheckWatermark() {
	var Wm = document.getElementById('watermark_on').checked;
	if (Wm) {
		jQuery('.wppa_watermark').css('display', '');
	}
	else {
		jQuery('.wppa_watermark').css('display', 'none');
	}
}

function wppaCheckPopup() {
	if (document.getElementById('use_thumb_popup').checked) {
		jQuery('.wppa_popup').css('display', '');
	}
	else {
		jQuery('.wppa_popup').css('display', 'none');
	}
}

function wppaCheckGravatar() {
	if ( ! document.getElementById('comment_gravatar') ) return;
	if (document.getElementById('comment_gravatar').value == 'url') {
		jQuery('.wppa_grav').css('display', '');
	}
	else {
		jQuery('.wppa_grav').css('display', 'none');
	}
}

function wppaCheckUserUpload() {
	if (document.getElementById('user_upload_on').checked) {
		jQuery('.wppa_feup').css('display', '');
	}
	else {
		jQuery('.wppa_feup').css('display', 'none');
	}
}

function wppaCheckSplitNamedesc() {
	if (document.getElementById('split_namedesc').checked) {
		jQuery('.swap_namedesc').css('display', 'none');
		jQuery('.hide_empty').css('display', '');
	}
	else {
		jQuery('.swap_namedesc').css('display', '');
		jQuery('.hide_empty').css('display', 'none');
	}
}

/*
function wppaCheckIndexSearch() {
//	if (document.getElementById('indexed_search').checked) {
		jQuery('.index_search').css('display', '');
//	}
//	else {
//		jQuery('.index_search').css('display', 'none');
//	}
}
*/
function wppa_tablecookieon(i) {
	wppa_setCookie('table_'+i, 'on', '365');
}

function wppa_tablecookieoff(i) {
	wppa_setCookie('table_'+i, 'off', '365');
}

function wppaCookieCheckbox(elm, id) {
	if ( elm.checked ) wppa_setCookie(id, 'on', '365');
	else wppa_setCookie(id, 'off', '365');
}

function wppa_move_up(who) {
	document.location = wppa_moveup_url+who+"&wppa-nonce="+document.getElementById('wppa-nonce').value;
}

function checkColor(xslug) {
	var slug = xslug.substr(5);
	var color = jQuery('#'+slug).val();
	jQuery('#colorbox-'+slug).css('background-color', color);
}

function checkAll(name, clas) {
	var elm = document.getElementById(name);
	if (elm) {
		if ( elm.checked ) {
			jQuery(clas).prop('checked', 'checked');
		}
		else {
			jQuery(clas).prop('checked', '');
		}
	}
}

function impUpd(elm, id) {
	if ( elm.checked ) {
		jQuery(id).prop('value', wppa_update);
		jQuery('.hideifupdate').css('display', 'none');
	}
	else {
		jQuery(id).prop('value', wppa_import);
		jQuery('.hideifupdate').css('display', '');
	}
}

function wppaAjaxDeletePhoto(photo, bef, aft) {

	var before = '';
	var after = '';
	if ( bef ) {
		before = bef;
	}
	else {
		before = '<div style="padding-left:5px;" >';
	}
	if ( aft ) {
		after = aft;
	}
	else {
		aftrer = '</div>';
	}

	wppaFeAjaxLog('in');

	var xmlhttp = wppaGetXmlHttp();

	// Make the Ajax url
	var url = wppaAjaxUrl+'?action=wppa&wppa-action=delete-photo&photo-id='+photo;
	url += '&wppa-nonce='+document.getElementById('photo-nonce-'+photo).value;

	// Do the Ajax action
	xmlhttp.open('GET',url,true);
	xmlhttp.send();

	// Process the result
	xmlhttp.onreadystatechange=function() {
		switch (xmlhttp.readyState) {
		case 1:
			document.getElementById('photostatus-'+photo).innerHTML = 'server connection established';
			break;
		case 2:
			document.getElementById('photostatus-'+photo).innerHTML = 'request received';
			break;
		case 3:
			document.getElementById('photostatus-'+photo).innerHTML = 'processing request';
			break;
		case 4:
			if ( xmlhttp.status == 200 ) {

				var str = wppaTrim(xmlhttp.responseText);
				var ArrValues = str.split("||");
				if ( ArrValues[0] == 'ER' ) {
					alert(ArrValues[3]);
					jQuery('#wppa-delete-'+photo).css('text-decoration','line-through');
				}
				else if (ArrValues[0] != '') {
					alert('The server returned unexpected output:\n'+ArrValues[0]);
				}

				if ( ArrValues[1] == 0 ) document.getElementById('photostatus-'+photo).innerHTML = ArrValues[2];	// Error
				else {
					document.getElementById('photoitem-'+photo).innerHTML = before+ArrValues[2]+after;	// OK
					wppaProcessFull(ArrValues[3], ArrValues[4]);
				}
				wppaFeAjaxLog('out');
			}
			else {	// status != 200
				document.getElementById('photoitem-'+photo).innerHTML = before+'<span style="color:red;" >Comm error '+xmlhttp.status+': '+xmlhttp.statusText+'</span>'+after;
			}
		}
	}
}

function wppaAjaxUndeletePhoto(photo) {

	wppaFeAjaxLog('in');

	var xmlhttp = wppaGetXmlHttp();

	// Make the Ajax url
	var url = wppaAjaxUrl+'?action=wppa&wppa-action=undelete-photo&photo-id='+photo;
	url += '&wppa-nonce='+document.getElementById('photo-nonce-'+photo).value;

	// Do the Ajax action
	xmlhttp.open('GET',url,true);
	xmlhttp.send();

	// Process the result
	xmlhttp.onreadystatechange=function() {
		switch (xmlhttp.readyState) {
		case 1:
			document.getElementById('photostatus-'+photo).innerHTML = 'server connection established';
			break;
		case 2:
			document.getElementById('photostatus-'+photo).innerHTML = 'request received';
			break;
		case 3:
			document.getElementById('photostatus-'+photo).innerHTML = 'processing request';
			break;
		case 4:
			if ( xmlhttp.status == 200 ) {

				var str = wppaTrim(xmlhttp.responseText);
				var ArrValues = str.split("||");
				if ( ArrValues[0] == 'ER' ) {
					alert(ArrValues[3]);
					jQuery('#wppa-delete-'+photo).css('text-decoration','line-through');
				}
				else if (ArrValues[0] != '') {
					alert('The server returned unexpected output:\n'+ArrValues[0]);
				}

				if ( ArrValues[1] == 0 ) document.getElementById('photostatus-'+photo).innerHTML = ArrValues[2];	// Error
				else {
					document.getElementById('photoitem-'+photo).innerHTML = '<div style="padding-left:5px;" >' + ArrValues[2] + '</div>';	// OK
				}
				wppaFeAjaxLog('out');
			}
			else {	// status != 200
				document.getElementById('photoitem-'+photo).innerHTML = before+'<span style="color:red;" >Comm error '+xmlhttp.status+': '+xmlhttp.statusText+'</span>'+after;
			}
		}
	}
}

function wppaAjaxApplyWatermark(photo, file, pos) {

	wppaFeAjaxLog('in');

	var xmlhttp = wppaGetXmlHttp();

	// Show spinner
	jQuery('#wppa-water-spin-'+photo).css({visibility:'visible'});

	// Make the Ajax send data
	var data = 'action=wppa&wppa-action=watermark-photo&photo-id='+photo;
	data += '&wppa-nonce='+document.getElementById('photo-nonce-'+photo).value;
	if (file) data += '&wppa-watermark-file='+file;
	if (pos) data += '&wppa-watermark-pos='+pos;

	// Do the Ajax action
	xmlhttp.open('POST',wppaAjaxUrl,true);
	xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp.send(data);

	// Process the result
	xmlhttp.onreadystatechange=function() {
		if ( xmlhttp.readyState == 4 ) {
			if ( xmlhttp.status == 200 ) {
				var str = wppaTrim(xmlhttp.responseText);
				var ArrValues = str.split("||");

				if (ArrValues[0] != '') {
					alert('The server returned unexpected output:\n'+ArrValues[0]);
				}
				switch (ArrValues[1]) {
					case '0':		// No error
						document.getElementById('photostatus-'+photo).innerHTML = ArrValues[2];
						break;
					default:
						document.getElementById('photostatus-'+photo).innerHTML = '<span style="color:red">'+ArrValues[2]+'</span>';
				}
				// Hide spinner
				jQuery('#wppa-water-spin-'+photo).css({visibility:'hidden'});

				wppaFeAjaxLog('out');
			}
			else {	// status != 200
				document.getElementById('photostatus-'+photo).innerHTML = '<span style="color:red;" >Comm error '+xmlhttp.status+': '+xmlhttp.statusText+'</span>';
			}
		}
	}
}

var wppaAjaxPhotoCount = new Array();
var wppaPhotoUpdateMatrix = new Array();

// Update photo
//
// @1: integer photo id
// @2: string action slug
// @3: elem ( this or getElementById() from caller ) OR a random number.
//     The number must be different in successive calls to trigger subsequent actions.
//     If the number is the same as before, no change is assumed.
// @4: bool: indicating if page reload needed after action.
// @5: An alphameric id for the photo ( required for TynyMce )
function wppaAjaxUpdatePhoto( photo, actionslug, elem, refresh, photoAlfaid ) {

	// Indexes in update matrix
	var phoidx = 0;
	var slgidx = 1;
	var ovlidx = 2;
	var nvlidx = 3;
	var bsyidx = 4;
	var refidx = 5;

	// For tynyMce
	var isTmce = false;
	if ( photoAlfaid ) {
		isTmce = jQuery( "#wppaphotodesc"+photoAlfaid+":visible" ).length == 0;
		jQuery( "#wppaphotodesc" + photoAlfaid + "-html" ).click();
		if ( isTmce ) jQuery( "#wppaphotodesc" + photoAlfaid + "-tmce" ).click();
	}

	// Init
	var count = wppaPhotoUpdateMatrix.length;
	var i = 0;
	var found = false;
	var index = -1;

	// See if we did this slug for this photo already
	while ( i < count ) {
		if ( wppaPhotoUpdateMatrix[i][phoidx] == photo && wppaPhotoUpdateMatrix[i][slgidx] == actionslug ) {
			found = true;
			index = i;
		}
		i++;
	}

	// Not done this yet, create new entry in array
	if ( ! found ) {
		var oldval = 'undefined';
		var newval = false;
		var busy = false;
		wppaPhotoUpdateMatrix[count] = [photo, actionslug, oldval, newval, busy, refresh];
		index = count;
	}

	// Updatearray
	wppaPhotoUpdateMatrix[index][nvlidx] = ( typeof( elem ) == 'number' ? elem : elem.value );
	wppaPhotoUpdateMatrix[index][refidx] = refresh;

	// Run the monitor
	wppaAjaxUpdatePhotoMonitor();
}

function wppaAjaxUpdateDelphoto( photo, actionslug, elem ) {
	wppaAjaxUpdatePhoto( photo, 'del' + actionslug, elem );
}

// This monitor keeps track of running ajax requests
// If many chars are typed quickly ( busy flag true ) updating will be skipped
// until the running ajax request ends. A new request will catch up the rest of the data mods.
function wppaAjaxUpdatePhotoMonitor() {

	// Indexes in update matrix
	var phoidx = 0; // photo id
	var slgidx = 1; // action slug
	var ovlidx = 2; // old value
	var nvlidx = 3; // new value
	var bsyidx = 4; // busy flag
	var refidx = 5; // refresh flag

	// Init
	var count = wppaPhotoUpdateMatrix.length;
	var i = 0;

	// Find the entries in the matrix for the photo/slug cominations where the new value is unequal to the old value,
	// where this combi is not busy ( operation in progress ).
	// For such matrix entries: set busy flag and start an ajax action
	while ( i < count ) {

		if ( ( wppaPhotoUpdateMatrix[i][ovlidx] != wppaPhotoUpdateMatrix[i][nvlidx] ) && ! wppaPhotoUpdateMatrix[i][bsyidx] ) {

			// Set busy
			wppaPhotoUpdateMatrix[i][bsyidx] = true;

			// Start ajax
			_wppaAjaxUpdatePhoto( wppaPhotoUpdateMatrix[i][phoidx], wppaPhotoUpdateMatrix[i][slgidx], wppaPhotoUpdateMatrix[i][nvlidx], wppaPhotoUpdateMatrix[i][refidx] );
		}
		i++;
	}
}

// Do the actual ajax update request
function _wppaAjaxUpdatePhoto( photo, actionslug, value, refresh, bef, aft ) {

	if ( ! bef ) bef = '';
	if ( ! aft ) aft = '';

	// Indexes in update matrix
	var phoidx = 0; // photo id
	var slgidx = 1; // action slug
	var ovlidx = 2; // old value
	var nvlidx = 3; // new value
	var bsyidx = 4; // busy flag
	var refidx = 5; // refresh flag

	// On Front-end edit photo classic style, there is a button: Update and exit.
	// Set it to the desired state
	wppaFeAjaxLog('in');

	// Increment total number of ajax actions pending for this photo
	if ( ! wppaAjaxPhotoCount[photo] ) wppaAjaxPhotoCount[photo] = 0;
	wppaAjaxPhotoCount[photo]++;

	// Open ajax object

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa&wppa-action=update-photo' +
								'&photo-id=' + photo +
								'&item=' + actionslug +
								'&wppa-nonce=' + document.getElementById( 'photo-nonce-' + photo ).value +
								'&value=' + wppaEncode( value ),
					async: 		true,
					type: 		'POST',
					timeout: 	10000,
					beforeSend: function( xhr ) {

									// Show spinner
									if ( actionslug == 'description' ) {
										jQuery('#wppa-photo-spin-'+photo).css( { visibility: 'visible' } );
									}

									// Update status
									jQuery( '#photostatus-' + photo ).html( 'Working, please wait... (' + wppaAjaxPhotoCount[photo] + ')');

								},
					success: 	function( result, status, xhr ) {

									// Format result
									var str = wppaTrim( result );
									var ArrValues = str.split("||");

									// One pending less
									wppaAjaxPhotoCount[photo]--;

									// Any strange results returned?
									if ( ArrValues[0] != '' ) {
										alert( 'The server returned unexpected output:\n' + ArrValues[0] );
									}

									// Update status field
									switch ( ArrValues[1] ) {
										case '0':	// No error

											// Udate status
											if ( wppaAjaxPhotoCount[photo] == 0 ) {
												jQuery('#photostatus-'+photo).html( ArrValues[2] );
											}
											else {
												jQuery('#photostatus-'+photo).html( 'Working, please wait... (' + wppaAjaxPhotoCount[photo] + ')' );
											}

											// Increment photo version no
											if ( ArrValues[3] ) {
												var href = jQuery( '#fs-a-' + photo ).attr( 'href' );
												var arr = href.split( '=' );

												arr[1] = parseInt( arr[1] ) + 1;
												jQuery( '#fs-a-' + photo ).attr( 'href', arr[0] + '=' + arr[1] );
												jQuery( '#fs-img-' + photo ).attr( 'src', arr[0] + '=' + arr[1] );
											}

											// Increment thumbnail version
											if ( ArrValues[4] ) {
												var src = jQuery( '#tnp-' + photo ).attr( 'src' );
												var arr = src.split( '=' );
												arr[1] = parseInt( arr[1] ) + 1;
												jQuery( '#tnp-' + photo ).attr( 'src', arr[0] + '=' + arr[1] );
											}

											// Update Magic stack
											if ( typeof( ArrValues[5] ) != 'undefined' ) {
												jQuery( '#imstack-' + photo ).html( ArrValues[5] );
												if ( ArrValues[5].length > 0 ) {
													jQuery( '#imstackbutton-' + photo ).css( 'display', '' );
												}
												else {
													jQuery( '#imstackbutton-' + photo ).css( 'display', 'none' );
												}
											}

											// Update display file sizes
											if ( typeof( ArrValues[6] ) != 'undefined' ) {
												jQuery( '#dispfileinfo-' + photo ).html( ArrValues[6] );
											}

											break;
										case '99':	// Photo is gone
											jQuery( '#photoitem-' + photo ).html( bef+'<span style="color:red">' + ArrValues[2] + '</span>'+aft );
											break;
										default:	// Any error
											jQuery( '#photostatus-' + photo ).html( '<span style="color:red">' + ArrValues[2] + ' (' + ArrValues[1] + ')</span>' );
											break;
									}

									// Hide spinner
									if ( actionslug == 'description' ) {
										jQuery('#wppa-photo-spin-'+photo).css( { visibility:'hidden' } );
									}

									// Find index in matrix
									var i = 0;
									var index = -1;
									count = wppaPhotoUpdateMatrix.length;
									while ( i < count ) {
										if ( wppaPhotoUpdateMatrix[i][0] == photo && wppaPhotoUpdateMatrix[i][1] == actionslug ) {
											index = i;
										}
										i++;
									}

									// Update matrix if not from bulk edit
									if ( wppaPhotoUpdateMatrix[index] ) {
										wppaPhotoUpdateMatrix[index][2] = ( value ? value : 0 );
										wppaPhotoUpdateMatrix[index][4] = false;	// no more busy
										wppaPhotoUpdateMatrix[index][5] = false;	// reset refresh
									}

									// Front-end button
									wppaFeAjaxLog('out');

									// check for more
									wppaAjaxUpdatePhotoMonitor();

									// Refresh only if no error
									if ( refresh && ArrValues[1] == '0' ) {
										jQuery( '#photostatus-' + photo ).after( '<span style="color:blue;font-weight:bold;"> Reloading...</span>' );
										jQuery( '#wppa-photo-spin-' + photo ).css( { visibility: 'visible' } );
										setTimeout( function() { wppaReload( '#photo_'+photo ) }, 1000 );
										return;
									}
								},
					error: 		function( xhr, status, error ) {

									// One pending less
									wppaAjaxPhotoCount[photo]--;

									// Update status
									jQuery( '#photostatus-' + photo ).html( '<span style="color:red;" >Comm error ' + status + '</span>' );

									// Log error
									wppaConsoleLog( '_wppaAjaxUpdatePhoto failed. Error = ' + error + ', status = ' + status, 'force' );
								},
					complete: 	function( xhr, status, newurl ) {
									jQuery( '#wppa-admin-spinner' ).css( 'display', 'none' );
								}
				} );
}


function wppaChangeScheduleAlbum(album, elem) {
	var onoff = jQuery(elem).prop('checked');
	if ( onoff ) {
		jQuery('.wppa-datetime-'+album).css('display', 'inline');
	}
	else {
		jQuery('.wppa-datetime-'+album).css('display', 'none');
		wppaAjaxUpdateAlbum(album, 'scheduledtm', document.getElementById('wppa-dummy') );
	}
}

var _wppaRefreshAfter = false;

var wppaAjaxAlbumCount = 0;
var wppaAlbumUpdateMatrix = new Array();

// Update album
//
// @1: integer album id
// @2: string action slug
// @3: elem ( this or getElementById() from caller ) OR a random number.
//     The number must be different in successive calls to trigger subsequent actions.
//     If the number is the same as before, no change is assumed.
// @4: bool: indicating if page reload needed after action.
function wppaAjaxUpdateAlbum(album, actionslug, elem, refresh) {

	// Indexes in udate matrix
	var albidx = 0;
	var slgidx = 1;
	var ovlidx = 2;
	var nvlidx = 3;
	var bsyidx = 4;
	var refidx = 5;

	// Are we using TynyMce?
	var isTmce = jQuery( "#wppaalbumdesc:visible" ).length == 0;
	jQuery( "#wppaalbumdesc-html" ).click();

	// Init
	var count = wppaAlbumUpdateMatrix.length;
	var i = 0;
	var found = false;
	var index = -1;

	// See if we did this slug for this album already
	while ( i < count ) {
		if ( wppaAlbumUpdateMatrix[i][albidx] == album && wppaAlbumUpdateMatrix[i][slgidx] == actionslug ) {
			found = true;
			index = i;
		}
		i++;
	}

	// Not done this yet, create new entry in array
	if ( ! found ) {
		var oldval = 'undefined';
		var newval = false;
		var busy = false;
		wppaAlbumUpdateMatrix[count] = [album, actionslug, oldval, newval, busy, refresh];
		index = count;
	}

	// Update array
	wppaAlbumUpdateMatrix[index][nvlidx] = ( typeof(elem) == 'number' ? elem : elem.value );
	wppaAlbumUpdateMatrix[index][refidx] = refresh;

	// Run the monitor
	wppaAjaxUpdateAlbumMonitor( isTmce );
}

// This monitor keeps track of running ajax requests
// If many chars are typed quickly ( busy flag true ) updating will be skipped
// until the running ajax request ends. A new request will catch up the rest of the data mods.
function wppaAjaxUpdateAlbumMonitor( isTmce ) {

	var albidx = 0;	// album id
	var slgidx = 1; // action slug
	var ovlidx = 2; // old value
	var nvlidx = 3; // new value
	var bsyidx = 4; // busy flag
	var refidx = 5; // page refresh after completion

	var count = wppaAlbumUpdateMatrix.length;
	var i = 0;

	// Find the entries in the matrix for the album/slug cominations where the new value is unequal to the old value,
	// where this combi is not busy ( operation in progress ).
	// For such matrix entries: set busy flag and start an ajax action
	while ( i < count ) {

		if ( ( wppaAlbumUpdateMatrix[i][ovlidx] != wppaAlbumUpdateMatrix[i][nvlidx] ) && ! wppaAlbumUpdateMatrix[i][bsyidx] ) {

			// Set busy
			wppaAlbumUpdateMatrix[i][bsyidx] = true;

			// Start ajax
			_wppaAjaxUpdateAlbum( wppaAlbumUpdateMatrix[i][albidx], wppaAlbumUpdateMatrix[i][slgidx], wppaAlbumUpdateMatrix[i][nvlidx], isTmce, wppaAlbumUpdateMatrix[i][refidx] );
		}
		i++;
	}
	if ( isTmce ) jQuery( "#wppaalbumdesc-tmce" ).click();
}

// Do the actual ajax update request
function _wppaAjaxUpdateAlbum( album, actionslug, value, isTmce, refresh ) {

	// Increment total number of ajax actions pending
	wppaAjaxAlbumCount++;

	var albidx = 0;	// album id
	var slgidx = 1; // action slug
	var ovlidx = 2; // old value
	var nvlidx = 3; // new value
	var bsyidx = 4; // busy flag
	var refidx = 5; // page refresh after completion

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa&wppa-action=update-album' +
								'&album-id=' + album +
								'&item=' + actionslug +
								'&wppa-nonce=' + document.getElementById( 'album-nonce-' + album ).value +
								'&value=' + wppaEncode( value ),
					async: 		true,
					type: 		'POST',
					timeout: 	10000,
					beforeSend: function( xhr ) {

									// Show spinner
									if ( actionslug == 'description' ) {
										jQuery( '#wppa-album-spin' ).css( { visibility: 'visible' } );
									}

									// Update status
									jQuery( '#albumstatus-' + album ).html( 'Working, please wait... (' + wppaAjaxAlbumCount + ')' );
								},
					success: 	function( result, status, xhr ) {

									// Format result
									var str = wppaTrim( result );
									var ArrValues = str.split( "||" );

									// One pending less
									wppaAjaxAlbumCount--;

									// Any strange results returned?
									if ( ArrValues[0] != '' ) {
										alert( 'The server returned unexpected output:\n' + ArrValues[0] );
									}

									// Update status field
									switch ( ArrValues[1] ) {
										case '0':		// No error

											// Update status
											// Done?
											if ( wppaAjaxAlbumCount == 0 ) {
												jQuery( '#albumstatus-' + album ).html( ArrValues[2] );
											}
											// Not done yet
											else {
												jQuery( '#albumstatus-' + album ).html( 'Working, please wait... (' + wppaAjaxAlbumCount + ')' );
											}
											break;

										default:		// Any error
											jQuery( '#albumstatus-' + album ).html = ( '<span style="color:red">' + ArrValues[2] + ' (' + ArrValues[1] + ')</span>' );
											break;
									}

									// Process full/notfull. The last action may have caused changing the status of 'album full'
									if ( typeof( ArrValues[3] ) != 'undefined' ) {
										wppaProcessFull( ArrValues[3], ArrValues[4] );
									}

									// Need refresh? Refresh only if no error
									if ( refresh && ArrValues[1] == '0' ) {

										// Indicate reloading
										jQuery( '#albumstatus-' + album ).after( '<span style="color:blue;font-weight:bold;"> Reloading...</span>' );

										// Show spinner
										jQuery( '#wppa-admin-spinner' ).fadeIn();

										// Plan reloading
										setTimeout( function() { wppaReload() }, 100 );
										return;
									}

									// No reload: Hide spinner
									if ( actionslug == 'description' ) {
										jQuery( '#wppa-album-spin' ).css( { visibility: 'hidden' } );
									}

									// Init Update Matrix
									var i = 0;
									var index = -1;
									var count = wppaAlbumUpdateMatrix.length;

									// Find this actioslugs matrix index
									while ( i < count ) {
										if ( wppaAlbumUpdateMatrix[i][albidx] == album && wppaAlbumUpdateMatrix[i][slgidx] == actionslug ) {
											index = i;
										}
										i++;
									}

									// Do update matrix
									wppaAlbumUpdateMatrix[index][2] = ( value ? value : 0 );
									wppaAlbumUpdateMatrix[index][4] = false; 	// No more busy
									wppaAlbumUpdateMatrix[index][5] = false;	// reset refresh

									// Update monitor
									wppaAjaxUpdateAlbumMonitor( isTmce );	// Check for more to do

								},
					error: 		function( xhr, status, error ) {

									// One pending less
									wppaAjaxAlbumCount--;

									// Update status
									jQuery( '#albumstatus-' + album ).html( '<span style="color:red;" >Comm error ' + status + '</span>' );

									// Log error
									wppaConsoleLog( '_wppaAjaxUpdateAlbum failed. Error = ' + error + ', status = ' + status, 'force' );
								},
					complete: 	function( xhr, status, newurl ) {

								}
				} );
}

function wppaProcessFull( arg, n ) {

	if ( arg == 'full' ) {
		jQuery('#full').css('display', '');
		jQuery('#notfull').css('display', 'none');
	}
	if ( arg == 'notfull' ) {
		jQuery('#full').css('display', 'none');
		if ( n > 0 ) jQuery('#notfull').attr('value', wppaUploadToThisAlbum+' (max '+n+')');
		else jQuery('#notfull').attr('value', wppaUploadToThisAlbum);
		jQuery('#notfull').css('display', '');
	}
}

function wppaAjaxUpdateCommentStatus( photo, id, value ) {

	var xmlhttp = wppaGetXmlHttp();

	// Make the Ajax url
	var url = wppaAjaxUrl+	'?action=wppa&wppa-action=update-comment-status'+
							'&wppa-photo-id='+photo+
							'&wppa-comment-id='+id+
							'&wppa-comment-status='+value+
							'&wppa-nonce='+document.getElementById('photo-nonce-'+photo).value;

	xmlhttp.onreadystatechange=function() {
		if ( xmlhttp.readyState == 4 ) {
			if ( xmlhttp.status == 200 ) {
				var str = wppaTrim(xmlhttp.responseText);
				var ArrValues = str.split("||");

				if (ArrValues[0] != '') {
					alert('The server returned unexpected output:\n'+ArrValues[0]);
				}
				switch (ArrValues[1]) {
					case '0':		// No error
						jQuery('#photostatus-'+photo).html(ArrValues[2]);
						break;
					default:	// Error
						jQuery('#photostatus-'+photo).html('<span style="color:red">'+ArrValues[2]+'</span>');
						break;
				}
				jQuery('#wppa-comment-spin-'+id).css('visibility', 'hidden');
			}
			else {	// status != 200
				jQuery('#photostatus-'+photo).html('<span style="color:red;" >Comm error '+xmlhttp.status+': '+xmlhttp.statusText+'</span>');
			}
		}
	}

	// Do the Ajax action
	xmlhttp.open('GET',url,true);
	xmlhttp.send();
}

function wppaAjaxUpdateOptionCheckBox(slug, elem) {

	var xmlhttp = wppaGetXmlHttp();

	// Make the Ajax url
	var url = wppaAjaxUrl+'?action=wppa&wppa-action=update-option&wppa-option='+slug;
	url += '&wppa-nonce='+document.getElementById('wppa-nonce').value;
	if (elem.checked) url += '&value=yes';
	else url += '&value=no';
//wppaConsoleLog(url,'force');
	// Process the result
	xmlhttp.onreadystatechange=function() {
		switch (xmlhttp.readyState) {
		case 1:
		case 2:
		case 3:
			jQuery('#img_'+slug).attr('src',wppaImageDirectory+'spinner.gif');
			break;
		case 4:
			var str = wppaTrim(xmlhttp.responseText);
			var ArrValues = str.split("||");

			if (ArrValues[0] != '') {
				alert('The server returned unexpected output:\n'+ArrValues[0]);
			}
			if (xmlhttp.status!=404) {
				switch (ArrValues[1]) {
					case '0':	// No error
						jQuery('#img_'+slug).attr('src',wppaImageDirectory+'tick.png');
						jQuery('#img_'+slug).attr('title',ArrValues[2]);
						if ( ArrValues[3] != '' ) alert(ArrValues[3]);
						if ( _wppaRefreshAfter ) {
							_wppaRefreshAfter = false;
							document.location.reload(true);
						}
						break;
					default:
						jQuery('#img_'+slug).attr('src',wppaImageDirectory+'cross.png');
						jQuery('#img_'+slug).attr('title','Error #'+ArrValues[1]+', message: '+ArrValues[2]+', status: '+xmlhttp.status);
						if ( ArrValues[3] != '' ) alert(ArrValues[3]);
						if ( _wppaRefreshAfter ) {
							_wppaRefreshAfter = false;
							document.location.reload(true);
						}
				}

			}
			else {
				jQuery('#img_'+slug).attr('src',wppaImageDirectory+'cross.png');
				jQuery('#img_'+slug).attr('title','Communication error, status = '+xmlhttp.status);
			}
			wppaCheckInconsistencies();
		}
	}

	// Do the Ajax action
	xmlhttp.open('GET',url,true);
	xmlhttp.send();
}

var wppaAlwaysContinue = 0;

function wppaMaintenanceProc(slug, intern, asCronJob ) {

	// If running: stop
	if ( asCronJob ) {
	}
	else if ( ! intern && document.getElementById(slug+"_continue").value == 'yes' ) {
		document.getElementById(slug+"_continue").value = 'no';
		document.getElementById(slug+"_button").value = 'Start!';
		if ( jQuery("#"+slug+"_togo").html() > 0 ) {
			jQuery("#"+slug+"_status").html('Pausing...');
			jQuery("#"+slug+"_button").css('display', 'none');
		}
		return;
	}

	// Start
	if ( asCronJob ) {
	}
	else {
		document.getElementById(slug+"_continue").value = 'yes';
		document.getElementById(slug+"_button").value = 'Stop!';
		if ( jQuery("#"+slug+"_status").html() == '' ) {
			jQuery("#"+slug+"_status").html('Wait...');
		}
	}

	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa'+
								'&wppa-action=maintenance'+
								'&slug='+slug+
								'&wppa-nonce='+jQuery('#wppa-nonce').val()+
								( asCronJob ? '&wppa-cron' : '' ),
					async: 		true,
					type: 		'POST',
					timeout: 	300000,
					beforeSend: function( xhr ) {

								},
					success: 	function( result, status, xhr ) {

									// sample: '<error>||<slug>||<status>||<togo>'
									var resparr = result.split("||");
									var slug 	= resparr[1];
									var error 	= false;

									// Check for unrecoverable error
									if ( ! slug ) {
										alert('The server returned unexpected output:\n'+result+'\nIf the current procedure has a Skip One button, press it before retrying. Reloading page...');
										wppaReload();
										return;	// give up;
									}

									// Check for recoverable error
									if ( resparr[0].length > 10 ) {
										alert('An error occurred:\n'+resparr[0]);
										error = true;
									}

									// Update status and togo
									jQuery("#"+slug+"_status").html(resparr[2]);
									jQuery("#"+slug+"_togo").html(resparr[3]);
									jQuery("#"+slug+"_button").css('display', '');

									// Stop on error or on ready
									if ( error || resparr[3] == '0' ) {
										if ( resparr[4] == 'reload' ) {
											alert('This page will now be reloaded to finish the operation. Please stay tuned...');
											wppaReload();
											return;
										}
										else {
											setTimeout('wppaMaintenanceProc(\''+slug+'\', false)', 20);	// fake extern to stop it
										}
										return;
									}

									// Continue if not stopped by user
									if ( document.getElementById(slug+"_continue").value == 'yes' ) {
										setTimeout('wppaMaintenanceProc(\''+slug+'\', true)', 20);
										return;
									}

									// Stopped but not ready yet
									if ( ! asCronJob ) {
										jQuery("#"+slug+"_status").html('Pending');
									}
								},

					error: 		function( xhr, status, error ) {
									wppaConsoleLog( 'wppaMaintenanceProc failed. Error = ' + error + ', status = ' + status, 'force' );
									jQuery("#"+slug+"_status").html('Server error');
									var wppaContinue = false;
									if ( wppaAlwaysContinue < 1 ) {
										wppaContinue = confirm( 'Server error.\nDo you want to continue?' );
										if ( wppaContinue ) {
											if ( wppaAlwaysContinue == 0 ) {
												if ( slug == 'wppa_remake' ||
													 slug == 'wppa_regen_thumbs' ||
													 slug == 'wppa_create_o1_files' ||
													 slug == 'wppa_recup' ) {
													if ( confirm( 'Always continue after server error?' ) ) {
														wppaAlwaysContinue = 1;
													}
												}
												else {
													wppaAlwaysContinue = -1;
												}
											}
										}
									}
									if ( wppaContinue || wppaAlwaysContinue == 1 ) {
										if ( slug == 'wppa_remake' ) {
											wppaAjaxUpdateOptionValue( 'wppa_remake_skip_one', 0 );
										}
										if ( slug == 'wppa_regen_thumbs' ) {
											wppaAjaxUpdateOptionValue( 'wppa_regen_thumbs_skip_one', 0 );
										}
										if ( slug == 'wppa_create_o1_files' ) {
											wppaAjaxUpdateOptionValue( 'wppa_create_o1_files_skip_one', 0 );
										}
										setTimeout('wppaMaintenanceProc(\''+slug+'\', true)', 2000);
									}
								},

					complete: 	function( xhr, status, newurl ) {

								}
	} );
}

function wppaAjaxPopupWindow( slug ) {

	var name;
	switch ( slug ) {
		case 'wppa_list_index':
			name = 'Search index table';
			break;
		case 'wppa_list_errorlog':
			name = 'WPPA+ Error log';
			break;
		case 'wppa_list_rating':
			name = 'Recent ratings';
			break;
		case 'wppa_list_session':
			name = 'Active sessions';
			break;
		case 'wppa_list_comments':
			name = 'Recent comments';
			break;
	}
	var desc = '';
	var width = jQuery(window).width() * 0.9;
	var height = 512;

	var xmlhttp = wppaGetXmlHttp();

	// Make the Ajax send data
	var url = wppaAjaxUrl;
	var data = 'action=wppa&wppa-action=maintenancepopup&slug='+slug;
	data += '&wppa-nonce='+document.getElementById('wppa-nonce').value;

	// Do the Ajax action
	xmlhttp.open('POST', url, false);	// Synchronously !!
	xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp.send(data);

	// Process result
	if (xmlhttp.readyState==4 && xmlhttp.status==200) {
		var result = xmlhttp.responseText;

												var opt = {
													modal:		true,
													resizable: 	true,
													width:		width,
													show: 		{
																	effect: 	"fadeIn",
																	duration: 	800
																},
													closeText: 	wppaCloseText,
												};
												jQuery( '#wppa-modal-container' ).html( result ).dialog( opt ).dialog( "open" );
												jQuery( '.ui-dialog' ).css( {
																				boxShadow: 			'0px 0px 5px 5px #aaaaaa',
																			//	borderRadius: 		wppaBoxRadius+'px',
																				padding: 			'8px',
																				backgroundColor: 	'#cccccc',
																				boxSizing: 			'content-box',
																				zIndex: 			'9999',
																			});
												jQuery( '.ui-dialog-titlebar' ).css(
																						{
																							lineHeight: '0px',
																							height: 	'32px',
																						}
																					)
												jQuery( '.ui-button' ).css(
																			{
																				float: 	'right',
																			});

												jQuery( '.ui-button' ).attr( 'title', wppaCloseText );

		/*
		wnd.document.write(result);
		*/
	}
//	wnd.document.write('</body>');
//	wnd.document.write('</html>');

}

function wppaAjaxUpdateOptionValue(slug, elem, multisel) {

	var xmlhttp = wppaGetXmlHttp();

	// on-unit to process the result
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState != 4) {
			jQuery( '#img_'+slug ).attr( 'src', wppaImageDirectory+'spinner.gif' );
//			document.getElementById('img_'+slug).src = wppaImageDirectory+'spinner.gif';
		}
		else {	// Ready
			var str = wppaTrim(xmlhttp.responseText);
			var ArrValues = str.split("||");

			if (ArrValues[0] != '') {
				alert('The server returned unexpected output:\n'+ArrValues[0]);
			}
			if (xmlhttp.status!=404) {	// No Not found
				switch (ArrValues[1]) {
					case '0':	// No error
						jQuery( '#img_'+slug ).attr( 'src', wppaImageDirectory+'tick.png' );
//						document.getElementById('img_'+slug).src = wppaImageDirectory+'tick.png';
						if ( ArrValues[3] != '' ) alert(ArrValues[3]);
						if ( _wppaRefreshAfter ) {
							_wppaRefreshAfter = false;
							document.location.reload(true);
						}
						break;
					default:
						jQuery( '#img_'+slug ).attr( 'src', wppaImageDirectory+'cross.png' );
//						document.getElementById('img_'+slug).src = wppaImageDirectory+'cross.png';
						if ( ArrValues[3] != '' ) alert(ArrValues[3]);
				}
				jQuery( '#img_'+slug ).attr( 'title', ArrValues[2] );
//				document.getElementById('img_'+slug).title = ArrValues[2];

				// Update cron statusses
				if (  ArrValues[4] ) {
					var tokens = ArrValues[4].split( ';' );
					var i = 0;
					var temp;
					var Old, New;
					while ( i < tokens.length ) {
						temp = tokens[i].split( ':' );
						Old = jQuery( '#'+ temp[0] ).html();
						New = temp[1];
						if ( Old != '' && New == '' ) {
					//		New = '<span style="color:red;font-weight:bold;" onclick="document.location.reload(true)" >Reload page</span>';
							New = '<input type="button" class="button-secundary" style="border-radius:3px;font-size:11px;height:18px;margin: 0 4px;padding:0px;color:red;background-color:pink;" onclick="document.location.reload(true)" value="Reload" />';
						}
						jQuery( '#'+ temp[0] ).html( New );
						i++;
					}
				}
			}
			else {						// Not found
				jQuery( '#img_'+slug ).attr( 'src', wppaImageDirectory+'cross.png' );
//				document.getElementById('img_'+slug).src = wppaImageDirectory+'cross.png';
				document.getElementById('img_'+slug).title = 'Communication error';
			}
			wppaCheckInconsistencies();
		}
	}

	// Make the Ajax url
	eslug = wppaEncode(slug);
	var data = 'action=wppa&wppa-action=update-option&wppa-option='+eslug;
	data += '&wppa-nonce='+document.getElementById('wppa-nonce').value;

	if ( elem != 0 ) {
		if ( typeof( elem ) == 'number' ) {
			data += '&value='+elem;
		}
		else if ( multisel ) {
			data += '&value='+wppaGetSelectionEnumByClass('.'+slug, ',');
		}
		else {
			data += '&value='+wppaEncode(elem.value);
		}
	}

//if (!confirm('Do '+wppaAjaxUrl+'\n'+data)) return;	// Diagnostic

	// Do the Ajax action
	xmlhttp.open('POST',wppaAjaxUrl,true);
	xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp.send(data);
}

function wppaEncode(xtext) {

	var text, result;

	if (typeof(xtext)=='undefined') return;
	if (typeof(xtext)=='number') return xtext;

	text = xtext;
	result = text.replace(/#/g, '||HASH||');
	text = result;
	result = text.replace(/&/g, '||AMP||');
	text = result;
//	result = text.replace(/+/g, '||PLUS||');
	var temp = text.split('+');
	var idx = 0;
	result = '';
	while (idx < temp.length) {
		result += temp[idx];
		idx++;
		if (idx < temp.length) result += '||PLUS||';
	}

//	alert('encoded result='+result);
	return result;
}

// Check conflicting settings, Autosave version only
function wppaCheckInconsistencies() {

	// Uses thumb popup and thumb lightbox?
	if ( jQuery('#use_thumb_popup').prop('checked') && jQuery('#thumb_linktype').val() == 'lightbox' ) {
		jQuery('.popup-lightbox-err').css('display', '');
	}
	else {
		jQuery('.popup-lightbox-err').css('display', 'none');
	}
}

// Get the http request object
function wppaGetXmlHttp() {
	if (window.XMLHttpRequest) {		// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	}
	else {								// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	return xmlhttp;
}

function wppaPhotoStatusChange(id) {
	// Init
	jQuery('#psdesc-'+id).css({display: 'none'});
	if ( ! jQuery('#status-'+id) ) return;

	elm = document.getElementById('status-'+id);

	if ( elm.value == 'pending' || elm.value == 'scheduled' ) {
		jQuery('#photoitem-'+id).css({backgroundColor: '#ffebe8', borderColor: '#cc0000'});
	}
	if (elm.value=='publish') {
		jQuery('#photoitem-'+id).css({backgroundColor:'#ffffe0', borderColor:'#e6db55'});
	}
	if (elm.value=='featured') {
		jQuery('#photoitem-'+id).css({backgroundColor: '#e0ffe0', borderColor: '#55ee55'});
		var temp = document.getElementById('pname-'+id).value;
		var name = temp.split('.')
		if (name.length > 1) {
			var i = 0;
			while ( i< name.length ) {
				if (name[i] == 'jpg' || name[i] == 'JPG' ) {
					jQuery('#psdesc-'+id).css({display: ''});
				}
				i++;
			}
		}
	}
	if (elm.value=='gold') {
		jQuery('#photoitem-'+id).css({backgroundColor:'#eeeecc', borderColor:'#ddddbb'});
	}
	if (elm.value=='silver') {
		jQuery('#photoitem-'+id).css({backgroundColor:'#ffffff', borderColor:'#eeeeee'});
	}
	if (elm.value=='bronze') {
		jQuery('#photoitem-'+id).css({backgroundColor:'#ddddbb', borderColor:'#ccccaa'});
	}

	if ( elm.value == 'scheduled' ) {
		jQuery( '.wppa-datetime-'+id ).css('display', ''); //prop( 'disabled', false );
	}
	else {
		jQuery( '.wppa-datetime-'+id ).css('display', 'none'); //prop( 'disabled', true );
	}

	// schedule delete
	if ( jQuery( '#scheduledel-' + id ).prop( 'checked' ) ) {
		jQuery( '.wppa-del-datetime-' + id ).css( 'display', '' );
	}
	else {
		jQuery( '.wppa-del-datetime-' + id ).css( 'display', 'none' );
	}
}

function wppaSetComBgCol( id ) {
	var status = jQuery( '#com-stat-' + id ).val();

	if ( status == 'approved' ) {
		jQuery( '#com-tr-' + id ).css({backgroundColor:'#ffffe0'});
	}
	else {
		jQuery( '#com-tr-' + id ).css({backgroundColor:'#ffebe8'});
	}
}

function wppaCheckLinkPageErr(slug) {

	var type = 'nil';
		if ( document.getElementById(slug+'_linktype') ) type = document.getElementById(slug+'_linktype').value;
	var page = document.getElementById(slug+'_linkpage').value;

	if ( page == '0' && ( type == 'nil' || type == 'photo' || type == 'single' || type == 'album' || type == 'content' || type == 'slide' || type == 'plainpage' )) {
		jQuery('#'+slug+'-err').css({display:''});
	}
	else {
		jQuery('#'+slug+'-err').css({display:'none'});
	}
}

function wppaAddCat(val, id) {
	wppaAddTag(val, id);
}

function wppaAddTag(val, id) {
	var elm = document.getElementById(id);
	if ( val ) {
		if ( elm.value ) {
			elm.value += ','+val;
		}
		else {
			elm.value = val;
		}
		if ( val == '-clear-' ) {
			elm.value = '';
		}
	}
}

function wppaRefresh(label) {
	var oldurl 	= new String(document.location);
	var temp 	= oldurl.split("#");
	var newurl 	= temp[0]+'#'+label;

	document.location = newurl;
}
function wppaReload(arg) {
	if ( arg ) {
		url = document.location.href.split('#');

		document.location.href = url[0] + arg;
		setTimeout( function(){document.location.reload( true );}, 10 );
	}
	else {
		document.location.reload( true );
	}
}
/*
function wppaTrim (str, chr) {
	if ( ! chr ) {
	*/
//		return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
/*	}
	else {
		// Ltrim
		while ( str.substr( 0,1 ) == chr ) {
			str = str.substr( 1 );
		}
		// Rtrim
		while ( str.substr( str.length-1, 1 ) == chr ) {
			str = str.substr( 0, str.length-1 );
		}
	}
	return str;
}
*/
var wppaFeCount = 0;
function wppaFeAjaxLog(key) {

	if ( key == 'in' ) {
		if ( wppaFeCount == 0 ) {
			jQuery('#wppa-fe-exit').css('display', 'none');
		}
		wppaFeCount++;
		jQuery('#wppa-fe-count').html(wppaFeCount);
	}
	if ( key == 'out' ) {
		if ( wppaFeCount == 1 ) {
			jQuery('#wppa-fe-count').html('');
			jQuery('#wppa-fe-exit').css('display', 'inline');
			wppaFeCount--;
		}
		if ( wppaFeCount > 1 ) {
			wppaFeCount--;
			jQuery('#wppa-fe-count').html(wppaFeCount);
		}
	}
}

function wppaArrayToEnum( arr, sep ) {

	// Step 1. Sort Ascending Numeric
	temp = arr.sort(function(a, b){return a-b});

	// Init
	var result = '';
	var lastitem = -1;
	var previtemp = -2;
	var lastitemp = 0;
	var isrange = false;
	var i = 0;
	var item;
	while ( i < arr.length ) {
		item = arr[i].valueOf();
		if ( item != 0 ) {
			lastitemp = lastitem;
			lastitemp++;
			if ( item == lastitemp ) {
				isrange = true;
			}
			else {
				if ( isrange ) {	// Close range
					if ( lastitem == previtemp ) {	// Range is x . (x+1)
						result += sep + lastitem + sep + item;
					}
					else {
						result += sep + sep + lastitem + sep + item;
					}
					isrange = false;
				}
				else {				// Add single item
					result += sep + item;
				}
			}
			if ( ! isrange ) {
				previtemp = item;
				previtemp++;
			}
			lastitem = item;
		}
		i++;
	}
	if ( isrange ) {	// Don't forget the last if it ends in a range
		result += '..' + lastitem;
	}

	// ltrim .
	while ( result.substr(0,1) == '.' ) result = result.substr(1);

	// ltrim sep
	while ( result.substr(0,1) == sep ) result = result.substr(1);

	return result;
}

function wppaGetSelEnumToId( cls, id ) {
	p = jQuery( '.'+cls );
	var pararr = [];
	i = 0;
	j = 0;
	while ( i < p.length ) {
		if ( p[i].selected ) {
			pararr[j] = p[i].value;
			j++;
		}
		i++;
	}
	jQuery( '#'+id ).val( wppaArrayToEnum( pararr, '.' ) );
}

function wppaGetSelectionEnumByClass( clas, sep ) {
var p;
var parr = [];
var i = 0;
var j = 0;
var result = '';

	if ( ! sep ) {
		sep = '.';
	}
	p = jQuery( clas );
	i = 0;
	j = 0;
	while ( i < p.length ) {
		if ( p[i].selected ) {
			parr[j] = p[i].value;
			j++;
		}
		i++;
	}
	result = wppaArrayToEnum( parr, sep );

	return result;
}

function wppaEditSearch( url, id ) {

	var ss = jQuery( '#'+id ).val();
	if ( ss.length == 0 ) {
		alert('Please enter searchstring');
	}
	else {
		document.location.href = url + '&wppa-searchstring=' + ss;
	}
}

function wppaEditTrash( url ) {
	document.location.href = url;
}

function wppaExportDbTable( table ) {
	jQuery.ajax( { 	url: 		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=export-table' +
								'&table=' + table,
					async: 		true,
					type: 		'GET',
					timeout: 	100000,
					beforeSend: function( xhr ) {
									jQuery( '#' + table + '-spin' ).css( 'display', 'inline' );
								},
					success: 	function( result, status, xhr ) {
									var ArrValues = result.split( "||" );
									if ( ArrValues[1] == '0' ) {	// Ok, no error

										// Publish result
										document.location = ArrValues[2];
									}
									else {

										// Show error
										alert( 'Error: '+ArrValues[1]+'\n\n'+ArrValues[2] );
									}
								},
					error: 		function( xhr, status, error ) {
									alert( 'Export Db Table ' + table + ' failed. Error = ' + error + ', status = ' + status );
								},
					complete: 	function( xhr, status, error ) {
									jQuery( '#' + table + '-spin' ).css( 'display', 'none' );
								}
				} );

}

function wppaDismissAdminNotice(notice, elm) {

	wppaAjaxUpdateOptionCheckBox(notice, elm);
	jQuery('#wppa-wr-').css('display','none');

}

function wppaAjaxUpdateTogo(slug) {
	jQuery.ajax( { 	url:		wppaAjaxUrl,
					data: 		'action=wppa' +
								'&wppa-action=gettogo' +
								'&slug=' + slug,
					async: 		true,
					type: 		'GET',
					timeout: 	100000,
					beforesend: function( xhr ) {
								},
					success: 	function( result, status, xhr ) {

									// Split status and togo
									var data = result.split('|');

									// Update togo
									jQuery( '#' + slug + '_togo' ).html( data[0] );

									// Update status when not changing to empty, else request user to reload page
									var Old = jQuery( '#' + slug + '_status' ).html();
									var New = data[1];
									if ( Old != '' && New == '' ) {
								//		New = '<span style="color:red;font-weight:bold;" onclick="document.location.reload(true)" >Reload page</span>';
										New = '<input type="button" class="button-secundary" style="border-radius:3px;font-size:11px;height:18px;margin: 0 4px;padding:0px;color:red;background-color:pink;" onclick="document.location.reload(true)" value="Reload" />';
									}
									jQuery( '#' + slug + '_status' ).html( New );

									setTimeout( function() {wppaAjaxUpdateTogo(slug);}, 5000 );
								},
					error: 		function( xhr ) {
								},
					complete: 	function( xhr ) {
								}
				} );
}

// The js equivalence of php's NOT (!)
function wppaIsEmpty( str ) {

	if ( str == null ) return true;
	if ( typeof( str ) == 'undefined' ) return true;
	if ( str == '' ) return true;
	if ( str == false ) return true;
	if ( str == 0 ) return true;

	return false;
}