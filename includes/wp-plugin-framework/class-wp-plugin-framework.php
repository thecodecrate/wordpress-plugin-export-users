<?php
/**
 * Class WPPluginFramework.
 *  A generic framework for plugin development.
 *
 * TODO:
 *  * [ ] "Options" format should be WC (an 1D vector) instead of our custom sections/fields structure;
 *  * [ ] Rename "slug" to "id" to maintain consistency with class-wc;
 *  * [ ] Field HTML generator (field_callback) should be copied from WC;
 *  * [ ] WC class should not be singleton and its methods should be non-static, so we can have multiple instances;
 *  * [ ] Automatize the process of updating code from WC repo;
 *  * [ ] Unit tests;
 *  * [ ] Take care of PHPCS / PHMD warnings;
 *  * [ ] Make usage / API more similar to WC;
 *  * [ ] Create a README.md / Publish on GitHub / Publish on Packagist;
 */

/** Our namespace */
namespace WPPluginFramework;

/** Aliases. */
use WPPluginFramework\WC;

/**
 * Class WPPluginFramework.
 */
class WPPluginFramework extends Singleton {

	/**
	 * Plugin's slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Menu definitions.
	 */
	public $root_menu;
	public $menu_title;
	public $menu_capability;
	public $page_title;
	public $settings_fields;
	public $save_callback;
	public $save_button_title;

	/**
	 * Constructor.
	 */
	public function __construct( $slug ) {
		/** Force slug to be defined. */
		$this->set_slug( $slug );
	}

	/**
	 * Set the plugin's slug.
	 *
	 * @param String $slug The plugin's slug.
	 *
	 * @return void
	 */
	public function set_slug( $slug ) {
		$this->slug = $slug;
	}

	/**
	 * Set the menu.
	 *
	 */
	public function set_menu( $root_menu = 'users', $menu_title, $page_title, $menu_capability, $settings_fields, $save_callback, $save_button_title = 'Save' ) {
		/**
		 * Save properties.
		 */
		$this->root_menu         = $root_menu;
		$this->menu_title        = $menu_title;
		$this->menu_capability   = $menu_capability;
		$this->page_title        = $page_title;
		$this->settings_fields   = $settings_fields;
		$this->save_callback     = $save_callback;
		$this->save_button_title = $save_button_title;

		/** Add menu item: Step 1/3 - Listen to the event. */
		add_action( 'admin_menu', [ $this, 'admin_menu_callback' ] );

		/** Fired on "Save" button. */
		add_action( "admin_post_{$this->slug}_save", $this->save_callback );
	}

	/** Add menu item: Step 3/3 - The page HTML. */
	public function settings_page_html_callback() {
		$nonce = wp_create_nonce( '_wpnonce' );
		?>
		<div class="wrap">
			<h2><?php echo $this->page_title; ?></h2>
			<form class="<?php echo "{$this->slug}_form"; ?>" method="post" action="admin-post.php">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
				<input type="hidden" name="action" value="<?php echo "{$this->slug}_save"; ?>">
				<?php do_settings_sections( $this->slug ); ?>
				<?php submit_button( $this->save_button_title ); ?>
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
	 * Add menu item: Step 3/3 - Call "add_options_page()".
	 *
	 * @return void
	 */
	public function admin_menu_callback() {
		/** Add menu item to the WP menu. */
		$html_callback = [ $this, 'settings_page_html_callback' ];
		$hookname      = add_users_page(
			$this->page_title,
			$this->menu_title,
			$this->menu_capability,
			$this->slug,
			$html_callback
		);

		/** Load scripts/styles for this menu page. */
		add_action( "load-{$hookname}", [ $this, 'load_admin_js_css_callback' ] );

		/** Settings page: Register sections for the settings page. */
		add_action( "load-{$hookname}", [ $this, 'register_fields_callback' ] );
	}

	/**
	 * Load scripts/styles for the menu page.
	 *
	 * @return void
	 */
	public function load_admin_js_css_callback() {
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
		$this->load_relative_css( '../../node_modules/select2/dist/css/select2.min.css' );
		$this->load_relative_js( '../../node_modules/select2/dist/js/select2.min.js' );
		$this->load_relative_css( '../../assets/index.css' ); /** index.css (index page style). */
		$this->load_relative_js( '../../assets/index.js' ); /** index.js (index page script). */
	}

	/**
	 * Settings page: Register sections for the settings page.
	 *
	 * @return void
	 */
	public function register_fields_callback() {
		/** Check permissions. */
		if ( ! current_user_can( $this->menu_capability ) ) {
			return;
		}

		/** Call Settings API to generate sections and fields. */
		$section_callback = [ $this, 'section_callback' ];
		$field_callback   = [ $this, 'field_callback' ];
		foreach ( $this->settings_fields as $section ) {
			/** Adds section. */
			$section_id = $this->slug . '_' . $section['id'];
			add_settings_section( $section_id, $section['title'], $section_callback, $this->slug );
			foreach ( $section['fields'] as $field ) {
				/** Adds field. */
				add_settings_field( $field['id'], $field['title'], $field_callback, $this->slug, $section_id, $field );

				/** Register field. */
				register_setting( $this->slug, $field['id'] );
			}
		}
	}

	/**
	 * Helper method: load js.
	 * Path must be relative to the current file.
	 *
	 * @param string $file_path_relative File path, relative to the current file.
	 * @param string $handle Passed to wp_register. If null, generates one based on the filename.
	 *
	 * @return void
	 */
	public function load_relative_js( $file_path_relative, $handle = null ) {
		$dir                = plugin_dir_path( __FILE__ );
		$url                = plugin_dir_url( __FILE__ ) . $file_path_relative;
		$file_path_absolute = $dir . $file_path_relative;

		/** Check if file exists. */
		if ( ! file_exists( $file_path_absolute ) ) {
			return;
		}

		$version = filemtime( $file_path_absolute );
		$handle  = null === $handle ? basename( $file_path_absolute ) : $handle;
		wp_register_script( "{$this->slug}_{$handle}", $url, [], $version, true );
		wp_enqueue_script( "{$this->slug}_{$handle}" );
	}

	/**
	 * Helper method: load css.
	 * Path must be relative to the current file.
	 *
	 * @param string $file_path_relative File path, relative to the current file.
	 * @param string $handle Passed to wp_register. If null, generates one based on the filename.
	 *
	 * @return void
	 */
	public function load_relative_css( $file_path_relative, $handle = null ) {
		$dir                = plugin_dir_path( __FILE__ );
		$url                = plugin_dir_url( __FILE__ ) . $file_path_relative;
		$file_path_absolute = $dir . $file_path_relative;

		/** Check if file exists. */
		if ( ! file_exists( $file_path_absolute ) ) {
			return;
		}

		$version = filemtime( $file_path_absolute );
		$handle  = null === $handle ? basename( $file_path_absolute ) : $handle;
		wp_register_style( "{$this->slug}_{$handle}", $url, [], $version );
		wp_enqueue_style( "{$this->slug}_{$handle}" );
	}

	/**
	 * Generates HTML code for sections.
	 *
	 * @param array $arguments Associative array.
	 *    $arguments = [
	 *      'title'   => (string) H3 Title. Required.
	 *      'id'      => (string) Input attribute "id". Required.
	 *    ].
	 *
	 * @return void
	 */
	public function section_callback( $arguments ) {
		echo "<h3 id='{$arguments['id']}'>{$arguments['title']}</h3>";
	}

	/**
	 * Generates HTML code for a given input.
	 *
	 * @param array $arguments Associative array.
	 *    $arguments = [
	 *      'type'             => (string) Input type (text, select, hidden, etc). Required.
	 *      'id'               => (string) Input attribute "name". Required.
	 *      'class'            => (string) Input attribute "class". Optional.
	 *      'options'          => (array) Options for the select. Required for types "select" and "multiselect".
	 *      'options_callback' => (function) Alternative to "options". Used for slow-generated/recurse intensive options.
	 *    ].
	 *
	 * @return void
	 */
	public function field_callback( $arguments ) {
		/** Value that is on the DB. */
		$field_name = $this->slug . '_' . $arguments['id'];
		$saved_value = get_option( $field_name );

		/** Output HTML according to the type. */
		switch ( $arguments['type'] ) {
			case 'multiselect':
				$saved_value = $saved_value ? $saved_value : []; /** If null, convert it to array. */
				$saved_value = is_scalar( $saved_value ) ? [ $saved_value ] : $saved_value ; /** If scalar, covert it to array. */
				echo '<select
						class="' . esc_attr( $arguments['class'] ) . '"
						name="' . esc_attr( $field_name ) . '[]"
						multiple="multiple"
					>' . "\n";
				/** Scan array. */
				$options = isset( $arguments['options_callback'] ) ? $arguments['options_callback']() : $arguments['options'];
				foreach ( $options as $key => $value ) {
					$selected = in_array( $key, $saved_value, true ) ? 'selected="selected" ' : '';
					echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . '>' . esc_attr( $value ) . '</option>' . "\n";
				}
				echo "</select>\n";
				break;

			case 'select':
				echo '<select
						class="' . esc_attr( $arguments['class'] ) . '"
						name="' . esc_attr( $field_name ) . '"
					>' . "\n";
				/** Scan array. */
				$options = isset( $arguments['options_callback'] ) ? $arguments['options_callback']() : $arguments['options'];
				foreach ( $options as $key => $value ) {
					$selected = $key === $saved_value ? 'selected="selected" ' : '';
					echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . '>' . esc_attr( $value ) . '</option>' . "\n";
				}
				echo "</select>\n";
				break;

			case 'select_with_text':
				echo '<select
						class="' . esc_attr( $arguments['class'] ) . '"
						name="' . esc_attr( $field_name ) . '"
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
				$saved_text_value = get_option( $this->slug . '_' . $arguments['text_name'] );
				echo '<input
						maxlength=1
						type="text"
						name="' . esc_attr( $this->slug . '_' . $arguments['text_name'] ) . '"
						value="' . esc_attr( $saved_text_value ) . '"
					/>';

				/** End wrapper. */
				echo '</span>';
				break;

			case 'checkbox':
				echo '<input
						type="hidden"
						name="' . esc_attr( $field_name ) . '"
						value="0"
					/>';
				echo '<input
						' . ( $saved_value ? 'checked' : '' ) . '
						type="' . esc_attr( $arguments['type'] ) . '"
						name="' . esc_attr( $field_name ) . '"
						value="1"
					/>';
				break;

			default:
				echo '<input
						type="' . esc_attr( $arguments['type'] ) . '"
						name="' . esc_attr( $field_name ) . '"
						value="' . esc_attr( $saved_value ) . '"
					/>';
		}
	}


	/**
	 * Save admin fields.
	 *
	 */
	public function update_options( $options ) {
		/** Check nonce. */
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], '_wpnonce' ) ) {
			return;
		}

		/** Check permissions. */
		if ( ! is_admin() || ! current_user_can( $this->menu_capability ) ) {
			return;
		}

		/** Convert our structure to WC format. */
		$fields = array();
		foreach ( $options as $section ) {
			foreach ( $section['fields'] as $field ) {
				$field['id'] = $this->slug . '_' . $field['id']; /** prefix field name with slug. */
				$fields[]    = $field;
			}
		}

		/** Bugfix: a "select" with "options_callback" is not being saved due to WC's sanitization. */
		foreach ( $fields as $field ) {
			if ( isset( $field['options_callback'] ) ) {
				$option_name = $this->slug . '_' . $field['id'];
				add_filter( "{$this->slug}_admin_settings_sanitize_option_$option_name", function ( $value, $option, $raw_value ) {
					return $raw_value;
				} );
			}
		}

		/** Save. */
		WC::set_id( $this->slug );
		WC::save_fields( $fields );
	}
}
