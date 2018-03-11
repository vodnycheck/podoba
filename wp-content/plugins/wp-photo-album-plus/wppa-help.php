<?php
/* wppa-help.php
* Pachkage: wp-photo-album-plus
*
* admin help page
* version 6.4.20
*/ 

function _wppa_page_help() {
global $wppa_revno;


?>
	<div class="wrap">

		<h3><?php echo sprintf(__('You will find all information and examples on the new %s%s%s site', 'wp-photo-album-plus'), '<a href="http://wppa.nl/" target="_blank" >', esc_attr( __( 'Docs & Demos', 'wp-photo-album-plus' ) ), '</a>' ) ?></h3>

		<h3><?php _e('About and credits', 'wp-photo-album-plus'); ?></h3>
		<p>
			<?php _e('WP Photo Album Plus is extended with many new features and is maintained by J.N. Breetvelt, a.k.a. OpaJaap', 'wp-photo-album-plus'); ?><br />
			<?php _e('Thanx to R.J. Kaplan for WP Photo Album 1.5.1.', 'wp-photo-album-plus'); ?><br />
			<?php _e('Thanx to E.S. Rosenberg for programming tips on security issues.', 'wp-photo-album-plus'); ?><br />
			<?php _e('Thanx to Pavel &#352;orejs for the Numbar code.', 'wp-photo-album-plus'); ?><br />
			<?php _e('Thanx to the users who reported bugs and asked for enhancements. Without them WPPA should not have been what it is now!', 'wp-photo-album-plus'); ?>
		</p>
		
		<h3><?php _e('Licence', 'wp-photo-album-plus'); ?></h3>
		<p>
			<?php _e('WP Photo Album is released under the', 'wp-photo-album-plus'); ?> <a href="http://www.gnu.org/licenses/gpl-2.0.html">GPLv2 or later</a> <?php _e('licence.', 'wp-photo-album-plus'); ?>
		</p>
		
	</div>
<?php
}
