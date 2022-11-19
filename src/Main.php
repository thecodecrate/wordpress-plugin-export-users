<?php
namespace UserExportWithMeta;

defined( 'ABSPATH' ) || exit;

use SettingsAsWoocommerce\Submenu;

class Main {
	public function __construct() {
		$submenu = new Submenu( 'Export to CSV', 'uewm_settings', 'users' );
		$submenu
			->set_capability( 'list_users' )
			->set_page_title( 'Export Users to CSV' )
			->add_tab( new SettingsPage() );
	}
}
