<?php
/**
 * Plugin Name:     User Export (with their Meta Data)
 * Plugin URI:      https://github.com/loureirorg/wordpress-plugin-export-users
 * Description:     Export users to CSV.
 * Author:          Daniel Loureiro
 * Author URI:      https://learnwithdaniel.com/
 * Text Domain:     uewm
 * Domain Path:     /languages
 * Version:         0.1.8
 *
 * @package         User_Export_With_Meta
 */

defined( 'ABSPATH' ) || exit;

/**
 * Auto Loader (PSR-4).
 * Called by PHP whenever a class is not found.
 *
 * @param  String $class_name The class name.
 * @return void
 */
function uewm_autoload( $class_name ) {
	/** "A_NewClass" is in the "class-a-newclass.php" file. */
	$class_name = strtolower( $class_name );
	$class_name = str_replace( '_', '-', $class_name );
	$file_name  = dirname( __FILE__ ) . "/includes/class-{$class_name}.php";
	if ( file_exists( $file_name ) ) { // Check if file exists.
		/** Includes the class file. */
		include_once $file_name;
	}
}

/** Register the autoloader on PHP. */
spl_autoload_register( 'uewm_autoload' );

/** Create our plugin instance. */
User_Export_With_Meta::instance();
