<?php
namespace UserExportWithMeta;

class WPUsers {
	/**
	 * Get all roles.
	 *
	 * @return array An associative array on format `[role_id] => role_name`.
	 */
	public function get_all_roles() {
		$roles_object = get_editable_roles(); /** Get roles. */

		/** Convert role records to be used on select: `[key] => value`. */
		$roles = array();
		foreach ( $roles_object as $role_id => $role_object ) {
			$roles[ $role_id ] = $role_object['name'];
		}
		return $roles;
	}

	/**
	 * Get all columns (scan the entire user database).
	 * Format: `[column_name] => column_name`.
	 * It has a cache system to prevent unecessary re-work.
	 *
	 * @return string[] A list with all column names
	 */
	public function get_all_columns() {
		/** Return cache. */
		$result = wp_cache_get( 'uewm_get_all_columns' );
		if ( false !== $result ) {
			return $result;
		}

		/** SQL on WP. */
		global $wpdb;

		/** Get `wp_users` column names. */
		$sql     = "SELECT * FROM {$wpdb->users} LIMIT 1";
		$md5     = md5( $sql );
		$columns = wp_cache_get( $md5 );
		if ( false === $columns ) {
			$wpdb->get_row( $sql );
			$columns = $wpdb->get_col_info();
			wp_cache_set( $md5, $columns );
		}

		/** Get metadata names. */
		$sql          = "SELECT meta_key FROM {$wpdb->usermeta} GROUP BY meta_key";
		$md5          = md5( $sql );
		$meta_columns = wp_cache_get( $md5 );
		if ( false === $meta_columns ) {
			$meta_columns = $wpdb->get_col( $sql, 0 );
			wp_cache_set( $md5, $meta_columns );
		}

		/** Return. */
		$result = array_merge( $columns, $meta_columns );
		$result = $this->value_to_keys( $result );
		wp_cache_set( 'uewm_get_all_columns', $result );
		return $result;
	}

	/**
	 * Load users and their meta.

	 * @param  string[] $ids User ids.
	 *
	 * @return int A database handler that returns a list of users and their data. Example: [
	 *   [fname => John, lname => Snow], [fname => Jane, lname => Doe]
	 * ].
	 */
	public function get_users_data( $ids ) {
		global $wpdb;

		/** A string "%d, %d, %d, ..." to be used on "id IN(...)". */
		$array_d = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		/** Get users of $ids. */
		$sql          = "SELECT * FROM {$wpdb->users} WHERE id IN ({$array_d})";
		$query        = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $ids ) );
		$user_records = $wpdb->get_results( $query );

		/** Get meta data of $ids. */
		$sql          = "SELECT * FROM {$wpdb->usermeta} WHERE user_id in ({$array_d})";
		$query        = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $ids ) );
		$meta_records = $wpdb->get_results( $query );

		/** Final array. */
		$user_rows = array();

		/** Set key = user ID. */
		foreach ( $user_records as $record ) {
			$user_rows[ $record->ID ] = (array) $record;
		}

		/** Add meta info */
		foreach ( $meta_records as $record ) {
			$user_rows[ $record->user_id ][ $record->meta_key ] = $record->meta_value;
		}
		return $user_rows;
	}

	/**
	 * Get all user IDs from a given set of roles.
	 *
	 * @param  string[] $roles Filter users with these roles.
	 *
	 * @return int[] An array of user ids.
	 */
	public function get_user_ids_by_roles( $roles ) {
		global $wpdb;

		/** Get user IDs by their roles. */
		$role_statements = array();
		foreach ( $roles as $role ) {
			$value             = serialize( $role );
			$role_statements[] = "meta_value LIKE '%{$value}%'";
		}
		$role_statements = join( ' OR ', $role_statements );
		$sql = "
			SELECT user_id
			FROM {$wpdb->usermeta}
			WHERE meta_key = '{$wpdb->prefix}capabilities' AND ({$role_statements})
		";
		$ids = $wpdb->get_col( $sql, 0 );
		return array_map( 'intval', $ids );
	}

	/**
	 * Get all user ids.
	 *
	 * @return int[] An array of user ids.
	 */
	public function get_user_ids() {
		global $wpdb;
		$sql = "SELECT ID FROM {$wpdb->users}";
		$ids = $wpdb->get_col( $sql, 0 );
		return array_map( 'intval', $ids );
	}

	/**
	 * Set values to keys: `[value] => value`.
	 *
	 * Useful for preparing a flat array to be used by an HTML select.
	 *
	 * @param string[] $array A simple array of strings.
	 * @return array An associative array `[value] => value`.
	 */
	private function value_to_keys( $array ) {
		$result = array();
		foreach ( $array as $value ) {
			$result[ $value ] = $value;
		}
		return $result;
	}
}
