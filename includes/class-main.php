<?php
/**
 * Class Main.
 *   This is the entrypoint for our plugin.
 *
 * TODO:
 * * [ ] Use composer's autoloader instead of our custom autoloader (classes have to be renamed to "CSV/Main/SettingsPage/WPUsers");
 * * [ ] Allow column sorting:
 *     * Drag n' drop is already working (jQuery's sortable on `index.js`), but
 *     * (1) The changes are not keeping if you save and reload; and
 *     * (2) It is not generating the file with the specified order.
 * * [ ] Fix PHPMD/PHPCS issues on files;
 * * [ ] Create unit tests for files in the "includes" folder;
 * * [ ] GitHub Action: One new WP versions, trigger testing and auto-update "Tested up to";
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
