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
	 * DB option fields.
	 */
	const FIELD_COLUMNS                 = self::SLUG . '_columns';
	const FIELD_ROLES                   = self::SLUG . '_roles';
	const FIELD_USE_CUSTOM_CSV_SETTINGS = self::SLUG . '_use_custom_csv_settings';
	const FIELD_SEPARATOR               = self::SLUG . '_field_separator';
	const FIELD_CUSTOM_SEPARATOR        = self::SLUG . '_custom_field_separator';
	const FIELD_TEXT_QUALIFIER          = self::SLUG . '_text_qualifier';
	const FIELD_CUSTOM_TEXT_QUALIFIER   = self::SLUG . '_custom_text_qualifier';

	/**
	 * Entry-point for the plugin. Initializes all hooks and actions.
	 */
	public function __construct() {
		/** Add menu item: Step 1/3 - Listen to the event. */
		add_action( 'admin_menu', [ $this, 'admin_menu_callback' ] );

		/** Fired on "Save and Export" button. */
		add_action( 'admin_post_' . self::SLUG . '_export_users', [ $this, 'generate_csv_callback' ] );
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
		if ( ! current_user_can( 'list_users' ) ) {
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
						'name'    => self::FIELD_ROLES,
						'title'   => 'Roles',
						'options' => $roles,
						'class'   => 'select2',
					],
					[
						'type'    => 'multiple',
						'name'    => self::FIELD_COLUMNS,
						'title'   => 'Columns',
						'options' => $columns,
						'class'   => 'select2',
					],
					[
						'type'  => 'checkbox',
						'name'  => self::FIELD_USE_CUSTOM_CSV_SETTINGS,
						'title' => 'Use a custom field separator (,) and text qualifier (")',
					],
				],
			],
			[
				'id'     => 'custom_csv_settings',
				'title'  => 'Field Separator and Text Qualifier',
				'fields' => [
					[
						'type'       => 'select_with_text',
						'name'       => self::FIELD_SEPARATOR,
						'title'      => 'Field Separator',
						'class'      => 'width_150',
						'options'    => [
							'comma'     => 'Comma (,)',
							'semicolon' => 'Semicolon (;)',
							'tab'       => 'Tab (\t)',
							'space'     => 'Space ( )',
							'custom'    => 'Other',
						],
						'text_name'  => self::FIELD_CUSTOM_SEPARATOR,
						'text_title' => 'Define it here: ',
					],
					[
						'type'       => 'select_with_text',
						'name'       => self::FIELD_TEXT_QUALIFIER,
						'title'      => 'Text Qualifier',
						'class'      => 'width_150',
						'options'    => [
							'double-quote' => 'Double-quote (")',
							'quote'        => 'Quote (\')',
							'custom'       => 'Other',
						],
						'text_name'  => self::FIELD_CUSTOM_TEXT_QUALIFIER,
						'text_title' => 'Define it here: ',
					],
				],
			],
		];

		/** Call Settings API to generate sections and fields. */
		$section_callback = [ $this, 'section_callback' ];
		$field_callback   = [ $this, 'field_callback' ];
		foreach ( $sections as $index => $section ) {
			/** Adds section. */
			$section_id = self::SLUG . '_' . $section['id'];
			add_settings_section( $section_id, $section['title'], $section_callback, self::SLUG );
			foreach ( $section['fields'] as $field ) {
				/** Adds field. */
				add_settings_field( $field['name'], $field['title'], $field_callback, self::SLUG, $section_id, $field );

				/** Register field. */
				register_setting( self::SLUG, $field['name'] );
			}
		}
	}

	/**
	 * Generates HTML code for sections.
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
	public function section_callback( $arguments ) {
		echo "<h3 id='{$arguments['id']}'>{$arguments['title']}</h3>";
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
		} elseif ( 'select' === $arguments['type'] ) {
			echo '<select
					class="' . esc_attr( $arguments['class'] ) . '"
					name="' . esc_attr( $arguments['name'] ) . '"
				  >' . "\n";
			/** Scan array. */
			foreach ( $arguments['options'] as $key => $value ) {
				$selected = $key === $saved_value ? 'selected="selected" ' : '';
				echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . '>' . esc_attr( $value ) . '</option>' . "\n";
			}
			echo "</select>\n";
		} elseif ( 'select_with_text' === $arguments['type'] ) {
			echo '<select
					class="' . esc_attr( $arguments['class'] ) . '"
					name="' . esc_attr( $arguments['name'] ) . '"
				  >' . "\n";
			/** Scan array. */
			foreach ( $arguments['options'] as $key => $value ) {
				$selected = $key === $saved_value ? 'selected="selected" ' : '';
				echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . '>' . esc_attr( $value ) . '</option>' . "\n";
			}
			echo "</select>\n";

			/** Text input wrapper. */
			echo '<span class="select-with-text-wrapper">';

			/** Label. */
			echo "<span>{$arguments['text_title']}</span>";

			/** Input. */
			$saved_text_value = get_option( $arguments['text_name'] );
			echo '<input
					maxlength=1
			    	type="text"
			    	name="' . esc_attr( $arguments['text_name'] ) . '"
			    	value="' . esc_attr( $saved_text_value ) . '"
				/>';

			/** End wrapper. */
			echo '</span>';
		} elseif ( 'checkbox' === $arguments['type'] ) {
			echo '<input 
					type="hidden" 
					name="' . esc_attr( $arguments['name'] ) . '" 
					value="0" 
				/>';
			echo '<input
					' . ( $saved_value ? 'checked' : '' ) . '
					type="' . esc_attr( $arguments['type'] ) . '"
					name="' . esc_attr( $arguments['name'] ) . '"
					value="1"
				/>';
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
		/** Add menu item to the "Users" menu. */
		$page_title = 'Export Users to CSV';
		$menu_title = 'Export to CSV';
		$capability = 'list_users'; // who can see and access.
		$callback   = [ $this, 'settings_page_html' ];
		$hookname   = add_users_page( $page_title, $menu_title, $capability, self::SLUG, $callback );

		/** Load scripts/styles for this menu page. */
		add_action( 'load-' . $hookname, [ $this, 'load_admin_js_css' ] );

		/** Settings page: Register sections for the settings page. */
		add_action( 'load-' . $hookname, [ $this, 'settings_page_register_sections_callback' ] );
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
		wp_register_style( self::SLUG . 'select2css', $url, [], $version );
		wp_enqueue_style( self::SLUG . 'select2css' );

		$file_path_relative = '../node_modules/select2/dist/js/select2.min.js';
		$dir                = plugin_dir_path( __FILE__ );
		$url                = plugin_dir_url( __FILE__ ) . $file_path_relative;
		$file_path_absolute = $dir . $file_path_relative;
		$version            = filemtime( $file_path_absolute );
		wp_register_script( self::SLUG . 'select2js', $url, [], $version, true );
		wp_enqueue_script( self::SLUG . 'select2js' );

		/** index.css (index page style) */
		$file_path_relative = '../assets/index.css';
		$dir                = plugin_dir_path( __FILE__ );
		$url                = plugin_dir_url( __FILE__ ) . $file_path_relative;
		$file_path_absolute = $dir . $file_path_relative;
		$version            = filemtime( $file_path_absolute );
		wp_register_style( self::SLUG . 'indexcss', $url, [], $version );
		wp_enqueue_style( self::SLUG . 'indexcss' );

		/** index.js (index page script) */
		$file_path_relative = '../assets/index.js';
		$dir                = plugin_dir_path( __FILE__ );
		$url                = plugin_dir_url( __FILE__ ) . $file_path_relative;
		$file_path_absolute = $dir . $file_path_relative;
		$version            = filemtime( $file_path_absolute );
		wp_register_script( self::SLUG . 'indexjs', $url, [], $version, true );
		wp_enqueue_script( self::SLUG . 'indexjs' );
	}

	/**
	 * Called on form submit.
	 */
	public function generate_csv_callback() {
		/** HTTP Arguments. */
		$arg_use_custom_csv_settings = '1' === $_REQUEST[ self::FIELD_USE_CUSTOM_CSV_SETTINGS ] ? true : false;
		$arg_roles                   = empty( $_REQUEST[ self::FIELD_ROLES ] )
			? null
			: wp_unslash( $_REQUEST[ self::FIELD_ROLES ] );
		$arg_columns                 = empty( $_REQUEST[ self::FIELD_COLUMNS ] )
			? null
			: wp_unslash( $_REQUEST[ self::FIELD_COLUMNS ] );
		$arg_field_separator         = empty( $_REQUEST[ self::FIELD_SEPARATOR ] )
			? null
			: wp_unslash( $_REQUEST[ self::FIELD_SEPARATOR ] );
		$arg_custom_field_separator  = empty( $_REQUEST[ self::FIELD_CUSTOM_SEPARATOR ] )
			? null
			: wp_unslash( $_REQUEST[ self::FIELD_CUSTOM_SEPARATOR ] );
		$arg_text_qualifier          = empty( $_REQUEST[ self::FIELD_TEXT_QUALIFIER ] )
			? null
			: wp_unslash( $_REQUEST[ self::FIELD_TEXT_QUALIFIER ] );
		$arg_custom_text_qualifier   = empty( $_REQUEST[ self::FIELD_CUSTOM_TEXT_QUALIFIER ] )
			? null
			: wp_unslash( $_REQUEST[ self::FIELD_CUSTOM_TEXT_QUALIFIER ] );

		/** Check nonce. */
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], '_wpnonce' ) ) {
			return;
		}

		/** Check permissions. */
		if ( ! is_admin() || ! current_user_can( 'list_users' ) ) {
			return;
		}

		/** Save options. */
		update_option( self::FIELD_ROLES, isset( $arg_roles ) ? $arg_roles : '' );
		update_option( self::FIELD_COLUMNS, isset( $arg_columns ) ? $arg_columns : '' );
		update_option( self::FIELD_USE_CUSTOM_CSV_SETTINGS, $arg_use_custom_csv_settings );
		update_option( self::FIELD_SEPARATOR, $arg_field_separator );
		update_option( self::FIELD_CUSTOM_SEPARATOR, $arg_custom_field_separator );
		update_option( self::FIELD_TEXT_QUALIFIER, $arg_text_qualifier );
		update_option( self::FIELD_CUSTOM_TEXT_QUALIFIER, $arg_custom_text_qualifier );

		/** Output. */
		$roles = $arg_roles ? $arg_roles : []; /** Empty = all. */
		$users = get_users( [ 'role__in' => $roles ] );
		$this->export_users( $users );
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
		$columns = get_option( self::FIELD_COLUMNS );
		$columns = $columns ? $columns : $all_column_names;

		/** Field Separator and Text Qualifier. */
		$delimiter_char          = ',';
		$enclosure_char          = '"';
		$use_custom_csv_settings = get_option( self::FIELD_USE_CUSTOM_CSV_SETTINGS );
		if ( $use_custom_csv_settings ) {
			/** Field Separator (delimiter). */
			$delimiter = get_option( self::FIELD_SEPARATOR );
			switch ( $delimiter ) {
				case 'comma':
					$delimiter_char = ',';
					break;
				case 'semicolon':
					$delimiter_char = ';';
					break;
				case 'tab':
					$delimiter_char = "\t";
					break;
				case 'space':
					$delimiter_char = ' ';
					break;
				case 'custom':
					$custom_delimiter = get_option( self::FIELD_CUSTOM_SEPARATOR );
					$delimiter_char   = empty( $custom_delimiter ) ? ',' : $custom_delimiter;
					break;
			}

			/** Text Qualifier (enclosure). */
			$enclosure = get_option( self::FIELD_TEXT_QUALIFIER );
			switch ( $enclosure ) {
				case 'double-quote':
					$enclosure_char = '"';
					break;
				case 'quote':
					$enclosure_char = "'";
					break;
				case 'custom':
					$custom_enclosure = get_option( self::FIELD_CUSTOM_TEXT_QUALIFIER );
					$enclosure_char   = empty( $custom_enclosure ) ? '"' : $custom_enclosure;
					break;
			}
		}

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
		fputcsv( $hnd, $columns, $delimiter_char, $enclosure_char );

		/** Output data. */
		foreach ( $all_user_rows as $user ) {
			/** Add missing columns to the user. */
			$row = [];
			foreach ( $columns as $column ) {
				$row[ $column ] = array_key_exists( $column, $user ) ? $user[ $column ] : '';
			}

			/** Now that the user has the same columns as the header, outputs it. */
			fputcsv( $hnd, $row, $delimiter_char, $enclosure_char );
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
			<form class="<?php echo self::SLUG . '_form'; ?>" method="post" action="admin-post.php">
				<?php $nonce = wp_create_nonce( '_wpnonce' ); ?>
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
				<input type="hidden" name="action" value="<?php echo self::SLUG . '_export_users'; ?>">
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
