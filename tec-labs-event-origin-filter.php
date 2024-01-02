<?php
/**
 * Plugin Name:       The Events Calendar Extension: Event Origin Filter
 * Plugin URI:
 * GitHub Plugin URI:
 * Description:       Adds a filter to the admin dashboard to allow filtering by origin.
 * Version:           1.0.0
 * Author:            The Events Calendar
 * Author URI:        https://evnt.is/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tec-labs-ce-convert-content-to-blocks
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

class Event_Filter_By_Origin {
	// Add a dropdown filter to the custom post type
	public function __construct() {
		// Hook to add the filter dropdown
		add_action( 'restrict_manage_posts', [ $this, 'custom_events_filter' ] );

		// Hook to filter the posts
		add_filter( 'parse_query', [ $this, 'filter_events_by_origin' ] );

		// Show the 'Filter' button
		add_action( 'admin_footer', [ $this, 'show_filter_button' ] );
	}

	function custom_events_filter() {
		global $typenow;

		// Check if the current post type is "tribe_events"
		if ( $typenow == 'tribe_events' ) {

			// Output the dropdown filter
			echo '<select name="event_origin_filter">';
			echo '<option value="">Events from all sources</option>';

			$origins = $this->get_unique_event_origins();

			foreach ( $origins as $origin ) {
				$origin_label = ucwords( str_replace( '-', ' ', $origin ) );
				$selected     = ( isset( $_GET['event_origin_filter'] ) && $_GET['event_origin_filter'] == $origin ) ? 'selected' : '';
				echo '<option value="' . $origin . '" ' . $selected . '>' . $origin_label . '</option>';
			}

			echo '</select>';
		}
	}

	// Filter the posts based on the selected '_EventOrigin'

	function get_unique_event_origins() {
		global $wpdb;

		$meta_key = '_EventOrigin';

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = %s ORDER BY meta_value ASC",
				$meta_key
			)
		);

		return $results;
	}

	function filter_events_by_origin( $query ) {
		global $pagenow;

		// Check if it's the admin panel and the main query
		if (
			is_admin()
			&& $pagenow == 'edit.php'
			&& isset( $_GET['post_type'] )
			&& $_GET['post_type'] == 'tribe_events'
			&& isset( $_GET['event_origin_filter'] )
			&& $_GET['event_origin_filter'] != ''
		) {
			// Add a meta query to filter by '_EventOrigin'
			$query->query_vars['meta_query'][] = [
				'key'     => '_EventOrigin',
				'value'   => $_GET['event_origin_filter'],
				'compare' => '=',
			];
		}
	}

	public function show_filter_button() {
		global $pagenow;

		if (
			is_admin()
			&& $pagenow == 'edit.php'
			&& isset( $_GET['post_type'] )
			&& $_GET['post_type'] == 'tribe_events'
		) {
			echo '<style>.events-cal #post-query-submit { display: block; }</style>';
		}
	}
}

new Event_Filter_By_Origin();