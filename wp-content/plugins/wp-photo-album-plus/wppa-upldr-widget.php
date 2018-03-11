<?php
/* wppa-upldr-widget.php
* Package: wp-photo-album-plus
*
* display a list of users linking to their photos
* Version 6.7.01
*/

if ( ! defined( 'ABSPATH' ) ) die( "Can't load this file directly" );

class UpldrWidget extends WP_Widget {

    /** constructor */
    function __construct() {
		$widget_ops = array( 'classname' => 'wppa_upldr_widget', 'description' => __( 'Display which users uploaded how many photos', 'wp-photo-album-plus' ) );
		parent::__construct( 'wppa_upldr_widget', __( 'WPPA+ Uploader Photos', 'wp-photo-album-plus' ), $widget_ops );
    }

	/** @see WP_Widget::widget */
    function widget($args, $instance) {
		global $wpdb;

		require_once(dirname(__FILE__) . '/wppa-links.php');
		require_once(dirname(__FILE__) . '/wppa-styles.php');
		require_once(dirname(__FILE__) . '/wppa-functions.php');
		require_once(dirname(__FILE__) . '/wppa-thumbnails.php');
		require_once(dirname(__FILE__) . '/wppa-boxes-html.php');
		require_once(dirname(__FILE__) . '/wppa-slideshow.php');
		wppa_initialize_runtime();

        wppa( 'in_widget', 'upldr' );
		wppa_bump_mocc();

		extract( $args );

		$instance 		= wp_parse_args( (array) $instance, array(
														'title' 	=> __( 'Uploader Photos', 'wp-photo-album-plus' ),
														'sortby' 	=> 'name',
														'ignore' 	=> 'admin',
														'parent' 	=> ''
														) );
 		$widget_title 		= apply_filters( 'widget_title', $instance['title'] );
		$page 				= in_array( 'album', wppa( 'links_no_page' ) ) ? '' : wppa_get_the_landing_page('upldr_widget_linkpage', __('User uploaded photos', 'wp-photo-album-plus'));
		$ignorelist			= explode(',', $instance['ignore']);
		$upldrcache 		= wppa_get_upldr_cache();
		$needupdate 		= false;
		$users 				= wppa_get_users();
		$workarr 			= array();
		$showownercount 	= wppa_checked( $instance['showownercount'] );
		$showphotocount 	= wppa_checked( $instance['showphotocount'] );
		$total_ownercount 	= 0;
		$total_photocount 	= 0;

		$selalbs 			= str_replace( '.', ',', wppa_expand_enum( wppa_alb_to_enum_children( wppa_expand_enum( $instance['parent'] ) ) ) );

		// Make the data we need
		if ( $users ) foreach ( $users as $user ) {
			if ( ! in_array($user['user_login'], $ignorelist) ) {
				$me = wppa_get_user();
				if ( $user['user_login'] != $me && isset ( $upldrcache[$this->get_widget_id()][$user['user_login']]['c'] ) ) {
					$photo_count = $upldrcache[$this->get_widget_id()][$user['user_login']]['c'];
				}
				else {
					if ( $instance['parent'] ) {
						$query = $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `owner` = %s AND `album` IN (".$selalbs.") AND ( ( `status` <> 'pending' AND `status` <> 'scheduled' ) OR `owner` = %s )", $user['user_login'], $me );//);
					}
					else {
						$query = $wpdb->prepare( "SELECT COUNT(*) FROM `".WPPA_PHOTOS."` WHERE `owner` = %s AND ( ( `status` <> 'pending' AND `status` <> 'scheduled' ) OR `owner` = %s )", $user['user_login'], $me );//);
					}
					$photo_count = $wpdb->get_var( $query );
					if ( $user['user_login'] != $me ) {
						$upldrcache[$this->get_widget_id()][$user['user_login']]['c'] = $photo_count;
						$needupdate = true;
					}
				}
				if ( $photo_count ) {
					if ( $user['user_login'] != $me && isset ( $upldrcache[$this->get_widget_id()][$user['user_login']]['d'] ) ) $last_dtm = $upldrcache[$this->get_widget_id()][$user['user_login']]['d'];
					else {
						if ( $instance['parent'] ) {
							$last_dtm = $wpdb->get_var($wpdb->prepare( "SELECT `timestamp` FROM `".WPPA_PHOTOS."` WHERE `owner` = %s AND `album` IN (".$selalbs.") AND ( ( `status` <> 'pending' AND `status` <> 'scheduled' ) OR `owner` = %s ) ORDER BY `timestamp` DESC LIMIT 1", $user['user_login'], $me ));
						}
						else {
							$last_dtm = $wpdb->get_var($wpdb->prepare( "SELECT `timestamp` FROM `".WPPA_PHOTOS."` WHERE `owner` = %s AND ( ( `status` <> 'pending' AND `status` <> 'scheduled' ) OR `owner` = %s ) ORDER BY `timestamp` DESC LIMIT 1", $user['user_login'], $me ));
						}
					}
					if ( $user['user_login'] != $me ) {
						$upldrcache[$this->get_widget_id()][$user['user_login']]['d'] = $last_dtm;
						$needupdate = true;
					}

					$workarr[] = array('login' => $user['user_login'], 'name' => $user['display_name'], 'count' => $photo_count, 'date' => $last_dtm);

					$total_photocount += $photo_count;
					$total_ownercount++;
				}
			}
		}
		else {
			$widget_content =
				__( 'There are too many registered users in the system for this widget' , 'wp-photo-album-plus' );
			echo "\n" . $before_widget;
			if ( !empty( $widget_title ) ) { echo $before_title . $widget_title . $after_title; }
			echo $widget_content . $after_widget;
			return;
		}

		if ( $needupdate ) update_option('wppa_upldr_cache', $upldrcache);

		// Bring me to top
		$myline = false;
		if ( is_user_logged_in() ) {
			$me = wppa_get_user();
			foreach ( array_keys($workarr) as $key ) {
				$user = $workarr[$key];
				if ( $user['login'] == $me ) {
					$myline = $workarr[$key];
					unset ( $workarr[$key] );
				}
			}
		}

		// Sort workarray
		$ord = $instance['sortby'] == 'name' ? SORT_ASC : SORT_DESC;
		$workarr = wppa_array_sort($workarr, $instance['sortby'], $ord);

		// Create widget content
		$widget_content = "\n".'<!-- WPPA+ Upldr Widget start -->';
		$widget_content .= '<div class="wppa-upldr" >';
		if ( $showownercount ) {
			$widget_content .= sprintf( _n( 'Number of contributors: %d', 'Number of contributors: %d', $total_ownercount, 'wp-photo-album-plus' ), $total_ownercount ) . '<br />';
		}
		if ( $showphotocount ) {
			$widget_content .= sprintf( _n( 'Number of photos: %d', 'Number of photos: %d', $total_photocount, 'wp-photo-album-plus' ), $total_photocount ) . '<br /><br />';
		}
		$widget_content .= '<table><tbody>';
		$albs = $instance['parent'] ? wppa_expand_enum( wppa_alb_to_enum_children( wppa_expand_enum( $instance['parent'] ) ) ) : '';
		$a = $albs ? wppa_trim_wppa_( '&amp;wppa-album='.$albs ) : '';

		if ( $myline ) {
			$user = $myline;
			$widget_content .= '<tr class="wppa-user" >
									<td style="padding: 0 3px;" ><a href="'.wppa_encrypt_url(wppa_get_upldr_link($user['login']).$a).'" title="'.__('Photos uploaded by', 'wp-photo-album-plus').' '.$user['name'].'" ><b>'.$user['name'].'</b></a></td>
									<td style="padding: 0 3px;" ><b>'.$user['count'].'</b></td>
									<td style="padding: 0 3px;" ><b>'.wppa_get_time_since($user['date']).'</b></td>
								</tr>';
		}
		foreach ( $workarr as $user ) {
			$widget_content .= '<tr class="wppa-user" >
									<td style="padding: 0 3px;" ><a href="'.wppa_encrypt_url(wppa_get_upldr_link($user['login']).$a).'" title="'.__('Photos uploaded by', 'wp-photo-album-plus').' '.$user['name'].'" >'.$user['name'].'</a></td>
									<td style="padding: 0 3px;" >'.$user['count'].'</td>
									<td style="padding: 0 3px;" >'.wppa_get_time_since($user['date']).'</td>
								</tr>';
		}
		$widget_content .= '</tbody></table></div>';
		$widget_content .= '<div style="clear:both"></div>';
		$widget_content .= "\n".'<!-- WPPA+ Upldr Widget end -->';

		// Output
		echo "\n" . $before_widget;
		if ( !empty( $widget_title ) ) { echo $before_title . $widget_title . $after_title; }
		echo $widget_content . $after_widget;

		wppa( 'in_widget', false );
    }

    /** @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title'] 			= strip_tags($new_instance['title']);
		$instance['sortby'] 		= $new_instance['sortby'];
		$instance['ignore'] 		= $new_instance['ignore'];
		$instance['parent'] 		= $new_instance['parent'];
		$instance['showownercount'] = $new_instance['showownercount'];
		$instance['showphotocount'] = $new_instance['showphotocount'];

		wppa_flush_upldr_cache( 'widgetid', $this->get_widget_id() );

        return $instance;
    }

    /** @see WP_Widget::form */
    function form( $instance ) {
		global $wpdb;

		//Defaults
		$instance 		= wp_parse_args( (array) $instance, array(
														'title' 			=> __( 'Uploader Photos', 'wp-photo-album-plus' ),
														'sortby' 			=> 'name',
														'ignore' 			=> 'admin',
														'parent' 			=> '',
														'showownercount' 	=> '',
														'showphotocount' 	=> ''
														) );

		// Title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) );

		// Sortby
		$options = array(	__( 'Display name', 'wp-photo-album-plus' ),
							__( 'Number of photos', 'wp-photo-album-plus' ),
							__( 'Most recent photo', 'wp-photo-album-plus' ),
							);
		$values  = array( 	'name',
							'count',
							'date',
							);
		echo
		wppa_widget_selection( $this, 'sortby', $instance['sortby'], __( 'Sort by', 'wp-photo-album-plus' ), $options, $values );

		// Ignore these users
		echo
		wppa_widget_input( 	$this,
							'ignore',
							$instance['ignore'],
							__( 'Ignore', 'wp-photo-album-plus' ),
							__( 'Enter loginnames seperated by commas', 'wp-photo-album-plus' )
							);
?>


		<p><label for="<?php echo $this->get_field_id('parent'); ?>"><?php _e('Look only in albums (including sub-albums):', 'wp-photo-album-plus'); ?></label>
			<input type="hidden" id="<?php echo $this->get_field_id('parent'); ?>" name="<?php echo $this->get_field_name('parent'); ?>" value="<?php echo $instance['parent'] ?>" />
			<?php if ( $instance['parent'] ) echo '<br/><small>( '.$instance['parent'].' )</small>' ?>
			<select class="widefat" multiple="multiple" onchange="wppaGetSelEnumToId( 'parentalbums-<?php echo $this->get_widget_id() ?>', '<?php echo $this->get_field_id('parent') ?>' )" id="<?php echo $this->get_field_id('parent-list'); ?>" name="<?php echo $this->get_field_name('parent-list'); ?>" >
			<?php
				// Prepare albuminfo
				if ( wppa_has_many_albums() ) {
					$albums = array();
				}
				else {
					$albums = $wpdb->get_results( "SELECT `id`, `name` FROM `" . WPPA_ALBUMS . "`", ARRAY_A );
				}
				if ( ! empty( $albums ) ) {
					if ( wppa_switch( 'hier_albsel' ) ) {
						$albums = wppa_add_paths( $albums );
					}
					else {
						foreach ( array_keys( $albums ) as $index ) $albums[$index]['name'] = __( stripslashes( $albums[$index]['name'] ) );
					}
					$albums = wppa_array_sort( $albums, 'name' );
				}

				// Please select
				$sel = $instance['parent'] ? '' : 'selected="selected" ';
				echo '<option class="parentalbums-'.$this->get_widget_id().'" value="" '.$sel.'>-- '.__('All albums', 'wp-photo-album-plus').' --</option>';

				// Find the albums currently selected
				$selalbs = explode( '.', wppa_expand_enum( $instance['parent'] ) );

				// All standard albums
				foreach ( $albums as $album ) {
					$s = in_array( $album['id'], $selalbs );
					$sel = $s ? 'selected="selected" ' : '';
					echo '<option class="parentalbums-'.$this->get_widget_id().'" value="' . $album['id'] . '" '.$sel.'>'.stripslashes( __( $album['name'] , 'wp-photo-album-plus') ) . ' (' . $album['id'] . ')</option>';
				}
			?>
			</select>
		</p>
<?php
		// Ownercount
		echo
		wppa_widget_checkbox( $this, 'showownercount', $instance['showownercount'], __( 'Show count of owners', 'wp-photo-album-plus' ) );

		// Photocount
		echo
		wppa_widget_checkbox( $this, 'showphotocount', $instance['showphotocount'], __( 'Show count of photos', 'wp-photo-album-plus' ) );

    }

	function get_widget_id() {
		$widgetid = substr( $this->get_field_name( 'txt' ), strpos( $this->get_field_name( 'txt' ), '[' ) + 1 );
		$widgetid = substr( $widgetid, 0, strpos( $widgetid, ']' ) );
		return $widgetid;
	}


} // class UpldrWidget

// register UpldrWidget widget
add_action('widgets_init', 'wppa_register_UpldrWidget' );

function wppa_register_UpldrWidget() {
	register_widget("UpldrWidget");
}
