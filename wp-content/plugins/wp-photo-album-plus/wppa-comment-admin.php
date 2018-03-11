<?php
/* wppa-commentadmin.php
* Package: wp-photo-album-plus
*
* manage all comments
* Version 6.7.00
*
*/


// LOAD THE BASE CLASS
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

// CREATE A PACKAGE CLASS *****************************
class WPPA_Comment_table extends WP_List_Table {

	var $data;

	function __construct() {
		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
			'singular'  => 'comment',
			'plural'    => 'comments',
			'ajax'      => false        //does this table support ajax?
		) );

	}

	// Filter
	function extra_tablenav( $which ) {

		if ( 'top' === $which ) {
			$comment_show = isset( $_COOKIE['comadmin-show'] ) ? $_COOKIE['comadmin-show'] : 'all';
			echo
			'<div class="alignleft actions">' .
				'<select id="wppa_comadmin_show" name="wppa_comadmin_show" onchange="" >
					<option value="all" ' . ( $comment_show == 'all' ? 'selected="selected"' : '' ) . ' >' . __( 'all', 'wp-photo-album-plus' ) . '</option>
					<option value="pending" ' . ( $comment_show == 'pending' ? 'selected="selected"' : '' ) . '>' . __( 'pending', 'wp-photo-album-plus' ) . '</option>
					<option value="approved" ' . ( $comment_show == 'approved' ? 'selected="selected"' : '' ) . '>' . __( 'approved', 'wp-photo-album-plus' ) . '</option>
					<option value="spam" ' . ( $comment_show == 'spam' ? 'selected="selected"' : '' ) . '>' . __( 'spam', 'wp-photo-album-plus' ) . '</option>
				</select>' .
				'<input' .
					' type="button"' .
					' class="button"' .
					' style="margin: 1px 8px 0 0;"' .
					' onclick="wppa_setCookie(\'comadmin-show\', jQuery( \'#wppa_comadmin_show\' ).val(), \'365\'); document.location.reload(true);"' .
					' value="' . esc_attr( __( 'Filter', 'wp-photo-album-plus' ) ) . '"' .
				' />' .
			'</div>';
		}
	}

	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'id':
			case 'user':
			case 'ip':
			case 'status':
				return $item[$column_name];
			default:
				return print_r($item,true); //Show the whole array for troubleshooting purposes
		}
	}

	function column_user( $item ) {

		return $item['user'] . '<br />' . $item['ip'];
	}

	function column_timestamp( $item ) {

		return wppa_local_date( false, $item['timestamp'] );
	}

	function column_photo( $item ) {

		$photo 	= $item['photo'];
		$src 	= wppa_get_thumb_url( $photo );
		$title 	= esc_attr( wppa_get_photo_name( $photo ) ) . ' (' . wppa_get_album_name( wppa_get_photo_item( $photo, 'album' ) ) . ')';
		$result =
		'<img' .
			' src="' . $src . '"' .
			' style="width:100px;max-height:75px;"' .
			' title="' . $title . '"' .
		' />' .
		'<br />' .
		'#' . $item['id'] . ' ' .
		__( 'Photo', 'wp-photo-album-plus' ) . ' #' . $item['photo'];

		return $result;
	}

	function column_email( $item ) {

		return make_clickable( $item['email'] );
	}

	function column_commenttext( $item ) {

		$action =
		'<a' .
			' id="href-' . $item['id'] . '"' .
			' style="display:none;"' .
			' href="' .
				'?page=' . $_REQUEST['page'] .
				'&comment=' . $item['id'] .
				'&action=editsingle' .
				'&commenttext=' . urlencode( $item['comment'] ) .
				'"
			>' .
			__( 'Update', 'wp-photo-album-plus' ) .
		'</a>';

		$actions = array(
			'editsingle' 	=> $action,
		);

		$result =
		'<textarea' .
			' id="commenttext-' . $item['id'] . '"' .
			' style="width:98%;"' .
			' onchange="wppaUpdateHref(\'' . $item['id'] . '\')"' .
			' >' .
			stripslashes( $item['comment'] ) .
		'</textarea>' .
		$this->row_actions( $actions );

		return $result;
	}

	function column_status( $item ) {

		$p1 = '<a href="?page=' . $_REQUEST['page'] . '&comment=' . $item['id'];
		$actions = array(
			'approvesingle' 	=> $p1 . '&action=approvesingle" >' . __( 'Approve', 'wp-photo-album-plus' ) . '</a>',
			'pendingsingle' 	=> $p1 . '&action=pendingsingle" >' . __( 'Pending', 'wp-photo-album-plus' ) . '</a>',
			'spamsingle'    	=> $p1 . '&action=spamsingle" >' . 	  __( 'Spam', 'wp-photo-album-plus' ) . '</a>',
			'deletesingle' 		=> $p1 . '&action=deletesingle" >' .  __( 'Delete', 'wp-photo-album-plus' ) . '</a>',
		);

		switch( $item['status'] ) {
			case 'pending':
				$status = __( 'Pending', 'wp-photo-album-plus' );
				$color 	= 'red';
				unset( $actions['pendingsingle'] );
				break;
			case 'approved':
				$status = __( 'Approved', 'wp-photo-album-plus' );
				$color 	= 'black';
				unset( $actions['approvesingle'] );
				break;
			case 'spam':
				$status = __( 'Spam', 'wp-photo-album-plus' );
				$color 	= 'red';
				unset( $actions['spamsingle'] );
				break;
			default:
				$status = '';
				$color 	= 'red';
		}
		$result = '<span id="status-' . $item['id'] . '" style="color:' . $color . ';" >' . $status . '</span>';
		$result .= $this->row_actions( $actions );

		return $result;
	}

	function column_cb( $item ){

		$result =
		'<input type="checkbox" name="' . $this->_args['singular'] . '[]" value="' . $item['id'] . '" />';

		return $result;
	}

	function get_columns() {
		$columns = array(
			'cb'       		=> '<input type="checkbox" />', //Render a checkbox instead of text
			'photo' 		=> __( 'Photo', 'wp-photo-album-plus' ),
			'user' 			=> __( 'User', 'wp-photo-album-plus' ),
			'email' 		=> __( 'User email', 'wp-photo-album-plus' ),
			'timestamp' 	=> __( 'Timestamp', 'wp-photo-album-plus' ),
			'status' 		=> __( 'Status', 'wp-photo-album-plus' ),
			'commenttext' 	=> __( 'Comment', 'wp-photo-album-plus' ),
		);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'id'     	=> array( 'id', false ),     //true means it's already sorted
			'timestamp' => array( 'timestamp', true ),
			'photo'  	=> array( 'photo', false ),
			'user' 		=> array( 'user', false ),
		);
		return $sortable_columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'approve' 	=> __( 'Approve', 'wp-photo-album-plus' ),
			'delete'    => __( 'Delete', 'wp-photo-album-plus' ),
		);
		return $actions;
	}

	// process_bulk_action also processes single actions as long as the query arg &action= exists
	function process_bulk_action() {
		global $wpdb;

		// If it is a bulk action, $_GET['comment']  holds an array of record ids
		// If it is a single action, $_GET['comment'] holds a single record id.
		$id = isset( $_GET['comment'] ) ? $_GET['comment'] : '';
		if ( is_array( $id ) ) {
			$ids = $id;
		}
		else {
			$ids = (array) $id;
		}

		$current_action = $this->current_action();

		if ( $current_action && $id ) {

			// Delete
			if ( 'delete' === $current_action || 'deletesingle' === $current_action ) {
				foreach( $ids as $id ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPPA_COMMENTS . "` WHERE `id` = %s", $id ) );
				}
			}

			// Approve
			if ( 'approve' === $current_action || 'approvesingle' === $current_action ) {
				foreach( $ids as $id ) {

					$iret = $wpdb->query( $wpdb->prepare( "UPDATE `" . WPPA_COMMENTS . "` SET `status` = 'approved' WHERE `id` = %s", $id ) );

					if ( $iret ) {
						wppa_send_comment_approved_email( $id );
						$photo = $wpdb->get_var( $wpdb->prepare( "SELECT `photo` FROM `" . WPPA_COMMENTS . "` WHERE `id` = %s", $id ) );
						wppa_add_credit_points( wppa_opt( 'cp_points_comment_appr' ), __( 'Photo comment approved' , 'wp-photo-album-plus'), $photo, '', wppa_get_photo_item( $photo, 'owner' )	);
					}
				}
			}

			// Spam
			if ( 'spam' === $current_action || 'spamsingle' === $current_action ) {
				foreach( $ids as $id ) {
					$wpdb->query( $wpdb->prepare( "UPDATE `" . WPPA_COMMENTS . "` SET `status` = 'spam' WHERE `id` = %s", $id ) );
				}
			}

			// Pending
			if ( 'pending' === $current_action || 'pendingsingle' === $current_action ) {
				foreach( $ids as $id ) {
					$wpdb->query( $wpdb->prepare( "UPDATE `" . WPPA_COMMENTS . "` SET `status` = 'pending' WHERE `id` = %s", $id ) );
				}
			}

			// Edit, exists single only
			if ( 'editsingle' === $current_action ) {
				$commenttext = $_GET['commenttext'];
				$id = $_GET['comment'];
				$wpdb->query( $wpdb->prepare( "UPDATE `" . WPPA_COMMENTS . "` SET `comment` = %s WHERE `id` = %s", $commenttext, $id ) );
			}

			// Update index in the near future
			if ( wppa_switch( 'search_comments' ) ) {
				foreach( $ids as $id ) {
					$photo = $wpdb->get_var( $wpdb->prepare( "SELECT `photo` FROM `" . WPPA_COMMENTS . "` WHERE `id` = %s", $id ) );
					wppa_index_update( 'photo', $photo );
				}
			}
		}
	}

	function prepare_items() {
		global $wpdb;

		$per_page 	= wppa_opt( 'comment_admin_pagesize' );
		$columns 	= $this->get_columns();
		$hidden 	= array();
		$sortable 	= $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();

		$filter 	= '';

		// Moderate single only?
		$moderating = isset( $_REQUEST['commentid'] );
		if ( $moderating ) {
			$filter = "WHERE `id` = " . strval( intval( $_REQUEST['commentid'] ) );
		}

		// Normal use
		else {
			if ( isset( $_COOKIE['comadmin-show'] ) ) {
				switch( $_COOKIE['comadmin-show'] ) {
					case 'all':
						break;
					case 'spam':
						$filter = "WHERE `status` = 'spam'";
						break;
					case 'pending':
						$filter = "WHERE `status` = 'pending'";
						break;
					case 'approved':
						$filter = "WHERE `status` = 'approved'";
						break;
				}
			}
		}

	    $data = $wpdb->get_results( "SELECT * FROM `" . WPPA_COMMENTS . "` " . $filter . " ORDER BY `timestamp` DESC", ARRAY_A );

		function usort_reorder( $a, $b ) {
			$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'timestamp'; //If no sort, default to title
			$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc'; //If no order, default to asc
			$result = strcmp( $a[$orderby], $b[$orderby] ); //Determine sort order
			return ( $order === 'asc' ) ? $result : -$result; //Send final sort direction to usort
		}
		usort( $data, 'usort_reorder' );

		$current_page 	= $this->get_pagenum();
		$total_items 	= count( $data );
		if ( $per_page ) {
			$data 			= array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		}
		$this->items 	= $data;

		$this->set_pagination_args( array(
			'total_items' => $total_items,                  	//WE have to calculate the total number of items
			'per_page'    => ( $per_page ? $per_page : $total_items ),                     	//WE have to determine how many items to show on a page
			'total_pages' => ( $per_page ? ceil( $total_items / $per_page ) : 1 ),   //WE have to calculate the total number of pages
		) );
	}
}


// The command admin page
function _wppa_comment_admin() {
	global $wpdb;

	// Create an instance of our package class...
	$testListTable = new WPPA_Comment_table();

	// Fetch, prepare, sort, and filter our data...
	$testListTable->prepare_items();

	// Moderate single only?
	$moderating = isset( $_REQUEST['commentid'] );

	// Open page
	echo
	'<div class="wrap">
		<script type="text/javascript" >
			function wppaUpdateHref( id ) {
				var val 	= encodeURIComponent(jQuery(\'#commenttext-\'+id).val());
				var href 	= jQuery(\'#href-\'+id).attr(\'href\');
				var arr 	= href.split(\'commenttext=\');
				arr[1] 		= val;
				href 		= arr[0] + \'commenttext=\' + arr[1];
				jQuery(\'#href-\'+id).attr(\'href\', href);
				jQuery(\'#href-\'+id).css(\'display\',\'inline\');
			}
		</script>
		<style type="text/css" >
			.column-photo {
				width:110px;
			}
			.column-user, .column-email, .column-timestamp {
				width:160px;
			}
			.column-status {
				width:100px;
			}
		</style>
		<h1>' .
			( $moderating ? __( 'Photo Albums -> Moderate Comment', 'wp-photo-album-plus' ) :
							__( 'Photo Albums -> Comment admin', 'wp-photo-album-plus' ) ) .
		'</h1>';
		if ( $moderating ) {
			$status_show = array( 'pending', 'spam' );
		}
		else {

			// Statistics
			$t_to_txt = array( 	'none' 		=> false,
								'600' 		=> sprintf( _n('%d minute', '%d minutes', '10', 'wp-photo-album-plus'), '10'),
								'1800' 		=> sprintf( _n('%d minute', '%d minutes', '30', 'wp-photo-album-plus'), '30'),
								'3600' 		=> sprintf( _n('%d hour', '%d hours', '1', 'wp-photo-album-plus'), '1'),
								'86400' 	=> sprintf( _n('%d day', '%d days', '1', 'wp-photo-album-plus'), '1'),
								'604800' 	=> sprintf( _n('%d week', '%d weeks', '1', 'wp-photo-album-plus'), '1'),
							);
			$spamtime = $t_to_txt[wppa_opt( 'spam_maxage' )];

			echo
			'<table>
				<tbody>
					<tr>
						<td style="margin:0; font-weight:bold; color:#777777;">' . __( 'Total:', 'wp-photo-album-plus' ) . '</td>
						<td style="margin:0; font-weight:bold;">' . $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPPA_COMMENTS . "`" ) . '</td>
						<td></td>
					</tr>
					<tr>
						<td style="margin:0; font-weight:bold; color:green;">' . __( 'Approved:', 'wp-photo-album-plus' ) . '</td>
						<td style="margin:0; font-weight:bold;">' . $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPPA_COMMENTS . "` WHERE `status` = 'approved'" ) . '</td>
						<td></td>
					</tr>
					<tr>
						<td style="margin:0; font-weight:bold; color:#e66f00;">' . __( 'Pending:', 'wp-photo-album-plus' ) . '</td>
						<td style="margin:0; font-weight:bold;">' . $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPPA_COMMENTS . "` WHERE `status` = 'pending'" ) . '</td>
						<td></td>
					</tr>
					<tr>
						<td style="margin:0; font-weight:bold; color:red;">' . __( 'Spam:', 'wp-photo-album-plus' ) . '</td>
						<td style="margin:0; font-weight:bold;">' . $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPPA_COMMENTS . "` WHERE `status` = 'spam'" ) . '</td>
						<td></td>
					</tr>';
					if ( $spamtime ) {
						echo
						'<tr>
							<td style="margin:0; font-weight:bold; color:red;">' . __( 'Auto deleted spam:', 'wp-photo-album-plus' ) . '</td>
							<td style="margin:0; font-weight:bold;">' . get_option( 'wppa_spam_auto_delcount', '0' ) . '</td>
							<td>' . sprintf( __( 'Comments marked as spam will be deleted when they are entered longer than %s ago.', 'wp-photo-album-plus' ), $spamtime ) . '</td>
						</tr>';
					}
				echo
				'</tbody>
			</table>';

			/*
			// Filter
			$comment_show = isset( $_COOKIE['comadmin-show'] ) ? $_COOKIE['comadmin-show'] : 'all';
			echo
			'<p>' .
				'<b>' . __( 'Filter', 'wp-photo-album-plus' ) . ': </b>' .
				'<select name="wppa_comadmin_show" onchange="wppa_setCookie(\'comadmin-show\', this.value, \'365\'); document.location.reload(true);" >
					<option value="all" ' . ( $comment_show == 'all' ? 'selected="selected"' : '' ) . ' >' . __( 'all', 'wp-photo-album-plus' ) . '</option>
					<option value="pending" ' . ( $comment_show == 'pending' ? 'selected="selected"' : '' ) . '>' . __( 'pending', 'wp-photo-album-plus' ) . '</option>
					<option value="approved" ' . ( $comment_show == 'approved' ? 'selected="selected"' : '' ) . '>' . __( 'approved', 'wp-photo-album-plus' ) . '</option>
					<option value="spam" ' . ( $comment_show == 'spam' ? 'selected="selected"' : '' ) . '>' . __( 'spam', 'wp-photo-album-plus' ) . '</option>
				</select>
			</p>';
			*/
		}

		echo
		'<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
		<form id="wppa-comment-form" method="GET" >

			<!-- For plugins, we also need to ensure that the form posts back to our current page -->
			<input type="hidden" name="page" value="' . $_REQUEST['page'] , '" />

			<!-- Now we can render the completed list table -->';
			$testListTable->display();
		echo
		'</form>

	</div>';

}