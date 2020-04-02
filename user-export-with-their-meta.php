<?php
/**
 * Plugin Name:     User Export (with their Meta Data)
 * Plugin URI:      https://github.com/loureirorg/wordpress-plugin-export-users
 * Description:     Export users to CSV.
 * Author:          Daniel Loureiro
 * Author URI:      https://learnwithdaniel.com/
 * Text Domain:     uewm
 * Domain Path:     /languages
 * Version:         0.5.0
 *
 * This file is a generic autoloader.
 * The entrypoint for this plugin is on "includes/class-main.php".
 *
 * @package UserExportWithMeta
 */

/** Our namespace */
namespace UserExportWithMeta;

/** Can't access it directly. */
defined( 'ABSPATH' ) || exit;

/**
 * Auto Loader (PSR-4).
 * Called by PHP whenever a class is not found.
 *
 * @param string $class_path The class name with optional namespacing path.
 *                           Ex.: MyNS/MyClass.
 *
 * @return void
 */
function autoloader( $class_path ) {
	/**
	 * Optimization: Leave if is not in our namespace.
	 */
	if ( strpos( $class_path, __NAMESPACE__ ) ) {
		return;
	}

	/** Get namespaces. Ex.: "A\B\C" -> Namespace "A\B", Class "C". */
	$namespace_list = explode( '\\', $class_path );
	$class_name     = array_pop( $namespace_list );

	/**
	 * If project namespace is named "myns", it's too verbose to put files on "includes/myns/...".
	 * Instead, let's put them on "includes/..." and supress our main namespace from the path name.
	 */
	if ( count( $namespace_list ) && __NAMESPACE__ === $namespace_list[0] ) {
		array_shift( $namespace_list );
	}

	/** "MyNamespace\some_RandomClass" is in the "my-namespace/class-some-random-class.php" file. */
	$class_name     = pascal_to_snake_case( $class_name );
	$class_name     = str_replace( '_', '-', $class_name ); /** Convert "_" on snake case to "-". */
	$namespace_list = array_map( __NAMESPACE__ . '\\pascal_to_snake_case', $namespace_list );

	/** Concat namespace items into a string, create a path and include the file. */
	$namespace_path = join( '/', $namespace_list );
	$namespace_path = str_replace( '_', '-', $namespace_path ); /** Convert "_" on snake case to "-". */
	$file_name      = join( '/', [ dirname( __FILE__ ), 'includes', $namespace_path, "class-{$class_name}.php" ] );
	$file_name      = strtolower( $file_name );
	if ( file_exists( $file_name ) ) { /** Check if file exists. */
		include_once $file_name; /** Includes the class file. */
	}
}

/**
 * Convert from PascalCase to snake_case.
 *
 * @param  String $input A PascalCase string.
 * @return String The $input converted to snake_case.
 */
function pascal_to_snake_case( $input ) {
	/** Input is on snake_case: exits forcing a lowercase. */
	if ( strpos( $input, '_' ) ) {
		return strtolower( $input );
	}

	/** Input is not on snake_case. Do conversion. */
	preg_match_all( '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches );
	$ret = $matches[0];
	foreach ( $ret as &$match ) {
		$match = strtoupper( $match ) === $match ? strtolower( $match ) : lcfirst( $match );
	}
	return implode( '_', $ret );
}

/** Register the autoloader on PHP. */
spl_autoload_register( implode( '\\', [ __NAMESPACE__, 'autoloader' ] ) );

/** Create our plugin instance. */
if ( ! class_exists( 'Main' ) ) {
	new Main();
}
