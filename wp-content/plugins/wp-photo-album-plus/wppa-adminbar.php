<?php
/* wppa-adminbar.php
* Package: wp-photo-album-plus
*
* enhances the admin bar with wppa+ menu
* version 6.6.29
*
*/

add_action( 'admin_bar_menu', 'wppa_admin_bar_menu', 97 );

function wppa_admin_bar_menu() {
	global $wp_admin_bar;
	global $wpdb;

	$wppaplus = 'wppa-admin-bar';

	$menu_items = false;

	// Pending comments
	$com_pend = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_COMMENTS."` WHERE `status` = 'pending'" );
	if ( $com_pend ) $com_pending = '&nbsp;<span id="ab-awaiting-mod" class="pending-count">'.$com_pend.'</span>';
	else $com_pending = '';

	// Pending uploads
	$upl_pend = $wpdb->get_var( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `status` = 'pending'" );
	if ( $upl_pend ) $upl_pending = '&nbsp;<span id="ab-awaiting-mod" class="pending-count">'.$upl_pend.'</span>';
	else $upl_pending = '';

	// Tot
	$tot_pend = '0';
	if ( current_user_can('administrator') ) $tot_pend += $com_pend;
	if ( current_user_can('wppa_admin') ) $tot_pend += $upl_pend;
	if ( $tot_pend ) $tot_pending = '&nbsp;<span id="ab-awaiting-mod" class="pending-count">'.$tot_pend.'</span>';
	else $tot_pending = '';

	if ( current_user_can( 'wppa_admin' ) ) {
		$menu_items['admin'] = array(
			'parent' => $wppaplus,
			'title'  => __( 'Album Admin', 'wp-photo-album-plus' ) . $upl_pending,
			'href'   => admin_url( 'admin.php?page=wppa_admin_menu' )
		);
	}
	if ( current_user_can( 'wppa_upload' ) ) {
		$menu_items['upload'] = array(
			'parent' => $wppaplus,
			'title'  => __( 'Upload Photos', 'wp-photo-album-plus' ),
			'href'   => admin_url( 'admin.php?page=wppa_upload_photos' )
		);
		if ( ! current_user_can( 'wppa_admin' ) && wppa_opt( 'upload_edit' ) != 'none' ) {
			$menu_items['edit'] = array(
				'parent' => $wppaplus,
				'title'  => __( 'Edit Photos' , 'wp-photo-album-plus' ),
				'href'   => admin_url( 'admin.php?page=wppa_edit_photo' )
			);
		}
	}
	if ( current_user_can( 'wppa_import' ) ) {
		$menu_items['import'] = array(
			'parent' => $wppaplus,
			'title'  => __( 'Import Photos', 'wp-photo-album-plus' ),
			'href'   => admin_url( 'admin.php?page=wppa_import_photos' )
		);
	}
	if ( current_user_can( 'wppa_moderate' ) ) {
		$menu_items['moderate'] = array(
			'parent' => $wppaplus,
			'title'	 => __( 'Moderate Photos', 'wp-photo-album-plus' ) . $tot_pending,
			'href'   => admin_url( 'admin.php?page=wppa_moderate_photos' )
		);
	}
	if ( current_user_can( 'wppa_export' ) ) {
		$menu_items['export'] = array(
			'parent' => $wppaplus,
			'title'  => __( 'Export Photos', 'wp-photo-album-plus' ),
			'href'   => admin_url( 'admin.php?page=wppa_export_photos' )
		);
	}
	if ( current_user_can( 'wppa_settings' ) ) {
		$menu_items['settings'] = array(
			'parent' => $wppaplus,
			'title'  => __( 'Settings', 'wp-photo-album-plus' ),
			'href'   => admin_url( 'admin.php?page=wppa_options' )
		);
	}
	if ( current_user_can( 'wppa_potd' ) ) {
		$menu_items['sidebar'] = array(
			'parent' => $wppaplus,
			'title'  => __( 'Photo of the day', 'wp-photo-album-plus' ),
			'href'   => admin_url( 'admin.php?page=wppa_photo_of_the_day' )
		);
	}
	if ( current_user_can( 'wppa_comments' ) ) {
		$menu_items['comments'] = array(
			'parent' => $wppaplus,
			'title'  => __( 'Comments', 'wp-photo-album-plus' ) . $com_pending,
			'href'   => admin_url( 'admin.php?page=wppa_manage_comments' )
		);
	}

	$menu_items['opajaap'] = array(
		'parent' => $wppaplus,
		'title'  => __( 'Documentation', 'wp-photo-album-plus' ),
		'href'   => 'http://wppa.nl'
	);

	if ( current_user_can( 'administrator' ) ) {
		if ( get_option( 'wppa_logfile_on_menu' ) == 'yes' ) {
			$menu_items['logfile'] = array(
				'parent' => $wppaplus,
				'title'  => __( 'Logfile', 'wp-photo-album-plus' ),
				'href'   => admin_url( 'admin.php?page=wppa_log' )
			);
		}
	}

	// Add top-level item
	$wp_admin_bar->add_menu( array(
		'id'    => $wppaplus,
		'title' => __( 'Photo Albums', 'wp-photo-album-plus' ) . $tot_pending,
		'href'  => ''
	) );

	// Loop through menu items
	if ( $menu_items ) foreach ( $menu_items as $id => $menu_item ) {

		// Add in item ID
		$menu_item['id'] = 'wppa-' . $id;

		// Add meta target to each item where it's not already set, so links open in new tab
		if ( ! isset( $menu_item['meta']['target'] ) )
			$menu_item['meta']['target'] = '_self';

		// Add class to links that open up in a new tab
		if ( '_blank' === $menu_item['meta']['target'] ) {
			if ( ! isset( $menu_item['meta']['class'] ) )
				$menu_item['meta']['class'] = '';
			$menu_item['meta']['class'] .= 'wppa-' . 'new-tab';
		}

		// Add item
		$wp_admin_bar->add_menu( $menu_item );
	}

	// Add New -> Photo Album
	if ( current_user_can( 'wppa_admin' ) ) {

		$menu_item = array( 'id' 		=> 'wppa-album-new',
							'parent' 	=> 'new-content-default',
							'title' 	=> __( 'Album', 'wp-photo-album-plus' ),
							'href' 		=> admin_url( 'admin.php?page=wppa_admin_menu&tab=edit&edit_id=new&wppa_nonce=' . wp_create_nonce( 'wppa_nonce' ) ),
							);
		// Add item
		$wp_admin_bar->add_menu( $menu_item );
	}
}