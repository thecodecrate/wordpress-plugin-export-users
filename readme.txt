=== Export Users With Meta ===
Contributors: loureirorg
Tags: users, export
Requires at least: 4.5
Tested up to: 5.2.2
Stable tag: 0.1.4
License: MIT
License URI: https://opensource.org/licenses/MIT

Export Users to CSV file (with their meta data).

== Description ==

This plugin exports all your users to a CSV file. It exports the user's meta data too. This means it works with WooCommerce and other plugins that store extra information on the users table.

== Installation ==

1. Upload the plugin folder to the /wp-content/plugins/ directory.
1. Activate the plugin through the Plugins menu in WordPress.
1. Thats it! you can now configure the plugin.

== Frequently Asked Questions ==

= Does it work with WooCommerce? =

Yes.

== Screenshots ==

1. Exporting users.

== Changelog ==

= 0.1.4 =
* Version bump.

= 0.1.3 =
* Version bump.

= 0.1.2 =
* Code Refactoring: Replaces the "router" implementation (an implementation that intercepts ALL http requests) with a "admin_post_{$action}" one. It reduces the code size and the code also becomes more WP-like and clear.
* Code Refactoring II: Avoids potential conflict with other plugins by changing the action name from a generic "export-users" to "uewm_export_users".

= 0.1.1 =
* Adding an icon for this plugin.

= 0.1.0 =
* First Version.
