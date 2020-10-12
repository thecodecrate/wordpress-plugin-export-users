<?php
/**
 * Class Main.
 *   This is the entrypoint for our plugin.
 *
 * @package UserExportWithMeta
 */

/** Our namespace. */
namespace UserExportWithMeta;

/** Load composer libraries. */
require_once __DIR__ . '/../vendor/autoload.php';

/** Aliases. */
use SettingsAsWoocommerce\Submenu;

/** Can't access this file directly. */
defined( 'ABSPATH' ) || exit;

/**
 * Main class for the plugin.
 */
class Main {
	/**
	 * Entry-point for the plugin.
	 *
	 * @return void
	 */
	public function __construct() {
		/**
		 * Create the menu and its page.
		 * Code is on `class-settings-page.php`.
		 */
		$submenu = new Submenu( 'Export to CSV', 'uewm_settings', 'users' );
		$submenu
			->set_capability( 'list_users' )
			->set_page_title( 'Export Users to CSV' )
			->add_tab( new SettingsPage() );
	}
}
