<?php
/* wppa-cron.php
* Package: wp-photo-album-plus
*
* Contains all cron functions
* Version 6.8.00
*
*
*/

// Are we in a cron job?
function wppa_is_cron() {

	if ( isset( $_GET['doing_wp_cron'] ) ) {
		return $_GET['doing_wp_cron'];
	}
	if ( defined( 'DOING_CRON' ) ) {
		return DOING_CRON;
	}
	return false;
}

// Activate our maintenance hook
add_action( 'wppa_cron_event', 'wppa_do_maintenance_proc', 10, 1 );

// Schedule maintenance proc
function wppa_schedule_maintenance_proc( $slug, $from_settings_page = false ) {
global $is_reschedule;

	// Are we temp disbled?
	if ( wppa_switch( 'maint_ignore_cron' ) ) {
		return;
	}

	// Schedule cron job
	if ( ! wp_next_scheduled( 'wppa_cron_event', array( $slug ) ) ) {
		if ( $is_reschedule || $from_settings_page ) {
			$delay = 5;
		}
		else switch ( $slug ) {
			case 'wppa_remake_index_photos':	// one hour
				$delay = 3600;
				break;
			case 'wppa_cleanup_index':			// 3 hours
				$delay = 10800;
				break;
			default:
				$delay = 30;					// 30 sec.
		}
		wp_schedule_single_event( time() + $delay, 'wppa_cron_event', array( $slug ) );
		$backtrace = debug_backtrace();
		$args = '';
		if ( is_array( $backtrace[1]['args'] ) ) {
			foreach( $backtrace[1]['args'] as $arg ) {
				if ( $args ) {
					$args .= ', ';
				}
				$args .= str_replace( "\n", '', var_export( $arg, true ) );
			}
			$args = trim( $args );
			if ( $args ) {
				$args = ' ' . str_replace( ',)', ', )', $args ) . ' ';
			}
		}
		elseif ( $backtrace[1]['args'] ) {
			$args = " '" . $backtrace[1]['args'] . "' ";
		}

		$re = $is_reschedule ? 're-' : '';
		wppa_log( 'Cron', '{b}' . $slug . '{/b} ' . $re . 'scheduled by {b}' . $backtrace[1]['function'] . '(' . $args . '){/b} on line {b}' . $backtrace[0]['line'] . '{/b} of ' . basename( $backtrace[0]['file'] ) . ' called by ' . $backtrace[2]['function'] );
	}

	// Update appropriate options
	update_option( $slug . '_status', 'Cron job' );
	update_option( $slug . '_user', 'cron-job' );

	// Inform calling Ajax proc about the results
	if ( $from_settings_page ) {
		echo '||' . $slug . '||' . 'Cron job' . '||0||reload';
	}

}

// Is cronjob crashed?
function wppa_is_maintenance_cron_job_crashed( $slug ) {

	// Asume not
	$result = false;

	// If there is a last timestamp longer than 5 minutes ago...
	$lasttime = get_option( $slug.'_lasttimestamp', '0' );
	if ( $lasttime && $lasttime < ( time() - 300 ) ) {

		// And the user is cron
		if ( get_option( $slug . '_user' ) == 'cron-job' ) {

			// And proc is not scheduled
			if ( ! wp_next_scheduled( 'wppa_cron_event', array( $slug ) ) ) {

				// It is crashed
				$result = true;
			}
		}
	}

	// No last timestamp, maybe never started?
	elseif ( ! $lasttime ) {

		// Nothing done yet
		if ( get_option( $slug . 'last' ) == '0' ) {

			// Togo not calculated yet
			if ( get_option( $slug . 'togo' ) == '' ) {

				// If the user is cron
				if ( get_option( $slug . '_user' ) == 'cron-job' ) {

					// And proc is not scheduled
					if ( ! wp_next_scheduled( 'wppa_cron_event', array( $slug ) ) ) {

						// It is crashed
						$result = true;
					}
				}
			}
		}
	}

	return $result;
}

// Activate our cleanup session hook
add_action( 'wppa_cleanup', 'wppa_do_cleanup' );

// Schedule cleanup session database table
function wppa_schedule_cleanup( $now = false ) {

	// Are we temp disbled?
	if ( wppa_switch( 'maint_ignore_cron' ) ) {
		return;
	}

	// Immediate action requested?
	if ( $now ) {
		wp_schedule_single_event( time() + 1, 'wppa_cleanup' );
	}
	// Schedule cron job
	if ( ! wp_next_scheduled( 'wppa_cleanup' ) ) {
		wp_schedule_event( time(), 'hourly', 'wppa_cleanup' );
	}
}

// The actual cleaner
function wppa_do_cleanup() {
global $wpdb;

	// Are we temp disbled?
	if ( wppa_switch( 'maint_ignore_cron' ) ) {
		return;
	}

	wppa_log( 'Cron', '{b}wppa_cleanup{/b} started.' );

	// Cleanup session db table
	$lifetime 	= 3600;			// Sessions expire after one hour
	$savetime 	= 86400;		// Save session data for 24 hour
	$expire 	= time() - $lifetime;
	$purge 		= time() - $savetime;
	$wpdb->query( $wpdb->prepare( "UPDATE `" . WPPA_SESSION . "` SET `status` = 'expired' WHERE `timestamp` < %s", $expire ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPPA_SESSION ."` WHERE `timestamp` < %s", $purge ) );

	// Delete obsolete spam
	$spammaxage = wppa_opt( 'spam_maxage' );
	if ( $spammaxage != 'none' ) {
		$time = time();
		$obsolete = $time - $spammaxage;
		$iret = $wpdb->query( $wpdb->prepare( "DELETE FROM `".WPPA_COMMENTS."` WHERE `status` = 'spam' AND `timestamp` < %s", $obsolete ) );
		if ( $iret ) wppa_update_option( 'wppa_spam_auto_delcount', get_option( 'wppa_spam_auto_delcount', '0' ) + $iret );
	}

	// Re-animate crashed cronjobs
	wppa_re_animate_cron();

	// Find lost photos, update their album to -9, meaning trashed
	$album_ids = $wpdb->get_col( "SELECT `id` FROM `" . WPPA_ALBUMS . "`" );
	if ( ! empty( $album_ids ) ) {
		$lost = $wpdb->query( "UPDATE `" . WPPA_PHOTOS . "` SET `album` = '-9' WHERE `album` > '0' AND `album` NOT IN ( " . implode( ',', $album_ids ) . " ) " );
	}

	// Remove 'deleted' photos from system
	$dels = $wpdb->get_col( "SELECT `id` FROM `".WPPA_PHOTOS."` WHERE `album` <= '-9' AND `modified` < " . ( time() - 3600 ) );
	foreach( $dels as $del ) {
		wppa_delete_photo( $del );
		wppa_log( 'Cron', 'Removed photo {b}' . $del . '{/b} from system' );
	}

	// Re-create permalink htaccess file
	wppa_create_pl_htaccess();

	// Cleanup index
//	wppa_index_compute_skips();
//	wppa_schedule_maintenance_proc( 'wppa_cleanup_index' );

	// Retry failed mails
	if ( wppa_opt( 'retry_mails' ) ) {

		$failed_mails = get_option( 'wppa_failed_mails' );
		if ( is_array( $failed_mails ) ) {

			foreach( array_keys( $failed_mails ) as $key ) {

				$mail = $failed_mails[$key];
				$mess = $mail['message'] . '(retried mail)';

				// Retry
				if ( wp_mail( $mail['to'], $mail['subj'], $mess, $mail['headers'], $mail['att'] ) ) {

					// Set counter to 0
					$failed_mails[$key]['retry'] = '0';
				}
				else {

					// Decrease retry counter
					$failed_mails[$key]['retry']--;
					wppa_log( 'Cron', 'Retried mail to ' . $mail['to'] . ' failed. Tries to go = ' . $failed_mails[$key]['retry'] );
				}
			}

			// Cleanup
			foreach( array_keys( $failed_mails ) as $key ) {
				if ( $failed_mails[$key]['retry'] < '1' ) {
					unset( $failed_mails[$key] );
				}
			}
		}

		// Store updated failed mails
		update_option( 'wppa_failed_mails', $failed_mails );
	}

	// Cleanup iptc and exif
	wppa_iptc_clean_garbage();
	wppa_exif_clean_garbage();

	// Cleanup qr cache
	if ( is_dir( WPPA_UPLOAD_PATH . '/qr' ) ) {
		$qrs = glob( WPPA_UPLOAD_PATH . '/qr/*.svg' );
		if ( ! empty( $qrs ) ) {
			$count = count( $qrs );
			if ( $count > 250 ) {
				foreach( $qrs as $qr ) @ unlink( $qr );
				wppa_log( 'Cron', $count . ' qr cache files removed' );
			}
		}
	}

	wppa_log( 'Cron', '{b}wppa_cleanup{/b} completed.' );

	// Redo after 5 minutes
//	wp_schedule_single_event( time() + 300, 'wppa_cleanup' );
//	wppa_log( 'Cron', '{b}wppa_cleanup{/b} re-scheduled' );
}

// Activate treecount update proc
add_action( 'wppa_update_treecounts', 'wppa_do_update_treecounts' );

function wppa_schedule_treecount_update() {

	// Are we temp disbled?
	if ( wppa_switch( 'maint_ignore_cron' ) ) {
		return;
	}

	// Schedule cron job
	if ( ! wp_next_scheduled( 'wppa_update_treecounts' ) ) {
		wp_schedule_single_event( time() + 10, 'wppa_update_treecounts' );
	}
}

function wppa_do_update_treecounts() {
global $wpdb;

	// Are we temp disbled?
	if ( wppa_switch( 'maint_ignore_cron' ) ) {
		return;
	}

	$start = time();

	$albs = $wpdb->get_col( "SELECT `id` FROM `" . WPPA_ALBUMS . "` WHERE `a_parent` < '1' ORDER BY `id`" );

	foreach( $albs as $alb ) {
		$treecounts = wppa_get_treecounts_a( $alb );
		if ( $treecounts['needupdate'] ) {
			wppa_verify_treecounts_a( $alb );
			wppa_log( 'Cron', 'Cron fixed treecounts for ' . $alb );
		}
		if ( time() > $start + 15 ) {
			wppa_schedule_treecount_update();
			exit();
		}
	}
}

function wppa_re_animate_cron() {
global $wppa_cron_maintenance_slugs;

	// Are we temp disbled?
	if ( wppa_switch( 'maint_ignore_cron' ) ) {
		return;
	}

	foreach ( $wppa_cron_maintenance_slugs as $slug ) {
		if ( wppa_is_maintenance_cron_job_crashed( $slug ) ) {
			$last = get_option( $slug . '_last' );
			update_option( $slug . '_last', $last + 1 );
			wppa_schedule_maintenance_proc( $slug );
			wppa_log( 'Cron', '{b}' . $slug . '{/b} re-animated at item {b}#' . $last . '{/b}' );
		}
	}

}