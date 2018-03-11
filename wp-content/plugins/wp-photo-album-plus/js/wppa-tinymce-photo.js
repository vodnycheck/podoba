/* wppa-tinymce-photo.js
* Pachkage: wp-photo-album-plus
*
*
* Version 6.7.09
*
*/

// Add the wppa button to the mce editor
tinymce.PluginManager.add('wppaphoto', function(editor, url) {

		function openWppaPhotoShortcodeGenerator() {
			// triggers the thickbox
			var width = jQuery(window).width(), H = jQuery(window).height(), W = ( 720 < width ) ? 720 : width;
			W = W - 80;
			H = jQuery(window).height();
			H = H - 120;
			tb_show( 'WPPA+ Insert photo', '#TB_inline?width=' + W + '&height=' + H + '&inlineId=wppaphoto-form' );
			wppaPhotoEvaluate();
		}

		editor.addButton('wppa_photo_button', {
			image: wppaImageDirectory+'camera32.png',
			tooltip: 'WPPA+ Insert photo',
			onclick: openWppaPhotoShortcodeGenerator
		});

});

// executes this when the DOM is ready
jQuery(function(){

	// creates a form to be displayed everytime the button is clicked
	var xmlhttp;
	if ( window.XMLHttpRequest ) {		// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp = new XMLHttpRequest();
	}
	else {								// code for IE6, IE5
		xmlhttp=new ActiveXObject( "Microsoft.XMLHTTP" );
	}

	// wppa-ajax.php calls wppa_make_tinymce_dialog(); which is located in wppa-tinymce.php
	var url = wppaAjaxUrl+'?action=wppa&wppa-action=tinymcephotodialog';

	xmlhttp.open("GET",url,true);
	xmlhttp.send();
	xmlhttp.onreadystatechange=function() {
		if  (xmlhttp.readyState == 4 && xmlhttp.status!=404 ) {
			var formtext = xmlhttp.responseText;

			var form = jQuery(formtext);

			var table = form.find('table');
			form.appendTo('body').hide();

			// handles the click event of the submit button
			form.find('#wppaphoto-submit').click(function(){

				// Get the shortcode from the preview/edit box
				newShortcode = jQuery( '#wppaphoto-shortcode-preview' ).val();

				// Filter
				newShortcode = newShortcode.replace(/&quot;/g, '"');

				// inserts the shortcode into the active editor
				tinyMCE.activeEditor.execCommand('mceInsertContent', 0, newShortcode);

				// Switch back to own photos only
				wppaMyPhotoSelection=true;
				jQuery('#wppaphoto-allphoto-tr').hide();
				jQuery('#wppaphoto-myphoto-tr').show();
				jQuery('#wppaphoto-photo-preview').html('');

				// closes Thickbox
				tb_remove();
			});
		}
	}
});

var wppaMyPhotoSelection = true;
function wppaPhotoEvaluate() {

	// Assume shortcode complete
	var shortcodeOk = true;
	var shortcode;
	var myAll;

	// Photo
	if ( wppaMyPhotoSelection ) {
		myAll = 'my';
	}
	else {
		myAll = 'all';
	}
	photo = jQuery('#wppaphoto-'+myAll+'photo').val();

	if ( ! wppaIsEmpty( photo ) ) {
		id = photo.replace(/\//g,'');
		id = id.split('.');
		id = id[0];
		jQuery('#wppaphoto-photo-preview-tr').show();
		wppaTinyMceBasicPhotoPreview( photo );
		jQuery('#wppaphoto-'+myAll+'photo').css('color', '#070');
		shortcode = '[photo ' + id + ']';
	}
	else {
		jQuery('#wppaphoto-'+myAll+'photo').css('color', '#700');
		shortcode = '';
	}

	// Display shortcode
	shortcode = shortcode.replace(/"/g, '&quot;');
	var html = '<input type="text" id="wppaphoto-shortcode-preview" style="background-color:#ddd; width:100%; height:26px;" value="'+shortcode+'" />';
	jQuery( '#wppaphoto-shortcode-preview-container' ).html( html );

	// Is shortcode complete?
	shortcodeOk = ! wppaIsEmpty( photo );

	// Display the right button
	if ( shortcodeOk ) {
		jQuery('#wppaphoto-submit').show();
		jQuery('#wppaphoto-submit-notok').hide();
	}
	else {
		jQuery('#wppaphoto-submit').hide();
		jQuery('#wppaphoto-submit-notok').show();
	}
}

function wppaTinyMceBasicPhotoPreview( id ) {

	if ( id == '#potd' ) {
		jQuery('#wppaphoto-photo-preview').html(wppaNoPreview); 	// No preview
	}
	else if ( id.indexOf('xxx') != -1 ) { 				// its a video
		var idv = id.replace('xxx', '');
		jQuery('#wppaphoto-photo-preview').html('<video preload="metadata" style="max-width:400px; max-height:300px; margin-top:3px;" controls>'+
													'<source src="'+wppaPhotoDirectory+idv+'mp4" type="video/mp4">'+
													'<source src="'+wppaPhotoDirectory+idv+'ogg" type="video/ogg">'+
													'<source src="'+wppaPhotoDirectory+idv+'ogv" type="video/ogg">'+
													'<source src="'+wppaPhotoDirectory+idv+'webm" type="video/webm">'+
												'</video>');
	}
	else {
		jQuery('#wppaphoto-photo-preview').html('<img src="'+wppaPhotoDirectory+id+'" style="max-width:400px; max-height:300px;" />');
	}
}

function wppaDisplaySelectedFile(filetagid, displaytagid) {

	var theFile = jQuery('#'+filetagid);
	var result 	= theFile[0].files[0].name;

	jQuery('#'+displaytagid).val('Upload '+result);
}

