<?php
/**
 * Class User_Export_With_Meta
 *
 * @package User_Export_With_Meta
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main class for the plugin.
 */
class User_Export_With_Meta {
	/**
	 * Our plugin slug ("uewm" means "User Export With Meta")
	 *
	 * @var String
	 */
	const SLUG = 'uewm';

	/**
	 * Entry-point for the plugin. Initializes all hooks and actions.
	 */
	public function __construct() {
		/** Add menu item: Step 1/3 - Listen to the event. */
		add_action( 'admin_menu', [ $this, 'admin_menu_callback' ] );

		/** Router. */
		add_action( 'admin_init', [ $this, 'router_callback' ] );

		/** Settings page: Register sections for the settings page. */
		add_action( 'admin_init', [ $this, 'settings_page_register_sections_callback' ] );
	}

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
	private function get_user_fields( $user ) {
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
	 * @return array List of all field names
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

	/**
	 * Settings page: Register sections for the settings page.
	 *
	 * @return void
	 */
	public function settings_page_register_sections_callback() {
		/** Check permissions. */
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		/** "Roles" field. */
		$roles        = [];
		$roles_object = get_editable_roles();
		foreach ( $roles_object as $role_id => $role_object ) {
			$roles[ $role_id ] = $role_object['name'];
		}

		/** "Columns" field. */
		$columns = $this->get_all_user_field_names();
		$columns = array_combine( $columns, $columns ); /** Keys and values have the same content. */

		/** Define here your sections and fields. */
		$sections = [
			[
				'id'     => 'filters_section',
				'title'  => '',
				'fields' => [
					[
						'type'    => 'multiple',
						'name'    => self::SLUG . '_roles',
						'title'   => 'Roles',
						'options' => $roles,
						'class'   => 'select2',
					],
					[
						'type'    => 'multiple',
						'name'    => self::SLUG . '_columns',
						'title'   => 'Columns',
						'options' => $columns,
						'class'   => 'select2',
					],
				],
			],
		];

		/** Call Settings API to generate sections and fields. */
		$callback = [ $this, 'field_callback' ];
		foreach ( $sections as $index => $section ) {
			/** Adds section. */
			add_settings_section( "section_$index", $section['title'], false, self::SLUG );
			foreach ( $section['fields'] as $field ) {
				/** Adds field. */
				add_settings_field( $field['name'], $field['title'], $callback, self::SLUG, "section_$index", $field );

				/** Register field. */
				register_setting( self::SLUG, $field['name'] );
			}
		}
	}

	/**
	 * Generates HTML code for a given input.
	 *
	 * @param  array $arguments Associative array.
	 *    $arguments = [
	 *      'type'    => (string) Input type (text, select, hidden, etc). Required.
	 *      'name'    => (string) Input attribute "name". Required.
	 *      'class'   => (string) Input attribute "class". Optional.
	 *      'options' => (array) Options for the select. Required for types "select" and "multiple".
	 *    ].
	 * @return void
	 */
	public function field_callback( $arguments ) {
		/** Value that is on the DB. */
		$saved_value = get_option( $arguments['name'] );

		/** Output HTML according to the type. */
		if ( 'multiple' === $arguments['type'] ) {
			$saved_value = $saved_value ? $saved_value : []; /** If null, convert it to array. */
			echo '<select
					class="' . esc_attr( $arguments['class'] ) . '"
					name="' . esc_attr( $arguments['name'] ) . '[]"
					multiple="multiple"
				  >' . "\n";
			/** Scan array. */
			foreach ( $arguments['options'] as $key => $value ) {
				$selected = in_array( $key, $saved_value, true ) ? 'selected="selected" ' : '';
				echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . '>' . esc_attr( $value ) . '</option>' . "\n";
			}
			echo "</select>\n";
		} else {
			echo '<input
			      type="' . esc_attr( $arguments['type'] ) . '"
			      name="' . esc_attr( $arguments['name'] ) . '"
			      value="' . esc_attr( $saved_value ) . '"
			    />';
		}
	}

	/**
	 * Add menu item: Step 3/3 - Call "add_options_page()".
	 *
	 * @return void
	 */
	public function admin_menu_callback() {
		$page_title = 'Export Users to CSV';
		$menu_title = 'Export to CSV';
		$capability = 'manage_options'; // who can see and access.
		$callback   = [ $this, 'settings_page_html' ];
		$hookname   = add_users_page( $page_title, $menu_title, $capability, self::SLUG, $callback );

		/** Load scripts/styles for this menu page. */
		add_action( 'load-' . $hookname, [ $this, 'load_admin_js_css' ] );
	}

	/**
	 * Load scripts/styles for the menu page.
	 *
	 * @return void
	 */
	public function load_admin_js_css() {
		/**
		 * It is not possible to enqueue the scripts here - it is too early.
		 * Instead, we hook to the proper action to deal with it.
		 */
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts_callback' ] );
	}

	/**
	 * Enqueue scripts/styles of the menu page.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts_callback() {
		$file_path_relative = '../node_modules/select2/dist/css/select2.min.css';
		$dir                = plugin_dir_path( __FILE__ );
		$url                = plugin_dir_url( __FILE__ ) . $file_path_relative;
		$file_path_absolute = $dir . $file_path_relative;
		$version            = filemtime( $file_path_absolute );
		wp_register_style( self::SLUG . 'select2', $url, [], $version );
		wp_enqueue_style( self::SLUG . 'select2' );

		$file_path_relative = '../node_modules/select2/dist/js/select2.min.js';
		$dir                = plugin_dir_path( __FILE__ );
		$url                = plugin_dir_url( __FILE__ ) . $file_path_relative;
		$file_path_absolute = $dir . $file_path_relative;
		$version            = filemtime( $file_path_absolute );
		wp_register_script( self::SLUG . 'select2', $url, [], $version, true );
		wp_enqueue_script( self::SLUG . 'select2' );
	}

	/**
	 * Router. Activated on "admin_init", intercept all HTTP requests.
	 */
	public function router_callback() {
		/** Get HTTP arguments. */
		$arg_action = empty( $_REQUEST['action'] ) ? null : wp_unslash( $_REQUEST['action'] );
		$arg_page   = empty( $_REQUEST['page'] ) ? null : wp_unslash( $_REQUEST['page'] );
		$arg_nonce  = empty( $_REQUEST['uewm_nonce'] ) ? null : wp_unslash( $_REQUEST['uewm_nonce'] );

		/** Our enpoints have action AND page set up. */
		if ( ! $arg_action || ! $arg_page ) {
			return;
		}

		/** Check if is our plugin. */
		if ( 'uewm' !== $arg_page ) {
			return;
		}

		/** Check permissions. */
		if ( ! is_admin() ) {
			return;
		}

		/** Check nonce. */
		if ( ! $arg_nonce || ! wp_verify_nonce( $arg_nonce, 'export-users' ) ) {
			return;
		}

		/** The Router. */
		if ( 'export-users' === $arg_action ) {
			/** HTTP Arguments. */
			$arg_roles   = empty( $_REQUEST[ self::SLUG . '_roles' ] ) ? null : wp_unslash( $_REQUEST[ self::SLUG . '_roles' ] );
			$arg_columns = empty( $_REQUEST[ self::SLUG . '_columns' ] ) ? null : wp_unslash( $_REQUEST[ self::SLUG . '_columns' ] );

			/** Save options. */
			update_option( self::SLUG . '_roles', $arg_roles ?? '' );
			update_option( self::SLUG . '_columns', $arg_columns ?? '' );

			/** Output. */
			$roles = $arg_roles ?: []; /** Empty = all. */
			$users = get_users( [ 'role__in' => $roles ] );
			$this->export_users( $users );
		}
	}

	/**
	 * Main function.
	 * Send users back to the browser as a csv attachment.
	 *
	 * @param  array(WP_User) $users The users to be exported.
	 * @return void
	 */
	public function export_users( $users ) {
		/** Set HTTP headers to download mode. */
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename=' . date( 'Y-m-d-H-i' ) . '-users.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		/** Get (1) all column names, (2) an assoc array with users data. */
		$all_column_names = [];
		$all_user_rows    = [];
		foreach ( $users as $user ) {
			$fields = $this->get_user_fields( $user );
			foreach ( array_keys( $fields ) as $key ) {
				$all_column_names[ $key ] = '';
			}
			$all_user_rows[] = $fields;
		}
		$all_column_names = array_keys( $all_column_names );

		/** Open file for writing. */
		$hnd = fopen( 'php://output', 'w' );

		/** Selected columns (Empty = all). */
		$columns = get_option( self::SLUG . '_columns' ) ?: $all_column_names;

		/** Do not export these. */
		$columns = array_diff(
			$columns,
			[
				'user_pass',
				'user_activation_key',
				'session_tokens',
				'wp_user-settings',
				'wp_user-settings-time',
				'wp_capabilities',
				'community-events-location',
			]
		);

		/** Output header. */
		fputcsv( $hnd, $columns );

		/** Output data. */
		foreach ( $all_user_rows as $user ) {
			/** Add missing columns to the user. */
			$row = [];
			foreach ( $columns as $column ) {
				$row[ $column ] = array_key_exists( $column, $user ) ? $user[ $column ] : '';
			}

			/** Now that the user has the same columns as the header, outputs it. */
			fputcsv( $hnd, $row );
		}

		/** Close file and exit. */
		fclose( $hnd );
		exit();
	}

	/** Add menu item: Step 3/3 - The page HTML. */
	public function settings_page_html() {
		?>
		<div class="wrap">
			<h2>Export Users to CSV</h2>
			<form method="post" action="admin.php?page=<?php echo esc_attr( self::SLUG ); ?>&action=export-users">
				<?php settings_fields( self::SLUG ); ?>
				<?php $nonce = wp_create_nonce( 'export-users' ); ?>
				<input type="hidden" name="uewm_nonce" value="<?php echo esc_attr( $nonce ); ?>">
				<input type="hidden" name="action" value="export-users">
				<?php do_settings_sections( self::SLUG ); ?>
				<?php submit_button( 'Save and Export' ); ?>
			</form>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('select.select2').select2({
					placeholder: 'All'
				});
			});
		</script>
		<style media="screen">
			select.select2 {
				width: 300px;
			}
		</style>
		<?php
	}

	/**
	 * Singleton with the current instance.
	 *
	 * @var User_Export_With_Meta
	 */
	protected static $instance = null;

	/**
	 * Get the current instance.
	 *
	 * @return [type] [description]
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
