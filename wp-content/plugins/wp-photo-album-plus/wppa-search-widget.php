<?php
/* wppa-searchwidget.php
* Package: wp-photo-album-plus
*
* display the search widget
* Version 6.7.03
*
*/

class SearchPhotos extends WP_Widget {

    /** constructor */
    function __construct() {
		$widget_ops = array( 	'classname' => 'wppa_search_photos',
								'description' => __( 'Display search photos dialog', 'wp-photo-album-plus' )
							);
		parent::__construct( 'wppa_search_photos', __( 'WPPA+ Search Photos', 'wp-photo-album-plus' ), $widget_ops );															//
    }

	/** @see WP_Widget::widget */
    function widget( $args, $instance ) {
		global $widget_content;
		global $wpdb;

		require_once( dirname( __FILE__ ) . '/wppa-links.php' );
		require_once( dirname( __FILE__ ) . '/wppa-styles.php' );
		require_once( dirname( __FILE__ ) . '/wppa-functions.php' );
		require_once( dirname( __FILE__ ) . '/wppa-thumbnails.php' );
		require_once( dirname( __FILE__ ) . '/wppa-boxes-html.php' );
		require_once( dirname( __FILE__ ) . '/wppa-slideshow.php' );
		wppa_initialize_runtime();

		wppa( 'mocc', wppa( 'mocc' ) + 1 );
		wppa( 'in_widget', 'search' );

        extract( $args );

		$instance = wp_parse_args( (array) 	$instance,
									array( 	'title' 		=> __( 'Search Photos', 'wp-photo-album-plus' ),
											'label' 		=> '',
											'root' 			=> false,
											'sub' 			=> false,
											'album' 		=> '0',
											'landingpage' 	=> '0',
											'catbox' 		=> false,
											'selboxes' 		=> false,
											) );

 		$widget_title = apply_filters( 'widget_title', $instance['title'] );

		// Display the widget
		echo $before_widget;

		if ( ! empty( $widget_title ) ) {
			echo $before_title . $widget_title . $after_title;
		}

		echo wppa_get_search_html( 	$instance['label'],
									wppa_checked( $instance['sub'] ),
									wppa_checked( $instance['root'] ),
									$instance['album'],
									$instance['landingpage'],
									wppa_checked( $instance['catbox'] ),
									wppa_checked( $instance['selboxes'] ) ? wppa_opt( 'search_selboxes' ) : false
									);

		echo $after_widget;

		// Reset switch
		wppa( 'in_widget', false );
    }

    /** @see WP_Widget::update */
    function update( $new_instance, $old_instance ) {

		$instance 					= $old_instance;
		$instance['title'] 			= strip_tags($new_instance['title']);
		$instance['label']			= $new_instance['label'];
		$instance['root']  			= isset( $new_instance['root'] ) ? $new_instance['root'] : false;
		$instance['sub']   			= isset( $new_instance['sub'] ) ? $new_instance['sub'] : false;
		$instance['album'] 			= $new_instance['album'];
		$instance['landingpage']	= $new_instance['landingpage'];
		$instance['catbox'] 		= $new_instance['catbox'];
		$instance['selboxes'] 		= $new_instance['selboxes'];

        return $instance;
    }

    /** @see WP_Widget::form */
    function form( $instance ) {
		global $wpdb;

		// Defaults
		$instance 		= wp_parse_args( 	(array) $instance,
											array(
												'title' 		=> __( 'Search Photos', 'wp-photo-album-plus' ),
												'label' 		=> '',
												'root' 			=> false,
												'sub' 			=> false,
												'album' 		=> '0',
												'landingpage' 	=> '',
												'catbox' 		=> false,
												'selboxes' 		=> false,
												) );

		// Title
		echo
		wppa_widget_input( $this, 'title', $instance['title'], __( 'Title', 'wp-photo-album-plus' ) );

		// Pre input text
		echo
		wppa_widget_input( 	$this,
							'label',
							$instance['label'],
							__( 'Text above input field', 'wp-photo-album-plus' ),
							__( 'Enter optional text that will appear before the input box. This may contain HTML so you can change font size and color.', 'wp-photo-album-plus' )
							);

		// Enable rootsearch
		echo
		wppa_widget_checkbox( 	$this,
								'root',
								$instance['root'],
								__( 'Enable rootsearch', 'wp-photo-album-plus' ),
								__( 'See Table IX-E17 to change the label text', 'wp-photo-album-plus' )
								);

		// Fixed root?
		$body = wppa_album_select_a( array( 	'selected' 			=> $instance['album'],
												'addblank' 			=> true,
												'sort'				=> true,
												'path' 				=> true,
												) );
		echo
		wppa_widget_selection_frame( 	$this,
										'album',
										$body,
										__( 'Album', 'wp-photo-album-plus' ),
										false,
										__( 'If you want the search to be limited to a specific album and its (grand)children, select the album here.', 'wp-photo-album-plus' ) .
											' ' .
											__( 'If you select an album here, it will overrule the previous checkbox using the album as a \'fixed\' root.', 'wp-photo-album-plus' )
										);

		// Subsearch?
		echo
		wppa_widget_checkbox( 	$this,
								'sub',
								$instance['sub'],
								__( 'Enable subsearch', 'wp-photo-album-plus' ),
								__( 'See Table IX-E16 to change the label text', 'wp-photo-album-plus' )
								);

		// Category selection
		echo
		wppa_widget_checkbox( 	$this,
								'catbox',
								$instance['catbox'],
								__( 'Add category selectionbox', 'wp-photo-album-plus' ),
								__( 'Enables the visitor to limit the results to an album category', 'wp-photo-album-plus' )
								);

		// Selection boxes
		echo
		wppa_widget_checkbox( 	$this,
								'selboxes',
								$instance['selboxes'],
								__( 'Add selectionboxes with pre-defined tokens', 'wp-photo-album-plus' ),
								__( 'See Table IX-E20.x for configuration', 'wp-photo-album-plus' )
								);

		// Landing page
		$options 	= array( __( '--- default ---', 'wp-photo-album-plus' ) );
		$values  	= array( '0' );
		$disabled 	= array( false );

		$query = 	"SELECT ID, post_title, post_content, post_parent " .
					"FROM " . $wpdb->posts . " " .
					"WHERE post_type = 'page' AND post_status = 'publish' " .
					"ORDER BY post_title ASC";
		$pages = 	$wpdb->get_results( $query, ARRAY_A );

		if ( $pages ) {

			// Add parents optionally OR translate only
			if ( wppa_switch( 'hier_pagesel' ) ) $pages = wppa_add_parents( $pages );

			// Just translate qTranslate-x
			else {
				foreach ( array_keys( $pages ) as $index ) {
					$pages[$index]['post_title'] = __( stripslashes( $pages[$index]['post_title'] ) );
				}
			}

			// Sort alpahbetically
			$pages = wppa_array_sort( $pages, 'post_title' );

			// Options / values
			foreach ( $pages as $page ) {

				$options[] 	= __( $page['post_title'] );
				$values[] 	= $page['ID'];
				$disabled[] = strpos( $page['post_content'], '[wppa' ) === false && strpos( $page['post_content'], '%%wppa%%' ) === false;

			}
		}

		echo
		wppa_widget_selection( 	$this,
								'landingpage',
								$instance['landingpage'],
								__( 'Landing page', 'wp-photo-album-plus' ),
								$options,
								$values,
								$disabled,
								'widefat',
								__( 'The default page will be created automatically', 'wp-photo-album-plus' )
								);

    }

} // class SearchPhotos

// register SearchPhotos widget
add_action('widgets_init', 'wppa_register_SearchPhotos' );

function wppa_register_SearchPhotos() {
	register_widget( "SearchPhotos" );
}
