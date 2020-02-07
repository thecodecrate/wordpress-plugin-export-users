<?php
/**
 * Class WC.
 *   This is a MOCK class. Replaces the real WooCommerce methods for our WPPluginFramework.
 */

/** Our namespace */
namespace WPPluginFramework;

/**
 * Class WC.
 */
class WC extends Singleton {

	/**
	 * Our settings' slug.
	 *
	 * @var string $id.
	 */
	public static $id = 'wp_plugin_framework';

	/**
	 * Setter method for $id.
	 *
	 * @param string $id The slug.
	 */
	public static function set_id( $id ) {
		self::$id = $id;
	}

	/**
	 * Copied from WC:
	 *   https://docs.woocommerce.com/wc-apidocs/source-class-WooCommerce.html#245-263
	 *
	 * To update:
	 *   * Use code verbatim - NO CHANGES NEEDED.
	 *
	 * ------------------------------------------
	 *
	 * Returns true if the request is a non-legacy REST API request.
	 *
	 * Legacy REST requests should still run some extra code for backwards compatibility.
	 *
	 * @todo: replace this function once core WP function is available: https://core.trac.wordpress.org/ticket/42061.
	 *
	 * @return bool
	 */
	public function is_rest_api_request() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix         = trailingslashit( rest_get_url_prefix() );
		$is_rest_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) ); // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return apply_filters( 'woocommerce_is_rest_api_request', $is_rest_api_request );
	}

	/**
	 * Copied from WC:
	 *   https://github.com/woocommerce/woocommerce/blob/a7d57a248ed03dac7bad119e71f83dd961f43cb3/includes/admin/class-wc-admin-settings.php#L733
	 *
	 * To update:
	 *   * Copy and Paste original code;
	 *   * Replace "woocommerce" references on filters and actions with "self::$id";
	 *   * Replace "array_map( 'wc_clean', ...)" reference with "array_map( __NAMESPACE__ . 'wc_clean', ...)";
	 *
	 * ------------------------------------------
	 *
	 * Save admin fields.
	 *
	 * Loops though the woocommerce options array and outputs each field.
	 *
	 * @param array $options Options array to output.
	 * @param array $data    Optional. Data to use for saving. Defaults to $_POST.
	 * @return bool
	 */
	public static function save_fields( $options, $data = null ) {
		if ( is_null( $data ) ) {
			$data = $_POST; // WPCS: input var okay, CSRF ok.
		}
		if ( empty( $data ) ) {
			return false;
		}

		// Options to update will be stored here and saved later.
		$update_options   = array();
		$autoload_options = array();

		// Loop options and get values to save.
		foreach ( $options as $option ) {
			if ( ! isset( $option['id'] ) || ! isset( $option['type'] ) || ( isset( $option['is_option'] ) && false === $option['is_option'] ) ) {
				continue;
			}

			// Get posted value.
			if ( strstr( $option['id'], '[' ) ) {
				parse_str( $option['id'], $option_name_array );
				$option_name  = current( array_keys( $option_name_array ) );
				$setting_name = key( $option_name_array[ $option_name ] );
				$raw_value    = isset( $data[ $option_name ][ $setting_name ] ) ? wp_unslash( $data[ $option_name ][ $setting_name ] ) : null;
			} else {
				$option_name  = $option['id'];
				$setting_name = '';
				$raw_value    = isset( $data[ $option['id'] ] ) ? wp_unslash( $data[ $option['id'] ] ) : null;
			}

			// Format the value based on option type.
			switch ( $option['type'] ) {
				case 'checkbox':
					$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
					break;
				case 'textarea':
					$value = wp_kses_post( trim( $raw_value ) );
					break;
				case 'multiselect':
				case 'multi_select_countries':
					$value = array_filter( array_map( __NAMESPACE__ . '\wc_clean', (array) $raw_value ) );
					break;
				case 'image_width':
					$value = array();
					if ( isset( $raw_value['width'] ) ) {
						$value['width']  = wc_clean( $raw_value['width'] );
						$value['height'] = wc_clean( $raw_value['height'] );
						$value['crop']   = isset( $raw_value['crop'] ) ? 1 : 0;
					} else {
						$value['width']  = $option['default']['width'];
						$value['height'] = $option['default']['height'];
						$value['crop']   = $option['default']['crop'];
					}
					break;
				case 'select':
					$allowed_values = empty( $option['options'] ) ? array() : array_map( 'strval', array_keys( $option['options'] ) );
					if ( empty( $option['default'] ) && empty( $allowed_values ) ) {
						$value = null;
						break;
					}
					$default = ( empty( $option['default'] ) ? $allowed_values[0] : $option['default'] );
					$value   = in_array( $raw_value, $allowed_values, true ) ? $raw_value : $default;
					break;
				case 'relative_date_selector':
					$value = wc_parse_relative_date_option( $raw_value );
					break;
				default:
					$value = wc_clean( $raw_value );
					break;
			}

			/**
			 * Fire an action when a certain 'type' of field is being saved.
			 *
			 * @deprecated 2.4.0 - doesn't allow manipulation of values!
			 */
			if ( has_action( self::$id . '_update_option_' . sanitize_title( $option['type'] ) ) ) {
				wc_deprecated_function( 'The ' . self::$id . '_update_option_X action', '2.4.0', self::$id . '_admin_settings_sanitize_option filter' );
				do_action( self::$id . '_update_option_' . sanitize_title( $option['type'] ), $option );
				continue;
			}

			/**
			 * Sanitize the value of an option.
			 *
			 * @since 2.4.0
			 */
			$value = apply_filters( self::$id . '_admin_settings_sanitize_option', $value, $option, $raw_value );

			/**
			 * Sanitize the value of an option by option name.
			 *
			 * @since 2.4.0
			 */
			$value = apply_filters( self::$id . "_admin_settings_sanitize_option_$option_name", $value, $option, $raw_value );

			if ( is_null( $value ) ) {
				continue;
			}

			// Check if option is an array and handle that differently to single values.
			if ( $option_name && $setting_name ) {
				if ( ! isset( $update_options[ $option_name ] ) ) {
					$update_options[ $option_name ] = get_option( $option_name, array() );
				}
				if ( ! is_array( $update_options[ $option_name ] ) ) {
					$update_options[ $option_name ] = array();
				}
				$update_options[ $option_name ][ $setting_name ] = $value;
			} else {
				$update_options[ $option_name ] = $value;
			}

			$autoload_options[ $option_name ] = isset( $option['autoload'] ) ? (bool) $option['autoload'] : true;

			/**
			 * Fire an action before saved.
			 *
			 * @deprecated 2.4.0 - doesn't allow manipulation of values!
			 */
			do_action( self::$id . '_update_option', $option );
		}

		// Save all options in our array.
		foreach ( $update_options as $name => $value ) {
			update_option( $name, $value, $autoload_options[ $name ] ? 'yes' : 'no' );
		}

		return true;
	}
}

/**
 * Wrapper: Gets the WC() singleton instance.
 *   This function is on a namespace, so it doesn't conflicts with the real WC() function.
 *
 */
function WC() {
	return WC::get_instance();
}

/**
 * Copied from WC:
 *   https://github.com/woocommerce/woocommerce/blob/master/includes/wc-formatting-functions.php#L386
 *
 * To update:
 *   * Copy and paste original code;
 *   * Replace "wp_clean" reference with "__NAMESPACE__ . '\wc_clean'"
 *
 * ------------------------------------------
 *
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function wc_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( __NAMESPACE__ . '\wc_clean', $var );
	}
	return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
}

/**
 * Copied from WC:
 *   https://docs.woocommerce.com/wp-content/images/wc-apidocs/source-function-wc_parse_relative_date_option.html#1416-1446
 *
 * To update:
 *   * Use code verbatim - NO CHANGES NEEDED.
 *
 * ------------------------------------------
 *
 * Parse a relative date option from the settings API into a standard format.
 *
 * @since 3.4.0
 * @param mixed $raw_value Value stored in DB.
 * @return array Nicely formatted array with number and unit values.
 */
function wc_parse_relative_date_option( $raw_value ) {
	$periods = array(
		'days'   => __( 'Day(s)', 'woocommerce' ),
		'weeks'  => __( 'Week(s)', 'woocommerce' ),
		'months' => __( 'Month(s)', 'woocommerce' ),
		'years'  => __( 'Year(s)', 'woocommerce' ),
	);

	$value = wp_parse_args(
		(array) $raw_value,
		array(
			'number' => '',
			'unit'   => 'days',
		)
	);

	$value['number'] = ! empty( $value['number'] ) ? absint( $value['number'] ) : '';

	if ( ! in_array( $value['unit'], array_keys( $periods ), true ) ) {
		$value['unit'] = 'days';
	}

	return $value;
}

/**
 * Copied from WC:
 *   https://docs.woocommerce.com/wc-apidocs/source-function-wc_deprecated_function.html#36-55
 *
 * To update:
 *   * Use code verbatim - NO CHANGES NEEDED.
 *
 * ------------------------------------------
 *
 * Wrapper for deprecated functions so we can apply some extra logic.
 *
 * @since 3.0.0
 * @param string $function Function used.
 * @param string $version Version the message was added in.
 * @param string $replacement Replacement for the called function.
 */
function wc_deprecated_function( $function, $version, $replacement = null ) {
	// @codingStandardsIgnoreStart
	if ( is_ajax() || WC()->is_rest_api_request() ) {
		do_action( 'deprecated_function_run', $function, $replacement, $version );
		$log_string  = "The {$function} function is deprecated since version {$version}.";
		$log_string .= $replacement ? " Replace with {$replacement}." : '';
		error_log( $log_string );
	} else {
		_deprecated_function( $function, $version, $replacement );
	}
	// @codingStandardsIgnoreEnd
}
