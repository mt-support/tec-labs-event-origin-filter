<?php
/**
 * Plugin Name:       The Events Calendar Extension: Event Origin Filter
 * Plugin URI:
 * GitHub Plugin URI: https://github.com/mt-support/tec-labs-event-origin-filter
 * Description:       Adds a filter to the admin dashboard to allow filtering Events by origin.
 * Version:           1.0.1
 * Author:            The Events Calendar
 * Author URI:        https://evnt.is/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tec-labs-event-origin-filter
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

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hook to add the filter dropdown
		add_action( 'restrict_manage_posts', [ $this, 'custom_events_filter' ] );

		// Hook to filter the posts
		add_filter( 'parse_query', [ $this, 'filter_events_by_origin' ] );

		// Show the 'Filter' button
		add_action( 'admin_footer', [ $this, 'show_filter_button' ] );
	}

	/**
	 * Renders the dropdown filter.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function custom_events_filter() {
		if ( $this->maybe_render() ) {
			// Output the dropdown filter
			echo '<select name="event_origin_filter">';
			echo '<option value="">' . esc_html__( 'Events from all sources', 'tec-labs-event-origin-filter' ) . '</option>';

			$origins = $this->get_unique_event_origins();

			foreach ( $origins as $origin ) {
				$origin_label = ucwords( str_replace( '-', ' ', $origin ) );
				$selected     = ( isset( $_GET['event_origin_filter'] ) && $_GET['event_origin_filter'] == $origin ) ? 'selected' : '';
				echo '<option value="' . $origin . '" ' . $selected . '>' . $origin_label . '</option>';
			}

			echo '</select>';
		}
	}

	/**
	 * Filter the posts based on the selected '_EventOrigin'
	 *
	 * @return array An array of post IDs.
	 *
	 * @since 1.0.0
	 */
	public function get_unique_event_origins() {
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

	/**
	 * Modify the post query according to the '_EventOrigin' filter.
	 *
	 * @param $query
	 *
	 * @return mixed|void
	 *
	 * @since 1.0.0
	 */
	public function filter_events_by_origin( $query ) {
		if ( ! $this->maybe_render() ) {
			return $query;
		}

		// Check if it's the admin panel and the main query
		if (
			isset( $_GET['event_origin_filter'] )
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

	/**
	 * Add CSS to (re-)show the filter submit button.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function show_filter_button() {
		if ( $this->maybe_render() ) {
			echo '<style>.events-cal #post-query-submit { display: block; }</style>';
		}
	}

	/**
	 * Check if we are on the right page to render the filter.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function maybe_render() {
		global $pagenow;

		// Bail, if not admin.
		if ( ! is_admin() ) {
			return false;
		}

		// Bail, if not Events.
		if (
			isset( $_GET['post_type'] )
			&& $_GET['post_type'] != 'tribe_events'
		) {
			return false;
		}

		// Bail, if not the post list page.
		if ( $pagenow != 'edit.php' ) {
			return false;
		}

		// Bail, if it's Ignored Events. (Those are only EA.)
		if ( isset( $_GET['post_status'] )
			&& $_GET['post_status'] == 'tribe-ignored'
		) {
			return false;
		}

		// Bail, if it's a different page.
		if ( isset( $_GET['page'] ) ) {
			return false;
		}

		return true;
	}
}

new Event_Filter_By_Origin();