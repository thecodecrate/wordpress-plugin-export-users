<?php
namespace UserExportWithMeta;

class WPUsers {
	/**
	 * Get user fields (name and data, standard and meta).
	 *
	 * @param  WP_User $user The user we are extracting fields.
	 * @return array (string) The key is the column name. The value is the column data.
	 *    Example: [
	 *      'first_name'  => 'John',
	 *      'last_name'   => 'Snow',
	 *    ]
	 */
	public function get_user_fields( $user ) {
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
	 * Get all user field names (scan the entire user database).
	 *
	 * @return string[] List of all field names
	 */
	public function get_all_user_field_names() {
		$users  = get_users();
		$result = [];
		foreach ( $users as $user ) {
			$fields = $this->get_user_fields( $user );
			foreach ( array_keys( $fields ) as $key ) {
				$result[ $key ] = '';
			}
		}
		return array_keys( $result );
	}

}
