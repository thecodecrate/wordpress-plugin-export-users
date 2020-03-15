<?php
namespace UserExportWithMeta;

class WPUsers {
	/**
	 * Cached result for `get_all_columns()`.
	 *
	 * @var array
	 */
	public $cached_get_all_columns;

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
	 * Get user data (standard and meta).
	 *
	 * @param  WP_User $user The user we are extracting data.
	 * @return array (string) The key is the column name. The value is the column data.
	 *    Example: [
	 *      'first_name'  => 'John',
	 *      'last_name'   => 'Snow',
	 *    ]
	 */
	public function get_user_data( $user ) {
		/** User fields are in "data". */
		$user_data = (array) $user->data;

		/** Extra fields are saved as meta. */
		$user_meta = get_user_meta( $user->ID );

		/**
		 * By default, the user_meta has this format:
		 * [ 'key' => [ 'value' ] ]
		 * Converts it to:
		 * [ 'key' => 'value' ]
		 */
		$user_meta = array_map( 'array_shift', $user_meta );

		/** Return $user_data and $user_meta merged. */
		return array_merge( $user_data, $user_meta );
	}

	/**
	 * Get all columns (scan the entire user database).
	 * Format: `[column_name] => column_name`.
	 * It has a cache system to prevent unecessary re-work.
	 *
	 * @return string[] A list with all column names
	 */
	public function get_all_columns() {
		/** Check cache. */
		if ( $this->cached_get_all_columns ) {
			return $this->cached_get_all_columns;
		}

		/** Not in cache. */
		$users  = get_users(); /** Get all users. It doesn't contain meta. */
		$result = array();
		foreach ( $users as $user ) {
			$user_data = $this->get_user_data( $user ); /** Load meta and merge with user object. */
			foreach ( array_keys( $user_data ) as $key ) {
				$result[ $key ] = $key;
			}
		}

		/** Return. */
		$this->cached_get_all_columns = $result; /** Save results on cache for re-usee. */
		return $result;
	}

	/**
	 * Load users' data
	 *
	 * @param array $users User record from `get_users()`.
	 *
	 * @return array A list of users and their data. Example: [
	 *   [fname => John, lname => Snow], [fname => Jane, lname => Doe]
	 * ].
	 */
	public function get_users_data( $users ) {
		$user_rows = array();
		foreach ( $users as $user ) {
			$user_rows[] = $this->get_user_data( $user );
		}
		return $user_rows;
	}
}
