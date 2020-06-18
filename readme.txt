=== Export Users With Meta ===
Contributors: loureirorg
Tags: users, export, meta
Requires at least: 4.5
Tested up to: 5.4.2
Stable tag: 0.6.1
License: MIT
License URI: https://opensource.org/licenses/MIT

Export Users to CSV file (with their meta data).

== Description ==

A very simple plugin to export users to a CSV file. It exports the user's meta data too.

== Installation ==

1. Upload the plugin folder to the /wp-content/plugins/ directory.
1. Activate the plugin through the Plugins menu in WordPress.
1. Thats it! you can now configure the plugin.

== Frequently Asked Questions ==

= What about "Formula Injection" vulnerability? =

This plugin is protected against this vulnerability since version 0.5.0.

== Screenshots ==

1. Exporting users.

== Changelog ==

= 0.6.1 =
* [Patch] Remove dev vendor files (e.g. phpunit).

= 0.6.0 =
* [Minor] Memory usage improvements. It consumes all memory and throws a memory exception on more than 10k users on a 256mb standard installation. This improvement makes it never use more than 10mb of memory.

= 0.5.1 =
* [Bug] Exception due to type hinting on scalar types (PHP5.6<, PHP7 with PHP5 compability enabled). Fixed by updating "settings-as-woocommerce" library.

= 0.5.0 =
* [Bug] Fix CSV Injection (aka Formula Injection).
* [Bug] Not saving custom delimiter/qualifier chars.
* [Minor] Use custom SQL to get all columns (performance).

= 0.4.1 =
* [Bug] Page title is not showing on some WP versions since last update.

= 0.4.0 =
* [Minor] Plugin code refactored.

= 0.3.1 =
* [Bug] Not saving checkbox (bug introduced on version 0.2).

= 0.3.0 =
* [Minor] Better UTF-8 support: Adds a BOM character at the beginning of the file.

= 0.2.4 =
* Code completely refactored. Users shouldn't notice any change.

= 0.2.3 =
* Testing.

= 0.2.2 =
* Testing.

= 0.2.1 =
* Testing.

= 0.2.0 =
* Code completely refactored. Users shouldn't notice any change.

= 0.1.9 =
* Bug: Missing js/css files.

= 0.1.8 =
* New Feature: It is now possible to specify a field separator (; or , for instance), and a text qualifier (" or nothing, for instance).
* Tested on WordPress 5.3 version

= 0.1.7 =
* Fix performance issue. The "get_all_user_field_names()" method is running on ALL admin pages. This bug restricts this method to the export page only. See https://wordpress.org/support/topic/slow-query-get-all-users-on-each-page/

= 0.1.6 =
* PHP 5.6 Compatibillity: Removing PHP7 operators ?? and ?:.

= 0.1.5 =
* Security: Only users with the "list_users" permission can generate CSV. Before, any user with "manage_options" could (i.e. any admin user with permission to change settings).

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
