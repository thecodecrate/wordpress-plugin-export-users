<?php
/**
 * Plugin Name:     User Export (with their Meta Data)
 * Plugin URI:      https://github.com/loureirorg/wordpress-plugin-export-users
 * Description:     Export users to CSV.
 * Author:          Daniel Loureiro
 * Author URI:      https://learnwithdaniel.com/
 * Text Domain:     uewm
 * Domain Path:     /languages
 * Version:         0.6.9
 *
 * The entrypoint for this plugin is on "src/Main.php".
 */
namespace UserExportWithMeta;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

/** Create our plugin instance. */
if ( ! class_exists( 'Main' ) ) {
	new Main();
}
