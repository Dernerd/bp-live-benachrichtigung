<?php
// No direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Get all new notifications after a given time for the current user
 *
 * @param int    $user_id user id.
 * @param string $last_notified last notified time.
 *
 * @return array
 */
function bpln_get_new_notifications( $user_id, $last_notified ) {

	global $wpdb;

	$bp = buddypress();

	$table = $bp->notifications->table_name;

	$registered_components = bp_notifications_get_registered_components();


	$components_list = array();

	foreach ( $registered_components as $component ) {
		$components_list[] = $wpdb->prepare( '%s', $component );
	}

	$components_list = implode( ',', $components_list );


	$query = "SELECT * FROM {$table} WHERE user_id = %d AND component_name IN ({$components_list}) AND id > %d AND is_new = %d ";

	$query = $wpdb->prepare( $query, $user_id, $last_notified, 1 );

	return $wpdb->get_results( $query );
}

/**
 * Get the last notification id for the user
 *
 * @param int $user_id user id.
 *
 * @return int
 */
function bpln_get_latest_notification_id( $user_id = 0 ) {

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	global $wpdb;

	$bp = buddypress();

	$table = $bp->notifications->table_name;

	$registered_components = bp_notifications_get_registered_components();


	$components_list = array();

	foreach ( $registered_components as $component ) {
		$components_list[] = $wpdb->prepare( '%s', $component );
	}

	$components_list = implode( ',', $components_list );


	$query = "SELECT MAX(id) FROM {$table} WHERE user_id = %d AND component_name IN ({$components_list}) AND is_new = %d ";

	$query = $wpdb->prepare( $query, $user_id, 1 );

	return (int) $wpdb->get_var( $query );
}


/**
 * Get a list of processed messages
 *
 * @param array $notifications notifications array.
 *
 * @return array
 */
function bpln_get_notification_messages( $notifications ) {

	$messages = array();

	if ( empty( $notifications ) ) {
		return $messages;
	}

	$total_notifications = count( $notifications );

	for ( $i = 0; $i < $total_notifications; $i ++ ) {

		$notification = $notifications[ $i ];

		$messages[] = bpln_get_the_notification_description( $notification );
	}

	return $messages;
}

/**
 * A copy of bp_get_the_notification_description to server our purpose of parsing notification to extract the message
 *
 * @see bp_get_the_notification_description
 *
 * @param stdClass $notification notification object.
 *
 * @return string
 */
function bpln_get_the_notification_description( $notification ) {

	$bp = buddypress();

	// Callback function exists.
	if ( isset( $bp->{$notification->component_name}->notification_callback ) && is_callable( $bp->{$notification->component_name}->notification_callback ) ) {
		$description = call_user_func( $bp->{$notification->component_name}->notification_callback, $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1 );

		// @deprecated format_notification_function - 1.5
	} elseif ( isset( $bp->{$notification->component_name}->format_notification_function ) && function_exists( $bp->{$notification->component_name}->format_notification_function ) ) {
		$description = call_user_func( $bp->{$notification->component_name}->format_notification_function, $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1 );

		// Allow non BuddyPress components to hook in.
	} else {

		/** This filter is documented in bp-notifications/bp-notifications-functions.php */
		$description = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', array(
			$notification->component_action,
			$notification->item_id,
			$notification->secondary_item_id,
			1,
			'string',
			$notification->component_action, // Duplicated so plugins can check the canonical action name.
			$notification->component_name,
			$notification->id,
		) );
	}

	/**
	 * Filters the full-text description for a specific notification.
	 *
	 * @param string $description Full-text description for a specific notification.
	 */
	return apply_filters( 'bp_get_the_notification_description', $description );
}

/**
 * Should we disable it in dashboard.
 *
 * @return bool
 */
function bpln_disable_in_dashboard() {
	// use this hook to disable notification in the backend.
	return apply_filters( 'bpln_disable_in_dashboard', false );
}
