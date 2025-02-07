<?php
defined( 'ABSPATH' ) OR exit;

/*
	Plugin Name: The Taxonomy Sort
	Plugin URI: https://wordpress.org/plugins/the-taxonomy-sort/
	Description: Allows you to easily change the order of different terms in taxonomies
	Version: 2.0
	Author: KonektOU
	Author URI: https://www.konekt.ee
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class TheTaxonomySort {
	/**
	 * Simple class constructor
	 */
	function __construct() {
		// admin initialize
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// front-end initialize
		add_action( 'init',       array( $this, 'init' ) );
	}

	/**
	 * Initialize administration
	 *
	 * @return void
	 */
	function admin_init() {
		// load scripts
		add_action( 'admin_enqueue_scripts',    array( $this, 'enqueue_scripts' ) );

		// load inline CSS style
		add_action( 'admin_print_styles',       array( $this, 'print_styles' ) );

		// ajax to save the sorting
		add_action( 'wp_ajax_get_inline_boxes', array( $this, 'inline_edit_boxes' ) );

		// reorder terms when someone tries to get terms
		add_filter( 'get_terms',                array( $this, 'reorder_terms' ) );
	}

	/**
	 * Initialize front-page
	 *
	 * @return void
	 */
	function init() {
		// reorder terms when someone tries to get terms
		add_filter( 'get_terms', array( $this, 'reorder_terms' ) );
	}

	/**
	 * Load scripts
	 *
	 * @return void
	 */
	function enqueue_scripts() {
		// allow enqueue only on tags/taxonomy page
		if ( get_current_screen()->base != 'edit-tags' ) return;

		// load jquery and plugin's script
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'thetaxonomysort', plugins_url( 'the-taxonomy-sort.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable' ) );
	}

	/**
	 * Print styles
	 *
	 * @return void
	 */
	function print_styles() {
		// show drag cursor
		echo '<style>.wp-list-table.tags td { cursor: move; }</style>';
	}

	/**
	 * Do the sorting
	 *
	 * @return void
	 */
	function inline_edit_boxes() {
		// loop through rows
		foreach ( $_POST['rows'] as $key => $row ) {
			// skip empty
			if ( ! isset( $row ) || $row == '' ) continue;

			// update order
			update_term_meta( $row, 'thets_order', ( $key + 1 ) );
		}

		// kill it for ajax
		exit;
	}

	/**
	 * Order terms
	 *
	 * @param object  $objects All the objects that need sorting
	 * @return object          Sorted objects
	 */
	function reorder_terms( $objects ) {
		// we do not need empty objects
		if( empty( $objects ) ) {
			return $objects;
		}

		// placeholder for ordered objects
		$placeholder = array();

		// invalid key counter (if key is not set)
		$invalid_key = 9000;

		// loop through objects
		foreach ( $objects as $key => $object ) {
			// increase invalid key count
			$invalid_key++;

			// continue if no term_id
			if( ! isset( $object->term_id ) ) {
				continue;
			}

			// get the order key
			$term_order = get_term_meta( $object->term_id, 'thets_order', true );

			// use order key if exists, invalid key if not
			$term_key = ( $term_order != "" && $term_order != 0 ) ? (int) $term_order : $invalid_key;

			// add object to placeholder by it's key
			$placeholder[$term_key] = $object;
		}

		// sort by keys
		ksort( $placeholder );

		// return sorted objects
		return $placeholder;
	}
}

/**
 * Uninstall the plugin
 *
 * @return void
 */
function thets_uninstall_plugin() {
	// check if user has permissions
	if ( ! current_user_can( 'activate_plugins' ) ) return;

	// Remove our meta keys
	delete_post_meta_by_key( 'thets_order' );
}
register_uninstall_hook( __FILE__, 'thets_uninstall_plugin' );

// Start our plugin
new TheTaxonomySort;
